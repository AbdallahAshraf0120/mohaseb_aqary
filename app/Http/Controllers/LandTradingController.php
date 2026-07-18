<?php

namespace App\Http\Controllers;

use App\Models\LandParcel;
use App\Models\LandParcelPart;
use App\Models\LandParcelPayment;
use App\Models\LandParcelShareholder;
use App\Services\LandParcelPaymentService;
use App\Support\LandInstallmentPlanBuilder;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class LandTradingController extends Controller
{
    public function __construct(
        private readonly LandParcelPaymentService $paymentService,
    ) {}

    public function index(Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $status = trim((string) $request->query('status', ''));

        $query = LandParcel::query()->with(['creator:id,name']);

        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('deed_number', 'like', $like)
                    ->orWhere('purchased_from', 'like', $like)
                    ->orWhere('sold_to', 'like', $like);
            });
        }

        if ($status !== '' && array_key_exists($status, LandParcel::STATUSES)) {
            $query->where('status', $status);
        }

        $filters->applyWhereDate($query, 'created_at');

        $kpiBase = (clone $query);
        $kpis = [
            'count' => (clone $kpiBase)->count(),
            'owned' => (clone $kpiBase)->where('status', 'owned')->count(),
            'for_sale' => (clone $kpiBase)->where('status', 'for_sale')->count(),
            'sold' => (clone $kpiBase)->where('status', 'sold')->count(),
            'purchase_total' => (float) (clone $kpiBase)->sum('purchase_price'),
            'sale_total' => (float) (clone $kpiBase)->whereNotNull('sale_price')->sum('sale_price'),
        ];
        $kpis['profit'] = $kpis['sale_total'] - (float) (clone $kpiBase)->whereNotNull('sale_price')->sum('purchase_price');

        $parcels = $query
            ->orderByRaw("case status when 'for_sale' then 0 when 'reserved' then 1 when 'owned' then 2 when 'sold' then 3 else 4 end")
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('land-trading.index', [
            'title' => 'أراضي البيع والشراء | Mohaseb Aqary',
            'pageTitle' => 'أراضي البيع والشراء',
            'parcels' => $parcels,
            'status' => $status,
            'kpis' => $kpis,
        ]);
    }

    /**
     * سكشن مبيعات الأراضي: أراضٍ عليها سعر بيع / للبيع / محجوزة / مباعة.
     */
    public function sales(Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $status = trim((string) $request->query('status', ''));
        $collection = trim((string) $request->query('collection', ''));

        $query = LandParcel::query()
            ->where(function ($q): void {
                $q->where(function ($sale): void {
                    $sale->whereNotNull('sale_price')->where('sale_price', '>', 0);
                })
                    ->orWhereIn('status', ['for_sale', 'reserved', 'sold']);
                if (Schema::hasTable('land_parcel_parts')) {
                    $q->orWhereHas('parts');
                }
            });

        if (Schema::hasTable('land_parcel_payments')) {
            $query->withSum([
                'payments as sale_collected' => function ($q): void {
                    $q->where('side', LandParcelPayment::SIDE_SALE)
                        ->where('approval_status', 'approved');
                },
            ], 'amount');
        }

        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('deed_number', 'like', $like)
                    ->orWhere('sold_to', 'like', $like)
                    ->orWhere('sale_phone', 'like', $like);
            });
        }

        if ($status !== '' && array_key_exists($status, LandParcel::STATUSES)) {
            $query->where('status', $status);
        }

        $filters->applyWhereDate($query, 'sale_date');

        if ($collection === 'remaining' && Schema::hasTable('land_parcel_payments')) {
            $query->havingRaw('COALESCE(sale_collected, 0) < COALESCE(sale_price, 0) - 0.01');
        } elseif ($collection === 'paid' && Schema::hasTable('land_parcel_payments')) {
            $query->havingRaw('COALESCE(sale_collected, 0) >= COALESCE(sale_price, 0) - 0.01')
                ->whereNotNull('sale_price')
                ->where('sale_price', '>', 0);
        }

        $kpiBase = (clone $query);
        // sum/count مع having قد يفشل؛ نحسب من المعرّفات بعد الفلتر
        $parcelIds = (clone $kpiBase)->pluck('id');
        $wholeSales = (float) LandParcel::query()->whereIn('id', $parcelIds)->whereNotNull('sale_price')->sum('sale_price');
        $partsSales = Schema::hasTable('land_parcel_parts') && $parcelIds->isNotEmpty()
            ? (float) LandParcelPart::query()->whereIn('land_parcel_id', $parcelIds)->sum('sale_price')
            : 0.0;
        $totalSales = $wholeSales + $partsSales;
        $purchaseOfSales = (float) LandParcel::query()->whereIn('id', $parcelIds)->sum('purchase_price');
        $count = $parcelIds->count();
        $soldCount = (int) LandParcel::query()->whereIn('id', $parcelIds)->where('status', 'sold')->count();

        $totalCollected = 0.0;
        if (Schema::hasTable('land_parcel_payments') && $parcelIds->isNotEmpty()) {
            $totalCollected = (float) LandParcelPayment::query()
                ->whereIn('land_parcel_id', $parcelIds)
                ->where('side', LandParcelPayment::SIDE_SALE)
                ->where('approval_status', 'approved')
                ->sum('amount');
        }

        $parcels = $query
            ->orderByRaw("case status when 'for_sale' then 0 when 'reserved' then 1 when 'sold' then 2 else 3 end")
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('land-trading.sales', [
            'title' => 'مبيعات الأراضي | Mohaseb Aqary',
            'pageTitle' => 'مبيعات الأراضي',
            'parcels' => $parcels,
            'status' => $status,
            'collection' => $collection,
            'saleTotals' => [
                'count' => $count,
                'sold_count' => $soldCount,
                'total_sales' => $totalSales,
                'total_collected' => $totalCollected,
                'total_remaining' => round(max(0, $totalSales - $totalCollected), 2),
                'profit' => round($totalSales - $purchaseOfSales, 2),
            ],
            'paymentsReady' => Schema::hasTable('land_parcel_payments'),
        ]);
    }

    public function create(): View
    {
        return view('land-trading.create', [
            'title' => 'إضافة أرض | Mohaseb Aqary',
            'pageTitle' => 'إضافة أرض (بيع/شراء)',
            'parcel' => new LandParcel([
                'status' => 'owned',
                'purchase_price' => 0,
                'purchase_payment_type' => 'cash',
                'purchase_down_payment' => 0,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()?->id;

        $parcel = LandParcel::query()->create($data);

        return redirect()
            ->route('land-trading.show', $parcel)
            ->with('success', 'تم تسجيل الأرض بنجاح.');
    }

    public function show(LandParcel $parcel): View
    {
        $parcel->load(['creator:id,name']);
        $shareholders = Schema::hasTable('land_parcel_shareholder')
            ? LandParcelShareholder::query()
                ->with('shareholder:id,name')
                ->where('land_parcel_id', (int) $parcel->id)
                ->get()
                ->filter(fn (LandParcelShareholder $m) => $m->shareholder !== null)
                ->values()
            : collect();

        $paymentsReady = Schema::hasTable('land_parcel_payments');
        $partsReady = Schema::hasTable('land_parcel_parts');
        $parts = $partsReady
            ? $parcel->parts()->orderBy('id')->get()
            : collect();

        $payments = $paymentsReady
            ? $parcel->payments()->with(['creator:id,name', 'part:id,name'])->orderByDesc('paid_at')->orderByDesc('id')->get()
            : collect();

        $partsSaleTotal = $partsReady ? (float) $parts->sum(fn (LandParcelPart $p) => (float) $p->sale_price) : 0.0;
        $partsCollected = $paymentsReady && $partsReady ? $parcel->partsCollectedTotal() : 0.0;
        $wholeSaleCollected = $paymentsReady ? $parcel->approvedPaidTotal(LandParcelPayment::SIDE_SALE) : 0.0;
        $salePaid = $partsReady && $parts->isNotEmpty()
            ? $partsCollected + $wholeSaleCollected
            : $wholeSaleCollected;
        $saleTarget = $partsReady && $parts->isNotEmpty()
            ? $partsSaleTotal + (float) ($parcel->sale_price ?? 0)
            : (float) ($parcel->sale_price ?? 0);

        return view('land-trading.show', [
            'title' => $parcel->name.' | أراضي البيع والشراء',
            'pageTitle' => $parcel->name,
            'parcel' => $parcel,
            'parcelShareholders' => $shareholders,
            'payments' => $payments,
            'paymentsReady' => $paymentsReady,
            'partsReady' => $partsReady,
            'parts' => $parts,
            'purchaseSchedule' => $paymentsReady ? $parcel->installmentScheduleWithPaymentSummary(LandParcelPayment::SIDE_PURCHASE) : [],
            'saleSchedule' => $paymentsReady ? $parcel->installmentScheduleWithPaymentSummary(LandParcelPayment::SIDE_SALE) : [],
            'purchasePaid' => $paymentsReady ? $parcel->approvedPaidTotal(LandParcelPayment::SIDE_PURCHASE) : 0.0,
            'purchaseRemaining' => $paymentsReady ? $parcel->remainingTotal(LandParcelPayment::SIDE_PURCHASE) : (float) $parcel->purchase_price,
            'salePaid' => $salePaid,
            'saleRemaining' => round(max(0, $saleTarget - $salePaid), 2),
            'remainingArea' => $partsReady ? $parcel->remainingArea() : null,
        ]);
    }

    public function edit(LandParcel $parcel): View
    {
        return view('land-trading.edit', [
            'title' => 'تعديل أرض | Mohaseb Aqary',
            'pageTitle' => 'تعديل أرض',
            'parcel' => $parcel,
        ]);
    }

    public function update(Request $request, LandParcel $parcel): RedirectResponse
    {
        $parcel->update($this->validated($request));

        return redirect()
            ->route('land-trading.show', $parcel)
            ->with('success', 'تم تحديث بيانات الأرض.');
    }

    public function destroy(LandParcel $parcel): RedirectResponse
    {
        $parcel->delete();

        return redirect()
            ->route('land-trading.index')
            ->with('success', 'تم حذف الأرض.');
    }

    public function storePayment(Request $request, LandParcel $parcel): RedirectResponse
    {
        if (! Schema::hasTable('land_parcel_payments')) {
            return back()->with('error', 'قاعدة البيانات غير محدّثة. شغّل: php artisan migrate --force');
        }

        $partIdRules = Schema::hasTable('land_parcel_parts')
            ? ['nullable', 'integer', 'exists:land_parcel_parts,id']
            : ['nullable'];

        $data = $request->validate([
            'side' => ['required', 'in:purchase,sale'],
            'land_parcel_part_id' => $partIdRules,
            'kind' => ['required', 'in:down_payment,installment,secondary,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,check'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (($data['side'] ?? '') === 'sale' && empty($data['land_parcel_part_id']) && Schema::hasTable('land_parcel_parts')) {
            // بيع جزء إلزامي إذا وُجدت أجزاء — وإلا يبقى البيع الكامل
            if ($parcel->parts()->exists() && (float) ($parcel->sale_price ?? 0) <= 0) {
                return back()->withInput()->with('error', 'اختر الجزء المراد تحصيله.');
            }
        }

        try {
            $this->paymentService->create($parcel, $data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $msg = $data['side'] === 'purchase' ? 'تم تسجيل دفعة الشراء وتحديث صندوق الأراضي.' : 'تم تسجيل تحصيل البيع وتحديث صندوق الأراضي.';

        return redirect()
            ->route('land-trading.show', $parcel)
            ->with('success', $msg);
    }

    public function destroyPayment(LandParcel $parcel, LandParcelPayment $payment): RedirectResponse
    {
        if ((int) $payment->land_parcel_id !== (int) $parcel->id) {
            abort(404);
        }

        $this->paymentService->delete($payment);

        return redirect()
            ->route('land-trading.show', $parcel)
            ->with('success', 'تم حذف الدفعة وإلغاء حركتها من الصندوق.');
    }

    public function storePart(Request $request, LandParcel $parcel): RedirectResponse
    {
        if (! Schema::hasTable('land_parcel_parts')) {
            return back()->with('error', 'قاعدة البيانات غير محدّثة. شغّل: php artisan migrate --force');
        }

        $data = $this->validatedPart($request, $parcel);
        $part = $parcel->parts()->create($data);

        if ($parcel->status === 'owned') {
            $parcel->update(['status' => 'for_sale']);
        }

        $message = 'تم إضافة جزء بيع «'.$part->name.'».';

        // البيع كاش = تحصيل كامل فورًا في صندوق الأراضي
        if (($data['sale_payment_type'] ?? '') === 'cash'
            && (float) $part->sale_price > 0
            && Schema::hasTable('land_parcel_payments')) {
            try {
                $this->paymentService->create($parcel, [
                    'side' => LandParcelPayment::SIDE_SALE,
                    'land_parcel_part_id' => (int) $part->id,
                    'kind' => LandParcelPayment::KIND_DOWN_PAYMENT,
                    'amount' => (float) $part->sale_price,
                    'paid_at' => $data['sale_date'] ?? now()->toDateString(),
                    'payment_method' => 'cash',
                    'notes' => 'تحصيل كاش تلقائي عند تسجيل الجزء',
                ], $request->user());
                $message .= ' وتم تسجيل تحصيل الكاش كاملًا في الصندوق.';
            } catch (InvalidArgumentException $e) {
                $message .= ' (لم يُسجَّل التحصيل التلقائي: '.$e->getMessage().')';
            }
        } else {
            $message .= ' يمكنك تحصيل أقساطه مع استمرار سداد شراء الأرض.';
        }

        return redirect()
            ->route('land-trading.show', $parcel)
            ->with('success', $message);
    }

    public function updatePart(Request $request, LandParcel $parcel, LandParcelPart $part): RedirectResponse
    {
        if ((int) $part->land_parcel_id !== (int) $parcel->id) {
            abort(404);
        }

        $part->update($this->validatedPart($request, $parcel, $part));

        return redirect()
            ->route('land-trading.show', $parcel)
            ->with('success', 'تم تحديث بيانات الجزء.');
    }

    public function destroyPart(LandParcel $parcel, LandParcelPart $part): RedirectResponse
    {
        if ((int) $part->land_parcel_id !== (int) $parcel->id) {
            abort(404);
        }

        if ($part->payments()->exists()) {
            return back()->with('error', 'لا يمكن حذف جزء عليه تحصيلات. احذف التحصيلات أولاً.');
        }

        $part->delete();

        return redirect()
            ->route('land-trading.show', $parcel)
            ->with('success', 'تم حذف الجزء.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPart(Request $request, LandParcel $parcel, ?LandParcelPart $exceptPart = null): array
    {
        $remaining = $parcel->remainingArea($exceptPart?->id);
        $areaRules = $parcel->area_size !== null
            ? ['required', 'numeric', 'min:0.01']
            : ['nullable', 'numeric', 'min:0.01'];
        if ($remaining !== null) {
            $areaRules[] = 'max:'.$remaining;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'area_size' => $areaRules,
            'status' => ['required', 'string', Rule::in(array_keys(LandParcelPart::STATUSES))],
            'sale_price' => ['required', 'numeric', 'min:0.01'],
            'sale_date' => ['nullable', 'date'],
            'sold_to' => ['nullable', 'string', 'max:255'],
            'sale_phone' => ['nullable', 'string', 'max:50'],
            'sale_payment_type' => ['required', 'in:cash,installment'],
            'sale_down_payment' => ['nullable', 'numeric', 'min:0'],
            'sale_installment_months' => ['nullable', 'integer', 'min:1', 'required_if:sale_payment_type,installment'],
            'sale_installment_schedule' => ['nullable', 'in:monthly,quarterly,semiannual', 'required_if:sale_payment_type,installment'],
            'sale_installment_start_date' => ['nullable', 'date', 'required_if:sale_payment_type,installment'],
            'notes' => ['nullable', 'string'],
        ], [
            'area_size.required' => 'المساحة مطلوبة لتحديد نسبة الجزء من الأرض.',
            'area_size.max' => $remaining !== null
                ? 'المساحة أكبر من المتاح. المتبقي: '.number_format($remaining, 2).' م².'
                : 'المساحة غير صحيحة.',
            'area_size.min' => 'أدخل مساحة أكبر من صفر.',
        ]);

        $partArea = isset($data['area_size']) && $data['area_size'] !== null && $data['area_size'] !== ''
            ? (float) $data['area_size']
            : 0.0;
        $salePerM2 = (float) ($parcel->sale_price_per_m2 ?? 0);
        if ($partArea > 0 && $salePerM2 > 0) {
            $data['sale_price'] = round($partArea * $salePerM2, 2);
        }

        $built = LandInstallmentPlanBuilder::build(
            (string) $data['sale_payment_type'],
            (float) $data['sale_price'],
            $data['sale_down_payment'] ?? null,
            $data['sale_installment_months'] ?? null,
            $data['sale_installment_schedule'] ?? null,
            $data['sale_installment_start_date'] ?? null,
        );
        $data['sale_payment_type'] = $built['payment_type'];
        $data['sale_down_payment'] = $built['down_payment'];
        $data['sale_installment_months'] = $built['installment_months'];
        $data['sale_installment_schedule'] = $built['installment_schedule'];
        $data['sale_installment_start_date'] = $built['installment_start_date'];
        $data['sale_installment_plan'] = $built['installment_plan'];

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'area_size' => ['nullable', 'numeric', 'min:0'],
            'purchase_price_per_m2' => ['nullable', 'numeric', 'min:0'],
            'sale_price_per_m2' => ['nullable', 'numeric', 'min:0'],
            'deed_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(array_keys(LandParcel::STATUSES))],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'purchased_from' => ['nullable', 'string', 'max:255'],
            'purchase_phone' => ['nullable', 'string', 'max:50'],
            'purchase_payment_type' => ['required', 'in:cash,installment'],
            'purchase_down_payment' => ['nullable', 'numeric', 'min:0'],
            'purchase_installment_months' => ['nullable', 'integer', 'min:1', 'required_if:purchase_payment_type,installment'],
            'purchase_installment_schedule' => ['nullable', 'in:monthly,quarterly,semiannual', 'required_if:purchase_payment_type,installment'],
            'purchase_installment_start_date' => ['nullable', 'date', 'required_if:purchase_payment_type,installment'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'sale_date' => ['nullable', 'date'],
            'sold_to' => ['nullable', 'string', 'max:255'],
            'sale_phone' => ['nullable', 'string', 'max:50'],
            'sale_payment_type' => ['nullable', 'in:cash,installment'],
            'sale_down_payment' => ['nullable', 'numeric', 'min:0'],
            'sale_installment_months' => ['nullable', 'integer', 'min:1', 'required_if:sale_payment_type,installment'],
            'sale_installment_schedule' => ['nullable', 'in:monthly,quarterly,semiannual', 'required_if:sale_payment_type,installment'],
            'sale_installment_start_date' => ['nullable', 'date', 'required_if:sale_payment_type,installment'],
            'notes' => ['nullable', 'string'],
        ]);

        $area = isset($data['area_size']) && $data['area_size'] !== null && $data['area_size'] !== ''
            ? (float) $data['area_size']
            : 0.0;
        $buyPerM2 = isset($data['purchase_price_per_m2']) && $data['purchase_price_per_m2'] !== null && $data['purchase_price_per_m2'] !== ''
            ? (float) $data['purchase_price_per_m2']
            : 0.0;
        $salePerM2 = isset($data['sale_price_per_m2']) && $data['sale_price_per_m2'] !== null && $data['sale_price_per_m2'] !== ''
            ? (float) $data['sale_price_per_m2']
            : 0.0;

        if ($area > 0 && $buyPerM2 > 0) {
            $data['purchase_price'] = round($area * $buyPerM2, 2);
        }
        if ($area > 0 && $salePerM2 > 0) {
            $data['sale_price'] = round($area * $salePerM2, 2);
        }

        if (($data['status'] ?? '') === 'sold' && empty($data['sale_price'])) {
            $hasParts = Schema::hasTable('land_parcel_parts')
                && $request->route('parcel') instanceof LandParcel
                && $request->route('parcel')->parts()->exists();
            if (! $hasParts) {
                $request->validate([
                    'sale_price' => ['required', 'numeric', 'min:0'],
                ], [
                    'sale_price.required' => 'سعر البيع مطلوب عند حالة «مباعة» (أو أضف أجزاء بيع من صفحة الأرض).',
                ]);
            }
        }

        $purchaseBuilt = LandInstallmentPlanBuilder::build(
            (string) $data['purchase_payment_type'],
            (float) $data['purchase_price'],
            $data['purchase_down_payment'] ?? null,
            $data['purchase_installment_months'] ?? null,
            $data['purchase_installment_schedule'] ?? null,
            $data['purchase_installment_start_date'] ?? null,
        );
        $data['purchase_payment_type'] = $purchaseBuilt['payment_type'];
        $data['purchase_down_payment'] = $purchaseBuilt['down_payment'];
        $data['purchase_installment_months'] = $purchaseBuilt['installment_months'];
        $data['purchase_installment_schedule'] = $purchaseBuilt['installment_schedule'];
        $data['purchase_installment_start_date'] = $purchaseBuilt['installment_start_date'];
        $data['purchase_installment_plan'] = $purchaseBuilt['installment_plan'];

        $salePrice = isset($data['sale_price']) && $data['sale_price'] !== null && $data['sale_price'] !== ''
            ? (float) $data['sale_price']
            : null;

        if ($salePrice !== null && $salePrice > 0) {
            $saleType = (string) ($data['sale_payment_type'] ?: 'cash');
            $saleBuilt = LandInstallmentPlanBuilder::build(
                $saleType,
                $salePrice,
                $data['sale_down_payment'] ?? null,
                $data['sale_installment_months'] ?? null,
                $data['sale_installment_schedule'] ?? null,
                $data['sale_installment_start_date'] ?? null,
            );
            $data['sale_payment_type'] = $saleBuilt['payment_type'];
            $data['sale_down_payment'] = $saleBuilt['down_payment'];
            $data['sale_installment_months'] = $saleBuilt['installment_months'];
            $data['sale_installment_schedule'] = $saleBuilt['installment_schedule'];
            $data['sale_installment_start_date'] = $saleBuilt['installment_start_date'];
            $data['sale_installment_plan'] = $saleBuilt['installment_plan'];
        } else {
            $data['sale_payment_type'] = null;
            $data['sale_down_payment'] = null;
            $data['sale_installment_months'] = null;
            $data['sale_installment_schedule'] = null;
            $data['sale_installment_start_date'] = null;
            $data['sale_installment_plan'] = null;
        }

        return $data;
    }
}
