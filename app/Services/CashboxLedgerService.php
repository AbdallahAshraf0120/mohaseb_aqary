<?php

namespace App\Services;

use App\Models\DebtPayment;
use App\Models\Expense;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\TreasuryTransaction;

class CashboxLedgerService
{
    public function syncFromRevenue(Revenue $revenue): void
    {
        TreasuryTransaction::query()->updateOrCreate(
            [
                'project_id' => $revenue->project_id,
                'reference_type' => Revenue::class,
                'reference_id' => $revenue->id,
            ],
            [
                'type' => 'revenue',
                'amount' => $revenue->amount,
                'description' => $this->revenueDescription($revenue),
            ]
        );
    }

    public function removeRevenue(int $revenueId): void
    {
        TreasuryTransaction::query()
            ->where('reference_type', Revenue::class)
            ->where('reference_id', $revenueId)
            ->delete();
    }

    public function syncFromExpense(Expense $expense): void
    {
        $desc = trim(implode(' — ', array_filter([
            $expense->category,
            $expense->description,
        ])));

        TreasuryTransaction::query()->updateOrCreate(
            [
                'project_id' => $expense->project_id,
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
            ],
            [
                'type' => 'expense',
                'amount' => $expense->amount,
                'description' => $desc !== '' ? $desc : 'مصروف',
            ]
        );
    }

    public function removeExpense(int $expenseId): void
    {
        TreasuryTransaction::query()
            ->where('reference_type', Expense::class)
            ->where('reference_id', $expenseId)
            ->delete();
    }

    /**
     * مقدم البيعة أو كامل المبلغ في حالة الكاش — يُعتبر واردًا للصندوق عند إتمام البيعة.
     */
    public function syncSaleDownPayment(Sale $sale): void
    {
        $amount = (float) ($sale->down_payment ?? 0);
        if ($amount <= 0) {
            $this->removeSaleDownPayment($sale->id);

            return;
        }

        $label = $sale->payment_type === 'cash'
            ? 'كاش / بيعة #'.$sale->id
            : 'مقدم / دفعة بيعة #'.$sale->id;

        TreasuryTransaction::query()->updateOrCreate(
            [
                'project_id' => $sale->project_id,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
            ],
            [
                'type' => 'revenue',
                'amount' => $amount,
                'description' => $label,
            ]
        );
    }

    public function removeSaleDownPayment(int $saleId): void
    {
        TreasuryTransaction::query()
            ->where('reference_type', Sale::class)
            ->where('reference_id', $saleId)
            ->delete();
    }

    public function syncFromDebtPayment(DebtPayment $payment): void
    {
        $payment->loadMissing('debt');
        $debt = $payment->debt;
        if ($debt === null) {
            return;
        }

        $creditor = filled($debt->creditor_name) ? (string) $debt->creditor_name : 'ذمة #'.$debt->id;
        $parts = array_filter([
            'سداد ذمة مورد',
            $creditor,
            $payment->note,
            'دفعة #'.$payment->id,
        ]);

        TreasuryTransaction::query()->updateOrCreate(
            [
                'project_id' => $debt->project_id,
                'reference_type' => DebtPayment::class,
                'reference_id' => $payment->id,
            ],
            [
                'type' => 'expense',
                'amount' => $payment->amount,
                'description' => implode(' — ', $parts),
            ]
        );
    }

    public function removeDebtPayment(int $debtPaymentId): void
    {
        TreasuryTransaction::query()
            ->where('reference_type', DebtPayment::class)
            ->where('reference_id', $debtPaymentId)
            ->delete();
    }

    /**
     * إعادة بناء حركات الصندوق المرتبطة بالتحصيل والمصروفات والمقدمات وسداد الذمم (بدون المساس بالحركات اليدوية reference null).
     */
    public function rebuildFromAccountingRecords(): void
    {
        TreasuryTransaction::query()
            ->whereIn('reference_type', [Revenue::class, Expense::class, Sale::class, DebtPayment::class])
            ->delete();

        Revenue::query()->orderBy('id')->each(fn (Revenue $r) => $this->syncFromRevenue($r));
        Expense::query()->orderBy('id')->each(fn (Expense $e) => $this->syncFromExpense($e));
        Sale::query()->orderBy('id')->each(fn (Sale $s) => $this->syncSaleDownPayment($s));
        DebtPayment::query()->with(['debt' => static fn ($q) => $q->withoutGlobalScopes()])->orderBy('id')->each(fn (DebtPayment $p) => $this->syncFromDebtPayment($p));
    }

    private function revenueDescription(Revenue $revenue): string
    {
        $parts = array_filter([
            $revenue->category,
            $revenue->source,
            $revenue->contract_id ? 'عقد #'.$revenue->contract_id : null,
        ]);

        return $parts !== [] ? implode(' — ', $parts) : 'تحصيل';
    }
}
