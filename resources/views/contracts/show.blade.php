@extends('layouts.admin')

@section('content')
    @php
        $downPayment = (float) ($contract->sale?->down_payment ?? 0);
        $netContractValue = max(0, (float) $contract->total_price - $downPayment);
        $total = (float) $contract->total_price;
        $paid = (float) $contract->paid_amount;
        $progressPct = $total > 0.01 ? min(100, round(($paid / $total) * 100, 1)) : 0;
    @endphp

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-body-secondary border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                <div class="text-body-secondary small mb-1">عقد</div>
                <h4 class="mb-0 fw-semibold">
                    CT-{{ now()->format('Y') }}-{{ str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT) }}
                </h4>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('contracts.index', $project) }}" class="btn btn-outline-secondary btn-sm">رجوع للعقود</a>
                @if ($hasContractTemplate ?? false)
                    <a href="{{ route('contracts.word', [$project, $contract]) }}" class="btn btn-outline-success btn-sm"><i class="fa-regular fa-file-word ms-1"></i> تصدير عقد Word</a>
                @endif
                @if ($contract->sale_id)
                    <a href="{{ route('sales.show', [$project, $contract->sale]) }}" class="btn btn-primary btn-sm">تفاصيل البيعة</a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <div class="d-flex justify-content-between small text-body-secondary mb-1">
                    <span>نسبة التسديد (من إجمالي العقد)</span>
                    <span class="font-monospace fw-semibold">{{ $progressPct }}%</span>
                </div>
                <div class="progress rounded-pill" style="height: 10px;" role="progressbar" aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-success" style="width: {{ $progressPct }}%"></div>
                </div>
                <div class="d-flex flex-wrap justify-content-between gap-2 mt-2 small">
                    <span>المدفوع: <strong class="font-monospace">{{ number_format($paid, 2) }}</strong> ج.م</span>
                    <span>المتبقي: <strong class="font-monospace">{{ number_format((float) $contract->remaining_amount, 2) }}</strong> ج.م</span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">الأطراف والعقار</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                            <tr>
                                <th class="text-body-secondary align-top py-2" style="width: 40%">العميل</th>
                                <td class="py-2 fw-medium">
                                    @if ($contract->client)
                                        <a href="{{ route('clients.show', [$project, $contract->client]) }}" class="text-decoration-none">{{ $contract->client->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">العقار</th>
                                <td class="py-2">{{ $contract->property?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">البيعة</th>
                                <td class="py-2">
                                    @if ($contract->sale_id && $contract->sale)
                                        <a href="{{ route('sales.show', [$project, $contract->sale]) }}" class="text-decoration-none font-monospace">SL-{{ str_pad((string) $contract->sale_id, 3, '0', STR_PAD_LEFT) }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @if ($contract->sale_id)
                                <tr>
                                    <th class="text-body-secondary align-top py-2">البروكر</th>
                                    <td class="py-2">{{ $contract->sale?->broker_name ?: '—' }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">الفترة</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                            <tr>
                                <th class="text-body-secondary align-top py-2" style="width: 40%">بداية العقد</th>
                                <td class="py-2 font-monospace">{{ $contract->start_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">نهاية العقد</th>
                                <td class="py-2 font-monospace">{{ $contract->end_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">المبالغ</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                            <tr>
                                <th class="text-body-secondary align-top py-2" style="width: 40%">إجمالي السعر</th>
                                <td class="py-2 font-monospace fw-medium">{{ number_format($total, 2) }} ج.م</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">المقدم</th>
                                <td class="py-2 font-monospace">{{ number_format($downPayment, 2) }} ج.م</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">بعد المقدم</th>
                                <td class="py-2 font-monospace">{{ number_format($netContractValue, 2) }} ج.م</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">المسدَّد</th>
                                <td class="py-2 font-monospace text-success-emphasis">{{ number_format($paid, 2) }} ج.م</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">المتبقي</th>
                                <td class="py-2 font-monospace fw-semibold">{{ number_format((float) $contract->remaining_amount, 2) }} ج.م</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
