@extends('layouts.admin')

@section('content')
    @php
        $val = function (string $key, mixed $fallback = '') use ($setting, $defaults) {
            $old = old($key);
            if ($old !== null) {
                return $old;
            }
            $current = $setting->{$key} ?? null;
            if ($current !== null && $current !== '') {
                return $current;
            }

            return $defaults[$key] ?? $fallback;
        };

        $marqueeText = old('marquee_text');
        if ($marqueeText === null) {
            $items = $setting->marquee_items ?: ($defaults['marquee_items'] ?? []);
            $marqueeText = is_array($items) ? implode("\n", $items) : '';
        }

        $trustPoints = old('trust_points', $setting->trust_points ?: ($defaults['trust_points'] ?? []));
        $services = old('services', $setting->services ?: ($defaults['services'] ?? []));
        while (count($trustPoints) < 3) {
            $trustPoints[] = ['title' => '', 'text' => ''];
        }
        while (count($services) < 3) {
            $services[] = ['title' => '', 'text' => ''];
        }
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
        <a href="{{ route('website.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-right ms-1"></i> لوحة الموقع
        </a>
        @foreach ($sections as $key => $item)
            @php
                $href = $item['route'] ?? route('website.edit', $key);
                $active = isset($item['route'])
                    ? request()->routeIs('website.projects.*')
                    : (($section ?? null) === $key);
            @endphp
            <a href="{{ $href }}" class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline-primary' }}">
                {{ $item['title'] }}
            </a>
        @endforeach
    </div>

    <div class="card app-surface border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="mb-4">
                <h4 class="mb-1 fw-semibold">{{ $meta['title'] }}</h4>
                <p class="text-body-secondary small mb-0">{{ $meta['desc'] }}</p>
            </div>

            <form method="post" action="{{ route('website.update', $section) }}" class="vstack gap-3">
                @csrf
                @method('PUT')

                @if ($section === 'general')
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="rounded-3 border border-primary border-opacity-25 bg-primary bg-opacity-10 p-3">
                                <label class="form-label fw-semibold" for="brand">اسم الشركة / البراند</label>
                                <p class="small text-body-secondary mb-2">هذا الاسم يظهر في الهيرو، القائمة العلوية، شاشة الافتتاح، والفوتر.</p>
                                <input type="text" name="brand" id="brand" class="form-control form-control-lg" value="{{ $val('brand') }}" required maxlength="120" autocomplete="organization">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="tagline">الشعار النصي</label>
                            <input type="text" name="tagline" id="tagline" class="form-control" value="{{ $val('tagline') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="phone">الهاتف</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ $val('phone') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="whatsapp">واتساب</label>
                            <input type="text" name="whatsapp" id="whatsapp" class="form-control" value="{{ $val('whatsapp') }}" placeholder="2010xxxxxxxx">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="email">البريد</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ $val('email') }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="address">العنوان</label>
                            <input type="text" name="address" id="address" class="form-control" value="{{ $val('address') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_published" id="is_published" value="1" @checked(old('is_published', $setting->is_published ?? true))>
                                <label class="form-check-label" for="is_published">نشر الموقع للزوار</label>
                            </div>
                        </div>
                    </div>
                @elseif ($section === 'home')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="hero_eyebrow">سطر فوق العنوان</label>
                            <input type="text" name="hero_eyebrow" id="hero_eyebrow" class="form-control" value="{{ $val('hero_eyebrow') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="hero_lead">نص الـ Hero</label>
                            <textarea name="hero_lead" id="hero_lead" class="form-control" rows="3">{{ $val('hero_lead') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="hero_cta_primary">زر أساسي</label>
                            <input type="text" name="hero_cta_primary" id="hero_cta_primary" class="form-control" value="{{ $val('hero_cta_primary') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="hero_cta_secondary">زر ثانوي</label>
                            <input type="text" name="hero_cta_secondary" id="hero_cta_secondary" class="form-control" value="{{ $val('hero_cta_secondary') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="marquee_text">عناصر الشريط المتحرك (سطر لكل عنصر)</label>
                            <textarea name="marquee_text" id="marquee_text" class="form-control" rows="5">{{ $marqueeText }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="home_projects_title">عنوان قسم المشاريع</label>
                            <input type="text" name="home_projects_title" id="home_projects_title" class="form-control" value="{{ $val('home_projects_title') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="home_projects_limit">عدد المشاريع</label>
                            <input type="number" min="1" max="24" name="home_projects_limit" id="home_projects_limit" class="form-control" value="{{ $val('home_projects_limit', 6) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="home_projects_subtitle">وصف قسم المشاريع</label>
                            <textarea name="home_projects_subtitle" id="home_projects_subtitle" class="form-control" rows="2">{{ $val('home_projects_subtitle') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cta_title">عنوان دعوة التواصل</label>
                            <input type="text" name="cta_title" id="cta_title" class="form-control" value="{{ $val('cta_title') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cta_button">نص زر الدعوة</label>
                            <input type="text" name="cta_button" id="cta_button" class="form-control" value="{{ $val('cta_button') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cta_text">نص دعوة التواصل</label>
                            <textarea name="cta_text" id="cta_text" class="form-control" rows="2">{{ $val('cta_text') }}</textarea>
                        </div>
                    </div>
                @elseif ($section === 'about')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="about_title">عنوان الصفحة</label>
                            <input type="text" name="about_title" id="about_title" class="form-control" value="{{ $val('about_title') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="about_vision_title">عنوان الرؤية</label>
                            <input type="text" name="about_vision_title" id="about_vision_title" class="form-control" value="{{ $val('about_vision_title') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="about_intro">مقدمة من نحن</label>
                            <textarea name="about_intro" id="about_intro" class="form-control" rows="3">{{ $val('about_intro') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="about_vision">نص الرؤية</label>
                            <textarea name="about_vision" id="about_vision" class="form-control" rows="4">{{ $val('about_vision') }}</textarea>
                        </div>
                    </div>
                @elseif ($section === 'trust')
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label class="form-label" for="trust_title">عنوان قسم الثقة</label>
                            <input type="text" name="trust_title" id="trust_title" class="form-control" value="{{ $val('trust_title') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="trust_subtitle">وصف القسم</label>
                            <input type="text" name="trust_subtitle" id="trust_subtitle" class="form-control" value="{{ $val('trust_subtitle') }}">
                        </div>
                    </div>
                    <h6 class="fw-semibold mt-2">نقاط «لماذا نحن»</h6>
                    @foreach ($trustPoints as $i => $point)
                        <div class="row g-2 align-items-start border rounded-3 p-3 mb-2">
                            <div class="col-md-4">
                                <label class="form-label">عنوان {{ $i + 1 }}</label>
                                <input type="text" name="trust_points[{{ $i }}][title]" class="form-control" value="{{ $point['title'] ?? '' }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">النص</label>
                                <input type="text" name="trust_points[{{ $i }}][text]" class="form-control" value="{{ $point['text'] ?? '' }}">
                            </div>
                        </div>
                    @endforeach
                    <h6 class="fw-semibold mt-3">الخدمات</h6>
                    @foreach ($services as $i => $service)
                        <div class="row g-2 align-items-start border rounded-3 p-3 mb-2">
                            <div class="col-md-4">
                                <label class="form-label">خدمة {{ $i + 1 }}</label>
                                <input type="text" name="services[{{ $i }}][title]" class="form-control" value="{{ $service['title'] ?? '' }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">الوصف</label>
                                <input type="text" name="services[{{ $i }}][text]" class="form-control" value="{{ $service['text'] ?? '' }}">
                            </div>
                        </div>
                    @endforeach
                @elseif ($section === 'contact')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="contact_title">عنوان الصفحة</label>
                            <input type="text" name="contact_title" id="contact_title" class="form-control" value="{{ $val('contact_title') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="contact_success">رسالة النجاح</label>
                            <input type="text" name="contact_success" id="contact_success" class="form-control" value="{{ $val('contact_success') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="contact_intro">مقدمة الصفحة</label>
                            <textarea name="contact_intro" id="contact_intro" class="form-control" rows="3">{{ $val('contact_intro') }}</textarea>
                        </div>
                    </div>
                @endif

                <div class="d-flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk ms-1"></i> حفظ
                    </button>
                    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">معاينة الموقع</a>
                </div>
            </form>
        </div>
    </div>
@endsection
