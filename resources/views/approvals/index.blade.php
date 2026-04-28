@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => number_format($n, 2, '.', ',');
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-semibold">طلبات الاعتماد</h4>
            <p class="small text-body-secondary mb-0">اعتماد أو رفض العمليات المعلقة قبل احتسابها في الصندوق والتقارير.</p>
        </div>
        <a href="{{ route('cashbox.index', [$project]) }}" class="btn btn-outline-secondary btn-sm">الصندوق</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-12">
            <div class="card app-surface">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <h5 class="mb-0 fw-semibold">الملخص</h5>
                        <div class="small text-body-secondary">
                            التحصيلات: <span class="fw-semibold">{{ $counts['revenues'] }}</span> —
                            المصروفات: <span class="fw-semibold">{{ $counts['expenses'] }}</span> —
                            المبيعات: <span class="fw-semibold">{{ $counts['sales'] }}</span> —
                            سداد الذمم: <span class="fw-semibold">{{ $counts['debt_payments'] }}</span> —
                            حركات يدوية: <span class="fw-semibold">{{ $counts['manual_treasury'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <p class="small text-body-secondary mb-0">اختر أي عملية من القوائم أدناه ثم اضغط «اعتماد» أو «رفض».</p>
                </div>
            </div>
        </div>

        @php
            $sections = [
                [
                    'key' => 'revenues',
                    'title' => 'تحصيلات معلّقة',
                    'rows' => $pending['revenues'],
                    'type' => 'revenue',
                ],
                [
                    'key' => 'expenses',
                    'title' => 'مصروفات معلّقة',
                    'rows' => $pending['expenses'],
                    'type' => 'expense',
                ],
                [
                    'key' => 'sales',
                    'title' => 'مبيعات معلّقة (مقدم البيع)',
                    'rows' => $pending['sales'],
                    'type' => 'sale',
                ],
                [
                    'key' => 'debt_payments',
                    'title' => 'سداد ذمم معلّق من الصندوق',
                    'rows' => $pending['debt_payments'],
                    'type' => 'debt_payment',
                ],
                [
                    'key' => 'manual_treasury',
                    'title' => 'حركات صندوق يدوية معلّقة',
                    'rows' => $pending['manual_treasury'],
                    'type' => 'manual_treasury',
                ],
            ];
        @endphp

        @foreach ($sections as $section)
            <div class="col-12">
                <div class="card app-surface">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h5 class="mb-0 fw-semibold">{{ $section['title'] }}</h5>
                            <span class="badge text-bg-warning">معلق</span>
                        </div>
                        <p class="small text-body-secondary mb-0 mt-2">
                            آخر 25 سجل — الإجمالي الكلي المعلق: {{ $counts[$section['key']] }}
                        </p>
                    </div>
                    <div class="card-body p-0 pt-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th class="text-body-secondary fw-semibold" style="width: 5rem">#</th>
                                    <th class="text-body-secondary fw-semibold">الوصف</th>
                                    <th class="text-body-secondary fw-semibold text-end">المبلغ</th>
                                    <th class="text-body-secondary fw-semibold text-end" style="width: 13rem">إجراءات</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($section['rows'] as $row)
                                    <tr>
                                        <td class="small font-monospace text-body-secondary">#{{ $row->id }}</td>
                                        <td class="small">
                                            @if ($section['key'] === 'revenues')
                                                {{ $row->category ?? 'تحصيل' }} — {{ $row->client?->name ?? '—' }}
                                            @elseif ($section['key'] === 'expenses')
                                                {{ $row->category ?? 'مصروف' }} — {{ $row->description ?? '—' }}
                                            @elseif ($section['key'] === 'sales')
                                                بيعة #{{ $row->id }} — مقدم: {{ $fmt((float) ($row->down_payment ?? 0)) }}
                                            @elseif ($section['key'] === 'debt_payments')
                                                ذمة #{{ $row->debt_id }} — {{ $row->debt?->creditor_name ?? '—' }} — {{ $row->note ?? '—' }}
                                            @else
                                                {{ $row->description ?? '—' }}
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-semibold">
                                            @php
                                                $amount = (float) ($row->amount ?? 0);
                                                if ($section['key'] === 'sales') $amount = (float) ($row->down_payment ?? 0);
                                            @endphp
                                            {{ $fmt($amount) }}
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <form method="post" action="{{ route('approvals.approve', [$project, $section['type'], $row->id]) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success">اعتماد</button>
                                                </form>
                                                <form method="post" action="{{ route('approvals.reject', [$project, $section['type'], $row->id]) }}" class="d-flex gap-1">
                                                    @csrf
                                                    <input type="hidden" name="reason" value="">
                                                    <button class="btn btn-sm btn-outline-danger">رفض</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-body-secondary">لا يوجد سجلات معلّقة</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

