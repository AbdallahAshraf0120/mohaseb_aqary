<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Property;
use App\Models\Sale;
use App\Services\CashboxLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SaleController extends Controller
{
    public function __construct(
        private CashboxLedgerService $cashboxLedger,
    ) {
    }

    public function index(): View
    {
        return view('sales.index', [
            'title' => 'المبيعات | Mohaseb Aqary',
            'pageTitle' => 'المبيعات',
            'sales' => Sale::query()->with(['property:id,name', 'client:id,name,phone'])->latest()->paginate(15),
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('sales.create', [
            'title' => 'تسجيل بيع | Mohaseb Aqary',
            'pageTitle' => 'تسجيل بيع',
            'properties' => Property::query()->select('id', 'name', 'floors_count', 'apartment_models')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $client = $this->upsertClient($validated);

        $sale = Sale::query()->create([
            'property_id' => $validated['property_id'],
            'client_id' => $client->id,
            'floor_number' => $validated['floor_number'],
            'apartment_model' => $validated['apartment_model'],
            'sale_price' => $validated['sale_price'],
            'payment_type' => $validated['payment_type'],
            'down_payment' => $validated['down_payment'] ?? 0,
            'installment_months' => $validated['payment_type'] === 'installment' ? $validated['installment_months'] : null,
            'installment_start_date' => $validated['payment_type'] === 'installment' ? $validated['installment_start_date'] : null,
            'installment_plan' => $validated['installment_plan'] ?? null,
            'sale_date' => $validated['sale_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncContractForSale($sale);
        $this->cashboxLedger->syncSaleDownPayment($sale->refresh());

        return redirect()->route('sales.index')->with('success', 'تم تسجيل البيعة بنجاح وإضافة العميل وإنشاء العقد.');
    }

    public function show(Sale $sale): View
    {
        return view('sales.show', [
            'title' => 'تفاصيل البيعة | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل البيعة',
            'sale' => $sale->load(['property', 'client']),
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Sale $sale): View
    {
        return view('sales.edit', [
            'title' => 'تعديل البيعة | Mohaseb Aqary',
            'pageTitle' => 'تعديل البيعة',
            'sale' => $sale->load('client'),
            'properties' => Property::query()->select('id', 'name', 'floors_count', 'apartment_models')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validated();
        $client = $sale->client ?: $this->upsertClient($validated);

        $client->update([
            'name' => $validated['client_name'],
            'phone' => $validated['client_phone'],
            'email' => $validated['client_email'] ?? null,
            'national_id' => $validated['client_national_id'] ?? null,
        ]);

        $sale->update([
            'property_id' => $validated['property_id'],
            'client_id' => $client->id,
            'floor_number' => $validated['floor_number'],
            'apartment_model' => $validated['apartment_model'],
            'sale_price' => $validated['sale_price'],
            'payment_type' => $validated['payment_type'],
            'down_payment' => $validated['down_payment'] ?? 0,
            'installment_months' => $validated['payment_type'] === 'installment' ? $validated['installment_months'] : null,
            'installment_start_date' => $validated['payment_type'] === 'installment' ? $validated['installment_start_date'] : null,
            'installment_plan' => $validated['installment_plan'] ?? null,
            'sale_date' => $validated['sale_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncContractForSale($sale->refresh());
        $this->cashboxLedger->syncSaleDownPayment($sale);

        return redirect()->route('sales.index')->with('success', 'تم تحديث البيعة بنجاح.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->cashboxLedger->removeSaleDownPayment((int) $sale->id);
        $sale->delete();

        return redirect()->route('sales.index')->with('success', 'تم حذف البيعة بنجاح.');
    }

    private function upsertClient(array $validated): Client
    {
        $client = null;

        if (! empty($validated['client_national_id'])) {
            $client = Client::query()->where('national_id', $validated['client_national_id'])->first();
        }

        if (! $client && ! empty($validated['client_phone'])) {
            $client = Client::query()->where('phone', $validated['client_phone'])->first();
        }

        if (! $client && ! empty($validated['client_email'])) {
            $client = Client::query()->where('email', $validated['client_email'])->first();
        }

        if ($client) {
            $client->update([
                'name' => $validated['client_name'],
                'phone' => $validated['client_phone'],
                'email' => $validated['client_email'] ?? null,
                'national_id' => $validated['client_national_id'] ?? null,
            ]);

            return $client;
        }

        return Client::query()->create([
            'name' => $validated['client_name'],
            'phone' => $validated['client_phone'],
            'email' => $validated['client_email'] ?? null,
            'national_id' => $validated['client_national_id'] ?? null,
        ]);
    }

    private function syncContractForSale(Sale $sale): void
    {
        $installmentMonths = (int) ($sale->installment_months ?? 0);
        $defaultEndDate = $installmentMonths > 0
            ? $sale->sale_date->copy()->addMonths($installmentMonths)
            : $sale->sale_date->copy()->addYear();

        Contract::query()->updateOrCreate(
            ['sale_id' => $sale->id],
            [
                'client_id' => $sale->client_id,
                'property_id' => $sale->property_id,
                'start_date' => $sale->sale_date->format('Y-m-d'),
                'end_date' => $defaultEndDate->format('Y-m-d'),
                'total_price' => $sale->sale_price,
                'paid_amount' => $sale->down_payment,
                'remaining_amount' => max(0, (float) $sale->sale_price - (float) $sale->down_payment),
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
            'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
