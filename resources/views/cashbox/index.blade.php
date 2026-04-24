@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => number_format($n, 2, '.', ',');
        $currencyLabel = strtoupper((string) $currency) === 'EGP' ? 'ج.م' : $currency;
    @endphp

    <x-partials.module-wireflow-header label="الصندوق" step="8" />

    <div class="card app-surface mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-4">
                    <div class="rounded-4 p-4 text-white" style="background: linear-gradient(135deg, var(--bs-primary) 0%, #0a58ca 100%);">
                        <div class="opacity-75 small mb-1">الرصيد الحالي</div>
                        <div class="fs-2 fw-bold font-monospace lh-sm">{{ $fmt((float) $currentBalance) }}</div>
                        <div class="opacity-75 small mt-2">{{ $currencyLabel }} — حركات هذا المشروع فقط</div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="rounded-3 border bg-body-secondary bg-opacity-50 p-3 h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="rounded-2 p-2 bg-body text-body-secondary"><i class="fa-solid fa-flag"></i></span>
                                    <span class="small text-body-secondary fw-semibold">رصيد افتتاحي</span>
                                </div>
                                <div class="fs-5 fw-bold font-monospace">{{ $fmt((float) $openingBalance) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 border bg-body-secondary bg-opacity-50 p-3 h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="rounded-2 p-2 text-bg-success"><i class="fa-solid fa-arrow-down-long"></i></span>
                                    <span class="small text-body-secondary fw-semibold">إجمالي القبض</span>
                                </div>
                                <div class="fs-5 fw-bold font-monospace text-success-emphasis">{{ $fmt((float) $revenuesTotal) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 border bg-body-secondary bg-opacity-50 p-3 h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="rounded-2 p-2 text-bg-danger"><i class="fa-solid fa-arrow-up-long"></i></span>
                                    <span class="small text-body-secondary fw-semibold">إجمالي الصرف</span>
                                </div>
                                <div class="fs-5 fw-bold font-monospace text-danger-emphasis">{{ $fmt((float) $expensesTotal) }}</div>
                            </div>
                        </div>
                    </div>
                    <p class="small text-body-secondary mb-0 mt-3">
                        <i class="fa-solid fa-circle-info ms-1"></i>
                        الحركات المعروضة هي السجلات اليدوية والمرتبطة بصندوق هذا المشروع. للتحصيلات التفصيلية راجع
                        <a href="{{ route('revenues.index') }}" class="fw-semibold">التحصيلات</a>
                        و<a href="{{ route('expenses.index') }}" class="fw-semibold">المصروفات</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-lg-8">
            <div class="card app-surface mb-4">
                <div class="card-header border-0 bg-transparent pt-4 px-4 pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="mb-0 fw-semibold">سجل حركات الصندوق</h5>
                            <p class="small text-body-secondary mb-0 mt-1">مرتبة من الأحدث — {{ $transactions->total() }} حركة</p>
                        </div>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-gauge-high ms-1"></i> لوحة التحكم
                        </a>
                    </div>
                </div>
                <div class="card-body p-0 pt-3">
                    @if (session('success'))
                        <div class="px-4 pb-3">
                            <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                            </div>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th class="text-body-secondary fw-semibold" style="width: 3rem">#</th>
                                <th class="text-body-secondary fw-semibold">النوع</th>
                                <th class="text-body-secondary fw-semibold text-end">المبلغ</th>
                                <th class="text-body-secondary fw-semibold">الوصف</th>
                                <th class="text-body-secondary fw-semibold text-end">التاريخ والوقت</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($transactions as $tx)
                                <tr>
                                    <td class="text-body-secondary small font-monospace">{{ $transactions->firstItem() + $loop->index }}</td>
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
                                    <td class="text-end small font-monospace text-body-secondary">{{ $tx->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="text-body-secondary mb-2"><i class="fa-solid fa-receipt fa-2x opacity-50"></i></div>
                                        <p class="mb-1 fw-semibold">لا توجد حركات مسجّلة بعد</p>
                                        <p class="small text-muted mb-0">سجّل أول حركة قبض أو صرف من النموذج على اليمين.</p>
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
        </div>

        <div class="col-lg-4">
            <div class="card app-surface mb-4 sticky-lg-top" style="top: 5rem;">
                <div class="card-header border-0 bg-transparent pt-4 px-4 pb-0">
                    <h5 class="mb-0 fw-semibold">تسجيل حركة يدوية</h5>
                    <p class="small text-body-secondary mb-0 mt-1">قبض أو صرف على صندوق المشروع</p>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('cashbox.store', [$project]) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">نوع الحركة</label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="revenue" @selected(old('type', 'revenue') === 'revenue')>قبض (وارد للصندوق)</option>
                                <option value="expense" @selected(old('type') === 'expense')>صرف (صادر من الصندوق)</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="cashbox-amount">المبلغ</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" name="amount" id="cashbox-amount"
                                       class="form-control font-monospace @error('amount') is-invalid @enderror"
                                       value="{{ old('amount') }}" placeholder="0.00" required>
                                <span class="input-group-text">{{ $currencyLabel }}</span>
                            </div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">أقل قيمة مسموحة 0.01 {{ $currencyLabel }}</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="cashbox-desc">الوصف <span class="text-muted fw-normal">(اختياري)</span></label>
                            <textarea name="description" id="cashbox-desc" rows="3" maxlength="500"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="مثال: سداد نقدي — توريد بنكي">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="fa-solid fa-floppy-disk ms-1"></i> حفظ الحركة
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
