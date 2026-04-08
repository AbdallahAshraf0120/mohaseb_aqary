@extends('layouts.admin')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($openingBalance, 2) }}</h5><p>رصيد افتتاحي</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($revenuesTotal, 2) }}</h5><p>مقبوضات</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($expensesTotal, 2) }}</h5><p>مدفوعات</p></div></div></div>
        <div class="col-md-3"><div class="small-box text-bg-light border"><div class="inner"><h5>{{ number_format($currentBalance, 2) }}</h5><p>الرصيد الحالي</p></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">حركات الصندوق</h5></div>
                <div class="card-body">
                    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
                    <table class="table table-striped align-middle">
                        <thead><tr><th>#</th><th>النوع</th><th>القيمة</th><th>الوصف</th><th>التاريخ</th></tr></thead>
                        <tbody>
                        @forelse ($transactions as $tx)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $tx->type === 'revenue' ? 'قبض' : 'صرف' }}</td>
                                <td>{{ number_format((float) $tx->amount, 2) }}</td>
                                <td>{{ $tx->description ?: '-' }}</td>
                                <td>{{ $tx->created_at?->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">لا توجد حركات يدويّة حتى الآن.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div>{{ $transactions->links() }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">حركة جديدة</h5></div>
                <div class="card-body">
                    <form method="post" action="{{ route('cashbox.store') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">النوع</label>
                            <select name="type" class="form-select">
                                <option value="revenue">قبض</option>
                                <option value="expense">صرف</option>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">القيمة</label><input type="number" step="0.01" min="1" name="amount" class="form-control" required></div>
                        <div class="mb-2"><label class="form-label">الوصف</label><input name="description" class="form-control"></div>
                        <button class="btn btn-primary w-100">حفظ الحركة</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
