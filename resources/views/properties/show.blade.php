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
                    <strong>عدد الأدوار:</strong> {{ $property->floors_count ?? '-' }}
                </div>
                <div class="col-md-6">
                    <strong>عدد الشقق بكل دور:</strong> {{ $property->apartments_per_floor ?? '-' }}
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
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($models as $model)
                                    <tr>
                                        <td>{{ $model['model_name'] ?? '-' }}</td>
                                        <td>{{ number_format((float) ($model['area'] ?? 0), 2) }}</td>
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
