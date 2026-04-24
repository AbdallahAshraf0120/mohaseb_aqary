@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">الوجهات (لنماذج الشقق)</h5>
            <a href="{{ route('facings.create') }}" class="btn btn-primary btn-sm">إضافة وجهة</a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <p class="text-muted small">تُستخدم في عمود «الواجهة» داخل نماذج الشقق عند إضافة أو تعديل عقار.</p>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>الرمز</th>
                        <th>الاسم</th>
                        <th>الترتيب</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($facings as $facing)
                        <tr>
                            <td>{{ $loop->iteration + ($facings->currentPage() - 1) * $facings->perPage() }}</td>
                            <td><code>{{ $facing->code }}</code></td>
                            <td>{{ $facing->name }}</td>
                            <td>{{ $facing->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('facings.edit', $facing) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form method="post" action="{{ route('facings.destroy', $facing) }}" class="d-inline" data-swal-confirm="{{ e('حذف هذه الوجهة؟ قد تحتاج لتحديث النماذج التي تستخدم الرمز '.$facing->code.'.') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">لا توجد وجهات. أضف وجهة أو أعد تشغيل التهجير الافتراضي للمشروع.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $facings->links() }}</div>
        </div>
    </div>
@endsection
