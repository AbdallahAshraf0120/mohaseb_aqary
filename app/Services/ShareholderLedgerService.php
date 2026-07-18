<?php

namespace App\Services;

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
     *     project_id: int,
     *     type: string,
     *     amount: float|int|string,
     *     entry_date: string,
     *     notes?: string|null,
     *     direction?: string|null,
     *     skip_cashbox?: bool
     * }  $data
     */
    public function create(Shareholder $shareholder, array $data, ?User $user = null): ShareholderLedgerEntry
    {
        $type = (string) $data['type'];
        if (! array_key_exists($type, ShareholderLedgerEntry::TYPES)) {
            throw new InvalidArgumentException('نوع حركة الجاري غير صالح.');
        }

        $projectId = (int) ($data['project_id'] ?? 0);
        if ($projectId <= 0) {
            throw new InvalidArgumentException('يجب اختيار المشروع الذي تُوجَّه إليه الحركة.');
        }

        $membership = ProjectShareholder::query()
            ->where('shareholder_id', (int) $shareholder->id)
            ->where('project_id', $projectId)
            ->first();
        if ($membership === null) {
            throw new InvalidArgumentException('هذا المساهم غير مرتبط بالمشروع المحدد.');
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
        $affectCashbox = ! $skipCashbox && ShareholderLedgerEntry::affectsCashbox($type);

        return DB::transaction(function () use ($shareholder, $projectId, $type, $direction, $amount, $data, $user, $affectCashbox): ShareholderLedgerEntry {
            app(CurrentProject::class)->force($projectId);

            $entry = ShareholderLedgerEntry::withoutProjectScope()->create([
                'project_id' => $projectId,
                'shareholder_id' => (int) $shareholder->id,
                'type' => $type,
                'direction' => $direction,
                'amount' => $amount,
                'entry_date' => $data['entry_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->id,
            ]);

            if ($affectCashbox) {
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
                $this->syncProjectInvestment($shareholder, $projectId);
            }

            return $entry->fresh(['treasuryTransaction', 'creator', 'project:id,name']) ?? $entry;
        });
    }

    public function delete(ShareholderLedgerEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $shareholder = $entry->shareholder()->first();
            $projectId = (int) $entry->project_id;
            $wasCapital = $entry->type === ShareholderLedgerEntry::TYPE_CAPITAL;
            $treasuryId = $entry->treasury_transaction_id;

            app(CurrentProject::class)->force($projectId);
            $entry->delete();

            if ($treasuryId) {
                TreasuryTransaction::withoutProjectScope()
                    ->whereKey($treasuryId)
                    ->where('reference_type', 'shareholder_ledger_entry')
                    ->delete();
            }

            if ($wasCapital && $shareholder) {
                $this->syncProjectInvestment($shareholder, $projectId);
            }
        });
    }

    public function syncProjectInvestment(Shareholder $shareholder, int $projectId): void
    {
        $total = $shareholder->capitalDepositsTotal($projectId);
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
}
