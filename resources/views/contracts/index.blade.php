@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="العقود" step="5" />
    <x-partials.module-kpis :items="[
        ['label' => 'العقود النشطة', 'value' => $contracts->total()],
        ['label' => 'قيمة العقود', 'value' => number_format((float) $contracts->sum('total_price')) . ' ج.م'],
        ['label' => 'المتبقي', 'value' => number_format((float) $contracts->sum('remaining_amount')) . ' ج.م'],
    ]" />

    <div class="card">
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
                        <th>قيمة العقد</th>
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
                            <td>{{ number_format((float) $contract->total_price, 2) }}</td>
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
