<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $layoutProject = request()->route()?->parameter('project');
        $layoutProject = $layoutProject instanceof \App\Models\Project ? $layoutProject : null;
        $routeProject = $layoutProject;
        $projectsTreeOpen = request()->routeIs('projects.*') || $routeProject;
    @endphp
    <title>@if ($layoutProject){{ $layoutProject->name }} — @endif{{ $title ?? config('app.name', 'Mohaseb Aqary') }}</title>
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
                                    @php
                                        $isThisProject = $routeProject && (int) $routeProject->id === (int) $np->id;
                                    @endphp
                                    <li class="nav-item has-treeview {{ $isThisProject ? 'menu-open' : '' }}">
                                        <a href="#" class="nav-link">
                                            <i class="nav-icon fa-solid fa-folder-tree"></i>
                                            <p>
                                                {{ $np->name }}
                                                <i class="nav-arrow fa-solid fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @foreach (($projectSidebarActions ?? []) as $action)
                                                @php
                                                    $subActive = $isThisProject && collect((array) $action['active'])
                                                        ->contains(fn (string $p) => request()->routeIs($p));
                                                    $canCreateFromMenu = !empty($action['create_route']);
                                                    $subCreateActive = $isThisProject && $canCreateFromMenu
                                                        && collect((array) ($action['create_active'] ?? []))
                                                            ->contains(fn (string $p) => request()->routeIs($p));
                                                @endphp
                                                <li class="nav-item">
                                                    <div class="d-flex align-items-center gap-1">
                                                        <a href="{{ route($action['route'], $np) }}"
                                                           class="nav-link flex-grow-1 {{ $subActive ? 'active' : '' }}">
                                                            <i class="nav-icon fa-solid {{ $action['icon'] }}"></i>
                                                            <p>{{ $action['label'] }}</p>
                                                        </a>
                                                        @if ($canCreateFromMenu)
                                                            <a href="{{ route($action['create_route'], $np) }}"
                                                               class="nav-link px-2 {{ $subCreateActive ? 'active' : '' }}"
                                                               title="إضافة">
                                                                <i class="nav-icon fa-solid fa-circle-plus"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @empty
                                    <li class="nav-item">
                                        <span class="nav-link text-secondary small">لا توجد مشاريع معروضة</span>
                                    </li>
                                @endforelse
                            </ul>
                        </li>
                        @php
                            $settingsMenuProject = $layoutProject ?? ($navCurrentProject ?? null) ?? (($navProjects ?? collect())->first());
                        @endphp
                        <li class="nav-item">
                            <a href="{{ route('projects.index') }}"
                               class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                                <i class="nav-icon fa-solid fa-folder-plus"></i>
                                <p>المسودة وإضافة مشروع</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            @if ($settingsMenuProject)
                                <a href="{{ route('settings.edit', $settingsMenuProject) }}"
                                   class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-gear"></i>
                                    <p>الإعدادات</p>
                                </a>
                            @else
                                <span class="nav-link text-secondary">
                                    <i class="nav-icon fa-solid fa-gear"></i>
                                    <p>الإعدادات</p>
                                </span>
                            @endif
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    @if ($layoutProject)
                        <div class="d-flex flex-wrap align-items-baseline gap-2 mb-2 pb-2 border-bottom border-secondary-subtle">
                            <span class="text-body-secondary small">المشروع</span>
                            <span class="fw-semibold fs-5">{{ $layoutProject->name }}</span>
                            @if ($layoutProject->code)
                                <span class="badge text-bg-light border text-body-secondary">{{ $layoutProject->code }}</span>
                            @endif
                        </div>
                    @endif
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
