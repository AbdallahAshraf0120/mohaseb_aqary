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
use App\Services\CashboxLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalsController extends Controller
{
    public function __construct(private readonly CashboxLedgerService $cashboxLedger) {}

    public function index(Project $project): View
    {
        $pending = [
            'revenues' => Revenue::query()->where('approval_status', 'pending')->with('client:id,name')->latest('paid_at')->latest('id')->limit(25)->get(),
            'expenses' => Expense::query()->where('approval_status', 'pending')->latest('id')->limit(25)->get(),
            'sales' => Sale::query()->where('approval_status', 'pending')->with(['client:id,name', 'property:id,name'])->latest('sale_date')->latest('id')->limit(25)->get(),
            'debt_payments' => DebtPayment::query()->where('approval_status', 'pending')->with('debt')->latest('id')->limit(25)->get(),
            'manual_treasury' => TreasuryTransaction::query()
                ->whereNull('reference_type')
                ->where('approval_status', 'pending')
                ->latest('id')
                ->limit(25)
                ->get(),
        ];

        $counts = [
            'revenues' => Revenue::query()->where('approval_status', 'pending')->count(),
            'expenses' => Expense::query()->where('approval_status', 'pending')->count(),
            'sales' => Sale::query()->where('approval_status', 'pending')->count(),
            'debt_payments' => DebtPayment::query()->where('approval_status', 'pending')->count(),
            'manual_treasury' => TreasuryTransaction::query()->whereNull('reference_type')->where('approval_status', 'pending')->count(),
        ];

        return view('approvals.index', [
            'title' => 'طلبات الاعتماد | Mohaseb Aqary',
            'pageTitle' => 'طلبات الاعتماد',
            'project' => $project,
            'pending' => $pending,
            'counts' => $counts,
            'modules' => $this->modules(),
        ]);
    }

    public function approve(Request $request, Project $project, string $type, int $id): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

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

        return redirect()->route('approvals.index', [$project])->with('success', 'تم اعتماد العملية بنجاح.');
    }

    public function reject(Request $request, Project $project, string $type, int $id): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

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

        return redirect()->route('approvals.index', [$project])->with('success', 'تم رفض العملية.');
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
        $this->cashboxLedger->syncFromRevenue($revenue->refresh());
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
        $this->cashboxLedger->syncFromRevenue($revenue->refresh());
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

        $total = round((float) $debt->total_amount, 2);
        $paid = round((float) DebtPayment::query()
            ->where('debt_id', $debtId)
            ->where('approval_status', 'approved')
            ->sum('amount'), 2);
        $paid = min($paid, $total);
        $remaining = round(max(0.0, $total - $paid), 2);
        $debt->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $remaining > 0.01 ? 'open' : 'closed',
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

