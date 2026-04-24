@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="المديونية" step="10" />
    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي الدين', 'value' => number_format((float) ($debtKpis['total_amount'] ?? 0), 2) . ' ج.م'],
        ['label' => 'المسدَّد', 'value' => number_format((float) ($debtKpis['paid_amount'] ?? 0), 2) . ' ج.م'],
        ['label' => 'المتبقي', 'value' => number_format((float) ($debtKpis['remaining_amount'] ?? 0), 2) . ' ج.م'],
    ]" />

    <x-listing.filters
        :placeholder="'اسم أو هاتف العميل…'"
        :help="'التصفية حسب تاريخ تسجيل المديونية.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header"><h5 class="mb-0">سجل المديونيات</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>#</th><th>العميل</th><th>إجمالي الدين</th><th>المسدَّد</th><th>المتبقي</th><th>الحالة</th></tr></thead>
                    <tbody>
                    @forelse ($debts as $debt)
                        <tr>
                            <td>{{ $debts->firstItem() + $loop->index }}</td>
                            <td>{{ $debt->client?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $debt->total_amount, 2) }}</td>
                            <td>{{ number_format((float) $debt->paid_amount, 2) }}</td>
                            <td>{{ number_format((float) $debt->remaining_amount, 2) }}</td>
                            <td>{{ $debt->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">لا توجد مديونيات مسجلة.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $debts->links() }}</div>
        </div>
    </div>
@endsection
