@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="ذمم دائنة على المشروع" step="10" />
    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي أصل الشراء', 'value' => number_format((float) ($debtKpis['total_amount'] ?? 0), 2) . ' ج.م'],
        ['label' => 'ما سُدِّد للمورد', 'value' => number_format((float) ($debtKpis['paid_amount'] ?? 0), 2) . ' ج.م'],
        ['label' => 'المتبقي على المشروع', 'value' => number_format((float) ($debtKpis['remaining_amount'] ?? 0), 2) . ' ج.م'],
    ]" />

    <x-listing.filters
        :placeholder="'اسم المورد أو وصف الشراء…'"
        :help="'التصفية حسب تاريخ تسجيل الذمة.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">مستحقات لموردين / جهات دائنة</h5>
            <a href="{{ route('debts.create', $project) }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus ms-1"></i> إضافة مورد / ذمة
            </a>
        </div>
        <div class="card-body">
            <p class="small text-body-secondary mb-3">
                من هنا تضيف <strong>مورداً وذمة دائنة</strong>: المسار <code>المشروع الحالي → ذمم دائنة → إضافة مورد / ذمة</code>، أو زر «+» بجانب «ذمم دائنة» في القائمة الجانبية تحت اسم المشروع.
                السجلات القديمة المرتبطة بعميل تظهر تحت اسم العميل حتى تُحدَّث باسم مورد صريح.
            </p>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>المورد / الجهة الدائنة</th>
                        <th>وصف الشراء</th>
                        <th class="text-end">إجمالي الشراء</th>
                        <th class="text-end">المسدَّد</th>
                        <th class="text-end">المتبقي</th>
                        <th>الحالة</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($debts as $debt)
                        <tr>
                            <td>{{ $debts->firstItem() + $loop->index }}</td>
                            <td class="fw-medium">{{ $debt->counterpartyLabel() }}</td>
                            <td class="small text-muted">{{ $debt->purchase_description ?? '—' }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) $debt->total_amount, 2) }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) $debt->paid_amount, 2) }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) $debt->remaining_amount, 2) }}</td>
                            <td>{{ $debt->status }}</td>
                            <td class="text-end text-nowrap">
                                @if ((float) $debt->remaining_amount > 0.009)
                                    <a href="{{ route('debts.edit', [$project, $debt]) }}#pay-from-cashbox" class="btn btn-outline-success btn-sm">سداد من الصندوق</a>
                                @endif
                                <a href="{{ route('debts.edit', [$project, $debt]) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form action="{{ route('debts.destroy', [$project, $debt]) }}" method="post" class="d-inline" data-swal-confirm="{{ e('هل تريد حذف هذا السجل؟') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                لا توجد ذمم دائنة مسجّلة.
                                <a href="{{ route('debts.create', $project) }}" class="fw-semibold">إضافة أول ذمة</a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $debts->links() }}</div>
        </div>
    </div>
@endsection
