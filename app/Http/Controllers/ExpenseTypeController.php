<?php

namespace App\Http\Controllers;

use App\Models\ExpenseType;
use App\Models\Project;
use App\Support\CurrentProject;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseTypeController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = ExpenseType::query()->withCount('expenses');
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where('name', 'like', $like);
        }
        $filters->applyWhereDate($query, 'created_at');

        return view('expense-types.index', [
            'title' => 'أنواع المصروفات | Mohaseb Aqary',
            'pageTitle' => 'أنواع المصروفات',
            'project' => $project,
            'typeCount' => (clone $query)->count(),
            'expenseTypes' => $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('expense-types.create', [
            'title' => 'إضافة نوع مصروف | Mohaseb Aqary',
            'pageTitle' => 'إضافة نوع مصروف',
            'modules' => $this->modules(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $projectId = (int) app(CurrentProject::class)->id();
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_types', 'name')->where(fn ($q) => $q->where('project_id', $projectId)),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);
        $data['project_id'] = $projectId;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        ExpenseType::query()->create($data);

        return redirect()->route('expense-types.index')->with('success', 'تم إضافة نوع المصروف بنجاح.');
    }

    public function edit(Project $project, ExpenseType $expenseType): View
    {
        return view('expense-types.edit', [
            'title' => 'تعديل نوع المصروف | Mohaseb Aqary',
            'pageTitle' => 'تعديل نوع المصروف',
            'expenseType' => $expenseType,
            'modules' => $this->modules(),
        ]);
    }

    public function update(Request $request, Project $project, ExpenseType $expenseType): RedirectResponse
    {
        $projectId = (int) app(CurrentProject::class)->id();
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expense_types', 'name')
                    ->ignore($expenseType->id)
                    ->where(fn ($q) => $q->where('project_id', $projectId)),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $expenseType->update($data);

        return redirect()->route('expense-types.index')->with('success', 'تم تحديث نوع المصروف بنجاح.');
    }

    public function destroy(Project $project, ExpenseType $expenseType): RedirectResponse
    {
        if ($expenseType->expenses()->exists()) {
            return redirect()->route('expense-types.index')
                ->with('error', 'لا يمكن حذف نوع المصروف لأنه مرتبط بمصروفات مسجّلة.');
        }

        $expenseType->delete();

        return redirect()->route('expense-types.index')->with('success', 'تم حذف نوع المصروف.');
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
            'facings' => ['label' => 'الوجهات', 'icon' => 'fa-compass-drafting', 'route' => 'facings.index'],
            'expense-types' => ['label' => 'أنواع المصروفات', 'icon' => 'fa-tags', 'route' => 'expense-types.index'],
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
            'debts' => ['label' => 'ذمم دائنة', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
