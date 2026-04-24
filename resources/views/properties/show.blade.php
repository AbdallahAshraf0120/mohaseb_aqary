@extends('layouts.admin')

@section('content')
    @php
        $costRows = [
            'تكلفة الأرض' => (float) ($property->land_cost ?? 0),
            'رخصة البناء' => (float) ($property->building_license_cost ?? 0),
            'خوازيق' => (float) ($property->piles_cost ?? 0),
            'حفر' => (float) ($property->excavation_cost ?? 0),
            'ظلط' => (float) ($property->gravel_cost ?? 0),
            'رملة' => (float) ($property->sand_cost ?? 0),
            'أسمنت' => (float) ($property->cement_cost ?? 0),
            'حديد' => (float) ($property->steel_cost ?? 0),
            'عمالة نجارة' => (float) ($property->carpentry_labor_cost ?? 0),
            'عمالة حدادة' => (float) ($property->blacksmith_labor_cost ?? 0),
            'عمالة بناَّء' => (float) ($property->mason_labor_cost ?? 0),
            'عمالة كهربائي' => (float) ($property->electrician_labor_cost ?? 0),
            'إكراميات' => (float) ($property->tips_cost ?? 0),
        ];
        $totalCosts = array_sum($costRows);
        $registeredFloors = collect($property->registered_floors ?? [])->filter()->values();
        $mushaaFloors = collect($property->mushaa_floors ?? [])->map(fn ($n) => (int) $n)->filter(fn ($n) => $n >= 1)->unique()->sort()->values();
        $mezzanineFloors = collect($property->mezzanine_floors ?? [])->filter()->values();
        $allocations = $property->shareholder_allocations ?? [];
        $models = $property->apartment_models ?? [];
    @endphp

    <div class="card app-surface mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="text-body-secondary small mb-1">عقار</div>
                <h4 class="mb-0 fw-semibold">{{ $property->name }}</h4>
            </div>
            <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary btn-sm">رجوع للقائمة</a>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-lg-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">التعريف والموقع</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                            <tr>
                                <th class="text-body-secondary align-top py-2" style="width: 42%">نوع العقار</th>
                                <td class="py-2">{{ $property->property_type ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">اسم الأرض</th>
                                <td class="py-2">{{ $property->land_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">الأرض المرتبطة</th>
                                <td class="py-2">{{ $property->land?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">المنطقة</th>
                                <td class="py-2">{{ $property->area?->name ?? ($property->location ?? '—') }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">تاريخ الإنشاء</th>
                                <td class="py-2 font-monospace">{{ $property->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">الأدوار والوحدات</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                            <tr>
                                <th class="text-body-secondary align-top py-2" style="width: 42%">إجمالي أدوار البرج</th>
                                <td class="py-2">{{ $property->building_total_floors ?? $property->floors_count ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">أدوار متكررة</th>
                                <td class="py-2">{{ $property->floors_count ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">شقق/دور متكرر</th>
                                <td class="py-2">{{ $property->apartments_per_floor ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">محلات الأرضي</th>
                                <td class="py-2">{{ $property->ground_floor_shops_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">شقق الميزان</th>
                                <td class="py-2">{{ $property->mezzanine_apartments_count ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">إجمالي الشقق</th>
                                <td class="py-2 fw-medium">{{ $property->total_apartments ?? '—' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">أدوار مسجلة ومشاعة وميزان</h6>
                        <div class="small mb-2">
                            <div class="text-body-secondary mb-1">الأدوار المسجّلة</div>
                            @if($registeredFloors->isNotEmpty())
                                <div>{{ $registeredFloors->implode(' ، ') }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <div class="small mb-2">
                            <div class="text-body-secondary mb-1">أدوار مشاعة</div>
                            @if($mushaaFloors->isNotEmpty())
                                @foreach($mushaaFloors as $mf)
                                    <span class="badge text-bg-info me-1 mb-1">
                                        دور {{ $mf }} (مشاع)
                                        @if(filled($property->mushaa_partner_name))
                                            — 50٪ مساهمين / 50٪ {{ $property->mushaa_partner_name }}
                                        @else
                                            — لم يُسجل شريك
                                        @endif
                                    </span>
                                @endforeach
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        @if(filled($property->mushaa_partner_name))
                            <div class="small mb-2">
                                <span class="text-body-secondary">الشريك (مشاع):</span>
                                {{ $property->mushaa_partner_name }}
                            </div>
                        @endif
                        <div class="small">
                            <div class="text-body-secondary mb-1">أدوار الميزان</div>
                            @if($mezzanineFloors->isNotEmpty())
                                @foreach($mezzanineFloors as $item)
                                    <span class="badge text-bg-secondary me-1 mb-1">
                                        الدور {{ (int) ($item['floor_number'] ?? 0) }} (ميزان) · {{ (int) ($item['apartments_count'] ?? 0) }} شقق
                                    </span>
                                @endforeach
                            @elseif($property->has_mezzanine)
                                <span class="badge text-bg-secondary">ميزان واحد (بيانات قديمة)</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 mb-3">
                <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">مصاريف الأرض والبناء</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>البند</th>
                            <th class="text-end">القيمة (ج.م)</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($costRows as $label => $value)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end font-monospace">{{ number_format($value, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-semibold">
                            <td>الإجمالي</td>
                            <td class="text-end font-monospace">{{ number_format($totalCosts, 2) }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">نسب المساهمين</h6>
                        @if (count($allocations))
                            <ul class="mb-0 ps-3">
                                @foreach ($allocations as $allocation)
                                    <li class="mb-1">
                                        {{ $allocation['shareholder_name'] ?? ('مساهم #' . ($allocation['shareholder_id'] ?? '-')) }}:
                                        <span class="font-monospace fw-medium">{{ number_format((float) ($allocation['percentage'] ?? 0), 2) }}%</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">لا توجد نسب مساهمين مسجلة.</p>
                        @endif
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">نماذج ومساحات الشقق</h6>
                        @if (count($models))
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>النموذج</th>
                                        <th class="text-end">المساحة</th>
                                        <th>غرف</th>
                                        <th>حمامات</th>
                                        <th>الواجهة</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($models as $model)
                                        <tr>
                                            <td>{{ $model['model_name'] ?? '—' }}</td>
                                            <td class="text-end font-monospace">{{ number_format((float) ($model['area'] ?? 0), 2) }}</td>
                                            <td>{{ (int) ($model['rooms_count'] ?? 0) }}</td>
                                            <td>{{ (int) ($model['bathrooms_count'] ?? 0) }}</td>
                                            <td>
                                                @php($viewType = $model['view_type'] ?? 'normal')
                                                {{ ($facingNames ?? collect())[$viewType] ?? match ($viewType) {
                                                    'corner' => 'ناصية',
                                                    'facade' => 'واجهة',
                                                    'normal' => 'عادية',
                                                    default => $viewType,
                                                } }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">لا توجد نماذج شقق مسجلة.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
