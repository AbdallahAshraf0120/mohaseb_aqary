<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLandRequest;
use App\Http\Requests\UpdateLandRequest;
use App\Models\Area;
use App\Models\Land;
use App\Models\Project;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LandController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Land::query()
            ->with('area:id,name')
            ->withCount('properties');
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->where('name', 'like', $like)
                    ->orWhereHas('area', fn ($a) => $a->where('name', 'like', $like));
            });
        }
        $filters->applyWhereDate($query, 'created_at');

        $landIds = (clone $query)->pluck('id');
        $landKpis = [
            'count' => $landIds->count(),
            'with_props' => $landIds->isEmpty()
                ? 0
                : (int) Land::query()->whereKey($landIds)->has('properties')->count(),
        ];

        return view('lands.index', [
            'title' => 'الأراضي | Mohaseb Aqary',
            'pageTitle' => 'الأراضي',
            'project' => $project,
            'landKpis' => $landKpis,
            'lands' => $query->latest()->paginate(15)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    public function create(Project $project): View
    {
        return view('lands.create', [
            'title' => 'إضافة أرض | Mohaseb Aqary',
            'pageTitle' => 'إضافة أرض',
            'project' => $project,
            'areas' => Area::query()->select('id', 'name')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function store(Project $project, StoreLandRequest $request): RedirectResponse
    {
        Land::query()->create($request->validated());

        return redirect()->route('lands.index', $project)->with('success', 'تم إضافة الأرض بنجاح.');
    }

    public function edit(Project $project, Land $land): View
    {
        return view('lands.edit', [
            'title' => 'تعديل الأرض | Mohaseb Aqary',
            'pageTitle' => 'تعديل الأرض',
            'project' => $project,
            'land' => $land,
            'areas' => Area::query()->select('id', 'name')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdateLandRequest $request, Project $project, Land $land): RedirectResponse
    {
        $land->update($request->validated());

        return redirect()->route('lands.index', $project)->with('success', 'تم تحديث الأرض بنجاح.');
    }

    public function destroy(Project $project, Land $land): RedirectResponse
    {
        if ($land->properties()->exists()) {
            return redirect()->route('lands.index', $project)->with('success', 'لا يمكن حذف الأرض لأنها مرتبطة بعقارات.');
        }

        $land->delete();

        return redirect()->route('lands.index', $project)->with('success', 'تم حذف الأرض بنجاح.');
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
            'debts' => ['label' => 'ذمم دائنة', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
