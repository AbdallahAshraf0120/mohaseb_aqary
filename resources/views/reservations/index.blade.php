@extends('layouts.admin')

@section('content')
    @php $m = fn ($v) => \App\Support\Money::display((float) $v); @endphp
    <x-partials.module-kpis :items="[
        ['label' => 'حجوزات سارية', 'value' => (int) $kpis['active']],
        ['label' => 'عرابين (أمانة)', 'value' => $m($kpis['deposits']) . ' ج.م'],
        ['label' => 'انتهت مدتها', 'value' => (int) $kpis['expired']],
    ]" />
    <div class="card app-surface mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <ul class="nav nav-pills gap-1">
                @foreach (['active' => 'سارية', 'expired' => 'انتهت مدتها', 'done' => 'اتحوّلت / اتلغت', 'all' => 'الكل'] as $k => $l)
                    <li class="nav-item"><a class="nav-link py-1 px-3 {{ $status === $k ? 'active' : '' }}" href="{{ route('reservations.index', [$project, 'status' => $k]) }}">{{ $l }}</a></li>
                @endforeach
            </ul>
            @can('sales.create')
                <a href="{{ route('reservations.create', $project) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus ms-1"></i> حجز جديد</a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>العميل</th><th>الوحدة</th><th class="text-end">العربون</th><th>من</th><th>لحد</th><th>الحالة</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    @forelse ($reservations as $r)
                        <tr>
                            <td>{{ $r->client?->name }}<div class="small text-body-secondary font-monospace">{{ $r->client?->phone }}</div></td>
                            <td>{{ $r->property?->name }}<div class="small text-body-secondary">{{ $r->unitLabel() }}</div></td>
                            <td class="text-end font-monospace">{{ $m($r->deposit) }}<div class="small text-body-secondary">{{ $r->receiptNumber() }}</div></td>
                            <td class="font-monospace">{{ $r->reserved_at?->format('Y-m-d') }}</td>
                            <td class="font-monospace {{ $r->isExpired() ? 'text-danger' : '' }}">{{ $r->expires_at?->format('Y-m-d') }}</td>
                            <td><span class="badge {{ $r->status === 'active' && ! $r->isExpired() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $r->statusLabel() }}</span>
                                @if ($r->sale_id)<a href="{{ route('sales.show', [$project, $r->sale_id]) }}" class="small">SL-{{ $r->sale_id }}</a>@endif</td>
                            <td class="text-end text-nowrap">
                                @if ($r->status === 'active')
                                    @unless ($r->isExpired())
                                        <a href="{{ route('sales.create', [$project, 'reservation_id' => $r->id]) }}" class="btn btn-outline-primary btn-sm">حوّل لبيعة</a>
                                    @endunless
                                    <form method="post" action="{{ route('reservations.cancel', [$project, $r]) }}" class="d-inline">
                                        @csrf
                                        <button name="deposit_outcome" value="refunded" class="btn btn-outline-secondary btn-sm" onclick="return confirm('إلغاء الحجز ورد العربون للعميل؟')">إلغاء ورد العربون</button>
                                        @if (auth()->user()?->isAdmin() && (float) $r->deposit > 0)
                                            <button name="deposit_outcome" value="forfeited" class="btn btn-outline-danger btn-sm" onclick="return confirm('إلغاء الحجز ومصادرة العربون؟')">مصادرة العربون</button>
                                        @endif
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">مفيش حجوزات هنا.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $reservations->links() }}
        </div>
    </div>
@endsection
