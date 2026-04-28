@extends('layouts.admin')

@section('content')
    @php
        $fmt = fn (float $n): string => number_format($n, 2, '.', ',');
        $totalPending = (int) (($counts['revenues'] ?? 0) + ($counts['expenses'] ?? 0) + ($counts['sales'] ?? 0) + ($counts['debt_payments'] ?? 0) + ($counts['manual_treasury'] ?? 0));
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1 fw-semibold">طلبات الاعتماد</h4>
            <p class="small text-body-secondary mb-0">اعتماد أو رفض العمليات المعلقة قبل احتسابها في الصندوق والتقارير.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('cashbox.index', [$project]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-vault ms-1"></i> الصندوق
            </a>
            <a href="{{ route('reports.index', [$project]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-chart-line ms-1"></i> التقارير
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-md-6">
            <div class="card app-surface h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="text-body-secondary small mb-1">إجمالي المعلّق</div>
                            <div class="fs-2 fw-bold font-monospace">{{ $totalPending }}</div>
                        </div>
                        <span class="rounded-3 p-2 text-bg-warning"><i class="fa-solid fa-hourglass-half"></i></span>
                    </div>
                    <p class="small text-body-secondary mb-0 mt-3">عمليات بانتظار اعتماد الأدمن.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-9 col-md-6">
            <div class="card app-surface h-100">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                                <div class="small text-body-secondary mb-1">تحصيلات</div>
                                <div class="fw-bold font-monospace">{{ (int) ($counts['revenues'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                                <div class="small text-body-secondary mb-1">مصروفات</div>
                                <div class="fw-bold font-monospace">{{ (int) ($counts['expenses'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                                <div class="small text-body-secondary mb-1">مبيعات</div>
                                <div class="fw-bold font-monospace">{{ (int) ($counts['sales'] ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 h-100">
                                <div class="small text-body-secondary mb-1">ذمم/يدوي</div>
                                <div class="fw-bold font-monospace">{{ (int) (($counts['debt_payments'] ?? 0) + ($counts['manual_treasury'] ?? 0)) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="small text-body-secondary mt-3">
                        نصيحة: يمكنك مراجعة “الصندوق” و“التقارير” للتأكد أن المعلّق لا يدخل ضمن الأرصدة.
                    </div>
                </div>
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
                    'icon' => 'fa-money-bill-trend-up',
                ],
                [
                    'key' => 'expenses',
                    'title' => 'مصروفات معلّقة',
                    'rows' => $pending['expenses'],
                    'type' => 'expense',
                    'icon' => 'fa-money-bill-wave',
                ],
                [
                    'key' => 'sales',
                    'title' => 'مبيعات معلّقة (مقدم البيع)',
                    'rows' => $pending['sales'],
                    'type' => 'sale',
                    'icon' => 'fa-cart-shopping',
                ],
                [
                    'key' => 'debt_payments',
                    'title' => 'سداد ذمم معلّق من الصندوق',
                    'rows' => $pending['debt_payments'],
                    'type' => 'debt_payment',
                    'icon' => 'fa-scale-balanced',
                ],
                [
                    'key' => 'manual_treasury',
                    'title' => 'حركات صندوق يدوية معلّقة',
                    'rows' => $pending['manual_treasury'],
                    'type' => 'manual_treasury',
                    'icon' => 'fa-vault',
                ],
            ];
        @endphp

        <div class="card app-surface">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-1 fw-semibold">قائمة الطلبات</h5>
                        <p class="small text-body-secondary mb-0">مرتبة حسب آخر 25 سجل لكل نوع.</p>
                    </div>
                    <span class="badge text-bg-warning">معلق</span>
                </div>
            </div>
            <div class="card-body px-4 pt-3 pb-4">
                <ul class="nav nav-pills gap-2 flex-wrap" id="approvalTabs" role="tablist">
                    @foreach ($sections as $section)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link @if($loop->first) active @endif"
                                    id="tab-{{ $section['key'] }}"
                                    data-bs-toggle="tab"
                                    data-bs-target="#pane-{{ $section['key'] }}"
                                    type="button" role="tab"
                                    aria-controls="pane-{{ $section['key'] }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                <i class="fa-solid {{ $section['icon'] }} ms-1"></i>
                                {{ $section['title'] }}
                                <span class="badge text-bg-light text-body-secondary ms-1">{{ (int) ($counts[$section['key']] ?? 0) }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="tab-content">
                @foreach ($sections as $section)
                    <div class="tab-pane fade @if($loop->first) show active @endif" id="pane-{{ $section['key'] }}" role="tabpanel" aria-labelledby="tab-{{ $section['key'] }}">
                        <div class="px-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="small text-body-secondary">
                                آخر 25 سجل — الإجمالي الكلي المعلق: <span class="fw-semibold">{{ (int) ($counts[$section['key']] ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th class="text-body-secondary fw-semibold" style="width: 5rem">#</th>
                                    <th class="text-body-secondary fw-semibold">الوصف</th>
                                    <th class="text-body-secondary fw-semibold text-end">المبلغ</th>
                                    <th class="text-body-secondary fw-semibold text-end" style="width: 14rem">إجراءات</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($section['rows'] as $row)
                                    <tr>
                                        <td class="small font-monospace text-body-secondary">#{{ $row->id }}</td>
                                        <td class="small">
                                            @if ($section['key'] === 'revenues')
                                                <div class="fw-semibold">{{ $row->category ?? 'تحصيل' }}</div>
                                                <div class="text-body-secondary">{{ $row->client?->name ?? '—' }}</div>
                                            @elseif ($section['key'] === 'expenses')
                                                <div class="fw-semibold">{{ $row->category ?? 'مصروف' }}</div>
                                                <div class="text-body-secondary">{{ $row->description ?? '—' }}</div>
                                            @elseif ($section['key'] === 'sales')
                                                <div class="fw-semibold">بيعة #{{ $row->id }}</div>
                                                <div class="text-body-secondary">{{ $row->property?->name ?? '—' }} / {{ $row->client?->name ?? '—' }}</div>
                                            @elseif ($section['key'] === 'debt_payments')
                                                <div class="fw-semibold">ذمة #{{ $row->debt_id }} — {{ $row->debt?->creditor_name ?? '—' }}</div>
                                                <div class="text-body-secondary">{{ $row->note ?? '—' }}</div>
                                            @else
                                                <div class="fw-semibold">{{ $row->type === 'revenue' ? 'قبض يدوي' : 'صرف يدوي' }}</div>
                                                <div class="text-body-secondary">{{ $row->description ?? '—' }}</div>
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
                                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                <form method="post" action="{{ route('approvals.approve', [$project, $section['type'], $row->id]) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success">
                                                        <i class="fa-solid fa-check ms-1"></i> اعتماد
                                                    </button>
                                                </form>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal"
                                                    data-approval-type="{{ $section['type'] }}"
                                                    data-approval-id="{{ $row->id }}"
                                                    data-approval-desc="@if ($section['key'] === 'revenues'){{ ($row->category ?? 'تحصيل') . ' — ' . ($row->client?->name ?? '—') }}@elseif ($section['key'] === 'expenses'){{ ($row->category ?? 'مصروف') . ' — ' . ($row->description ?? '—') }}@elseif ($section['key'] === 'sales'){{ 'بيعة #' . $row->id . ' — ' . ($row->property?->name ?? '—') . ' / ' . ($row->client?->name ?? '—') }}@elseif ($section['key'] === 'debt_payments'){{ 'ذمة #' . $row->debt_id . ' — ' . ($row->debt?->creditor_name ?? '—') }}@else{{ ($row->type === 'revenue' ? 'قبض يدوي' : 'صرف يدوي') . ' — ' . ($row->description ?? '—') }}@endif"
                                                >
                                                    <i class="fa-solid fa-xmark ms-1"></i> رفض
                                                </button>
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
                        <div class="px-4 py-3 border-top small text-body-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span>يمكنك رفض العملية مع سبب اختياري.</span>
                            <a href="{{ route('cashbox.index', [$project]) }}" class="text-decoration-none">عرض الصندوق</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="rejectModalLabel">رفض العملية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="post" id="rejectModalForm" action="#">
                    @csrf
                    <div class="modal-body">
                        <div class="small text-body-secondary mb-2">ستقوم برفض العملية التالية:</div>
                        <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 small mb-3" id="rejectModalDesc">—</div>
                        <label class="form-label fw-semibold" for="reject-reason">سبب الرفض (اختياري)</label>
                        <textarea class="form-control" id="reject-reason" name="reason" rows="3" maxlength="500" placeholder="اكتب سبب الرفض…"></textarea>
                        <div class="form-text">السبب يساعد في المراجعة لاحقًا.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-xmark ms-1"></i> تأكيد الرفض
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('rejectModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (!button) return;

                const type = button.getAttribute('data-approval-type');
                const id = button.getAttribute('data-approval-id');
                const desc = button.getAttribute('data-approval-desc') || '—';

                const form = document.getElementById('rejectModalForm');
                const descEl = document.getElementById('rejectModalDesc');
                const reasonEl = document.getElementById('reject-reason');
                if (!form || !descEl || !reasonEl) return;

                descEl.textContent = desc;
                reasonEl.value = '';
                form.action = @json(url('/')) + '/' + @json((string) $project->id) + '/approvals/' + encodeURIComponent(type) + '/' + encodeURIComponent(id) + '/reject';
            });
        })();
    </script>
@endsection

