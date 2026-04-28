<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRevenueRequest;
use App\Http\Requests\UpdateRevenueRequest;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Revenue;
use App\Services\CashboxLedgerService;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function __construct(
        private CashboxLedgerService $cashboxLedger,
    ) {}

    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $totalsQuery = Revenue::query();
        $this->applyRevenueListingFilters($totalsQuery, $filters);
        $revenueStats = [
            'sum_amount' => (float) (clone $totalsQuery)->sum('amount'),
            'count' => (clone $totalsQuery)->count(),
            'avg_amount' => (float) (clone $totalsQuery)->avg('amount'),
        ];

        $listQuery = Revenue::query()->with(['client:id,name', 'contract:id']);
        $this->applyRevenueListingFilters($listQuery, $filters);
        $revenues = $listQuery->latest()->paginate(15)->withQueryString();

        return view('revenues.index', [
            'title' => 'التحصيل | Mohaseb Aqary',
            'pageTitle' => 'التحصيل',
            'project' => $project,
            'revenueStats' => $revenueStats,
            'revenues' => $revenues,
            'modules' => $this->modules(),
        ]);
    }

    private function applyRevenueListingFilters(Builder $query, ListingFilters $filters): void
    {
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->where('notes', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('payment_method', 'like', $like)
                    ->orWhere('source', 'like', $like)
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $like));
            });
        }
        $filters->applyWhereDate($query, 'paid_at');
    }

    public function create(): View
    {
        $contracts = Contract::query()
            ->with([
                'client:id,name',
                'sale',
                'revenues' => static fn ($q) => $q->orderBy('paid_at')->orderBy('id'),
            ])
            ->where('remaining_amount', '>', 0)
            ->latest()
            ->get();

        $contractSuggestedAmounts = $contracts
            ->mapWithKeys(static fn (Contract $c): array => [$c->id => $c->suggestedNextCollectionAmount(null)])
            ->all();

        return view('revenues.create', [
            'title' => 'تحصيل دفعة | Mohaseb Aqary',
            'pageTitle' => 'تحصيل دفعة',
            'revenue' => new Revenue,
            'contracts' => $contracts,
            'contractSuggestedAmounts' => $contractSuggestedAmounts,
            'modules' => $this->modules(),
        ]);
    }

    public function store(StoreRevenueRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['approval_status'] = 'pending';
        $revenue = Revenue::query()->create($validated);
        $this->cashboxLedger->syncFromRevenue($revenue);
        $this->recalculateContract((int) $revenue->contract_id);

        return redirect()->route('revenues.index')->with('success', 'تم تسجيل التحصيل كعملية معلقة حتى اعتماد الأدمن.');
    }

    public function show(Project $project, Revenue $revenue): View
    {
        return view('revenues.show', [
            'title' => 'تفاصيل التحصيل | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل التحصيل',
            'project' => $project,
            'revenue' => $revenue->load(['client', 'contract.sale', 'sale']),
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Project $project, Revenue $revenue): View
    {
        $contracts = Contract::query()
            ->with([
                'client:id,name',
                'sale',
                'revenues' => static fn ($q) => $q->orderBy('paid_at')->orderBy('id'),
            ])
            ->latest()
            ->get();

        $contractSuggestedAmounts = $contracts
            ->mapWithKeys(static function (Contract $c) use ($revenue): array {
                $exclude = (int) $revenue->contract_id === (int) $c->id ? (int) $revenue->id : null;

                return [$c->id => $c->suggestedNextCollectionAmount($exclude)];
            })
            ->all();

        return view('revenues.edit', [
            'title' => 'تعديل التحصيل | Mohaseb Aqary',
            'pageTitle' => 'تعديل التحصيل',
            'revenue' => $revenue,
            'contracts' => $contracts,
            'contractSuggestedAmounts' => $contractSuggestedAmounts,
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdateRevenueRequest $request, Project $project, Revenue $revenue): RedirectResponse
    {
        $oldContractId = (int) $revenue->contract_id;
        $revenue->update($request->validated());
        $revenue->refresh();
        $this->cashboxLedger->syncFromRevenue($revenue);
        $this->recalculateContract($oldContractId);
        $this->recalculateContract((int) $revenue->contract_id);

        return redirect()->route('revenues.index')->with('success', 'تم تحديث التحصيل بنجاح.');
    }

    public function destroy(Project $project, Revenue $revenue): RedirectResponse
    {
        $contractId = (int) $revenue->contract_id;
        $revenueId = (int) $revenue->id;
        $this->cashboxLedger->removeRevenue($revenueId);
        $revenue->delete();
        $this->recalculateContract($contractId);

        return redirect()->route('revenues.index')->with('success', 'تم حذف التحصيل بنجاح.');
    }

    private function recalculateContract(int $contractId): void
    {
        $contract = Contract::query()->with('sale:id,down_payment,approval_status')->find($contractId);
        if (! $contract) {
            return;
        }

        $paidFromRevenues = (float) Revenue::query()
            ->where('contract_id', $contractId)
            ->where('approval_status', 'approved')
            ->sum('amount');
        $downPayment = (float) (($contract->sale?->approval_status ?? 'approved') === 'approved' ? ($contract->sale?->down_payment ?? 0) : 0);
        $paid = $downPayment + $paidFromRevenues;
        $contract->update([
            'paid_amount' => $paid,
            'remaining_amount' => max(0, (float) $contract->total_price - $paid),
        ]);
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
