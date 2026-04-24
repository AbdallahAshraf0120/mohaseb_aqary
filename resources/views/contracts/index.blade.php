@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="العقود" step="5" />
    @php
        $contractsNetValue = (float) $contracts->sum(function ($contract) {
            $downPayment = (float) ($contract->sale?->down_payment ?? 0);
            return max(0, (float) $contract->total_price - $downPayment);
        });
    @endphp
    <x-partials.module-kpis :items="[
        ['label' => 'العقود النشطة', 'value' => $contracts->total()],
        ['label' => 'قيمة العقود بعد المقدم', 'value' => number_format($contractsNetValue) . ' ج.م'],
        ['label' => 'المتبقي', 'value' => number_format((float) $contracts->sum('remaining_amount')) . ' ج.م'],
    ]" />

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
                            <td>{{ $loop->iteration }}</td>
                            <td>CT-{{ now()->format('Y') }}-{{ str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $contract->client?->name ?? '-' }}</td>
                            <td>{{ $contract->property?->name ?? '-' }}</td>
                            @php($netContractValue = max(0, (float) $contract->total_price - (float) ($contract->sale?->down_payment ?? 0)))
                            <td>{{ number_format($netContractValue, 2) }}</td>
                            <td>{{ number_format((float) $contract->remaining_amount, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-info btn-sm">عرض</a>
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
