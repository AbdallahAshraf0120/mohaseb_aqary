@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تفاصيل البيعة</h5>
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            @php
                $floorLabel = (string) $sale->floor_number;
                $scheduleType = $sale->installment_plan['schedule_type'] ?? 'monthly';
                $scheduleLabel = $scheduleType === 'quarterly' ? 'كل 3 شهور' : 'شهري';
                if ((int) $sale->floor_number === 0) {
                    $floorLabel = '0 (أرضي تجاري)';
                } elseif ((int) $sale->floor_number === 1 && ($sale->property?->has_mezzanine ?? false)) {
                    $floorLabel = '1 (ميزان)';
                }
            @endphp
            <div class="row g-3">
                <div class="col-md-4"><strong>العقار:</strong> {{ $sale->property?->name ?? '-' }}</div>
                <div class="col-md-4"><strong>الدور:</strong> {{ $floorLabel }}</div>
                <div class="col-md-4"><strong>النموذج:</strong> {{ $sale->apartment_model }}</div>
                <div class="col-md-4"><strong>سعر البيع:</strong> {{ number_format((float) $sale->sale_price, 2) }}</div>
                <div class="col-md-4"><strong>نوع السداد:</strong> {{ $sale->payment_type === 'cash' ? 'كاش' : 'تقسيط' }}</div>
                <div class="col-md-4"><strong>المقدم:</strong> {{ number_format((float) $sale->down_payment, 2) }}</div>
                <div class="col-md-4"><strong>مدة التقسيط:</strong> {{ $sale->installment_months ?: '-' }}</div>
                <div class="col-md-4"><strong>نظام القسط:</strong> {{ $sale->payment_type === 'installment' ? $scheduleLabel : '-' }}</div>
                <div class="col-md-4"><strong>بداية القسط:</strong> {{ $sale->installment_start_date?->format('Y-m-d') ?? '-' }}</div>
                <div class="col-md-4"><strong>تاريخ البيعة:</strong> {{ $sale->sale_date?->format('Y-m-d') }}</div>
                <div class="col-md-6"><strong>العميل:</strong> {{ $sale->client?->name ?? '-' }}</div>
                <div class="col-md-6"><strong>هاتف العميل:</strong> {{ $sale->client?->phone ?? '-' }}</div>
                <div class="col-12"><strong>ملاحظات:</strong> {{ $sale->notes ?: '-' }}</div>
            </div>
        </div>
    </div>
@endsection
