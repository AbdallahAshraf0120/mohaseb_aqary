@extends('layouts.admin')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-2">نظام إدارة ومحاسبة العقارات</h4>
                    <p class="text-muted mb-0">
                        عرض توضيحي جاهز يوضح رحلة العمل داخل النظام من الصلاحيات وحتى التقارير المالية.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-3 col-md-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>Role & Permission</h3>
                    <p>إدارة الأدوار والصلاحيات للمستخدمين</p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3>العقارات</h3>
                    <p>الوحدات، الحالة، والبيانات الأساسية</p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-building"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>العملاء</h3>
                    <p>إدارة بيانات العملاء وربطهم بالعقود</p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>العقود</h3>
                    <p>تسجيل العقود ومتابعة الاستحقاقات</p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-3 col-md-6">
            <div class="small-box text-bg-secondary">
                <div class="inner">
                    <h3>المبيعات</h3>
                    <p>عمليات البيع والدفعات والمتبقي</p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>الإيرادات</h3>
                    <p>تتبع التحصيلات والإيرادات الدورية</p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-money-bill-trend-up"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>المصروفات</h3>
                    <p>تسجيل المصروفات التشغيلية والإدارية</p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box text-bg-dark">
                <div class="inner">
                    <h3>الصندوق</h3>
                    <p>حركة القبض والصرف والرصيد الحالي</p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-vault"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">محاور العرض السريع</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">الدخول بالنظام ومراجعة الصلاحيات.</li>
                        <li class="mb-2">استعراض العقارات والعملاء.</li>
                        <li class="mb-2">متابعة العقود والمبيعات والمتبقي.</li>
                        <li class="mb-2">مراجعة الإيرادات والمصروفات والصندوق.</li>
                        <li class="mb-2">عرض المديونية والتصفيات والمساهمين.</li>
                        <li>إنهاء العرض بالتقارير والإعدادات.</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">الموديولات الأساسية</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (($modules ?? []) as $moduleKey => $module)
                            @php
                                $moduleHref = $module['route'] === 'modules.show'
                                    ? route('modules.show', $moduleKey)
                                    : route($module['route']);
                            @endphp
                            <a href="{{ $moduleHref }}" class="badge text-bg-light border text-decoration-none">
                                {{ $module['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
