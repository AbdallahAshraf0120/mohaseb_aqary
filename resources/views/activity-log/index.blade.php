@extends('layouts.admin')

@section('content')
    <div class="card app-surface border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h4 class="mb-2 fw-semibold d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width:2.75rem;height:2.75rem">
                            <i class="fa-solid fa-clipboard-list"></i>
                        </span>
                        سجل النشاط
                    </h4>
                    <p class="text-body-secondary small mb-0" style="max-width: 44rem;">
                        عرض زمني للعمليات المهمة: إضافات، تعديلات، حذف، مصادقة، وتحويلات.
                        @unless ($includeBrowsing)
                            <span class="text-body-secondary">تصفح الصفحات مخفي افتراضيًا.</span>
                        @endunless
                    </p>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-sm-6 col-lg-4">
                    <div class="dashboard-stat-tile h-100 mb-0">
                        <div class="tile-icon bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-calendar-day"></i></div>
                        <div>
                            <div class="small text-body-secondary">كل نشاط اليوم</div>
                            <div class="fs-4 fw-bold lh-1">{{ number_format($stats['today_total'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="dashboard-stat-tile h-100 mb-0">
                        <div class="tile-icon bg-success bg-opacity-10 text-success"><i class="fa-solid fa-bolt"></i></div>
                        <div>
                            <div class="small text-body-secondary">عمليات مهمة اليوم</div>
                            <div class="fs-4 fw-bold lh-1">{{ number_format($stats['today_actions'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="dashboard-stat-tile h-100 mb-0">
                        <div class="tile-icon bg-dark bg-opacity-10 text-dark"><i class="fa-solid fa-key"></i></div>
                        <div>
                            <div class="small text-body-secondary">مصادقة اليوم</div>
                            <div class="fs-4 fw-bold lh-1">{{ number_format($stats['today_auth'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header bg-transparent py-3">
            <span class="fw-semibold"><i class="fa-solid fa-filter ms-1 text-body-secondary"></i> تصفية</span>
        </div>
        <div class="card-body pt-0">
            <form method="get" action="{{ route('activity-log.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label small fw-semibold text-body-secondary mb-1">بحث</label>
                    <input type="search" name="q" class="form-control" value="{{ $q }}"
                           placeholder="وصف العملية، مسار، مستخدم…">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small fw-semibold text-body-secondary mb-1">نوع السجل</label>
                    <select name="log_name" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($logNames as $ln)
                            <option value="{{ $ln }}" @selected($filterLogName === $ln)>
                                {{ $logNameLabels[$ln] ?? $ln }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small fw-semibold text-body-secondary mb-1">المستخدم</label>
                    <select name="causer_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $filterCauserId === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-semibold text-body-secondary mb-1">من تاريخ</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-semibold text-body-secondary mb-1">إلى تاريخ</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="include_browsing"
                               name="include_browsing" value="1" @checked($includeBrowsing)>
                        <label class="form-check-label" for="include_browsing">
                            إظهار كل النشاط (يشمل تصفح الصفحات)
                        </label>
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-check ms-1"></i> تطبيق
                    </button>
                    <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary">مسح التصفية</a>
                    <span class="align-self-center small text-body-secondary ms-auto">
                        النتيجة: {{ number_format($activities->total()) }} سجل
                    </span>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold">الخط الزمني</span>
            <span class="small text-body-secondary">
                عرض {{ $activities->firstItem() ?? 0 }}–{{ $activities->lastItem() ?? 0 }}
                من {{ number_format($activities->total()) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="activity-timeline list-group list-group-flush">
                @forelse ($activities as $item)
                    @php
                        /** @var \Spatie\Activitylog\Models\Activity $row */
                        $row = $item['row'];
                        $v = $item['view'];
                    @endphp
                    <div class="list-group-item px-4 py-3 activity-timeline-item">
                        <div class="d-flex flex-wrap gap-3 align-items-start">
                            <div class="activity-time text-center flex-shrink-0" style="min-width: 5.5rem;">
                                <div class="small text-body-secondary font-monospace" dir="ltr">{{ $v['time_date'] }}</div>
                                <div class="fw-semibold font-monospace" dir="ltr">{{ $v['time_clock'] }}</div>
                                <div class="small text-muted">{{ $v['time_relative'] }}</div>
                            </div>

                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                                    <span class="badge rounded-pill {{ $v['log_badge'] }}">{{ $v['log_label'] }}</span>
                                    @if ($v['is_http'] && $v['method'])
                                        <span class="badge rounded-pill {{ $v['method_badge'] }} font-monospace">{{ $v['method'] }}</span>
                                    @elseif (! $v['is_http'] && $row->event)
                                        <span class="badge rounded-pill text-bg-light border">{{ $v['event_label'] }}</span>
                                    @endif
                                    @if ($v['is_http'] && $v['status'] !== null)
                                        <span class="badge rounded-pill {{ $v['status_badge'] }}">{{ $v['status'] }}</span>
                                    @endif
                                    @if ($v['is_http'] && $v['module'] !== '—')
                                        <span class="badge rounded-pill text-bg-light border">{{ $v['module'] }}</span>
                                    @endif
                                </div>

                                <div class="fw-semibold fs-6 text-body mb-1">{{ $v['headline'] }}</div>

                                <div class="small text-body-secondary d-flex flex-wrap gap-x-3 gap-y-1">
                                    <span>
                                        <i class="fa-regular fa-user ms-1 opacity-75"></i>
                                        @if ($row->causer)
                                            {{ $row->causer->name }}
                                            @if ($row->causer->role ?? null)
                                                <span class="badge text-bg-light border ms-1">{{ $row->causer->role }}</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </span>
                                    <span>
                                        <i class="fa-solid fa-cube ms-1 opacity-75"></i>
                                        {{ $v['subject_label'] }}
                                        @if ($row->subject_id)
                                            <span class="font-monospace">#{{ $row->subject_id }}</span>
                                        @endif
                                        @if ($v['subject_name'])
                                            — {{ $v['subject_name'] }}
                                        @endif
                                    </span>
                                    @if ($v['ip'])
                                        <span class="font-monospace" dir="ltr">
                                            <i class="fa-solid fa-network-wired ms-1 opacity-75"></i> {{ $v['ip'] }}
                                        </span>
                                    @endif
                                </div>

                                @if (count($v['changed_fields']) > 0)
                                    <details class="activity-details mt-2 border rounded-3 p-2 bg-body-tertiary bg-opacity-40">
                                        <summary class="fw-semibold text-primary small user-select-none">
                                            التغييرات ({{ count($v['changed_fields']) }})
                                        </summary>
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                <tr class="small text-body-secondary">
                                                    <th>الحقل</th>
                                                    <th>قبل</th>
                                                    <th>بعد</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($v['changed_fields'] as $change)
                                                    <tr class="small">
                                                        <td class="fw-semibold">{{ $change['label'] }}</td>
                                                        <td class="text-danger-emphasis text-break">{{ $change['old'] }}</td>
                                                        <td class="text-success-emphasis text-break">{{ $change['new'] }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                @elseif ($v['is_http'])
                                    <details class="activity-details mt-2 border rounded-3 p-2 bg-body-tertiary bg-opacity-40">
                                        <summary class="fw-semibold text-primary small user-select-none">ملخص الطلب</summary>
                                        <ul class="small mb-0 mt-2 ps-3">
                                            @if ($v['path'])
                                                <li><span class="text-body-secondary">المسار:</span> <span class="font-monospace" dir="ltr">{{ $v['path'] }}</span></li>
                                            @endif
                                            @if ($v['route'])
                                                <li><span class="text-body-secondary">المعرّف:</span> <span class="font-monospace" dir="ltr">{{ $v['route'] }}</span></li>
                                            @endif
                                            @if ($v['status'] !== null)
                                                <li><span class="text-body-secondary">الحالة:</span> {{ $v['status'] }}</li>
                                            @endif
                                            @if ($v['ip'])
                                                <li><span class="text-body-secondary">IP:</span> <span class="font-monospace" dir="ltr">{{ $v['ip'] }}</span></li>
                                            @endif
                                        </ul>
                                        <details class="mt-2">
                                            <summary class="small text-body-secondary user-select-none">تفاصيل تقنية</summary>
                                            <div class="d-flex justify-content-end mt-1">
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 activity-copy-json"
                                                        data-json="{{ e($v['technical_json']) }}">نسخ JSON</button>
                                            </div>
                                            <pre class="activity-json-pre mb-0 mt-2 rounded-3" dir="ltr">{{ $v['technical_json'] }}</pre>
                                        </details>
                                    </details>
                                @elseif ($v['has_technical'])
                                    <details class="activity-details mt-2 border rounded-3 p-2 bg-body-tertiary bg-opacity-40">
                                        <summary class="fw-semibold text-primary small user-select-none d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                            <span>تفاصيل تقنية</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 activity-copy-json"
                                                    data-json="{{ e($v['technical_json']) }}">نسخ JSON</button>
                                        </summary>
                                        <pre class="activity-json-pre mb-0 mt-2 rounded-3" dir="ltr">{{ $v['technical_json'] }}</pre>
                                    </details>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 px-3">
                        <div class="text-body-secondary mb-2"><i class="fa-regular fa-folder-open fa-2x opacity-50"></i></div>
                        <div class="fw-semibold">لا توجد سجلات مطابقة</div>
                        <div class="small text-body-secondary mt-1">
                            جرّب توسيع الفترة أو تفعيل «إظهار كل النشاط».
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
        @if ($activities->hasPages())
            <div class="card-footer bg-transparent border-0 pt-0 pb-4 px-4">
                {{ $activities->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <style>
        .activity-timeline-item + .activity-timeline-item {
            border-top: 1px solid var(--bs-border-color-translucent);
        }
        .activity-details summary { cursor: pointer; list-style: none; }
        .activity-details summary::-webkit-details-marker { display: none; }
        .activity-json-pre {
            font-size: 0.7rem;
            line-height: 1.45;
            padding: 0.75rem 1rem;
            margin: 0;
            background: var(--bs-dark);
            color: var(--bs-gray-100);
            overflow: auto;
            max-height: 16rem;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .gap-x-3 { column-gap: 1rem; }
        .gap-y-1 { row-gap: 0.25rem; }
    </style>

    <script>
        (function () {
            document.querySelectorAll('.activity-copy-json').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var raw = btn.getAttribute('data-json');
                    if (!raw) return;
                    try {
                        navigator.clipboard.writeText(raw);
                        btn.textContent = 'تم النسخ';
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-success', 'text-white');
                        setTimeout(function () {
                            btn.textContent = 'نسخ JSON';
                            btn.classList.add('btn-outline-secondary');
                            btn.classList.remove('btn-success', 'text-white');
                        }, 1600);
                    } catch (err) {}
                });
            });
        })();
    </script>
@endsection
