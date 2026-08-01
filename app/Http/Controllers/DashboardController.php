<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Debt;
use App\Models\FundTransfer;
use App\Models\Project;
use App\Models\Property;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Shareholder;
use App\Models\TreasuryTransaction;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(Project $project): View
    {
        $setting = Setting::query()->first();
        $currency = $setting?->currency ?? 'EGP';

        $approved = TreasuryTransaction::query()->where('approval_status', 'approved');

        // رصيد الصندوق يشمل كل الحركات (بما فيها تحويلات الصناديق)
        $treasuryInAll = (float) (clone $approved)->where('type', 'revenue')->sum('amount');
        $treasuryOutAll = (float) (clone $approved)->where('type', 'expense')->sum('amount');
        $balance = $treasuryInAll - $treasuryOutAll;

        // كروت الوارد/المصروف: بدون تحويلات صناديق ولا حركات جاري المساهمين (دي مش تحصيل ولا مصروف تشغيلي)
        $operating = (clone $approved)->where(function ($q): void {
            $q->whereNull('reference_type')
                ->orWhereNotIn('reference_type', [FundTransfer::class, 'shareholder_ledger_entry']);
        });
        $treasuryIn = (float) (clone $operating)->where('type', 'revenue')->sum('amount');
        $treasuryOut = (float) (clone $operating)->where('type', 'expense')->sum('amount');

        $shareholderIn = (float) (clone $approved)->where('type', 'revenue')->where('reference_type', 'shareholder_ledger_entry')->sum('amount');
        $shareholderOut = (float) (clone $approved)->where('type', 'expense')->where('reference_type', 'shareholder_ledger_entry')->sum('amount');
        $transfersIn = round($treasuryInAll - $treasuryIn - $shareholderIn, 5);
        $transfersOut = round($treasuryOutAll - $treasuryOut - $shareholderOut, 5);
        $shareholderIn = round($shareholderIn, 5);
        $shareholderOut = round($shareholderOut, 5);

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
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->where('approval_status', 'approved')
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
            'project' => $project,
            'modules' => $this->modules(),
            'currency' => $currency,
            'treasuryIn' => $treasuryIn,
            'treasuryOut' => $treasuryOut,
            'transfersIn' => $transfersIn,
            'transfersOut' => $transfersOut,
            'shareholderIn' => $shareholderIn,
            'shareholderOut' => $shareholderOut,
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
            'debts' => ['label' => 'ذمم دائنة', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
