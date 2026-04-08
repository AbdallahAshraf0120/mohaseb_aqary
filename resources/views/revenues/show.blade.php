@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تفاصيل التحصيل</h5>
            <a href="{{ route('revenues.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>رقم الإيصال:</strong> RV-{{ str_pad((string) $revenue->id, 3, '0', STR_PAD_LEFT) }}</div>
                <div class="col-md-4"><strong>العميل:</strong> {{ $revenue->client?->name ?? '-' }}</div>
                <div class="col-md-4"><strong>العقد:</strong> {{ $revenue->contract_id ? 'CT-' . now()->format('Y') . '-' . str_pad((string) $revenue->contract_id, 3, '0', STR_PAD_LEFT) : '-' }}</div>
                <div class="col-md-4"><strong>المبلغ:</strong> {{ number_format((float) $revenue->amount, 2) }}</div>
                <div class="col-md-4"><strong>النوع:</strong> {{ $revenue->category }}</div>
                <div class="col-md-4"><strong>طريقة الدفع:</strong> {{ $revenue->payment_method }}</div>
                <div class="col-md-4"><strong>تاريخ التحصيل:</strong> {{ $revenue->paid_at?->format('Y-m-d') ?? '-' }}</div>
                <div class="col-md-4"><strong>المصدر:</strong> {{ $revenue->source ?: '-' }}</div>
                <div class="col-md-4"><strong>مرجع البيعة:</strong> {{ $revenue->sale_id ? 'SL-' . str_pad((string) $revenue->sale_id, 3, '0', STR_PAD_LEFT) : '-' }}</div>
                <div class="col-12"><strong>ملاحظات:</strong> {{ $revenue->notes ?: '-' }}</div>
            </div>
        </div>
    </div>
@endsection
