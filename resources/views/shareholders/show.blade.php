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
                                <th class="text-end">مخطط</th>
                                <th class="text-end">%</th>
                                <th class="text-end">فعلي</th>
                                <th class="text-end">%</th>
                                <th class="text-end">جاري</th>
                                @can('shareholders.manage')
                                    <th class="text-end">إجراء</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($projectBreakdown as $row)
                                @php
                                    $pInv = (float) ($row->membership->planned_investment ?? $row->membership->total_investment ?? 0);
                                    $aInv = (float) ($row->membership->actual_investment ?? $row->capital_deposits ?? 0);
                                @endphp
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('projects.landing', $row->project) }}" class="link-primary text-decoration-none">
                                            {{ $row->project->name }}
                                        </a>
                                    </td>
                                    <td class="text-end font-monospace">{{ number_format($pInv, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) ($row->membership->planned_percentage ?? $row->membership->share_percentage ?? 0), 2) }}%</td>
                                    <td class="text-end font-monospace">{{ number_format($aInv, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) ($row->membership->actual_percentage ?? 0), 2) }}%</td>
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
                                                data-funding-pct="{{ number_format((float) ($row->membership->planned_percentage ?? $row->membership->share_percentage ?? 0), 2, '.', '') }}"
                                                data-funding-amount=""
                                                data-funding-hint="عدّل النسبة المخططة. التمويل النقدي اختياري ويحدّث رأس المال الفعلي في الجاري."
                                            >
                                                تعديل النسبة
                                            </button>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr><td colspan="{{ auth()->user()?->can('shareholders.manage') ? 7 : 6 }}" class="text-muted text-center">لا توجد مشاريع.</td></tr>
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
                                <th class="text-end">مخطط</th>
                                <th class="text-end">%</th>
                                <th class="text-end">فعلي</th>
                                <th class="text-end">%</th>
                                <th class="text-end">جاري</th>
                                @can('shareholders.manage')
                                    <th class="text-end">إجراء</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($landBreakdown as $row)
                                @php
                                    $lpInv = (float) ($row->membership->planned_investment ?? $row->membership->total_investment ?? 0);
                                    $laInv = (float) ($row->membership->actual_investment ?? $row->capital_deposits ?? 0);
                                @endphp
                                <tr>
                                    <td class="fw-semibold">
                                        @if ($row->parcel)
                                            <a href="{{ route('land-trading.show', $row->parcel) }}" class="link-primary text-decoration-none">{{ $row->parcel->name }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace">{{ number_format($lpInv, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) ($row->membership->planned_percentage ?? $row->membership->share_percentage ?? 0), 2) }}%</td>
                                    <td class="text-end font-monospace">{{ number_format($laInv, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) ($row->membership->actual_percentage ?? 0), 2) }}%</td>
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
                                                    data-funding-pct="{{ number_format((float) ($row->membership->planned_percentage ?? $row->membership->share_percentage ?? 0), 2, '.', '') }}"
                                                    data-funding-amount=""
                                                    data-funding-hint="عدّل النسبة المخططة. التمويل النقدي اختياري. السدادات على الأرض تبني الفعلي."
                                                >
                                                    تعديل النسبة
                                                </button>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr><td colspan="{{ auth()->user()?->can('shareholders.manage') ? 7 : 6 }}" class="text-muted text-center">لا توجد أراضي.</td></tr>
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
                                <div class="col-md-4">
                                    <label class="form-label">المشروع</label>
                                    <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                                        <option value="">اختر…</option>
                                        @foreach ($availableProjects as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">النسبة %</label>
                                    <input type="number" step="0.01" min="0.01" max="100" name="share_percentage" class="form-control font-monospace @error('share_percentage') is-invalid @enderror" value="{{ old('share_percentage') }}" required>
                                    @error('share_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">تمويل نقدي (اختياري)</label>
                                    <input type="number" step="0.01" min="0" name="total_investment" class="form-control font-monospace @error('total_investment') is-invalid @enderror" value="{{ old('total_investment') }}">
                                    @error('total_investment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-2">
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
                                <div class="col-md-4">
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
                                <div class="col-md-3">
                                    <label class="form-label">النسبة %</label>
                                    <input type="number" step="0.01" min="0.01" max="100" name="share_percentage" class="form-control font-monospace @error('share_percentage') is-invalid @enderror" value="{{ old('share_percentage') }}" required>
                                    @error('share_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">تمويل نقدي (اختياري)</label>
                                    <input type="number" step="0.01" min="0" name="total_investment" class="form-control font-monospace @error('total_investment') is-invalid @enderror" value="{{ old('total_investment') }}">
                                    @error('total_investment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">ربط</button>
                                </div>
                            </form>
                            <div class="form-text mt-2">النسبة إلزامية · التمويل النقدي اختياري (الفعلي يتراكم بالسداد).</div>
                        @endif
                    </div>
                </div>
            @endcan
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0"><i class="fa-solid fa-map-location-dot text-warning ms-1"></i> حركات أراضي البيع/الشراء</h5>
                <div class="small text-body-secondary">سداد = يخصم من الجاري · تحصيل = يضيف للجاري</div>
            </div>
            <span class="badge text-bg-light">{{ ($landPayments ?? collect())->count() }} حركة</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الأرض</th>
                        <th>النوع</th>
                        <th class="text-end">المبلغ</th>
                        <th>خرج من حساب</th>
                        <th>دخل حساب</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($landPayments ?? [] as $payment)
                        <tr>
                            <td class="font-monospace small">{{ $payment->paid_at?->format('Y-m-d') }}</td>
                            <td class="small">
                                @if ($payment->landParcel)
                                    <a href="{{ route('land-trading.show', $payment->landParcel) }}" class="link-primary text-decoration-none fw-semibold">
                                        {{ $payment->landParcel->name }}
                                    </a>
                                    @if ($payment->part)
                                        <span class="text-body-secondary">/ {{ $payment->part->name }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $payment->side === 'purchase' ? 'text-bg-danger' : 'text-bg-success' }}">
                                    {{ $payment->side === 'purchase' ? 'سداد شراء' : 'تحصيل بيع' }}
                                </span>
                                <div class="small text-body-secondary">{{ $payment->kindLabel() }}</div>
                            </td>
                            <td class="text-end font-monospace">{{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="small">
                                @if ($payment->side === 'purchase')
                                    @if ($payment->paidByShareholder)
                                        <a href="{{ route('shareholders.show', $payment->paidByShareholder) }}"
                                           class="fw-semibold link-dark text-decoration-none {{ (int) $payment->paid_by_shareholder_id === (int) $shareholder->id ? '' : 'text-body-secondary' }}">
                                            {{ $payment->paidByShareholder->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                @else
                                    <span class="text-body-secondary">المشتري</span>
                                @endif
                            </td>
                            <td class="small">
                                @if ($payment->side === 'sale')
                                    @if ($payment->receivedByShareholder)
                                        <a href="{{ route('shareholders.show', $payment->receivedByShareholder) }}"
                                           class="fw-semibold link-dark text-decoration-none {{ (int) $payment->received_by_shareholder_id === (int) $shareholder->id ? '' : 'text-body-secondary' }}">
                                            {{ $payment->receivedByShareholder->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                @else
                                    <a href="{{ route('land-cashbox.index') }}" class="text-body-secondary text-decoration-none">صندوق الأراضي</a>
                                @endif
                            </td>
                            <td class="small">{{ $payment->approval_status }}</td>
                            <td class="text-end">
                                @if ($payment->landParcel)
                                    <a href="{{ route('land-trading.show', $payment->landParcel) }}" class="btn btn-outline-info btn-sm">الأرض</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                لا توجد سدادات/تحصيلات منسوبة لهذا المساهم بعد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
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
                                    <span class="badge text-bg-primary">مشروع</span>
                                    <a href="{{ route('projects.landing', $entry->project) }}" class="link-primary text-decoration-none">
                                        {{ $entry->project->name }}
                                    </a>
                                @elseif ($entry->landParcel)
                                    <span class="badge text-bg-warning">أرض</span>
                                    <a href="{{ route('land-trading.show', $entry->landParcel) }}" class="link-primary text-decoration-none">
                                        {{ $entry->landParcel->name }}
                                    </a>
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
                                @if ($entry->treasuryTransaction && $entry->project)
                                    @php
                                        $st = $entry->treasuryTransaction->approval_status;
                                        $badge = $st === 'approved' ? 'text-bg-success' : ($st === 'pending' ? 'text-bg-warning' : 'text-bg-secondary');
                                        $label = $st === 'approved' ? 'معتمد' : ($st === 'pending' ? 'معلّق' : $st);
                                    @endphp
                                    <a href="{{ route('cashbox.index', $entry->project) }}" class="text-decoration-none">
                                        <span class="badge {{ $badge }}">{{ $label }}</span>
                                    </a>
                                @elseif ($entry->treasuryTransaction)
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
                                    @php
                                        $canAllocate = $entry->direction === 'credit'
                                            && $entry->project_id
                                            && ($allocateTargetProjects ?? collect())->where('id', '!=', (int) $entry->project_id)->isNotEmpty()
                                            && $entry->remainingAllocatableAmount() >= 0.01;
                                    @endphp
                                    <div class="d-inline-flex gap-1 justify-content-end flex-wrap">
                                        @if ($canAllocate)
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#allocateLedgerModal"
                                                data-allocate-id="{{ $entry->id }}"
                                                data-allocate-action="{{ route('shareholders.ledger.allocate', [$shareholder, $entry]) }}"
                                                data-allocate-amount="{{ number_format((float) $entry->amount, 2, '.', '') }}"
                                                data-allocate-remaining="{{ number_format($entry->remainingAllocatableAmount(), 2, '.', '') }}"
                                                data-allocate-project-id="{{ (int) $entry->project_id }}"
                                                data-allocate-project="{{ e($entry->project?->name ?? '—') }}"
                                                data-allocate-date="{{ $entry->entry_date?->format('Y-m-d') }}"
                                                data-allocate-type="{{ e($entry->typeLabel()) }}"
                                                data-allocate-notes="{{ e(\Illuminate\Support\Str::limit((string) ($entry->notes ?: ''), 80)) }}"
                                            >
                                                توزيع على مشروع
                                            </button>
                                        @endif
                                        <form method="post"
                                              action="{{ route('shareholders.ledger.destroy', [$shareholder, $entry]) }}"
                                              class="d-inline"
                                              data-swal-confirm="{{ e('حذف الحركة وحركة الصندوق المرتبطة؟') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                        </form>
                                    </div>
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
                            <td>
                                <a href="{{ route('projects.landing', $row->project) }}" class="link-primary text-decoration-none fw-semibold">
                                    {{ $row->project->name }}
                                </a>
                            </td>
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
                            <td>
                                <a href="{{ route('properties.show', [$p->project_id, $p]) }}" class="link-primary text-decoration-none fw-semibold">
                                    {{ $p->name }}
                                </a>
                            </td>
                            <td>
                                @if ($p->project)
                                    <a href="{{ route('projects.landing', $p->project) }}" class="link-primary text-decoration-none">
                                        {{ $p->project->name }}
                                    </a>
                                @else
                                    {{ $p->project_id }}
                                @endif
                            </td>
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
                        <h5 class="modal-title fw-semibold" id="fundingModalLabel">تعديل النسبة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <form method="post" id="fundingModalForm" action="{{ route('shareholders.funding.update', $shareholder) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="target_type" id="funding-target-type" value="{{ old('target_type') }}">
                        <input type="hidden" name="target_id" id="funding-target-id" value="{{ old('target_id') }}">
                        <div class="modal-body">
                            <div class="small text-body-secondary mb-2" id="fundingModalLabelTarget">—</div>
                            <label class="form-label fw-semibold" for="funding-share-percentage">نسبة المساهمة (%)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                max="100"
                                name="share_percentage"
                                id="funding-share-percentage"
                                value="{{ old('share_percentage') }}"
                                class="form-control font-monospace @error('share_percentage') is-invalid @enderror"
                                required
                            >
                            @error('share_percentage')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <label class="form-label fw-semibold mt-3" for="funding-total-investment">تمويل نقدي فعلي (اختياري)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="total_investment"
                                id="funding-total-investment"
                                value="{{ old('total_investment') }}"
                                class="form-control font-monospace @error('total_investment') is-invalid @enderror"
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
                            <button type="submit" class="btn btn-warning">حفظ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="allocateLedgerModal" tabindex="-1" aria-labelledby="allocateLedgerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-bottom-0 pb-0">
                        <div>
                            <h5 class="modal-title fw-semibold mb-1" id="allocateLedgerModalLabel">توزيع من حركة الجاري</h5>
                            <div class="small text-body-secondary">ينقل المبلغ لمشروع آخر في الجاري، ويحوّل نفس المبلغ من صندوق المصدر إلى صندوق الهدف</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <form method="post" id="allocateLedgerForm" action="#">
                        @csrf
                        <div class="modal-body pt-3">
                            <div class="rounded-3 border bg-body-tertiary p-3 mb-3">
                                <div class="row g-2 small">
                                    <div class="col-md-4">
                                        <div class="text-body-secondary">المصدر</div>
                                        <div class="fw-semibold" id="allocate-source-project">—</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-body-secondary">التاريخ / النوع</div>
                                        <div class="fw-semibold"><span id="allocate-source-date">—</span> · <span id="allocate-source-type">—</span></div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-body-secondary">أصل الحركة</div>
                                        <div class="fw-semibold font-monospace text-success" id="allocate-source-amount">—</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-body-secondary">المتبقي القابل للتوزيع</div>
                                        <div class="fw-semibold font-monospace text-primary" id="allocate-source-remaining">—</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="text-body-secondary">ملاحظة الحركة</div>
                                        <div id="allocate-source-notes" class="text-truncate">—</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="allocate-target-project">المشروع الهدف</label>
                                <select name="target_project_id" id="allocate-target-project" class="form-select @error('target_project_id') is-invalid @enderror" required>
                                    <option value="">اختر المشروع…</option>
                                    @foreach (($allocateTargetProjects ?? collect()) as $p)
                                        <option value="{{ $p->id }}" @selected((string) old('target_project_id') === (string) $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                @error('target_project_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text">يظهر فقط المشاريع المرتبطة بهذا المساهم (غير مشروع المصدر).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">طريقة التوزيع</label>
                                <div class="btn-group w-100" role="group" aria-label="وضع التوزيع">
                                    <input type="radio" class="btn-check" name="mode" id="allocate-mode-percentage" value="percentage" autocomplete="off" @checked(old('mode', 'percentage') === 'percentage')>
                                    <label class="btn btn-outline-primary" for="allocate-mode-percentage">بالنسبة %</label>
                                    <input type="radio" class="btn-check" name="mode" id="allocate-mode-amount" value="amount" autocomplete="off" @checked(old('mode') === 'amount')>
                                    <label class="btn btn-outline-primary" for="allocate-mode-amount">بالمبلغ</label>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6" id="allocate-percentage-wrap">
                                    <label class="form-label fw-semibold" for="allocate-percentage">النسبة من أصل الحركة (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0.01" max="100" name="percentage" id="allocate-percentage"
                                               value="{{ old('percentage') }}"
                                               class="form-control font-monospace @error('percentage') is-invalid @enderror"
                                               placeholder="مثال: 30">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('percentage')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary allocate-pct-preset" data-pct="25">25%</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary allocate-pct-preset" data-pct="50">50%</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary allocate-pct-preset" data-pct="75">75%</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary allocate-pct-preset" data-pct="100">الكل</button>
                                    </div>
                                </div>
                                <div class="col-md-6" id="allocate-amount-wrap" style="display:none">
                                    <label class="form-label fw-semibold" for="allocate-amount">المبلغ المراد توزيعه</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0.01" name="amount" id="allocate-amount"
                                               value="{{ old('amount') }}"
                                               class="form-control font-monospace @error('amount') is-invalid @enderror"
                                               placeholder="0.00">
                                        <span class="input-group-text">ج.م</span>
                                    </div>
                                    @error('amount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">معاينة المبلغ</label>
                                    <div class="form-control-plaintext font-monospace fs-5 fw-semibold text-primary" id="allocate-preview-amount">—</div>
                                    <div class="small text-body-secondary" id="allocate-preview-hint">يُخصم من جاري وصندوق المصدر، ويُضاف لجاري وصندوق الهدف.</div>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-semibold" for="allocate-notes">ملاحظة (اختياري)</label>
                                <textarea name="notes" id="allocate-notes" rows="2" class="form-control @error('notes') is-invalid @enderror" maxlength="1000" placeholder="سبب التوزيع أو مرجع…">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary px-4" id="allocate-submit-btn">تأكيد التوزيع</button>
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
                if (fundingModal) {
                    const typeInput = document.getElementById('funding-target-type');
                    const idInput = document.getElementById('funding-target-id');
                    const pctInput = document.getElementById('funding-share-percentage');
                    const amountInput = document.getElementById('funding-total-investment');
                    const labelEl = document.getElementById('fundingModalLabelTarget');
                    const hintEl = document.getElementById('fundingModalHint');

                    fundingModal.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;
                        if (!button) return;

                        typeInput.value = button.getAttribute('data-funding-type') || '';
                        idInput.value = button.getAttribute('data-funding-id') || '';
                        if (pctInput) pctInput.value = button.getAttribute('data-funding-pct') || '';
                        amountInput.value = button.getAttribute('data-funding-amount') || '';
                        labelEl.textContent = button.getAttribute('data-funding-label') || '—';
                        hintEl.textContent = button.getAttribute('data-funding-hint') || '';
                    });

                    @if ($errors->hasAny(['share_percentage', 'total_investment', 'target_id', 'target_type']))
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
                }

                const allocateModal = document.getElementById('allocateLedgerModal');
                if (!allocateModal) return;

                const form = document.getElementById('allocateLedgerForm');
                const targetSelect = document.getElementById('allocate-target-project');
                const pctInput = document.getElementById('allocate-percentage');
                const amountInput = document.getElementById('allocate-amount');
                const pctWrap = document.getElementById('allocate-percentage-wrap');
                const amountWrap = document.getElementById('allocate-amount-wrap');
                const previewEl = document.getElementById('allocate-preview-amount');
                const modePct = document.getElementById('allocate-mode-percentage');
                const modeAmt = document.getElementById('allocate-mode-amount');
                let sourceAmount = 0;
                let remaining = 0;
                let sourceProjectId = '';

                function money(n) {
                    return (Math.round((n + Number.EPSILON) * 100) / 100).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + ' ج.م';
                }

                function syncMode() {
                    const isPct = modePct && modePct.checked;
                    if (pctWrap) pctWrap.style.display = isPct ? '' : 'none';
                    if (amountWrap) amountWrap.style.display = isPct ? 'none' : '';
                    if (pctInput) pctInput.required = !!isPct;
                    if (amountInput) amountInput.required = !isPct;
                    updatePreview();
                }

                function updatePreview() {
                    let value = 0;
                    if (modePct && modePct.checked) {
                        const pct = parseFloat(pctInput && pctInput.value ? pctInput.value : '0') || 0;
                        value = Math.round(sourceAmount * (pct / 100) * 100) / 100;
                    } else {
                        value = parseFloat(amountInput && amountInput.value ? amountInput.value : '0') || 0;
                    }
                    if (previewEl) {
                        previewEl.textContent = value > 0 ? money(value) : '—';
                        previewEl.classList.toggle('text-danger', value > remaining + 0.001);
                        previewEl.classList.toggle('text-primary', value <= remaining + 0.001);
                    }
                }

                function filterTargets() {
                    if (!targetSelect) return;
                    Array.from(targetSelect.options).forEach(function (opt) {
                        if (!opt.value) return;
                        const hide = String(opt.value) === String(sourceProjectId);
                        opt.hidden = hide;
                        opt.disabled = hide;
                        if (hide && opt.selected) {
                            targetSelect.value = '';
                        }
                    });
                }

                allocateModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button || !button.hasAttribute('data-allocate-action') || !form) return;
                    populateFromButton(button);
                });

                function populateFromButton(button) {
                    form.action = button.getAttribute('data-allocate-action') || '#';
                    sourceAmount = parseFloat(button.getAttribute('data-allocate-amount') || '0') || 0;
                    remaining = parseFloat(button.getAttribute('data-allocate-remaining') || '0') || 0;
                    sourceProjectId = button.getAttribute('data-allocate-project-id') || '';

                    document.getElementById('allocate-source-project').textContent = button.getAttribute('data-allocate-project') || '—';
                    document.getElementById('allocate-source-date').textContent = button.getAttribute('data-allocate-date') || '—';
                    document.getElementById('allocate-source-type').textContent = button.getAttribute('data-allocate-type') || '—';
                    document.getElementById('allocate-source-amount').textContent = money(sourceAmount);
                    document.getElementById('allocate-source-remaining').textContent = money(remaining);
                    document.getElementById('allocate-source-notes').textContent = button.getAttribute('data-allocate-notes') || '—';

                    if (amountInput) {
                        amountInput.max = String(remaining);
                        amountInput.value = '';
                    }
                    if (pctInput) {
                        const maxPct = sourceAmount > 0 ? Math.min(100, Math.round((remaining / sourceAmount) * 10000) / 100) : 100;
                        pctInput.max = String(maxPct);
                        pctInput.value = '';
                    }
                    if (modePct) modePct.checked = true;
                    filterTargets();
                    syncMode();
                }

                if (modePct) modePct.addEventListener('change', syncMode);
                if (modeAmt) modeAmt.addEventListener('change', syncMode);
                if (pctInput) pctInput.addEventListener('input', updatePreview);
                if (amountInput) amountInput.addEventListener('input', updatePreview);

                document.querySelectorAll('.allocate-pct-preset').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!pctInput || !modePct) return;
                        modePct.checked = true;
                        syncMode();
                        const pct = parseFloat(btn.getAttribute('data-pct') || '0') || 0;
                        const maxPct = sourceAmount > 0 ? Math.min(100, (remaining / sourceAmount) * 100) : 100;
                        pctInput.value = String(Math.min(pct, Math.round(maxPct * 100) / 100));
                        updatePreview();
                    });
                });

                @if (session('open_allocate_ledger_id') || $errors->hasAny(['target_project_id', 'percentage', 'amount', 'mode', 'notes']))
                    (function () {
                        const id = @json(session('open_allocate_ledger_id'));
                        const btn = id
                            ? document.querySelector('[data-allocate-id="' + id + '"]')
                            : document.querySelector('[data-allocate-action]');
                        if (!btn) return;
                        populateFromButton(btn);
                        @if (old('percentage') !== null)
                            if (pctInput) pctInput.value = @json(old('percentage'));
                        @endif
                        @if (old('amount') !== null)
                            if (amountInput) amountInput.value = @json(old('amount'));
                        @endif
                        @if (old('mode') === 'amount')
                            if (modeAmt) modeAmt.checked = true;
                        @else
                            if (modePct) modePct.checked = true;
                        @endif
                        @if (old('target_project_id'))
                            if (targetSelect) targetSelect.value = @json((string) old('target_project_id'));
                        @endif
                        @if (old('notes'))
                            const notesEl = document.getElementById('allocate-notes');
                            if (notesEl) notesEl.value = @json(old('notes'));
                        @endif
                        syncMode();
                        bootstrap.Modal.getOrCreateInstance(allocateModal).show();
                    })();
                @endif
            })();
        </script>
    @endpush
@endsection
