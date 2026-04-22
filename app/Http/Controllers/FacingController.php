<?php

namespace App\Http\Controllers;

use App\Models\Facing;
use App\Models\Project;
use App\Support\CurrentProject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacingController extends Controller
{
    public function index(): View
    {
        return view('facings.index', [
            'title' => 'الوجهات | Mohaseb Aqary',
            'pageTitle' => 'الوجهات',
            'facings' => Facing::query()->orderBy('sort_order')->orderBy('name')->paginate(20),
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('facings.create', [
            'title' => 'إضافة وجهة | Mohaseb Aqary',
            'pageTitle' => 'إضافة وجهة',
            'modules' => $this->modules(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $projectId = (int) app(CurrentProject::class)->id();
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('facings', 'code')->where(fn ($q) => $q->where('project_id', $projectId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);
        $data['project_id'] = $projectId;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        Facing::query()->create($data);

        return redirect()->route('facings.index')->with('success', 'تم إضافة الوجهة بنجاح.');
    }

    public function edit(Project $project, Facing $facing): View
    {
        return view('facings.edit', [
            'title' => 'تعديل الوجهة | Mohaseb Aqary',
            'pageTitle' => 'تعديل الوجهة',
            'facing' => $facing,
            'modules' => $this->modules(),
        ]);
    }

    public function update(Request $request, Project $project, Facing $facing): RedirectResponse
    {
        $projectId = (int) app(CurrentProject::class)->id();
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('facings', 'code')
                    ->ignore($facing->id)
                    ->where(fn ($q) => $q->where('project_id', $projectId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $facing->update($data);

        return redirect()->route('facings.index')->with('success', 'تم تحديث الوجهة بنجاح.');
    }

    public function destroy(Project $project, Facing $facing): RedirectResponse
    {
        $facing->delete();

        return redirect()->route('facings.index')->with('success', 'تم حذف الوجهة.');
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
            'facings' => ['label' => 'الوجهات', 'icon' => 'fa-compass-drafting', 'route' => 'facings.index'],
            'lands' => ['label' => 'الأراضي', 'icon' => 'fa-map-location-dot', 'route' => 'lands.index'],
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
