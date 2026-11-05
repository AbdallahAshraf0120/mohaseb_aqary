@extends('layouts.admin')

@section('content')
    @php
        $m = fn ($v) => \App\Support\Money::display((float) $v);
        $f = $figures;
    @endphp

    <x-listing.filters
        :placeholder="'—'"
        :help="'فلتر التاريخ اختياري: من غيره التصفية بتحسب المشروع من أوله لحد النهارده.'"
    />

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="rounded-4 border p-4 h-100 {{ $f['realized_profit'] >= 0 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                <div class="small text-body-secondary mb-1">الربح المحقق (اللي دخل − اللي خرج فعلًا)</div>
                <div class="fs-3 fw-bold font-monospace">{{ $m($f['realized_profit']) }} <span class="fs-6">ج.م</span></div>
                <div class="small text-body-secondary">محصّل {{ $m($f['cash_in']) }} − مصروف {{ $m($f['cash_out']) }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="rounded-4 border p-4 h-100 bg-primary-subtle">
                <div class="small text-body-secondary mb-1">الربح المتوقع (بعد تحصيل كل العقود وسداد كل الموردين)</div>
                <div class="fs-3 fw-bold font-monospace">{{ $m($f['expected_profit']) }} <span class="fs-6">ج.م</span></div>
                <div class="small text-body-secondary">لسه عند العملاء {{ $m($f['receivables']) }} · لسه للموردين {{ $m($f['supplier_open']) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card app-surface h-100">
                <div class="card-header"><h5 class="mb-0 fw-semibold">الإيرادات</h5></div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <tr><td>قيمة المبيعات المعتمدة ({{ (int) $f['sales_count'] }} بيعة)</td><td class="text-end font-monospace fw-semibold">{{ $m($f['sales_value']) }}</td></tr>
                        <tr><td class="ps-4">منها مقدمات</td><td class="text-end font-monospace">{{ $m($f['down_payments']) }}</td></tr>
                        <tr><td class="ps-4">ومنها تحصيلات أقساط</td><td class="text-end font-monospace">{{ $m($f['collections']) }}</td></tr>
                        <tr><td class="ps-4">ولسه عند العملاء</td><td class="text-end font-monospace">{{ $m($f['receivables']) }}</td></tr>
                        <tr><td>قبض يدوي في الصندوق</td><td class="text-end font-monospace">{{ $m($f['manual_in']) }}</td></tr>
                        <tr class="table-light"><td class="fw-semibold">المحصّل فعلًا</td><td class="text-end font-monospace fw-bold text-success-emphasis">{{ $m($f['cash_in']) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card app-surface h-100">
                <div class="card-header"><h5 class="mb-0 fw-semibold">التكاليف</h5></div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <tr><td>المصروفات</td><td class="text-end font-monospace">{{ $m($f['expenses']) }}</td></tr>
                        <tr><td>مشتريات الموردين (إجمالي الذمم)</td><td class="text-end font-monospace fw-semibold">{{ $m($f['supplier_purchases']) }}</td></tr>
                        <tr><td class="ps-4">منها اتسدد</td><td class="text-end font-monospace">{{ $m($f['supplier_paid']) }}</td></tr>
                        <tr><td class="ps-4">ولسه مستحق للموردين</td><td class="text-end font-monospace">{{ $m($f['supplier_open']) }}</td></tr>
                        <tr><td>صرف يدوي من الصندوق</td><td class="text-end font-monospace">{{ $m($f['manual_out']) }}</td></tr>
                        <tr class="table-light"><td class="fw-semibold">المصروف فعلًا</td><td class="text-end font-monospace fw-bold text-danger-emphasis">{{ $m($f['cash_out']) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-semibold">نصيب كل شريك</h5>
            <span class="badge text-bg-light border">مجموع النسب: {{ $m($pctSum) }}%</span>
        </div>
        <div class="card-body">
            @if ($shareholders->isNotEmpty() && abs($pctSum - 100) > 0.01)
                <div class="alert alert-warning small">مجموع نسب الشركاء مش 100%، فالربح بيتوزع على نسبة كل شريك من المجموع. راجع النسب من صفحة المساهمين.</div>
            @endif
            <p class="small text-body-secondary mb-3">
                «المستحق له الآن» = رصيده في الجاري (رأس المال − السحوبات) + نصيبه من الربح المحقق. ده المبلغ اللي ياخده لو المشروع اتصفّى النهارده.
            </p>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>المساهم</th>
                        <th>النسبة</th>
                        <th class="text-end">رأس المال المودع</th>
                        <th class="text-end">رصيد الجاري</th>
                        <th class="text-end">نصيبه من الربح المحقق</th>
                        <th class="text-end">نصيبه من الربح المتوقع</th>
                        <th class="text-end">المستحق له الآن</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($shareholders as $sh)
                        <tr>
                            <td class="fw-semibold">{{ $sh->name }}</td>
                            <td>{{ $m($sh->share_percentage) }}% <span class="text-body-secondary small">({{ $sh->percentage_basis }})</span></td>
                            <td class="text-end font-monospace">{{ $m($sh->capital_deposits_total) }}</td>
                            <td class="text-end font-monospace {{ $sh->ledger_balance >= 0 ? '' : 'text-danger' }}">{{ $m($sh->ledger_balance) }}</td>
                            <td class="text-end font-monospace">{{ $m($sh->realized_profit_share) }}</td>
                            <td class="text-end font-monospace text-body-secondary">{{ $m($sh->expected_profit_share) }}</td>
                            <td class="text-end font-monospace fw-bold {{ $sh->due_now >= 0 ? 'text-success-emphasis' : 'text-danger' }}">{{ $m($sh->due_now) }}</td>
                            <td class="text-end"><a href="{{ route('shareholders.show', $sh) }}" class="btn btn-outline-info btn-sm">الجاري</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">لا يوجد مساهمون في هذا المشروع.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-body small text-body-secondary">
            رأس مال الشركاء وسحوباتهم وتحويلات الصناديق مش ربح ولا تكلفة، فمش داخلين في حساب الربح. والحركات اليدوية في الصندوق داخلة كإيراد أو تكلفة، فلو فيه حركة يدوية هي في الحقيقة رأس مال أو سحب، سجّلها من جاري المساهم بدل الصندوق.
        </div>
    </div>
@endsection
