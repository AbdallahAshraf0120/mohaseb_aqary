<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Contracts\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('clients.index', [
            'title' => 'العملاء | Mohaseb Aqary',
            'pageTitle' => 'العملاء',
            'clients' => Client::query()->withCount('sales')->latest()->paginate(15),
            'modules' => $this->modules(),
        ]);
    }

    public function show(Client $client): View
    {
        return view('clients.show', [
            'title' => 'تفاصيل العميل | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل العميل',
            'client' => $client->load(['sales.property:id,name']),
            'modules' => $this->modules(),
        ]);
    }

    private function modules(): array
    {
        return [
            'role-permission' => ['label' => 'Role & Permission', 'icon' => 'fa-user-shield', 'route' => 'modules.show'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
            'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'clients.index'],
            'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'contracts.index'],
            'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'sales.index'],
            'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'revenues.index'],
            'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'modules.show'],
            'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'modules.show'],
            'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'modules.show'],
            'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'modules.show'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'modules.show'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'modules.show'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'modules.show'],
        ];
    }
}
