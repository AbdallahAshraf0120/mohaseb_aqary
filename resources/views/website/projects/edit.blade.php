@extends('layouts.admin')

@section('content')
    @php
        $featuresText = old('features_text');
        if ($featuresText === null) {
            $featuresText = implode("\n", is_array($website->features) ? $website->features : []);
        }
        $gallery = is_array($website->gallery) ? $website->gallery : [];
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

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('website.projects.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-right ms-1"></i> مشاريع الموقع
        </a>
        @if ($website->exists && $website->is_published)
            <a href="{{ route('site.projects.show', $project) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">معاينة الصفحة</a>
        @endif
    </div>

    <div class="card app-surface border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="mb-4">
                <h4 class="mb-1 fw-semibold">{{ $project->name }}</h4>
                <p class="text-body-secondary small mb-0">إعدادات الظهور والمحتوى التسويقي والصور على الموقع العام.</p>
            </div>

            <form method="post" action="{{ route('website.projects.update', $project) }}" enctype="multipart/form-data" class="vstack gap-4">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_published" id="is_published" value="1" @checked(old('is_published', $website->is_published))>
                            <label class="form-check-label" for="is_published">نشر على الموقع</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_on_home" id="show_on_home" value="1" @checked(old('show_on_home', $website->show_on_home ?? true))>
                            <label class="form-check-label" for="show_on_home">إظهار في الرئيسية</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="sort_order">الترتيب (الأصغر أولاً)</label>
                        <input type="number" min="0" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', $website->sort_order ?? 0) }}">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="title">العنوان المعروض</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $website->title ?: $project->name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="subtitle">عنوان فرعي</label>
                        <input type="text" name="subtitle" id="subtitle" class="form-control" value="{{ old('subtitle', $website->subtitle) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="location">الموقع / المنطقة</label>
                        <input type="text" name="location" id="location" class="form-control" value="{{ old('location', $website->location) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="status_label">حالة المشروع</label>
                        <input type="text" name="status_label" id="status_label" class="form-control" value="{{ old('status_label', $website->status_label) }}" placeholder="جاري / مكتمل / قريباً">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="cta_label">نص زر التواصل</label>
                        <input type="text" name="cta_label" id="cta_label" class="form-control" value="{{ old('cta_label', $website->cta_label) }}" placeholder="تواصل بخصوص المشروع">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="excerpt">نبذة قصيرة (للبطاقات)</label>
                        <textarea name="excerpt" id="excerpt" class="form-control" rows="2" maxlength="500">{{ old('excerpt', $website->excerpt) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">الوصف التفصيلي</label>
                        <textarea name="description" id="description" class="form-control" rows="6">{{ old('description', $website->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="features_text">المميزات (سطر لكل ميزة)</label>
                        <textarea name="features_text" id="features_text" class="form-control" rows="4">{{ $featuresText }}</textarea>
                    </div>
                </div>

                <div class="border rounded-3 p-3">
                    <h6 class="fw-semibold mb-3">صورة الغلاف</h6>
                    @if ($website->coverUrl())
                        <div class="mb-3 d-flex align-items-start gap-3 flex-wrap">
                            <img src="{{ $website->coverUrl() }}" alt="" class="rounded border" style="max-width:240px;max-height:150px;object-fit:cover">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remove_cover" id="remove_cover" value="1">
                                <label class="form-check-label" for="remove_cover">حذف صورة الغلاف</label>
                            </div>
                        </div>
                    @endif
                    <input type="file" name="cover" id="cover" class="form-control" accept="image/*">
                    <div class="form-text">يفضّل صورة أفقية، حتى 5 ميجا.</div>
                </div>

                <div class="border rounded-3 p-3">
                    <h6 class="fw-semibold mb-3">معرض الصور</h6>
                    @if (count($gallery))
                        <div class="row g-3 mb-3">
                            @foreach ($gallery as $i => $path)
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 overflow-hidden">
                                        <img src="{{ '/storage/'.$path }}" alt="" class="w-100" style="height:120px;object-fit:cover">
                                        <div class="p-2">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" name="remove_gallery[]" id="rmg{{ $i }}" value="{{ $i }}">
                                                <label class="form-check-label small" for="rmg{{ $i }}">حذف</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <input type="file" name="gallery[]" id="gallery" class="form-control" accept="image/*" multiple>
                    <div class="form-text">يمكنك رفع عدة صور مرة واحدة.</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk ms-1"></i> حفظ
                    </button>
                    <a href="{{ route('website.projects.index') }}" class="btn btn-outline-secondary">رجوع</a>
                </div>
            </form>
        </div>
    </div>
@endsection
