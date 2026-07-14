@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1">Total Assets</div>
                        <div class="fs-2 fw-bold">{{ $stats['total_assets'] }}</div>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-laptop text-primary fs-4"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted" style="font-size:.75rem;">{{ $stats['total_users'] }} registered users</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1">Available</div>
                        <div class="fs-2 fw-bold text-success">{{ $stats['available_assets'] }}</div>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted" style="font-size:.75rem;">
                    {{ $stats['total_assets'] > 0 ? round($stats['available_assets'] / $stats['total_assets'] * 100) : 0 }}% of total
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1">In Use</div>
                        <div class="fs-2 fw-bold text-primary">{{ $stats['in_use_assets'] }}</div>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-person-check text-primary fs-4"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted" style="font-size:.75rem;">
                    {{ $stats['total_assets'] > 0 ? round($stats['in_use_assets'] / $stats['total_assets'] * 100) : 0 }}% of total
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1">In Maintenance</div>
                        <div class="fs-2 fw-bold text-warning">{{ $stats['maintenance_assets'] }}</div>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-wrench text-warning fs-4"></i>
                    </div>
                </div>
                <div class="mt-2" style="font-size:.75rem;">
                    @if($stats['lost_assets'] > 0)
                        <span class="text-danger">{{ $stats['lost_assets'] }} lost</span>
                    @else
                        <span class="text-muted">{{ $stats['retired_assets'] }} retired</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pending Tasks Alert --}}
@if(auth()->user()->isAdmin() && $pendingTaskAlert && $pendingTaskAlert['count'] > 0)
<div class="card border-0 shadow-sm border-start border-warning border-3 mb-4">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold text-warning">
            <i class="bi bi-bell-fill me-2"></i>
            {{ $pendingTaskAlert['count'] }} pending task{{ $pendingTaskAlert['count'] > 1 ? 's' : '' }} · Week {{ $pendingTaskAlert['weekNumber'] }}
            <span class="fw-normal text-muted ms-1" style="font-size:.8rem;">({{ $pendingTaskAlert['weekStart']->format('d M') }} – {{ $pendingTaskAlert['weekEnd']->format('d M Y') }})</span>
        </h6>
        <a href="{{ route('tasks.index', ['date' => now('Asia/Kuala_Lumpur')->toDateString()]) }}" class="btn btn-sm btn-outline-warning">View Tasks</a>
    </div>
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
            @foreach($pendingTaskAlert['tasks'] as $task)
            <span class="badge bg-warning text-dark fw-normal px-2 py-1" style="font-size:.8rem;">
                {{ $task->title }}
                <span class="opacity-75 ms-1">· {{ \Illuminate\Support\Carbon::parse((string)$task->task_date)->format('d M') }}</span>
            </span>
            @endforeach
            @if($pendingTaskAlert['count'] > $pendingTaskAlert['tasks']->count())
            <span class="badge bg-secondary-subtle text-secondary-emphasis border fw-normal px-2 py-1" style="font-size:.8rem;">
                +{{ $pendingTaskAlert['count'] - $pendingTaskAlert['tasks']->count() }} more
            </span>
            @endif
        </div>
    </div>
</div>
@endif

<div class="row g-4">

    {{-- Recent Assets --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
                <h6 class="mb-0 fw-semibold">Recent Assets</h6>
                <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentAssets as $asset)
                <a href="{{ route('assets.show', $asset) }}" class="d-flex align-items-center p-3 border-bottom text-decoration-none text-body asset-row-hover">
                    <div class="bg-primary bg-opacity-10 rounded-3 p-2 me-3 flex-shrink-0">
                        <i class="bi bi-laptop text-primary"></i>
                    </div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="fw-semibold small text-truncate">{{ $asset->name }}</div>
                        <div class="text-muted" style="font-size:.72rem;">
                            <code style="font-size:.7rem;">{{ $asset->asset_tag }}</code>
                            @if($asset->category) · {{ $asset->category->name }}@endif
                            @if($asset->assignedEmployee) · <span class="text-primary">{{ $asset->assignedEmployee->name }}</span>@endif
                        </div>
                    </div>
                    <span class="badge bg-{{ $asset->status_badge }} ms-3 flex-shrink-0">{{ $asset->status_label }}</span>
                </a>
                @empty
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    <div class="small">No assets added yet</div>
                    @if(auth()->user()->isStaff())
                    <a href="{{ route('assets.create') }}" class="btn btn-sm btn-primary mt-3">Add First Asset</a>
                    @endif
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right column: Status Breakdown + Quick Actions --}}
    <div class="col-lg-5 d-flex flex-column gap-4">

        {{-- Asset Status Breakdown --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Asset Status</h6>
            </div>
            <div class="card-body">
                @php
                    $statuses = [
                        ['label' => 'Available',    'count' => $stats['available_assets'],    'color' => 'success'],
                        ['label' => 'In Use',        'count' => $stats['in_use_assets'],        'color' => 'primary'],
                        ['label' => 'Maintenance',   'count' => $stats['maintenance_assets'],   'color' => 'warning'],
                        ['label' => 'Retired',       'count' => $stats['retired_assets'],       'color' => 'secondary'],
                        ['label' => 'Lost',          'count' => $stats['lost_assets'],          'color' => 'danger'],
                    ];
                @endphp
                @foreach($statuses as $s)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="d-flex align-items-center gap-2">
                            <span class="rounded-circle bg-{{ $s['color'] }} d-inline-block" style="width:8px;height:8px;flex-shrink:0;"></span>
                            {{ $s['label'] }}
                        </span>
                        <span class="fw-semibold">{{ $s['count'] }}</span>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-{{ $s['color'] }}" style="width:{{ $stats['total_assets'] > 0 ? ($s['count'] / $stats['total_assets'] * 100) : 0 }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if(auth()->user()->isStaff())
                    <a href="{{ route('assets.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle"></i> Add Asset
                    </a>
                    @endif
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('assets.live') }}" class="btn btn-outline-success d-flex align-items-center gap-2">
                        <i class="bi bi-activity"></i> Live Tracker
                    </a>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="bi bi-people"></i> Manage Users
                    </a>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Assets by Category --}}
    @if($assetsByCategory->count() > 0)
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Assets by Category</h6>
            </div>
            <div class="card-body">
                @foreach($assetsByCategory->sortByDesc('assets_count') as $cat)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $cat->name }}</span>
                        <span class="fw-semibold">{{ $cat->assets_count }}</span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar" style="width:{{ $stats['total_assets'] > 0 ? ($cat->assets_count / $stats['total_assets'] * 100) : 0 }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Warranty Expiring --}}
    @if($warrantyExpiring->count() > 0)
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-warning border-3">
            <div class="card-header bg-transparent border-0 pt-3 d-flex align-items-center gap-2">
                <h6 class="mb-0 fw-semibold text-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>Warranties Expiring Soon
                </h6>
                <span class="badge bg-warning text-dark">{{ $warrantyExpiring->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Asset</th>
                                <th>Tag</th>
                                <th>Category</th>
                                <th>Expires</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warrantyExpiring->sortBy('warranty_expiry') as $asset)
                            <tr>
                                <td class="fw-semibold">{{ $asset->name }}</td>
                                <td><code class="small">{{ $asset->asset_tag }}</code></td>
                                <td class="text-muted small">{{ $asset->category?->name ?? '-' }}</td>
                                <td>
                                    <span class="text-warning fw-semibold">{{ $asset->warranty_expiry->format('d M Y') }}</span>
                                    <small class="text-muted ms-1">{{ $asset->warranty_expiry->diffForHumans() }}</small>
                                </td>
                                <td><a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
