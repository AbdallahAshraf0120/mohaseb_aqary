@extends('layouts.admin')

@section('content')
    @php
        $plan = $sale->installment_plan ?? [];
        $scheduleLabel = $sale->installmentScheduleTypeLabel();
        $secondaryList = $plan['secondary_payments'] ?? [];
        $secondaryTotal = (float) ($plan['secondary_payments_total'] ?? 0);
        $mzNums = collect($sale->property?->mezzanine_floors ?? [])
            ->pluck('floor_number')
            ->map(static fn ($n) => (int) $n)
            ->filter(static fn (int $n) => $n >= 1);
        if ((int) $sale->floor_number === 0) {
            $floorLabel = '0 (أرضي تجاري)';
        } elseif ($sale->is_mezzanine) {
            $floorLabel = $sale->floor_number . ' (ميزان)';
        } elseif ($mzNums->contains((int) $sale->floor_number)) {
            $floorLabel = $sale->floor_number . ' (سكني)';
        } else {
            $floorLabel = (string) $sale->floor_number;
        }
        $revenues = $sale->contract?->revenues ?? collect();
        $contractTotal = (float) ($stats['contract_total'] ?? 0);
        $contractPaid = (float) ($stats['contract_paid'] ?? 0);
        $contractProgressPct = $contractTotal > 0.01 ? min(100, round(($contractPaid / $contractTotal) * 100, 1)) : 0;
    @endphp

    <x-partials.module-kpis :items="[
        ['label' => 'سعر البيعة', 'value' => number_format((float) $sale->sale_price, 2) . ' ج.م'],
        ['label' => 'المقدم', 'value' => number_format((float) $sale->down_payment, 2) . ' ج.م'],
        ['label' => 'تحصيلات على العقد', 'value' => number_format($stats['revenues_sum'], 2) . ' ج.م (' . $stats['revenues_count'] . ')'],
        ['label' => 'متبقي العقد', 'value' => number_format($stats['contract_remaining'], 2) . ' ج.م'],
        ['label' => 'أقساط مجدولة', 'value' => $stats['installment_rows'] > 0 ? $stats['installment_rows'] . ' بند' : '—'],
    ]" />

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-body-secondary border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                <div class="text-body-secondary small mb-1">بيعة</div>
                <h4 class="mb-0 fw-semibold">#{{ $sale->id }}</h4>
                <div class="small text-body-secondary mt-1">{{ $sale->property?->name ?? '—' }} · {{ $floorLabel }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('sales.index', $project) }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
                <a href="{{ route('sales.edit', [$project, $sale]) }}" class="btn btn-primary btn-sm">تعديل</a>
                @if ($sale->client)
                    <a href="{{ route('clients.show', [$project, $sale->client]) }}" class="btn btn-outline-info btn-sm">العميل</a>
                @endif
                @if ($sale->contract)
                    <a href="{{ route('contracts.show', [$project, $sale->contract]) }}" class="btn btn-outline-dark btn-sm">العقد</a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">العقار والوحدة</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                            <tr>
                                <th class="text-body-secondary align-top py-2" style="width: 40%">العقار</th>
                                <td class="py-2 fw-medium">{{ $sale->property?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">نوع العقار</th>
                                <td class="py-2">{{ $sale->property?->property_type ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">المنطقة</th>
                                <td class="py-2">{{ $sale->property?->area?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">الأرض</th>
                                <td class="py-2">{{ $sale->property?->land?->name ?? ($sale->property?->land_name ?? '—') }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">الدور / الوحدة</th>
                                <td class="py-2">{{ $floorLabel }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">نموذج الشقة</th>
                                <td class="py-2">{{ $sale->apartment_model ?: '—' }}</td>
                            </tr>
                            @php($mf = collect($sale->property?->mushaa_floors ?? [])->map(fn ($n) => (int) $n)->filter(fn ($n) => $n >= 1))
                            @if ($mf->isNotEmpty())
                                <tr>
                                    <th class="text-body-secondary align-top py-2">أدوار مشاعة</th>
                                    <td class="py-2">{{ $mf->sort()->implode('، ') }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">المالية والسداد</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                            <tr>
                                <th class="text-body-secondary align-top py-2" style="width: 40%">سعر البيع</th>
                                <td class="py-2 font-monospace fw-medium">{{ number_format((float) $sale->sale_price, 2) }} ج.م</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">نوع السداد</th>
                                <td class="py-2">
                                    @if ($sale->payment_type === 'cash')
                                        <span class="badge text-bg-success">كاش</span>
                                    @else
                                        <span class="badge text-bg-primary">تقسيط</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">المقدم</th>
                                <td class="py-2 font-monospace">{{ number_format((float) $sale->down_payment, 2) }} ج.م</td>
                            </tr>
                            @if ($sale->payment_type === 'installment')
                                <tr>
                                    <th class="text-body-secondary align-top py-2">مدة التقسيط</th>
                                    <td class="py-2">{{ $sale->installment_months ?? '—' }} شهرًا</td>
                                </tr>
                                <tr>
                                    <th class="text-body-secondary align-top py-2">نظام القسط</th>
                                    <td class="py-2">{{ $scheduleLabel }} @if(!empty($plan['interval_months']))<span class="text-muted small">(كل {{ (int) $plan['interval_months'] }} شهر)</span>@endif</td>
                                </tr>
                                <tr>
                                    <th class="text-body-secondary align-top py-2">بداية القسط</th>
                                    <td class="py-2 font-monospace">{{ $sale->installment_start_date?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-body-secondary align-top py-2">عدد الأقساط</th>
                                    <td class="py-2">{{ (int) ($plan['installments_count'] ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <th class="text-body-secondary align-top py-2">قيمة القسط</th>
                                    <td class="py-2 font-monospace">{{ number_format((float) ($plan['installment_amount'] ?? $plan['monthly_installment'] ?? 0), 2) }} ج.م</td>
                                </tr>
                                <tr>
                                    <th class="text-body-secondary align-top py-2">متبقي بعد المقدم</th>
                                    <td class="py-2 font-monospace">{{ number_format((float) ($plan['remaining_amount'] ?? 0), 2) }} ج.م</td>
                                </tr>
                                @if (is_array($secondaryList) && count($secondaryList) > 0)
                                    <tr>
                                        <th class="text-body-secondary align-top py-2">دفعات ثانوية</th>
                                        <td class="py-2 small">{{ count($secondaryList) }} بندًا — <span class="font-monospace">{{ number_format($secondaryTotal, 2) }}</span> ج.م</td>
                                    </tr>
                                    <tr>
                                        <th class="text-body-secondary align-top py-2">أساس الأقساط</th>
                                        <td class="py-2 font-monospace">{{ number_format((float) ($plan['installment_base_for_schedule'] ?? max(0, (float) ($plan['remaining_amount'] ?? 0) - $secondaryTotal)), 2) }} ج.م</td>
                                    </tr>
                                @endif
                            @endif
                            <tr>
                                <th class="text-body-secondary align-top py-2">تاريخ البيعة</th>
                                <td class="py-2 font-monospace">{{ $sale->sale_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                        <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3 pb-2 border-bottom border-secondary-subtle">العميل والوسيط</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                            <tr>
                                <th class="text-body-secondary align-top py-2" style="width: 40%">العميل</th>
                                <td class="py-2 fw-medium">{{ $sale->client?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">الهاتف</th>
                                <td class="py-2 font-monospace">{{ $sale->client?->phone ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">البريد</th>
                                <td class="py-2 text-break">{{ $sale->client?->email ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">الرقم القومي</th>
                                <td class="py-2 font-monospace">{{ $sale->client?->national_id ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-body-secondary align-top py-2">البروكر</th>
                                <td class="py-2">{{ $sale->broker_name ?: '—' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($sale->contract)
                    <div class="col-12">
                        <div class="rounded-3 border p-3">
                            <h6 class="small text-uppercase text-body-secondary fw-semibold mb-3">العقد</h6>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small text-body-secondary mb-1">
                                    <span>التسديد من إجمالي العقد</span>
                                    <span class="font-monospace fw-semibold">{{ $contractProgressPct }}%</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 10px;" role="progressbar" aria-valuenow="{{ $contractProgressPct }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-success" style="width: {{ $contractProgressPct }}%"></div>
                                </div>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-md-3"><span class="text-body-secondary">الإجمالي</span><br><span class="font-monospace fw-medium">{{ number_format($stats['contract_total'], 2) }}</span> ج.م</div>
                                <div class="col-md-3"><span class="text-body-secondary">المدفوع</span><br><span class="font-monospace text-success-emphasis">{{ number_format($stats['contract_paid'], 2) }}</span> ج.م</div>
                                <div class="col-md-3"><span class="text-body-secondary">المتبقي</span><br><span class="font-monospace fw-semibold">{{ number_format($stats['contract_remaining'], 2) }}</span> ج.م</div>
                                <div class="col-md-3"><span class="text-body-secondary">الفترة</span><br><span class="font-monospace">{{ $sale->contract->start_date?->format('Y-m-d') ?? '—' }}</span> → <span class="font-monospace">{{ $sale->contract->end_date?->format('Y-m-d') ?? '—' }}</span></div>
                            </div>
                        </div>
                    </div>
                @endif
                @if (filled($sale->notes))
                    <div class="col-12">
                        <div class="rounded-3 border bg-body-secondary bg-opacity-25 p-3">
                            <h6 class="small text-uppercase text-body-secondary fw-semibold mb-2">ملاحظات</h6>
                            <p class="mb-0 small">{{ $sale->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($sale->payment_type === 'cash')
        <div class="alert alert-success border-0 shadow-sm mb-3">
            <i class="fa-solid fa-circle-check ms-1"></i> هذه البيعة <strong>كاش</strong>؛ لا يوجد جدول أقساط.
        </div>
    @elseif (count($installmentRows) > 0)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-body-secondary border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div>
                    <h5 class="mb-0">جدول الاستحقاقات</h5>
                    <div class="small text-body-secondary mt-1">أقساط منتظمة ودفعات ثانوية مرتبة حسب التاريخ</div>
                </div>
                <span class="badge text-bg-secondary fs-6">المجموع: {{ number_format($stats['scheduled_total'], 2) }} ج.م</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3 border-start border-4 border-secondary ps-3">
                    عمود «المسدد» يوزّع مجموع <strong>تحصيلات العقد</strong> بالتسلسل (FIFO) على البنود — للمتابعة السريعة.
                </p>
                <div class="table-responsive rounded-3 border">
                    <table class="table table-sm table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">البند</th>
                            <th scope="col">الاستحقاق</th>
                            <th scope="col" class="text-end">المبلغ</th>
                            <th scope="col" class="text-end">المسدد</th>
                            <th scope="col" class="text-end">المتبقي</th>
                            <th scope="col">الحالة</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($installmentRows as $row)
                            <tr>
                                <td class="font-monospace">{{ $row['number'] }}</td>
                                <td>
                                    @if (($row['kind'] ?? 'installment') === 'secondary')
                                        <span class="badge text-bg-info">دفعة ثانوية</span>
                                        <span class="small ms-1">{{ $row['label'] ?? '—' }}</span>
                                    @else
                                        <span class="badge text-bg-light text-dark border">قسط</span>
                                    @endif
                                </td>
                                <td class="font-monospace">{{ $row['due_date']->format('Y-m-d') }}</td>
                                <td class="text-end font-monospace">{{ number_format($row['amount'], 2) }}</td>
                                <td class="text-end font-monospace">{{ number_format($row['paid'], 2) }}</td>
                                <td class="text-end font-monospace">{{ number_format($row['balance'], 2) }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $row['status'] === 'مسدد' ? 'success' : ($row['status'] === 'جزئي' ? 'warning' : 'secondary') }}">{{ $row['status'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="table-group-divider fw-semibold table-light">
                        <tr>
                            <td colspan="3">الإجمالي</td>
                            <td class="text-end font-monospace">{{ number_format(collect($installmentRows)->sum('amount'), 2) }}</td>
                            <td class="text-end font-monospace">{{ number_format(collect($installmentRows)->sum('paid'), 2) }}</td>
                            <td class="text-end font-monospace">{{ number_format(collect($installmentRows)->sum('balance'), 2) }}</td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @elseif ($sale->payment_type === 'installment')
        <div class="alert alert-warning border-0 shadow-sm mb-3">
            تقسيط بدون بيانات خطة أقساط كافية (عدد الأقساط أو تاريخ البدء). يمكنك
            <a href="{{ route('sales.edit', [$project, $sale]) }}" class="alert-link">تعديل البيعة</a> لإكمال الخطة.
        </div>
    @endif

    @if ($sale->contract && $revenues->isNotEmpty())
        <div class="card shadow-sm border-0">
            <div class="card-header bg-body-secondary border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <div>
                    <h5 class="mb-0">حركات التحصيل</h5>
                    <div class="small text-body-secondary mt-1">مسجلة على عقد هذه البيعة</div>
                </div>
                <span class="badge text-bg-primary fs-6">{{ $revenues->count() }} حركة</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">التاريخ</th>
                            <th scope="col" class="text-end">المبلغ</th>
                            <th scope="col">طريقة الدفع</th>
                            <th scope="col">ملاحظات</th>
                            <th scope="col" class="text-end"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($revenues as $rev)
                            @php($revPm = ['cash' => 'نقدي', 'bank_transfer' => 'تحويل بنكي', 'check' => 'شيك'][$rev->payment_method ?? ''] ?? $rev->payment_method)
                            <tr>
                                <td class="font-monospace">{{ $rev->id }}</td>
                                <td class="font-monospace">{{ $rev->paid_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="text-end font-monospace fw-medium">{{ number_format((float) $rev->amount, 2) }}</td>
                                <td>{{ $revPm ?: '—' }}</td>
                                <td class="small text-break">{{ $rev->notes ?: '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('revenues.show', [$project, $rev]) }}" class="btn btn-outline-secondary btn-sm">عرض</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="table-group-divider fw-semibold table-light">
                        <tr>
                            <td colspan="2">المجموع</td>
                            <td class="text-end font-monospace">{{ number_format($stats['revenues_sum'], 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @elseif ($sale->contract)
        <div class="card border-secondary-subtle shadow-sm">
            <div class="card-body text-muted small mb-0">لا توجد تحصيلات مسجلة على عقد هذه البيعة بعد.</div>
        </div>
    @endif
@endsection
