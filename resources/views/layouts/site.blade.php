<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($site->brand ?? config('site.brand')) }}</title>
    <meta name="description" content="{{ $metaDescription ?? ($site->tagline ?? config('site.tagline')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/site.css', 'resources/css/site-lighthouse-full.css', 'resources/js/site.js'])
    @endif
</head>
<body class="site-body site-body--splash">
@php
    $site = $site ?? \App\Support\WebsiteContent::all();
@endphp
<div class="site-splash" data-site-splash aria-hidden="false">
    <div class="site-splash__brand">
        <span class="site-splash__name">{{ $site->brand }}</span>
        <span class="site-splash__mark" aria-hidden="true">
            <i></i><i></i><i></i><i></i>
        </span>
    </div>
</div>
<div class="site-progress" data-site-progress aria-hidden="true"></div>

<div class="site-shell">
    <header class="site-header {{ request()->routeIs('site.home') ? 'is-over-hero' : '' }}" data-site-header>
        <div class="site-wrap site-header__inner">
            <a href="{{ route('site.home') }}" class="site-brand">
                <span class="site-brand__mark" aria-hidden="true"></span>
                <span class="site-brand__text">{{ $site->brand }}</span>
            </a>

            <button type="button" class="site-menu-btn" data-site-menu aria-expanded="false" aria-label="القائمة">
                <span></span><span></span><span></span>
            </button>

            <nav class="site-nav" data-site-nav>
                <div class="site-nav__links">
                    <a href="{{ route('site.home') }}" @class(['is-active' => request()->routeIs('site.home')])>الرئيسية</a>
                    <a href="{{ route('site.projects') }}" @class(['is-active' => request()->routeIs('site.projects*')])>المشاريع</a>
                    <a href="{{ route('site.about') }}" @class(['is-active' => request()->routeIs('site.about')])>من نحن</a>
                </div>
                <a href="{{ route('site.contact') }}" class="site-nav__cta">تواصل معنا</a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="site-wrap">
            <div class="site-footer__grid">
                <div class="site-reveal">
                    <div class="site-footer__brand">{{ $site->brand }}</div>
                    <p>{{ $site->tagline }}</p>
                </div>
                <div class="site-reveal">
                    <strong style="color:#fff;display:block;margin-bottom:.5rem">روابط</strong>
                    <div style="display:grid;gap:.35rem">
                        <a href="{{ route('site.projects') }}">المشاريع</a>
                        <a href="{{ route('site.about') }}">من نحن</a>
                        <a href="{{ route('site.contact') }}">تواصل</a>
                    </div>
                </div>
                <div class="site-reveal">
                    <strong style="color:#fff;display:block;margin-bottom:.5rem">تواصل</strong>
                    <div style="display:grid;gap:.35rem">
                        @if ($site->phone)
                            <a href="tel:{{ $site->phone }}">{{ $site->phone }}</a>
                        @endif
                        @if ($site->email)
                            <a href="mailto:{{ $site->email }}">{{ $site->email }}</a>
                        @endif
                        <span>{{ $site->address }}</span>
                    </div>
                </div>
            </div>
            <div class="site-footer__copy">
                <span>© {{ date('Y') }} {{ $site->brand }}. جميع الحقوق محفوظة.</span>
                <a href="{{ route('login') }}">دخول النظام</a>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
