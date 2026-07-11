@extends('layouts.admin')

@section('content')
    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي المصروفات', 'value' => number_format((float) ($expenseStats['sum_amount'] ?? 0), 2) . ' ج.م'],
        ['label' => 'عدد الحركات', 'value' => (int) ($expenseStats['count'] ?? 0)],
        ['label' => 'متوسط الحركة', 'value' => ($expenseStats['count'] ?? 0) > 0 ? number_format((float) ($expenseStats['avg_amount'] ?? 0), 2) . ' ج.م' : '—'],
    ]" />

    <x-listing.filters
        :placeholder="'فئة، وصف…'"
        :help="'التصفية حسب تاريخ الصرف (وليس تاريخ الإدخال على النظام).'"
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
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>تاريخ الصرف</th>
                        <th>تاريخ الإدخال</th>
                        <th>الفئة</th>
                        <th>القيمة</th>
                        <th>الوصف</th>
                        <th>الحالة</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expenses->firstItem() + $loop->index }}</td>
                            <td class="font-monospace">{{ $expense->spent_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="font-monospace small text-body-secondary">{{ $expense->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>{{ $expense->category }}</td>
                            <td>{{ number_format((float) $expense->amount, 2) }}</td>
                            <td>{{ $expense->description ?: '-' }}</td>
                            <td>
                                @if (($expense->approval_status ?? 'approved') === 'approved')
                                    <span class="badge text-bg-success">معتمد</span>
                                @elseif (($expense->approval_status ?? '') === 'pending')
                                    <span class="badge text-bg-warning">معلق</span>
                                @else
                                    <span class="badge text-bg-secondary">مرفوض</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('expenses.show', [$project, $expense]) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                <a href="{{ route('expenses.edit', [$project, $expense]) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form method="post"
                                      action="{{ route('expenses.destroy', [$project, $expense]) }}"
                                      class="d-inline js-expense-ajax-delete"
                                      data-confirm="حذف المصروف؟">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">لا توجد مصروفات حتى الآن.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $expenses->links() }}</div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('partials.ajax-delete-script', [
        'ajaxDeleteClass' => 'js-expense-ajax-delete',
        'ajaxEmptyColspan' => 8,
        'ajaxEmptyMessage' => 'لا توجد مصروفات حتى الآن.',
    ])
@endpush
