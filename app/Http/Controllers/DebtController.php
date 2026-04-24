<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDebtRequest;
use App\Http\Requests\UpdateDebtRequest;
use App\Models\Client;
use App\Models\Debt;
use App\Models\Project;
use App\Services\CashboxLedgerService;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DebtController extends Controller
{
    public function __construct(private readonly CashboxLedgerService $cashboxLedger) {}

    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Debt::query()->with('client:id,name,phone');
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->where('creditor_name', 'like', $like)
                    ->orWhere('purchase_description', 'like', $like)
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like));
            });
        }
        $filters->applyWhereDate($query, 'created_at');

        $debtKpis = [
            'total_amount' => (float) (clone $query)->sum('total_amount'),
            'paid_amount' => (float) (clone $query)->sum('paid_amount'),
            'remaining_amount' => (float) (clone $query)->sum('remaining_amount'),
        ];

        return view('debts.index', [
            'title' => 'ذمم دائنة على المشروع | Mohaseb Aqary',
            'pageTitle' => 'ذمم دائنة (مستحقات موردين)',
            'project' => $project,
            'debtKpis' => $debtKpis,
            'debts' => $query->latest()->paginate(15)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    public function create(Project $project): View
    {
        return view('debts.create', [
            'title' => 'إضافة ذمة دائنة | Mohaseb Aqary',
            'pageTitle' => 'إضافة ذمة دائنة',
            'project' => $project,
            'debt' => new Debt,
            'modules' => $this->modules(),
        ]);
    }

    public function store(Project $project, StoreDebtRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $payload = $this->normalizedDebtPayload($project, $data);

        Debt::query()->create($payload);

        return redirect()->route('debts.index', $project)->with('success', 'تم تسجيل الذمة الدائنة بنجاح.');
    }

    public function edit(Project $project, Debt $debt): View
    {
        $debt->load(['debtPayments' => static fn ($q) => $q->latest()]);

        return view('debts.edit', [
            'title' => 'تعديل ذمة دائنة | Mohaseb Aqary',
            'pageTitle' => 'تعديل ذمة دائنة',
            'project' => $project,
            'debt' => $debt,
            'modules' => $this->modules(),
        ]);
    }

    public function payFromCashbox(Request $request, Project $project, Debt $debt): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        $remaining = round((float) $debt->remaining_amount, 2);
        if ($amount - $remaining > 0.009) {
            return redirect()
                ->route('debts.edit', [$project, $debt])
                ->withErrors(['pay_amount' => 'المبلغ أكبر من المتبقي في الذمة ('.number_format($remaining, 2).' ج.م).'])
                ->withInput();
        }

        DB::transaction(function () use ($debt, $amount, $validated): void {
            $payment = $debt->debtPayments()->create([
                'amount' => $amount,
                'note' => $validated['note'] ?? null,
            ]);
            $this->cashboxLedger->syncFromDebtPayment($payment->fresh(['debt']));

            $debt->refresh();
            $total = round((float) $debt->total_amount, 2);
            $newPaid = round(min((float) $debt->paid_amount + $amount, $total), 2);
            $newRemaining = round(max(0.0, $total - $newPaid), 2);
            $debt->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $newRemaining,
                'status' => $newRemaining > 0.01 ? 'open' : 'closed',
            ]);
        });

        return redirect()
            ->route('debts.edit', [$project, $debt])
            ->with('success', 'تم تسجيل السداد من الصندوق وإضافة حركة مصروف مرتبطة بالذمة.');
    }

    public function update(Project $project, Debt $debt, UpdateDebtRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $payload = $this->normalizedDebtPayload($project, $data, $debt);

        $debt->update($payload);

        return redirect()->route('debts.index', $project)->with('success', 'تم تحديث الذمة الدائنة بنجاح.');
    }

    public function destroy(Project $project, Debt $debt): RedirectResponse
    {
        $debt->delete();

        return redirect()->route('debts.index', $project)->with('success', 'تم حذف سجل الذمة الدائنة.');
    }

    /**
     * @param  array{creditor_name: string, purchase_description?: string|null, total_amount: float|int|string, paid_amount?: float|int|string|null}  $data
     * @return array{project_id: int, client_id: int|null, creditor_name: string, purchase_description: ?string, total_amount: float, paid_amount: float, remaining_amount: float, status: string}
     */
    private function normalizedDebtPayload(Project $project, array $data, ?Debt $existing = null): array
    {
        $total = round((float) $data['total_amount'], 2);
        $paid = round((float) ($data['paid_amount'] ?? 0), 2);
        $paid = min($paid, $total);
        $remaining = round(max(0.0, $total - $paid), 2);
        $status = $remaining > 0.01 ? 'open' : 'closed';

        $clientId = $existing?->client_id;
        if ($clientId === null && Schema::getConnection()->getDriverName() !== 'mysql') {
            $clientId = Client::query()->where('project_id', $project->id)->orderBy('id')->value('id');
        }

        $desc = $data['purchase_description'] ?? null;
        if (is_string($desc)) {
            $desc = trim($desc);
        }
        $desc = $desc === '' || $desc === null ? null : (string) $desc;

        return [
            'project_id' => (int) $project->id,
            'client_id' => $clientId,
            'creditor_name' => (string) $data['creditor_name'],
            'purchase_description' => $desc,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $status,
        ];
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
