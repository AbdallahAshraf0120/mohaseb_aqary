<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Models\TreasuryTransaction;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * تصفية المشروع: ربح المشروع ونصيب كل شريك.
 *
 * - المحصّل فعلًا = المقدمات (اللي دخلت الصندوق أو استلمها شريك) + التحصيلات + القبض اليدوي.
 * - المصروف فعلًا = المصروفات + المسدد للموردين من الصندوق + الصرف اليدوي.
 * - الربح المحقق = المحصّل − المصروف.
 * - الربح المتوقع = قيمة المبيعات المعتمدة + القبض اليدوي − (المصروفات + إجمالي مشتريات الموردين + الصرف اليدوي).
 *   (يعني بعد ما كل العملاء يدفعوا وكل الموردين يتسددوا)
 * - رأس المال، سحوبات الشركاء، وتحويلات الصناديق مش ربح ولا تكلفة، فمش داخلين.
 */
class SettlementController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $pid = (int) $project->id;

        // ===== الإيرادات =====
        $salesQ = Sale::query()->withoutGlobalScope('project')->where('project_id', $pid)->where('approval_status', 'approved');
        $filters->applyWhereDate($salesQ, 'sale_date');
        $salesValue = (float) (clone $salesQ)->sum('sale_price');
        $downPayments = (float) (clone $salesQ)->sum('down_payment');
        $salesCount = (int) (clone $salesQ)->count();

        $revQ = Revenue::query()->withoutGlobalScope('project')->where('project_id', $pid)->where('approval_status', 'approved');
        $filters->applyWhereDate($revQ, 'paid_at');
        $collections = (float) $revQ->sum('amount');

        $manualQ = TreasuryTransaction::query()->withoutGlobalScope('project')
            ->where('project_id', $pid)->whereNull('reference_type')->where('approval_status', 'approved');
        $filters->applyWhereDate($manualQ, 'created_at');
        $manualIn = (float) (clone $manualQ)->where('type', 'revenue')->sum('amount');
        $manualOut = (float) (clone $manualQ)->where('type', 'expense')->sum('amount');

        // ===== التكاليف =====
        $expQ = Expense::query()->withoutGlobalScope('project')->where('project_id', $pid)->where('approval_status', 'approved');
        $filters->applyWhereDate($expQ, 'spent_at');
        $expenses = (float) $expQ->sum('amount');

        $debtsQ = Debt::query()->withoutGlobalScope('project')->where('project_id', $pid);
        $filters->applyWhereDate($debtsQ, 'created_at');
        $supplierPurchases = (float) (clone $debtsQ)->sum('total_amount');
        $supplierOpen = (float) (clone $debtsQ)->sum('remaining_amount');

        $debtPayQ = DebtPayment::query()->where('approval_status', 'approved')
            ->whereIn('debt_id', Debt::query()->withoutGlobalScope('project')->where('project_id', $pid)->select('id'));
        $filters->applyWhereDate($debtPayQ, 'created_at');
        $supplierPaidFromCashbox = (float) $debtPayQ->sum('amount');
        // المسدد للموردين بره الصندوق (اتسجل وقت إنشاء الذمة)
        $supplierPaidTotal = max(0.0, $supplierPurchases - $supplierOpen);

        // ===== الذمم عند العملاء =====
        $receivables = (float) Contract::query()->withoutGlobalScope('project')->where('project_id', $pid)->active()->sum('remaining_amount');

        // ===== الربح =====
        $cashIn = $downPayments + $collections + $manualIn;
        $cashOut = $expenses + $supplierPaidTotal + $manualOut;
        $realizedProfit = $cashIn - $cashOut;
        $expectedProfit = ($salesValue + $manualIn) - ($expenses + $supplierPurchases + $manualOut);

        // ===== الشركاء =====
        $memberships = ProjectShareholder::query()
            ->with('shareholder:id,name')
            ->where('project_id', $pid)
            ->get()
            ->filter(fn (ProjectShareholder $m) => $m->shareholder !== null)
            ->values();

        $pctOf = function (ProjectShareholder $m): array {
            if (Schema::hasColumn('project_shareholder', 'actual_percentage') && (float) ($m->actual_percentage ?? 0) > 0) {
                return [(float) $m->actual_percentage, 'فعلية'];
            }
            if (Schema::hasColumn('project_shareholder', 'planned_percentage') && (float) ($m->planned_percentage ?? 0) > 0) {
                return [(float) $m->planned_percentage, 'مخططة'];
            }

            return [(float) ($m->share_percentage ?? 0), 'مخططة'];
        };
        $pctSum = (float) $memberships->sum(fn ($m) => $pctOf($m)[0]);

        $shareholders = $memberships->map(function (ProjectShareholder $m) use ($pid, $pctOf, $pctSum, $realizedProfit, $expectedProfit) {
            /** @var Shareholder $sh */
            $sh = $m->shareholder;
            $entries = $sh->ledgerEntries()->where('project_id', $pid);
            $credit = (float) (clone $entries)->where('direction', ShareholderLedgerEntry::DIRECTION_CREDIT)->sum('amount');
            $debit = (float) (clone $entries)->where('direction', ShareholderLedgerEntry::DIRECTION_DEBIT)->sum('amount');
            $capital = (float) (clone $entries)->where('type', ShareholderLedgerEntry::TYPE_CAPITAL)->sum('amount');

            [$pct, $basis] = $pctOf($m);
            // لو مجموع النسب مش 100، بنوزّع الربح على نسبة كل شريك من المجموع
            $weight = $pctSum > 0 ? $pct / $pctSum : 0.0;
            $ledger = round($credit - $debit, 5);
            $realizedShare = round($realizedProfit * $weight, 5);

            $sh->setAttribute('share_percentage', $pct);
            $sh->setAttribute('percentage_basis', $basis);
            $sh->setAttribute('capital_deposits_total', round($capital, 5));
            $sh->setAttribute('ledger_balance', $ledger);
            $sh->setAttribute('realized_profit_share', $realizedShare);
            $sh->setAttribute('expected_profit_share', round($expectedProfit * $weight, 5));
            // المستحق له لو اتصفّى المشروع النهارده = رصيده في الجاري + نصيبه من الربح المحقق
            $sh->setAttribute('due_now', round($ledger + $realizedShare, 5));

            return $sh;
        });

        return view('settlements.index', [
            'title' => 'التسويات | Mohaseb Aqary',
            'pageTitle' => 'التسويات',
            'project' => $project,
            'figures' => [
                'sales_value' => $salesValue,
                'sales_count' => $salesCount,
                'down_payments' => $downPayments,
                'collections' => $collections,
                'manual_in' => $manualIn,
                'cash_in' => $cashIn,
                'expenses' => $expenses,
                'supplier_purchases' => $supplierPurchases,
                'supplier_paid' => $supplierPaidTotal,
                'supplier_paid_cashbox' => $supplierPaidFromCashbox,
                'supplier_open' => $supplierOpen,
                'manual_out' => $manualOut,
                'cash_out' => $cashOut,
                'receivables' => $receivables,
                'realized_profit' => $realizedProfit,
                'expected_profit' => $expectedProfit,
            ],
            'pctSum' => $pctSum,
            'shareholders' => $shareholders,
            'shareholderLedgerTotal' => (float) $shareholders->sum(fn ($sh) => (float) ($sh->ledger_balance ?? 0)),
            'modules' => $this->modules(),
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
        ];
    }
}
