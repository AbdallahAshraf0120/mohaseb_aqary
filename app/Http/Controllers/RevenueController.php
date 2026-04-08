<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRevenueRequest;
use App\Http\Requests\UpdateRevenueRequest;
use App\Models\Contract;
use App\Models\Revenue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RevenueController extends Controller
{
    public function index(): View
    {
        return view('revenues.index', [
            'title' => 'التحصيل | Mohaseb Aqary',
            'pageTitle' => 'التحصيل',
            'revenues' => Revenue::query()->with(['client:id,name', 'contract:id'])->latest()->paginate(15),
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('revenues.create', [
            'title' => 'تحصيل دفعة | Mohaseb Aqary',
            'pageTitle' => 'تحصيل دفعة',
            'contracts' => Contract::query()->with(['client:id,name', 'sale:id'])->where('remaining_amount', '>', 0)->latest()->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function store(StoreRevenueRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $revenue = Revenue::query()->create($validated);
        $this->recalculateContract((int) $revenue->contract_id);

        return redirect()->route('revenues.index')->with('success', 'تم تسجيل التحصيل وتحديث العقد بنجاح.');
    }

    public function show(Revenue $revenue): View
    {
        return view('revenues.show', [
            'title' => 'تفاصيل التحصيل | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل التحصيل',
            'revenue' => $revenue->load(['client', 'contract', 'sale']),
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Revenue $revenue): View
    {
        return view('revenues.edit', [
            'title' => 'تعديل التحصيل | Mohaseb Aqary',
            'pageTitle' => 'تعديل التحصيل',
            'revenue' => $revenue,
            'contracts' => Contract::query()->with(['client:id,name', 'sale:id'])->latest()->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdateRevenueRequest $request, Revenue $revenue): RedirectResponse
    {
        $oldContractId = (int) $revenue->contract_id;
        $revenue->update($request->validated());
        $this->recalculateContract($oldContractId);
        $this->recalculateContract((int) $revenue->contract_id);

        return redirect()->route('revenues.index')->with('success', 'تم تحديث التحصيل بنجاح.');
    }

    public function destroy(Revenue $revenue): RedirectResponse
    {
        $contractId = (int) $revenue->contract_id;
        $revenue->delete();
        $this->recalculateContract($contractId);

        return redirect()->route('revenues.index')->with('success', 'تم حذف التحصيل بنجاح.');
    }

    private function recalculateContract(int $contractId): void
    {
        $contract = Contract::query()->find($contractId);
        if (! $contract) {
            return;
        }

        $paid = (float) Revenue::query()->where('contract_id', $contractId)->sum('amount');
        $contract->update([
            'paid_amount' => $paid,
            'remaining_amount' => max(0, (float) $contract->total_price - $paid),
        ]);
    }

    private function modules(): array
    {
        return [
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
