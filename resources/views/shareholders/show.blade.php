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
    @endphp

    <x-partials.module-wireflow-header label="بروفايل المساهم" step="2" />
    <x-partials.module-kpis :items="[
        ['label' => 'عدد المشاريع', 'value' => $projectsCount],
        ['label' => 'متوسط النسبة داخل المشاريع', 'value' => $avgShareInProjects !== null ? number_format($avgShareInProjects, 2) . '%' : '—'],
        ['label' => 'رأس المال (الملف)', 'value' => number_format((float) $shareholder->total_investment, 2) . ' ج.م'],
        ['label' => 'الأرباح (الملف)', 'value' => number_format((float) $shareholder->profit_amount, 2) . ' ج.م'],
    ]" />

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
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
                            <div class="text-muted small mb-1">النسبة العامة المسجّلة في الملف</div>
                            <div class="fw-semibold">{{ number_format((float) $shareholder->share_percentage, 2) }}%</div>
                            <div class="small text-muted mt-1">تُستخدم كمرجع عام؛ النسب داخل كل مشروع تظهر في الجدول أدناه.</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">رأس المال</div>
                            <div class="fw-semibold">{{ number_format((float) $shareholder->total_investment, 2) }} ج.م</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small mb-1">الأرباح (حسب الملف)</div>
                            <div class="fw-semibold">{{ number_format((float) $shareholder->profit_amount, 2) }} ج.م</div>
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
            <div class="card h-100 border-secondary-subtle">
                <div class="card-header"><h6 class="mb-0">ملحوظة</h6></div>
                <div class="card-body small text-muted">
                    جدول «المشاريع» يعرض كل عقار يظهر فيه هذا المساهم ضمن <strong>توزيع المساهمين</strong> على العقار، مع النسبة المحفوظة لكل مشروع على حدة.
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">المشاريع / العقارات التي يشارك فيها</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>المشروع (العقار)</th>
                        <th>المنطقة</th>
                        <th>نوع العقار</th>
                        <th>النسبة في المشروع</th>
                        <th>الأدوار / الشقق</th>
                        <th>الحالة</th>
                        <th class="text-end">عرض العقار</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($participations as $item)
                        @php
                            /** @var \App\Models\Property $p */
                            $p = $item->property;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-medium">{{ $p->name }}</td>
                            <td>{{ $p->area?->name ?? ($p->location ?? '—') }}</td>
                            <td>{{ $p->property_type ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-primary">{{ number_format((float) $item->percentage, 2) }}%</span>
                            </td>
                            <td class="small">
                                @if($p->floors_count || $p->total_apartments)
                                    {{ $p->floors_count ?? '—' }} دور
                                    @if($p->total_apartments)
                                        · {{ $p->total_apartments }} وحدة
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $p->status ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('properties.show', $p) }}" class="btn btn-outline-info btn-sm">العقار</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                لا يوجد هذا المساهم ضمن توزيع المساهمين على أي عقار حتى الآن. يمكن ربطه من شاشة إضافة/تعديل العقار.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
