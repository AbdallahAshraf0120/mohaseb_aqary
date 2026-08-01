<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Services\CashboxBalanceService;
use App\Services\CashboxLedgerService;
use App\Services\RevenueShareholderAttributionService;
use App\Services\SaleShareholderAttributionService;
use App\Support\CurrentProject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApprovalsController extends Controller
{
    public function __construct(
        private readonly CashboxLedgerService $cashboxLedger,
        private readonly CashboxBalanceService $cashboxBalanceService,
        private readonly RevenueShareholderAttributionService $revenueAttribution,
        private readonly SaleShareholderAttributionService $saleAttribution,
    ) {}

    /**
     * طلبات الاعتماد عبر كل المشاريع في مكان واحد (خارج سياق مشروع بعينه).
     */
    public function index(Request $request): View
    {
        $projectId = $request->integer('project_id') ?: null;

        $revenuesQ = Revenue::query()->withoutProjectScope()
            ->where('approval_status', 'pending')
            ->with(['client:id,name', 'receivedByShareholder:id,name', 'project:id,name']);
        $expensesQ = Expense::query()->withoutProjectScope()
            ->where('approval_status', 'pending')
            ->with(['project:id,name']);
        $salesQ = Sale::query()->withoutProjectScope()
            ->where('approval_status', 'pending')
            ->with(['client:id,name', 'property:id,name', 'receivedByShareholder:id,name', 'project:id,name']);
        $debtPaymentsQ = DebtPayment::query()
            ->where('approval_status', 'pending')
            ->with(['debt' => fn ($q) => $q->withoutProjectScope()->with('project:id,name')]);
        $manualTreasuryQ = TreasuryTransaction::query()->withoutProjectScope()
            ->whereNull('reference_type')
            ->where('approval_status', 'pending')
            ->with(['project:id,name']);

        if ($projectId !== null) {
            $revenuesQ->where('project_id', $projectId);
            $expensesQ->where('project_id', $projectId);
            $salesQ->where('project_id', $projectId);
            $debtPaymentsQ->whereHas('debt', fn ($q) => $q->withoutProjectScope()->where('project_id', $projectId));
            $manualTreasuryQ->where('project_id', $projectId);
        }

        $pending = [
            'revenues' => (clone $revenuesQ)->latest('paid_at')->latest('id')->limit(25)->get(),
            'expenses' => (clone $expensesQ)->latest('id')->limit(25)->get(),
            'sales' => (clone $salesQ)->latest('sale_date')->latest('id')->limit(25)->get(),
            'debt_payments' => (clone $debtPaymentsQ)->latest('id')->limit(25)->get(),
            'manual_treasury' => (clone $manualTreasuryQ)->latest('id')->limit(25)->get(),
        ];

        $counts = [
            'revenues' => (clone $revenuesQ)->count(),
            'expenses' => (clone $expensesQ)->count(),
            'sales' => (clone $salesQ)->count(),
            'debt_payments' => (clone $debtPaymentsQ)->count(),
            'manual_treasury' => (clone $manualTreasuryQ)->count(),
        ];

        return view('approvals.index', [
            'title' => 'طلبات الاعتماد | Mohaseb Aqary',
            'pageTitle' => 'طلبات الاعتماد',
            'pending' => $pending,
            'counts' => $counts,
            'projects' => Project::query()->listed()->orderBy('name')->get(['id', 'name']),
            'selectedProjectId' => $projectId,
            'modules' => $this->modules(),
        ]);
    }

    public function approve(Request $request, string $type, int $id): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        try {
            $this->withProjectContext($type, $id, function () use ($type, $id, $user): void {
                DB::transaction(function () use ($type, $id, $user): void {
                    match ($type) {
                        'revenue' => $this->approveRevenue($id, (int) $user->id),
                        'expense' => $this->approveExpense($id, (int) $user->id),
                        'sale' => $this->approveSale($id, (int) $user->id),
                        'debt_payment' => $this->approveDebtPayment($id, (int) $user->id),
                        'manual_treasury' => $this->approveManualTreasury($id, (int) $user->id),
                        default => throw new \InvalidArgumentException('unknown_type'),
                    };
                });
            });
        } catch (InvalidArgumentException $e) {
            return redirect()->route('approvals.index')->with('error', $e->getMessage());
        }

        return redirect()->route('approvals.index')->with('success', 'تم اعتماد العملية بنجاح.');
    }

    public function reject(Request $request, string $type, int $id): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->withProjectContext($type, $id, function () use ($type, $id, $user, $data): void {
                DB::transaction(function () use ($type, $id, $user, $data): void {
                    $reason = $data['reason'] ?? null;
                    match ($type) {
                        'revenue' => $this->rejectRevenue($id, (int) $user->id, $reason),
                        'expense' => $this->rejectExpense($id, (int) $user->id, $reason),
                        'sale' => $this->rejectSale($id, (int) $user->id, $reason),
                        'debt_payment' => $this->rejectDebtPayment($id, (int) $user->id, $reason),
                        'manual_treasury' => $this->rejectManualTreasury($id, (int) $user->id, $reason),
                        default => throw new \InvalidArgumentException('unknown_type'),
                    };
                });
            });
        } catch (InvalidArgumentException $e) {
            return redirect()->route('approvals.index')->with('error', $e->getMessage());
        }

        return redirect()->route('approvals.index')->with('success', 'تم رفض العملية.');
    }

    /**
     * هذه الصفحة برا سياق أي مشروع، لكن كل عملية اعتماد/رفض بتلمس نماذج مربوطة
     * بمشروع بعينه (BelongsToProject). بنحدد مشروع العملية الحقيقي ونفرضه مؤقتًا
     * كـ "المشروع الحالي" علشان أي كتابة/قراءة داخلية (مزامنة الصندوق، نسب المساهمين...)
     * تتم على المشروع الصحيح، بغض النظر عن آخر مشروع كان مفتوح في الجلسة.
     */
    private function withProjectContext(string $type, int $id, \Closure $callback): void
    {
        $projectId = $this->resolveProjectId($type, $id);
        $current = app(CurrentProject::class);
        $current->force($projectId);
        try {
            $callback();
        } finally {
            $current->force(null);
        }
    }

    private function resolveProjectId(string $type, int $id): int
    {
        $projectId = match ($type) {
            'revenue' => (int) (Revenue::query()->withoutProjectScope()->whereKey($id)->value('project_id') ?? 0),
            'expense' => (int) (Expense::query()->withoutProjectScope()->whereKey($id)->value('project_id') ?? 0),
            'sale' => (int) (Sale::query()->withoutProjectScope()->whereKey($id)->value('project_id') ?? 0),
            'debt_payment' => $this->resolveDebtPaymentProjectId($id),
            'manual_treasury' => (int) (TreasuryTransaction::query()->withoutProjectScope()->whereKey($id)->value('project_id') ?? 0),
            default => 0,
        };

        if ($projectId < 1) {
            throw new InvalidArgumentException('لم يتم العثور على المشروع المرتبط بهذا الطلب.');
        }

        return $projectId;
    }

    private function resolveDebtPaymentProjectId(int $id): int
    {
        $debtId = (int) (DebtPayment::query()->whereKey($id)->value('debt_id') ?? 0);
        if ($debtId < 1) {
            return 0;
        }

        return (int) (Debt::query()->withoutProjectScope()->whereKey($debtId)->value('project_id') ?? 0);
    }

    private function approveRevenue(int $id, int $userId): void
    {
        $revenue = Revenue::query()->findOrFail($id);
        $revenue->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $userId,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);
        $revenue = $revenue->refresh();
        $this->cashboxLedger->syncFromRevenue($revenue);
        $this->revenueAttribution->sync($revenue, User::query()->find($userId));
        if ($revenue->contract_id) {
            $this->recalculateContract((int) $revenue->contract_id);
        }
    }

    private function rejectRevenue(int $id, int $userId, ?string $reason): void
    {
        $revenue = Revenue::query()->findOrFail($id);
        $revenue->update([
            'approval_status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $userId,
            'rejection_reason' => $reason ? trim($reason) : null,
            'approved_at' => null,
            'approved_by' => null,
        ]);
        $revenue = $revenue->refresh();
        $this->cashboxLedger->syncFromRevenue($revenue);
        $this->revenueAttribution->sync($revenue, User::query()->find($userId));
        if ($revenue->contract_id) {
            $this->recalculateContract((int) $revenue->contract_id);
        }
    }

    private function approveExpense(int $id, int $userId): void
    {
        $expense = Expense::query()->findOrFail($id);
        $expense->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $userId,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);
        $this->cashboxLedger->syncFromExpense($expense->refresh());
    }

    private function rejectExpense(int $id, int $userId, ?string $reason): void
    {
        $expense = Expense::query()->findOrFail($id);
        $expense->update([
            'approval_status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $userId,
            'rejection_reason' => $reason ? trim($reason) : null,
            'approved_at' => null,
            'approved_by' => null,
        ]);
        $this->cashboxLedger->syncFromExpense($expense->refresh());
    }

    private function approveSale(int $id, int $userId): void
    {
        $sale = Sale::query()->findOrFail($id);
        $sale->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $userId,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);
        $sale->refresh();
        $this->cashboxLedger->syncSaleDownPayment($sale);
        $this->saleAttribution->sync($sale, User::query()->find($userId));

        $contractId = (int) Contract::query()->withoutProjectScope()->where('sale_id', $sale->id)->value('id');
        if ($contractId > 0) {
            $this->recalculateContract($contractId);
        }
    }

    private function rejectSale(int $id, int $userId, ?string $reason): void
    {
        $sale = Sale::query()->findOrFail($id);
        $sale->update([
            'approval_status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $userId,
            'rejection_reason' => $reason ? trim($reason) : null,
            'approved_at' => null,
            'approved_by' => null,
        ]);
        $sale->refresh();
        $this->cashboxLedger->syncSaleDownPayment($sale);
        $this->saleAttribution->sync($sale, User::query()->find($userId));

        $contractId = (int) Contract::query()->withoutProjectScope()->where('sale_id', $sale->id)->value('id');
        if ($contractId > 0) {
            $this->recalculateContract($contractId);
        }
    }

    private function approveDebtPayment(int $id, int $userId): void
    {
        $payment = DebtPayment::query()->with('debt')->findOrFail($id);
        $payment->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $userId,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);
        $this->cashboxLedger->syncFromDebtPayment($payment->fresh(['debt']));
        if ($payment->debt_id) {
            $this->recalculateDebt((int) $payment->debt_id);
        }
    }

    private function rejectDebtPayment(int $id, int $userId, ?string $reason): void
    {
        $payment = DebtPayment::query()->with('debt')->findOrFail($id);
        $payment->update([
            'approval_status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $userId,
            'rejection_reason' => $reason ? trim($reason) : null,
            'approved_at' => null,
            'approved_by' => null,
        ]);
        $this->cashboxLedger->syncFromDebtPayment($payment->fresh(['debt']));
        if ($payment->debt_id) {
            $this->recalculateDebt((int) $payment->debt_id);
        }
    }

    private function approveManualTreasury(int $id, int $userId): void
    {
        $tx = TreasuryTransaction::query()->whereNull('reference_type')->findOrFail($id);
        if (($tx->type ?? '') === 'expense') {
            $this->cashboxBalanceService->assertCanSpend(
                (int) $tx->project_id,
                (float) $tx->amount,
                ['treasury_id' => (int) $tx->id]
            );
        }
        $tx->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $userId,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);
    }

    private function rejectManualTreasury(int $id, int $userId, ?string $reason): void
    {
        $tx = TreasuryTransaction::query()->whereNull('reference_type')->findOrFail($id);
        $tx->update([
            'approval_status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $userId,
            'rejection_reason' => $reason ? trim($reason) : null,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    private function recalculateContract(int $contractId): void
    {
        $contract = Contract::query()->withoutProjectScope()->with('sale:id,down_payment,approval_status')->find($contractId);
        if (! $contract) {
            return;
        }

        $paidFromRevenues = (float) Revenue::query()
            ->withoutProjectScope()
            ->where('contract_id', $contractId)
            ->where('approval_status', 'approved')
            ->sum('amount');
        $downPayment = (float) (($contract->sale?->approval_status ?? 'approved') === 'approved' ? ($contract->sale?->down_payment ?? 0) : 0);
        $paid = $downPayment + $paidFromRevenues;
        $contract->update([
            'paid_amount' => $paid,
            'remaining_amount' => max(0, (float) $contract->total_price - $paid),
        ]);
    }

    private function recalculateDebt(int $debtId): void
    {
        $debt = Debt::query()->withoutProjectScope()->find($debtId);
        if (! $debt) {
            return;
        }

        $total = round((float) $debt->total_amount, 5);
        $paid = round((float) DebtPayment::query()
            ->where('debt_id', $debtId)
            ->where('approval_status', 'approved')
            ->sum('amount'), 5);
        $paid = min($paid, $total);
        $remaining = round(max(0.0, $total - $paid), 5);
        $debt->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $remaining > 0.00001 ? 'open' : 'closed',
        ]);
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
            'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'clients.index'],
            'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'contracts.index'],
            'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'sales.index'],
            'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'revenues.index'],
            'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'expenses.index'],
            'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'cashbox.index'],
            'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'remaining.index'],
            'debts' => ['label' => 'ذمم دائنة', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
            'approvals' => ['label' => 'طلبات الاعتماد', 'icon' => 'fa-user-check', 'route' => 'approvals.index'],
        ];
    }
}

