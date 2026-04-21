@extends('layouts.admin')

@section('content')
    @php
        $totalSales = (float) $sales->sum('sale_price');
        $totalDownPayment = (float) $sales->sum('down_payment');
        $remaining = max(0, $totalSales - $totalDownPayment);
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1">المبيعات</h4>
                        <p class="text-muted mb-0">إدارة عمليات البيع وربطها بالعملاء وخطط السداد</p>
                    </div>
                    <div class="text-end">
                        <div class="badge text-bg-primary mb-2">الخطوة 6 من 13</div>
                        <div class="small text-muted">Demo Wireflow</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">بيانات المبيعات</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge text-bg-secondary">حالة التحصيل</span>
                        <span class="badge text-bg-secondary">الفترة</span>
                        <span class="badge text-bg-secondary">رقم عملية البيع</span>
                    </div>
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
                                    <td>{{ $loop->iteration }}</td>
                                    <td>SL-{{ str_pad((string) $sale->id, 3, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $sale->property?->name ?? '-' }} / {{ $sale->client?->name ?? '-' }}</td>
                                    <td>{{ $sale->broker_name ?: '—' }}</td>
                                    <td>{{ number_format((float) $sale->sale_price, 2) }} ج.م</td>
                                    <td>{{ number_format((float) $sale->down_payment, 2) }} ج.م</td>
                                    <td class="text-end">
                                        <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                        <a href="{{ route('sales.edit', $sale) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
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
            <div class="card h-100">
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
