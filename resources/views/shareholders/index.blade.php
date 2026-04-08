@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="المساهمين" step="2" />
    <x-partials.module-kpis :items="[
        ['label' => 'عدد المساهمين', 'value' => $shareholders->total()],
        ['label' => 'رأس المال', 'value' => number_format((float) $shareholders->sum('total_investment')) . ' ج.م'],
        ['label' => 'إجمالي النسب', 'value' => number_format((float) $shareholders->sum('share_percentage'), 2) . '%'],
        ['label' => 'الأرباح', 'value' => number_format((float) $shareholders->sum('profit_amount')) . ' ج.م'],
    ]" />

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">قائمة المساهمين</h5>
            <a href="{{ route('shareholders.create') }}" class="btn btn-primary btn-sm">إضافة مساهم</a>
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
                        <th>اسم المساهم</th>
                        <th>نسبة المساهمة</th>
                        <th>رأس المال</th>
                        <th>الأرباح</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($shareholders as $shareholder)
                        <tr>
                            <td>{{ $shareholder->id }}</td>
                            <td>{{ $shareholder->name }}</td>
                            <td>{{ number_format((float) $shareholder->share_percentage, 2) }}%</td>
                            <td>{{ number_format((float) $shareholder->total_investment, 2) }}</td>
                            <td>{{ number_format((float) $shareholder->profit_amount, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('shareholders.show', $shareholder) }}" class="btn btn-outline-info btn-sm">بروفايل</a>
                                <a href="{{ route('shareholders.edit', $shareholder) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form action="{{ route('shareholders.destroy', $shareholder) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('هل تريد حذف هذا المساهم؟')">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">لا توجد بيانات مساهمين حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $shareholders->links() }}</div>
        </div>
    </div>
@endsection
