@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => number_format($n, 2, '.', ',');
        $currencyLabel = strtoupper((string) $currency) === 'EGP' ? 'ج.م' : $currency;
    @endphp

    <div class="card app-surface mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="text-body-secondary small mb-1">المشروع الحالي</div>
                    <h2 class="h4 mb-2 fw-semibold">{{ $project->name }}</h2>
                    <p class="text-body-secondary mb-0 small">
                        ملخص للصندوق والتحصيل والالتزامات — {{ now()->locale('ar')->translatedFormat('l j F Y') }}
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('properties.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-building ms-1"></i> العقارات
                    </a>
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-diagram-project ms-1"></i> المشاريع
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('cashbox.index') }}" class="dashboard-kpi-link d-block h-100">
                <div class="dashboard-kpi-card text-bg-primary shadow-sm h-100">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="opacity-75 small mb-1">رصيد الصندوق</div>
                                <div class="fs-4 fw-bold font-monospace text-truncate" title="{{ $fmt($balance) }}">{{ $fmt($balance) }}</div>
                                <div class="opacity-75 small mt-1">{{ $currencyLabel }}</div>
                            </div>
                            <i class="fa-solid fa-vault fa-2x opacity-50 flex-shrink-0"></i>
                        </div>
                    </div>
                    <div class="px-4 py-2 small bg-black bg-opacity-10">الصندوق <i class="fa-solid fa-arrow-left ms-1"></i></div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('revenues.index') }}" class="dashboard-kpi-link d-block h-100">
                <div class="dashboard-kpi-card text-bg-success shadow-sm h-100">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="opacity-75 small mb-1">إجمالي الوارد</div>
                                <div class="fs-4 fw-bold font-monospace text-truncate" title="{{ $fmt($treasuryIn) }}">{{ $fmt($treasuryIn) }}</div>
                                <div class="opacity-75 small mt-1">{{ $currencyLabel }}</div>
                            </div>
                            <i class="fa-solid fa-arrow-trend-up fa-2x opacity-50 flex-shrink-0"></i>
                        </div>
                    </div>
                    <div class="px-4 py-2 small bg-black bg-opacity-10">التحصيل <i class="fa-solid fa-arrow-left ms-1"></i></div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('expenses.index') }}" class="dashboard-kpi-link d-block h-100">
                <div class="dashboard-kpi-card text-bg-danger shadow-sm h-100">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="opacity-75 small mb-1">إجمالي المصروف</div>
                                <div class="fs-4 fw-bold font-monospace text-truncate" title="{{ $fmt($treasuryOut) }}">{{ $fmt($treasuryOut) }}</div>
                                <div class="opacity-75 small mt-1">{{ $currencyLabel }}</div>
                            </div>
                            <i class="fa-solid fa-arrow-trend-down fa-2x opacity-50 flex-shrink-0"></i>
                        </div>
                    </div>
                    <div class="px-4 py-2 small bg-black bg-opacity-10">المصروفات <i class="fa-solid fa-arrow-left ms-1"></i></div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('remaining.index') }}" class="dashboard-kpi-link d-block h-100">
                <div class="dashboard-kpi-card text-bg-warning shadow-sm h-100">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="opacity-75 small mb-1 text-dark">متبقي العقود</div>
                                <div class="fs-4 fw-bold font-monospace text-dark text-truncate" title="{{ $fmt($stats['remaining_total']) }}">{{ $fmt($stats['remaining_total']) }}</div>
                                <div class="opacity-75 small mt-1 text-dark">{{ $currencyLabel }}</div>
                            </div>
                            <i class="fa-solid fa-file-invoice-dollar fa-2x opacity-50 text-dark flex-shrink-0"></i>
                        </div>
                    </div>
                    <div class="px-4 py-2 small bg-dark bg-opacity-10 text-dark">المتبقي <i class="fa-solid fa-arrow-left ms-1"></i></div>
                </div>
            </a>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header border-0 bg-transparent pt-4 px-4 pb-0">
            <div>
                <h5 class="mb-0 fw-semibold">أرقام المشروع</h5>
                <p class="small text-body-secondary mb-0 mt-1">اختصارات لأهم السجلات والأعداد</p>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-2 g-md-3">
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('properties.index') }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                        <span class="tile-icon text-bg-secondary"><i class="fa-solid fa-building"></i></span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">عقارات</div>
                            <div class="fs-5 fw-semibold font-monospace">{{ $stats['properties'] }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('clients.index') }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                        <span class="tile-icon text-bg-info"><i class="fa-solid fa-users"></i></span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">عملاء</div>
                            <div class="fs-5 fw-semibold font-monospace">{{ $stats['clients'] }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('sales.index') }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                        <span class="tile-icon text-bg-primary"><i class="fa-solid fa-cart-shopping"></i></span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">مبيعات</div>
                            <div class="fs-5 fw-semibold font-monospace">{{ $stats['sales'] }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('contracts.index') }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                        <span class="tile-icon text-bg-warning"><i class="fa-solid fa-file-signature"></i></span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">عقود بمتبقي</div>
                            <div class="fs-5 fw-semibold font-monospace">{{ $stats['contracts_with_balance'] }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('debts.index') }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                        <span class="tile-icon text-bg-danger"><i class="fa-solid fa-scale-balanced"></i></span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">ديون مفتوحة</div>
                            <div class="fs-5 fw-semibold font-monospace">{{ $stats['debts_open'] }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a href="{{ route('revenues.index') }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                        <span class="tile-icon text-bg-success"><i class="fa-solid fa-coins"></i></span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">تحصيل الشهر</div>
                            <div class="small fw-semibold font-monospace text-truncate" title="{{ $fmt($stats['revenues_this_month']) }}">{{ $fmt($stats['revenues_this_month']) }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="{{ route('areas.index') }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                        <span class="tile-icon bg-body-secondary text-body"><i class="fa-solid fa-location-dot"></i></span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">مناطق</div>
                            <div class="fs-5 fw-semibold font-monospace">{{ $stats['areas'] }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="{{ route('shareholders.index', $project) }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                        <span class="tile-icon bg-body-secondary text-body"><i class="fa-solid fa-people-group"></i></span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">مساهمين</div>
                            <div class="fs-5 fw-semibold font-monospace">{{ $stats['shareholders'] }}</div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-4">
                    <a href="{{ route('contracts.index') }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                        <span class="tile-icon bg-body-secondary text-body"><i class="fa-solid fa-file-contract"></i></span>
                        <div class="min-w-0">
                            <div class="small text-body-secondary">إجمالي العقود</div>
                            <div class="fs-5 fw-semibold font-monospace">{{ $stats['contracts_total'] }}</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card app-surface h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-0 bg-transparent pt-4 px-4 pb-0">
                    <div>
                        <h5 class="mb-0 fw-semibold">آخر المبيعات</h5>
                        <p class="small text-body-secondary mb-0 mt-1">آخر {{ $recentSales->count() }} عمليات</p>
                    </div>
                    <a href="{{ route('sales.create') }}" class="btn btn-sm btn-primary">تسجيل بيعة</a>
                </div>
                <div class="card-body p-0 pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>العميل</th>
                                <th>العقار</th>
                                <th>البروكر</th>
                                <th class="text-end">المبلغ</th>
                                <th class="text-end">التاريخ</th>
                                <th class="text-end" style="width: 4rem"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($recentSales as $sale)
                                <tr>
                                    <td>{{ $sale->client?->name ?? '—' }}</td>
                                    <td class="text-truncate" style="max-width: 7rem">{{ $sale->property?->name ?? '—' }}</td>
                                    <td class="text-truncate small" style="max-width: 5rem">{{ $sale->broker_name ?: '—' }}</td>
                                    <td class="text-end font-monospace small">{{ $fmt((float) $sale->sale_price) }}</td>
                                    <td class="text-end small font-monospace">{{ $sale->sale_date?->format('Y-m-d') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('sales.show', [$project, $sale]) }}" class="btn btn-outline-secondary btn-sm py-0 px-1" title="تفاصيل">عرض</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-body-secondary py-4">لا توجد مبيعات بعد</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="{{ route('sales.index') }}" class="btn btn-link btn-sm px-0">عرض كل المبيعات <i class="fa-solid fa-arrow-left ms-1"></i></a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card app-surface h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-0 bg-transparent pt-4 px-4 pb-0">
                    <div>
                        <h5 class="mb-0 fw-semibold">آخر التحصيلات</h5>
                        <p class="small text-body-secondary mb-0 mt-1">آخر {{ $recentRevenues->count() }} حركات</p>
                    </div>
                    <a href="{{ route('revenues.create') }}" class="btn btn-sm btn-success">تحصيل دفعة</a>
                </div>
                <div class="card-body p-0 pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>العميل</th>
                                <th>البند</th>
                                <th class="text-end">المبلغ</th>
                                <th class="text-end">التاريخ</th>
                                <th class="text-end" style="width: 4rem"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($recentRevenues as $rev)
                                <tr>
                                    <td>{{ $rev->client?->name ?? '—' }}</td>
                                    <td class="text-truncate" style="max-width: 7rem">{{ $rev->category ?? $rev->source ?? '—' }}</td>
                                    <td class="text-end font-monospace small">{{ $fmt((float) $rev->amount) }}</td>
                                    <td class="text-end small font-monospace">{{ $rev->paid_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('revenues.show', [$project, $rev]) }}" class="btn btn-outline-secondary btn-sm py-0 px-1" title="تفاصيل">عرض</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-body-secondary py-4">لا توجد تحصيلات بعد</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="{{ route('revenues.index') }}" class="btn btn-link btn-sm px-0">عرض كل التحصيلات <i class="fa-solid fa-arrow-left ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header">
            <h5 class="mb-0 fw-semibold">إجراءات سريعة</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('properties.create') }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-plus ms-1"></i> إضافة عقار
                </a>
                <a href="{{ route('sales.create') }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-cart-plus ms-1"></i> تسجيل بيعة
                </a>
                <a href="{{ route('revenues.create') }}" class="btn btn-outline-success">
                    <i class="fa-solid fa-money-bill-wave ms-1"></i> تحصيل
                </a>
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-users ms-1"></i> العملاء
                </a>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-chart-line ms-1"></i> التقارير
                </a>
                <a href="{{ route('settlements.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-filter-circle-dollar ms-1"></i> التصفيات
                </a>
                <a href="{{ route('settings.edit') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-gear ms-1"></i> الإعدادات
                </a>
            </div>
        </div>
    </div>
@endsection
