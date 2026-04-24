@extends('layouts.admin')

@section('content')
    @php
        $plan = $sale->installment_plan ?? [];
        $scheduleType = $plan['schedule_type'] ?? 'monthly';
        $scheduleLabel = $scheduleType === 'quarterly' ? 'كل 3 شهور' : 'شهري';
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
    @endphp

    <x-partials.module-wireflow-header label="المبيعات" step="5" />
    <x-partials.module-kpis :items="[
        ['label' => 'سعر البيعة', 'value' => number_format((float) $sale->sale_price, 2) . ' ج.م'],
        ['label' => 'المقدم', 'value' => number_format((float) $sale->down_payment, 2) . ' ج.م'],
        ['label' => 'تحصيلات على العقد', 'value' => number_format($stats['revenues_sum'], 2) . ' ج.م (' . $stats['revenues_count'] . ')'],
        ['label' => 'متبقي العقد', 'value' => number_format($stats['contract_remaining'], 2) . ' ج.م'],
        ['label' => 'أقساط مجدولة', 'value' => $stats['installment_rows'] > 0 ? $stats['installment_rows'] . ' قسط' : '—'],
    ]" />

    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0">بيعة #{{ $sale->id }}</h5>
                <div class="small text-body-secondary">{{ $sale->property?->name ?? '—' }} · {{ $floorLabel }}</div>
            </div>
            <div class="d-flex flex-wrap gap-1">
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">رجوع للمبيعات</a>
                <a href="{{ route('sales.edit', [$project, $sale]) }}" class="btn btn-outline-primary btn-sm">تعديل</a>
                @if ($sale->client)
                    <a href="{{ route('clients.show', [$project, $sale->client]) }}" class="btn btn-outline-info btn-sm">بروفايل العميل</a>
                @endif
                @if ($sale->contract)
                    <a href="{{ route('contracts.show', [$project, $sale->contract]) }}" class="btn btn-outline-dark btn-sm">العقد</a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-lg-4">
                    <h6 class="text-body-secondary border-bottom pb-1 mb-2">العقار والوحدة</h6>
                    <div><strong>العقار:</strong> {{ $sale->property?->name ?? '—' }}</div>
                    <div><strong>نوع العقار:</strong> {{ $sale->property?->property_type ?: '—' }}</div>
                    <div><strong>المنطقة:</strong> {{ $sale->property?->area?->name ?? '—' }}</div>
                    <div><strong>الأرض:</strong> {{ $sale->property?->land?->name ?? ($sale->property?->land_name ?? '—') }}</div>
                    <div><strong>الدور / الوحدة:</strong> {{ $floorLabel }}</div>
                    <div><strong>نموذج الشقة:</strong> {{ $sale->apartment_model ?: '—' }}</div>
                    @php($mf = collect($sale->property?->mushaa_floors ?? [])->map(fn ($n) => (int) $n)->filter(fn ($n) => $n >= 1))
                    @if ($mf->isNotEmpty())
                        <div><strong>أدوار مشاعة بالعقار:</strong> {{ $mf->sort()->implode('، ') }}</div>
                    @endif
                </div>
                <div class="col-lg-4">
                    <h6 class="text-body-secondary border-bottom pb-1 mb-2">المالية والسداد</h6>
                    <div><strong>سعر البيع:</strong> {{ number_format((float) $sale->sale_price, 2) }} ج.م</div>
                    <div><strong>نوع السداد:</strong> {{ $sale->payment_type === 'cash' ? 'كاش' : 'تقسيط' }}</div>
                    <div><strong>المقدم:</strong> {{ number_format((float) $sale->down_payment, 2) }} ج.م</div>
                    @if ($sale->payment_type === 'installment')
                        <div><strong>مدة التقسيط:</strong> {{ $sale->installment_months ?? '—' }} شهرًا</div>
                        <div><strong>نظام القسط:</strong> {{ $scheduleLabel }} @if(!empty($plan['interval_months']))<span class="text-muted">(كل {{ (int) $plan['interval_months'] }} شهر)</span>@endif</div>
                        <div><strong>بداية القسط:</strong> {{ $sale->installment_start_date?->format('Y-m-d') ?? '—' }}</div>
                        <div><strong>عدد الأقساط (الخطة):</strong> {{ (int) ($plan['installments_count'] ?? 0) }}</div>
                        <div><strong>قيمة القسط (متساوية):</strong> {{ number_format((float) ($plan['installment_amount'] ?? $plan['monthly_installment'] ?? 0), 2) }} ج.م</div>
                        <div><strong>المتبقي بعد المقدم (الخطة):</strong> {{ number_format((float) ($plan['remaining_amount'] ?? 0), 2) }} ج.م</div>
                    @endif
                    <div><strong>تاريخ البيعة:</strong> {{ $sale->sale_date?->format('Y-m-d') ?? '—' }}</div>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-body-secondary border-bottom pb-1 mb-2">العميل والوسيط</h6>
                    <div><strong>العميل:</strong> {{ $sale->client?->name ?? '—' }}</div>
                    <div><strong>الهاتف:</strong> {{ $sale->client?->phone ?? '—' }}</div>
                    <div><strong>البريد:</strong> {{ $sale->client?->email ?: '—' }}</div>
                    <div><strong>الرقم القومي:</strong> {{ $sale->client?->national_id ?: '—' }}</div>
                    <div><strong>البروكر:</strong> {{ $sale->broker_name ?: '—' }}</div>
                </div>
                @if ($sale->contract)
                    <div class="col-12">
                        <h6 class="text-body-secondary border-bottom pb-1 mb-2">العقد</h6>
                        <div class="row g-2 small">
                            <div class="col-md-3"><strong>إجمالي العقد:</strong> {{ number_format($stats['contract_total'], 2) }} ج.م</div>
                            <div class="col-md-3"><strong>المدفوع (حسب العقد):</strong> {{ number_format($stats['contract_paid'], 2) }} ج.م</div>
                            <div class="col-md-3"><strong>المتبقي (حسب العقد):</strong> {{ number_format($stats['contract_remaining'], 2) }} ج.م</div>
                            <div class="col-md-3"><strong>فترة العقد:</strong> {{ $sale->contract->start_date?->format('Y-m-d') ?? '—' }} → {{ $sale->contract->end_date?->format('Y-m-d') ?? '—' }}</div>
                        </div>
                    </div>
                @endif
                @if (filled($sale->notes))
                    <div class="col-12">
                        <h6 class="text-body-secondary border-bottom pb-1 mb-2">ملاحظات</h6>
                        <p class="mb-0">{{ $sale->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($sale->payment_type === 'cash')
        <div class="alert alert-success mb-3">
            <i class="fa-solid fa-circle-check ms-1"></i> هذه البيعة <strong>كاش</strong>؛ لا يوجد جدول أقساط.
        </div>
    @elseif (count($installmentRows) > 0)
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">جدول الأقساط المجدولة</h5>
                <span class="badge text-bg-secondary">مجموع الأقساط: {{ number_format($stats['scheduled_total'], 2) }} ج.م</span>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    يظهر تاريخ كل استحقاق ومبلغه. عمود «المسدد» يوزّع مجموع <strong>تحصيلات العقد</strong> بالتسلسل (FIFO) على الأقساط — للمتابعة السريعة وليس بالضرورة مطابقة لأمر دفع محدد إن وُجدت حركات مركبة.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>تاريخ الاستحقاق</th>
                            <th class="text-end">مبلغ القسط</th>
                            <th class="text-end">المسدد (تقديري)</th>
                            <th class="text-end">متبقي القسط</th>
                            <th>الحالة</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($installmentRows as $row)
                            <tr>
                                <td>{{ $row['number'] }}</td>
                                <td>{{ $row['due_date']->format('Y-m-d') }}</td>
                                <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['paid'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['balance'], 2) }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $row['status'] === 'مسدد' ? 'success' : ($row['status'] === 'جزئي' ? 'warning' : 'secondary') }}">{{ $row['status'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="table-group-divider fw-semibold">
                        <tr>
                            <td colspan="2">الإجمالي</td>
                            <td class="text-end">{{ number_format(collect($installmentRows)->sum('amount'), 2) }}</td>
                            <td class="text-end">{{ number_format(collect($installmentRows)->sum('paid'), 2) }}</td>
                            <td class="text-end">{{ number_format(collect($installmentRows)->sum('balance'), 2) }}</td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @elseif ($sale->payment_type === 'installment')
        <div class="alert alert-warning mb-3">
            تقسيط بدون بيانات خطة أقساط كافية (عدد الأقساط أو تاريخ البدء). يمكنك <a href="{{ route('sales.edit', [$project, $sale]) }}">تعديل البيعة</a> لإكمال الخطة.
        </div>
    @endif

    @if ($sale->contract && $revenues->isNotEmpty())
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">حركات التحصيل الفعلية</h5>
                <span class="badge text-bg-primary">{{ $revenues->count() }} حركة</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th class="text-end">المبلغ</th>
                            <th>طريقة الدفع</th>
                            <th>ملاحظات</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($revenues as $rev)
                            <tr>
                                <td>{{ $rev->id }}</td>
                                <td>{{ $rev->paid_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="text-end">{{ number_format((float) $rev->amount, 2) }}</td>
                                <td>{{ $rev->payment_method ?? '—' }}</td>
                                <td class="small">{{ $rev->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="table-group-divider fw-semibold">
                        <tr>
                            <td colspan="2">المجموع</td>
                            <td class="text-end">{{ number_format($stats['revenues_sum'], 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @elseif ($sale->contract)
        <div class="card border-secondary-subtle">
            <div class="card-body text-muted small mb-0">لا توجد تحصيلات مسجلة على عقد هذه البيعة بعد.</div>
        </div>
    @endif
@endsection
