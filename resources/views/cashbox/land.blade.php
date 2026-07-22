@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => number_format($n, 2, '.', ',');
        $currencyLabel = strtoupper((string) $currency) === 'EGP' ? 'ج.م' : $currency;
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <x-listing.filters
        :placeholder="'وصف الحركة…'"
        :help="'حركات صندوق أراضي البيع والشراء — تظهر أيضًا ضمن الصندوق الشامل.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-4">
                    <div class="rounded-4 p-4 text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #198754 100%);">
                        <div class="opacity-75 small mb-1">رصيد صندوق الأراضي</div>
                        <div class="fs-2 fw-bold font-monospace lh-sm">{{ $fmt((float) $currentBalance) }}</div>
                        <div class="opacity-75 small mt-2">{{ $currencyLabel }} — {{ $project->name }}</div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="rounded-3 border bg-body-secondary bg-opacity-50 p-3 h-100">
                                <div class="small text-body-secondary fw-semibold mb-2">إجمالي القبض (تحصيل بيع)</div>
                                <div class="fs-5 fw-bold font-monospace text-success-emphasis">{{ $fmt((float) $revenuesTotal) }}</div>
                                <div class="small text-body-secondary mt-2">معلق: <span class="font-monospace fw-semibold">{{ $fmt((float) $pendingIn) }}</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 border bg-body-secondary bg-opacity-50 p-3 h-100">
                                <div class="small text-body-secondary fw-semibold mb-2">إجمالي الصرف (دفع شراء)</div>
                                <div class="fs-5 fw-bold font-monospace text-danger-emphasis">{{ $fmt((float) $expensesTotal) }}</div>
                                <div class="small text-body-secondary mt-2">معلق: <span class="font-monospace fw-semibold">{{ $fmt((float) $pendingOut) }}</span></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 border bg-body-secondary bg-opacity-50 p-3 h-100">
                                <div class="small text-body-secondary fw-semibold mb-2">روابط</div>
                                <a href="{{ route('land-trading.index') }}" class="d-block small fw-semibold mb-1">أراضي البيع والشراء</a>
                                <a href="{{ route('fund-transfers.index') }}" class="d-block small fw-semibold mb-1">تحويلات الصناديق</a>
                                <a href="{{ route('global-cashbox.index', ['project_id' => $project->id]) }}" class="d-block small fw-semibold">عرض في الصندوق الشامل</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="@can('cashbox.manage') col-lg-8 @else col-12 @endcan">
            <div class="card app-surface mb-4">
                <div class="card-header border-0 bg-transparent pt-4 px-4 pb-0">
                    <h5 class="mb-0 fw-semibold">سجل حركات صندوق الأراضي</h5>
                    <p class="small text-body-secondary mb-0 mt-1">{{ $transactions->total() }} حركة</p>
                </div>
                <div class="card-body p-0 pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>النوع</th>
                                <th class="text-end">المبلغ</th>
                                <th>الوصف</th>
                                <th>الحالة</th>
                                <th class="text-end">التاريخ</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($transactions as $tx)
                                <tr>
                                    <td class="small font-monospace text-body-secondary">{{ $transactions->firstItem() + $loop->index }}</td>
                                    <td>
                                        @if ($tx->type === 'revenue')
                                            <span class="badge text-bg-success">قبض</span>
                                        @else
                                            <span class="badge text-bg-danger">صرف</span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace fw-semibold">
                                        {{ $tx->type === 'revenue' ? '+' : '−' }}{{ $fmt((float) $tx->amount) }}
                                    </td>
                                    <td class="small">{{ $tx->description ? \Illuminate\Support\Str::limit($tx->description, 90) : '—' }}</td>
                                    <td class="small">
                                        @if (($tx->approval_status ?? 'approved') === 'approved')
                                            <span class="badge text-bg-success">معتمد</span>
                                        @elseif (($tx->approval_status ?? '') === 'pending')
                                            <span class="badge text-bg-warning">معلق</span>
                                        @else
                                            <span class="badge text-bg-secondary">مرفوض</span>
                                        @endif
                                    </td>
                                    <td class="text-end small font-monospace">{{ $tx->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">لا توجد حركات بعد. سجّل دفعات من صفحة الأرض.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($transactions->hasPages())
                    <div class="card-footer bg-transparent border-0">{{ $transactions->links() }}</div>
                @endif
            </div>
        </div>

        @can('cashbox.manage')
            <div class="col-lg-4">
                <div class="card app-surface mb-4 sticky-lg-top" style="top: 5rem;">
                    <div class="card-header border-0 bg-transparent pt-4 px-4 pb-0">
                        <h5 class="mb-0 fw-semibold">حركة يدوية</h5>
                        <p class="small text-body-secondary mb-0 mt-1">تُسجَّل على صندوق الأراضي وتظهر في الصندوق الشامل</p>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('land-cashbox.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">نوع الحركة</label>
                                <select name="type" class="form-select" required>
                                    <option value="revenue" @selected(old('type', 'revenue') === 'revenue')>قبض</option>
                                    <option value="expense" @selected(old('type') === 'expense')>صرف</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">المبلغ</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control font-monospace" value="{{ old('amount') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الوصف</label>
                                <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">حفظ</button>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endsection
