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
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('ma_theme');
                if (!theme) {
                    theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', theme);
            } catch (e) {}
        })();
    </script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body border-bottom border-secondary-subtle">
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
                    <li class="nav-item py-1">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                                id="themeToggle"
                                title="تبديل الوضع الليلي">
                            <i class="fa-solid fa-moon"></i>
                            <span class="d-none d-lg-inline">المظهر</span>
                        </button>
                    </li>

                    @php
                        $hasQuickLinks = auth()->user()?->can('projects.view')
                            || auth()->user()?->can('users.view')
                            || auth()->user()?->can('activity_log.view');
                    @endphp
                    @if ($hasQuickLinks)
                        <li class="nav-item dropdown py-1">
                            <a class="nav-link d-inline-flex align-items-center gap-2"
                               href="#"
                               role="button"
                               data-bs-toggle="dropdown"
                               aria-expanded="false">
                                <i class="fa-solid fa-grid-2"></i>
                                <span class="small d-none d-lg-inline">اختصارات</span>
                                <i class="fa-solid fa-angle-down small opacity-75"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @can('projects.view')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('projects.index') }}">
                                            <i class="fa-solid fa-diagram-project ms-2"></i> إدارة المشاريع
                                        </a>
                                    </li>
                                @endcan
                                @can('users.view')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('users.index') }}">
                                            <i class="fa-solid fa-users-gear ms-2"></i> المستخدمون
                                        </a>
                                    </li>
                                @endcan
                                @can('activity_log.view')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('activity-log.index') }}">
                                            <i class="fa-solid fa-clipboard-list ms-2"></i> سجل النشاط
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif
                    @php
                        $navUser = auth()->user();
                        $navUserName = (string) ($navUser?->name ?? '');
                        $navInitials = trim(mb_substr($navUserName, 0, 1));
                        if ($navInitials === '') {
                            $navInitials = 'U';
                        }
                    @endphp
                    <li class="nav-item dropdown py-1">
                        <a class="nav-link d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-body-secondary text-body"
                                  style="width: 32px; height: 32px; font-weight: 700;">
                                {{ $navInitials }}
                            </span>
                            <span class="small d-none d-lg-inline">{{ $navUserName ?: 'المستخدم' }}</span>
                            <i class="fa-solid fa-angle-down small opacity-75"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">{{ $navUserName ?: 'المستخدم' }}</h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="{{ route('logout') }}" class="mb-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fa-solid fa-right-from-bracket ms-2"></i> تسجيل خروج
                                    </button>
                                </form>
                            </li>
                        </ul>
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
                        @can('projects.manage')
                            <li class="nav-item">
                                <a href="{{ route('projects.index') }}"
                                   class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-folder-plus"></i>
                                    <p>المسودة وإضافة مشروع</p>
                                </a>
                            </li>
                        @endcan
                        @can('settings.manage')
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
                        @endcan
                        @can('crm.view')
                            <li class="nav-item">
                                <a href="{{ route('crm-leads.index') }}"
                                   class="nav-link {{ request()->routeIs('crm-leads.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-address-book"></i>
                                    <p>CRM - متابعة العملاء</p>
                                </a>
                            </li>
                        @endcan
                        @can('land-trading.view')
                            <li class="nav-item">
                                <a href="{{ route('land-trading.index') }}"
                                   class="nav-link {{ request()->routeIs('land-trading.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-map"></i>
                                    <p>أراضي البيع والشراء</p>
                                </a>
                            </li>
                        @endcan
                        @can('site-sketch.view')
                            <li class="nav-item">
                                <a href="{{ route('site-sketch.index') }}"
                                   class="nav-link {{ request()->routeIs('site-sketch.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-table-cells"></i>
                                    <p>مخطط الموقع</p>
                                </a>
                            </li>
                        @endcan
                        @can('tasks.view')
                            @can('tasks.manage')
                                <li class="nav-item">
                                    <a href="{{ route('tasks.index') }}"
                                       class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                                        <i class="nav-icon fa-solid fa-list-check"></i>
                                        <p>المهام</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('tasks.mine') }}"
                                       class="nav-link {{ request()->routeIs('tasks.mine') ? 'active' : '' }}">
                                        <i class="nav-icon fa-solid fa-user-check"></i>
                                        <p>مهامي</p>
                                    </a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a href="{{ route('tasks.mine') }}"
                                       class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                                        <i class="nav-icon fa-solid fa-list-check"></i>
                                        <p>مهامي</p>
                                    </a>
                                </li>
                            @endcan
                        @endcan
                        @can('cashbox.view')
                            <li class="nav-item">
                                <a href="{{ route('land-cashbox.index') }}"
                                   class="nav-link {{ request()->routeIs('land-cashbox.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-map-location-dot"></i>
                                    <p>صندوق الأراضي</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('global-cashbox.index') }}"
                                   class="nav-link {{ request()->routeIs('global-cashbox.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-vault"></i>
                                    <p>الصندوق الشامل</p>
                                </a>
                            </li>
                        @endcan
                        @can('shareholders.view')
                            <li class="nav-item">
                                <a href="{{ route('shareholders.index') }}"
                                   class="nav-link {{ request()->routeIs('shareholders.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-people-group"></i>
                                    <p>إدارة المساهمين</p>
                                </a>
                            </li>
                        @endcan
                        @can('users.view')
                            <li class="nav-item">
                                <a href="{{ route('users.index') }}"
                                   class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-users-gear"></i>
                                    <p>المستخدمون والأدوار</p>
                                </a>
                            </li>
                        @endcan
                        @can('activity_log.view')
                            <li class="nav-item">
                                <a href="{{ route('activity-log.index') }}"
                                   class="nav-link {{ request()->routeIs('activity-log.*') ? 'active' : '' }}">
                                    <i class="nav-icon fa-solid fa-clipboard-list"></i>
                                    <p>سجل النشاط</p>
                                </a>
                            </li>
                        @endcan
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
    <script>
        (function () {
            // Fallback: ensure theme toggle works even if bundled JS isn't loaded on server.
            try {
                if (window.__maThemeToggleBound) return;
                var btn = document.getElementById('themeToggle');
                if (!btn) return;

                function getTheme() {
                    try {
                        var t = localStorage.getItem('ma_theme');
                        if (t === 'dark' || t === 'light') return t;
                    } catch (e) {}
                    return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }

                function applyTheme(theme) {
                    document.documentElement.setAttribute('data-bs-theme', theme);
                    try { localStorage.setItem('ma_theme', theme); } catch (e) {}
                }

                function syncIcon(theme) {
                    var icon = btn.querySelector('i');
                    if (!icon) return;
                    icon.classList.remove('fa-moon', 'fa-sun');
                    icon.classList.add(theme === 'dark' ? 'fa-sun' : 'fa-moon');
                    btn.title = theme === 'dark' ? 'الوضع النهاري' : 'الوضع الليلي';
                }

                var initial = getTheme();
                applyTheme(initial);
                syncIcon(initial);

                btn.addEventListener('click', function () {
                    var next = (getTheme() === 'dark') ? 'light' : 'dark';
                    applyTheme(next);
                    syncIcon(next);
                });

                window.__maThemeToggleBound = true;
            } catch (e) {}
        })();
    </script>
    @stack('scripts')
</body>
</html>
