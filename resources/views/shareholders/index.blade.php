@extends('layouts.admin')

@section('content')
    <x-partials.module-kpis :items="[
        ['label' => 'عدد المساهمين', 'value' => (int) ($shareholderKpis['count'] ?? 0)],
        ['label' => 'مجموع رأس المال المُدخل (ملف)', 'value' => number_format((float) ($shareholderKpis['total_investment'] ?? 0), 2) . ' ج.م'],
        ['label' => 'مجموع حصة التكاليف (محسوب)', 'value' => number_format((float) ($shareholderKpis['attributed_cost_total'] ?? 0), 2) . ' ج.م'],
        ['label' => 'مجموع المنسب التشغيلي', 'value' => number_format((float) ($shareholderKpis['attributed_operating_total'] ?? 0), 2) . ' ج.م'],
        ['label' => 'مجموع جاري المساهمين (تقريبي)', 'value' => number_format((float) ($shareholderKpis['current_account_total'] ?? 0), 2) . ' ج.م'],
        ['label' => 'إجمالي النسب (ملف)', 'value' => number_format((float) ($shareholderKpis['share_percentage'] ?? 0), 2) . '%'],
    ]" />

    <x-listing.filters
        :placeholder="'اسم المساهم…'"
        :help="'التصفية حسب تاريخ التسجيل.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">قائمة المساهمين</h5>
            <a href="{{ route('shareholders.create', $project) }}" class="btn btn-primary btn-sm">إضافة مساهم</a>
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
                        <th class="text-end">رأس المال <span class="text-muted fw-normal small">(ملف)</span></th>
                        <th class="text-end">حصة التكلفة <span class="text-muted fw-normal small">(محسوب)</span></th>
                        <th class="text-end">المنسب <span class="text-muted fw-normal small">(محسوب)</span></th>
                        <th class="text-end">جاري <span class="text-muted fw-normal small">(تقريبي)</span></th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($shareholders as $shareholder)
                        <tr>
                            <td>{{ $shareholders->firstItem() + $loop->index }}</td>
                            <td>{{ $shareholder->name }}</td>
                            <td>{{ number_format((float) $shareholder->share_percentage, 2) }}%</td>
                            <td class="text-end font-monospace">{{ number_format((float) $shareholder->total_investment, 2) }}</td>
                            <td class="text-end font-monospace small">{{ number_format((float) ($shareholder->attributed_development_cost_share ?? 0), 2) }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) ($shareholder->attributed_operating_flow ?? 0), 2) }}</td>
                            <td class="text-end font-monospace small {{ ($shareholder->shareholder_current_account ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) ($shareholder->shareholder_current_account ?? 0), 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('shareholders.show', [$project, $shareholder]) }}" class="btn btn-outline-info btn-sm">بروفايل</a>
                                <a href="{{ route('shareholders.edit', [$project, $shareholder]) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form action="{{ route('shareholders.destroy', [$project, $shareholder]) }}" method="post" class="d-inline" data-swal-confirm="{{ e('هل تريد حذف هذا المساهم؟') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">لا توجد بيانات مساهمين حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $shareholders->links() }}</div>
        </div>
    </div>
@endsection
