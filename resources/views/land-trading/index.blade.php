@extends('layouts.admin')

@section('content')
    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي الأراضي', 'value' => (int) ($kpis['count'] ?? 0)],
        ['label' => 'مملوكة', 'value' => (int) ($kpis['owned'] ?? 0)],
        ['label' => 'للبيع', 'value' => (int) ($kpis['for_sale'] ?? 0)],
        ['label' => 'مباعة', 'value' => (int) ($kpis['sold'] ?? 0)],
        ['label' => 'إجمالي الشراء', 'value' => number_format((float) ($kpis['purchase_total'] ?? 0), 5)],
        ['label' => 'صافي الربح (المباع)', 'value' => number_format((float) ($kpis['profit'] ?? 0), 5)],
    ]" />

    <x-listing.filters
        :placeholder="'اسم الأرض، الموقع، الصك، الطرف…'"
        :help="'تصفية حسب تاريخ تسجيل الأرض.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">أراضي البيع والشراء</h5>
                @if (request()->filled('q') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('status'))
                    <span class="badge text-bg-primary">فلاتر نشطة</span>
                @endif
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('land-trading.sales') }}" class="btn btn-outline-success btn-sm">
                    <i class="fa-solid fa-handshake ms-1"></i> مبيعات الأراضي
                </a>
                @can('land-trading.manage')
                    <a href="{{ route('land-trading.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-circle-plus ms-1"></i> إضافة أرض
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="d-flex flex-wrap gap-2 mb-3">
                @php
                    $statuses = ['' => 'الكل'] + \App\Models\LandParcel::STATUSES;
                @endphp
                @foreach ($statuses as $key => $label)
                    @php
                        $active = (string) $status === (string) $key;
                        $qs = request()->query();
                        if ($key === '') {
                            unset($qs['status']);
                        } else {
                            $qs['status'] = $key;
                        }
                    @endphp
                    <a href="{{ route('land-trading.index', $qs) }}"
                       class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>الأرض</th>
                        <th>الموقع</th>
                        <th>المساحة</th>
                        <th>الحالة</th>
                        <th>سعر الشراء</th>
                        <th>سعر البيع</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($parcels as $parcel)
                        @php
                            $badge = match ($parcel->status) {
                                'owned' => 'text-bg-info',
                                'for_sale' => 'text-bg-warning',
                                'reserved' => 'text-bg-primary',
                                'sold' => 'text-bg-success',
                                'cancelled' => 'text-bg-secondary',
                                default => 'text-bg-light',
                            };
                        @endphp
                        <tr>
                            <td>{{ $parcels->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">
                                {{ $parcel->name }}
                                @if ($parcel->deed_number)
                                    <div class="small text-body-secondary font-monospace">صك: {{ $parcel->deed_number }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $parcel->location ?: '—' }}
                                @if ($parcel->city)
                                    <div class="small text-body-secondary">{{ $parcel->city }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($parcel->area_size !== null)
                                    <span class="font-monospace">{{ number_format((float) $parcel->area_size, 5) }}</span>
                                    <span class="small text-body-secondary">م²</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="badge {{ $badge }}">{{ $parcel->statusLabel() }}</span></td>
                            <td class="font-monospace">{{ number_format((float) $parcel->purchase_price, 5) }}</td>
                            <td class="font-monospace">
                                {{ $parcel->sale_price !== null ? number_format((float) $parcel->sale_price, 5) : '—' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('land-trading.show', $parcel) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                @can('land-trading.manage')
                                    <a href="{{ route('land-trading.edit', $parcel) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                    <form method="post" action="{{ route('land-trading.destroy', $parcel) }}" class="d-inline"
                                          data-swal-confirm="{{ e('هل تريد حذف هذه الأرض؟') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">لا توجد أراضٍ مسجّلة بعد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $parcels->links() }}</div>
        </div>
    </div>
@endsection
