@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card app-surface mb-4" id="pay-from-cashbox">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">سداد من الصندوق</h5>
            <span class="text-muted small">المتاح: <span class="font-monospace">{{ number_format((float) ($remainingAvailable ?? $debt->remaining_amount), 2) }}</span> ج.م</span>
        </div>
        <div class="card-body">
            @php
                $avail = (float) ($remainingAvailable ?? $debt->remaining_amount);
            @endphp
            @if ($avail > 0.009)
                <form method="post" action="{{ route('debts.pay-from-cashbox', [$project, $debt]) }}" class="mb-0">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label" for="pay-amount">المبلغ (ج.م)</label>
                            <input id="pay-amount" type="number" name="amount" step="0.01" min="0.01"
                                   max="{{ number_format($avail, 2, '.', '') }}"
                                   class="form-control font-monospace @error('amount') is-invalid @enderror @error('pay_amount') is-invalid @enderror"
                                   value="{{ old('amount', number_format($avail, 2, '.', '')) }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('pay_amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="pay-note">ملاحظة (اختياري)</label>
                            <input id="pay-note" type="text" name="note" class="form-control" maxlength="500"
                                   value="{{ old('note') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fa-solid fa-vault me-1"></i>تسجيل سداد من الصندوق
                            </button>
                        </div>
                    </div>
                    <p class="form-text mb-0 mt-2">يُسجَّل كحركة مصروف معلّقة في الصندوق حتى اعتماد الأدمن.</p>
                </form>
            @else
                <p class="text-muted mb-0">لا يوجد متبقي للسداد من الصندوق لهذه الذمة.</p>
            @endif
        </div>
    </div>

    @if ($debt->debtPayments->isNotEmpty())
        <div class="card app-surface mb-4">
            <div class="card-header">
                <h5 class="mb-0">سدادات مسجَّلة من الصندوق</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th class="text-end">المبلغ (ج.م)</th>
                            <th>ملاحظة</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($debt->debtPayments as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                <td class="text-end font-monospace">{{ number_format((float) $p->amount, 2) }}</td>
                                <td>{{ $p->note ?: '—' }}</td>
                                <td>
                                    @if (($p->approval_status ?? 'approved') === 'approved')
                                        <span class="badge text-bg-success">معتمد</span>
                                    @elseif (($p->approval_status ?? '') === 'pending')
                                        <span class="badge text-bg-warning">معلق</span>
                                    @else
                                        <span class="badge text-bg-secondary">مرفوض</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل ذمة دائنة</h5>
            <a href="{{ route('debts.index', $project) }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('debts.update', [$project, $debt]) }}">
                @method('PUT')
                @include('debts._form')
                <button type="submit" class="btn btn-primary mt-3">تحديث</button>
            </form>
        </div>
    </div>
@endsection
