@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => number_format($n, 2, '.', ',');
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-12">
            <p class="text-body-secondary mb-0">ملخص سريع للمشروع الحالي والصندوق والالتزامات.</p>
        </div>
    </div>

    <div class="row g-3 mb-2">
        <div class="col-xl-3 col-md-6">
            <div class="small-box text-bg-primary shadow-sm">
                <div class="inner">
                    <h3>{{ $fmt($balance) }}</h3>
                    <p class="mb-0">رصيد الصندوق <span class="opacity-75 small">({{ $currency }})</span></p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-vault"></i>
                </div>
                <a href="{{ route('cashbox.index') }}" class="small-box-footer">الصندوق <i class="fa-solid fa-arrow-left ms-1"></i></a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="small-box text-bg-success shadow-sm">
                <div class="inner">
                    <h3>{{ $fmt($treasuryIn) }}</h3>
                    <p class="mb-0">إجمالي الوارد <span class="opacity-75 small">({{ $currency }})</span></p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
                <a href="{{ route('revenues.index') }}" class="small-box-footer">التحصيل <i class="fa-solid fa-arrow-left ms-1"></i></a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="small-box text-bg-danger shadow-sm">
                <div class="inner">
                    <h3>{{ $fmt($treasuryOut) }}</h3>
                    <p class="mb-0">إجمالي المصروف <span class="opacity-75 small">({{ $currency }})</span></p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-arrow-trend-down"></i>
                </div>
                <a href="{{ route('expenses.index') }}" class="small-box-footer">المصروفات <i class="fa-solid fa-arrow-left ms-1"></i></a>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="small-box text-bg-warning shadow-sm">
                <div class="inner">
                    <h3>{{ $fmt($stats['remaining_total']) }}</h3>
                    <p class="mb-0">متبقي العقود <span class="opacity-75 small">({{ $currency }})</span></p>
                </div>
                <div class="small-box-icon">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <a href="{{ route('remaining.index') }}" class="small-box-footer">المتبقي <i class="fa-solid fa-arrow-left ms-1"></i></a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4 col-6">
            <div class="info-box shadow-sm">
                <span class="info-box-icon text-bg-secondary"><i class="fa-solid fa-building"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">عقارات</span>
                    <span class="info-box-number">{{ $stats['properties'] }}</span>
                </div>
                <a href="{{ route('properties.index') }}" class="stretched-link text-decoration-none" aria-label="العقارات"></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="info-box shadow-sm">
                <span class="info-box-icon text-bg-info"><i class="fa-solid fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">عملاء</span>
                    <span class="info-box-number">{{ $stats['clients'] }}</span>
                </div>
                <a href="{{ route('clients.index') }}" class="stretched-link text-decoration-none" aria-label="العملاء"></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="info-box shadow-sm">
                <span class="info-box-icon text-bg-primary"><i class="fa-solid fa-cart-shopping"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">مبيعات</span>
                    <span class="info-box-number">{{ $stats['sales'] }}</span>
                </div>
                <a href="{{ route('sales.index') }}" class="stretched-link text-decoration-none" aria-label="المبيعات"></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="info-box shadow-sm">
                <span class="info-box-icon text-bg-warning"><i class="fa-solid fa-file-signature"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">عقود بمتبقي</span>
                    <span class="info-box-number">{{ $stats['contracts_with_balance'] }}</span>
                </div>
                <a href="{{ route('contracts.index') }}" class="stretched-link text-decoration-none" aria-label="العقود"></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="info-box shadow-sm">
                <span class="info-box-icon text-bg-danger"><i class="fa-solid fa-scale-balanced"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">ديون مفتوحة</span>
                    <span class="info-box-number">{{ $stats['debts_open'] }}</span>
                </div>
                <a href="{{ route('debts.index') }}" class="stretched-link text-decoration-none" aria-label="المديونية"></a>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6">
            <div class="info-box shadow-sm">
                <span class="info-box-icon text-bg-success"><i class="fa-solid fa-coins"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">تحصيل الشهر</span>
                    <span class="info-box-number small d-block text-truncate" title="{{ $fmt($stats['revenues_this_month']) }}">{{ $fmt($stats['revenues_this_month']) }}</span>
                </div>
                <a href="{{ route('revenues.index') }}" class="stretched-link text-decoration-none" aria-label="التحصيل"></a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
            <div class="d-flex align-items-center p-3 border rounded bg-body-secondary shadow-sm">
                <i class="fa-solid fa-location-dot fa-2x text-body-secondary me-3"></i>
                <div>
                    <div class="small text-body-secondary">مناطق</div>
                    <div class="fs-4 fw-semibold">{{ $stats['areas'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="d-flex align-items-center p-3 border rounded bg-body-secondary shadow-sm">
                <i class="fa-solid fa-people-group fa-2x text-body-secondary me-3"></i>
                <div>
                    <div class="small text-body-secondary">مساهمين</div>
                    <div class="fs-4 fw-semibold">{{ $stats['shareholders'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12">
            <div class="d-flex align-items-center p-3 border rounded bg-body-secondary shadow-sm">
                <i class="fa-solid fa-file-contract fa-2x text-body-secondary me-3"></i>
                <div>
                    <div class="small text-body-secondary">إجمالي العقود</div>
                    <div class="fs-4 fw-semibold">{{ $stats['contracts_total'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">آخر المبيعات</span>
                    <a href="{{ route('sales.create') }}" class="btn btn-sm btn-primary">تسجيل بيعة</a>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>العميل</th>
                                <th>العقار</th>
                                <th class="text-end">المبلغ</th>
                                <th class="text-end">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSales as $sale)
                                <tr>
                                    <td>{{ $sale->client?->name ?? '—' }}</td>
                                    <td class="text-truncate" style="max-width: 8rem">{{ $sale->property?->name ?? '—' }}</td>
                                    <td class="text-end">{{ $fmt((float) $sale->sale_price) }}</td>
                                    <td class="text-end small">{{ $sale->sale_date?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary py-4">لا توجد مبيعات بعد</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('sales.index') }}" class="small">عرض كل المبيعات</a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">آخر التحصيلات</span>
                    <a href="{{ route('revenues.create') }}" class="btn btn-sm btn-success">تحصيل دفعة</a>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>العميل</th>
                                <th>البند</th>
                                <th class="text-end">المبلغ</th>
                                <th class="text-end">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentRevenues as $rev)
                                <tr>
                                    <td>{{ $rev->client?->name ?? '—' }}</td>
                                    <td class="text-truncate" style="max-width: 7rem">{{ $rev->category ?? $rev->source ?? '—' }}</td>
                                    <td class="text-end">{{ $fmt((float) $rev->amount) }}</td>
                                    <td class="text-end small">{{ $rev->paid_at?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary py-4">لا توجد تحصيلات بعد</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('revenues.index') }}" class="small">عرض كل التحصيلات</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-2 mt-2">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('properties.create') }}" class="btn btn-outline-primary btn-sm">إضافة عقار</a>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">التقارير</a>
                <a href="{{ route('settlements.index') }}" class="btn btn-outline-secondary btn-sm">التصفيات</a>
            </div>
        </div>
    </div>
@endsection
