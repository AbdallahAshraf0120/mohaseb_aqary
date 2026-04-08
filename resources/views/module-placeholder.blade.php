@extends('layouts.admin')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h4 class="mb-1">{{ $module['label'] }}</h4>
                        <p class="text-muted mb-0">هذه صفحة بداية (Starter) لتطوير موديول {{ $module['label'] }}.</p>
                    </div>
                    <span class="badge text-bg-primary">Demo Ready</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">خطة التطوير المقترحة</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">إنشاء الجداول والموديلات الخاصة بالموديول.</li>
                        <li class="mb-2">بناء CRUD (إضافة - تعديل - حذف - عرض).</li>
                        <li class="mb-2">تطبيق التحقق من المدخلات (Validation).</li>
                        <li class="mb-2">ربط الصلاحيات (Role & Permission).</li>
                        <li class="mb-2">إضافة تقارير وفلترة وعمليات تصدير لاحقًا.</li>
                        <li>كتابة اختبارات أساسية للنقاط الحرجة.</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">تنقل سريع</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    @foreach (($modules ?? []) as $key => $item)
                        @php
                            $quickLink = $item['route'] === 'modules.show'
                                ? route('modules.show', $key)
                                : route($item['route']);
                        @endphp
                        <a href="{{ $quickLink }}"
                           class="btn btn-outline-secondary text-start {{ $moduleKey === $key ? 'active' : '' }}">
                            <i class="fa-solid {{ $item['icon'] }} me-2"></i>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
