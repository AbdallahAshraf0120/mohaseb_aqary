@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="المناطق" step="3" />
    <x-partials.module-kpis :items="[
        ['label' => 'عدد المناطق', 'value' => $areas->total()],
        ['label' => 'عقارات مرتبطة', 'value' => $areas->sum('properties_count')],
    ]" />

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">قائمة المناطق</h5>
            <a href="{{ route('areas.create') }}" class="btn btn-primary btn-sm">إضافة منطقة</a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>#</th><th>اسم المنطقة</th><th>عدد العقارات</th><th class="text-end">العمليات</th></tr></thead>
                    <tbody>
                    @forelse ($areas as $area)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $area->name }}</td>
                            <td>{{ $area->properties_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('areas.edit', $area) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form method="post" action="{{ route('areas.destroy', $area) }}" class="d-inline" data-swal-confirm="{{ e('هل تريد حذف المنطقة؟') }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">لا توجد مناطق حتى الآن.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $areas->links() }}</div>
        </div>
    </div>
@endsection
