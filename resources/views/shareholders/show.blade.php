@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <x-partials.module-kpis :items="[
        ['label' => 'رصيد الجاري الموحّد', 'value' => number_format((float) $ledgerBalance, 2) . ' ج.م'],
        ['label' => 'رأس المال (كل المشاريع)', 'value' => number_format((float) $capitalDepositsTotal, 2) . ' ج.م'],
        ['label' => 'عدد المشاريع', 'value' => $memberships->count()],
        ['label' => 'عقارات ضمن التوزيع', 'value' => $participations->count()],
    ]" />

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card app-surface h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">البيانات الأساسية</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('shareholders.edit', $shareholder) }}" class="btn btn-outline-warning btn-sm">تعديل الاسم</a>
                        <a href="{{ route('shareholders.index') }}" class="btn btn-outline-secondary btn-sm">قائمة المساهمين</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-muted small mb-1">اسم المساهم</div>
                    <div class="fw-semibold fs-5 mb-3">{{ $shareholder->name }}</div>
                    <div class="text-muted small mb-2">المشاريع المرتبطة</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr>
                                <th>المشروع</th>
                                <th class="text-end">التمويل</th>
                                <th class="text-end">النسبة</th>
                                <th class="text-end">جاري المشروع</th>
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
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center">لا توجد مشاريع مرتبطة.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            @can('shareholders.manage')
                <div class="card app-surface h-100">
                    <div class="card-header">
                        <h5 class="mb-0">ربط بمشروع إضافي</h5>
                    </div>
                    <div class="card-body">
                        @if ($availableProjects->isEmpty())
                            <p class="text-muted small mb-0">المساهم مرتبط بكل المشاريع المتاحة أو لا توجد مشاريع برأس مال.</p>
                        @else
                            <form method="post" action="{{ route('shareholders.projects.attach', $shareholder) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">المشروع</label>
                                    <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                                        <option value="">اختر…</option>
                                        @foreach ($availableProjects as $p)
                                            <option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>
                                                {{ $p->name }} — رأس المال {{ number_format((float) $p->capital, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">التمويل في المشروع (ج.م)</label>
                                    <input type="number" step="0.01" min="0.01" name="total_investment"
                                           class="form-control font-monospace @error('total_investment') is-invalid @enderror"
                                           value="{{ old('total_investment') }}" required>
                                    @error('total_investment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <button type="submit" class="btn btn-primary w-100">ربط وتسجيل رأس المال</button>
                            </form>
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
                @if ($memberships->isEmpty())
                    <div class="alert alert-warning">اربط المساهم بمشروع أولاً قبل تسجيل حركات الجاري.</div>
                @else
                    <form method="post" action="{{ route('shareholders.ledger.store', $shareholder) }}" class="border rounded-3 p-3 mb-4 bg-body-tertiary bg-opacity-50">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">المشروع المستهدف</label>
                                <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                                    <option value="">اختر المشروع…</option>
                                    @foreach ($memberships as $m)
                                        <option value="{{ $m->project_id }}" @selected((string) old('project_id') === (string) $m->project_id)>
                                            {{ $m->project?->name ?? ('#'.$m->project_id) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">تحدد صندوق أي مشروع تتأثر به الحركة.</div>
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
                        <div class="form-text mt-2">عند الإيداع/السحب يُحدَّث <strong>صندوق المشروع المختار</strong> فقط.</div>
                    </form>
                @endif
            @endcan

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>المشروع</th>
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
                            <td class="small fw-semibold">{{ $entry->project?->name ?? '—' }}</td>
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

    @push('scripts')
        <script>
            (function () {
                const typeEl = document.getElementById('ledger-type');
                const dirWrap = document.getElementById('ledger-direction-wrap');
                if (!typeEl || !dirWrap) return;
                const sync = () => { dirWrap.style.display = typeEl.value === 'adjustment' ? '' : 'none'; };
                typeEl.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endpush
@endsection
