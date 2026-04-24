@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @php
        $projectsCount = $participations->count();
        $avgShareInProjects = $projectsCount > 0
            ? round((float) $participations->avg('percentage'), 2)
            : null;
        $finByPid = collect($participationFinancialBreakdown)->keyBy('property_id');
    @endphp

    <x-partials.module-wireflow-header label="بروفايل المساهم" step="2" />
    <x-partials.module-kpis :items="[
        ['label' => 'عدد العقارات (ضمن التوزيع)', 'value' => $projectsCount],
        ['label' => 'متوسط النسبة في العقارات', 'value' => $avgShareInProjects !== null ? number_format($avgShareInProjects, 2) . '%' : '—'],
        ['label' => 'رأس المال المُدخل (الملف)', 'value' => number_format((float) $shareholder->total_investment, 2) . ' ج.م'],
        ['label' => 'حصة التكاليف (محسوبة من العقار)', 'value' => number_format((float) $attributedDevelopmentCostShare, 2) . ' ج.م'],
        ['label' => 'المنسب التشغيلي (محسوب)', 'value' => number_format((float) $attributedOperatingTotal, 2) . ' ج.م'],
        ['label' => 'جاري المساهم (تقريبي)', 'value' => number_format((float) $shareholderCurrentAccountApprox, 2) . ' ج.م'],
    ]" />

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card app-surface h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">البيانات الأساسية</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('shareholders.edit', [$project, $shareholder]) }}" class="btn btn-outline-warning btn-sm">تعديل المساهم</a>
                        <a href="{{ route('shareholders.index', $project) }}" class="btn btn-outline-secondary btn-sm">قائمة المساهمين</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">اسم المساهم</div>
                            <div class="fw-semibold">{{ $shareholder->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">النسبة العامة المسجّلة في الملف</div>
                            <div class="fw-semibold">{{ number_format((float) $shareholder->share_percentage, 2) }}%</div>
                            <div class="small text-muted mt-1">مرجع عام؛ التوزيع الفعلي على التحصيل والتكلفة يعتمد على النسبة داخل كل عقار.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">رأس المال / التمويل المُدخل (الملف)</div>
                            <div class="fw-semibold font-monospace">{{ number_format((float) $shareholder->total_investment, 2) }} ج.م</div>
                            <div class="small text-muted mt-1">يُحدَّد عند <strong>إضافة المساهم</strong> أو في <strong>أي تعديل</strong> لاحق — تاريخ التعديل هو «متى» يتغيّر هذا الرقم في النظام.</div>
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
                <div class="card-header"><h6 class="mb-0">رأس المال مقابل المحسوب</h6></div>
                <div class="card-body small text-muted">
                    <p class="mb-2"><strong>رأس المال في الملف:</strong> ما تدخله أنت إدارياً عند التسجيل أو التعديل (لا يُشتق آلياً من العقار).</p>
                    <p class="mb-2"><strong>حصة التكاليف:</strong> مجموع حقول التكلفة على كل عقار (أرض، ترخيص، مواد، أجور…) × نسبتك في توزيع المساهمين على ذلك العقار — يتحدّث مع تعديل تكاليف العقار أو النسب.</p>
                    <p class="mb-0"><strong>جاري المساهم (تقريبي):</strong> المنسب التشغيلي (تحصيلات + مقدمات) ناقص حصة التكاليف؛ لكل مساهم على حدة ضمن نفس المشروع.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4 border-0 shadow-sm">
        <div class="card-header bg-body-secondary border-0 py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-semibold"><i class="fa-solid fa-scale-balanced text-primary ms-1"></i> ملخص مالي لكل مساهم</h5>
                    <p class="small text-body-secondary mb-0 mt-1">مقارنة بين ما سجّلته كتمويل وبين ما يُوزَّع تلقائياً من العقارات والتحصيل.</p>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="rounded-3 border p-3 h-100 bg-body-tertiary bg-opacity-50">
                        <div class="small text-muted mb-1">رأس المال المُدخل (ملف)</div>
                        <div class="fs-4 fw-bold font-monospace">{{ number_format((float) $shareholder->total_investment, 2) }}</div>
                        <div class="small text-muted mt-1">ج.م — يُحدَّد عند التسجيل أو التعديل</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded-3 border p-3 h-100 bg-body-tertiary bg-opacity-50">
                        <div class="small text-muted mb-1">حصة التكاليف (محسوبة)</div>
                        <div class="fs-4 fw-bold font-monospace">{{ number_format((float) $attributedDevelopmentCostShare, 2) }}</div>
                        <div class="small text-muted mt-1">ج.م — من تكاليف العقار × نسبتك</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded-3 border p-3 h-100 {{ $shareholderCurrentAccountApprox >= 0 ? 'border-success bg-success bg-opacity-10' : 'border-warning bg-warning bg-opacity-10' }}">
                        <div class="small text-muted mb-1">جاري المساهم (تقريبي)</div>
                        <div class="fs-4 fw-bold font-monospace">{{ number_format((float) $shareholderCurrentAccountApprox, 2) }}</div>
                        <div class="small text-muted mt-1">ج.م — المنسب التشغيلي − حصة التكلفة</div>
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
                        <p class="small text-body-secondary mb-0 mt-2">مجموع <code>sale_price</code> للبيعات على كل عقار × نسبتك؛ للمرجعية وليست بديلاً عن التحصيل الفعلي.</p>
                    </div>
                    <ul class="small text-body-secondary mb-0 ps-3">
                        <li class="mb-2">التحصيلات تُؤخذ من سجلات التحصيل المرتبطة بالعقود على كل عقار.</li>
                        <li class="mb-2">المقدمات تُؤخذ من حقل مقدم البيعة لكل بيعة على العقار (وارد الصندوق عند تسجيل البيعة).</li>
                        <li class="mb-2">وحدات على أدوار «مشاع مع شريك» (كما هُو مُعرَّف في العقار): يُدخل في الجدول أعلاه <strong>نصف</strong> المبلغ فقط ضمن منسب المساهمين.</li>
                        <li class="mb-0">تجنّب تسجيل المقدم مرة أخرى كتحصيل على نفس العقد لتفادي ازدواجية في العرض المحاسبي خارج هذا الملخص.</li>
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
                        <th class="text-end">جاري جزئي</th>
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
@endsection
