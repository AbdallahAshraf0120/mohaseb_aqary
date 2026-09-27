@extends('layouts.site')

@section('content')
    @php
        $cover = $websiteProject->coverUrl();
        $gallery = $websiteProject->galleryUrls();
        $features = is_array($websiteProject->features) ? $websiteProject->features : [];
    @endphp

    <section class="site-page-hero {{ $cover ? 'site-page-hero--cover' : '' }}" @if($cover) style="--cover:url('{{ $cover }}')" @endif>
        <div class="site-wrap">
            <p style="margin:0 0 .5rem;color:var(--site-accent);font-weight:600;font-size:.9rem">
                <a href="{{ route('site.projects') }}">المشاريع</a>
            </p>
            <h1>{{ $websiteProject->displayTitle() }}</h1>
            <p>
                @if ($websiteProject->subtitle)
                    {{ $websiteProject->subtitle }}
                @elseif ($websiteProject->excerpt)
                    {{ $websiteProject->excerpt }}
                @else
                    تفاصيل المشروع والعقارات المتاحة.
                @endif
            </p>
            <div style="display:flex;flex-wrap:wrap;gap:.6rem;margin-top:1rem">
                @if ($websiteProject->location)
                    <span class="site-badge">{{ $websiteProject->location }}</span>
                @endif
                @if ($websiteProject->status_label)
                    <span class="site-badge">{{ $websiteProject->status_label }}</span>
                @endif
            </div>
        </div>
    </section>

    @if (count($gallery))
        <section class="site-section" style="padding-top:2rem;padding-bottom:0">
            <div class="site-wrap">
                <div class="site-section__head" style="margin-bottom:1.25rem">
                    <span class="site-section__eyebrow">Gallery</span>
                    <h2 style="margin:0">معرض الصور</h2>
                </div>
                <div class="site-gallery site-gallery--lg">
                    @foreach ($gallery as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="site-gallery__item">
                            <img src="{{ $url }}" alt="{{ $websiteProject->displayTitle() }}" loading="lazy">
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="site-section">
        <div class="site-wrap site-detail-grid">
            <div>
                @if ($websiteProject->description)
                    <div class="site-panel" style="margin-bottom:1rem">
                        <h2>عن المشروع</h2>
                        <div style="white-space:pre-line;color:var(--site-ink-soft)">{{ $websiteProject->description }}</div>
                    </div>
                @endif

                <div class="site-panel">
                    <h2>العقارات</h2>
                    @if ($project->properties->isEmpty())
                        <div class="site-empty">لا توجد عقارات معروضة لهذا المشروع بعد.</div>
                    @else
                        <div class="site-units">
                            @foreach ($project->properties as $property)
                                <div class="site-unit">
                                    <div>
                                        <strong>{{ $property->name }}</strong>
                                        <span>
                                            @if ($property->property_type)
                                                {{ $property->property_type }}
                                            @endif
                                            @if ($property->location)
                                                — {{ $property->location }}
                                            @endif
                                            @if ($property->floors_count)
                                                — {{ $property->floors_count }} أدوار
                                            @endif
                                            @if ($property->total_apartments)
                                                — {{ $property->total_apartments }} وحدة
                                            @endif
                                        </span>
                                    </div>
                                    @if ($property->status)
                                        <span class="site-badge">{{ $property->status }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <aside>
                @if (count($features))
                    <div class="site-panel" style="margin-bottom:1rem">
                        <h2>المميزات</h2>
                        <ul style="margin:0;padding-inline-start:1.1rem;color:var(--site-ink-soft)">
                            @foreach ($features as $feature)
                                <li style="margin-bottom:.4rem">{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="site-panel" style="margin-bottom:1rem">
                    <h2>المناطق</h2>
                    @if ($project->areas->isEmpty())
                        <p style="margin:0;color:var(--site-ink-soft)">لا توجد مناطق مسجلة.</p>
                    @else
                        <ul style="margin:0;padding-inline-start:1.1rem;color:var(--site-ink-soft)">
                            @foreach ($project->areas as $area)
                                <li style="margin-bottom:.35rem">{{ $area->name }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="site-panel">
                    <h2>مهتم بهذا المشروع؟</h2>
                    <p style="margin:0 0 1rem;color:var(--site-ink-soft);font-size:.95rem">
                        أرسل استفسارك وسيتواصل معك فريق المبيعات.
                    </p>
                    <a href="{{ route('site.contact', ['project' => $project->id]) }}" class="site-btn site-btn--primary" style="width:100%">
                        {{ $websiteProject->cta_label ?: 'تواصل بخصوص المشروع' }}
                    </a>
                </div>
            </aside>
        </div>
    </section>
@endsection
