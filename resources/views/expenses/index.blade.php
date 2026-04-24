@extends('layouts.admin')

@section('content')
    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي المصروفات', 'value' => number_format((float) ($expenseStats['sum_amount'] ?? 0), 2) . ' ج.م'],
        ['label' => 'عدد الحركات', 'value' => (int) ($expenseStats['count'] ?? 0)],
        ['label' => 'متوسط الحركة', 'value' => ($expenseStats['count'] ?? 0) > 0 ? number_format((float) ($expenseStats['avg_amount'] ?? 0), 2) . ' ج.م' : '—'],
    ]" />

    <x-listing.filters
        :placeholder="'فئة، وصف…'"
        :help="'التصفية حسب تاريخ تسجيل المصروف.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">سجل المصروفات</h5>
            <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">إضافة مصروف</a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>#</th><th>الفئة</th><th>القيمة</th><th>الوصف</th><th class="text-end">حذف</th></tr></thead>
                    <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expenses->firstItem() + $loop->index }}</td>
                            <td>{{ $expense->category }}</td>
                            <td>{{ number_format((float) $expense->amount, 2) }}</td>
                            <td>{{ $expense->description ?: '-' }}</td>
                            <td class="text-end">
                                <form method="post" action="{{ route('expenses.destroy', [$project, $expense]) }}" data-swal-confirm="{{ e('حذف المصروف؟') }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">لا توجد مصروفات حتى الآن.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $expenses->links() }}</div>
        </div>
    </div>
@endsection
