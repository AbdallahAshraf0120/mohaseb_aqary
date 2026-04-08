@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">كشف المتبقي على العقود</h5></div>
        <div class="card-body">
            <table class="table table-striped align-middle">
                <thead><tr><th>#</th><th>العقد</th><th>العميل</th><th>العقار</th><th>المتبقي</th></tr></thead>
                <tbody>
                @forelse ($contracts as $contract)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>CT-{{ now()->format('Y') }}-{{ str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $contract->client?->name ?? '-' }}</td>
                        <td>{{ $contract->property?->name ?? '-' }}</td>
                        <td>{{ number_format((float) $contract->remaining_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">لا يوجد متبقي على العقود حاليًا.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div>{{ $contracts->links() }}</div>
        </div>
    </div>
@endsection
