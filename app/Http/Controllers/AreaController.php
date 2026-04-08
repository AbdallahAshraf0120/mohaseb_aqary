<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(): View
    {
        return view('areas.index', [
            'title' => 'المناطق | Mohaseb Aqary',
            'pageTitle' => 'المناطق',
            'areas' => Area::query()->withCount('properties')->orderBy('name')->paginate(15),
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('areas.create', [
            'title' => 'إضافة منطقة | Mohaseb Aqary',
            'pageTitle' => 'إضافة منطقة',
            'modules' => $this->modules(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:areas,name'],
        ]);

        Area::query()->create($data);

        return redirect()->route('areas.index')->with('success', 'تم إضافة المنطقة بنجاح.');
    }

    public function edit(Area $area): View
    {
        return view('areas.edit', [
            'title' => 'تعديل المنطقة | Mohaseb Aqary',
            'pageTitle' => 'تعديل المنطقة',
            'area' => $area,
            'modules' => $this->modules(),
        ]);
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:areas,name,' . $area->id],
        ]);

        $area->update($data);

        return redirect()->route('areas.index')->with('success', 'تم تحديث المنطقة بنجاح.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        if ($area->properties()->exists()) {
            return redirect()->route('areas.index')->with('success', 'لا يمكن حذف المنطقة لأنها مرتبطة بعقارات.');
        }

        $area->delete();

        return redirect()->route('areas.index')->with('success', 'تم حذف المنطقة بنجاح.');
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
