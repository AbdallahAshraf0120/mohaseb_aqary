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
                    <strong>الموقع:</strong> {{ $property->location }}
                </div>
                <div class="col-md-6">
                    <strong>السعر:</strong> {{ number_format((float) $property->price, 2) }}
                </div>
                <div class="col-md-6">
                    <strong>الحالة:</strong> {{ $property->status }}
                </div>
                <div class="col-md-6">
                    <strong>المالك:</strong> {{ $property->owner?->name ?? '-' }}
                </div>
                <div class="col-md-6">
                    <strong>تاريخ الإنشاء:</strong> {{ $property->created_at?->format('Y-m-d H:i') }}
                </div>
            </div>
        </div>
    </div>
@endsection
