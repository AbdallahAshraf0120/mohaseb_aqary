@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تفاصيل العقد</h5>
            <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>رقم العقد:</strong> CT-{{ now()->format('Y') }}-{{ str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT) }}</div>
                <div class="col-md-4"><strong>العميل:</strong> {{ $contract->client?->name ?? '-' }}</div>
                <div class="col-md-4"><strong>العقار:</strong> {{ $contract->property?->name ?? '-' }}</div>
                <div class="col-md-4"><strong>تاريخ البداية:</strong> {{ $contract->start_date }}</div>
                <div class="col-md-4"><strong>تاريخ النهاية:</strong> {{ $contract->end_date }}</div>
                <div class="col-md-4"><strong>مرجع البيعة:</strong> {{ $contract->sale_id ? 'SL-' . str_pad((string) $contract->sale_id, 3, '0', STR_PAD_LEFT) : '-' }}</div>
                <div class="col-md-4"><strong>إجمالي العقد:</strong> {{ number_format((float) $contract->total_price, 2) }}</div>
                <div class="col-md-4"><strong>المسدَّد:</strong> {{ number_format((float) $contract->paid_amount, 2) }}</div>
                <div class="col-md-4"><strong>المتبقي:</strong> {{ number_format((float) $contract->remaining_amount, 2) }}</div>
            </div>
        </div>
    </div>
@endsection
