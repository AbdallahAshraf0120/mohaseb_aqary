@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    <div class="card app-surface border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width:2.5rem;height:2.5rem">
                            <i class="fa-solid fa-building"></i>
                        </span>
                        مشاريع الموقع
                    </h4>
                    <p class="text-body-secondary small mb-0">
                        اختر المشاريع الظاهرة للزوار، رتّبها، وأضف الصور والوصف التسويقي بدون التأثير على بيانات النظام الداخلية.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('website.index') }}" class="btn btn-outline-secondary btn-sm">لوحة الموقع</a>
                    <a href="{{ route('site.projects') }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">معاينة صفحة المشاريع</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card app-surface border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>المشروع</th>
                        <th>الصورة</th>
                        <th>الحالة على الموقع</th>
                        <th>الرئيسية</th>
                        <th>الترتيب</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        @php $wp = $project->websiteProject; @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $wp?->displayTitle() ?? $project->name }}</div>
                                <div class="small text-body-secondary">
                                    {{ $project->name }}
                                    @if ($project->code) · {{ $project->code }} @endif
                                    · {{ $project->properties_count }} عقار
                                </div>
                            </td>
                            <td style="width:88px">
                                @if ($wp?->coverUrl())
                                    <img src="{{ $wp->coverUrl() }}" alt="" class="rounded" style="width:64px;height:48px;object-fit:cover">
                                @else
                                    <span class="text-body-secondary small">بدون صورة</span>
                                @endif
                            </td>
                            <td>
                                @if ($wp?->is_published)
                                    <span class="badge text-bg-success">منشور</span>
                                @else
                                    <span class="badge text-bg-secondary">مخفي</span>
                                @endif
                            </td>
                            <td>
                                @if ($wp?->show_on_home)
                                    <span class="badge text-bg-info">نعم</span>
                                @else
                                    <span class="badge text-bg-light border">لا</span>
                                @endif
                            </td>
                            <td>{{ $wp->sort_order ?? 0 }}</td>
                            <td class="text-end text-nowrap">
                                <form method="post" action="{{ route('website.projects.toggle', $project) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $wp?->is_published ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                        {{ $wp?->is_published ? 'إخفاء' : 'نشر' }}
                                    </button>
                                </form>
                                <a href="{{ route('website.projects.edit', $project) }}" class="btn btn-sm btn-primary">
                                    تعديل العرض
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary py-4">لا توجد مشاريع نشطة حالياً.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
