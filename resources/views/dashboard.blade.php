@extends('layouts.app')

@section('title', 'Dashboard - IT Asset Management')
@section('page-title', 'Dashboard')

@section('content')
<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Total Assets</div>
                        <div class="fs-3 fw-bold">{{ $stats['total_assets'] }}</div>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-laptop text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Available</div>
                        <div class="fs-3 fw-bold text-success">{{ $stats['available_assets'] }}</div>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Open Tickets</div>
                        <div class="fs-3 fw-bold text-warning">{{ $stats['open_tickets'] }}</div>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-ticket-detailed text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">In Maintenance</div>
                        <div class="fs-3 fw-bold text-danger">{{ $stats['maintenance_assets'] }}</div>
                    </div>
                    <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-wrench text-danger fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin() && $pendingTaskAlert && $pendingTaskAlert['count'] > 0)
<div class="card border-0 shadow-sm border-start border-warning border-3 mb-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold text-warning">
            <i class="bi bi-bell-fill me-2"></i>
            Pending Tasks Alert · Work Week {{ $pendingTaskAlert['weekNumber'] }} ({{ $pendingTaskAlert['weekYear'] }})
        </h6>
        <a href="{{ route('tasks.index', ['date' => now('Asia/Kuala_Lumpur')->toDateString()]) }}" class="btn btn-sm btn-outline-warning">Open Tasks</a>
    </div>
    <div class="card-body">
        <div class="small text-muted mb-3">
            You have <span class="fw-semibold text-warning">{{ $pendingTaskAlert['count'] }} pending</span> tasks for {{ $pendingTaskAlert['weekStart']->format('d-m-Y') }} - {{ $pendingTaskAlert['weekEnd']->format('d-m-Y') }}.
        </div>
        <div class="list-group list-group-flush">
            @foreach($pendingTaskAlert['tasks'] as $task)
            <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold small">{{ $task->title }}</div>
                    <div class="text-muted" style="font-size:.75rem;">{{ $task->category }} · {{ \Illuminate\Support\Carbon::parse((string) $task->task_date)->format('d-m-Y') }}</div>
                </div>
                <span class="badge bg-warning">Pending</span>
            </div>
            @endforeach
        </div>
        @if($pendingTaskAlert['count'] > $pendingTaskAlert['tasks']->count())
        <div class="small mt-2 text-muted">+{{ $pendingTaskAlert['count'] - $pendingTaskAlert['tasks']->count() }} more pending tasks</div>
        @endif
    </div>
</div>
@endif

<div class="row g-4">
    <!-- Recent Tickets -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3">
                <h6 class="mb-0 fw-semibold">Recent Tickets</h6>
                <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentTickets as $ticket)
                <div class="d-flex align-items-center p-3 border-bottom">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-{{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span>
                            <span class="small text-muted">{{ $ticket->ticket_number }}</span>
                        </div>
                        <div class="fw-semibold small">{{ Str::limit($ticket->subject, 50) }}</div>
                        <div class="text-muted" style="font-size:.75rem;">
                            By {{ $ticket->requester->name }} · {{ $ticket->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <span class="badge bg-{{ $ticket->status_badge }} ms-2">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                </div>
                @empty
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>No tickets yet
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Assets -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3">
                <h6 class="mb-0 fw-semibold">Recent Assets</h6>
                <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentAssets as $asset)
                <div class="d-flex align-items-center p-3 border-bottom">
                    <div class="bg-light rounded-3 p-2 me-3">
                        <i class="bi bi-laptop text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">{{ $asset->name }}</div>
                        <div class="text-muted" style="font-size:.75rem;">
                            {{ $asset->asset_tag }} · {{ $asset->category?->name ?? 'Uncategorized' }}
                        </div>
                    </div>
                    <span class="badge bg-{{ $asset->status_badge }}">{{ $asset->status_label }}</span>
                </div>
                @empty
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>No assets yet
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Warranty Expiring -->
    @if($warrantyExpiring->count() > 0)
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-warning border-3">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Warranties Expiring Soon (30 days)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Asset</th>
                                <th>Tag</th>
                                <th>Category</th>
                                <th>Warranty Expires</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warrantyExpiring as $asset)
                            <tr>
                                <td class="fw-semibold">{{ $asset->name }}</td>
                                <td><code>{{ $asset->asset_tag }}</code></td>
                                <td>{{ $asset->category?->name ?? '-' }}</td>
                                <td>
                                    <span class="text-warning fw-semibold">{{ $asset->warranty_expiry->format('d M Y') }}</span>
                                    <small class="text-muted ms-1">({{ $asset->warranty_expiry->diffForHumans() }})</small>
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

    <!-- Assets by Category -->
    @if($assetsByCategory->count() > 0)
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Assets by Category</h6>
            </div>
            <div class="card-body">
                @foreach($assetsByCategory as $cat)
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $cat->name }}</span>
                        <span class="fw-semibold">{{ $cat->assets_count }}</span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar" style="width:{{ $stats['total_assets'] > 0 ? ($cat->assets_count / $stats['total_assets'] * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ route('tickets.create') }}" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
                            <i class="bi bi-plus-square fs-4"></i>
                            <span class="small">New Request</span>
                        </a>
                    </div>
                    <div class="col-6">
                        @if(auth()->user()->isAdmin())
                        <a href="{{ route('assets.live') }}" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center gap-1">
                            <i class="bi bi-activity fs-4"></i>
                            <span class="small">Live Tracker</span>
                        </a>
                        @endif
                    </div>
                    @if(auth()->user()->isStaff())
                    <div class="col-6">
                        <a href="{{ route('assets.create') }}" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center gap-1">
                            <i class="bi bi-plus-circle fs-4"></i>
                            <span class="small">Add Asset</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-warning w-100 py-3 d-flex flex-column align-items-center gap-1">
                            <i class="bi bi-list-check fs-4"></i>
                            <span class="small">All Tickets</span>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
