@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تفاصيل العقار</h5>
            <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>الاسم:</strong> {{ $property->name }}
                </div>
                <div class="col-md-6">
                    <strong>نوع العقار:</strong> {{ $property->property_type ?? '-' }}
                </div>
                <div class="col-md-6">
                    <strong>اسم الأرض:</strong> {{ $property->land_name ?? '-' }}
                </div>
                <div class="col-md-6">
                    <strong>الأرض المرتبطة:</strong> {{ $property->land?->name ?? '-' }}
                </div>
                <div class="col-md-6">
                    <strong>المنطقة:</strong> {{ $property->area?->name ?? ($property->location ?? '-') }}
                </div>
                <div class="col-md-6">
                    <strong>إجمالي أدوار البرج:</strong> {{ $property->building_total_floors ?? $property->floors_count ?? '-' }}
                </div>
                <div class="col-md-6">
                    <strong>الأدوار المتكررة:</strong> {{ $property->floors_count ?? '-' }}
                </div>
                <div class="col-md-6">
                    <strong>شقق/دور متكرر:</strong> {{ $property->apartments_per_floor ?? '-' }}
                </div>
                <div class="col-12">
                    <strong>الأدوار المسجلة:</strong>
                    @php($registeredFloors = collect($property->registered_floors ?? [])->filter()->values())
                    @if($registeredFloors->isNotEmpty())
                        {{ $registeredFloors->implode(' ، ') }}
                    @else
                        —
                    @endif
                </div>
                <div class="col-12">
                    <strong>أدوار مشاعة:</strong>
                    @php($mushaaFloors = collect($property->mushaa_floors ?? [])->map(fn ($n) => (int) $n)->filter(fn ($n) => $n >= 1)->unique()->sort()->values())
                    @if($mushaaFloors->isNotEmpty())
                        @foreach($mushaaFloors as $mf)
                            <span class="badge text-bg-info me-1">
                                دور {{ $mf }} (مشاع)
                                @if(filled($property->mushaa_partner_name))
                                    — 50٪ مساهمين / 50٪ {{ $property->mushaa_partner_name }}
                                @else
                                    — لم يُسجل شريك (أضف الاسم في التعديل لتفعيل تقسيم 50/50 مع الشريك)
                                @endif
                            </span>
                        @endforeach
                    @else
                        —
                    @endif
                </div>
                <div class="col-md-6">
                    <strong>محلات الأرضي (0):</strong> {{ $property->ground_floor_shops_count ?? 0 }}
                </div>
                <div class="col-md-6">
                    <strong>إجمالي شقق الميزان:</strong> {{ $property->mezzanine_apartments_count ?? 0 }}
                </div>
                @if(filled($property->mushaa_partner_name))
                    <div class="col-md-6">
                        <strong>الشريك الآخر (مشاع):</strong> {{ $property->mushaa_partner_name }}
                    </div>
                @endif
                <div class="col-12">
                    <strong>أدوار الميزان:</strong>
                    @php($mezzanineFloors = collect($property->mezzanine_floors ?? [])->filter()->values())
                    @if($mezzanineFloors->isNotEmpty())
                        @foreach($mezzanineFloors as $item)
                            <span class="badge text-bg-secondary me-1">
                                الدور {{ (int) ($item['floor_number'] ?? 0) }} (ميزان) · {{ (int) ($item['apartments_count'] ?? 0) }} شقق
                            </span>
                        @endforeach
                    @elseif($property->has_mezzanine)
                        <span class="badge text-bg-secondary">ميزان واحد (بيانات قديمة)</span>
                    @else
                        —
                    @endif
                </div>
                <div class="col-md-6">
                    <strong>إجمالي الشقق:</strong> {{ $property->total_apartments ?? '-' }}
                </div>
                <div class="col-md-6">
                    <strong>تاريخ الإنشاء:</strong> {{ $property->created_at?->format('Y-m-d H:i') }}
                </div>
                <div class="col-12">
                    <hr>
                    <h6>مصاريف الأرض والبناء</h6>
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
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                            <tr>
                                <th>البند</th>
                                <th class="text-end">القيمة</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($costRows as $label => $value)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end">{{ number_format($value, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold">
                                <td>الإجمالي</td>
                                <td class="text-end">{{ number_format($totalCosts, 2) }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-12">
                    <hr>
                    <h6>نسب المساهمين</h6>
                    @php($allocations = $property->shareholder_allocations ?? [])
                    @if (count($allocations))
                        <ul class="mb-0">
                            @foreach ($allocations as $allocation)
                                <li>{{ $allocation['shareholder_name'] ?? ('مساهم #' . ($allocation['shareholder_id'] ?? '-')) }}:
                                    {{ number_format((float) ($allocation['percentage'] ?? 0), 2) }}%</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">لا توجد نسب مساهمين مسجلة.</p>
                    @endif
                </div>

                <div class="col-12">
                    <hr>
                    <h6>نماذج ومساحات الشقق</h6>
                    @php($models = $property->apartment_models ?? [])
                    @if (count($models))
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                <tr>
                                    <th>اسم النموذج</th>
                                    <th>المساحة (م2)</th>
                                    <th>الغرف</th>
                                    <th>الحمامات</th>
                                    <th>الواجهة</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($models as $model)
                                    <tr>
                                        <td>{{ $model['model_name'] ?? '-' }}</td>
                                        <td>{{ number_format((float) ($model['area'] ?? 0), 2) }}</td>
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
@endsection
