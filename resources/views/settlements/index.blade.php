@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="التسويات" step="11" />
    <div class="row g-3">
        <div class="col-md-4"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($revenues, 2) }}</h5><p>إجمالي الإيرادات</p></div></div></div>
        <div class="col-md-4"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($expenses, 2) }}</h5><p>إجمالي المصروفات</p></div></div></div>
        <div class="col-md-4"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($net, 2) }}</h5><p>صافي التسوية</p></div></div></div>
    </div>
    <div class="card mt-3">
        <div class="card-header"><h5 class="mb-0">ملخص التسويات</h5></div>
        <div class="card-body text-muted">هذه شاشة تسوية تشغيلية أولية. يمكن إضافة اعتماد قيد وتصدير قيود لاحقًا.</div>
    </div>
@endsection
