@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @php
        $projectsCount = $participations->count();
        $avgShareInProjects = $projectsCount > 0
            ? round((float) $participations->avg('percentage'), 2)
            : null;
        $finByPid = collect($participationFinancialBreakdown)->keyBy('property_id');
    @endphp

    <x-partials.module-kpis :items="[
        ['label' => 'رصيد الجاري (دفتر)', 'value' => number_format((float) $ledgerBalance, 2) . ' ج.م'],
        ['label' => 'رأس المال (مجموع الإيداعات)', 'value' => number_format((float) $capitalDepositsTotal, 2) . ' ج.م'],
        ['label' => 'عدد العقارات (ضمن التوزيع)', 'value' => $projectsCount],
        ['label' => 'متوسط النسبة في العقارات', 'value' => $avgShareInProjects !== null ? number_format($avgShareInProjects, 2) . '%' : '—'],
        ['label' => 'المنسب التشغيلي (مرجع)', 'value' => number_format((float) $attributedOperatingTotal, 2) . ' ج.م'],
        ['label' => 'جاري تقريبي (مرجع)', 'value' => number_format((float) $shareholderCurrentAccountApprox, 2) . ' ج.م'],
    ]" />

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card app-surface h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">البيانات الأساسية</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('shareholders.edit', $shareholder) }}" class="btn btn-outline-warning btn-sm">تعديل المساهم</a>
                        <a href="{{ route('shareholders.index') }}" class="btn btn-outline-secondary btn-sm">قائمة المساهمين</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">اسم المساهم</div>
                            <div class="fw-semibold">{{ $shareholder->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">المشروع</div>
                            <div class="fw-semibold">{{ $project->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">النسبة العامة المسجّلة في الملف</div>
                            <div class="fw-semibold">{{ number_format((float) $shareholder->share_percentage, 2) }}%</div>
                            <div class="small text-muted mt-1">مرجع عام؛ التوزيع الفعلي على التحصيل والتكلفة يعتمد على النسبة داخل كل عقار.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">رأس المال (مجموع إيداعات الدفتر)</div>
                            <div class="fw-semibold font-monospace">{{ number_format((float) $capitalDepositsTotal, 2) }} ج.م</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">رصيد الجاري (دفتر)</div>
                            <div class="fw-semibold font-monospace {{ $ledgerBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $ledgerBalance, 2) }} ج.م</div>
                            <div class="small text-muted mt-1">دائن − مدين. موجب = مستحق للمساهم على المشروع.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">تاريخ التسجيل</div>
                            <div>{{ $shareholder->created_at?->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">آخر تحديث</div>
                            <div>{{ $shareholder->updated_at?->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card app-surface h-100 border-secondary-subtle">
                <div class="card-header"><h6 class="mb-0">كيف يُحسب الجاري؟</h6></div>
                <div class="card-body small text-muted">
                    <p class="mb-2"><strong>دفتر الجاري:</strong> حركات يدوية فقط. الإيداع يزيد الرصيد ويربط بإيراد صندوق؛ السحب/التوزيع/التصفية تنقص الرصيد وتربط بمصروف صندوق.</p>
                    <p class="mb-0"><strong>المرجع المحسوب:</strong> منسب تشغيلي وتكاليف عقار — للعرض فقط ولا يدخل الدفتر تلقائياً.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- دفتر الجاري --}}
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fa-solid fa-book-open text-primary ms-1"></i> دفتر جاري المساهم</h5>
            <span class="badge {{ $ledgerBalance >= 0 ? 'text-bg-success' : 'text-bg-danger' }}">
                الرصيد: {{ number_format((float) $ledgerBalance, 2) }} ج.م
            </span>
        </div>
        <div class="card-body">
            @can('shareholders.manage')
                <form method="post" action="{{ route('shareholders.ledger.store', $shareholder) }}" class="border rounded-3 p-3 mb-4 bg-body-tertiary bg-opacity-50">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
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
                            <select name="direction" class="form-select @error('direction') is-invalid @enderror">
                                <option value="credit" @selected(old('direction', 'credit') === 'credit')>دائن (+)</option>
                                <option value="debit" @selected(old('direction') === 'debit')>مدين (−)</option>
                            </select>
                            @error('direction') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                            @error('entry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ملاحظات</label>
                            <input type="text" name="notes" value="{{ old('notes') }}"
                                   class="form-control @error('notes') is-invalid @enderror" placeholder="اختياري">
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-lg-auto">
                            <button type="submit" class="btn btn-primary">تسجيل الحركة</button>
                        </div>
                    </div>
                    <div class="form-text mt-2">الإيداع = إيراد صندوق · السحب / التوزيع / التصفية = مصروف صندوق · التسوية بدون صندوق.</div>
                </form>
            @endcan

            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>التاريخ</th>
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
                                    <span class="text-muted">({{ $entry->treasuryTransaction->type === 'revenue' ? 'إيراد' : 'مصروف' }})</span>
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
                            <td colspan="7" class="text-center text-muted py-4">لا توجد حركات جاري بعد.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- مرجع محسوب --}}
    <div class="card app-surface mb-4 border-0 shadow-sm">
        <div class="card-header bg-body-secondary border-0 py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-calculator text-secondary ms-1"></i> مرجع محسوب (ليس دفتر)</h5>
                    <p class="small text-body-secondary mb-0 mt-1">منسب تشغيلي وحصة تكاليف من العقارات — للعرض فقط ولا يدخل رصيد الجاري تلقائياً.</p>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="rounded-3 border p-3 h-100 bg-body-tertiary bg-opacity-50">
                        <div class="small text-muted mb-1">حصة التكاليف (محسوبة)</div>
                        <div class="fs-4 fw-bold font-monospace">{{ number_format((float) $attributedDevelopmentCostShare, 2) }}</div>
                        <div class="small text-muted mt-1">ج.م — من تكاليف العقار × نسبتك</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded-3 border p-3 h-100 bg-body-tertiary bg-opacity-50">
                        <div class="small text-muted mb-1">المنسب التشغيلي (محسوب)</div>
                        <div class="fs-4 fw-bold font-monospace">{{ number_format((float) $attributedOperatingTotal, 2) }}</div>
                        <div class="small text-muted mt-1">ج.م — تحصيلات + مقدمات معتمدة</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded-3 border p-3 h-100 {{ $shareholderCurrentAccountApprox >= 0 ? 'border-success bg-success bg-opacity-10' : 'border-warning bg-warning bg-opacity-10' }}">
                        <div class="small text-muted mb-1">جاري تقريبي (مرجع)</div>
                        <div class="fs-4 fw-bold font-monospace">{{ number_format((float) $shareholderCurrentAccountApprox, 2) }}</div>
                        <div class="small text-muted mt-1">ج.م — المنسب − حصة التكلفة</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4 border-0 shadow-sm">
        <div class="card-header bg-body-secondary border-0 py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-chart-pie text-warning ms-1"></i> المنسب التشغيلي (من التحصيلات والمبيعات)</h5>
                    <p class="small text-body-secondary mb-0 mt-1">محسوب تلقائياً من بيانات المشروع الحالي؛ يتحدّث مع كل تحصيل أو بيعة جديدة.</p>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-5">
                    <div class="rounded-4 p-4 h-100 border border-success border-opacity-50 bg-success bg-opacity-10">
                        <div class="text-body-secondary small mb-2">المنسب التشغيلي (تحصيلات + مقدمات)</div>
                        <div class="display-6 fw-bold font-monospace text-body">{{ number_format((float) $attributedOperatingTotal, 2) }}</div>
                        <div class="text-muted small mt-2">ج.م — حسب نسبك في كل عقار</div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-50 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                            <h6 class="small fw-semibold text-body-secondary mb-0">حصة من إجمالي سعر البيعات (كمبيالة)</h6>
                            <span class="font-monospace fw-semibold">{{ number_format((float) $attributedSaleVolumeShare, 2) }} ج.م</span>
                        </div>
                        <p class="small text-body-secondary mb-0 mt-2">مجموع <code>sale_price</code> للبيعات المعتمدة على كل عقار × نسبتك؛ للمرجعية وليست بديلاً عن التحصيل الفعلي.</p>
                    </div>
                    <ul class="small text-body-secondary mb-0 ps-3">
                        <li class="mb-2">تُحتسب فقط البيعات والتحصيلات <strong>المعتمدة</strong>.</li>
                        <li class="mb-2">وحدات «مشاع مع شريك»: نصف المبلغ فقط ضمن منسب المساهمين.</li>
                        <li class="mb-0">هذا القسم مرجع فقط — لا يُرحَّل تلقائياً إلى دفتر الجاري.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header">
            <h5 class="mb-0">العقارات والمنسب التفصيلي</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0 table-sm">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>العقار</th>
                        <th>المنطقة</th>
                        <th>النسبة</th>
                        <th class="text-end">تحصيلات (داخل منسب المساهمين)</th>
                        <th class="text-end">مقدمات (داخل المنسب)</th>
                        <th class="text-end">تكاليف العقار</th>
                        <th class="text-end">حصتك (تشغيلي)</th>
                        <th class="text-end">حصتك من التكلفة</th>
                        <th class="text-end">جاري جزئي (مرجع)</th>
                        <th class="text-end">كمبيالة</th>
                        <th>الحالة</th>
                        <th class="text-end">عرض</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($participations as $item)
                        @php($p = $item->property)
                        @php($fin = $finByPid->get($p->id))
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-medium">{{ $p->name }}</td>
                            <td>{{ $p->area?->name ?? ($p->location ?? '—') }}</td>
                            <td><span class="badge text-bg-primary">{{ number_format((float) $item->percentage, 2) }}%</span></td>
                            <td class="text-end font-monospace small">{{ $fin ? number_format($fin['revenues'], 2) : '—' }}</td>
                            <td class="text-end font-monospace small">{{ $fin ? number_format($fin['down_payments'], 2) : '—' }}</td>
                            <td class="text-end font-monospace small">{{ $fin ? number_format($fin['development_cost_total'], 2) : '—' }}</td>
                            <td class="text-end font-monospace fw-semibold">{{ $fin ? number_format($fin['attributed_operating'], 2) : '—' }}</td>
                            <td class="text-end font-monospace small">{{ $fin ? number_format($fin['attributed_development_cost'], 2) : '—' }}</td>
                            <td class="text-end font-monospace small @if($fin) {{ ($fin['current_account_slice'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }} @endif">{{ $fin ? number_format($fin['current_account_slice'], 2) : '—' }}</td>
                            <td class="text-end font-monospace small text-muted">{{ $fin ? number_format($fin['attributed_sale_volume'], 2) : '—' }}</td>
                            <td>{{ $p->status ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('properties.show', [$project, $p]) }}" class="btn btn-outline-info btn-sm">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">
                                لا يوجد هذا المساهم ضمن توزيع المساهمين على أي عقار حتى الآن. أضفه من شاشة تعديل العقار — بدون نسبة على عقار يبقى المنسب والجاري المحسوبان صفراً.
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
                const sync = () => {
                    dirWrap.style.display = typeEl.value === 'adjustment' ? '' : 'none';
                };
                typeEl.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endpush
@endsection
