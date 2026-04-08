<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Mohaseb Aqary') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="fa-solid fa-bars"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="{{ url('/demo') }}" class="nav-link">الرئيسية</a>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ url('/') }}" class="brand-link">
                    <span class="brand-text fw-light">Mohaseb Aqary</span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                        <li class="nav-item">
                            <a href="{{ route('demo') }}" class="nav-link {{ request()->routeIs('home') || request()->routeIs('demo') ? 'active' : '' }}">
                                <i class="nav-icon fa-solid fa-chalkboard-user"></i>
                                <p>Demo العرض</p>
                            </a>
                        </li>
                        @foreach (($modules ?? []) as $moduleKey => $menuItem)
                            <li class="nav-item">
                                @php
                                    $menuHref = $menuItem['route'] === 'modules.show'
                                        ? route('modules.show', $moduleKey)
                                        : route($menuItem['route']);
                                @endphp
                                <a href="{{ $menuHref }}"
                                   class="nav-link {{ request()->is('modules/' . $moduleKey) || ($menuItem['route'] === 'properties.index' && request()->is('properties*')) || ($menuItem['route'] === 'shareholders.index' && request()->is('shareholders*')) || ($menuItem['route'] === 'sales.index' && request()->is('sales*')) || ($menuItem['route'] === 'clients.index' && request()->is('clients*')) || ($menuItem['route'] === 'contracts.index' && request()->is('contracts*')) ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid {{ $menuItem['icon'] }}"></i>
                                    <p>{{ $menuItem['label'] }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <h3 class="mb-0">{{ $pageTitle ?? 'Dashboard' }}</h3>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
</body>
</html>
