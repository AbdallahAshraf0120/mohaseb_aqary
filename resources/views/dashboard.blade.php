@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => \App\Support\Money::display($n);
        $currencyLabel = strtoupper((string) $currency) === 'EGP' ? 'ج.م' : $currency;
    @endphp

    @section('page-actions')
        @can('sales.manage')
            <a href="{{ route('sales.create', $project) }}" class="btn btn-primary"><i class="fa-solid fa-plus ms-1"></i> تسجيل بيعة</a>
        @endcan
        @can('revenues.manage')
            <a href="{{ route('revenues.create', $project) }}" class="btn btn-outline-primary"><i class="fa-solid fa-hand-holding-dollar ms-1"></i> تحصيل دفعة</a>
        @endcan
        @can('expenses.manage')
            <a href="{{ route('expenses.create', $project) }}" class="btn btn-light"><i class="fa-solid fa-money-bill-wave ms-1"></i> مصروف</a>
        @endcan
    @endsection

    <p class="text-body-secondary small mb-3">{{ now()->locale('ar')->translatedFormat('l j F Y') }}</p>

    <x-partials.module-kpis :items="[
        ['label' => 'رصيد الصندوق', 'value' => $fmt($balance).' ج.م', 'icon' => 'fa-vault', 'tone' => $balance < 0 ? 'danger' : 'primary', 'url' => route('cashbox.index', $project)],
        ['label' => 'إجمالي الوارد', 'value' => $fmt($treasuryIn).' ج.م', 'icon' => 'fa-arrow-trend-up', 'tone' => 'success', 'url' => route('cashbox.index', $project),
            'hint' => (($transfersIn ?? 0) > 0.009 ? '+ تحويلات '.$fmt($transfersIn).' ' : '').(($shareholderIn ?? 0) > 0.009 ? '+ إيداعات شركاء '.$fmt($shareholderIn) : '') ?: null],
        ['label' => 'إجمالي المصروف', 'value' => $fmt($treasuryOut).' ج.م', 'icon' => 'fa-arrow-trend-down', 'tone' => 'danger', 'url' => route('cashbox.index', $project),
            'hint' => (($transfersOut ?? 0) > 0.009 ? '+ تحويلات '.$fmt($transfersOut).' ' : '').(($shareholderOut ?? 0) > 0.009 ? '+ مسحوبات شركاء '.$fmt($shareholderOut) : '') ?: null],
        ['label' => 'المتبقي على العملاء', 'value' => $fmt($stats['remaining_total']).' ج.م', 'icon' => 'fa-hourglass-half', 'tone' => 'warning', 'url' => route('remaining.index', $project),
            'hint' => $stats['contracts_with_balance'].' عقد عليه متبقي'],
    ]" />

    <div class="card app-surface mb-4">
        <div class="card-header"><h5>أرقام المشروع</h5></div>
        <div class="card-body">
            <div class="row g-2 g-md-3">
                @foreach ([
                    ['عقارات', $stats['properties'], 'fa-building', route('properties.index', $project)],
                    ['عملاء', $stats['clients'], 'fa-users', route('clients.index', $project)],
                    ['مبيعات', $stats['sales'], 'fa-file-contract', route('sales.index', $project)],
                    ['عقود عليها متبقي', $stats['contracts_with_balance'].' / '.$stats['contracts_total'], 'fa-file-signature', route('contracts.index', $project)],
                    ['ذمم موردين مفتوحة', $stats['debts_open'], 'fa-scale-balanced', route('debts.index', $project)],
                    ['تحصيل الشهر', $fmt($stats['revenues_this_month']), 'fa-coins', route('revenues.index', $project)],
                    ['مناطق', $stats['areas'], 'fa-location-dot', route('areas.index', $project)],
                    ['مساهمين', $stats['shareholders'], 'fa-people-group', route('shareholders.index', ['project_id' => $project->id])],
                ] as [$label, $val, $icon, $url])
                    <div class="col-6 col-md-3">
                        <a href="{{ $url }}" class="dashboard-stat-tile text-decoration-none text-reset h-100">
                            <span class="tile-icon" style="background:var(--ma-surface-2);color:var(--ma-muted)"><i class="fa-solid {{ $icon }}"></i></span>
                            <div class="min-w-0">
                                <div class="small text-body-secondary">{{ $label }}</div>
                                <div class="fs-6 fw-bold ma-num text-truncate">{{ $val }}</div>
                            </div>
                        </a>
                    </div>
                @endforeach
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

@endsection
