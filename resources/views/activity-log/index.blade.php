@extends('layouts.admin')

@section('content')
    @php
        $logBadgeClass = static function (?string $name): string {
            return match ($name) {
                'http' => 'text-bg-primary',
                'auth' => 'text-bg-dark',
                'users' => 'text-bg-success',
                'projects' => 'text-bg-info',
                'default' => 'text-bg-secondary',
                default => 'text-bg-light text-dark border',
            };
        };
        $methodBadge = static function (?string $m): string {
            return match (strtoupper((string) $m)) {
                'GET', 'HEAD' => 'text-bg-secondary',
                'POST' => 'text-bg-primary',
                'PUT', 'PATCH' => 'text-bg-warning text-dark',
                'DELETE' => 'text-bg-danger',
                default => 'text-bg-light text-dark border',
            };
        };
    @endphp

    <div class="card app-surface border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-0">
                <div>
                    <h4 class="mb-2 fw-semibold d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width:2.75rem;height:2.75rem">
                            <i class="fa-solid fa-clipboard-list"></i>
                        </span>
                        مركز تدقيق النشاط
                    </h4>
                    <p class="text-body-secondary small mb-0" style="max-width: 42rem;">
                        سجل موحَّد للعمليات: طلبات التطبيق (<span class="font-monospace">http</span>)، تعديلات النماذج، وتسجيل الدخول.
                        البيانات الحساسة لا تُخزَّن في السجل.
                    </p>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="dashboard-stat-tile h-100 mb-0">
                        <div class="tile-icon bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-calendar-day"></i></div>
                        <div>
                            <div class="small text-body-secondary">نشاط اليوم</div>
                            <div class="fs-4 fw-bold lh-1">{{ number_format($stats['today_total'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="dashboard-stat-tile h-100 mb-0">
                        <div class="tile-icon bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-globe"></i></div>
                        <div>
                            <div class="small text-body-secondary">طلبات HTTP اليوم</div>
                            <div class="fs-4 fw-bold lh-1">{{ number_format($stats['today_http'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="dashboard-stat-tile h-100 mb-0">
                        <div class="tile-icon bg-dark bg-opacity-10 text-dark"><i class="fa-solid fa-key"></i></div>
                        <div>
                            <div class="small text-body-secondary">أحداث المصادقة اليوم</div>
                            <div class="fs-4 fw-bold lh-1">{{ number_format($stats['today_auth'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="dashboard-stat-tile h-100 mb-0 border-primary border-opacity-25">
                        <div class="tile-icon bg-success bg-opacity-10 text-success"><i class="fa-solid fa-filter"></i></div>
                        <div>
                            <div class="small text-body-secondary">نتيجة التصفية الحالية</div>
                            <div class="fs-4 fw-bold lh-1">{{ number_format($activities->total()) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header bg-transparent py-3">
            <span class="fw-semibold"><i class="fa-solid fa-magnifying-glass ms-1 text-body-secondary"></i> تصفية السجلات</span>
        </div>
        <div class="card-body pt-0">
            <form method="get" action="{{ route('activity-log.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label small fw-semibold text-body-secondary mb-1">بحث شامل</label>
                    <div class="input-group">
                        <span class="input-group-text bg-body-secondary border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="search" name="q" class="form-control border-start-0" value="{{ $q }}"
                               placeholder="وصف، اسم مسار، مسار URL، نوع سجل…">
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label small fw-semibold text-body-secondary mb-1">نوع السجل</label>
                    <select name="log_name" class="form-select">
                        <option value="">كل الأنواع</option>
                        @foreach ($logNames as $ln)
                            <option value="{{ $ln }}" @selected($filterLogName === $ln)>{{ $ln }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary px-4 flex-grow-1 flex-sm-grow-0">
                        <i class="fa-solid fa-check ms-1"></i> تطبيق
                    </button>
                    <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary">مسح التصفية</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold">السجلات</span>
            <span class="small text-body-secondary">عرض {{ $activities->firstItem() ?? 0 }}–{{ $activities->lastItem() ?? 0 }} من {{ number_format($activities->total()) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 activity-log-table">
                    <thead class="table-light">
                    <tr class="small text-body-secondary">
                        <th scope="col" class="ps-4 py-3">الوقت</th>
                        <th scope="col" class="py-3">الإجراء</th>
                        <th scope="col" class="py-3">المستخدم</th>
                        <th scope="col" class="py-3">الكائن</th>
                        <th scope="col" class="pe-4 py-3">التفاصيل</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($activities as $row)
                        @php
                            $rawProps = $row->properties;
                            $props = $rawProps instanceof \Illuminate\Support\Collection ? $rawProps->toArray() : (is_array($rawProps) ? $rawProps : []);
                            $attrs = $props['attributes'] ?? null;
                            $old = $props['old'] ?? null;
                            $httpStatus = $props['status'] ?? null;
                            $isHttpLog = ($row->log_name ?? '') === 'http';
                            $httpMethod = $props['method'] ?? $row->event;
                            $jsonPayload = json_encode($props, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        @endphp
                        <tr>
                            <td class="text-nowrap small ps-4 text-body-secondary font-monospace" dir="ltr">
                                {{ $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d') }}
                                <span class="d-block fw-semibold text-body">{{ $row->created_at?->timezone(config('app.timezone'))->format('H:i:s') }}</span>
                            </td>
                            <td style="min-width: 14rem;">
                                <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                                    @if ($isHttpLog && $httpMethod)
                                        <span class="badge rounded-pill {{ $methodBadge($httpMethod) }} font-monospace">{{ $httpMethod }}</span>
                                    @endif
                                    @if ($isHttpLog && $httpStatus !== null)
                                        @php
                                            $st = (int) $httpStatus;
                                            $badgeClass = $st >= 500 ? 'text-bg-danger' : ($st >= 400 ? 'text-bg-warning text-dark' : ($st >= 300 ? 'text-bg-info text-dark' : 'text-bg-success'));
                                        @endphp
                                        <span class="badge rounded-pill {{ $badgeClass }}">{{ $httpStatus }}</span>
                                    @elseif ($row->event && ! $isHttpLog)
                                        <span class="badge rounded-pill text-bg-light border">{{ $row->event }}</span>
                                    @endif
                                    @if ($row->log_name)
                                        <span class="badge rounded-pill {{ $logBadgeClass($row->log_name) }}">{{ $row->log_name }}</span>
                                    @endif
                                </div>
                                <div class="fw-semibold text-body">{{ $row->description }}</div>
                                @if ($isHttpLog && ! empty($props['route']))
                                    <div class="small font-monospace text-primary-emphasis mt-1" dir="ltr">{{ $props['route'] }}</div>
                                @endif
                                @if ($isHttpLog && ! empty($props['path']))
                                    <div class="small font-monospace text-muted mt-1" dir="ltr">{{ $props['path'] }}</div>
                                @endif
                            </td>
                            <td class="small" style="min-width: 11rem;">
                                @if ($row->causer)
                                    <div class="fw-semibold">{{ $row->causer->name }}</div>
                                    <div class="text-muted small text-break">{{ $row->causer->email }}</div>
                                    @if ($row->causer->role ?? null)
                                        <span class="badge text-bg-light border mt-1">{{ $row->causer->role }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if ($row->subject)
                                    <span class="badge text-bg-light text-dark border font-monospace">{{ class_basename($row->subject_type) }}</span>
                                    <span class="font-monospace small">#{{ $row->subject_id }}</span>
                                    @if (optional($row->subject)->getAttribute('name'))
                                        <div class="text-muted mt-1">{{ \Illuminate\Support\Str::limit((string) $row->subject->getAttribute('name'), 48) }}</div>
                                    @endif
                                @else
                                    <span class="text-muted small">{{ $isHttpLog ? 'طلب ويب' : '—' }}</span>
                                @endif
                            </td>
                            <td class="small pe-4" style="max-width: 22rem;">
                                @if ($attrs || $old)
                                    <details class="activity-log-details border rounded-3 p-2 bg-body-tertiary bg-opacity-40">
                                        <summary class="fw-semibold text-primary small user-select-none">تغييرات السجل</summary>
                                        @if ($old)
                                            <div class="mt-2 small text-danger-emphasis">قبل</div>
                                            <pre class="activity-json-pre mb-2 mt-1 rounded-3" dir="ltr">{{ json_encode($old, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                        @endif
                                        @if ($attrs)
                                            <div class="small text-success-emphasis">بعد</div>
                                            <pre class="activity-json-pre mb-0 mt-1 rounded-3" dir="ltr">{{ json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                        @endif
                                    </details>
                                @elseif ($isHttpLog && count($props) > 0)
                                    <details class="activity-log-details border rounded-3 p-2 bg-body-tertiary bg-opacity-40">
                                        <summary class="fw-semibold text-primary small user-select-none d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                            <span>حمولة الطلب (آمنة)</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 activity-copy-json" data-json="{{ e($jsonPayload) }}">
                                                نسخ JSON
                                            </button>
                                        </summary>
                                        <pre class="activity-json-pre mb-0 mt-2 rounded-3" dir="ltr" style="max-height: 18rem;">{{ $jsonPayload }}</pre>
                                    </details>
                                @elseif (count($props) > 0)
                                    <details class="activity-log-details border rounded-3 p-2 bg-body-tertiary bg-opacity-40">
                                        <summary class="fw-semibold text-primary small user-select-none d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                            <span>الخصائص</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 activity-copy-json" data-json="{{ e($jsonPayload) }}">
                                                نسخ JSON
                                            </button>
                                        </summary>
                                        <pre class="activity-json-pre mb-0 mt-2 rounded-3" dir="ltr">{{ $jsonPayload }}</pre>
                                    </details>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-body-secondary mb-2"><i class="fa-regular fa-folder-open fa-2x opacity-50"></i></div>
                                <div class="fw-semibold">لا توجد سجلات مطابقة</div>
                                <div class="small mt-1">جرّب توسيع البحث أو إزالة نوع السجل من التصفية.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($activities->hasPages())
            <div class="card-footer bg-transparent border-0 pt-0 pb-4 px-4">
                {{ $activities->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <style>
        .activity-log-table thead th { border-bottom-width: 1px; font-weight: 600; }
        .activity-json-pre {
            font-size: 0.7rem;
            line-height: 1.45;
            padding: 0.75rem 1rem;
            margin: 0;
            background: var(--bs-dark);
            color: var(--bs-gray-100);
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .activity-log-details summary { cursor: pointer; list-style: none; }
        .activity-log-details summary::-webkit-details-marker { display: none; }
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
