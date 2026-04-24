<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Project;
use App\Services\CashboxLedgerService;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        private CashboxLedgerService $cashboxLedger,
    ) {}

    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Expense::query();
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->where('category', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }
        $filters->applyWhereDate($query, 'created_at');

        $expenseStats = [
            'sum_amount' => (float) (clone $query)->sum('amount'),
            'count' => (clone $query)->count(),
            'avg_amount' => (float) (clone $query)->avg('amount'),
        ];

        return view('expenses.index', [
            'title' => 'المصروفات | Mohaseb Aqary',
            'pageTitle' => 'المصروفات',
            'project' => $project,
            'expenseStats' => $expenseStats,
            'expenses' => $query->latest()->paginate(15)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'title' => 'إضافة مصروف | Mohaseb Aqary',
            'pageTitle' => 'إضافة مصروف',
            'modules' => $this->modules(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $expense = Expense::query()->create($data);
        $this->cashboxLedger->syncFromExpense($expense);

        return redirect()->route('expenses.index')->with('success', 'تم إضافة المصروف بنجاح.');
    }

    public function destroy(Project $project, Expense $expense): RedirectResponse
    {
        $this->cashboxLedger->removeExpense((int) $expense->id);
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'تم حذف المصروف بنجاح.');
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
