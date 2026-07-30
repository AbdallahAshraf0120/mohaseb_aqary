<?php

namespace App\Services;

use App\Models\TreasuryTransaction;
use InvalidArgumentException;

/**
 * يحسب رصيد الصندوق المعتمد ويمنع الصرف/التحويل عند عدم كفاية الرصيد.
 */
class CashboxBalanceService
{
    /**
     * رصيد معتمد = إجمالي القبض المعتمد − إجمالي الصرف المعتمد.
     *
     * @param  array{treasury_id?: int|null, reference_type?: string|null, reference_id?: int|null}|null  $exclude
     */
    public function approvedBalance(int $projectId, ?array $exclude = null): float
    {
        if ($projectId < 1) {
            return 0.0;
        }

        $base = TreasuryTransaction::withoutProjectScope()
            ->where('project_id', $projectId)
            ->where('approval_status', 'approved');

        $this->applyExclude($base, $exclude);

        $in = (float) (clone $base)->where('type', 'revenue')->sum('amount');
        $out = (float) (clone $base)->where('type', 'expense')->sum('amount');

        return round($in - $out, 5);
    }

    /**
     * @param  array{treasury_id?: int|null, reference_type?: string|null, reference_id?: int|null}|null  $exclude
     */
    public function assertCanSpend(int $projectId, float|int|string $amount, ?array $exclude = null): void
    {
        $amount = round((float) $amount, 5);
        if ($amount < 0.00001) {
            return;
        }

        $balance = $this->approvedBalance($projectId, $exclude);
        if ($amount > $balance + 0.01) {
            throw new InvalidArgumentException(
                'رصيد الصندوق غير كافٍ للصرف (المتاح: '.number_format($balance, 5).' ج.م).'
            );
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\TreasuryTransaction>  $query
     * @param  array{treasury_id?: int|null, reference_type?: string|null, reference_id?: int|null}|null  $exclude
     */
    private function applyExclude($query, ?array $exclude): void
    {
        if ($exclude === null) {
            return;
        }

        $treasuryId = isset($exclude['treasury_id']) && $exclude['treasury_id'] !== null
            ? (int) $exclude['treasury_id']
            : null;
        if ($treasuryId !== null && $treasuryId > 0) {
            $query->whereKeyNot($treasuryId);
        }

        $refType = isset($exclude['reference_type']) ? $exclude['reference_type'] : null;
        $refId = isset($exclude['reference_id']) && $exclude['reference_id'] !== null
            ? (int) $exclude['reference_id']
            : null;

        if ($refType !== null && $refType !== '' && $refId !== null && $refId > 0) {
            $query->where(function ($q) use ($refType, $refId): void {
                $q->whereNull('reference_type')
                    ->orWhere('reference_type', '!=', $refType)
                    ->orWhere('reference_id', '!=', $refId);
            });
        }
    }
}
