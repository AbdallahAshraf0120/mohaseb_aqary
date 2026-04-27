@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header">
            <h5 class="mb-0">سجل النشاط</h5>
            <p class="text-muted small mb-0 mt-1">تسجيل الدخول/الخروج، تعديلات المستخدمين والمشاريع، وغيرها.</p>
        </div>
        <div class="card-body">
            <form method="get" action="{{ route('activity-log.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-0">بحث في الوصف / السجل / الحدث</label>
                    <input type="search" name="q" class="form-control" value="{{ $q }}" placeholder="كلمة…">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">اسم السجل</label>
                    <select name="log_name" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($logNames as $ln)
                            <option value="{{ $ln }}" @selected($filterLogName === $ln)>{{ $ln }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">تصفية</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('activity-log.index') }}" class="btn btn-link w-100">إعادة ضبط</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>الوقت</th>
                        <th>الوصف</th>
                        <th>المستخدم</th>
                        <th>الكائن</th>
                        <th>التفاصيل</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($activities as $row)
                        @php
                            $props = $row->properties ?? null;
                            $attrs = is_array($props) ? ($props['attributes'] ?? null) : null;
                            $old = is_array($props) ? ($props['old'] ?? null) : null;
                        @endphp
                        <tr>
                            <td class="text-nowrap small">{{ $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                            <td>
                                <span class="fw-medium">{{ $row->description }}</span>
                                @if ($row->event)
                                    <span class="badge text-bg-light border ms-1">{{ $row->event }}</span>
                                @endif
                                @if ($row->log_name)
                                    <span class="text-muted small d-block">{{ $row->log_name }}</span>
                                @endif
                            </td>
                            <td class="small">
                                @if ($row->causer)
                                    {{ $row->causer->name }}
                                    <div class="text-muted">{{ $row->causer->email }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if ($row->subject)
                                    <span class="text-break">{{ class_basename($row->subject_type) }} #{{ $row->subject_id }}</span>
                                    @if (optional($row->subject)->getAttribute('name'))
                                        <div class="text-muted">{{ \Illuminate\Support\Str::limit((string) $row->subject->getAttribute('name'), 40) }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if ($attrs || $old)
                                    <details>
                                        <summary class="text-primary" style="cursor: pointer;">عرض التغييرات</summary>
                                        @if ($old)
                                            <div class="mt-2 text-danger small">قبل: <pre class="mb-1 p-2 bg-body-secondary rounded small" dir="ltr">{{ json_encode($old, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre></div>
                                        @endif
                                        @if ($attrs)
                                            <div class="text-success small">بعد: <pre class="mb-0 p-2 bg-body-secondary rounded small" dir="ltr">{{ json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre></div>
                                        @endif
                                    </details>
                                @elseif ($props && count((array) $props) > 0)
                                    <details>
                                        <summary class="text-primary" style="cursor: pointer;">خصائص</summary>
                                        <pre class="mt-2 mb-0 p-2 bg-body-secondary rounded small" dir="ltr">{{ json_encode($props, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">لا توجد سجلات.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $activities->links() }}
            </div>
        </div>
    </div>
@endsection
