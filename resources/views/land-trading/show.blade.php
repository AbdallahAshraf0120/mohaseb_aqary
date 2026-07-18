@extends('layouts.admin')

@section('content')
    @php
        $badge = match ($parcel->status) {
            'owned' => 'text-bg-info',
            'for_sale' => 'text-bg-warning',
            'reserved' => 'text-bg-primary',
            'sold' => 'text-bg-success',
            'cancelled' => 'text-bg-secondary',
            default => 'text-bg-light',
        };
        $profit = $parcel->profit();
    @endphp

    <div class="card app-surface mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ $parcel->name }}</h5>
                <div class="small text-body-secondary">
                    <span class="badge {{ $badge }}">{{ $parcel->statusLabel() }}</span>
                    @if ($parcel->deed_number)
                        <span class="ms-2">الصك: <span class="font-monospace">{{ $parcel->deed_number }}</span></span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @can('land-trading.manage')
                    <a href="{{ route('land-trading.edit', $parcel) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                @endcan
                <a href="{{ route('land-trading.index') }}" class="btn btn-outline-secondary btn-sm">القائمة</a>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-body-secondary">الموقع</div>
                    <div class="fw-semibold">{{ $parcel->location ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-body-secondary">المدينة</div>
                    <div class="fw-semibold">{{ $parcel->city ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-body-secondary">المساحة</div>
                    <div class="fw-semibold">
                        @if ($parcel->area_size !== null)
                            <span class="font-monospace">{{ number_format((float) $parcel->area_size, 2) }}</span> م²
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card app-surface h-100">
                <div class="card-header"><h6 class="mb-0">الشراء</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="small text-body-secondary">سعر الشراء</div>
                            <div class="fw-semibold font-monospace">{{ number_format((float) $parcel->purchase_price, 2) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">تاريخ الشراء</div>
                            <div class="fw-semibold font-monospace">{{ optional($parcel->purchase_date)->format('Y-m-d') ?: '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">البائع</div>
                            <div class="fw-semibold">{{ $parcel->purchased_from ?: '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">هاتف البائع</div>
                            <div class="fw-semibold font-monospace">{{ $parcel->purchase_phone ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card app-surface h-100">
                <div class="card-header"><h6 class="mb-0">البيع</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="small text-body-secondary">سعر البيع</div>
                            <div class="fw-semibold font-monospace">
                                {{ $parcel->sale_price !== null ? number_format((float) $parcel->sale_price, 2) : '—' }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">تاريخ البيع</div>
                            <div class="fw-semibold font-monospace">{{ optional($parcel->sale_date)->format('Y-m-d') ?: '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">المشتري</div>
                            <div class="fw-semibold">{{ $parcel->sold_to ?: '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">هاتف المشتري</div>
                            <div class="fw-semibold font-monospace">{{ $parcel->sale_phone ?: '—' }}</div>
                        </div>
                        @if ($profit !== null)
                            <div class="col-12">
                                <div class="small text-body-secondary">الربح / الخسارة</div>
                                <div class="fw-bold font-monospace {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($profit, 2) }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card app-surface">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0">مساهمو الأرض</h6>
                    @can('shareholders.manage')
                        <a href="{{ route('shareholders.index') }}" class="btn btn-outline-primary btn-sm">إدارة المساهمين</a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                            <tr>
                                <th>المساهم</th>
                                <th class="text-end">التمويل</th>
                                <th class="text-end">النسبة</th>
                                <th class="text-end">بروفايل</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($parcelShareholders ?? [] as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->shareholder->name }}</td>
                                    <td class="text-end font-monospace">{{ number_format((float) $row->total_investment, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $row->share_percentage, 2) }}%</td>
                                    <td class="text-end">
                                        <a href="{{ route('shareholders.show', $row->shareholder) }}" class="btn btn-outline-info btn-sm">فتح</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        لا يوجد مساهمون على هذه الأرض بعد. اربطهم من بروفايل المساهم ← «ربط بأرض بيع/شراء».
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2 small text-body-secondary">
                        أساس النسبة = سعر شراء الأرض ({{ number_format((float) $parcel->purchase_price, 2) }} ج.م).
                    </div>
                </div>
            </div>
        </div>

        @if ($parcel->notes)
            <div class="col-12">
                <div class="card app-surface">
                    <div class="card-header"><h6 class="mb-0">ملاحظات</h6></div>
                    <div class="card-body">
                        <div class="border rounded p-3 bg-body-tertiary">{{ $parcel->notes }}</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="small text-body-secondary">
                سجّلها: {{ $parcel->creator?->name ?? '—' }}
                @if ($parcel->created_at)
                    — <span class="font-monospace">{{ $parcel->created_at->format('Y-m-d H:i') }}</span>
                @endif
            </div>
        </div>
    </div>
@endsection
