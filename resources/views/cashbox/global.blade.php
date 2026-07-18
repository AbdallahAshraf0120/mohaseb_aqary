@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => number_format($n, 2, '.', ',');
        $currencyLabel = strtoupper((string) $currency) === 'EGP' ? 'ج.م' : $currency;
    @endphp

    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-body-secondary border-0 py-3 px-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="fw-semibold"><i class="fa-solid fa-magnifying-glass ms-1 text-body-secondary"></i> بحث وتصفية</div>
                @if (request()->filled('q') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('project_id') || request()->filled('type') || request()->filled('status'))
                    <a href="{{ route('global-cashbox.index') }}" class="btn btn-sm btn-outline-secondary">مسح الفلاتر</a>
                @endif
            </div>
        </div>
        <div class="card-body p-4">
            <form method="get" action="{{ route('global-cashbox.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-body-secondary mb-1">المشروع</label>
                    <select name="project_id" class="form-select">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected((string) ($selectedProjectId ?? '') === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-body-secondary mb-1">النوع</label>
                    <select name="type" class="form-select">
                        <option value="">الكل</option>
                        <option value="revenue" @selected(($selectedType ?? '') === 'revenue')>قبض</option>
                        <option value="expense" @selected(($selectedType ?? '') === 'expense')>صرف</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-body-secondary mb-1">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">الكل</option>
                        <option value="approved" @selected(($selectedStatus ?? '') === 'approved')>معتمد</option>
                        <option value="pending" @selected(($selectedStatus ?? '') === 'pending')>معلق</option>
                        <option value="rejected" @selected(($selectedStatus ?? '') === 'rejected')>مرفوض</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-body-secondary mb-1">وصف الحركة</label>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="وصف الحركة…" maxlength="200" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">تطبيق</button>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-body-secondary mb-1">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-body-secondary mb-1">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
            </form>
            <p class="small text-body-secondary mb-0 mt-3">مراقبة حركات صناديق كل المشاريع من مكان واحد. التسجيل اليدوي يتم من صندوق كل مشروع.</p>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-4">
                    <div class="rounded-4 p-4 text-white" style="background: linear-gradient(135deg, var(--bs-primary) 0%, #0a58ca 100%);">
                        <div class="opacity-75 small mb-1">الرصيد الشامل (معتمد)</div>
                        <div class="fs-2 fw-bold font-monospace lh-sm">{{ $fmt((float) $currentBalance) }}</div>
                        <div class="opacity-75 small mt-2">{{ $currencyLabel }} — مجموع أرصدة كل المشاريع</div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="rounded-3 border bg-body-secondary bg-opacity-50 p-3 h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="rounded-2 p-2 text-bg-success"><i class="fa-solid fa-arrow-down-long"></i></span>
                                    <span class="small text-body-secondary fw-semibold">إجمالي القبض</span>
                                </div>
                                <div class="fs-5 fw-bold font-monospace text-success-emphasis">{{ $fmt((float) $revenuesTotal) }}</div>
                                <div class="small text-body-secondary mt-2">معلق: <span class="font-monospace fw-semibold">{{ $fmt((float) $pendingIn) }}</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 border bg-body-secondary bg-opacity-50 p-3 h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="rounded-2 p-2 text-bg-danger"><i class="fa-solid fa-arrow-up-long"></i></span>
                                    <span class="small text-body-secondary fw-semibold">إجمالي الصرف</span>
                                </div>
                                <div class="fs-5 fw-bold font-monospace text-danger-emphasis">{{ $fmt((float) $expensesTotal) }}</div>
                                <div class="small text-body-secondary mt-2">معلق: <span class="font-monospace fw-semibold">{{ $fmt((float) $pendingOut) }}</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 border bg-body-secondary bg-opacity-50 p-3 h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="rounded-2 p-2 bg-body text-body-secondary"><i class="fa-solid fa-diagram-project"></i></span>
                                    <span class="small text-body-secondary fw-semibold">مشاريع لها حركات</span>
                                </div>
                                <div class="fs-5 fw-bold font-monospace">{{ $byProject->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header border-0 bg-transparent pt-4 px-4 pb-0">
            <h5 class="mb-0 fw-semibold">أرصدة الصناديق حسب المشروع</h5>
            <p class="small text-body-secondary mb-0 mt-1">رصيد كل مشروع = القبض المعتمد − الصرف المعتمد</p>
        </div>
        <div class="card-body p-0 pt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>المشروع</th>
                        <th class="text-end">قبض معتمد</th>
                        <th class="text-end">صرف معتمد</th>
                        <th class="text-end">الرصيد</th>
                        <th class="text-end">معلق قبض</th>
                        <th class="text-end">معلق صرف</th>
                        <th class="text-end">عدد الحركات</th>
                        <th class="text-end">فتح الصندوق</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($byProject as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->project_name }}</td>
                            <td class="text-end font-monospace text-success-emphasis">{{ $fmt((float) $row->approved_in) }}</td>
                            <td class="text-end font-monospace text-danger-emphasis">{{ $fmt((float) $row->approved_out) }}</td>
                            <td class="text-end font-monospace fw-semibold {{ $row->balance >= 0 ? 'text-success' : 'text-danger' }}">{{ $fmt((float) $row->balance) }}</td>
                            <td class="text-end font-monospace small text-muted">{{ $fmt((float) $row->pending_in) }}</td>
                            <td class="text-end font-monospace small text-muted">{{ $fmt((float) $row->pending_out) }}</td>
                            <td class="text-end font-monospace">{{ $row->movements_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('cashbox.index', $row->project_id) }}" class="btn btn-outline-primary btn-sm">صندوق المشروع</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">لا توجد حركات صندوق ضمن الفلاتر الحالية.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header border-0 bg-transparent pt-4 px-4 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-semibold">سجل الحركات الشامل</h5>
                    <p class="small text-body-secondary mb-0 mt-1">مرتبة من الأحدث — {{ $transactions->total() }} حركة</p>
                </div>
            </div>
        </div>
        <div class="card-body p-0 pt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th class="text-body-secondary fw-semibold" style="width: 3rem">#</th>
                        <th class="text-body-secondary fw-semibold">المشروع</th>
                        <th class="text-body-secondary fw-semibold">النوع</th>
                        <th class="text-body-secondary fw-semibold text-end">المبلغ</th>
                        <th class="text-body-secondary fw-semibold">الوصف</th>
                        <th class="text-body-secondary fw-semibold">الحالة</th>
                        <th class="text-body-secondary fw-semibold text-end">التاريخ والوقت</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
                            <td class="text-body-secondary small font-monospace">{{ $transactions->firstItem() + $loop->index }}</td>
                            <td class="small fw-semibold">{{ $tx->project?->name ?? '—' }}</td>
                            <td>
                                @if ($tx->type === 'revenue')
                                    <span class="badge rounded-pill text-bg-success"><i class="fa-solid fa-arrow-trend-down ms-1"></i> قبض</span>
                                @else
                                    <span class="badge rounded-pill text-bg-danger"><i class="fa-solid fa-arrow-trend-up ms-1"></i> صرف</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace fw-semibold @if($tx->type === 'revenue') text-success-emphasis @else text-danger-emphasis @endif">
                                {{ $tx->type === 'revenue' ? '+' : '−' }}{{ $fmt((float) $tx->amount) }}
                            </td>
                            <td class="small">{{ $tx->description ? \Illuminate\Support\Str::limit($tx->description, 80) : '—' }}</td>
                            <td class="small">
                                @if (($tx->approval_status ?? 'approved') === 'approved')
                                    <span class="badge text-bg-success">معتمد</span>
                                @elseif (($tx->approval_status ?? '') === 'pending')
                                    <span class="badge text-bg-warning">معلق</span>
                                @else
                                    <span class="badge text-bg-secondary">مرفوض</span>
                                @endif
                            </td>
                            <td class="text-end small font-monospace text-body-secondary">{{ $tx->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-body-secondary mb-2"><i class="fa-solid fa-receipt fa-2x opacity-50"></i></div>
                                <p class="mb-1 fw-semibold">لا توجد حركات مسجّلة</p>
                                <p class="small text-muted mb-0">حركات كل مشروع تظهر هنا تلقائياً عند تسجيلها في صناديق المشاريع.</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($transactions->hasPages())
            <div class="card-footer bg-transparent border-0 pt-0">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
@endsection
