<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\TreasuryTransaction;
use App\Services\ProjectReportsExcelExporter;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $setting = Setting::query()->first();
        $currencyLabel = strtoupper((string) ($setting?->currency ?? 'EGP')) === 'EGP' ? 'ج.م' : (string) ($setting?->currency ?? '');

        $w = $this->buildFilteredQueries($request);

        $snap = $this->financialSnapshots($w);

        $revenueRows = (clone $w['revenuesQ'])->with(['client:id,name'])->latest('paid_at')->latest('id')->limit(15)->get();
        $expenseRows = (clone $w['expensesQ'])->latest()->limit(15)->get();
        $saleRows = (clone $w['salesQ'])->with(['client:id,name', 'property:id,name'])->latest('sale_date')->latest('id')->limit(15)->get();

        return view('reports.index', [
            'title' => 'التقارير | Mohaseb Aqary',
            'pageTitle' => 'التقارير',
            'project' => $project,
            'currencyLabel' => $currencyLabel,
            'filters' => $w['filters'],
            'periodFrom' => $w['periodFrom'],
            'periodTo' => $w['periodTo'],
            'periodStats' => $snap['periodStats'],
            'allTime' => $snap['allTime'],
            'contractsRemaining' => $snap['contractsRemaining'],
            'contractsOpenCount' => $snap['contractsOpenCount'],
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

        $w = $this->buildFilteredQueries($request);
        $fromStr = $w['fromStr'];
        $toStr = $w['toStr'];
        $filters = $w['filters'];

        $filename = 'report-'.$project->id.'-'.$fromStr.'-'.$toStr.'.csv';
        $self = $this;

        return response()->streamDownload(function () use ($project, $fromStr, $toStr, $currencyLabel, $w): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Mohaseb Aqary — ملخص تقرير', $project->name]);
            fputcsv($out, ['من', $fromStr, 'إلى', $toStr, 'العملة', $currencyLabel]);
            fputcsv($out, []);

            $revenuesQ = clone $w['revenuesQ'];
            $expensesQ = clone $w['expensesQ'];

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

    public function exportExcel(Project $project, Request $request): StreamedResponse
    {
        $setting = Setting::query()->first();
        $currencyLabel = strtoupper((string) ($setting?->currency ?? 'EGP')) === 'EGP' ? 'ج.م' : (string) ($setting?->currency ?? '');

        $w = $this->buildFilteredQueries($request);
        $snap = $this->financialSnapshots($w);

        $contractsQ = Contract::query();

        $exporter = new ProjectReportsExcelExporter(
            project: $project,
            currencyLabel: $currencyLabel,
            fromStr: $w['fromStr'],
            toStr: $w['toStr'],
            filters: $w['filters'],
            periodStats: $snap['periodStats'],
            allTime: $snap['allTime'],
            contractsRemaining: $snap['contractsRemaining'],
            contractsOpenCount: $snap['contractsOpenCount'],
            revenuesQuery: $w['revenuesQ'],
            expensesQuery: $w['expensesQ'],
            salesQuery: $w['salesQ'],
            treasuryInQuery: $w['treasuryInQ'],
            treasuryOutQuery: $w['treasuryOutQ'],
            contractsQuery: $contractsQ,
        );

        return response()->streamDownload(function () use ($exporter): void {
            $exporter->stream();
        }, $exporter->downloadFilename(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Project $project, Request $request): Response
    {
        @ini_set('memory_limit', '512M');

        $setting = Setting::query()->first();
        $currencyLabel = strtoupper((string) ($setting?->currency ?? 'EGP')) === 'EGP' ? 'ج.م' : (string) ($setting?->currency ?? '');

        $w = $this->buildFilteredQueries($request);
        $snap = $this->financialSnapshots($w);

        $revenues = (clone $w['revenuesQ'])->with(['client:id,name'])->orderByDesc('paid_at')->orderByDesc('id')->get();
        $expenses = (clone $w['expensesQ'])->orderByDesc('id')->get();
        $sales = (clone $w['salesQ'])->with(['client:id,name', 'property:id,name'])->orderByDesc('sale_date')->orderByDesc('id')->get();
        $treasuryIn = (clone $w['treasuryInQ'])->orderByDesc('created_at')->orderByDesc('id')->get();
        $treasuryOut = (clone $w['treasuryOutQ'])->orderByDesc('created_at')->orderByDesc('id')->get();
        $contracts = Contract::query()->with(['client:id,name', 'property:id,name'])->orderBy('id')->get();

        $ps = $snap['periodStats'];
        $at = $snap['allTime'];

        $summaryPeriod = [
            'تحصيلات الفترة (إجمالي)' => $ps['revenues_sum'],
            'عدد إيصالات التحصيل' => $ps['revenues_count'],
            'مصروفات الفترة (إجمالي)' => $ps['expenses_sum'],
            'عدد سجلات المصروفات' => $ps['expenses_count'],
            'صافي (تحصيل − مصروف)' => $ps['net_revenue_expense'],
            'مبيعات الفترة (إجمالي)' => $ps['sales_sum'],
            'مجموع المقدمات' => $ps['sales_down'],
            'عدد المبيعات' => $ps['sales_count'],
            'صندوق الفترة — وارد' => $ps['treasury_in'],
            'صندوق الفترة — صادر' => $ps['treasury_out'],
            'صندوق الفترة — صافي' => $ps['net_treasury'],
        ];

        $summaryAllTime = [
            'تحصيلات متراكمة (كل الفترات)' => $at['revenues_sum'],
            'مصروفات متراكمة' => $at['expenses_sum'],
            'وارد الصندوق اليدوي' => $at['treasury_in'],
            'صادر الصندوق اليدوي' => $at['treasury_out'],
            'صافي الصندوق' => $at['treasury_net'],
            'المتبقي على العقود' => $snap['contractsRemaining'],
            'عدد العقود ذات متبقٍ' => $snap['contractsOpenCount'],
        ];

        $html = view('reports.export-pdf', [
            'project' => $project,
            'currencyLabel' => $currencyLabel,
            'filters' => $w['filters'],
            'fromStr' => $w['fromStr'],
            'toStr' => $w['toStr'],
            'summaryPeriod' => $summaryPeriod,
            'summaryAllTime' => $summaryAllTime,
            'revenues' => $revenues,
            'expenses' => $expenses,
            'sales' => $sales,
            'treasuryIn' => $treasuryIn,
            'treasuryOut' => $treasuryOut,
            'contracts' => $contracts,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 12,
            'margin_bottom' => 14,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);

        $filename = 'report-'.$project->id.'-'.$w['fromStr'].'-'.$w['toStr'].'.pdf';

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array{
     *     filters: ListingFilters,
     *     periodFrom: Carbon,
     *     periodTo: Carbon,
     *     fromStr: string,
     *     toStr: string,
     *     revenuesQ: Builder,
     *     expensesQ: Builder,
     *     salesQ: Builder,
     *     treasuryInQ: Builder,
     *     treasuryOutQ: Builder
     * }
     */
    private function buildFilteredQueries(Request $request): array
    {
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
            ->whereDate('paid_at', '<=', $toStr)
            ->where('approval_status', 'approved');
        $this->applyRevenueSearch($revenuesQ, $filters);

        $expensesQ = Expense::query()
            ->whereDate('created_at', '>=', $fromStr)
            ->whereDate('created_at', '<=', $toStr)
            ->where('approval_status', 'approved');
        $this->applyExpenseSearch($expensesQ, $filters);

        $salesQ = Sale::query()
            ->whereDate('sale_date', '>=', $fromStr)
            ->whereDate('sale_date', '<=', $toStr)
            ->where('approval_status', 'approved');
        $this->applySaleSearch($salesQ, $filters);

        $treasuryInQ = TreasuryTransaction::query()->where('type', 'revenue')
            ->whereDate('created_at', '>=', $fromStr)
            ->whereDate('created_at', '<=', $toStr)
            ->where('approval_status', 'approved');
        $this->applyTreasurySearch($treasuryInQ, $filters);

        $treasuryOutQ = TreasuryTransaction::query()->where('type', 'expense')
            ->whereDate('created_at', '>=', $fromStr)
            ->whereDate('created_at', '<=', $toStr)
            ->where('approval_status', 'approved');
        $this->applyTreasurySearch($treasuryOutQ, $filters);

        return [
            'filters' => $filters,
            'periodFrom' => $periodFrom,
            'periodTo' => $periodTo,
            'fromStr' => $fromStr,
            'toStr' => $toStr,
            'revenuesQ' => $revenuesQ,
            'expensesQ' => $expensesQ,
            'salesQ' => $salesQ,
            'treasuryInQ' => $treasuryInQ,
            'treasuryOutQ' => $treasuryOutQ,
        ];
    }

    /**
     * @param  array<string, mixed>  $w
     * @return array{periodStats: array<string, float|int>, allTime: array<string, float>, contractsRemaining: float, contractsOpenCount: int}
     */
    private function financialSnapshots(array $w): array
    {
        /** @var Builder $revenuesQ */
        $revenuesQ = $w['revenuesQ'];
        /** @var Builder $expensesQ */
        $expensesQ = $w['expensesQ'];
        /** @var Builder $salesQ */
        $salesQ = $w['salesQ'];
        /** @var Builder $treasuryInQ */
        $treasuryInQ = $w['treasuryInQ'];
        /** @var Builder $treasuryOutQ */
        $treasuryOutQ = $w['treasuryOutQ'];

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
            'treasury_in' => (float) TreasuryTransaction::query()->where('type', 'revenue')->where('approval_status', 'approved')->sum('amount'),
            'treasury_out' => (float) TreasuryTransaction::query()->where('type', 'expense')->where('approval_status', 'approved')->sum('amount'),
            'revenues_sum' => (float) Revenue::query()->where('approval_status', 'approved')->sum('amount'),
            'expenses_sum' => (float) Expense::query()->where('approval_status', 'approved')->sum('amount'),
        ];
        $allTime['treasury_net'] = $allTime['treasury_in'] - $allTime['treasury_out'];

        return [
            'periodStats' => $periodStats,
            'allTime' => $allTime,
            'contractsRemaining' => (float) Contract::query()->sum('remaining_amount'),
            'contractsOpenCount' => (int) Contract::query()->where('remaining_amount', '>', 0)->count(),
        ];
    }

    private function applyRevenueSearch(Builder $q, ListingFilters $filters): void
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

    private function applyExpenseSearch(Builder $q, ListingFilters $filters): void
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

    private function applySaleSearch(Builder $q, ListingFilters $filters): void
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

    private function applyTreasurySearch(Builder $q, ListingFilters $filters): void
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
            'debts' => ['label' => 'ذمم دائنة', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
