@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => number_format($n, 2, '.', ',');
    @endphp

    <x-partials.module-wireflow-header label="التقارير" step="12" />

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="h4 fw-semibold mb-1">تقارير المشروع</h2>
            <p class="text-body-secondary small mb-0">{{ $project->name }} — ملخص مالي وتشغيلي حسب الفترة والبحث.</p>
        </div>
        @can('reports.export')
            <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-success btn-sm">
                <i class="fa-solid fa-file-csv ms-1"></i> تصدير CSV
            </a>
        @endcan
    </div>

    <x-listing.filters
        :placeholder="'عميل، بند تحصيل، ملاحظة، فئة مصروف…'"
        :help="'الفترة: إذا تركت التواريخ فارغة يُعتمد الشهر الحالي حتى اليوم. البحث يُطبَّق على أرقام التقرير والجداول أدناه.'"
    />

    <div class="alert alert-light border small mb-4">
        <strong>الفترة المعروضة:</strong>
        <span class="font-monospace">{{ $periodFrom->format('Y-m-d') }}</span>
        —
        <span class="font-monospace">{{ $periodTo->format('Y-m-d') }}</span>
        @if ($filters->q !== '')
            <span class="mx-2">|</span> <strong>بحث:</strong> {{ $filters->q }}
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="rounded-4 border p-4 h-100 bg-body-secondary bg-opacity-25">
                <div class="small text-body-secondary mb-1">تحصيلات الفترة</div>
                <div class="fs-4 fw-bold font-monospace text-success-emphasis">{{ $fmt($periodStats['revenues_sum']) }}</div>
                <div class="small text-muted">{{ $currencyLabel }} — {{ $periodStats['revenues_count'] }} إيصال</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="rounded-4 border p-4 h-100 bg-body-secondary bg-opacity-25">
                <div class="small text-body-secondary mb-1">مصروفات الفترة</div>
                <div class="fs-4 fw-bold font-monospace text-danger-emphasis">{{ $fmt($periodStats['expenses_sum']) }}</div>
                <div class="small text-muted">{{ $currencyLabel }} — {{ $periodStats['expenses_count'] }} سجل</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="rounded-4 border p-4 h-100 bg-body-secondary bg-opacity-25">
                <div class="small text-body-secondary mb-1">صافي (تحصيل − مصروف)</div>
                <div class="fs-4 fw-bold font-monospace">{{ $fmt($periodStats['net_revenue_expense']) }}</div>
                <div class="small text-muted">{{ $currencyLabel }}</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="rounded-4 border p-4 h-100 bg-body-secondary bg-opacity-25">
                <div class="small text-body-secondary mb-1">صندوق الفترة (وارد − صادر)</div>
                <div class="fs-4 fw-bold font-monospace">{{ $fmt($periodStats['net_treasury']) }}</div>
                <div class="small text-muted">يدوي: قبض {{ $fmt($periodStats['treasury_in']) }} / صرف {{ $fmt($periodStats['treasury_out']) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card app-surface h-100">
                <div class="card-header">
                    <div>
                        <h5 class="mb-0 fw-semibold">مبيعات الفترة</h5>
                        <p class="small text-body-secondary mb-0 mt-1">{{ $periodStats['sales_count'] }} بيعة — إجمالي {{ $fmt($periodStats['sales_sum']) }} {{ $currencyLabel }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="small text-body-secondary mb-1">مجموع المقدمات</div>
                    <div class="fs-5 font-monospace fw-semibold">{{ $fmt($periodStats['sales_down']) }} {{ $currencyLabel }}</div>
                    <hr>
                    <div class="small text-body-secondary mb-1">المتبقي الحالي على كل العقود</div>
                    <div class="fs-5 font-monospace fw-semibold">{{ $fmt($contractsRemaining) }} {{ $currencyLabel }}</div>
                    <div class="small text-muted">{{ $contractsOpenCount }} عقداً بها متبقٍ</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card app-surface h-100">
                <div class="card-header">
                    <h5 class="mb-0 fw-semibold">إجماليات المشروع (كل الفترات)</h5>
                </div>
                <div class="card-body small">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>تحصيلات متراكمة</span>
                        <span class="font-monospace fw-semibold">{{ $fmt($allTime['revenues_sum']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>مصروفات متراكمة</span>
                        <span class="font-monospace fw-semibold">{{ $fmt($allTime['expenses_sum']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>وارد الصندوق اليدوي</span>
                        <span class="font-monospace fw-semibold text-success-emphasis">{{ $fmt($allTime['treasury_in']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>صادر الصندوق اليدوي</span>
                        <span class="font-monospace fw-semibold text-danger-emphasis">{{ $fmt($allTime['treasury_out']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="fw-semibold">صافي الصندوق</span>
                        <span class="font-monospace fw-bold">{{ $fmt($allTime['treasury_net']) }}</span>
                    </div>
                    <p class="text-muted mb-0 mt-2">{{ $currencyLabel }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card app-surface h-100">
                <div class="card-header">
                    <h5 class="mb-0 fw-semibold">اختصارات</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('revenues.index', array_filter(['q' => request('q'), 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}" class="btn btn-outline-primary btn-sm">سجل التحصيلات</a>
                    <a href="{{ route('expenses.index', array_filter(['q' => request('q'), 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}" class="btn btn-outline-primary btn-sm">سجل المصروفات</a>
                    <a href="{{ route('sales.index', array_filter(['q' => request('q'), 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}" class="btn btn-outline-primary btn-sm">المبيعات</a>
                    <a href="{{ route('remaining.index') }}" class="btn btn-outline-secondary btn-sm">كشف المتبقي</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card app-surface h-100">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold">آخر التحصيلات في الفترة</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light"><tr><th>التاريخ</th><th>العميل</th><th class="text-end">المبلغ</th></tr></thead>
                            <tbody>
                            @forelse ($revenueRows as $r)
                                <tr>
                                    <td class="small font-monospace">{{ $r->paid_at?->format('Y-m-d') }}</td>
                                    <td class="small">{{ $r->client?->name ?? '—' }}</td>
                                    <td class="text-end small font-monospace">{{ $fmt((float) $r->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4 small">لا توجد بيانات.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card app-surface h-100">
                <div class="card-header"><h6 class="mb-0 fw-semibold">آخر المصروفات في الفترة</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light"><tr><th>التاريخ</th><th>الفئة</th><th class="text-end">المبلغ</th></tr></thead>
                            <tbody>
                            @forelse ($expenseRows as $e)
                                <tr>
                                    <td class="small font-monospace">{{ $e->created_at?->format('Y-m-d') }}</td>
                                    <td class="small">{{ $e->category }}</td>
                                    <td class="text-end small font-monospace">{{ $fmt((float) $e->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4 small">لا توجد بيانات.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card app-surface h-100">
                <div class="card-header"><h6 class="mb-0 fw-semibold">آخر المبيعات في الفترة</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light"><tr><th>العقار</th><th>العميل</th><th class="text-end">السعر</th></tr></thead>
                            <tbody>
                            @forelse ($saleRows as $s)
                                <tr>
                                    <td class="small text-truncate" style="max-width: 7rem">{{ $s->property?->name ?? '—' }}</td>
                                    <td class="small">{{ $s->client?->name ?? '—' }}</td>
                                    <td class="text-end small font-monospace">{{ $fmt((float) $s->sale_price) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4 small">لا توجد بيانات.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
