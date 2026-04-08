@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تفاصيل المساهم</h5>
            <a href="{{ route('shareholders.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>اسم المساهم:</strong> {{ $shareholder->name }}
                </div>
                <div class="col-md-6">
                    <strong>نسبة المساهمة:</strong> {{ number_format((float) $shareholder->share_percentage, 2) }}%
                </div>
                <div class="col-md-6">
                    <strong>رأس المال:</strong> {{ number_format((float) $shareholder->total_investment, 2) }}
                </div>
                <div class="col-md-6">
                    <strong>الأرباح:</strong> {{ number_format((float) $shareholder->profit_amount, 2) }}
                </div>
                <div class="col-md-6">
                    <strong>تاريخ الإنشاء:</strong> {{ $shareholder->created_at?->format('Y-m-d H:i') }}
                </div>
            </div>
        </div>
    </div>
@endsection
