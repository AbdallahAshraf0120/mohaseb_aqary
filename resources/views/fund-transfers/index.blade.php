@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => number_format($n, 2, '.', ',');
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card app-surface h-100">
                <div class="card-body">
                    <div class="small text-body-secondary">رصيد صندوق الأراضي</div>
                    <div class="fs-4 fw-bold font-monospace">{{ $fmt((float) $landCashboxBalance) }}</div>
                    <a href="{{ route('land-cashbox.index') }}" class="small">فتح صندوق الأراضي</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card app-surface h-100">
                <div class="card-body">
                    <div class="small text-body-secondary mb-2">أرصدة صناديق المشاريع</div>
                    <div class="d-flex flex-wrap gap-3">
                        @forelse ($projects as $p)
                            <div>
                                <div class="small text-body-secondary">{{ $p->name }}</div>
                                <div class="font-monospace fw-semibold">{{ $fmt((float) ($projectBalances[$p->id] ?? 0)) }}</div>
                            </div>
                        @empty
                            <span class="text-muted small">لا توجد مشاريع.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @can('cashbox.manage')
            <div class="col-lg-5">
                <div class="card app-surface mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">تحويل جديد</h5>
                        <div class="small text-body-secondary">من صندوق إلى آخر — مع إمكانية نسب التحويل لمساهم.</div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('fund-transfers.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">من</label>
                                    <select name="from_type" id="from_type" class="form-select" required>
                                        <option value="land_cashbox">صندوق الأراضي</option>
                                        <option value="project">صندوق مشروع</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">المصدر</label>
                                    <select name="from_id" id="from_id" class="form-select" required>
                                        <option value="{{ $landCashboxId }}">صندوق الأراضي</option>
                                        @foreach ($projects as $p)
                                            <option value="{{ $p->id }}" data-type="project">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">إلى</label>
                                    <select name="to_type" id="to_type" class="form-select" required>
                                        <option value="project">صندوق مشروع</option>
                                        <option value="land_cashbox">صندوق الأراضي</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الهدف</label>
                                    <select name="to_id" id="to_id" class="form-select" required>
                                        @foreach ($projects as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                        <option value="{{ $landCashboxId }}">صندوق الأراضي</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">المبلغ</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control font-monospace" value="{{ old('amount') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">التاريخ</label>
                                    <input type="date" name="transferred_at" class="form-control" value="{{ old('transferred_at', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">باسم مساهم (اختياري)</label>
                                    <select name="shareholder_id" class="form-select">
                                        <option value="">—</option>
                                        @foreach ($shareholders as $sh)
                                            <option value="{{ $sh->id }}" @selected((string) old('shareholder_id') === (string) $sh->id)>{{ $sh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">من حصيلة أرض (اختياري)</label>
                                    <select name="source_land_parcel_id" class="form-select">
                                        <option value="">—</option>
                                        @foreach ($parcels as $parcel)
                                            <option value="{{ $parcel->id }}" @selected((string) old('source_land_parcel_id') === (string) $parcel->id)>{{ $parcel->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">ملاحظة</label>
                                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">تنفيذ التحويل</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        <div class="@can('cashbox.manage') col-lg-7 @else col-12 @endcan">
            <div class="card app-surface mb-4">
                <div class="card-header">
                    <h5 class="mb-0">سجل التحويلات</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>من</th>
                                <th>إلى</th>
                                <th class="text-end">المبلغ</th>
                                <th>مساهم</th>
                                <th>ملاحظة</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($transfers as $tx)
                                <tr>
                                    <td class="font-monospace small">{{ $tx->transferred_at?->format('Y-m-d') }}</td>
                                    <td class="small">{{ \App\Models\FundTransfer::typeLabel($tx->from_type) }} #{{ $tx->from_id }}</td>
                                    <td class="small">{{ \App\Models\FundTransfer::typeLabel($tx->to_type) }} #{{ $tx->to_id }}</td>
                                    <td class="text-end font-monospace">{{ $fmt((float) $tx->amount) }}</td>
                                    <td class="small">{{ $tx->shareholder?->name ?? '—' }}</td>
                                    <td class="small">{{ $tx->notes ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">لا توجد تحويلات بعد.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($transfers->hasPages())
                    <div class="card-footer">{{ $transfers->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
