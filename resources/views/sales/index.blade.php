@extends('layouts.admin')

@section('content')
    @php
        $totalSales = (float) ($saleTotals['total_sales'] ?? 0);
        $totalDownPayment = (float) ($saleTotals['total_down_payment'] ?? 0);
        $remaining = max(0, $totalSales - $totalDownPayment);
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-lg-4 col-md-6">
            <div class="small-box text-bg-light border">
                <div class="inner">
                    <h5 class="mb-2">{{ number_format($totalSales, 2) }} ج.م</h5>
                    <p class="mb-0">المبيعات الكلية</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box text-bg-light border">
                <div class="inner">
                    <h5 class="mb-2">{{ number_format($totalDownPayment, 2) }} ج.م</h5>
                    <p class="mb-0">الدفعات المحصلة</p>
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
                                <th>العقار/العميل</th>
                                <th>البروكر</th>
                                <th>قيمة البيع</th>
                                <th>المقدم</th>
                                <th class="text-end">العمليات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($sales as $sale)
                                <tr>
                                    <td>{{ $sales->firstItem() + $loop->index }}</td>
                                    <td>SL-{{ str_pad((string) $sale->id, 3, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $sale->property?->name ?? '-' }} / {{ $sale->client?->name ?? '-' }}</td>
                                    <td>{{ $sale->broker_name ?: '—' }}</td>
                                    <td>{{ number_format((float) $sale->sale_price, 2) }} ج.م</td>
                                    <td>{{ number_format((float) $sale->down_payment, 2) }} ج.م</td>
                                    <td class="text-end">
                                        <a href="{{ route('sales.show', [$project, $sale]) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                        <a href="{{ route('sales.edit', [$project, $sale]) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">لا توجد مبيعات مسجلة حتى الآن.</td>
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
