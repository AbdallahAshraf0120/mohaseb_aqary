<?php

namespace App\Services;

use App\Models\LandParcel;
use App\Models\LandParcelShareholder;
use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Support\CurrentProject;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShareholderLedgerService
{
    /**
     * @param  array{
     *     project_id?: int|null,
     *     land_parcel_id?: int|null,
     *     type: string,
     *     amount: float|int|string,
     *     entry_date: string,
     *     notes?: string|null,
     *     direction?: string|null,
     *     skip_cashbox?: bool,
     *     land_parcel_payment_id?: int|null
     * }  $data
     */
    public function create(Shareholder $shareholder, array $data, ?User $user = null): ShareholderLedgerEntry
    {
        $type = (string) $data['type'];
        if (! array_key_exists($type, ShareholderLedgerEntry::TYPES)) {
            throw new InvalidArgumentException('نوع حركة الجاري غير صالح.');
        }

        $projectId = isset($data['project_id']) && $data['project_id'] !== null && $data['project_id'] !== ''
            ? (int) $data['project_id']
            : null;
        $landParcelId = isset($data['land_parcel_id']) && $data['land_parcel_id'] !== null && $data['land_parcel_id'] !== ''
            ? (int) $data['land_parcel_id']
            : null;

        if (($projectId === null) === ($landParcelId === null)) {
            throw new InvalidArgumentException('يجب اختيار مشروع أو أرض (واحد فقط) كوجهة للحركة.');
        }

        if ($projectId !== null) {
            $membership = ProjectShareholder::query()
                ->where('shareholder_id', (int) $shareholder->id)
                ->where('project_id', $projectId)
                ->first();
            if ($membership === null) {
                throw new InvalidArgumentException('هذا المساهم غير مرتبط بالمشروع المحدد.');
            }
        } else {
            $membership = LandParcelShareholder::query()
                ->where('shareholder_id', (int) $shareholder->id)
                ->where('land_parcel_id', $landParcelId)
                ->first();
            if ($membership === null) {
                throw new InvalidArgumentException('هذا المساهم غير مرتبط بالأرض المحددة.');
            }
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        $direction = $type === ShareholderLedgerEntry::TYPE_ADJUSTMENT
            ? (string) ($data['direction'] ?? '')
            : ShareholderLedgerEntry::defaultDirectionForType($type);

        if (! in_array($direction, [
            ShareholderLedgerEntry::DIRECTION_CREDIT,
            ShareholderLedgerEntry::DIRECTION_DEBIT,
        ], true)) {
            throw new InvalidArgumentException('اتجاه التسوية غير صالح.');
        }

        $skipCashbox = (bool) ($data['skip_cashbox'] ?? false);
        // الصندوق مرتبط بالمشاريع فقط؛ حركات الأراضي لا تُنشئ حركة خزينة مشروع
        $affectCashbox = $projectId !== null
            && ! $skipCashbox
            && ShareholderLedgerEntry::affectsCashbox($type);

        return DB::transaction(function () use (
            $shareholder,
            $projectId,
            $landParcelId,
            $type,
            $direction,
            $amount,
            $data,
            $user,
            $affectCashbox
        ): ShareholderLedgerEntry {
            if ($projectId !== null) {
                app(CurrentProject::class)->force($projectId);
            } else {
                app(CurrentProject::class)->force(null);
            }

            $entry = ShareholderLedgerEntry::withoutProjectScope()->create([
                'project_id' => $projectId,
                'land_parcel_id' => $landParcelId,
                'land_parcel_payment_id' => isset($data['land_parcel_payment_id']) ? (int) $data['land_parcel_payment_id'] : null,
                'shareholder_id' => (int) $shareholder->id,
                'type' => $type,
                'direction' => $direction,
                'amount' => $amount,
                'entry_date' => $data['entry_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->id,
            ]);

            if ($affectCashbox && $projectId !== null) {
                $cashboxType = ShareholderLedgerEntry::cashboxTypeFor($type, $direction);
                $isAdmin = $user instanceof User && $user->isAdmin();
                $tx = TreasuryTransaction::withoutProjectScope()->create([
                    'project_id' => $projectId,
                    'type' => $cashboxType,
                    'amount' => $amount,
                    'reference_type' => 'shareholder_ledger_entry',
                    'reference_id' => (int) $entry->id,
                    'description' => sprintf(
                        'جاري مساهم — %s — %s',
                        $entry->typeLabel(),
                        $shareholder->name
                    ),
                    'approval_status' => $isAdmin ? 'approved' : 'pending',
                    'approved_at' => $isAdmin ? now() : null,
                    'approved_by' => $isAdmin ? (int) $user->id : null,
                ]);
                $entry->update(['treasury_transaction_id' => (int) $tx->id]);
            }

            if ($type === ShareholderLedgerEntry::TYPE_CAPITAL) {
                if ($projectId !== null) {
                    $this->syncProjectInvestment($shareholder, $projectId);
                } else {
                    $this->syncLandInvestment($shareholder, (int) $landParcelId);
                }
            }

            return $entry->fresh(['treasuryTransaction', 'creator', 'project:id,name', 'landParcel:id,name']) ?? $entry;
        });
    }

    public function delete(ShareholderLedgerEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $shareholder = $entry->shareholder()->first();
            $projectId = $entry->project_id !== null ? (int) $entry->project_id : null;
            $landParcelId = $entry->land_parcel_id !== null ? (int) $entry->land_parcel_id : null;
            $wasCapital = $entry->type === ShareholderLedgerEntry::TYPE_CAPITAL;
            $treasuryId = $entry->treasury_transaction_id;

            if ($projectId !== null) {
                app(CurrentProject::class)->force($projectId);
            }
            $entry->delete();

            if ($treasuryId) {
                TreasuryTransaction::withoutProjectScope()
                    ->whereKey($treasuryId)
                    ->where('reference_type', 'shareholder_ledger_entry')
                    ->delete();
            }

            if ($wasCapital && $shareholder) {
                if ($projectId !== null) {
                    $this->syncProjectInvestment($shareholder, $projectId);
                } elseif ($landParcelId !== null) {
                    $this->syncLandInvestment($shareholder, $landParcelId);
                }
            }
        });
    }

    public function syncProjectInvestment(Shareholder $shareholder, int $projectId): void
    {
        $total = $shareholder->capitalDepositsTotal($projectId, null);
        $project = Project::query()->find($projectId);
        $percentage = $project
            ? $project->shareholderPercentageForInvestment($total)
            : 0.0;

        ProjectShareholder::query()->updateOrCreate(
            [
                'shareholder_id' => (int) $shareholder->id,
                'project_id' => $projectId,
            ],
            [
                'total_investment' => $total,
                'share_percentage' => $percentage,
            ]
        );
    }

    public function syncLandInvestment(Shareholder $shareholder, int $landParcelId): void
    {
        $total = $shareholder->capitalDepositsTotal(null, $landParcelId);
        $parcel = LandParcel::query()->find($landParcelId);
        $percentage = $parcel
            ? $parcel->shareholderPercentageForInvestment($total)
            : 0.0;

        LandParcelShareholder::query()->updateOrCreate(
            [
                'shareholder_id' => (int) $shareholder->id,
                'land_parcel_id' => $landParcelId,
            ],
            [
                'total_investment' => $total,
                'share_percentage' => $percentage,
            ]
        );
    }
}
