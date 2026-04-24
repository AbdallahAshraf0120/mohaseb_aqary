@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="العملاء" step="4" />
    <x-partials.module-kpis :items="[
        ['label' => 'عدد البيعات', 'value' => $stats['sales_count']],
        ['label' => 'إجمالي قيمة المبيعات', 'value' => number_format($stats['total_sale_price'], 2) . ' ج.م'],
        ['label' => 'إجمالي المقدمات', 'value' => number_format($stats['total_down_payment'], 2) . ' ج.م'],
        ['label' => 'إجمالي التحصيلات', 'value' => number_format($stats['total_collected_revenues'], 2) . ' ج.م'],
        ['label' => 'متبقي على العقود', 'value' => number_format($stats['total_remaining_contracts'], 2) . ' ج.م'],
    ]" />

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">بيانات العميل</h5>
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary btn-sm">رجوع للقائمة</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>الاسم:</strong> {{ $client->name }}</div>
                <div class="col-md-6"><strong>الهاتف:</strong> {{ $client->phone ?: '—' }}</div>
                <div class="col-md-6"><strong>البريد:</strong> {{ $client->email ?: '—' }}</div>
                <div class="col-md-6"><strong>الرقم القومي:</strong> {{ $client->national_id ?: '—' }}</div>
                <div class="col-md-6"><strong>تاريخ التسجيل:</strong> {{ $client->created_at?->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">المبيعات والأقساط والعقود</h5>
        </div>
        <div class="card-body">
            @forelse ($client->sales as $sale)
                @php
                    $plan = $sale->installment_plan ?? [];
                    $scheduleLabel = $sale->installmentScheduleTypeLabel();
                    $instCount = (int) ($plan['installments_count'] ?? 0);
                    $instAmount = (float) ($plan['installment_amount'] ?? $plan['monthly_installment'] ?? 0);
                    $planRemaining = (float) ($plan['remaining_amount'] ?? 0);
                    $secondaryPlan = $plan['secondary_payments'] ?? [];
                    $secondaryPlanTotal = (float) ($plan['secondary_payments_total'] ?? 0);
                    $installmentBasePlan = (float) ($plan['installment_base_for_schedule'] ?? max(0, $planRemaining - $secondaryPlanTotal));
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
                    $contract = $sale->contract;
                    $revenues = $contract?->revenues ?? collect();
                    $paidFromRevenues = (float) $revenues->sum(fn ($r) => (float) $r->amount);
                @endphp
                <div class="border rounded mb-3 overflow-hidden">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2 bg-body-secondary border-bottom">
                        <div>
                            <span class="fw-semibold">بيعة #{{ $sale->id }}</span>
                            <span class="text-body-secondary">— {{ $sale->property?->name ?? '—' }}</span>
                            <span class="badge text-bg-primary ms-1">{{ number_format((float) $sale->sale_price, 2) }} ج.م</span>
                        </div>
                        <a href="{{ route('sales.show', [$project, $sale]) }}" class="btn btn-sm btn-outline-primary">تفاصيل البيعة</a>
                    </div>
                    <div class="p-3">
                        <div class="row g-2 small mb-3">
                            <div class="col-md-4"><strong>الدور / الوحدة:</strong> {{ $floorLabel }}</div>
                            <div class="col-md-4"><strong>نموذج الشقة:</strong> {{ $sale->apartment_model ?: '—' }}</div>
                            <div class="col-md-4"><strong>تاريخ البيعة:</strong> {{ $sale->sale_date?->format('Y-m-d') ?? '—' }}</div>
                            <div class="col-md-4"><strong>نوع السداد:</strong> {{ $sale->payment_type === 'cash' ? 'كاش' : 'تقسيط' }}</div>
                            <div class="col-md-4"><strong>المقدم:</strong> {{ number_format((float) $sale->down_payment, 2) }} ج.م</div>
                            <div class="col-md-4"><strong>البروكر:</strong> {{ $sale->broker_name ?: '—' }}</div>
                        </div>

                        @if ($sale->payment_type === 'installment' && $instCount > 0)
                            <h6 class="text-body-secondary border-top pt-3 mb-2">خطة التقسيط</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered mb-0 align-middle">
                                    <tbody>
                                    <tr>
                                        <th class="w-25 bg-body-secondary">مدة التقسيط (شهور)</th>
                                        <td>{{ $sale->installment_months ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-secondary">نظام القسط</th>
                                        <td>{{ $scheduleLabel }} @if(!empty($plan['interval_months']))<span class="text-muted">(كل {{ (int) $plan['interval_months'] }} شهر)</span>@endif</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-secondary">بداية القسط</th>
                                        <td>{{ $sale->installment_start_date?->format('Y-m-d') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-secondary">عدد الأقساط</th>
                                        <td><strong>{{ $instCount }}</strong> قسط</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-secondary">قيمة القسط (متساوية)</th>
                                        <td><strong>{{ number_format($instAmount, 2) }}</strong> ج.م</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-body-secondary">المتبقي حسب الخطة (بعد المقدم)</th>
                                        <td>{{ number_format($planRemaining, 2) }} ج.م</td>
                                    </tr>
                                    @if (is_array($secondaryPlan) && count($secondaryPlan) > 0)
                                        <tr>
                                            <th class="bg-body-secondary">دفعات ثانوية</th>
                                            <td>{{ count($secondaryPlan) }} بندًا — {{ number_format($secondaryPlanTotal, 2) }} ج.م</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-body-secondary">المقسّط على الأقساط المنتظمة</th>
                                            <td>{{ number_format($installmentBasePlan, 2) }} ج.م</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th class="bg-body-secondary">إجمالي الأقساط المتوقع</th>
                                        <td>{{ number_format($instCount * $instAmount, 2) }} ج.م <span class="text-muted small">(عدد × قيمة القسط)</span></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        @elseif ($sale->payment_type === 'installment')
                            <p class="text-muted small mb-3">تقسيط بدون تفاصيل خطة محفوظة (بيانات قديمة أو غير مكتملة).</p>
                        @endif

                        @if ($contract)
                            <h6 class="text-body-secondary border-top pt-3 mb-2">العقد المرتبط</h6>
                            <div class="row g-2 small mb-2">
                                <div class="col-md-4"><strong>إجمالي العقد:</strong> {{ number_format((float) $contract->total_price, 2) }} ج.م</div>
                                <div class="col-md-4"><strong>المدفوع (حسب العقد):</strong> {{ number_format((float) $contract->paid_amount, 2) }} ج.م</div>
                                <div class="col-md-4"><strong>المتبقي (حسب العقد):</strong> {{ number_format((float) $contract->remaining_amount, 2) }} ج.م</div>
                                <div class="col-md-6"><strong>من:</strong> {{ $contract->start_date?->format('Y-m-d') ?? '—' }}
                                    <strong class="ms-2">إلى:</strong> {{ $contract->end_date?->format('Y-m-d') ?? '—' }}</div>
                                <div class="col-md-6 text-md-end">
                                    <a href="{{ route('contracts.show', [$project, $contract]) }}" class="btn btn-sm btn-outline-secondary">عرض العقد</a>
                                </div>
                            </div>

                            <h6 class="text-body-secondary mb-2">حركات التحصيل على هذا العقد</h6>
                            @if ($revenues->isNotEmpty())
                                <p class="small text-muted mb-1">عدد الحركات: <strong>{{ $revenues->count() }}</strong>
                                    — مجموع المبالغ: <strong>{{ number_format($paidFromRevenues, 2) }}</strong> ج.م</p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead>
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
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small mb-0">لا توجد تحصيلات مسجلة على هذا العقد بعد.</p>
                            @endif
                        @else
                            <p class="text-muted small mb-0 border-top pt-3">لا يوجد عقد مرتبط بهذه البيعة.</p>
                        @endif

                        @if (filled($sale->notes))
                            <div class="mt-3 small"><strong>ملاحظات البيعة:</strong> {{ $sale->notes }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">لا توجد مبيعات مسجلة لهذا العميل.</p>
            @endforelse
        </div>
    </div>
@endsection
