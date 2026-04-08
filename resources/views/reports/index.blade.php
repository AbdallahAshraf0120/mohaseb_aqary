@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="التقارير" step="12" />
    <div class="row g-3">
        <div class="col-md-3"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($revenues, 2) }}</h5><p>الإيرادات</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($expenses, 2) }}</h5><p>المصروفات</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($net, 2) }}</h5><p>صافي النتيجة</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($remaining, 2) }}</h5><p>المتبقي على العقود</p></div></div></div>
    </div>
    <div class="card mt-3">
        <div class="card-header"><h5 class="mb-0">تقارير سريعة</h5></div>
        <div class="card-body d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-secondary">تقرير PDF (قريبًا)</button>
            <button class="btn btn-outline-secondary">تصدير Excel (قريبًا)</button>
            <button class="btn btn-outline-secondary">مقارنة شهرية (قريبًا)</button>
        </div>
    </div>
@endsection
