<?php

namespace App\Http\Controllers;

use App\Models\LandParcel;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LandTradingController extends Controller
{
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

    public function create(): View
    {
        return view('land-trading.create', [
            'title' => 'إضافة أرض | Mohaseb Aqary',
            'pageTitle' => 'إضافة أرض (بيع/شراء)',
            'parcel' => new LandParcel([
                'status' => 'owned',
                'purchase_price' => 0,
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

        return view('land-trading.show', [
            'title' => $parcel->name.' | أراضي البيع والشراء',
            'pageTitle' => $parcel->name,
            'parcel' => $parcel,
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
            'deed_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(array_keys(LandParcel::STATUSES))],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'purchased_from' => ['nullable', 'string', 'max:255'],
            'purchase_phone' => ['nullable', 'string', 'max:50'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'sale_date' => ['nullable', 'date'],
            'sold_to' => ['nullable', 'string', 'max:255'],
            'sale_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($data['status'] ?? '') === 'sold' && empty($data['sale_price'])) {
            $request->validate([
                'sale_price' => ['required', 'numeric', 'min:0'],
            ], [
                'sale_price.required' => 'سعر البيع مطلوب عند حالة «مباعة».',
            ]);
        }

        return $data;
    }
}
