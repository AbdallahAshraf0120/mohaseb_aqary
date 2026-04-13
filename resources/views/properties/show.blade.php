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
                            <span class="badge text-bg-info me-1">دور {{ $mf }} (50٪ مساهمين / 50٪ شريك)</span>
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
                                دور {{ (int) ($item['floor_number'] ?? 0) }} · {{ (int) ($item['apartments_count'] ?? 0) }} شقق
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
                                            {{ $viewType === 'corner' ? 'ناصية' : ($viewType === 'facade' ? 'واجهة' : 'عادية') }}
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
