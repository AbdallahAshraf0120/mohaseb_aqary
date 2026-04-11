<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Debt;
use App\Models\Property;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Shareholder;
use App\Models\TreasuryTransaction;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $setting = Setting::query()->first();
        $currency = $setting?->currency ?? 'EGP';

        $treasuryIn = (float) TreasuryTransaction::query()->where('type', 'revenue')->sum('amount');
        $treasuryOut = (float) TreasuryTransaction::query()->where('type', 'expense')->sum('amount');
        $balance = $treasuryIn - $treasuryOut;

        $stats = [
            'properties' => Property::query()->count(),
            'clients' => Client::query()->count(),
            'sales' => Sale::query()->count(),
            'contracts_with_balance' => Contract::query()->where('remaining_amount', '>', 0)->count(),
            'contracts_total' => Contract::query()->count(),
            'remaining_total' => (float) Contract::query()->sum('remaining_amount'),
            'areas' => Area::query()->count(),
            'shareholders' => Shareholder::query()->count(),
            'debts_open' => Debt::query()->where('status', 'open')->count(),
            'revenues_this_month' => (float) Revenue::query()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        $recentSales = Sale::query()
            ->with(['client:id,name', 'property:id,name'])
            ->latest()
            ->limit(6)
            ->get();

        $recentRevenues = Revenue::query()
            ->with(['client:id,name'])
            ->latest('paid_at')
            ->latest('id')
            ->limit(6)
            ->get();

        return view('dashboard', [
            'title' => 'لوحة التحكم | Mohaseb Aqary',
            'pageTitle' => 'لوحة التحكم',
            'modules' => $this->modules(),
            'currency' => $currency,
            'treasuryIn' => $treasuryIn,
            'treasuryOut' => $treasuryOut,
            'balance' => $balance,
            'stats' => $stats,
            'recentSales' => $recentSales,
            'recentRevenues' => $recentRevenues,
        ]);
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
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
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
