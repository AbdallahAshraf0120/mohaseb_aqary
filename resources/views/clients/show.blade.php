@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تفاصيل العميل</h5>
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>الاسم:</strong> {{ $client->name }}</div>
                <div class="col-md-6"><strong>الهاتف:</strong> {{ $client->phone }}</div>
                <div class="col-md-6"><strong>البريد الإلكتروني:</strong> {{ $client->email ?: '-' }}</div>
                <div class="col-md-6"><strong>الرقم القومي:</strong> {{ $client->national_id ?: '-' }}</div>
            </div>

            <hr>
            <h6>مبيعات العميل</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>العقار</th>
                        <th>السعر</th>
                        <th>نوع السداد</th>
                        <th>البروكر</th>
                        <th>تاريخ البيعة</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($client->sales as $sale)
                        <tr>
                            <td>{{ $sale->id }}</td>
                            <td>{{ $sale->property?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $sale->sale_price, 2) }}</td>
                            <td>{{ $sale->payment_type === 'cash' ? 'كاش' : 'تقسيط' }}</td>
                            <td>{{ $sale->broker_name ?: '—' }}</td>
                            <td>{{ $sale->sale_date?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">لا توجد مبيعات لهذا العميل.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
