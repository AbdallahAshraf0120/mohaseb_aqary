@extends('layouts.admin')

@section('content')
    <x-partials.module-kpis :items="[
        ['label' => 'عدد أنواع المصروفات', 'value' => (int) ($typeCount ?? 0)],
    ]" />

    <x-listing.filters
        :placeholder="'اسم جهة الصرف…'"
        :help="'التصفية حسب تاريخ الإنشاء.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">أنواع المصروفات (جهات الصرف)</h5>
            <a href="{{ route('expense-types.create') }}" class="btn btn-primary btn-sm">إضافة نوع</a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <p class="text-muted small">تُستخدم عند تسجيل مصروف جديد لاختيار جهة الصرف من قائمة جاهزة.</p>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>جهة الصرف</th>
                        <th>الترتيب</th>
                        <th>مصروفات مرتبطة</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($expenseTypes as $expenseType)
                        <tr>
                            <td>{{ $expenseTypes->firstItem() + $loop->index }}</td>
                            <td>{{ $expenseType->name }}</td>
                            <td>{{ $expenseType->sort_order }}</td>
                            <td>{{ $expenseType->expenses_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('expense-types.edit', [$project, $expenseType]) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form method="post" action="{{ route('expense-types.destroy', [$project, $expenseType]) }}" class="d-inline" data-swal-confirm="{{ e('هل تريد حذف نوع المصروف؟') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">لا توجد أنواع مصروفات. أضف نوعًا لتظهر في نموذج المصروف.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $expenseTypes->links() }}</div>
        </div>
    </div>
@endsection
