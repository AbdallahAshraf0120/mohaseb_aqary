@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="المصروفات" step="9" />
    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي المصروفات', 'value' => number_format((float) $expenses->sum('amount')) . ' ج.م'],
        ['label' => 'عدد الحركات', 'value' => $expenses->total()],
        ['label' => 'متوسط الحركة', 'value' => number_format((float) $expenses->avg('amount')) . ' ج.م'],
    ]" />

    <div class="card">
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
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $expense->category }}</td>
                            <td>{{ number_format((float) $expense->amount, 2) }}</td>
                            <td>{{ $expense->description ?: '-' }}</td>
                            <td class="text-end">
                                <form method="post" action="{{ route('expenses.destroy', $expense) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" onclick="return confirm('حذف المصروف؟')">حذف</button>
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
