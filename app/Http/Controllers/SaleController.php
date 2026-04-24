<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Property;
use App\Models\Revenue;
use App\Models\Sale;
use App\Services\CashboxLedgerService;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        private CashboxLedgerService $cashboxLedger,
    ) {}

    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $totalsQuery = Sale::query();
        $this->applySaleListingFilters($totalsQuery, $filters);
        $saleTotals = [
            'total_sales' => (float) (clone $totalsQuery)->sum('sale_price'),
            'total_down_payment' => (float) (clone $totalsQuery)->sum('down_payment'),
        ];

        $listQuery = Sale::query()->with(['property:id,name', 'client:id,name,phone']);
        $this->applySaleListingFilters($listQuery, $filters);
        $sales = $listQuery->latest()->paginate(15)->withQueryString();

        return view('sales.index', [
            'title' => 'المبيعات | Mohaseb Aqary',
            'pageTitle' => 'المبيعات',
            'project' => $project,
            'saleTotals' => $saleTotals,
            'sales' => $sales,
            'modules' => $this->modules(),
        ]);
    }

    private function applySaleListingFilters(Builder $query, ListingFilters $filters): void
    {
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->where('broker_name', 'like', $like)
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like))
                    ->orWhereHas('property', fn ($p) => $p->where('name', 'like', $like));
            });
        }
        $filters->applyWhereDate($query, 'sale_date');
    }

    public function create(): View
    {
        return view('sales.create', [
            'title' => 'تسجيل بيع | Mohaseb Aqary',
            'pageTitle' => 'تسجيل بيع',
            'sale' => new Sale,
            'properties' => Property::query()->select(
                'id',
                'name',
                'floors_count',
                'registered_floors',
                'ground_floor_shops_count',
                'has_mezzanine',
                'mezzanine_floors',
                'mushaa_floors',
                'apartment_models'
            )->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $client = $this->createClientForNewSale($validated);

        $sale = Sale::query()->create([
            'property_id' => $validated['property_id'],
            'client_id' => $client->id,
            'floor_number' => $validated['floor_number'],
            'is_mezzanine' => (bool) ($validated['is_mezzanine'] ?? false),
            'apartment_model' => $validated['apartment_model'],
            'sale_price' => $validated['sale_price'],
            'payment_type' => $validated['payment_type'],
            'down_payment' => $validated['down_payment'] ?? 0,
            'installment_months' => $validated['payment_type'] === 'installment' ? $validated['installment_months'] : null,
            'installment_start_date' => $validated['payment_type'] === 'installment' ? $validated['installment_start_date'] : null,
            'installment_plan' => $validated['installment_plan'] ?? null,
            'sale_date' => $validated['sale_date'],
            'broker_name' => $validated['broker_name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncContractForSale($sale);
        $this->cashboxLedger->syncSaleDownPayment($sale->refresh());

        return redirect()->route('sales.index')->with('success', 'تم تسجيل البيعة بنجاح وإضافة العميل وإنشاء العقد.');
    }

    public function show(Project $project, Sale $sale): View
    {
        $sale->load([
            'property.area:id,name',
            'property.land:id,name',
            'client',
            'contract.revenues' => static fn ($q) => $q->orderBy('paid_at')->orderBy('id'),
        ]);

        $installmentRows = $sale->installmentScheduleWithPaymentSummary();
        $revenues = $sale->contract?->revenues ?? collect();
        $scheduledTotal = (float) collect($installmentRows)->sum(static fn (array $r) => $r['amount']);
        $stats = [
            'installment_rows' => count($installmentRows),
            'scheduled_total' => round($scheduledTotal, 2),
            'revenues_count' => $revenues->count(),
            'revenues_sum' => round((float) $revenues->sum(static fn ($r) => (float) $r->amount), 2),
            'contract_total' => round((float) ($sale->contract?->total_price ?? 0), 2),
            'contract_paid' => round((float) ($sale->contract?->paid_amount ?? 0), 2),
            'contract_remaining' => round((float) ($sale->contract?->remaining_amount ?? 0), 2),
        ];

        return view('sales.show', [
            'title' => 'تفاصيل البيعة | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل البيعة',
            'project' => $project,
            'sale' => $sale,
            'installmentRows' => $installmentRows,
            'stats' => $stats,
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Project $project, Sale $sale): View
    {
        return view('sales.edit', [
            'title' => 'تعديل البيعة | Mohaseb Aqary',
            'pageTitle' => 'تعديل البيعة',
            'sale' => $sale->load('client'),
            'properties' => Property::query()->select(
                'id',
                'name',
                'floors_count',
                'registered_floors',
                'ground_floor_shops_count',
                'has_mezzanine',
                'mezzanine_floors',
                'mushaa_floors',
                'apartment_models'
            )->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdateSaleRequest $request, Project $project, Sale $sale): RedirectResponse
    {
        $validated = $request->validated();
        $client = $this->resolveClientForSaleUpdate($sale, $validated);

        $sale->update([
            'property_id' => $validated['property_id'],
            'client_id' => $client->id,
            'floor_number' => $validated['floor_number'],
            'is_mezzanine' => (bool) ($validated['is_mezzanine'] ?? false),
            'apartment_model' => $validated['apartment_model'],
            'sale_price' => $validated['sale_price'],
            'payment_type' => $validated['payment_type'],
            'down_payment' => $validated['down_payment'] ?? 0,
            'installment_months' => $validated['payment_type'] === 'installment' ? $validated['installment_months'] : null,
            'installment_start_date' => $validated['payment_type'] === 'installment' ? $validated['installment_start_date'] : null,
            'installment_plan' => $validated['installment_plan'] ?? null,
            'sale_date' => $validated['sale_date'],
            'broker_name' => $validated['broker_name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncContractForSale($sale->refresh());
        $this->cashboxLedger->syncSaleDownPayment($sale);

        return redirect()->route('sales.index')->with('success', 'تم تحديث البيعة بنجاح.');
    }

    public function destroy(Project $project, Sale $sale): RedirectResponse
    {
        $this->cashboxLedger->removeSaleDownPayment((int) $sale->id);
        $sale->delete();

        return redirect()->route('sales.index')->with('success', 'تم حذف البيعة بنجاح.');
    }

    private function createClientForNewSale(array $validated): Client
    {
        return Client::query()->create([
            'name' => $validated['client_name'],
            'phone' => $validated['client_phone'],
            'email' => $validated['client_email'] ?? null,
            'national_id' => $validated['client_national_id'] ?? null,
        ]);
    }

    private function resolveClientForSaleUpdate(Sale $sale, array $validated): Client
    {
        $existing = $sale->client;
        if ($existing && $this->clientMatchesValidated($existing, $validated)) {
            return $existing;
        }

        return $this->createClientForNewSale($validated);
    }

    private function clientMatchesValidated(Client $client, array $validated): bool
    {
        return $client->name === $validated['client_name']
            && $client->phone === $validated['client_phone']
            && $this->normalizedNullableString($client->email) === $this->normalizedNullableString($validated['client_email'] ?? null)
            && $this->normalizedNullableString($client->national_id) === $this->normalizedNullableString($validated['client_national_id'] ?? null);
    }

    private function normalizedNullableString(?string $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function syncContractForSale(Sale $sale): void
    {
        $installmentMonths = (int) ($sale->installment_months ?? 0);
        $defaultEndDate = $installmentMonths > 0
            ? $sale->sale_date->copy()->addMonths($installmentMonths)
            : $sale->sale_date->copy()->addYear();
        $existingContract = Contract::query()->where('sale_id', $sale->id)->first();
        $revenuesPaid = $existingContract
            ? (float) Revenue::query()->where('contract_id', $existingContract->id)->sum('amount')
            : 0.0;
        $totalPaid = (float) $sale->down_payment + $revenuesPaid;
        $remaining = max(0, (float) $sale->sale_price - $totalPaid);

        Contract::query()->updateOrCreate(
            ['sale_id' => $sale->id],
            [
                'client_id' => $sale->client_id,
                'property_id' => $sale->property_id,
                'start_date' => $sale->sale_date->format('Y-m-d'),
                'end_date' => $defaultEndDate->format('Y-m-d'),
                'total_price' => $sale->sale_price,
                'paid_amount' => $totalPaid,
                'remaining_amount' => $remaining,
            ]
        );
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
