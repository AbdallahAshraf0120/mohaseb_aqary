@extends('layouts.admin')

@section('content')
    @php
        $totalSales = (float) ($saleTotals['total_sales'] ?? 0);
        $totalCollected = (float) ($saleTotals['total_collected'] ?? 0);
        $totalDownPayments = (float) ($saleTotals['total_down_payments'] ?? 0);
        $totalInstallments = (float) ($saleTotals['total_installments'] ?? 0);
        $remaining = round(max(0, $totalSales - $totalCollected), 2);
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-lg-4 col-md-6">
            <div class="small-box text-bg-light border">
                <div class="inner">
                    <h5 class="mb-2">{{ number_format($totalSales, 2) }} ج.م</h5>
                    <p class="mb-0">المبيعات الكلية</p>
                    <p class="mb-0 small text-body-secondary">المعتمدة فقط</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box text-bg-light border">
                <div class="inner">
                    <h5 class="mb-2">{{ number_format($totalCollected, 2) }} ج.م</h5>
                    <p class="mb-0">الدفعات المحصلة</p>
                    <p class="mb-0 small text-body-secondary">
                        مقدمات {{ number_format($totalDownPayments, 2) }}
                        + أقساط {{ number_format($totalInstallments, 2) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box text-bg-light border">
                <div class="inner">
                    <h5 class="mb-2">{{ number_format($remaining, 2) }} ج.م</h5>
                    <p class="mb-0">المتبقي من المبيعات</p>
                </div>
            </div>
        </div>
    </div>

    <x-listing.filters
        :placeholder="'عميل، هاتف، عقار، بروكر…'"
        :help="'تصفية حسب تاريخ البيعة (عمود تاريخ البيع). الملخصات أعلاه تعكس نفس الفلاتر.'"
    />

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card app-surface h-100">
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
                                        <div>{{ $sale->property?->name ?? '-' }} / {{ $sale->client?->name ?? '-' }}</div>
                                        @if (filled($sale->broker_name))
                                            <div class="small text-body-secondary">البروكر: {{ $sale->broker_name }}</div>
                                        @endif
                                    </td>
                                    <td>{{ number_format((float) $sale->sale_price, 2) }} ج.م</td>
                                    <td>{{ number_format((float) $sale->down_payment, 2) }} ج.م</td>
                                    <td>
                                        @if (($sale->approval_status ?? 'approved') === 'approved')
                                            <span class="badge text-bg-success">معتمد</span>
                                        @elseif (($sale->approval_status ?? '') === 'pending')
                                            <span class="badge text-bg-warning">معلق</span>
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
                                    <td colspan="8" class="text-center text-muted">لا توجد مبيعات مسجلة حتى الآن.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>{{ $sales->links() }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card app-surface h-100">
                <div class="card-header">
                    <h5 class="mb-0">اجراءات سريعة</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('sales.create') }}" class="btn btn-outline-secondary text-start">تسجيل بيع</a>
                    <a href="{{ route('sales.create') }}" class="btn btn-outline-secondary text-start">جدولة اقساط</a>
                    <a href="{{ route('sales.create') }}" class="btn btn-outline-secondary text-start">توليد إيصال مقدم</a>
                    <hr>
                    <a href="{{ route('revenues.index') }}" class="btn btn-primary">الانتقال إلى التحصيل</a>
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
