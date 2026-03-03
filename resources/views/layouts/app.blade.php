<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IT Asset Management')</title>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background: #1e293b;
            color: #94a3b8;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
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
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
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
        @media (max-width: 768px) {
            .sidebar { width: 100%; min-height: auto; position: relative; }
            .main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column">
            <div class="sidebar-brand">
                <i class="bi bi-hdd-rack me-2"></i>IT Assets
            </div>
            <div class="mt-2 flex-grow-1">
                <div class="sidebar-section">Main</div>
                <a href="{{ route('dashboard') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>

                <div class="sidebar-section mt-2">Assets</div>
                <a href="{{ route('assets.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('assets.index') ? 'active' : '' }}">
                    <i class="bi bi-laptop"></i> All Assets
                </a>
                <a href="{{ route('assets.live') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('assets.live') ? 'active' : '' }}">
                    <i class="bi bi-activity"></i> Live Tracker
                </a>
                @if(auth()->user()->isStaff())
                <a href="{{ route('assets.create') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('assets.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle"></i> Add Asset
                </a>
                @endif

                <div class="sidebar-section mt-2">Tickets</div>
                <a href="{{ route('tickets.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('tickets.index') ? 'active' : '' }}">
                    <i class="bi bi-ticket-detailed"></i> My Tickets
                </a>
                <a href="{{ route('tickets.create') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('tickets.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-square"></i> New Request
                </a>

                @if(auth()->user()->isStaff())
                <div class="sidebar-section mt-2">Management</div>
                <a href="{{ route('categories.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <i class="bi bi-tags"></i> Categories
                </a>
                <a href="{{ route('locations.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('locations.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt"></i> Locations
                </a>
                @endif

                @if(auth()->user()->isAdmin())
                <div class="sidebar-section mt-2">Admin</div>
                <a href="{{ route('users.index') }}" class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Users
                </a>
                @endif
            </div>

            <div class="p-3 border-top border-secondary mt-auto">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                        <span class="text-white fw-bold" style="font-size:.8rem;">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    </div>
                    <div class="small">
                        <div class="text-white fw-semibold">{{ auth()->user()->name }}</div>
                        <div class="text-muted" style="font-size:.75rem;">{{ ucfirst(auth()->user()->role) }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-box-arrow-left me-1"></i>Logout
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
    @stack('scripts')
</body>
</html>
