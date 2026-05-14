@extends('layouts.admin')

@section('content')
    @php
        $canManage = auth()->user()?->can('site-sketch.manage');
        $colorMap = [
            'available' => ['bg' => '#16a34a', 'text' => '#ffffff', 'icon' => 'fa-circle-check'],
            'sold' => ['bg' => '#dc2626', 'text' => '#ffffff', 'icon' => 'fa-circle-xmark'],
            'pending' => ['bg' => '#f97316', 'text' => '#ffffff', 'icon' => 'fa-hourglass-half'],
            'reserved' => ['bg' => '#facc15', 'text' => '#1f2937', 'icon' => 'fa-hand'],
            'viewing' => ['bg' => '#3b82f6', 'text' => '#ffffff', 'icon' => 'fa-eye'],
            'blocked' => ['bg' => '#6b7280', 'text' => '#ffffff', 'icon' => 'fa-ban'],
        ];
    @endphp

    <style>
        .sketch-grid { display: flex; flex-direction: column; gap: .75rem; }
        .sketch-floor {
            border: 1px solid var(--bs-border-color);
            border-radius: .5rem;
            padding: .65rem .75rem;
            background: var(--bs-tertiary-bg);
        }
        .sketch-floor-head {
            display: flex; align-items: center; justify-content: space-between;
            gap: .5rem; margin-bottom: .5rem; flex-wrap: wrap;
        }
        .sketch-floor-cells {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: .5rem;
        }
        .sketch-cell {
            position: relative; cursor: pointer;
            border-radius: .5rem; padding: .55rem .5rem;
            text-align: center; font-weight: 600;
            min-height: 64px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            border: 1px solid rgba(0,0,0,.1);
            transition: transform .08s ease, box-shadow .12s ease;
            user-select: none;
        }
        .sketch-cell:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,0,0,.18);
            outline: 2px dashed rgba(255,255,255,.45);
            outline-offset: -4px;
        }
        .sketch-cell::before {
            content: '\f25a';
            font-family: 'Font Awesome 6 Free'; font-weight: 900;
            position: absolute; bottom: 4px; inset-inline-start: 6px;
            font-size: .65rem; opacity: 0; transition: opacity .12s ease;
        }
        .sketch-cell:hover::before { opacity: .85; }
        .sketch-readonly .sketch-cell { cursor: default; }
        .sketch-readonly .sketch-cell:hover::before { opacity: 0; }
        .sketch-readonly .sketch-cell:hover { outline: none; }
        .sketch-cell.is-manual::after {
            content: ''; position: absolute; top: 4px; inset-inline-end: 4px;
            width: 8px; height: 8px; border-radius: 50%; background: #fff;
            border: 1px solid rgba(0,0,0,.25);
        }
        .sketch-cell .cell-status { font-size: .72rem; opacity: .92; font-weight: 500; }
        .sketch-cell .cell-label { font-size: .9rem; }
        .legend-dot {
            display: inline-block; width: .9rem; height: .9rem; border-radius: 50%;
            margin-inline-end: .35rem; vertical-align: -2px; border: 1px solid rgba(0,0,0,.12);
        }
    </style>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card app-surface mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <form method="get" action="{{ route('site-sketch.index') }}" id="sketchSelectForm">
                        <label class="form-label small text-body-secondary mb-1">اختر العقار</label>
                        <select name="property_id" class="form-select" onchange="document.getElementById('sketchSelectForm').submit()">
                            <option value="">— اختر عقار —</option>
                            @foreach ($projects as $p)
                                @if ($p->properties->isNotEmpty())
                                    <optgroup label="{{ $p->name }}">
                                        @foreach ($p->properties as $prop)
                                            <option value="{{ $prop->id }}"
                                                @selected($selectedProperty && (int) $selectedProperty->id === (int) $prop->id)>
                                                {{ $prop->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="col-md-6 text-md-end">
                    @if ($selectedProperty && $canManage)
                        <form method="post" action="{{ route('site-sketch.reset', $selectedProperty) }}"
                              id="sketchResetForm"
                              class="d-inline-block">
                            @csrf
                            <button type="button" class="btn btn-outline-danger btn-sm" id="sketchResetBtn">
                                <i class="fa-solid fa-rotate-left ms-1"></i> إعادة الكروكي للحالة الأصلية
                            </button>
                        </form>
                        <script>
                            (function () {
                                var btn = document.getElementById('sketchResetBtn');
                                var form = document.getElementById('sketchResetForm');
                                if (!btn || !form) return;
                                btn.addEventListener('click', function () {
                                    if (confirm('هل تريد إعادة كل الكروكي إلى الحالة الأصلية من البيانات الفعلية؟')) {
                                        form.submit();
                                    }
                                });
                            })();
                        </script>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (! $selectedProperty)
        <div class="alert alert-info mb-0">
            اختر عقارًا من القائمة بالأعلى لعرض كروكي الأدوار والشقق.
        </div>
    @else
        @php
            $totals = $sketch['totals'];
        @endphp

        <x-partials.module-kpis :items="[
            ['label' => 'إجمالي الوحدات', 'value' => (int) ($totals['total'] ?? 0)],
            ['label' => 'متاح', 'value' => (int) ($totals['available'] ?? 0)],
            ['label' => 'مباع', 'value' => (int) ($totals['sold'] ?? 0)],
            ['label' => 'تحت الاعتماد', 'value' => (int) ($totals['pending'] ?? 0)],
            ['label' => 'محجوز شفهي', 'value' => (int) ($totals['reserved'] ?? 0)],
        ]" />

        <div class="card app-surface mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">{{ $selectedProperty->name }}</h5>
                    <small class="text-body-secondary">المشروع: {{ $selectedProperty->project?->name ?? '—' }}</small>
                </div>
                @if ($canManage)
                    <div class="small text-body-secondary">
                        <i class="fa-solid fa-hand-pointer ms-1"></i>
                        اضغط على أي شقة بالشبكة لتغيير حالتها.
                    </div>
                @endif
            </div>
            <div class="card-body">
                <div class="alert alert-secondary py-2 small mb-3 d-flex flex-wrap align-items-center gap-3">
                    <span class="fw-semibold ms-1">دليل الألوان:</span>
                    @foreach ($statusOptions as $key => $label)
                        <span class="d-inline-flex align-items-center">
                            <span class="legend-dot" style="background: {{ $colorMap[$key]['bg'] ?? '#999' }}"></span>
                            {{ $label }}
                        </span>
                    @endforeach
                    @if ($canManage)
                        <span class="text-body-secondary ms-auto">
                            <i class="fa-solid fa-circle-info ms-1"></i>
                            هذه ألوان فقط — اضغط الخلية بالأسفل لاختيار حالتها.
                        </span>
                    @endif
                </div>

                @if (empty($sketch['floors']))
                    <div class="text-muted small">
                        لا توجد بيانات أدوار/شقق كافية لرسم كروكي لهذا العقار. تأكد من ضبط (عدد الأدوار، عدد الشقق بالدور).
                    </div>
                @else
                    <div class="sketch-grid {{ $canManage ? '' : 'sketch-readonly' }}" id="sketchGrid"
                         data-can-manage="{{ $canManage ? '1' : '0' }}"
                         data-update-url="{{ route('site-sketch.cells.update', $selectedProperty) }}"
                         data-csrf="{{ csrf_token() }}">
                        @foreach ($sketch['floors'] as $floor)
                            <div class="sketch-floor">
                                <div class="sketch-floor-head">
                                    <div>
                                        <span class="fw-semibold">{{ $floor['label'] }}</span>
                                        @if (! empty($floor['sub']))
                                            <span class="badge text-bg-secondary ms-1">{{ $floor['sub'] }}</span>
                                        @endif
                                    </div>
                                    <div class="small text-body-secondary">{{ count($floor['cells']) }} وحدة</div>
                                </div>
                                <div class="sketch-floor-cells">
                                    @foreach ($floor['cells'] as $cell)
                                        @php
                                            $c = $colorMap[$cell['effective_status']] ?? $colorMap['available'];
                                        @endphp
                                        <div class="sketch-cell {{ $cell['manual_status'] ? 'is-manual' : '' }}"
                                             data-key="{{ $cell['key'] }}"
                                             data-status="{{ $cell['effective_status'] }}"
                                             data-computed="{{ $cell['computed_status'] }}"
                                             data-note="{{ $cell['note'] }}"
                                             title="{{ $cell['label'] }} - {{ $cell['effective_label'] }}{{ $cell['note'] ? ' / '.$cell['note'] : '' }}"
                                             style="background: {{ $c['bg'] }}; color: {{ $c['text'] }};">
                                            <span class="cell-label">
                                                <i class="fa-solid {{ $c['icon'] }} ms-1"></i>
                                                {{ $cell['label'] }}
                                            </span>
                                            <span class="cell-status">{{ $cell['effective_label'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if ($canManage)
            <style>
                .ma-sketch-backdrop {
                    position: fixed; inset: 0; background: rgba(0,0,0,.55);
                    display: none; align-items: center; justify-content: center;
                    z-index: 1080; padding: 1rem;
                }
                .ma-sketch-backdrop.is-open { display: flex; }
                .ma-sketch-dialog {
                    background: var(--bs-body-bg); color: var(--bs-body-color);
                    border-radius: .65rem; width: 100%; max-width: 520px;
                    box-shadow: 0 18px 48px rgba(0,0,0,.4);
                    overflow: hidden; border: 1px solid var(--bs-border-color);
                }
                .ma-sketch-dialog header {
                    padding: .9rem 1rem; border-bottom: 1px solid var(--bs-border-color);
                    display: flex; align-items: center; justify-content: space-between;
                }
                .ma-sketch-dialog header h5 { margin: 0; font-weight: 600; }
                .ma-sketch-dialog .body { padding: 1rem; }
                .ma-sketch-dialog footer {
                    padding: .75rem 1rem; border-top: 1px solid var(--bs-border-color);
                    display: flex; gap: .5rem; justify-content: flex-end;
                }
                .ma-sketch-close {
                    background: none; border: 0; font-size: 1.25rem; cursor: pointer;
                    color: var(--bs-body-color); opacity: .65;
                }
                .ma-sketch-close:hover { opacity: 1; }
                .ma-status-pick { transition: outline .12s ease; }
                .ma-status-pick.is-selected { outline: 3px solid #0d6efd; outline-offset: 2px; }
            </style>

            <div class="ma-sketch-backdrop" id="cellEditModal" role="dialog" aria-modal="true">
                <div class="ma-sketch-dialog">
                    <header>
                        <h5 id="cellEditTitle">تعديل خلية</h5>
                        <button type="button" class="ma-sketch-close" data-close aria-label="إغلاق">&times;</button>
                    </header>
                    <div class="body">
                        <input type="hidden" id="cellEditKey">
                        <div class="mb-3">
                            <label class="form-label small mb-2">الحالة</label>
                            <div class="d-flex flex-wrap gap-2" id="cellEditStatusOptions">
                                @foreach ($statusOptions as $key => $label)
                                    @php $c = $colorMap[$key] ?? ['bg' => '#999', 'text' => '#fff']; @endphp
                                    <button type="button"
                                            class="btn btn-sm ma-status-pick"
                                            data-status="{{ $key }}"
                                            style="background: {{ $c['bg'] }}; color: {{ $c['text'] }}; border: 1px solid rgba(0,0,0,.15);">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">ملاحظة (اختياري)</label>
                            <input type="text" id="cellEditNote" class="form-control" maxlength="255" placeholder="مثلاً: اسم العميل، رقمه، تذكير…">
                        </div>
                        <div class="small text-body-secondary" id="cellEditComputedNote"></div>
                    </div>
                    <footer>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-close>إلغاء</button>
                        <button type="button" class="btn btn-primary btn-sm" id="cellEditSave">حفظ</button>
                    </footer>
                </div>
            </div>

            <script>
                (function () {
                    var grid = document.getElementById('sketchGrid');
                    if (!grid || grid.dataset.canManage !== '1') return;

                    var url = grid.dataset.updateUrl;
                    var csrf = grid.dataset.csrf;

                    var modalEl = document.getElementById('cellEditModal');
                    var keyInput = document.getElementById('cellEditKey');
                    var noteInput = document.getElementById('cellEditNote');
                    var titleEl = document.getElementById('cellEditTitle');
                    var computedNoteEl = document.getElementById('cellEditComputedNote');
                    var saveBtn = document.getElementById('cellEditSave');
                    var statusButtons = modalEl.querySelectorAll('.ma-status-pick');
                    var selectedStatus = null;
                    var activeCell = null;

                    var labelMap = @json($statusOptions);
                    var colorMap = @json($colorMap);

                    function openModal() { modalEl.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
                    function closeModal() { modalEl.classList.remove('is-open'); document.body.style.overflow = ''; activeCell = null; }

                    modalEl.addEventListener('click', function (e) {
                        if (e.target === modalEl) closeModal();
                        if (e.target.closest('[data-close]')) closeModal();
                    });
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') closeModal();
                    });

                    function setSelected(btn) {
                        statusButtons.forEach(function (b) { b.classList.remove('is-selected'); });
                        if (btn) {
                            btn.classList.add('is-selected');
                            selectedStatus = btn.dataset.status;
                        }
                    }

                    statusButtons.forEach(function (btn) {
                        btn.addEventListener('click', function () { setSelected(btn); });
                    });

                    grid.querySelectorAll('.sketch-cell').forEach(function (cell) {
                        cell.addEventListener('click', function () {
                            activeCell = cell;
                            keyInput.value = cell.dataset.key;
                            noteInput.value = cell.dataset.note || '';
                            var labelTxt = (cell.querySelector('.cell-label') && cell.querySelector('.cell-label').textContent.trim()) || cell.dataset.key;
                            titleEl.textContent = 'تعديل: ' + labelTxt;
                            selectedStatus = cell.dataset.status;
                            setSelected(null);
                            statusButtons.forEach(function (b) {
                                if (b.dataset.status === selectedStatus) setSelected(b);
                            });
                            var computed = cell.dataset.computed;
                            var compLbl = labelMap[computed] || computed;
                            computedNoteEl.textContent = 'الحالة الأصلية من النظام: ' + compLbl;
                            openModal();
                        });
                    });

                    saveBtn.addEventListener('click', function () {
                        if (!activeCell || !selectedStatus) {
                            alert('اختر حالة أولاً.');
                            return;
                        }
                        saveBtn.disabled = true;
                        var savedCell = activeCell;
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                cell_key: keyInput.value,
                                status: selectedStatus,
                                note: noteInput.value || null,
                            }),
                        }).then(function (res) {
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            return res.json();
                        }).then(function (data) {
                            if (!data || !data.ok) throw new Error('Server error');
                            var c = colorMap[data.cell.status] || colorMap['available'];
                            savedCell.style.background = c.bg;
                            savedCell.style.color = c.text;
                            savedCell.dataset.status = data.cell.status;
                            savedCell.dataset.note = data.cell.note || '';
                            savedCell.classList.add('is-manual');
                            var statusSpan = savedCell.querySelector('.cell-status');
                            if (statusSpan) statusSpan.textContent = data.cell.label;
                            var iconEl = savedCell.querySelector('.cell-label i');
                            if (iconEl) iconEl.className = 'fa-solid ' + (c.icon || 'fa-circle') + ' ms-1';
                            closeModal();
                        }).catch(function () {
                            alert('تعذّر حفظ التعديل. حاول مرة أخرى.');
                        }).finally(function () {
                            saveBtn.disabled = false;
                        });
                    });
                })();
            </script>
        @endif
    @endif
@endsection
