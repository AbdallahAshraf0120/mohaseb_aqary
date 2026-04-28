@extends('layouts.admin')

@section('content')
    @php
        $selected = collect(old('daily_available_units_report_recipients', data_get($setting->meta, 'daily_available_units_report_recipients', [])))
            ->map(fn ($v) => (int) $v)
            ->all();
        $reportEnabled = (bool) old('daily_available_units_report_enabled', data_get($setting->meta, 'daily_available_units_report_enabled', false));
        $reportTime = (string) old('daily_available_units_report_time', data_get($setting->meta, 'daily_available_units_report_time', '08:00'));
        $repeatMinutes = (int) old('daily_available_units_report_repeat_minutes', data_get($setting->meta, 'daily_available_units_report_repeat_minutes', 0));
        $selectedCount = count($selected);
    @endphp

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">تحقق من الحقول التالية:</div>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="post" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card app-surface h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h5 class="mb-0 fw-semibold">الإعدادات العامة</h5>
                        <p class="small text-body-secondary mb-0 mt-2">إعدادات المشروع الحالية.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">اسم الشركة</label>
                                <input name="company_name" class="form-control" required value="{{ old('company_name', $setting->company_name) }}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">العملة</label>
                                <input name="currency" class="form-control" required value="{{ old('currency', $setting->currency) }}">
                                <div class="form-text">مثال: EGP أو USD.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card app-surface h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="mb-0 fw-semibold">التقارير البريدية</h5>
                                <p class="small text-body-secondary mb-0 mt-2">تقرير يومي للوحدات المتاحة (غير مباعة).</p>
                            </div>
                            <span class="badge @if($reportEnabled) text-bg-success @else text-bg-secondary @endif">
                                {{ $reportEnabled ? 'مفعّل' : 'متوقف' }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="report-enabled"
                                       name="daily_available_units_report_enabled" value="1"
                                       @checked($reportEnabled)>
                                <label class="form-check-label fw-semibold" for="report-enabled">تفعيل التقرير اليومي</label>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-body-secondary" for="report-time">وقت الإرسال</label>
                                <input type="time" class="form-control form-control-sm" style="width: 8rem" id="report-time"
                                       name="daily_available_units_report_time" value="{{ $reportTime }}">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small text-body-secondary mb-1" for="report-repeat">تكرار الإرسال</label>
                                <select class="form-select form-select-sm" id="report-repeat" name="daily_available_units_report_repeat_minutes">
                                    <option value="0" @selected($repeatMinutes === 0)>مرة واحدة يوميًا</option>
                                    <option value="30" @selected($repeatMinutes === 30)>كل 30 دقيقة</option>
                                    <option value="60" @selected($repeatMinutes === 60)>كل ساعة</option>
                                    <option value="180" @selected($repeatMinutes === 180)>كل 3 ساعات</option>
                                    <option value="360" @selected($repeatMinutes === 360)>كل 6 ساعات</option>
                                    <option value="720" @selected($repeatMinutes === 720)>كل 12 ساعة</option>
                                </select>
                                <div class="form-text">بعد وقت الإرسال المحدد أعلاه، سيُعاد الإرسال حسب التكرار. (يمنع التكرار قبل انتهاء المدة)</div>
                                @error('daily_available_units_report_repeat_minutes')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-end justify-content-md-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="send-report-now-btn">
                                    <i class="fa-solid fa-paper-plane ms-1"></i> إرسال التقرير الآن
                                </button>
                            </div>
                        </div>

                        <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 mb-3">
                            <div class="small text-body-secondary">المستلمون المختارون</div>
                            <div class="fs-5 fw-bold font-monospace">{{ $selectedCount }}</div>
                            <div class="small text-body-secondary mt-1">لن يُرسل التقرير إن لم تُحدد أي مستلمين.</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="select-all-recipients">تحديد الكل</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-all-recipients">إلغاء الكل</button>
                        </div>

                        <div class="row g-2">
                            @foreach (($users ?? collect()) as $u)
                                <div class="col-lg-6">
                                    <label class="form-check d-flex align-items-start gap-2 border rounded-3 p-3 bg-body-tertiary bg-opacity-25 h-100">
                                        <input class="form-check-input mt-1 report-recipient" type="checkbox"
                                               name="daily_available_units_report_recipients[]"
                                               value="{{ $u->id }}"
                                               @checked(in_array((int) $u->id, $selected, true))>
                                        <span class="flex-grow-1">
                                            <span class="fw-semibold">{{ $u->name }}</span>
                                            <span class="text-body-secondary small d-block">{{ $u->email }}</span>
                                            <span class="badge text-bg-light text-body-secondary mt-1">{{ $u->role }}</span>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        @error('daily_available_units_report_recipients')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        @error('daily_available_units_report_recipients.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary px-4">حفظ الإعدادات</button>
        </div>
    </form>

    <script>
        (function () {
            const allBtn = document.getElementById('select-all-recipients');
            const clearBtn = document.getElementById('clear-all-recipients');
            const boxes = Array.from(document.querySelectorAll('.report-recipient'));
            if (!allBtn || !clearBtn || boxes.length === 0) return;

            allBtn.addEventListener('click', function () {
                boxes.forEach(b => b.checked = true);
            });
            clearBtn.addEventListener('click', function () {
                boxes.forEach(b => b.checked = false);
            });
        })();

        (function () {
            const btn = document.getElementById('send-report-now-btn');
            const form = document.getElementById('send-report-now-form');
            if (!btn || !form) return;
            btn.addEventListener('click', function () {
                form.submit();
            });
        })();
    </script>

    <form id="send-report-now-form" method="post" action="{{ route('settings.send-available-units-report', [$setting->project_id ?? request()->route('project')]) }}" class="d-none">
        @csrf
    </form>
@endsection
