@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">سجل المديونيات</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>#</th><th>العميل</th><th>إجمالي الدين</th><th>المسدَّد</th><th>المتبقي</th><th>الحالة</th></tr></thead>
                    <tbody>
                    @forelse ($debts as $debt)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $debt->client?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $debt->total_amount, 2) }}</td>
                            <td>{{ number_format((float) $debt->paid_amount, 2) }}</td>
                            <td>{{ number_format((float) $debt->remaining_amount, 2) }}</td>
                            <td>{{ $debt->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">لا توجد مديونيات مسجلة.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $debts->links() }}</div>
        </div>
    </div>
@endsection
