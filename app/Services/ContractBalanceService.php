<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Revenue;

/** إعادة حساب المدفوع والمتبقي على العقد (المقدم المعتمد + التحصيلات المحسوبة فعلًا). */
class ContractBalanceService
{
    public function recalculate(int $contractId): void
    {
        $contract = Contract::query()->withoutGlobalScopes()->with(['sale' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'down_payment', 'approval_status')])->find($contractId);
        if (! $contract) {
            return;
        }
        $fromRevenues = (float) Revenue::query()->withoutGlobalScopes()->where('contract_id', $contractId)->counted()->sum('amount');
        $down = (float) (($contract->sale?->approval_status ?? 'approved') === 'approved' ? ($contract->sale?->down_payment ?? 0) : 0);
        $paid = $down + $fromRevenues;
        $contract->update([
            'paid_amount' => $paid,
            'remaining_amount' => max(0, (float) $contract->total_price - $paid),
        ]);
    }
}
