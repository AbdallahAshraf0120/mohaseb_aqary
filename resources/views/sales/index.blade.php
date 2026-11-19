@extends('layouts.admin')

@section('content')
    @php
        $totalSales = (float) ($saleTotals['total_sales'] ?? 0);
        $totalCollected = (float) ($saleTotals['total_collected'] ?? 0);
        $totalDownPayments = (float) ($saleTotals['total_down_payments'] ?? 0);
        $totalInstallments = (float) ($saleTotals['total_installments'] ?? 0);
        $remaining = round(max(0, $totalSales - $totalCollected), 5);
    @endphp

    @section('page-actions')
        @can('sales.manage')
            <a href="{{ route('sales.create', $project) }}" class="btn btn-primary"><i class="fa-solid fa-plus ms-1"></i> تسجيل بيعة</a>
            <a href="{{ route('reservations.create', $project) }}" class="btn btn-light"><i class="fa-solid fa-bookmark ms-1"></i> حجز وحدة</a>
        @endcan
    @endsection

    <x-partials.module-kpis :items="[
        ['label' => 'المبيعات المعتمدة', 'value' => \App\Support\Money::display($totalSales).' ج.م', 'icon' => 'fa-file-contract'],
        ['label' => 'المحصّل', 'value' => \App\Support\Money::display($totalCollected).' ج.م', 'icon' => 'fa-coins', 'tone' => 'success',
            'hint' => 'مقدمات '.\App\Support\Money::display($totalDownPayments).' + أقساط '.\App\Support\Money::display($totalInstallments)],
        ['label' => 'المتبقي على العملاء', 'value' => \App\Support\Money::display($remaining).' ج.م', 'icon' => 'fa-hourglass-half', 'tone' => 'warning', 'url' => route('remaining.due', $project)],
    ]" />

    <x-listing.filters
        :placeholder="'عميل، هاتف، عقار، بروكر…'"
        :help="'تصفية حسب تاريخ البيعة (عمود تاريخ البيع). الملخصات أعلاه تعكس نفس الفلاتر.'"
    />

    <div>
        <div>
            <div class="card app-surface">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">بيانات المبيعات</h5>
                    @if (request()->filled('q') || request()->filled('date_from') || request()->filled('date_to'))
                        <span class="badge text-bg-primary">فلاتر نشطة</span>
                    @endif
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم البيعة</th>
                                <th>تاريخ البيعة</th>
                                <th>العقار/العميل</th>
                                <th>قيمة البيع</th>
                                <th>المقدم</th>
                                <th>دخل حساب</th>
                                <th>الحالة</th>
                                <th class="text-end">العمليات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($sales as $sale)
                                <tr>
                                    <td>{{ $sales->firstItem() + $loop->index }}</td>
                                    <td>SL-{{ str_pad((string) $sale->id, 3, '0', STR_PAD_LEFT) }}</td>
                                    <td class="font-monospace">{{ $sale->sale_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td>
                                        <div>{{ $sale->property?->name ?? '-' }}@if ($sale->unit_number) <span class="text-body-secondary small">(د{{ $sale->floor_number }} · {{ (int) $sale->floor_number === 0 && ! $sale->is_mezzanine ? 'محل' : 'شقة' }} {{ $sale->unit_number }})</span>@endif / {{ $sale->client?->name ?? '-' }}</div>
                                        @if (filled($sale->broker_name))
                                            <div class="small text-body-secondary">البروكر: {{ $sale->broker_name }}</div>
                                        @endif
                                    </td>
                                    <td>{{ \App\Support\Money::display((float) $sale->sale_price) }} ج.م</td>
                                    <td>
                                        <div class="font-monospace">{{ \App\Support\Money::display((float) $sale->down_payment) }} ج.م</div>
                                        @if ($sale->shareholderDownPaymentAmount() >= 0.00001 || $sale->cashboxDownPaymentAmount() + 0.000009 < (float) $sale->down_payment)
                                            <div class="small text-body-secondary">
                                                صندوق {{ \App\Support\Money::display($sale->cashboxDownPaymentAmount()) }}
                                                · مساهم {{ \App\Support\Money::display($sale->shareholderDownPaymentAmount()) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $sale->receivedByShareholder?->name ?? '—' }}</td>
                                    <td>
                                        @if (($sale->approval_status ?? 'approved') === 'approved')
                                            <span class="badge text-bg-success">معتمد</span>
                                        @elseif (($sale->approval_status ?? '') === 'pending')
                                            <span class="badge text-bg-warning">معلق</span>
                                        @elseif (($sale->approval_status ?? '') === 'cancelled')
                                            <span class="badge text-bg-danger">ملغي</span>
                                        @else
                                            <span class="badge text-bg-secondary">مرفوض</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('sales.show', [$project, $sale]) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                        <a href="{{ route('sales.edit', [$project, $sale]) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                        <form method="post"
                                              action="{{ route('sales.destroy', [$project, $sale]) }}"
                                              class="d-inline js-sale-ajax-delete"
                                              data-confirm="حذف البيعة؟">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">لا توجد مبيعات مسجلة حتى الآن.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>{{ $sales->links() }}</div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    @include('partials.ajax-delete-script', [
        'ajaxDeleteClass' => 'js-sale-ajax-delete',
        'ajaxEmptyColspan' => 8,
        'ajaxEmptyMessage' => 'لا توجد مبيعات مسجلة حتى الآن.',
    ])
@endpush
