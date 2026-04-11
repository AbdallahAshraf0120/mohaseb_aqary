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

                        @if ($navCurrentProject ?? null)

                            <a href="{{ route('properties.index') }}" class="nav-link">الرئيسية</a>

                        @else

                            <a href="{{ route('projects.index') }}" class="nav-link">المشاريع</a>

                        @endif

                    </li>

                </ul>

                <ul class="navbar-nav ms-auto align-items-center gap-2">

                    <li class="nav-item py-1 d-none d-md-block">

                        <a href="{{ route('projects.index') }}" class="nav-link small">إدارة المشاريع</a>

                    </li>

                    <li class="nav-item py-1">

                        <form method="post" action="{{ route('logout') }}" class="mb-0">

                            @csrf

                            <button type="submit" class="btn btn-sm btn-outline-secondary">

                                <i class="fa-solid fa-right-from-bracket ms-1"></i>تسجيل خروج

                            </button>

                        </form>

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

                        @php

                            $projectsTreeOpen = request()->routeIs('projects.*') || ($navCurrentProject ?? null);

                        @endphp

                        <li class="nav-item has-treeview {{ $projectsTreeOpen ? 'menu-open' : '' }}">

                            <a href="#" class="nav-link">

                                <i class="nav-icon fa-solid fa-diagram-project"></i>

                                <p>

                                    المشاريع

                                    <i class="nav-arrow fa-solid fa-angle-left"></i>

                                </p>

                            </a>

                            <ul class="nav nav-treeview">

                                @forelse (($navProjects ?? collect()) as $np)

                                    <li class="nav-item">

                                        <a href="{{ route('properties.index', $np) }}"

                                           class="nav-link {{ ($navCurrentProject ?? null) && (int) $navCurrentProject->id === (int) $np->id ? 'active' : '' }}">

                                            <i class="nav-icon fa-solid fa-circle small"></i>

                                            <p>{{ $np->name }}</p>

                                        </a>

                                    </li>

                                @empty

                                    <li class="nav-item">

                                        <span class="nav-link text-secondary small">لا توجد مشاريع معروضة</span>

                                    </li>

                                @endforelse

                                <li class="nav-item">

                                    <a href="{{ route('projects.index') }}"

                                       class="nav-link {{ request()->routeIs('projects.index') ? 'active' : '' }}">

                                        <i class="nav-icon fa-solid fa-gear"></i>

                                        <p>المسودة وإضافة مشروع</p>

                                    </a>

                                </li>

                            </ul>

                        </li>



                        @php

                            $showWorkspaceModules = ($navCurrentProject ?? null)

                                || (request()->route() && request()->route()->hasParameter('project'));

                        @endphp

                        @if ($showWorkspaceModules)

                            @foreach (($modules ?? []) as $moduleKey => $menuItem)

                                @continue($moduleKey === 'projects')

                                @php

                                    $menuHref = route($menuItem['route']);

                                    $routePrefix = \Illuminate\Support\Str::before($menuItem['route'], '.');

                                    $menuActive = request()->routeIs($routePrefix . '.*');

                                @endphp

                                <li class="nav-item">

                                    <a href="{{ $menuHref }}"

                                       class="nav-link {{ $menuActive ? 'active' : '' }}">

                                        <i class="nav-icon fa-solid {{ $menuItem['icon'] }}"></i>

                                        <p>{{ $menuItem['label'] }}</p>

                                    </a>

                                </li>

                            @endforeach

                        @endif

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

