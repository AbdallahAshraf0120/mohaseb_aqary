<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($site->brand ?? config('site.brand')) }}</title>
    <meta name="description" content="{{ $metaDescription ?? ($site->tagline ?? config('site.tagline')) }}">
    @php
        $metaTitle = $title ?? ($site->brand ?? config('site.brand'));
        $metaDesc = $metaDescription ?? ($site->tagline ?? config('site.tagline'));
    @endphp
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ar_EG">
    <meta property="og:site_name" content="{{ $site->brand ?? config('site.brand') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if (! empty($metaImage))
        <meta property="og:image" content="{{ $metaImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @php
        $siteManifestPath = public_path('build/manifest.json');
        $siteManifest = (is_file($siteManifestPath) ? json_decode((string) file_get_contents($siteManifestPath), true) : null) ?: [];
        $hasSiteVite = isset(
            $siteManifest['resources/css/site.css'],
            $siteManifest['resources/css/site-lighthouse-full.css'],
            $siteManifest['resources/js/site.js']
        );
    @endphp
    @if ($hasSiteVite || file_exists(public_path('hot')))
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
@if (! empty($site->whatsapp))
    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $site->whatsapp) }}" target="_blank" rel="noopener" aria-label="تواصل معنا على واتساب"
       style="position:fixed;bottom:20px;left:20px;z-index:60;width:56px;height:56px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(0,0,0,.25)">
        <svg width="30" height="30" viewBox="0 0 32 32" fill="#fff" aria-hidden="true"><path d="M16 3C9 3 3.3 8.6 3.3 15.6c0 2.5.7 4.9 2 6.9L3 29l6.7-2.2c1.9 1 4.1 1.6 6.3 1.6 7 0 12.7-5.6 12.7-12.6S23 3 16 3zm0 23.1c-2 0-3.9-.6-5.6-1.6l-.4-.2-4 1.3 1.3-3.9-.3-.4c-1.1-1.7-1.7-3.7-1.7-5.7C5.3 9.8 10.1 5.1 16 5.1s10.7 4.7 10.7 10.5S21.9 26.1 16 26.1zm5.9-7.9c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.2-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.3.3-.6.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.7s1.2 3.2 1.4 3.4c.2.2 2.4 3.6 5.8 5 .8.3 1.4.5 1.9.7.8.2 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4z"/></svg>
    </a>
@endif
</body>
</html>
