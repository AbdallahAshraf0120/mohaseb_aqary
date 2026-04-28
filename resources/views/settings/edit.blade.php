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
        $cronEveryMinute = '* * * * *';
        $cronPhpArtisan = '/usr/bin/php '.base_path().'/artisan schedule:run';
        $cronCdRun = 'cd '.base_path().' && php artisan schedule:run';
    @endphp

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">تحقق من الحقول التالية:</div>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card app-surface border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width:2.5rem;height:2.5rem">
                            <i class="fa-solid fa-sliders"></i>
                        </span>
                        لوحة إعدادات المشروع
                    </h4>
                    <p class="text-body-secondary small mb-0">تحكم بالبيانات المعروضة في التقارير والتنبيهات البريدية، وراقب حالة الإرسال التلقائي.</p>
                </div>
                @if ($project ?? null)
                    <span class="badge rounded-pill text-bg-light border text-body-secondary align-self-center">{{ $project->name }}</span>
                @endif
            </div>

            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-40 p-3 h-100">
                        <div class="small text-body-secondary mb-1"><i class="fa-regular fa-clock ms-1"></i> المنطقة الزمنية</div>
                        <div class="fw-semibold font-monospace small">{{ $appTimezone ?? config('app.timezone') }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-40 p-3 h-100">
                        <div class="small text-body-secondary mb-1"><i class="fa-solid fa-paper-plane ms-1"></i> حالة التقرير اليومي</div>
                        <div class="fw-semibold">
                            @if ($reportEnabled)
                                <span class="text-success">مفعّل</span>
                            @else
                                <span class="text-secondary">متوقف</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-40 p-3 h-100">
                        <div class="small text-body-secondary mb-1"><i class="fa-solid fa-users ms-1"></i> المستلمون</div>
                        <div class="fw-semibold">{{ $selectedCount }} مختار</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-40 p-3 h-100">
                        <div class="small text-body-secondary mb-1"><i class="fa-solid fa-calendar-check ms-1"></i> آخر إرسال تلقائي</div>
                        @if ($lastSentDisplay)
                            <div class="fw-semibold small font-monospace">{{ $lastSentDisplay }}</div>
                            @if ($lastSentRelative)
                                <div class="text-body-secondary small">{{ $lastSentRelative }}</div>
                            @endif
                        @else
                            <div class="text-body-secondary small">لم يُسجَّل بعد</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="{{ route('settings.update', $project ?? request()->route('project')) }}">
        @csrf
        @method('PUT')

        <ul class="nav nav-pills flex-column flex-sm-row gap-2 mb-3" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill px-4" id="tab-general-btn" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab" aria-controls="tab-general" aria-selected="true">
                    <i class="fa-solid fa-building ms-1"></i> عامة
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="tab-mail-btn" data-bs-toggle="pill" data-bs-target="#tab-mail" type="button" role="tab" aria-controls="tab-mail" aria-selected="false">
                    <i class="fa-solid fa-envelope-open-text ms-1"></i> التقارير البريدية
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="tab-auto-btn" data-bs-toggle="pill" data-bs-target="#tab-auto" type="button" role="tab" aria-controls="tab-auto" aria-selected="false">
                    <i class="fa-solid fa-server ms-1"></i> الجدولة والخادم
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsTabsContent">
            <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general-btn" tabindex="0">
                <div class="card app-surface">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h5 class="mb-0 fw-semibold">البيانات العامة للمشروع</h5>
                        <p class="small text-body-secondary mb-0 mt-2">تظهر في الواجهات والتقارير المرتبطة بهذا المشروع.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold" for="company_name">اسم الشركة / العنوان في التقارير</label>
                                <input id="company_name" name="company_name" class="form-control form-control-lg" required value="{{ old('company_name', $setting->company_name) }}" autocomplete="organization">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold" for="currency">العملة</label>
                                <input id="currency" name="currency" class="form-control" required value="{{ old('currency', $setting->currency) }}" list="currency-list" maxlength="20" placeholder="EGP">
                                <datalist id="currency-list">
                                    <option value="EGP"></option>
                                    <option value="USD"></option>
                                    <option value="SAR"></option>
                                    <option value="AED"></option>
                                    <option value="EUR"></option>
                                    <option value="KWD"></option>
                                </datalist>
                                <div class="form-text">رمز ISO قصير (يُستخدم في التصدير والعرض).</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-mail" role="tabpanel" aria-labelledby="tab-mail-btn" tabindex="0">
                <div class="card app-surface">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="mb-0 fw-semibold">تقرير الوحدات المتاحة</h5>
                                <p class="small text-body-secondary mb-0 mt-2">إرسال دوري للوحدات غير المباعة حسب الجدولة على الخادم.</p>
                            </div>
                            <span class="badge @if($reportEnabled) text-bg-success @else text-bg-secondary @endif rounded-pill">{{ $reportEnabled ? 'مفعّل' : 'متوقف' }}</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom border-secondary-subtle">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="report-enabled"
                                       name="daily_available_units_report_enabled" value="1"
                                       @checked($reportEnabled)>
                                <label class="form-check-label fw-semibold" for="report-enabled">تفعيل الإرسال التلقائي</label>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <label class="small text-body-secondary mb-0" for="report-time">أول إرسال بعد</label>
                                <input type="time" class="form-control form-control-sm" style="width: 8rem" id="report-time"
                                       name="daily_available_units_report_time" value="{{ $reportTime }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold" for="report-repeat">تكرار الإرسال بعد البداية</label>
                                <select class="form-select" id="report-repeat" name="daily_available_units_report_repeat_minutes">
                                    <option value="0" @selected($repeatMinutes === 0)>مرة واحدة يوميًا</option>
                                    <option value="30" @selected($repeatMinutes === 30)>كل 30 دقيقة</option>
                                    <option value="60" @selected($repeatMinutes === 60)>كل ساعة</option>
                                    <option value="180" @selected($repeatMinutes === 180)>كل 3 ساعات</option>
                                    <option value="360" @selected($repeatMinutes === 360)>كل 6 ساعات</option>
                                    <option value="720" @selected($repeatMinutes === 720)>كل 12 ساعة</option>
                                </select>
                                <div class="form-text">لا يُكرَّر الإرسال قبل انتهاء المدة منذ آخر بريد، وبعد تجاوز وقت البداية اليومية.</div>
                                @error('daily_available_units_report_repeat_minutes')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary w-100" form="send-report-now-form">
                                    <i class="fa-solid fa-paper-plane ms-1"></i> إرسال تجريبي الآن
                                </button>
                            </div>
                        </div>

                        <div class="rounded-3 border bg-primary-subtle bg-opacity-25 p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
                                <div>
                                    <div class="small text-body-secondary">المستلمون المفعَّلون</div>
                                    <div class="fs-4 fw-bold font-monospace">{{ $selectedCount }}</div>
                                </div>
                                <div class="flex-grow-1" style="max-width: 22rem;">
                                    <label class="visually-hidden" for="recipient-filter">بحث في المستخدمين</label>
                                    <input type="search" class="form-control form-control-sm" id="recipient-filter" placeholder="ابحث بالاسم أو البريد..." autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="select-all-recipients">تحديد الكل</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-all-recipients">إلغاء الكل</button>
                        </div>

                        <div class="row g-2" id="recipient-cards">
                            @foreach (($users ?? collect()) as $u)
                                <div class="col-lg-6 recipient-card-wrap" data-filter="{{ strtolower($u->name.' '.$u->email) }}">
                                    <label class="form-check d-flex align-items-start gap-2 border rounded-3 p-3 bg-body-tertiary bg-opacity-25 h-100 recipient-card">
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

            <div class="tab-pane fade" id="tab-auto" role="tabpanel" aria-labelledby="tab-auto-btn" tabindex="0">
                <div class="card app-surface mb-3">
                    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                        <h5 class="mb-0 fw-semibold">الجدولة على الخادم (Cron)</h5>
                        <p class="small text-body-secondary mb-0 mt-2">يجب أن يستدعي النظام <code class="small">schedule:run</code> كل دقيقة حتى تعمل مهام Laravel بما فيها هذا التقرير.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-secondary border-0 mb-4">
                            <div class="fw-semibold mb-2"><i class="fa-solid fa-circle-info ms-1"></i> التوقيت في الواجهة</div>
                            يعتمد على <code>APP_TIMEZONE</code> الحالي (<strong>{{ $appTimezone ?? config('app.timezone') }}</strong>). اضبطه في ملف البيئة على الخادم إذا لم يطابق منطقتكم.
                        </div>

                        <p class="small text-body-secondary mb-2">جدولة كل دقيقة (موصى به):</p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control font-monospace small" readonly id="cron-line-php" value="{{ $cronEveryMinute }} {{ $cronPhpArtisan }}">
                            <button class="btn btn-outline-secondary" type="button" data-copy-target="cron-line-php" title="نسخ">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>

                        <p class="small text-body-secondary mb-2">بديل بعد الانتقال لمجلد المشروع:</p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control font-monospace small" readonly id="cron-line-cd" value="{{ $cronEveryMinute }} {{ $cronCdRun }}">
                            <button class="btn btn-outline-secondary" type="button" data-copy-target="cron-line-cd" title="نسخ">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>

                        <p class="small text-muted mb-0">مسار التطبيق على هذا الخادم: <code class="small">{{ base_path() }}</code></p>
                    </div>
                </div>

                <div class="card app-surface border-secondary-subtle">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-2"><i class="fa-solid fa-list-check ms-1"></i> تسلسل التحقق</h6>
                        <ol class="small text-body-secondary mb-0 ps-3">
                            <li class="mb-2">إضافة Cron أعلاه في لوحة الاستضافة أو عبر <code class="small">crontab -e</code>.</li>
                            <li class="mb-2">التأكد أن بريد SMTP في <code class="small">.env</code> صحيح حتى تصل الرسائل.</li>
                            <li>اختي حفظ الإعدادات ثم تجربة «إرسال تجريبي الآن» من تبويب التقارير.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 pt-3 border-top border-secondary-subtle">
            <span class="small text-body-secondary">التغييرات تنطبق على هذا المشروع فقط.</span>
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="fa-solid fa-floppy-disk ms-2"></i> حفظ الإعدادات
            </button>
        </div>
    </form>

    <form id="send-report-now-form" method="post" action="{{ route('settings.send-available-units-report', $project ?? request()->route('project')) }}" class="d-none">
        @csrf
    </form>

    <script>
        (function () {
            const allBtn = document.getElementById('select-all-recipients');
            const clearBtn = document.getElementById('clear-all-recipients');
            const boxes = Array.from(document.querySelectorAll('.report-recipient'));
            const filterInput = document.getElementById('recipient-filter');
            const wraps = Array.from(document.querySelectorAll('.recipient-card-wrap'));

            if (allBtn && clearBtn && boxes.length > 0) {
                allBtn.addEventListener('click', function () {
                    const visible = wraps.filter(function (w) { return w.style.display !== 'none'; });
                    visible.forEach(function (w) {
                        const cb = w.querySelector('.report-recipient');
                        if (cb) cb.checked = true;
                    });
                });
                clearBtn.addEventListener('click', function () {
                    boxes.forEach(function (b) { b.checked = false; });
                });
            }

            if (filterInput && wraps.length) {
                filterInput.addEventListener('input', function () {
                    const q = this.value.trim().toLowerCase();
                    wraps.forEach(function (w) {
                        var hay = w.getAttribute('data-filter') || '';
                        w.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
                    });
                });
            }

            document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = btn.getAttribute('data-copy-target');
                    var el = document.getElementById(id);
                    if (!el) return;
                    el.select();
                    el.setSelectionRange(0, 99999);
                    try {
                        navigator.clipboard.writeText(el.value);
                        btn.classList.add('btn-success');
                        btn.classList.remove('btn-outline-secondary');
                        setTimeout(function () {
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-outline-secondary');
                        }, 1200);
                    } catch (e) {}
                });
            });
        })();
    </script>
@endsection
