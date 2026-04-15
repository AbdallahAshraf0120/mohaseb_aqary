<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLandRequest;
use App\Http\Requests\UpdateLandRequest;
use App\Models\Area;
use App\Models\Land;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LandController extends Controller
{
    public function index(): View
    {
        return view('lands.index', [
            'title' => 'الأراضي | Mohaseb Aqary',
            'pageTitle' => 'الأراضي',
            'lands' => Land::query()
                ->with('area:id,name')
                ->withCount('properties')
                ->latest()
                ->paginate(15),
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('lands.create', [
            'title' => 'إضافة أرض | Mohaseb Aqary',
            'pageTitle' => 'إضافة أرض',
            'areas' => Area::query()->select('id', 'name')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function store(StoreLandRequest $request): RedirectResponse
    {
        Land::query()->create($request->validated());

        return redirect()->route('lands.index')->with('success', 'تم إضافة الأرض بنجاح.');
    }

    public function edit(Project $project, Land $land): View
    {
        return view('lands.edit', [
            'title' => 'تعديل الأرض | Mohaseb Aqary',
            'pageTitle' => 'تعديل الأرض',
            'land' => $land,
            'areas' => Area::query()->select('id', 'name')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdateLandRequest $request, Project $project, Land $land): RedirectResponse
    {
        $land->update($request->validated());

        return redirect()->route('lands.index')->with('success', 'تم تحديث الأرض بنجاح.');
    }

    public function destroy(Project $project, Land $land): RedirectResponse
    {
        if ($land->properties()->exists()) {
            return redirect()->route('lands.index')->with('success', 'لا يمكن حذف الأرض لأنها مرتبطة بعقارات.');
        }

        $land->delete();

        return redirect()->route('lands.index')->with('success', 'تم حذف الأرض بنجاح.');
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
            'lands' => ['label' => 'الأراضي', 'icon' => 'fa-map-location-dot', 'route' => 'lands.index'],
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
