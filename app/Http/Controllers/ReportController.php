<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\TreasuryTransaction;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $setting = Setting::query()->first();
        $currencyLabel = strtoupper((string) ($setting?->currency ?? 'EGP')) === 'EGP' ? 'ج.م' : (string) ($setting?->currency ?? '');

        $filters = ListingFilters::fromRequest($request);

        $periodFrom = $filters->dateFrom ?? now()->startOfMonth()->startOfDay();
        $periodTo = $filters->dateTo ?? now()->endOfDay();
        if ($periodFrom->gt($periodTo)) {
            $periodTo = $periodFrom->copy()->endOfDay();
        }

        $fromStr = $periodFrom->toDateString();
        $toStr = $periodTo->toDateString();

        $revenuesQ = Revenue::query()
            ->whereDate('paid_at', '>=', $fromStr)
            ->whereDate('paid_at', '<=', $toStr);
        $this->applyRevenueSearch($revenuesQ, $filters);

        $expensesQ = Expense::query()
            ->whereDate('created_at', '>=', $fromStr)
            ->whereDate('created_at', '<=', $toStr);
        $this->applyExpenseSearch($expensesQ, $filters);

        $salesQ = Sale::query()
            ->whereDate('sale_date', '>=', $fromStr)
            ->whereDate('sale_date', '<=', $toStr);
        $this->applySaleSearch($salesQ, $filters);

        $treasuryInQ = TreasuryTransaction::query()->where('type', 'revenue')
            ->whereDate('created_at', '>=', $fromStr)
            ->whereDate('created_at', '<=', $toStr);
        $this->applyTreasurySearch($treasuryInQ, $filters);

        $treasuryOutQ = TreasuryTransaction::query()->where('type', 'expense')
            ->whereDate('created_at', '>=', $fromStr)
            ->whereDate('created_at', '<=', $toStr);
        $this->applyTreasurySearch($treasuryOutQ, $filters);

        $periodStats = [
            'revenues_sum' => (float) (clone $revenuesQ)->sum('amount'),
            'revenues_count' => (clone $revenuesQ)->count(),
            'expenses_sum' => (float) (clone $expensesQ)->sum('amount'),
            'expenses_count' => (clone $expensesQ)->count(),
            'sales_sum' => (float) (clone $salesQ)->sum('sale_price'),
            'sales_down' => (float) (clone $salesQ)->sum('down_payment'),
            'sales_count' => (clone $salesQ)->count(),
            'treasury_in' => (float) (clone $treasuryInQ)->sum('amount'),
            'treasury_out' => (float) (clone $treasuryOutQ)->sum('amount'),
        ];

        $periodStats['net_treasury'] = $periodStats['treasury_in'] - $periodStats['treasury_out'];
        $periodStats['net_revenue_expense'] = $periodStats['revenues_sum'] - $periodStats['expenses_sum'];

        $allTime = [
            'treasury_in' => (float) TreasuryTransaction::query()->where('type', 'revenue')->sum('amount'),
            'treasury_out' => (float) TreasuryTransaction::query()->where('type', 'expense')->sum('amount'),
            'revenues_sum' => (float) Revenue::query()->sum('amount'),
            'expenses_sum' => (float) Expense::query()->sum('amount'),
        ];
        $allTime['treasury_net'] = $allTime['treasury_in'] - $allTime['treasury_out'];

        $contractsRemaining = (float) Contract::query()->sum('remaining_amount');
        $contractsOpenCount = (int) Contract::query()->where('remaining_amount', '>', 0)->count();

        $revenueRows = (clone $revenuesQ)->with(['client:id,name'])->latest('paid_at')->latest('id')->limit(15)->get();
        $expenseRows = (clone $expensesQ)->latest()->limit(15)->get();
        $saleRows = (clone $salesQ)->with(['client:id,name', 'property:id,name'])->latest('sale_date')->latest('id')->limit(15)->get();

        return view('reports.index', [
            'title' => 'التقارير | Mohaseb Aqary',
            'pageTitle' => 'التقارير',
            'project' => $project,
            'currencyLabel' => $currencyLabel,
            'filters' => $filters,
            'periodFrom' => $periodFrom,
            'periodTo' => $periodTo,
            'periodStats' => $periodStats,
            'allTime' => $allTime,
            'contractsRemaining' => $contractsRemaining,
            'contractsOpenCount' => $contractsOpenCount,
            'revenueRows' => $revenueRows,
            'expenseRows' => $expenseRows,
            'saleRows' => $saleRows,
            'modules' => $this->modules(),
        ]);
    }

    public function exportCsv(Project $project, Request $request): StreamedResponse
    {
        $setting = Setting::query()->first();
        $currencyLabel = strtoupper((string) ($setting?->currency ?? 'EGP')) === 'EGP' ? 'ج.م' : (string) ($setting?->currency ?? '');

        $filters = ListingFilters::fromRequest($request);
        $periodFrom = $filters->dateFrom ?? now()->startOfMonth()->startOfDay();
        $periodTo = $filters->dateTo ?? now()->endOfDay();
        if ($periodFrom->gt($periodTo)) {
            $periodTo = $periodFrom->copy()->endOfDay();
        }
        $fromStr = $periodFrom->toDateString();
        $toStr = $periodTo->toDateString();

        $filename = 'report-'.$project->id.'-'.$fromStr.'-'.$toStr.'.csv';
        $self = $this;

        return response()->streamDownload(function () use ($self, $project, $fromStr, $toStr, $currencyLabel, $filters): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Mohaseb Aqary — ملخص تقرير', $project->name]);
            fputcsv($out, ['من', $fromStr, 'إلى', $toStr, 'العملة', $currencyLabel]);
            fputcsv($out, []);

            $revenuesQ = Revenue::query()
                ->whereDate('paid_at', '>=', $fromStr)
                ->whereDate('paid_at', '<=', $toStr);
            $self->applyRevenueSearch($revenuesQ, $filters);
            $expensesQ = Expense::query()
                ->whereDate('created_at', '>=', $fromStr)
                ->whereDate('created_at', '<=', $toStr);
            $self->applyExpenseSearch($expensesQ, $filters);

            fputcsv($out, ['إجمالي التحصيلات', (string) (float) (clone $revenuesQ)->sum('amount'), 'عدد السجلات', (string) (clone $revenuesQ)->count()]);
            fputcsv($out, ['إجمالي المصروفات', (string) (float) (clone $expensesQ)->sum('amount'), 'عدد السجلات', (string) (clone $expensesQ)->count()]);
            fputcsv($out, ['المتبقي على العقود', (string) (float) Contract::query()->sum('remaining_amount')]);
            fputcsv($out, []);
            fputcsv($out, ['تحصيلات — التفاصيل']);
            fputcsv($out, ['id', 'paid_at', 'amount', 'client', 'category', 'notes']);
            foreach ((clone $revenuesQ)->with(['client:id,name'])->orderByDesc('paid_at')->orderByDesc('id')->cursor() as $r) {
                fputcsv($out, [
                    $r->id,
                    optional($r->paid_at)->format('Y-m-d'),
                    (string) (float) $r->amount,
                    $r->client?->name,
                    (string) ($r->category ?? ''),
                    (string) ($r->notes ?? ''),
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['مصروفات — التفاصيل']);
            fputcsv($out, ['id', 'created_at', 'amount', 'category', 'description']);
            foreach ((clone $expensesQ)->orderByDesc('id')->cursor() as $e) {
                fputcsv($out, [
                    $e->id,
                    $e->created_at?->format('Y-m-d H:i'),
                    (string) (float) $e->amount,
                    (string) ($e->category ?? ''),
                    (string) ($e->description ?? ''),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function applyRevenueSearch(\Illuminate\Database\Eloquent\Builder $q, ListingFilters $filters): void
    {
        if ($filters->q === '') {
            return;
        }
        $like = '%'.$filters->likeTerm().'%';
        $q->where(function ($w) use ($like): void {
            $w->where('notes', 'like', $like)
                ->orWhere('category', 'like', $like)
                ->orWhere('payment_method', 'like', $like)
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $like));
        });
    }

    private function applyExpenseSearch(\Illuminate\Database\Eloquent\Builder $q, ListingFilters $filters): void
    {
        if ($filters->q === '') {
            return;
        }
        $like = '%'.$filters->likeTerm().'%';
        $q->where(function ($w) use ($like): void {
            $w->where('category', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    private function applySaleSearch(\Illuminate\Database\Eloquent\Builder $q, ListingFilters $filters): void
    {
        if ($filters->q === '') {
            return;
        }
        $like = '%'.$filters->likeTerm().'%';
        $q->where(function ($w) use ($like): void {
            $w->where('broker_name', 'like', $like)
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like))
                ->orWhereHas('property', fn ($p) => $p->where('name', 'like', $like));
        });
    }

    private function applyTreasurySearch(\Illuminate\Database\Eloquent\Builder $q, ListingFilters $filters): void
    {
        if ($filters->q === '') {
            return;
        }
        $like = '%'.$filters->likeTerm().'%';
        $q->where('description', 'like', $like);
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
            'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
