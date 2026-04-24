<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Sale;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Client::query()->withCount('sales');
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('national_id', 'like', $like);
            });
        }
        $filters->applyWhereDate($query, 'created_at');

        $clientIds = (clone $query)->pluck('id');
        $clientKpis = [
            'count' => $clientIds->count(),
            'with_sales' => $clientIds->isEmpty()
                ? 0
                : (int) Client::query()->whereKey($clientIds)->whereHas('sales')->count(),
            'sales_ops' => $clientIds->isEmpty()
                ? 0
                : (int) Sale::query()->whereIn('client_id', $clientIds)->count(),
        ];

        return view('clients.index', [
            'title' => 'العملاء | Mohaseb Aqary',
            'pageTitle' => 'العملاء',
            'project' => $project,
            'clientKpis' => $clientKpis,
            'clients' => $query->latest()->paginate(15)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    public function show(Project $project, Client $client): View
    {
        $client->load([
            'sales' => function ($query): void {
                $query->orderByDesc('sale_date')
                    ->orderByDesc('id')
                    ->with([
                        'property:id,name,mezzanine_floors',
                        'contract.revenues' => function ($q): void {
                            $q->orderBy('paid_at')->orderBy('id');
                        },
                    ]);
            },
        ]);

        $sales = $client->sales;
        $stats = [
            'sales_count' => $sales->count(),
            'total_sale_price' => round((float) $sales->sum(static fn (Sale $s) => (float) $s->sale_price), 2),
            'total_down_payment' => round((float) $sales->sum(static fn (Sale $s) => (float) $s->down_payment), 2),
            'total_remaining_contracts' => round((float) $sales->sum(static fn (Sale $s) => (float) ($s->contract?->remaining_amount ?? 0)), 2),
            'total_collected_revenues' => round((float) Revenue::query()->where('client_id', $client->id)->sum('amount'), 2),
        ];

        return view('clients.show', [
            'title' => 'بروفايل العميل | Mohaseb Aqary',
            'pageTitle' => 'بروفايل العميل',
            'project' => $project,
            'client' => $client,
            'stats' => $stats,
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
            'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
