<?php

namespace App\Services;

use App\Models\DebtPayment;
use App\Models\Expense;
use App\Models\LandParcelPayment;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\TreasuryTransaction;
use App\Support\LandTradingCashbox;

class CashboxLedgerService
{
    private bool $enforceBalanceChecks = true;

    public function __construct(
        private readonly CashboxBalanceService $cashboxBalanceService,
    ) {}

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
                'approval_status' => (string) ($revenue->approval_status ?? 'approved'),
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

        if ($this->enforceBalanceChecks && ($expense->approval_status ?? '') === 'approved') {
            $this->cashboxBalanceService->assertCanSpend(
                (int) $expense->project_id,
                (float) $expense->amount,
                [
                    'reference_type' => Expense::class,
                    'reference_id' => (int) $expense->id,
                ]
            );
        }

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
                'approval_status' => (string) ($expense->approval_status ?? 'approved'),
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
        $amount = $sale->cashboxDownPaymentAmount();
        if ($amount <= 0) {
            $this->removeSaleDownPayment($sale->id);

            return;
        }

        $down = round((float) ($sale->down_payment ?? 0), 2);
        $sharePart = $sale->shareholderDownPaymentAmount();
        if ($sale->payment_type === 'cash') {
            $label = 'كاش / بيعة #'.$sale->id;
        } elseif ($sharePart >= 0.01 && $sharePart + 0.009 < $down) {
            $label = sprintf(
                'مقدم للصندوق %.2f من أصل %.2f / بيعة #%d',
                $amount,
                $down,
                (int) $sale->id
            );
        } else {
            $label = 'مقدم / دفعة بيعة #'.$sale->id;
        }

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
                'approval_status' => (string) ($sale->approval_status ?? 'approved'),
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

        if ($this->enforceBalanceChecks && ($payment->approval_status ?? '') === 'approved') {
            $this->cashboxBalanceService->assertCanSpend(
                (int) $debt->project_id,
                (float) $payment->amount,
                [
                    'reference_type' => DebtPayment::class,
                    'reference_id' => (int) $payment->id,
                ]
            );
        }

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
                'approval_status' => (string) ($payment->approval_status ?? 'approved'),
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

    public function syncFromLandParcelPayment(LandParcelPayment $payment): void
    {
        $payment->loadMissing(['landParcel:id,name', 'part:id,name']);
        $parcelName = $payment->landParcel?->name ?? ('أرض #'.$payment->land_parcel_id);
        $partName = $payment->part?->name;
        $isPurchase = $payment->side === LandParcelPayment::SIDE_PURCHASE;
        $type = $isPurchase ? 'expense' : 'revenue';
        $label = $isPurchase
            ? 'دفعة شراء أرض — '.$parcelName.' — '.$payment->kindLabel()
            : 'تحصيل بيع أرض — '.$parcelName
                .($partName ? ' / جزء: '.$partName : '')
                .' — '.$payment->kindLabel();

        $landProjectId = LandTradingCashbox::projectId();
        if ($this->enforceBalanceChecks && $isPurchase && ($payment->approval_status ?? '') === 'approved') {
            $this->cashboxBalanceService->assertCanSpend(
                $landProjectId,
                (float) $payment->amount,
                [
                    'reference_type' => LandParcelPayment::class,
                    'reference_id' => (int) $payment->id,
                ]
            );
        }

        TreasuryTransaction::withoutProjectScope()->updateOrCreate(
            [
                'reference_type' => LandParcelPayment::class,
                'reference_id' => $payment->id,
            ],
            [
                'project_id' => $landProjectId,
                'type' => $type,
                'amount' => $payment->amount,
                'description' => $label.($payment->notes ? ' — '.$payment->notes : ''),
                'approval_status' => (string) ($payment->approval_status ?? 'approved'),
            ]
        );
    }

    public function removeLandParcelPayment(int $paymentId): void
    {
        TreasuryTransaction::withoutProjectScope()
            ->where('reference_type', LandParcelPayment::class)
            ->where('reference_id', $paymentId)
            ->delete();
    }

    /**
     * إعادة بناء حركات الصندوق المرتبطة بالتحصيل والمصروفات والمقدمات وسداد الذمم (بدون المساس بالحركات اليدوية reference null).
     */
    public function rebuildFromAccountingRecords(): void
    {
        TreasuryTransaction::query()
            ->whereIn('reference_type', [Revenue::class, Expense::class, Sale::class, DebtPayment::class, LandParcelPayment::class])
            ->where('approval_status', 'approved')
            ->delete();

        $previous = $this->enforceBalanceChecks;
        $this->enforceBalanceChecks = false;
        try {
            Revenue::query()->where('approval_status', 'approved')->orderBy('id')->each(fn (Revenue $r) => $this->syncFromRevenue($r));
            Expense::query()->where('approval_status', 'approved')->orderBy('id')->each(fn (Expense $e) => $this->syncFromExpense($e));
            Sale::query()->where('approval_status', 'approved')->orderBy('id')->each(fn (Sale $s) => $this->syncSaleDownPayment($s));
            DebtPayment::query()
                ->where('approval_status', 'approved')
                ->with(['debt' => static fn ($q) => $q->withoutGlobalScopes()])
                ->orderBy('id')
                ->each(fn (DebtPayment $p) => $this->syncFromDebtPayment($p));
            LandParcelPayment::query()
                ->where('approval_status', 'approved')
                ->orderBy('id')
                ->each(fn (LandParcelPayment $p) => $this->syncFromLandParcelPayment($p));
        } finally {
            $this->enforceBalanceChecks = $previous;
        }
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
