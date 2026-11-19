<!DOCTYPE html>
<html lang="ar" dir="rtl">
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
    @php $uiV = @filemtime(public_path('css/ui.css')); @endphp
    {{-- ستايل عربي من اليمين للشمال (AdminLTE + Bootstrap RTL) — مش من Vite عشان Vite بيبني نسخة LTR --}}
    <link rel="stylesheet" href="{{ route('ui.asset', ['path' => 'vendor/adminlte/adminlte.rtl.min.css']) }}">
    <link rel="stylesheet" href="{{ route('ui.asset', ['path' => 'vendor/fontawesome/css/all.min.css']) }}">
    <link rel="stylesheet" href="{{ route('ui.asset', ['path' => 'vendor/tom-select/tom-select.bootstrap5.min.css']) }}">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/app.js'])
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('ui.asset', ['path' => 'css/ui.css', 'v' => $uiV]) }}">
</head>
<body class="layout-fixed sidebar-expand-lg ma-ui">
    @php
        $navUser = auth()->user();
        $navUserName = (string) ($navUser?->name ?? '');
        $navInitials = trim(mb_substr($navUserName, 0, 1)) ?: 'U';
        $roleLabels = ['admin' => 'مدير النظام', 'accountant' => 'محاسب', 'sales' => 'مبيعات', 'viewer' => 'مشاهد'];
        $navProjectsList = $navProjects ?? collect();
    @endphp
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand ma-topbar">
            <div class="container-fluid gap-2">
                <ul class="navbar-nav align-items-center gap-1">
                    <li class="nav-item">
                        <a class="nav-link ma-icon-btn" data-lte-toggle="sidebar" href="#" role="button" title="القائمة">
                            <i class="fa-solid fa-bars"></i>
                        </a>
                    </li>
                    @if ($navProjectsList->isNotEmpty())
                        <li class="nav-item dropdown">
                            <button class="btn ma-project-switch dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-folder-tree"></i>
                                <span class="text-truncate">{{ $navProject?->name ?? 'اختر مشروع' }}</span>
                            </button>
                            <div class="dropdown-menu ma-project-menu shadow">
                                <div class="px-3 pt-2 pb-1 small text-body-secondary">التبديل لمشروع</div>
                                @if ($navProjectsList->count() > 6)
                                    <div class="px-2 pb-2">
                                        <input type="search" class="form-control form-control-sm" placeholder="ابحث عن مشروع…" oninput="var q=this.value.trim();this.closest('.dropdown-menu').querySelectorAll('[data-project-name]').forEach(function(a){a.style.display=a.dataset.projectName.indexOf(q)>-1?'':'none'})">
                                    </div>
                                @endif
                                <div class="ma-project-list">
                                    @foreach ($navProjectsList as $np)
                                        <a class="dropdown-item d-flex align-items-center justify-content-between gap-2 {{ ($navProject?->id === $np->id) ? 'active' : '' }}"
                                           data-project-name="{{ $np->name }}"
                                           href="{{ route('dashboard', $np) }}">
                                            <span class="text-truncate">{{ $np->name }}</span>
                                            @if ($navProject?->id === $np->id)<i class="fa-solid fa-check small"></i>@endif
                                        </a>
                                    @endforeach
                                </div>
                                @can('projects.manage')
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="{{ route('projects.index') }}"><i class="fa-solid fa-gear ms-2"></i> إدارة المشاريع</a>
                                @endcan
                            </div>
                        </li>
                    @endif
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    @can('approvals.index')
                        <li class="nav-item">
                            <a href="{{ route('approvals.index') }}" class="nav-link ma-icon-btn position-relative" title="طلبات الاعتماد">
                                <i class="fa-regular fa-bell"></i>
                                @if (($pendingApprovalsCount ?? 0) > 0)
                                    <span class="ma-dot-badge">{{ $pendingApprovalsCount > 99 ? '99+' : $pendingApprovalsCount }}</span>
                                @endif
                            </a>
                        </li>
                    @endcan
                    <li class="nav-item">
                        <button type="button" class="nav-link ma-icon-btn border-0 bg-transparent" id="themeToggle" title="تبديل الوضع الليلي">
                            <i class="fa-solid fa-moon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link d-flex align-items-center gap-2 ma-user" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="ma-avatar">{{ $navInitials }}</span>
                            <span class="d-none d-lg-flex flex-column lh-sm text-start">
                                <span class="fw-semibold small">{{ $navUserName ?: 'المستخدم' }}</span>
                                <span class="text-body-secondary" style="font-size:.72rem">{{ $roleLabels[$navUser?->role] ?? $navUser?->role }}</span>
                            </span>
                            <i class="fa-solid fa-angle-down small opacity-50"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><h6 class="dropdown-header">{{ $navUserName ?: 'المستخدم' }}</h6></li>
                            @can('users.view')
                                <li><a class="dropdown-item" href="{{ route('users.index') }}"><i class="fa-solid fa-users-gear ms-2"></i> المستخدمين</a></li>
                            @endcan
                            @can('activity_log.view')
                                <li><a class="dropdown-item" href="{{ route('activity-log.index') }}"><i class="fa-solid fa-clipboard-list ms-2"></i> سجل النشاط</a></li>
                            @endcan
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="{{ route('logout') }}" class="mb-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-right-from-bracket ms-2"></i> تسجيل خروج</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar ma-sidebar">
            <div class="sidebar-brand ma-brand">
                <a href="{{ route('home') }}" class="brand-link d-flex align-items-center gap-2">
                    <span class="ma-logo"><i class="fa-solid fa-building-columns"></i></span>
                    <span class="brand-text">محاسب عقاري</span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                        @foreach (($navGroups ?? []) as $group)
                            @if ($group['title'] === null)
                                @foreach ($group['items'] as $item)
                                    <li class="nav-item">
                                        <a href="{{ $item['url'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}">
                                            <i class="nav-icon fa-solid {{ $item['icon'] }}"></i>
                                            <p class="flex-grow-1">{{ $item['label'] }}</p>
                                            @if ($item['badge'])<span class="badge rounded-pill text-bg-danger ms-auto">{{ $item['badge'] }}</span>@endif
                                        </a>
                                    </li>
                                @endforeach
                            @else
                                <li class="nav-item ma-group {{ $group['open'] ? 'menu-open' : '' }}" data-group="{{ $group['title'] }}">
                                    <a href="#" class="nav-link ma-group-link">
                                        <i class="nav-icon fa-solid {{ $group['icon'] }}"></i>
                                        <p>{{ $group['title'] }} <i class="nav-arrow fa-solid fa-angle-left"></i></p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @foreach ($group['items'] as $item)
                                            <li class="nav-item">
                                                <div class="d-flex align-items-center">
                                                    <a href="{{ $item['url'] }}" class="nav-link flex-grow-1 {{ $item['active'] ? 'active' : '' }}">
                                                        <i class="nav-icon fa-solid {{ $item['icon'] }}"></i>
                                                        <p>{{ $item['label'] }}</p>
                                                    </a>
                                                    @if ($item['create_url'])
                                                        <a href="{{ $item['create_url'] }}" class="ma-add-btn" title="إضافة"><i class="fa-solid fa-plus"></i></a>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header ma-page-head">
                <div class="container-fluid">
                    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2">
                        <div class="min-w-0">
                            @if ($layoutProject)
                                <div class="ma-crumb"><i class="fa-solid fa-folder-tree"></i><span>{{ $layoutProject->name }}</span></div>
                            @endif
                            <h1 class="ma-page-title">{{ $pageTitle ?? 'لوحة التحكم' }}</h1>
                        </div>
                        @hasSection('page-actions')
                            <div class="d-flex flex-wrap gap-2">@yield('page-actions')</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    {{-- رسائل الخطأ لأي صفحة مش بتعرضها بنفسها --}}
                    @foreach (['error' => 'danger', 'warning' => 'warning'] as $flashKey => $flashClass)
                        @if (is_string(session($flashKey)) && session($flashKey) !== '')
                            <div class="alert alert-{{ $flashClass }} js-flash-fallback" data-flash="{{ session($flashKey) }}">{{ session($flashKey) }}</div>
                        @endif
                    @endforeach
                    @yield('content')
                    <script>
                        document.querySelectorAll('.js-flash-fallback').forEach(function (el) {
                            var msg = el.getAttribute('data-flash');
                            var dup = Array.prototype.some.call(document.querySelectorAll('.alert:not(.js-flash-fallback)'), function (a) {
                                return a.textContent.trim() === msg.trim();
                            });
                            if (dup) el.remove();
                        });
                    </script>
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

                // بنمسك الضغطة قبل أي سكريبت تاني (app.js كان بيبدّل مرة كمان فالوضع يرجع زي ما هو)
                document.addEventListener('click', function (e) {
                    if (!e.target.closest || !e.target.closest('#themeToggle')) return;
                    e.preventDefault();
                    e.stopPropagation();
                    var next = (getTheme() === 'dark') ? 'light' : 'dark';
                    applyTheme(next);
                    syncIcon(next);
                }, true);

                window.__maThemeToggleBound = true;
            } catch (e) {}
        })();
    </script>
    <script>
        // تأكيد قبل حفظ أي مبلغ كبير بشكل غير معتاد (حماية من الأصفار الزيادة).
        (function () {
            const LIMIT = 50000000; // 50 مليون
            const MONEY = /(amount|price|payment|capital|investment|total)/i;
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!(form instanceof HTMLFormElement) || form.dataset.bigAmountOk === '1') return;
                const big = Array.from(form.querySelectorAll('input'))
                    .filter(function (i) { return MONEY.test(i.name || '') && i.type !== 'hidden'; })
                    .map(function (i) { return parseFloat(String(i.value).replace(/,/g, '')); })
                    .filter(function (v) { return isFinite(v) && v >= LIMIT; });
                if (!big.length) return;
                const max = Math.max.apply(null, big);
                if (!window.confirm('المبلغ ' + max.toLocaleString('en-US') + ' ج.م كبير جدًا. متأكد إنه مكتوب صح؟')) {
                    e.preventDefault();
                    return;
                }
                form.dataset.bigAmountOk = '1';
            }, true);
        })();
    </script>
    <script>
        // تذكّر المجموعات المفتوحة في القائمة الجانبية
        (function () {
            var KEY = 'ma_nav_open';
            var saved = [];
            try { saved = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) {}
            var groups = document.querySelectorAll('.ma-sidebar .ma-group');
            groups.forEach(function (g) {
                if (saved.indexOf(g.dataset.group) > -1 && !g.classList.contains('menu-open')) {
                    g.classList.add('menu-open');
                    var ul = g.querySelector('.nav-treeview'); if (ul) ul.style.display = 'block';
                }
            });
            document.querySelectorAll('.ma-sidebar .ma-group-link').forEach(function (a) {
                a.addEventListener('click', function () {
                    setTimeout(function () {
                        var open = Array.prototype.filter.call(groups, function (g) { return g.classList.contains('menu-open'); })
                            .map(function (g) { return g.dataset.group; });
                        try { localStorage.setItem(KEY, JSON.stringify(open)); } catch (e) {}
                    }, 400);
                });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
