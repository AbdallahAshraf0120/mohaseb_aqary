@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="المتبقي" step="8" />
    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي المتبقي', 'value' => number_format((float) ($remainingKpis['remaining'] ?? 0), 2) . ' ج.م'],
        ['label' => 'عدد العقود', 'value' => (int) ($remainingKpis['count'] ?? 0)],
    ]" />

    <x-listing.filters
        :placeholder="'عميل، عقار…'"
        :help="'التصفية حسب تاريخ إنشاء العقد. تُعرض العقود ذات المتبقي فقط.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header"><h5 class="mb-0">كشف المتبقي على العقود</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>#</th><th>العقد</th><th>العميل</th><th>العقار</th><th>المتبقي</th><th class="text-end">عرض</th></tr></thead>
                    <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td>{{ $contracts->firstItem() + $loop->index }}</td>
                            <td>CT-{{ now()->format('Y') }}-{{ str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $contract->client?->name ?? '-' }}</td>
                            <td>{{ $contract->property?->name ?? '-' }}</td>
                            <td class="font-monospace">{{ number_format((float) $contract->remaining_amount, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('contracts.show', [$project, $contract]) }}" class="btn btn-outline-info btn-sm">العقد</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">لا يوجد متبقي على العقود حاليًا.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $contracts->links() }}</div>
        </div>
    </div>
@endsection
