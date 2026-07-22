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
                        ماذا تم في النظام ومن نفّذه ومتى — بلغة واضحة بدون تفاصيل تقنية.
                        @unless ($includeBrowsing)
                            <span class="d-block mt-1">يُعرض هنا ما يهم العمل فقط (إضافة / تعديل / حذف / دخول). تصفح الصفحات مخفي.</span>
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
                            <div class="small text-body-secondary">دخول وخروج اليوم</div>
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
                           placeholder="ابحث باسم العملية أو المستخدم…">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small fw-semibold text-body-secondary mb-1">النوع</label>
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
                            إظهار تصفح الصفحات أيضاً
                        </label>
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary px-4">تطبيق</button>
                    <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary">مسح</a>
                    <span class="align-self-center small text-body-secondary ms-auto">
                        {{ number_format($activities->total()) }} عملية
                    </span>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold">ماذا حدث؟</span>
            <span class="small text-body-secondary">
                {{ $activities->firstItem() ?? 0 }}–{{ $activities->lastItem() ?? 0 }}
                من {{ number_format($activities->total()) }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="activity-timeline list-group list-group-flush">
                @forelse ($activities as $item)
                    @php
                        $row = $item['row'];
                        $v = $item['view'];
                        $roleLabel = $row->causer?->role ? $presenter->roleLabel($row->causer->role) : null;
                    @endphp
                    <div class="list-group-item px-4 py-3 activity-timeline-item">
                        <div class="d-flex flex-wrap gap-3 align-items-start">
                            <div class="activity-time text-center flex-shrink-0" style="min-width: 5.25rem;">
                                <div class="small text-body-secondary">{{ $v['time_date'] }}</div>
                                <div class="fw-semibold">{{ $v['time_clock'] }}</div>
                                <div class="small text-muted">{{ $v['time_relative'] }}</div>
                            </div>

                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-1 mb-1">
                                    <span class="badge rounded-pill {{ $v['action_kind_badge'] }}">{{ $v['action_kind'] }}</span>
                                    @if (! empty($v['module']) && $v['module'] !== '—')
                                        <span class="badge rounded-pill text-bg-light border">{{ $v['module'] }}</span>
                                    @endif
                                    @if (! empty($v['failed']))
                                        <span class="badge rounded-pill text-bg-danger">{{ $v['result_label'] }}</span>
                                    @endif
                                </div>

                                <div class="fw-semibold fs-6 text-body mb-1">{{ $v['headline'] }}</div>

                                <div class="small text-body-secondary">
                                    بواسطة
                                    <span class="fw-medium text-body">{{ $row->causer?->name ?? '—' }}</span>
                                    @if ($roleLabel)
                                        <span class="text-muted">({{ $roleLabel }})</span>
                                    @endif
                                    @if (! empty($v['subject_label']) || ! empty($v['subject_name']))
                                        <span class="mx-1 opacity-50">·</span>
                                        @if (! empty($v['subject_name']))
                                            {{ $v['subject_label'] ?? '' }}: {{ $v['subject_name'] }}
                                        @elseif (! empty($v['subject_label']) && ($v['subject_label'] ?? '') !== ($v['module'] ?? null))
                                            {{ $v['subject_label'] }}
                                        @endif
                                    @endif
                                </div>

                                @if (count($v['changed_fields']) > 0)
                                    <details class="activity-details mt-2 border rounded-3 p-2 bg-body-tertiary bg-opacity-40">
                                        <summary class="fw-semibold text-primary small user-select-none">
                                            ماذا تغيّر؟ ({{ count($v['changed_fields']) }})
                                        </summary>
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                <tr class="small text-body-secondary">
                                                    <th>البيان</th>
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
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 px-3">
                        <div class="text-body-secondary mb-2"><i class="fa-regular fa-folder-open fa-2x opacity-50"></i></div>
                        <div class="fw-semibold">لا توجد عمليات مطابقة</div>
                        <div class="small text-body-secondary mt-1">
                            جرّب توسيع الفترة أو تفعيل «إظهار تصفح الصفحات».
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
    </style>
@endsection
