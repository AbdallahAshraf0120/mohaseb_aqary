@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (empty($landsSchemaReady))
        <div class="alert alert-warning">
            ميزة أراضي البيع/شراء تحتاج تحديث قاعدة البيانات على السيرفر:
            <code class="mx-1">php artisan migrate --force</code>
        </div>
    @endif

    <x-partials.module-kpis :items="[
        ['label' => 'رصيد الجاري الموحّد', 'value' => number_format((float) $ledgerBalance, 2) . ' ج.م'],
        ['label' => 'رأس المال (دفتر)', 'value' => number_format((float) $capitalDepositsTotal, 2) . ' ج.م'],
        ['label' => 'مشاريع', 'value' => $memberships->count()],
        ['label' => 'أراضي بيع/شراء', 'value' => $landMemberships->count()],
    ]" />

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card app-surface h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ $shareholder->name }}</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('shareholders.edit', $shareholder) }}" class="btn btn-outline-warning btn-sm">تعديل الاسم</a>
                        <a href="{{ route('shareholders.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-muted small mb-2">المشاريع المرتبطة</div>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr>
                                <th>المشروع</th>
                                <th class="text-end">التمويل</th>
                                <th class="text-end">النسبة</th>
                                <th class="text-end">جاري</th>
                                @can('shareholders.manage')
                                    <th class="text-end">إجراء</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($projectBreakdown as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->project->name }}</td>
                                    <td class="text-end font-monospace">{{ number_format((float) $row->membership->total_investment, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $row->membership->share_percentage, 2) }}%</td>
                                    <td class="text-end font-monospace {{ $row->ledger_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format((float) $row->ledger_balance, 2) }}
                                    </td>
                                    @can('shareholders.manage')
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-outline-warning btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#fundingModal"
                                                data-funding-type="project"
                                                data-funding-id="{{ $row->project->id }}"
                                                data-funding-label="مشروع: {{ $row->project->name }}"
                                                data-funding-amount="{{ number_format((float) $row->membership->total_investment, 2, '.', '') }}"
                                                data-funding-hint="النسبة = التمويل ÷ رأس مال المشروع × 100. الزيادة/التخفيض تُسجَّل في دفتر الجاري."
                                            >
                                                تعديل التمويل
                                            </button>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr><td colspan="{{ auth()->user()?->can('shareholders.manage') ? 5 : 4 }}" class="text-muted text-center">لا توجد مشاريع.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted small mb-2">أراضي البيع والشراء المرتبطة</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr>
                                <th>الأرض</th>
                                <th class="text-end">التمويل</th>
                                <th class="text-end">النسبة</th>
                                <th class="text-end">جاري</th>
                                @can('shareholders.manage')
                                    <th class="text-end">إجراء</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($landBreakdown as $row)
                                <tr>
                                    <td class="fw-semibold">
                                        @if ($row->parcel)
                                            <a href="{{ route('land-trading.show', $row->parcel) }}">{{ $row->parcel->name }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace">{{ number_format((float) $row->membership->total_investment, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $row->membership->share_percentage, 2) }}%</td>
                                    <td class="text-end font-monospace {{ $row->ledger_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format((float) $row->ledger_balance, 2) }}
                                    </td>
                                    @can('shareholders.manage')
                                        <td class="text-end">
                                            @if ($row->parcel)
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-warning btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#fundingModal"
                                                    data-funding-type="land"
                                                    data-funding-id="{{ $row->parcel->id }}"
                                                    data-funding-label="أرض: {{ $row->parcel->name }}"
                                                    data-funding-amount="{{ number_format((float) $row->membership->total_investment, 2, '.', '') }}"
                                                    data-funding-hint="النسبة = التمويل ÷ سعر شراء الأرض × 100. الزيادة/التخفيض تُسجَّل في دفتر الجاري."
                                                >
                                                    تعديل التمويل
                                                </button>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr><td colspan="{{ auth()->user()?->can('shareholders.manage') ? 5 : 4 }}" class="text-muted text-center">لا توجد أراضي.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            @can('shareholders.manage')
                <div class="card app-surface mb-3">
                    <div class="card-header"><h5 class="mb-0">ربط بمشروع</h5></div>
                    <div class="card-body">
                        @if ($availableProjects->isEmpty())
                            <p class="text-muted small mb-0">لا توجد مشاريع متاحة للربط.</p>
                        @else
                            <form method="post" action="{{ route('shareholders.projects.attach', $shareholder) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-5">
                                    <label class="form-label">المشروع</label>
                                    <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                                        <option value="">اختر…</option>
                                        @foreach ($availableProjects as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">التمويل</label>
                                    <input type="number" step="0.01" min="0.01" name="total_investment" class="form-control font-monospace @error('total_investment') is-invalid @enderror" required>
                                    @error('total_investment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">ربط</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="card app-surface">
                    <div class="card-header"><h5 class="mb-0">ربط بأرض بيع/شراء</h5></div>
                    <div class="card-body">
                        @if ($availableLands->isEmpty())
                            <p class="text-muted small mb-0">لا توجد أراضي بسعر شراء متاحة للربط. سجّل سعر الشراء من شاشة الأرض أولاً.</p>
                        @else
                            <form method="post" action="{{ route('shareholders.lands.attach', $shareholder) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-5">
                                    <label class="form-label">الأرض</label>
                                    <select name="land_parcel_id" class="form-select @error('land_parcel_id') is-invalid @enderror" required>
                                        <option value="">اختر…</option>
                                        @foreach ($availableLands as $land)
                                            <option value="{{ $land->id }}">
                                                {{ $land->name }} — شراء {{ number_format((float) $land->purchase_price, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('land_parcel_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">التمويل</label>
                                    <input type="number" step="0.01" min="0.01" name="total_investment" class="form-control font-monospace @error('total_investment') is-invalid @enderror" required>
                                    @error('total_investment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">ربط</button>
                                </div>
                            </form>
                            <div class="form-text mt-2">النسبة = التمويل ÷ سعر شراء الأرض × 100.</div>
                        @endif
                    </div>
                </div>
            @endcan
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fa-solid fa-book-open text-primary ms-1"></i> دفتر جاري المساهم (موحّد)</h5>
            <span class="badge {{ $ledgerBalance >= 0 ? 'text-bg-success' : 'text-bg-danger' }}">
                الرصيد: {{ number_format((float) $ledgerBalance, 2) }} ج.م
            </span>
        </div>
        <div class="card-body">
            @can('shareholders.manage')
                @if ($memberships->isEmpty() && $landMemberships->isEmpty())
                    <div class="alert alert-warning">اربط المساهم بمشروع أو أرض أولاً قبل تسجيل حركات الجاري.</div>
                @else
                    <form method="post" action="{{ route('shareholders.ledger.store', $shareholder) }}" class="border rounded-3 p-3 mb-4 bg-body-tertiary bg-opacity-50">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">الوجهة (مشروع / أرض)</label>
                                <select name="destination" class="form-select @error('destination') is-invalid @enderror" required>
                                    <option value="">اختر…</option>
                                    @if ($memberships->isNotEmpty())
                                        <optgroup label="مشاريع">
                                            @foreach ($memberships as $m)
                                                <option value="project:{{ $m->project_id }}" @selected(old('destination') === 'project:'.$m->project_id)>
                                                    {{ $m->project?->name ?? ('مشروع #'.$m->project_id) }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if ($landMemberships->isNotEmpty())
                                        <optgroup label="أراضي بيع/شراء">
                                            @foreach ($landMemberships as $m)
                                                <option value="land:{{ $m->land_parcel_id }}" @selected(old('destination') === 'land:'.$m->land_parcel_id)>
                                                    {{ $m->landParcel?->name ?? ('أرض #'.$m->land_parcel_id) }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                @error('destination') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">المشروع يحدّث صندوقه — الأرض تُسجَّل في الجاري فقط.</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">نوع الحركة</label>
                                <select name="type" id="ledger-type" class="form-select @error('type') is-invalid @enderror" required>
                                    @foreach (\App\Models\ShareholderLedgerEntry::TYPES as $k => $v)
                                        <option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>
                                    @endforeach
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2" id="ledger-direction-wrap" style="display:none;">
                                <label class="form-label">اتجاه التسوية</label>
                                <select name="direction" class="form-select">
                                    <option value="credit" @selected(old('direction', 'credit') === 'credit')>دائن (+)</option>
                                    <option value="debit" @selected(old('direction') === 'debit')>مدين (−)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">المبلغ</label>
                                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}"
                                       class="form-control font-monospace @error('amount') is-invalid @enderror" required>
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">التاريخ</label>
                                <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}"
                                       class="form-control @error('entry_date') is-invalid @enderror" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ملاحظات</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="اختياري">
                            </div>
                            <div class="col-12 col-lg-auto">
                                <button type="submit" class="btn btn-primary">تسجيل الحركة</button>
                            </div>
                        </div>
                        <div class="form-text mt-2">اختر مشروعًا أو أرضًا كوجهة للفلوس.</div>
                    </form>
                @endif
            @endcan

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الوجهة</th>
                        <th>النوع</th>
                        <th>الاتجاه</th>
                        <th class="text-end">المبلغ</th>
                        <th>الصندوق</th>
                        <th>ملاحظات</th>
                        <th class="text-end">عمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($ledgerEntries as $entry)
                        <tr>
                            <td class="font-monospace small">{{ $entry->entry_date?->format('Y-m-d') }}</td>
                            <td class="small fw-semibold">
                                @if ($entry->project)
                                    <span class="badge text-bg-primary">مشروع</span> {{ $entry->project->name }}
                                @elseif ($entry->landParcel)
                                    <span class="badge text-bg-warning">أرض</span> {{ $entry->landParcel->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $entry->typeLabel() }}</td>
                            <td>
                                @if ($entry->direction === 'credit')
                                    <span class="badge text-bg-success">دائن</span>
                                @else
                                    <span class="badge text-bg-danger">مدين</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace fw-semibold {{ $entry->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $entry->direction === 'credit' ? '+' : '−' }}{{ number_format((float) $entry->amount, 2) }}
                            </td>
                            <td class="small">
                                @if ($entry->treasuryTransaction)
                                    @php
                                        $st = $entry->treasuryTransaction->approval_status;
                                        $badge = $st === 'approved' ? 'text-bg-success' : ($st === 'pending' ? 'text-bg-warning' : 'text-bg-secondary');
                                        $label = $st === 'approved' ? 'معتمد' : ($st === 'pending' ? 'معلّق' : $st);
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $label }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">{{ $entry->notes ?: '—' }}</td>
                            <td class="text-end">
                                @can('shareholders.manage')
                                    <form method="post"
                                          action="{{ route('shareholders.ledger.destroy', [$shareholder, $entry]) }}"
                                          class="d-inline"
                                          data-swal-confirm="{{ e('حذف الحركة وحركة الصندوق المرتبطة؟') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">لا توجد حركات جاري بعد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header">
            <h5 class="mb-0">مرجع محسوب حسب المشروع (ليس دفتر)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>المشروع</th>
                        <th class="text-end">المنسب التشغيلي</th>
                        <th class="text-end">حصة التكاليف</th>
                        <th class="text-end">جاري تقريبي</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($projectBreakdown as $row)
                        <tr>
                            <td>{{ $row->project->name }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) $row->attributed_operating, 2) }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) $row->attributed_cost, 2) }}</td>
                            <td class="text-end font-monospace {{ $row->approx_current >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format((float) $row->approx_current, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">—</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header">
            <h5 class="mb-0">العقارات ضمن توزيع المساهمين</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0 table-sm">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>العقار</th>
                        <th>المشروع</th>
                        <th>المنطقة</th>
                        <th>النسبة على العقار</th>
                        <th class="text-end">عرض</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($participations as $item)
                        @php($p = $item->property)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $p->name }}</td>
                            <td>{{ $p->project?->name ?? $p->project_id }}</td>
                            <td>{{ $p->area?->name ?? '—' }}</td>
                            <td><span class="badge text-bg-primary">{{ number_format((float) $item->percentage, 2) }}%</span></td>
                            <td class="text-end">
                                <a href="{{ route('properties.show', [$p->project_id, $p]) }}" class="btn btn-outline-info btn-sm">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                لا يوجد ضمن توزيع المساهمين على أي عقار بعد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('shareholders.manage')
        <div class="modal fade" id="fundingModal" tabindex="-1" aria-labelledby="fundingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold" id="fundingModalLabel">تعديل التمويل</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <form method="post" id="fundingModalForm" action="{{ route('shareholders.funding.update', $shareholder) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="target_type" id="funding-target-type" value="{{ old('target_type') }}">
                        <input type="hidden" name="target_id" id="funding-target-id" value="{{ old('target_id') }}">
                        <div class="modal-body">
                            <div class="small text-body-secondary mb-2" id="fundingModalLabelTarget">—</div>
                            <label class="form-label fw-semibold" for="funding-total-investment">التمويل الجديد</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                name="total_investment"
                                id="funding-total-investment"
                                value="{{ old('total_investment') }}"
                                class="form-control font-monospace @error('total_investment') is-invalid @enderror"
                                required
                            >
                            @error('total_investment')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('target_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="form-text mt-2" id="fundingModalHint"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-warning">حفظ التمويل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @push('scripts')
        <script>
            (function () {
                const typeEl = document.getElementById('ledger-type');
                const dirWrap = document.getElementById('ledger-direction-wrap');
                if (typeEl && dirWrap) {
                    const sync = () => { dirWrap.style.display = typeEl.value === 'adjustment' ? '' : 'none'; };
                    typeEl.addEventListener('change', sync);
                    sync();
                }

                const fundingModal = document.getElementById('fundingModal');
                if (!fundingModal) return;

                const typeInput = document.getElementById('funding-target-type');
                const idInput = document.getElementById('funding-target-id');
                const amountInput = document.getElementById('funding-total-investment');
                const labelEl = document.getElementById('fundingModalLabelTarget');
                const hintEl = document.getElementById('fundingModalHint');

                fundingModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    typeInput.value = button.getAttribute('data-funding-type') || '';
                    idInput.value = button.getAttribute('data-funding-id') || '';
                    amountInput.value = button.getAttribute('data-funding-amount') || '';
                    labelEl.textContent = button.getAttribute('data-funding-label') || '—';
                    hintEl.textContent = button.getAttribute('data-funding-hint') || '';
                });

                @if ($errors->hasAny(['total_investment', 'target_id', 'target_type']))
                    (function () {
                        const type = @json(old('target_type'));
                        const id = @json(old('target_id'));
                        if (type && id) {
                            const btn = document.querySelector(
                                '[data-funding-type="' + type + '"][data-funding-id="' + id + '"]'
                            );
                            if (btn) {
                                labelEl.textContent = btn.getAttribute('data-funding-label') || '—';
                                hintEl.textContent = btn.getAttribute('data-funding-hint') || '';
                            }
                        }
                        bootstrap.Modal.getOrCreateInstance(fundingModal).show();
                    })();
                @endif
            })();
        </script>
    @endpush
@endsection
