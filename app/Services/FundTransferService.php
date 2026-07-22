<?php

namespace App\Services;

use App\Models\FundTransfer;
use App\Models\LandParcel;
use App\Models\Project;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Support\LandTradingCashbox;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FundTransferService
{
    public function __construct(
        private readonly ShareholderLedgerService $shareholderLedgerService,
        private readonly CashboxBalanceService $cashboxBalanceService,
    ) {}

    public function balanceFor(string $type, int $id): float
    {
        $projectId = $this->resolveProjectId($type, $id);

        return $this->cashboxBalanceService->approvedBalance($projectId);
    }

    /**
     * @param  array{
     *     from_type: string,
     *     from_id: int,
     *     to_type: string,
     *     to_id: int,
     *     amount: float|int|string,
     *     transferred_at: string,
     *     notes?: string|null,
     *     shareholder_id?: int|null,
     *     source_land_parcel_id?: int|null
     * }  $data
     */
    public function transfer(array $data, ?User $user = null): FundTransfer
    {
        $fromType = (string) $data['from_type'];
        $toType = (string) $data['to_type'];
        $fromId = (int) $data['from_id'];
        $toId = (int) $data['to_id'];
        $amount = round((float) $data['amount'], 2);

        $this->assertEndpoint($fromType, $fromId);
        $this->assertEndpoint($toType, $toId);

        if ($fromType === $toType && $fromId === $toId) {
            throw new InvalidArgumentException('لا يمكن التحويل لنفس الصندوق.');
        }

        if ($amount < 0.01) {
            throw new InvalidArgumentException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        $fromProjectId = $this->resolveProjectId($fromType, $fromId);
        $this->cashboxBalanceService->assertCanSpend($fromProjectId, $amount);

        $shareholderId = isset($data['shareholder_id']) && $data['shareholder_id'] !== null && $data['shareholder_id'] !== ''
            ? (int) $data['shareholder_id']
            : null;
        $sourceParcelId = isset($data['source_land_parcel_id']) && $data['source_land_parcel_id'] !== null && $data['source_land_parcel_id'] !== ''
            ? (int) $data['source_land_parcel_id']
            : null;

        if ($sourceParcelId !== null && ! LandParcel::query()->whereKey($sourceParcelId)->exists()) {
            throw new InvalidArgumentException('الأرض المصدر غير موجودة.');
        }

        $shareholder = null;
        if ($shareholderId !== null) {
            $shareholder = Shareholder::query()->find($shareholderId);
            if (! $shareholder instanceof Shareholder) {
                throw new InvalidArgumentException('المساهم غير موجود.');
            }
        }

        $toProjectId = $this->resolveProjectId($toType, $toId);
        $isAdmin = $user instanceof User && $user->isAdmin();
        $date = (string) $data['transferred_at'];
        $notes = $data['notes'] ?? null;

        $fromLabel = $this->endpointLabel($fromType, $fromId);
        $toLabel = $this->endpointLabel($toType, $toId);

        return DB::transaction(function () use (
            $fromType,
            $fromId,
            $toType,
            $toId,
            $amount,
            $date,
            $notes,
            $shareholder,
            $shareholderId,
            $sourceParcelId,
            $fromProjectId,
            $toProjectId,
            $fromLabel,
            $toLabel,
            $user,
            $isAdmin
        ): FundTransfer {
            $descBase = sprintf('تحويل صناديق — من %s إلى %s', $fromLabel, $toLabel);
            if ($notes) {
                $descBase .= ' — '.$notes;
            }

            $fromTx = TreasuryTransaction::withoutProjectScope()->create([
                'project_id' => $fromProjectId,
                'type' => 'expense',
                'amount' => $amount,
                'description' => $descBase,
                'reference_type' => FundTransfer::class,
                'reference_id' => 0,
                'approval_status' => $isAdmin ? 'approved' : 'pending',
                'approved_at' => $isAdmin ? now() : null,
                'approved_by' => $isAdmin ? (int) $user->id : null,
            ]);

            $toTx = TreasuryTransaction::withoutProjectScope()->create([
                'project_id' => $toProjectId,
                'type' => 'revenue',
                'amount' => $amount,
                'description' => $descBase,
                'reference_type' => FundTransfer::class,
                'reference_id' => 0,
                'approval_status' => $isAdmin ? 'approved' : 'pending',
                'approved_at' => $isAdmin ? now() : null,
                'approved_by' => $isAdmin ? (int) $user->id : null,
            ]);

            $transfer = FundTransfer::query()->create([
                'from_type' => $fromType,
                'from_id' => $fromId,
                'to_type' => $toType,
                'to_id' => $toId,
                'amount' => $amount,
                'transferred_at' => $date,
                'notes' => $notes,
                'shareholder_id' => $shareholderId,
                'source_land_parcel_id' => $sourceParcelId,
                'from_treasury_transaction_id' => (int) $fromTx->id,
                'to_treasury_transaction_id' => (int) $toTx->id,
                'created_by' => $user?->id,
            ]);

            $fromTx->update(['reference_id' => (int) $transfer->id]);
            $toTx->update(['reference_id' => (int) $transfer->id]);

            if ($shareholder instanceof Shareholder) {
                $this->applyShareholderLegs(
                    $shareholder,
                    $fromType,
                    $fromId,
                    $toType,
                    $toId,
                    $amount,
                    $date,
                    $user
                );
            }

            return $transfer->fresh(['shareholder', 'sourceLandParcel', 'creator']) ?? $transfer;
        });
    }

    private function applyShareholderLegs(
        Shareholder $shareholder,
        string $fromType,
        int $fromId,
        string $toType,
        int $toId,
        float $amount,
        string $date,
        ?User $user
    ): void {
        // سحب من المصدر (إن كان مشروعًا عاديًا وله عضوية)
        if ($fromType === FundTransfer::TYPE_PROJECT) {
            $this->shareholderLedgerService->create($shareholder, [
                'project_id' => $fromId,
                'type' => ShareholderLedgerEntry::TYPE_WITHDRAWAL,
                'amount' => $amount,
                'entry_date' => $date,
                'notes' => 'تحويل صناديق — سحب من المشروع المصدر',
                'skip_cashbox' => true,
            ], $user);
        }

        if ($toType === FundTransfer::TYPE_PROJECT) {
            $this->shareholderLedgerService->create($shareholder, [
                'project_id' => $toId,
                'type' => ShareholderLedgerEntry::TYPE_CAPITAL,
                'amount' => $amount,
                'entry_date' => $date,
                'notes' => 'تحويل صناديق — إيداع في المشروع الهدف',
                'skip_cashbox' => true,
            ], $user);
        }
    }

    private function assertEndpoint(string $type, int $id): void
    {
        if ($type === FundTransfer::TYPE_LAND_CASHBOX) {
            if ($id !== LandTradingCashbox::projectId()) {
                // normalize: land_cashbox always uses the system project id
            }

            return;
        }

        if ($type === FundTransfer::TYPE_PROJECT) {
            $project = Project::query()->find($id);
            if (! $project instanceof Project || $project->is_land_trading_cashbox) {
                throw new InvalidArgumentException('المشروع الهدف/المصدر غير صالح.');
            }

            return;
        }

        throw new InvalidArgumentException('نوع الصندوق غير صالح.');
    }

    private function resolveProjectId(string $type, int $id): int
    {
        if ($type === FundTransfer::TYPE_LAND_CASHBOX) {
            return LandTradingCashbox::projectId();
        }

        return $id;
    }

    private function endpointLabel(string $type, int $id): string
    {
        if ($type === FundTransfer::TYPE_LAND_CASHBOX) {
            return 'صندوق الأراضي';
        }

        $name = Project::query()->whereKey($id)->value('name');

        return $name ? 'مشروع: '.$name : 'مشروع #'.$id;
    }
}
