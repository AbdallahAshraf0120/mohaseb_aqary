<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Project;
use App\Services\InstallmentDueService;
use App\Support\ListingFilters;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RemainingController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Contract::query()->active()->with(['client:id,name', 'property:id,name'])->where('remaining_amount', '>', 0);
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->whereHas('client', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like))
                    ->orWhereHas('property', fn ($p) => $p->where('name', 'like', $like));
            });
        }
        $filters->applyWhereDate($query, 'created_at');

        $remainingKpis = [
            'remaining' => (float) (clone $query)->sum('remaining_amount'),
            'count' => (clone $query)->count(),
        ];

        return view('remaining.index', [
            'title' => 'المتبقي | Mohaseb Aqary',
            'pageTitle' => 'المتبقي',
            'project' => $project,
            'remainingKpis' => $remainingKpis,
            'contracts' => $query->latest()->paginate(15)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    /**
     * الأقساط المتأخرة والمستحقة قريبًا.
     */
    public function due(Project $project, Request $request, InstallmentDueService $service): View|StreamedResponse
    {
        $scope = (string) $request->query('scope', 'overdue');
        if (! in_array($scope, ['overdue', 'week', 'month'], true)) {
            $scope = 'overdue';
        }
        $today = now()->startOfDay();
        $until = match ($scope) {
            'week' => $today->copy()->addDays(7)->endOfDay(),
            'month' => $today->copy()->addDays(30)->endOfDay(),
            default => $today->copy()->subDay()->endOfDay(),
        };
        $from = $scope === 'overdue' ? null : $today->copy();

        $rows = $service->dueRows((int) $project->id, $until, $from);
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $rows = $rows->filter(function (array $r) use ($q): bool {
                $hay = mb_strtolower(($r['client']?->name ?? '').' '.($r['client']?->phone ?? '').' '.($r['property']?->name ?? ''));

                return str_contains($hay, mb_strtolower($q));
            })->values();
        }

        $all = $service->dueRows((int) $project->id, $today->copy()->addDays(30)->endOfDay());
        $kpis = [
            'overdue_total' => (float) $all->where('days_late', '>', 0)->sum('balance'),
            'overdue_clients' => $all->where('days_late', '>', 0)->map(fn ($r) => $r['client']?->id)->filter()->unique()->count(),
            'week_total' => (float) $all->filter(fn ($r) => $r['days_late'] === 0 && $r['due_date']->lte($today->copy()->addDays(7)))->sum('balance'),
            'month_total' => (float) $all->where('days_late', 0)->sum('balance'),
        ];

        if ($request->query('export') === 'csv') {
            return response()->streamDownload(function () use ($rows): void {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['العميل', 'الموبايل', 'العقار', 'الوحدة', 'البند', 'تاريخ الاستحقاق', 'قيمة البند', 'المسدد', 'المتبقي', 'أيام التأخير']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r['client']?->name, $r['client']?->phone, $r['property']?->name, $r['sale']->unitLabel(),
                        $r['label'] ?: 'قسط '.$r['number'], $r['due_date']->toDateString(),
                        round($r['amount'], 2), round($r['paid'], 2), round($r['balance'], 2), $r['days_late'],
                    ]);
                }
                fclose($out);
            }, 'installments-'.$scope.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('remaining.due', [
            'title' => 'الأقساط المستحقة | Mohaseb Aqary',
            'pageTitle' => 'الأقساط المستحقة والمتأخرة',
            'project' => $project,
            'rows' => $rows,
            'scope' => $scope,
            'kpis' => $kpis,
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
