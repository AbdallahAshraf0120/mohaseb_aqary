<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()->listed()->orderBy('name')->get();
        $draftProjects = Project::query()
            ->where('is_active', true)
            ->where('is_draft', true)
            ->orderBy('name')
            ->get();

        $currentId = session('current_project_id');
        if ($currentId !== null && ! $projects->contains('id', (int) $currentId)) {
            session(['current_project_id' => $projects->first()?->id]);
        }

        if (session('current_project_id') === null && $projects->isNotEmpty()) {
            session(['current_project_id' => (int) $projects->first()->id]);
        }

        return view('projects.index', [
            'title' => 'المشاريع | Mohaseb Aqary',
            'pageTitle' => 'المشاريع',
            'projects' => $projects,
            'draftProjects' => $draftProjects,
            'modules' => $this->modules(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:projects,code'],
        ]);
        $data['is_active'] = true;
        $data['is_draft'] = false;

        $project = Project::query()->create($data);
        session(['current_project_id' => (int) $project->id]);

        return redirect()->route('properties.index', $project)->with('success', 'تم إنشاء المشروع. يمكنك الآن إدارة بياناته بشكل منفصل.');
    }

    public function toDraft(Project $managedProject): RedirectResponse
    {
        $managedProject->update(['is_draft' => true]);

        if ((int) session('current_project_id') === (int) $managedProject->id) {
            $next = Project::query()->listed()->orderBy('id')->value('id');
            session(['current_project_id' => $next]);
        }

        return redirect()->route('projects.index')->with('success', 'تم نقل المشروع إلى المسودة ولن يظهر في الشريط الجانبي حتى تستعيده.');
    }

    public function restore(Project $draftProject): RedirectResponse
    {
        $draftProject->update(['is_draft' => false]);

        return redirect()->route('projects.index')->with('success', 'تم إرجاع المشروع من المسودة وظهوره في القائمة.');
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
            'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'cashbox.index'],
            'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'expenses.index'],
            'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
            'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'remaining.index'],
        ];
    }
}
