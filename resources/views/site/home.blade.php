@extends('layouts.site')

@section('content')
    <section class="site-hero site-hero--fx site-hero--home" data-site-hero data-fx-stage>
        <div class="site-hero__fx" aria-hidden="true">
            <div class="fx-space"></div>
            <div class="site-hero__veil"></div>
        </div>

        <div
            class="site-3d-stage site-3d-stage--hero"
            data-building-3d
            data-variant="lighthouse"
            data-hero-3d
            role="img"
            aria-label="برج المنارة ثلاثي الأبعاد — اسحب للتدوير"
        ></div>

        <div class="site-wrap site-hero__panel">
            <div class="site-hero__content site-hero__content--fx">
                <p class="site-hero__kicker">{{ $site->hero_eyebrow }}</p>
                <h1 class="site-hero__brand">{{ $site->brand }}</h1>
                <p class="site-hero__title">{{ $site->tagline }}</p>
                <p class="site-hero__lead">{{ $site->hero_lead }}</p>
                <div class="site-hero__actions">
                    <a href="{{ route('site.projects') }}" class="site-btn site-btn--primary site-btn--hero">{{ $site->hero_cta_primary }}</a>
                    <a href="{{ route('site.contact') }}" class="site-btn site-btn--ghost site-btn--hero">{{ $site->hero_cta_secondary }}</a>
                </div>
            </div>
        </div>

        <div class="site-hero__scroll" aria-hidden="true">
            <span>اسحب للأسفل</span>
            <span class="site-hero__scroll-line"></span>
        </div>
    </section>

    @php $marquee = is_array($site->marquee_items ?? null) ? $site->marquee_items : []; @endphp
    @if (count($marquee))
        <div class="site-marquee" aria-hidden="true">
            <div class="site-marquee__track">
                @foreach ([1, 2] as $loopDup)
                    @foreach ($marquee as $item)
                        <span>@if ($loop->first)<b>{{ $site->brand }}</b> — @endif{{ $item }}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    <section class="site-section site-section--has-3d">
        <div class="site-3d-stage site-3d-stage--bg site-3d-stage--bg-start" data-building-3d data-variant="complex" data-bg-3d aria-hidden="true"></div>
        <div class="site-wrap">
            <div class="site-stats">
                <div class="site-stat site-reveal site-reveal--scale">
                    <span class="site-stat__value" data-count="{{ max($projects->count(), 0) }}" data-suffix="+">0</span>
                    <div class="site-stat__label">مشاريع معروضة</div>
                </div>
                <div class="site-stat site-reveal site-reveal--scale">
                    <span class="site-stat__value" data-count="{{ (int) $projects->sum(fn ($p) => (int) ($p->project?->properties_count ?? 0)) }}" data-suffix="+">0</span>
                    <div class="site-stat__label">عقارات</div>
                </div>
                <div class="site-stat site-reveal site-reveal--scale">
                    <span class="site-stat__value" data-count="100" data-suffix="%">0</span>
                    <div class="site-stat__label">شفافية تعاقدية</div>
                </div>
                <div class="site-stat site-reveal site-reveal--scale">
                    <span class="site-stat__value" data-count="24" data-suffix="/7">0</span>
                    <div class="site-stat__label">استجابة للعملاء</div>
                </div>
            </div>
        </div>
    </section>

    <section class="site-section site-section--has-3d" style="padding-top:0">
        <div class="site-3d-stage site-3d-stage--bg" data-building-3d data-variant="twin" data-bg-3d aria-hidden="true"></div>
        <div class="site-wrap">
            <div class="site-section__head site-reveal">
                <span class="site-section__eyebrow">Portfolio</span>
                <h2>{{ $site->home_projects_title }}</h2>
                <p>{{ $site->home_projects_subtitle }}</p>
            </div>

            @if ($projects->isEmpty())
                <div class="site-empty site-reveal">سيتم عرض المشاريع هنا قريباً.</div>
            @else
                <div class="site-projects">
                    @foreach ($projects as $item)
                        @include('site.partials.project-card', ['item' => $item])
                    @endforeach
                </div>
            @endif

            <div class="site-reveal" style="margin-top:1.9rem">
                <a href="{{ route('site.projects') }}" class="site-btn site-btn--outline">كل المشاريع</a>
            </div>
        </div>
    </section>

    <section class="site-section site-section--muted site-section--has-3d">
        <div class="site-3d-stage site-3d-stage--bg site-3d-stage--bg-start" data-building-3d data-variant="villa" data-bg-3d aria-hidden="true"></div>
        <div class="site-wrap site-trust">
            <div class="site-reveal site-reveal--right">
                <div class="site-section__head" style="margin-bottom:0">
                    <span class="site-section__eyebrow">Why us</span>
                    <h2>{{ $site->trust_title }}</h2>
                    <p>{{ $site->trust_subtitle }}</p>
                </div>
            </div>
            <ul class="site-trust__points">
                @foreach (($site->trust_points ?? []) as $point)
                    <li class="site-reveal site-reveal--left">
                        <strong>{{ $point['title'] ?? '' }}</strong>
                        <span>{{ $point['text'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="site-section site-section--has-3d">
        <div class="site-3d-stage site-3d-stage--bg" data-building-3d data-variant="tower" data-bg-3d aria-hidden="true"></div>
        <div class="site-wrap">
            <div class="site-cta-band site-reveal site-reveal--scale">
                <h2 style="margin:0 0 .75rem;font-size:clamp(1.5rem,3vw,2.1rem)">{{ $site->cta_title }}</h2>
                <p style="margin:0 0 1.5rem;color:color-mix(in srgb, #fff 80%, transparent);max-width:32rem;margin-inline:auto">
                    {{ $site->cta_text }}
                </p>
                <a href="{{ route('site.contact') }}" class="site-btn site-btn--primary">{{ $site->cta_button }}</a>
            </div>
        </div>
    </section>
@endsection
