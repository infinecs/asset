<div class="sidebar-section" x-show="!sidebarCollapsed || mobileOpen">Outcome Admin</div>
<a href="{{ route('outcome.categories.index') }}" class="sidebar-link {{ request()->routeIs('outcome.categories.*') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
    <i class="bi bi-tags"></i><span x-show="!sidebarCollapsed || mobileOpen">Task Categories</span>
    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Task Categories</span>
</a>
<a href="{{ route('outcome.departments.index') }}" class="sidebar-link {{ request()->routeIs('outcome.departments.*') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
    <i class="bi bi-building"></i><span x-show="!sidebarCollapsed || mobileOpen">Departments</span>
    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Departments</span>
</a>
<a href="{{ route('outcome.report') }}" class="sidebar-link {{ request()->routeIs('outcome.report') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
    <i class="bi bi-bar-chart"></i><span x-show="!sidebarCollapsed || mobileOpen">Outcome Report</span>
    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Outcome Report</span>
</a>
<a href="{{ route('outcome.summary') }}" class="sidebar-link {{ request()->routeIs('outcome.summary') ? 'active' : '' }} group relative" :class="{ 'justify-center': sidebarCollapsed && !mobileOpen }">
    <i class="bi bi-graph-up"></i><span x-show="!sidebarCollapsed || mobileOpen">Summary Report</span>
    <span x-show="sidebarCollapsed && !mobileOpen" x-cloak class="pointer-events-none absolute left-full ml-2 whitespace-nowrap rounded-md bg-slate-800 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 z-50">Summary Report</span>
</a>
