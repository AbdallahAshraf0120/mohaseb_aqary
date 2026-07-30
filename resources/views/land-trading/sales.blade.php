@extends('layouts.admin')

@section('content')
    @php
        $totalSales = (float) ($saleTotals['total_sales'] ?? 0);
        $totalCollected = (float) ($saleTotals['total_collected'] ?? 0);
        $totalRemaining = (float) ($saleTotals['total_remaining'] ?? 0);
        $profit = (float) ($saleTotals['profit'] ?? 0);
    @endphp

    <x-partials.module-kpis :items="[
        ['label' => 'قيمة المبيعات', 'value' => number_format($totalSales, 5) . ' ج.م'],
        ['label' => 'المحصّل', 'value' => number_format($totalCollected, 5) . ' ج.م'],
        ['label' => 'المتبقي', 'value' => number_format($totalRemaining, 5) . ' ج.م'],
        ['label' => 'ربح السعر (بيع − شراء)', 'value' => number_format($profit, 5) . ' ج.م'],
        ['label' => 'عدد البيعات', 'value' => (int) ($saleTotals['count'] ?? 0)],
        ['label' => 'مباعة بالكامل', 'value' => (int) ($saleTotals['sold_count'] ?? 0)],
    ]" />

    <x-listing.filters
        :placeholder="'أرض، مشتري، هاتف، صك…'"
        :help="'تصفية حسب تاريخ البيع. الملخصات أعلاه تعكس نفس الفلاتر.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">مبيعات الأراضي</h5>
                @if (request()->filled('q') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('status') || request()->filled('collection'))
                    <span class="badge text-bg-primary">فلاتر نشطة</span>
                @endif
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('land-trading.index') }}" class="btn btn-outline-secondary btn-sm">كل الأراضي</a>
                @can('cashbox.view')
                    @if (\Illuminate\Support\Facades\Route::has('land-cashbox.index'))
                        <a href="{{ route('land-cashbox.index') }}" class="btn btn-outline-primary btn-sm">صندوق الأراضي</a>
                    @endif
                @endcan
                @can('land-trading.manage')
                    <a href="{{ route('land-trading.create') }}" class="btn btn-primary btn-sm">إضافة أرض</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @php
                    $statusFilters = [
                        '' => 'كل الحالات',
                        'for_sale' => 'للبيع',
                        'reserved' => 'محجوزة',
                        'sold' => 'مباعة',
                    ];
                @endphp
                @foreach ($statusFilters as $key => $label)
                    @php
                        $active = (string) ($status ?? '') === (string) $key;
                        $qs = request()->query();
                        if ($key === '') {
                            unset($qs['status']);
                        } else {
                            $qs['status'] = $key;
                        }
                    @endphp
                    <a href="{{ route('land-trading.sales', $qs) }}"
                       class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach

                @if (!empty($paymentsReady))
                    <span class="vr mx-1 d-none d-md-inline"></span>
                    @php
                        $collectionFilters = [
                            '' => 'كل التحصيل',
                            'remaining' => 'عليه متبقي',
                            'paid' => 'محصّل بالكامل',
                        ];
                    @endphp
                    @foreach ($collectionFilters as $key => $label)
                        @php
                            $active = (string) ($collection ?? '') === (string) $key;
                            $qs = request()->query();
                            if ($key === '') {
                                unset($qs['collection']);
                            } else {
                                $qs['collection'] = $key;
                            }
                        @endphp
                        <a href="{{ route('land-trading.sales', $qs) }}"
                           class="btn btn-sm {{ $active ? 'btn-success' : 'btn-outline-success' }}">{{ $label }}</a>
                    @endforeach
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>الأرض</th>
                        <th>المشتري</th>
                        <th>تاريخ البيع</th>
                        <th>طريقة التحصيل</th>
                        <th class="text-end">سعر البيع</th>
                        <th class="text-end">محصّل</th>
                        <th class="text-end">متبقي</th>
                        <th>الحالة</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($parcels as $parcel)
                        @php
                            $salePrice = (float) ($parcel->sale_price ?? 0);
                            $collected = (float) ($parcel->sale_collected ?? 0);
                            $remaining = round(max(0, $salePrice - $collected), 5);
                            $payType = $parcel->sale_payment_type ?? null;
                            $payLabel = match ($payType) {
                                'installment' => 'أقساط',
                                'cash' => 'كاش',
                                default => '—',
                            };
                            $badge = match ($parcel->status) {
                                'for_sale' => 'text-bg-warning',
                                'reserved' => 'text-bg-primary',
                                'sold' => 'text-bg-success',
                                default => 'text-bg-light',
                            };
                        @endphp
                        <tr>
                            <td>{{ $parcels->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">
                                {{ $parcel->name }}
                                @if ($parcel->location || $parcel->city)
                                    <div class="small text-body-secondary">
                                        {{ collect([$parcel->location, $parcel->city])->filter()->implode(' — ') }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                {{ $parcel->sold_to ?: '—' }}
                                @if ($parcel->sale_phone)
                                    <div class="small font-monospace text-body-secondary">{{ $parcel->sale_phone }}</div>
                                @endif
                            </td>
                            <td class="font-monospace small">{{ optional($parcel->sale_date)->format('Y-m-d') ?: '—' }}</td>
                            <td>{{ $payLabel }}</td>
                            <td class="text-end font-monospace">
                                {{ $salePrice > 0 ? number_format($salePrice, 5) : '—' }}
                            </td>
                            <td class="text-end font-monospace text-success-emphasis">{{ number_format($collected, 5) }}</td>
                            <td class="text-end font-monospace {{ $remaining > 0.00001 ? 'text-danger-emphasis' : 'text-body-secondary' }}">
                                {{ number_format($remaining, 5) }}
                            </td>
                            <td><span class="badge {{ $badge }}">{{ $parcel->statusLabel() }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('land-trading.show', $parcel) }}" class="btn btn-outline-info btn-sm">تحصيل / عرض</a>
                                @can('land-trading.manage')
                                    <a href="{{ route('land-trading.edit', $parcel) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                لا توجد مبيعات أراضٍ بعد. سجّل سعر البيع من تعديل الأرض أو غيّر حالتها إلى «للبيع».
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $parcels->links() }}</div>
        </div>
    </div>
@endsection
