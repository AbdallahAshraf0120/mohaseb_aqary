@extends('layouts.admin')

@section('content')
    <x-partials.module-kpis :items="[
        ['label' => 'العقود (بعد الفلتر)', 'value' => (int) ($contractKpis['count'] ?? 0)],
        ['label' => 'قيمة العقود بعد المقدم', 'value' => \App\Support\Money::display((float) ($contractKpis['net_value'] ?? 0)) . ' ج.م'],
        ['label' => 'المتبقي', 'value' => \App\Support\Money::display((float) ($contractKpis['remaining'] ?? 0)) . ' ج.م'],
    ]" />

    <x-listing.filters
        :placeholder="'عميل، هاتف، عقار…'"
        :help="'التصفية حسب تاريخ إنشاء سجل العقد.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header">
            <h5 class="mb-0">قائمة العقود</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم العقد</th>
                        <th>العميل</th>
                        <th>العقار</th>
                        <th>قيمة العقد (بعد المقدم)</th>
                        <th>المتبقي</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td>{{ $contracts->firstItem() + $loop->index }}</td>
                            <td>CT-{{ now()->format('Y') }}-{{ str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $contract->client?->name ?? '-' }}</td>
                            <td>{{ $contract->property?->name ?? '-' }}</td>
                            @php($netContractValue = max(0, (float) $contract->total_price - (float) ($contract->sale?->down_payment ?? 0)))
                            <td>{{ \App\Support\Money::display($netContractValue) }}</td>
                            <td>{{ \App\Support\Money::display((float) $contract->remaining_amount) }}</td>
                            <td class="text-end">
                                <a href="{{ route('contracts.show', [$project, $contract]) }}" class="btn btn-outline-info btn-sm">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">لا توجد عقود مسجلة حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $contracts->links() }}</div>
        </div>
    </div>
@endsection
