@extends('layouts.site')

@section('content')
    <section class="site-page-hero">
        <div class="site-wrap">
            <h1>{{ $site->about_title }}</h1>
            <p>{{ $site->brand }} — {{ $site->about_intro }}</p>
        </div>
    </section>

    <section class="site-section">
        <div class="site-wrap site-trust">
            <div class="site-reveal site-reveal--right">
                <span class="site-section__eyebrow">Vision</span>
                <h2 style="margin:0 0 .75rem;font-size:clamp(1.5rem,3vw,2rem)">{{ $site->about_vision_title }}</h2>
                <p style="margin:0;color:var(--site-ink-soft);max-width:34rem">
                    {{ $site->about_vision }}
                </p>
            </div>
            <div class="site-stats" style="grid-template-columns:1fr 1fr">
                <div class="site-stat site-reveal site-reveal--scale">
                    <span class="site-stat__value" data-count="{{ (int) $stats['projects'] }}" data-suffix="+">0</span>
                    <div class="site-stat__label">مشروع نشط</div>
                </div>
                <div class="site-stat site-reveal site-reveal--scale">
                    <span class="site-stat__value" data-count="{{ (int) $stats['properties'] }}" data-suffix="+">0</span>
                    <div class="site-stat__label">عقار ضمن المشاريع</div>
                </div>
            </div>
        </div>
    </section>

    <section class="site-section site-section--muted">
        <div class="site-wrap">
            <div class="site-section__head site-reveal">
                <span class="site-section__eyebrow">Services</span>
                <h2>ما نقدمه</h2>
                <p>خدمات متكاملة تغطي دورة حياة المشروع العقاري.</p>
            </div>
            <ul class="site-trust__points" style="max-width:42rem">
                @foreach (($site->services ?? []) as $service)
                    <li class="site-reveal site-reveal--left">
                        <strong>{{ $service['title'] ?? '' }}</strong>
                        <span>{{ $service['text'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="site-reveal" style="margin-top:2rem">
                <a href="{{ route('site.contact') }}" class="site-btn site-btn--dark">تواصل مع الفريق</a>
            </div>
        </div>
    </section>
@endsection
