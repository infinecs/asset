@extends('layouts.app')

@section('title', 'Live Asset Tracker - IT Asset Management')
@section('page-title', 'Live Asset Tracker')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">Live Asset Tracker</h5>
        <p class="text-muted small mb-0">Real-time view of all asset statuses and locations</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted small">Last updated: <strong id="last-updated">{{ now()->format('H:i:s') }}</strong></span>
        <a href="{{ route('assets.live') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </a>
    </div>
</div>

<!-- Status Summary Cards -->
<div class="row g-3 mb-4">
    @php
        $statusConfig = [
            'available' => ['label' => 'Available', 'color' => 'success', 'icon' => 'check-circle'],
            'in_use' => ['label' => 'In Use', 'color' => 'primary', 'icon' => 'person-check'],
            'under_maintenance' => ['label' => 'Maintenance', 'color' => 'warning', 'icon' => 'wrench'],
            'retired' => ['label' => 'Retired', 'color' => 'secondary', 'icon' => 'archive'],
            'lost' => ['label' => 'Lost', 'color' => 'danger', 'icon' => 'question-circle'],
        ];
    @endphp
    @foreach($statusConfig as $key => $config)
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm border-bottom border-{{ $config['color'] }} border-3">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="status-dot {{ $key }}"></span>
                    <div>
                        <div class="fw-bold fs-4">{{ $statusCounts[$key] ?? 0 }}</div>
                        <div class="text-muted small">{{ $config['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Live Asset Grid -->
<div class="card border-0 shadow-sm" data-bulk-container>
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">
            <span class="bg-success rounded-circle d-inline-block me-2" style="width:8px;height:8px;"></span>
            Live Status
        </h6>
        <div class="d-flex align-items-center gap-2">
            @if(auth()->user()->isStaff())
            <span class="text-muted small"><span data-bulk-count>0</span> selected</span>
            <form method="POST" action="{{ route('assets.bulk-destroy') }}" data-bulk-form onsubmit="return confirm('Delete selected assets?')">
                @csrf
                @method('DELETE')
                <span data-bulk-inputs></span>
                <button type="submit" class="btn btn-outline-danger btn-sm" data-bulk-delete-selected>
                    <i class="bi bi-trash me-1"></i>Delete Selected
                </button>
            </form>
            <form method="POST" action="{{ route('assets.destroy-all') }}" onsubmit="return confirm('Delete all assets? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash3 me-1"></i>Delete All
                </button>
            </form>
            @endif
            <span class="text-muted small">{{ $assets->total() }} total assets</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        @if(auth()->user()->isStaff())
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" data-bulk-select-all>
                        </th>
                        @endif
                        <th>Asset</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Last Activity</th>
                        @if(auth()->user()->isStaff())
                        <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                    <tr data-asset-id="{{ $asset->id }}">
                        @if(auth()->user()->isStaff())
                        <td>
                            <input type="checkbox" class="form-check-input" data-bulk-row value="{{ $asset->id }}">
                        </td>
                        @endif
                        <td>
                            <div class="fw-semibold">{{ $asset->name }}</div>
                            <code class="text-muted small">{{ $asset->asset_tag }}</code>
                        </td>
                        <td>{{ $asset->category?->name ?? '-' }}</td>
                        <td>
                            @if($asset->location)
                            <i class="bi bi-geo-alt text-muted me-1"></i>{{ $asset->location->name }}
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($asset->assignedUser)
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:24px;height:24px;">
                                    <span class="text-white" style="font-size:.65rem;">{{ substr($asset->assignedUser->name, 0, 1) }}</span>
                                </div>
                                <span class="small">{{ $asset->assignedUser->name }}</span>
                            </div>
                            @else
                            <span class="text-muted small">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $asset->status_badge }} asset-status-badge" data-status="{{ $asset->status }}">
                                <span class="status-dot {{ $asset->status }} me-1"></span>
                                {{ $asset->status_label }}
                            </span>
                        </td>
                        <td>
                            <span class="text-muted small">
                                @if($asset->last_seen_at)
                                    <i class="bi bi-clock me-1"></i>{{ $asset->last_seen_at->diffForHumans() }}
                                @else
                                    <i class="bi bi-dash text-muted"></i>
                                @endif
                            </span>
                        </td>
                        @if(auth()->user()->isStaff())
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('assets.show', $asset) }}" class="btn btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isStaff() ? 8 : 6 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>No assets to track
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($assets->hasPages())
    <div class="card-footer bg-white">
        {{ $assets->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Auto-refresh the page every 60 seconds
    let countdown = 60;
    const interval = setInterval(() => {
        countdown--;
        if (countdown <= 0) {
            clearInterval(interval);
            window.location.reload();
        }
    }, 1000);
</script>
@endpush
