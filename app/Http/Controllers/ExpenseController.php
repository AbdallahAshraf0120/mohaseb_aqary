<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Project;
use App\Services\CashboxBalanceService;
use App\Services\CashboxLedgerService;
use App\Support\CurrentProject;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ExpenseController extends Controller
{
    public function __construct(
        private CashboxLedgerService $cashboxLedger,
        private CashboxBalanceService $cashboxBalanceService,
    ) {}

    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Expense::query();
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->where('category', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('expenseType', fn ($t) => $t->where('name', 'like', $like));
            });
        }
        $filters->applyWhereDate($query, 'spent_at');

        $approvedQuery = (clone $query)->where('approval_status', 'approved');
        $expenseStats = [
            'sum_amount' => (float) (clone $approvedQuery)->sum('amount'),
            'count' => (clone $approvedQuery)->count(),
            'avg_amount' => (float) (clone $approvedQuery)->avg('amount'),
        ];

        return view('expenses.index', [
            'title' => 'المصروفات | Mohaseb Aqary',
            'pageTitle' => 'المصروفات',
            'project' => $project,
            'expenseStats' => $expenseStats,
            'expenses' => $query->with('expenseType')->latest()->paginate(15)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'title' => 'إضافة مصروف | Mohaseb Aqary',
            'pageTitle' => 'إضافة مصروف',
            'expenseTypes' => $this->expenseTypes(),
            'modules' => $this->modules(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedExpenseData($request);

        $user = $request->user();
        $isAdmin = $user instanceof \App\Models\User && $user->isAdmin();
        $projectId = (int) app(CurrentProject::class)->id();

        try {
            $this->cashboxBalanceService->assertCanSpend($projectId, $data['amount']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $data['approval_status'] = $isAdmin ? 'approved' : 'pending';
        if ($isAdmin) {
            $data['approved_at'] = now();
            $data['approved_by'] = (int) $user->id;
        }
        $expense = Expense::query()->create($data);
        try {
            $this->cashboxLedger->syncFromExpense($expense);
        } catch (InvalidArgumentException $e) {
            $expense->delete();

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('expenses.index')->with('success', $isAdmin ? 'تم تسجيل المصروف واعتماده تلقائيًا.' : 'تم تسجيل المصروف كعملية معلقة حتى اعتماد الأدمن.');
    }

    public function show(Project $project, Expense $expense): View
    {
        $expense->load('expenseType');

        return view('expenses.show', [
            'title' => 'تفاصيل المصروف | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل المصروف',
            'project' => $project,
            'expense' => $expense,
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Project $project, Expense $expense): View
    {
        return view('expenses.edit', [
            'title' => 'تعديل المصروف | Mohaseb Aqary',
            'pageTitle' => 'تعديل المصروف',
            'project' => $project,
            'expense' => $expense,
            'expenseTypes' => $this->expenseTypes(),
            'modules' => $this->modules(),
        ]);
    }

    public function update(Request $request, Project $project, Expense $expense): RedirectResponse
    {
        $data = $this->validatedExpenseData($request);

        try {
            $this->cashboxBalanceService->assertCanSpend(
                (int) $project->id,
                $data['amount'],
                [
                    'reference_type' => Expense::class,
                    'reference_id' => (int) $expense->id,
                ]
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $wasApproved = ($expense->approval_status ?? 'approved') === 'approved';
        if ($wasApproved) {
            $data['approval_status'] = 'pending';
            $data['approved_at'] = null;
            $data['approved_by'] = null;
            $data['rejected_at'] = null;
            $data['rejected_by'] = null;
            $data['rejection_reason'] = null;
        }

        $expense->update($data);
        try {
            $this->cashboxLedger->syncFromExpense($expense->refresh());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = $wasApproved
            ? 'تم تحديث المصروف وأصبح معلقًا حتى إعادة الاعتماد.'
            : 'تم تحديث المصروف بنجاح.';

        return redirect()->route('expenses.index')->with('success', $message);
    }

    public function destroy(Request $request, Project $project, Expense $expense): RedirectResponse|JsonResponse
    {
        $this->cashboxLedger->removeExpense((int) $expense->id);
        $expense->delete();

        $message = 'تم حذف المصروف بنجاح.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'redirect' => route('expenses.index', $project),
            ]);
        }

        return redirect()->route('expenses.index')->with('success', $message);
    }

    /**
     * @return array{expense_type_id: int, category: string, amount: float|int|string, description: ?string, spent_at: string}
     */
    private function validatedExpenseData(Request $request): array
    {
        $projectId = (int) app(CurrentProject::class)->id();
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'expense_type_id' => [
                'required',
                'integer',
                Rule::exists('expense_types', 'id')->where(fn ($q) => $q->where('project_id', $projectId)),
            ],
            'description' => ['nullable', 'string'],
            'spent_at' => ['required', 'date'],
        ]);

        $type = ExpenseType::query()->findOrFail((int) $data['expense_type_id']);
        $data['category'] = (string) $type->name;

        return $data;
    }

    private function expenseTypes()
    {
        return ExpenseType::query()->orderBy('sort_order')->orderBy('name')->get();
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
            'expense-types' => ['label' => 'أنواع المصروفات', 'icon' => 'fa-tags', 'route' => 'expense-types.index'],
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
