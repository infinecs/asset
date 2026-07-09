<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Infinecs Asset Management')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.ico') }}">
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            height: 100vh;
            background: #1e293b;
            color: #94a3b8;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
            transition: width .2s ease;
        }
        .sidebar-menu {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: .5rem;
        }
        .sidebar-menu::-webkit-scrollbar {
            width: 8px;
        }
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 999px;
        }
        .sidebar .nav-link {
            color: #94a3b8;
            padding: .5rem 1.25rem;
            border-radius: .375rem;
            margin: 2px 8px;
            font-size: .9rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #334155;
            color: #f1f5f9;
        }
        .sidebar .nav-link i { width: 20px; }
        .sidebar-brand {
            padding: 1.25rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            border-bottom: 1px solid #334155;
        }
        .sidebar-toggle-btn {
            color: #e2e8f0;
            border: 0;
            background: transparent;
            padding: .25rem .4rem;
            border-radius: .375rem;
        }
        .sidebar-toggle-btn:hover {
            background: #334155;
            color: #fff;
        }
        body.sidebar-collapsed .sidebar {
            width: 78px;
        }
        body.sidebar-collapsed .main-content {
            margin-left: 78px;
        }
        body.sidebar-collapsed .sidebar-section,
        body.sidebar-collapsed .nav-text,
        body.sidebar-collapsed .brand-text,
        body.sidebar-collapsed .user-meta,
        body.sidebar-collapsed .logout-text {
            display: none;
        }
        body.sidebar-collapsed .sidebar .nav-link {
            justify-content: center;
            padding: .6rem .5rem;
            margin: 2px 6px;
        }
        body.sidebar-collapsed .sidebar-brand {
            justify-content: center;
            padding: 1rem .5rem;
        }
        body.sidebar-collapsed .sidebar .sidebar-toggle-btn {
            margin: 0 auto;
        }
        .sidebar-section {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            padding: .75rem 1.25rem .25rem;
        }
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            transition: margin-left .2s ease;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
        }
        .ts-wrapper.single .ts-control {
            min-height: calc(1.5em + .75rem + 2px);
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.available { background: #22c55e; }
        .status-dot.in_use { background: #3b82f6; }
        .status-dot.under_maintenance { background: #f59e0b; }
        .status-dot.retired { background: #94a3b8; }
        .status-dot.lost { background: #ef4444; }
        .asset-row-hover { transition: background .15s; }
        .asset-row-hover:hover { background: #f1f5f9; }
        [data-bs-theme="dark"] .asset-row-hover:hover { background: #1e293b; }
        [data-bs-theme="dark"] body { background-color: #0f172a; }
        [data-bs-theme="dark"] .topbar { background: #1e293b; border-color: #334155; }
        [data-bs-theme="dark"] .topbar h6 { color: #f1f5f9 !important; }
        [data-bs-theme="dark"] .topbar .text-muted { color: #94a3b8 !important; }
        #theme-toggle { border: 1px solid #cbd5e1; background: transparent; color: inherit; padding: .25rem .4rem; border-radius: .375rem; cursor: pointer; font-size: 1.1rem; }
        #theme-toggle:hover { background: rgba(0,0,0,.08); }
        [data-bs-theme="dark"] #theme-toggle { border-color: #475569; }
        [data-bs-theme="dark"] #theme-toggle:hover { background: rgba(255,255,255,.08); }
        @media (max-width: 768px) {
            .sidebar { width: 100%; min-height: auto; position: relative; }
            .main-content { margin-left: 0; }
            body.sidebar-collapsed .sidebar { width: 100%; }
            body.sidebar-collapsed .main-content { margin-left: 0; }
            body.sidebar-collapsed .sidebar-section,
            body.sidebar-collapsed .nav-text,
            body.sidebar-collapsed .brand-text,
            body.sidebar-collapsed .user-meta,
            body.sidebar-collapsed .logout-text {
                display: initial;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column">
            <div class="sidebar-brand d-flex align-items-center justify-content-between">
                <a href="{{ route('dashboard') }}" class="brand-text"><img src="{{ asset('images/infinecs-logo-white.png') }}" alt="Infinecs" style="height: 44px; max-width: 200px; object-fit: contain; object-position: left center;"></a>
                <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle-btn" title="Simplify view">
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>
            </div>
            <div class="sidebar-menu mt-2">
                <div class="sidebar-section">Main</div>
                <a href="{{ route('dashboard') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}" data-sidebar-tooltip="Dashboard">
                    <i class="bi bi-speedometer2"></i><span class="nav-text">Dashboard</span>
                </a>
                <a href="{{ route('directory.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('directory.index') ? 'active' : '' }}" data-sidebar-tooltip="Directory">
                    <i class="bi bi-journal-bookmark"></i><span class="nav-text">Directory</span>
                </a>
                <a href="{{ route('employees.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('employees.*') ? 'active' : '' }}" data-sidebar-tooltip="Employees">
                    <i class="bi bi-people"></i><span class="nav-text">Employees</span>
                </a>

                @if(auth()->user()->isStaff())
                <div class="sidebar-section mt-2">Assets</div>
                <a href="{{ route('assets.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('assets.index') ? 'active' : '' }}" data-sidebar-tooltip="All Assets">
                    <i class="bi bi-laptop"></i><span class="nav-text">All Assets</span>
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('assets.live') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('assets.live') ? 'active' : '' }}" data-sidebar-tooltip="Live Tracker">
                    <i class="bi bi-activity"></i><span class="nav-text">Live Tracker</span>
                </a>
                @endif
                @endif

                @if(auth()->user()->isStaff())
                <div class="sidebar-section mt-2">Management</div>
                <a href="{{ route('departments.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('departments.*') ? 'active' : '' }}" data-sidebar-tooltip="Departments">
                    <i class="bi bi-building"></i><span class="nav-text">Departments</span>
                </a>
                <a href="{{ route('brands.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('brands.*') ? 'active' : '' }}" data-sidebar-tooltip="Brands">
                    <i class="bi bi-award"></i><span class="nav-text">Brands</span>
                </a>
                <a href="{{ route('categories.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('categories.*') ? 'active' : '' }}" data-sidebar-tooltip="Categories">
                    <i class="bi bi-tags"></i><span class="nav-text">Categories</span>
                </a>
                <a href="{{ route('locations.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('locations.*') ? 'active' : '' }}" data-sidebar-tooltip="Locations">
                    <i class="bi bi-geo-alt"></i><span class="nav-text">Locations</span>
                </a>
                @endif

                @if(auth()->user()->isAdmin())
                <div class="sidebar-section mt-2">Admin</div>
                <a href="{{ route('tasks.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('tasks.*') ? 'active' : '' }}" data-sidebar-tooltip="Tasks">
                    <i class="bi bi-list-task"></i><span class="nav-text">Tasks</span>
                </a>
                <a href="{{ route('users.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('users.*') ? 'active' : '' }}" data-sidebar-tooltip="Users">
                    <i class="bi bi-people"></i><span class="nav-text">Users</span>
                </a>
                @endif
            </div>

            <div class="p-3 border-top border-secondary mt-auto">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                        <span class="text-white fw-bold" style="font-size:.8rem;">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    </div>
                    <div class="small user-meta">
                        <div class="text-white fw-semibold">{{ auth()->user()->name }}</div>
                        <div class="text-white" style="font-size:.75rem;">{{ ucfirst(auth()->user()->role) }}</div>
                    </div>
                </div>
                <a href="{{ route('settings.edit') }}" class="btn btn-sm btn-outline-secondary w-100 mb-2" data-sidebar-tooltip="Settings">
                    <i class="bi bi-gear me-1"></i><span class="logout-text">Settings</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary w-100" data-sidebar-tooltip="Logout">
                        <i class="bi bi-box-arrow-left me-1"></i><span class="logout-text">Logout</span>
                    </button>
                </form>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="topbar d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold text-dark">@yield('page-title', 'Dashboard')</h6>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-muted">{{ now()->format('D, d M Y') }}</span>
                    <button id="theme-toggle" title="Toggle dark mode">
                        <i class="bi bi-moon-stars-fill" id="theme-icon"></i>
                    </button>
                </div>
            </div>

            <div class="p-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggleButton = document.getElementById('sidebar-toggle-btn');
            const mobileBreakpoint = 768;
            let sidebarTooltips = [];

            const initInlineDropdownSearch = function () {
                if (typeof TomSelect === 'undefined') {
                    return;
                }

                const searchableNames = [
                    'asset_id',
                    'brand_id',
                    'department',
                    'location',
                    'location_id',
                    'category',
                    'category_id',
                    'assigned_to',
                    'user_id',
                    'requested_by',
                    'lessee_id'
                ];

                const selector = searchableNames
                    .map(function (name) { return 'select[name="' + name + '"]'; })
                    .join(',');

                document.querySelectorAll(selector).forEach(function (select) {
                    if (select.dataset.inlineSearchReady === '1' || select.options.length <= 1) {
                        return;
                    }

                    new TomSelect(select, {
                        create: false,
                        maxItems: 1,
                        allowEmptyOption: true,
                        searchField: ['text'],
                        placeholder: 'Search and select...',
                        sortField: [
                            { field: '$score' },
                            { field: '$order' }
                        ]
                    });

                    select.dataset.inlineSearchReady = '1';
                });
            };

            const updateSidebarTooltips = function () {
                sidebarTooltips.forEach(function (tooltip) {
                    tooltip.dispose();
                });
                sidebarTooltips = [];

                const shouldEnableTooltips = document.body.classList.contains('sidebar-collapsed') && window.innerWidth > mobileBreakpoint;
                if (!shouldEnableTooltips) {
                    return;
                }

                document.querySelectorAll('.sidebar [data-sidebar-tooltip]').forEach(function (el) {
                    const title = el.getAttribute('data-sidebar-tooltip');
                    el.setAttribute('data-bs-toggle', 'tooltip');
                    el.setAttribute('data-bs-placement', 'right');
                    el.setAttribute('data-bs-title', title);
                    sidebarTooltips.push(new bootstrap.Tooltip(el));
                });
            };

            const applySidebarPreference = function () {
                if (window.innerWidth <= mobileBreakpoint) {
                    document.body.classList.remove('sidebar-collapsed');
                    updateSidebarTooltips();
                    return;
                }

                if (localStorage.getItem('sidebarSimplified') === '1') {
                    document.body.classList.add('sidebar-collapsed');
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                }

                updateSidebarTooltips();
            };

            applySidebarPreference();
            initInlineDropdownSearch();

            if (sidebarToggleButton) {
                sidebarToggleButton.addEventListener('click', function () {
                    if (window.innerWidth <= mobileBreakpoint) {
                        return;
                    }

                    const collapsed = document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('sidebarSimplified', collapsed ? '1' : '0');
                    updateSidebarTooltips();
                });
            }

            window.addEventListener('resize', applySidebarPreference);

            document.querySelectorAll('[data-bulk-container]').forEach(function (container) {
                const selectAll = container.querySelector('[data-bulk-select-all]');
                const rowCheckboxes = Array.from(container.querySelectorAll('[data-bulk-row]'));
                const selectedForm = container.querySelector('[data-bulk-form]');
                const selectedInputs = container.querySelector('[data-bulk-inputs]');
                const deleteSelectedButton = container.querySelector('[data-bulk-delete-selected]');
                const selectedCount = container.querySelector('[data-bulk-count]');

                if (!selectAll || !selectedForm || !selectedInputs || rowCheckboxes.length === 0) {
                    if (selectAll) {
                        selectAll.disabled = true;
                    }
                    if (deleteSelectedButton) {
                        deleteSelectedButton.disabled = true;
                    }
                    return;
                }

                const syncSelection = function () {
                    const selected = rowCheckboxes.filter(function (checkbox) {
                        return checkbox.checked;
                    });

                    selectAll.checked = selected.length > 0 && selected.length === rowCheckboxes.length;
                    selectAll.indeterminate = selected.length > 0 && selected.length < rowCheckboxes.length;

                    selectedInputs.innerHTML = '';
                    selected.forEach(function (checkbox) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = checkbox.value;
                        selectedInputs.appendChild(input);
                    });

                    if (deleteSelectedButton) {
                        deleteSelectedButton.disabled = selected.length === 0;
                    }

                    if (selectedCount) {
                        selectedCount.textContent = selected.length;
                    }
                };

                selectAll.addEventListener('change', function () {
                    rowCheckboxes.forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                    syncSelection();
                });

                rowCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', syncSelection);
                });

                syncSelection();
            });
        });
    </script>
    <script>
        (function () {
            const btn = document.getElementById('theme-toggle');
            const icon = document.getElementById('theme-icon');

            function applyTheme(theme) {
                document.documentElement.setAttribute('data-bs-theme', theme);
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
                localStorage.setItem('theme', theme);
            }

            applyTheme(localStorage.getItem('theme') || 'light');

            btn.addEventListener('click', function () {
                const current = document.documentElement.getAttribute('data-bs-theme');
                applyTheme(current === 'dark' ? 'light' : 'dark');
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
