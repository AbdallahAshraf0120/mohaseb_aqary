@extends('layouts.admin')

@section('content')
    @php
        $m = fn ($v) => \App\Support\Money::display((float) $v);
        $company = in_array($companyName, ['Real Estate Demo', ''], true) ? $project->name : $companyName;
        $wa = \App\Support\Phone::whatsappUrl($client->phone);
    @endphp

    <style>
        @media print {
            .app-sidebar, .app-header, .app-footer, .d-print-none, .app-content-header { display: none !important; }
            .app-main, .app-content { margin: 0 !important; padding: 0 !important; }
            .card { border: 0 !important; box-shadow: none !important; }
            body { background: #fff !important; }
        }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <div class="small text-body-secondary d-none d-print-block">{{ $company }} — {{ $project->name }}</div>
            <h4 class="mb-1 fw-semibold">كشف حساب: {{ $client->name }}</h4>
            <div class="small text-body-secondary">
                <span class="font-monospace">{{ $client->phone }}</span>
                @if ($client->national_id) · رقم قومي <span class="font-monospace">{{ $client->national_id }}</span>@endif
                · حتى {{ now()->format('Y-m-d') }}
            </div>
        </div>
        <div class="d-flex gap-2 d-print-none">
            <a href="{{ route('clients.show', [$project, $client]) }}" class="btn btn-outline-secondary btn-sm">بروفايل العميل</a>
            @if ($wa)
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm"><i class="fa-brands fa-whatsapp ms-1"></i> واتساب</a>
            @endif
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa-solid fa-print ms-1"></i> طباعة</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([['إجمالي التعاقدات', $totals['price'], ''], ['المدفوع', $totals['paid'], 'text-success-emphasis'], ['المتبقي', $totals['remaining'], ''], ['متأخر حاليًا', $totals['overdue'], $totals['overdue'] > 0 ? 'text-danger' : '']] as [$label, $val, $cls])
            <div class="col-6 col-lg-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="small text-body-secondary">{{ $label }}</div>
                    <div class="fs-5 fw-bold font-monospace {{ $cls }}">{{ $m($val) }} <span class="fs-6">ج.م</span></div>
                </div>
            </div>
        @endforeach
    </div>

    @foreach ($units as $u)
        @php $sale = $u['sale']; @endphp
        <div class="card app-surface mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between gap-2">
                <h6 class="mb-0 fw-semibold">
                    {{ $sale->property?->name ?? '—' }} — {{ $sale->unitLabel() }}
                    <span class="text-body-secondary small">(SL-{{ $sale->id }} · {{ optional($sale->sale_date)->format('Y-m-d') }})</span>
                    @if ($sale->isCancelled())
                        <span class="badge text-bg-danger">تعاقد ملغي — اترد {{ $m($sale->refund_amount) }}</span>
                    @elseif (! $u['approved'])
                        <span class="badge text-bg-warning">لسه ما اتعتمدتش</span>
                    @endif
                </h6>
                <div class="small font-monospace">
                    السعر {{ $m($u['price']) }} · المدفوع {{ $m($u['paid']) }} · المتبقي <b>{{ $m($u['remaining']) }}</b>
                </div>
            </div>
            @if (count($u['schedule']))
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead><tr><th>#</th><th>البند</th><th>الاستحقاق</th><th class="text-end">القيمة</th><th class="text-end">المسدد</th><th class="text-end">الباقي</th><th>الحالة</th></tr></thead>
                            <tbody>
                            @foreach ($u['schedule'] as $row)
                                @php $late = $row['balance'] > 0.00001 && $row['due_date']->lt(now()->startOfDay()); @endphp
                                <tr class="{{ $late ? 'table-danger' : '' }}">
                                    <td>{{ $row['number'] }}</td>
                                    <td>{{ $row['label'] ?: 'قسط' }}</td>
                                    <td class="font-monospace">{{ $row['due_date']->format('Y-m-d') }}</td>
                                    <td class="text-end font-monospace">{{ $m($row['amount']) }}</td>
                                    <td class="text-end font-monospace">{{ $m($row['paid']) }}</td>
                                    <td class="text-end font-monospace">{{ $m($row['balance']) }}</td>
                                    <td>{{ $late ? 'متأخر' : $row['status'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="card-body small text-body-secondary">{{ $sale->payment_type === 'cash' ? 'بيعة كاش — مفيش جدول أقساط.' : 'مفيش جدول أقساط محفوظ للبيعة دي.' }}</div>
            @endif
        </div>
    @endforeach

    <div class="card app-surface mb-4">
        <div class="card-header"><h6 class="mb-0 fw-semibold">الحركة المالية</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead><tr><th>التاريخ</th><th>البيان</th><th>المرجع</th><th class="text-end">عليه</th><th class="text-end">دفع</th><th class="text-end">الرصيد</th></tr></thead>
                    <tbody>
                    @forelse ($ledger as $row)
                        <tr>
                            <td class="font-monospace">{{ optional($row['date'])->format('Y-m-d') }}</td>
                            <td>{{ $row['desc'] }}
                                @if (! empty($row['note']))<span class="badge text-bg-light border">{{ $row['note'] }}</span>@endif
                            </td>
                            <td class="font-monospace small">{{ $row['ref'] }}</td>
                            <td class="text-end font-monospace">{{ $row['debit'] > 0 ? $m($row['debit']) : '' }}</td>
                            <td class="text-end font-monospace">{{ $row['credit'] > 0 ? $m($row['credit']) : '' }}</td>
                            <td class="text-end font-monospace fw-semibold">{{ $m($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">مفيش حركة.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if (! empty($cheques) && count($cheques))
        <div class="card app-surface mb-4">
            <div class="card-header"><h6 class="mb-0 fw-semibold">شيكات تحت التحصيل / مرتدة</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>رقم الشيك</th><th>البنك</th><th>الاستحقاق</th><th class="text-end">المبلغ</th><th>الحالة</th></tr></thead>
                    <tbody>
                    @foreach ($cheques as $c)
                        <tr class="{{ $c->cheque_status === 'bounced' ? 'table-danger' : '' }}">
                            <td class="font-monospace">{{ $c->cheque_number }}</td>
                            <td>{{ $c->cheque_bank ?: '—' }}</td>
                            <td class="font-monospace">{{ optional($c->cheque_due_date)->format('Y-m-d') }}</td>
                            <td class="text-end font-monospace">{{ $m($c->amount) }}</td>
                            <td>{{ $c->chequeStatusLabel() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <p class="small text-body-secondary">الشيكات بتتحسب في المدفوع لما تتصرف بس. التحصيلات المعتمدة بتتوزع على أقدم قسط الأول.</p>
@endsection
