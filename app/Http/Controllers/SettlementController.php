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

        $revQ = Revenue::query()->withoutGlobalScope('project')->where('project_id', $pid)->counted();
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

        // ===== التعاقدات الملغية: اللي اتقبض منها فعلًا واللي اترد =====
        $cancelledQ = Sale::query()->withoutGlobalScope('project')->where('project_id', $pid)->where('approval_status', 'cancelled');
        $filters->applyWhereDate($cancelledQ, 'sale_date');
        $cancelledIds = (clone $cancelledQ)->pluck('id');
        $cancelledDown = (float) (clone $cancelledQ)->sum('down_payment');
        $cancelledCollected = $cancelledIds->isEmpty() ? 0.0 : (float) Revenue::query()->withoutGlobalScope('project')
            ->whereIn('sale_id', $cancelledIds)->counted()->sum('amount');
        $refundQ = TreasuryTransaction::query()->withoutGlobalScope('project')->where('project_id', $pid)
            ->where('reference_type', 'sale_refund')->where('approval_status', 'approved');
        $filters->applyWhereDate($refundQ, 'created_at');
        $refunds = (float) $refundQ->sum('amount');
        $cancelledKept = $cancelledDown + $cancelledCollected - $refunds;

        // ===== الذمم عند العملاء =====
        $receivables = (float) Contract::query()->withoutGlobalScope('project')->where('project_id', $pid)->active()->sum('remaining_amount');

        // ===== الربح =====
        $cashIn = $downPayments + $cancelledDown + $collections + $manualIn;
        $cashOut = $expenses + $supplierPaidTotal + $manualOut + $refunds;
        $realizedProfit = $cashIn - $cashOut;
        $expectedProfit = ($salesValue + $cancelledKept + $manualIn) - ($expenses + $supplierPurchases + $manualOut);

        // ===== ربح كل عقار =====
        $byProperty = $this->profitByProperty($pid, $filters);

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
                'refunds' => $refunds,
                'cancelled_kept' => $cancelledKept,
                'cancelled_down' => $cancelledDown,
                'receivables' => $receivables,
                'realized_profit' => $realizedProfit,
                'expected_profit' => $expectedProfit,
            ],
            'pctSum' => $pctSum,
            'byProperty' => $byProperty,
            'shareholders' => $shareholders,
            'shareholderLedgerTotal' => (float) $shareholders->sum(fn ($sh) => (float) ($sh->ledger_balance ?? 0)),
            'modules' => $this->modules(),
        ]);
    }

    /**
     * ربح كل عقار: المبيعات والمحصّل عليه، والمصروفات وذمم الموردين المربوطة بيه.
     * المصروفات اللي مش مربوطة بعقار بتظهر في سطر «عام على المشروع».
     *
     * @return list<array<string, mixed>>
     */
    private function profitByProperty(int $pid, ListingFilters $filters): array
    {
        $props = \App\Models\Property::query()->withoutGlobalScope('project')->where('project_id', $pid)->orderBy('name')->get(['id', 'name', 'total_apartments']);
        $sumBy = function ($query, string $sumCol, string $keyCol = 'property_id'): array {
            return $query->selectRaw($keyCol.' as k, SUM('.$sumCol.') as s, COUNT(*) as c')->groupBy($keyCol)->get()
                ->mapWithKeys(fn ($r) => [(string) ($r->k ?? '') => ['s' => (float) $r->s, 'c' => (int) $r->c]])->all();
        };

        $salesQ = Sale::query()->withoutGlobalScope('project')->where('project_id', $pid)->where('approval_status', 'approved');
        $filters->applyWhereDate($salesQ, 'sale_date');
        $sales = $sumBy(clone $salesQ, 'sale_price');
        $downs = $sumBy(clone $salesQ, 'down_payment');

        $revQ = Revenue::query()->withoutGlobalScope('project')->where('revenues.project_id', $pid)->counted()
            ->join('sales', 'sales.id', '=', 'revenues.sale_id');
        $filters->applyWhereDate($revQ, 'revenues.paid_at');
        $revs = $sumBy($revQ, 'revenues.amount', 'sales.property_id');

        $hasExpProp = Schema::hasColumn('expenses', 'property_id');
        $expQ = Expense::query()->withoutGlobalScope('project')->where('project_id', $pid)->where('approval_status', 'approved');
        $filters->applyWhereDate($expQ, 'spent_at');
        $exps = $hasExpProp ? $sumBy($expQ, 'amount') : ['' => ['s' => (float) $expQ->sum('amount'), 'c' => 0]];

        $hasDebtProp = Schema::hasColumn('debts', 'property_id');
        $debtQ = Debt::query()->withoutGlobalScope('project')->where('project_id', $pid);
        $filters->applyWhereDate($debtQ, 'created_at');
        $debtTotal = $hasDebtProp ? $sumBy(clone $debtQ, 'total_amount') : ['' => ['s' => (float) (clone $debtQ)->sum('total_amount'), 'c' => 0]];
        $debtOpen = $hasDebtProp ? $sumBy(clone $debtQ, 'remaining_amount') : ['' => ['s' => (float) (clone $debtQ)->sum('remaining_amount'), 'c' => 0]];

        $row = function (string $key, string $name, ?int $units) use ($sales, $downs, $revs, $exps, $debtTotal, $debtOpen): array {
            $salesValue = $sales[$key]['s'] ?? 0.0;
            $collected = ($downs[$key]['s'] ?? 0.0) + ($revs[$key]['s'] ?? 0.0);
            $expenses = $exps[$key]['s'] ?? 0.0;
            $purchases = $debtTotal[$key]['s'] ?? 0.0;
            $supplierPaid = max(0.0, $purchases - ($debtOpen[$key]['s'] ?? 0.0));

            return [
                'key' => $key,
                'name' => $name,
                'units_total' => $units,
                'units_sold' => $sales[$key]['c'] ?? 0,
                'sales_value' => $salesValue,
                'collected' => $collected,
                'receivable' => max(0.0, $salesValue - $collected),
                'costs' => $expenses + $purchases,
                'costs_paid' => $expenses + $supplierPaid,
                'realized' => $collected - $expenses - $supplierPaid,
                'expected' => $salesValue - $expenses - $purchases,
            ];
        };

        $out = [];
        foreach ($props as $p) {
            $out[] = $row((string) $p->id, (string) $p->name, (int) ($p->total_apartments ?? 0) ?: null);
        }
        $general = $row('', 'عام على المشروع (مش مربوط بعقار)', null);
        if (abs($general['costs']) > 0.00001 || abs($general['sales_value']) > 0.00001) {
            $out[] = $general;
        }

        return $out;
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
