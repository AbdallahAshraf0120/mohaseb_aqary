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
        <div class="card-header">
            <h5 class="mb-0">مستحقات لموردين / جهات دائنة</h5>
        </div>
        <div class="card-body">
            <p class="small text-body-secondary mb-3">
                تُسجَّل هنا <strong>التزامات المشروع</strong> عند شراء بضاعة أو خدمة للمشروع ولم يُسدَّد ثمنها بالكامل بعد (ذمة دائنة).
                السجلات القديمة المرتبطة بعميل تظهر تحت اسم العميل حتى يُراجع توثيقها.
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">لا توجد ذمم دائنة مسجّلة.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $debts->links() }}</div>
        </div>
    </div>
@endsection
