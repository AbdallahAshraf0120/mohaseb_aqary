<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Setting;
use App\Models\TreasuryTransaction;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CashboxController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $opening = 0.0;

        $inQuery = TreasuryTransaction::query()->where('type', 'revenue');
        $outQuery = TreasuryTransaction::query()->where('type', 'expense');
        $filters->applyWhereDate($inQuery, 'created_at');
        $filters->applyWhereDate($outQuery, 'created_at');
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $inQuery->where('description', 'like', $like);
            $outQuery->where('description', 'like', $like);
        }

        $treasuryIn = (float) (clone $inQuery)->sum('amount');
        $treasuryOut = (float) (clone $outQuery)->sum('amount');
        $currentBalance = $opening + $treasuryIn - $treasuryOut;

        $txQuery = TreasuryTransaction::query()->latest();
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $txQuery->where('description', 'like', $like);
        }
        $filters->applyWhereDate($txQuery, 'created_at');

        $setting = Setting::query()->first();
        $currency = $setting?->currency ?? 'EGP';

        return view('cashbox.index', [
            'title' => 'الصندوق | Mohaseb Aqary',
            'pageTitle' => 'الصندوق',
            'project' => $project,
            'currency' => $currency,
            'openingBalance' => $opening,
            'revenuesTotal' => $treasuryIn,
            'expensesTotal' => $treasuryOut,
            'currentBalance' => $currentBalance,
            'transactions' => $txQuery->paginate(15)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:revenue,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
        ]);

        TreasuryTransaction::query()->create([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()
            ->route('cashbox.index', [$project])
            ->with('success', 'تم تسجيل حركة الصندوق بنجاح.');
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
