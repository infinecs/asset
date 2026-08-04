<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Infinecs Asset Management')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.ico') }}">
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.default.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased" x-data="{
        sidebarCollapsed: localStorage.getItem('sidebarSimplified') === '1',
        mobileOpen: false,
        dark: localStorage.getItem('theme') === 'dark',
        toggleSidebar() { this.sidebarCollapsed = !this.sidebarCollapsed; localStorage.setItem('sidebarSimplified', this.sidebarCollapsed ? '1' : '0'); },
        toggleTheme() { this.dark = !this.dark; document.documentElement.classList.toggle('dark', this.dark); localStorage.setItem('theme', this.dark ? 'dark' : 'light'); }
    }">
    <div class="flex min-h-full">
        <!-- Mobile overlay -->
        <div x-show="mobileOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="mobileOpen = false"></div>

        <!-- Sidebar -->
        <nav
            class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-slate-900 transition-all duration-200 lg:translate-x-0"
            :class="{ 'translate-x-0': mobileOpen, 'lg:!w-[78px]': sidebarCollapsed }"
        >
            <div class="flex h-[68px] shrink-0 items-center justify-between border-b border-slate-800 px-4">
                <a href="{{ route('dashboard') }}" x-show="!sidebarCollapsed || mobileOpen">
                    <img src="{{ asset('images/infinecs-logo-white.png') }}" alt="Infinecs" class="h-10 max-w-[170px] object-contain object-left">
                </a>
                <button type="button" class="rounded-lg p-1.5 text-slate-300 hover:bg-slate-800 hover:text-white hidden lg:inline-flex" @click="toggleSidebar()" title="Simplify view">
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>
                <button type="button" class="rounded-lg p-1.5 text-slate-300 hover:bg-slate-800 hover:text-white lg:hidden" @click="mobileOpen = false">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-2 py-3">
                <div class="sidebar-section" x-show="!sidebarCollapsed || mobileOpen">Main</div>
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-speedometer2"></i><span x-show="!sidebarCollapsed || mobileOpen">Dashboard</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Dashboard</span>
                </a>
                <a href="{{ route('directory.index') }}" class="sidebar-link {{ request()->routeIs('directory.index') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-journal-bookmark"></i><span x-show="!sidebarCollapsed || mobileOpen">Directory</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Directory</span>
                </a>
                <a href="{{ route('employees.index') }}" class="sidebar-link {{ request()->routeIs('employees.*') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-people"></i><span x-show="!sidebarCollapsed || mobileOpen">Employees</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Employees</span>
                </a>

                @if(auth()->user()->isStaff())
                <div class="sidebar-section" x-show="!sidebarCollapsed || mobileOpen">Assets</div>
                <a href="{{ route('assets.index') }}" class="sidebar-link {{ request()->routeIs('assets.index') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-laptop"></i><span x-show="!sidebarCollapsed || mobileOpen">All Assets</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">All Assets</span>
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('assets.live') }}" class="sidebar-link {{ request()->routeIs('assets.live') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-activity"></i><span x-show="!sidebarCollapsed || mobileOpen">Live Tracker</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Live Tracker</span>
                </a>
                @endif
                @endif

                @if(auth()->user()->isStaff())
                <div class="sidebar-section" x-show="!sidebarCollapsed || mobileOpen">Management</div>
                <a href="{{ route('departments.index') }}" class="sidebar-link {{ request()->routeIs('departments.*') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-building"></i><span x-show="!sidebarCollapsed || mobileOpen">Departments</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Departments</span>
                </a>
                <a href="{{ route('brands.index') }}" class="sidebar-link {{ request()->routeIs('brands.*') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-award"></i><span x-show="!sidebarCollapsed || mobileOpen">Brands</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Brands</span>
                </a>
                <a href="{{ route('categories.index') }}" class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-tags"></i><span x-show="!sidebarCollapsed || mobileOpen">Categories</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Categories</span>
                </a>
                <a href="{{ route('locations.index') }}" class="sidebar-link {{ request()->routeIs('locations.*') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-geo-alt"></i><span x-show="!sidebarCollapsed || mobileOpen">Locations</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Locations</span>
                </a>
                @endif

                @if(auth()->user()->isAdmin())
                <div class="sidebar-section" x-show="!sidebarCollapsed || mobileOpen">Admin</div>
                <a href="{{ route('tasks.index') }}" class="sidebar-link {{ request()->routeIs('tasks.*') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-list-task"></i><span x-show="!sidebarCollapsed || mobileOpen">Tasks</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Tasks</span>
                </a>
                <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-people"></i><span x-show="!sidebarCollapsed || mobileOpen">Users</span>
                    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Users</span>
                </a>
                @endif
            </div>

            <div class="shrink-0 border-t border-slate-800 p-3">
                <div class="mb-2 flex items-center gap-2" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600">
                        <span class="text-xs font-bold text-white">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    </div>
                    <div class="min-w-0 text-xs" x-show="!sidebarCollapsed || mobileOpen">
                        <div class="truncate font-semibold text-white">{{ auth()->user()->name }}</div>
                        <div class="text-slate-400">{{ ucfirst(auth()->user()->role) }}</div>
                    </div>
                </div>
                <a href="{{ route('settings.edit') }}" class="btn btn-outline mb-2 w-full !border-slate-700 !text-slate-200 hover:!bg-slate-800" :class="{ '!px-2': sidebarCollapsed && !mobileOpen }">
                    <i class="bi bi-gear"></i><span x-show="!sidebarCollapsed || mobileOpen">Settings</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline w-full !border-slate-700 !text-slate-200 hover:!bg-slate-800" :class="{ '!px-2': sidebarCollapsed && !mobileOpen }">
                        <i class="bi bi-box-arrow-left"></i><span x-show="!sidebarCollapsed || mobileOpen">Logout</span>
                    </button>
                </form>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex min-h-full flex-1 flex-col transition-all duration-200" :class="sidebarCollapsed ? 'lg:pl-[78px]' : 'lg:pl-64'">
            <header class="sticky top-0 z-30 flex h-[68px] shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 dark:border-slate-800 dark:bg-slate-900 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden" @click="mobileOpen = true">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <h6 class="mb-0 text-sm font-semibold text-slate-800 dark:text-slate-100 sm:text-base">@yield('page-title', 'Dashboard')</h6>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-slate-500 dark:text-slate-400 sm:inline">{{ now()->format('D, d M Y') }}</span>
                    <button type="button" class="rounded-lg border border-slate-300 p-1.5 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="toggleTheme()" title="Toggle dark mode">
                        <i class="bi" :class="dark ? 'bi-sun-fill' : 'bi-moon-stars-fill'"></i>
                    </button>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition class="alert alert-success mb-4">
                    <i class="bi bi-check-circle mt-0.5"></i>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button type="button" class="text-green-600 hover:text-green-800 dark:text-green-400" @click="show = false"><i class="bi bi-x-lg"></i></button>
                </div>
                @endif
                @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-triangle mt-0.5"></i>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400" @click="show = false"><i class="bi bi-x-lg"></i></button>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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

            initInlineDropdownSearch();

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
    @stack('scripts')
</body>
</html>
