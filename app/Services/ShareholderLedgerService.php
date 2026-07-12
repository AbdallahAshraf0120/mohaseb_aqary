<?php

namespace App\Services;

use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShareholderLedgerService
{
    /**
     * @param  array{
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

        return DB::transaction(function () use ($shareholder, $type, $direction, $amount, $data, $user, $affectCashbox): ShareholderLedgerEntry {
            $entry = ShareholderLedgerEntry::query()->create([
                'project_id' => (int) $shareholder->project_id,
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
                $tx = TreasuryTransaction::query()->create([
                    'project_id' => (int) $shareholder->project_id,
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
                $this->syncTotalInvestment($shareholder);
            }

            return $entry->fresh(['treasuryTransaction', 'creator']) ?? $entry;
        });
    }

    public function delete(ShareholderLedgerEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $shareholder = $entry->shareholder;
            $wasCapital = $entry->type === ShareholderLedgerEntry::TYPE_CAPITAL;
            $treasuryId = $entry->treasury_transaction_id;

            $entry->delete();

            if ($treasuryId) {
                TreasuryTransaction::query()
                    ->whereKey($treasuryId)
                    ->where('reference_type', 'shareholder_ledger_entry')
                    ->delete();
            }

            if ($wasCapital && $shareholder) {
                $this->syncTotalInvestment($shareholder);
            }
        });
    }

    public function syncTotalInvestment(Shareholder $shareholder): void
    {
        $total = $shareholder->capitalDepositsTotal();
        $shareholder->update(['total_investment' => $total]);
    }
}
