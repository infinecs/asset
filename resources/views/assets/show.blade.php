@extends('layouts.app')

@section('title', $asset->name . ' - IT Asset Management')
@section('page-title', 'Asset Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">{{ $asset->name }}</h5>
        <code class="text-muted">{{ $asset->asset_tag }}</code>
    </div>
    <div class="d-flex gap-2">
@if(auth()->user()->isStaff())
        <a href="{{ route('assets.edit', $asset) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        @endif
        <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Asset Details -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Asset Information</h6>
                <span class="badge bg-{{ $asset->status_badge }} px-3 py-2">
                    <span class="status-dot {{ $asset->status }} me-1"></span>{{ $asset->status_label }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Asset Tag</label>
                        <div class="fw-semibold"><code>{{ $asset->asset_tag }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Serial Number</label>
                        <div class="fw-semibold">{{ $asset->serial_number ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Brand</label>
                        <div class="fw-semibold">{{ $asset->brand_label }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Model</label>
                        <div class="fw-semibold">{{ $asset->model ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Category</label>
                        <div class="fw-semibold">{{ $asset->category?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Location</label>
                        <div class="fw-semibold">{{ $asset->location?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Assigned To</label>
                        <div class="fw-semibold">{{ $asset->assignedEmployee?->name ?? 'Unassigned' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Last Seen</label>
                        <div class="fw-semibold">{{ $asset->last_seen_at ? $asset->last_seen_at->format('d M Y H:i') : 'Never' }}</div>
                    </div>
                    @if($asset->notes)
                    <div class="col-12">
                        <label class="text-muted small">Notes</label>
                        <div>{{ $asset->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Purchase Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Purchase Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="text-muted small">Purchase Date</label>
                        <div class="fw-semibold">{{ $asset->purchase_date ? $asset->purchase_date->format('d M Y') : '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Purchase Cost</label>
                        <div class="fw-semibold">{{ $asset->purchase_cost ? 'MYR ' . number_format($asset->purchase_cost, 2) : '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small">Warranty Expiry</label>
                        <div class="fw-semibold">
                            @if($asset->warranty_expiry)
                                @if($asset->isWarrantyExpired())
                                    <span class="text-danger"><i class="bi bi-x-circle me-1"></i>{{ $asset->warranty_expiry->format('d M Y') }} (Expired)</span>
                                @elseif($asset->isWarrantyExpiringSoon())
                                    <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>{{ $asset->warranty_expiry->format('d M Y') }} (Expiring soon)</span>
                                @else
                                    <span class="text-success"><i class="bi bi-check-circle me-1"></i>{{ $asset->warranty_expiry->format('d M Y') }}</span>
                                @endif
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technical Details -->
        @if($asset->cpu || $asset->ram || $asset->storage || $asset->display)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-cpu me-2 text-muted"></i>Technical Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">CPU</label>
                        <div class="fw-semibold">{{ $asset->cpu ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">RAM</label>
                        <div class="fw-semibold">{{ $asset->ram ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Storage</label>
                        <div class="fw-semibold">{{ $asset->storage ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Display</label>
                        <div class="fw-semibold">{{ $asset->display ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Asset Photo</h6>
            </div>
            <div class="card-body text-center">
                @if($asset->photo_path)
                <img src="{{ asset('storage/' . $asset->photo_path) }}" alt="{{ $asset->name }}" class="img-fluid rounded border" style="max-height: 240px; object-fit: cover; width: 100%;">
                @else
                <div class="text-muted small py-4">No photo uploaded.</div>
                @endif
            </div>
        </div>

        <!-- Quick Status Update -->
        @if(auth()->user()->isStaff())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.update-status', $asset) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            @foreach(['available', 'in_use', 'under_maintenance', 'retired', 'lost'] as $s)
                            <option value="{{ $s }}" {{ $asset->status == $s ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $s)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <textarea name="notes" class="form-control" rows="2" placeholder="Update notes..."></textarea>
                    </div>
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-arrow-repeat me-1"></i>Update Status
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- History -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Activity History</h6>
            </div>
            <div class="card-body p-0">
                @forelse($activityTimeline as $activity)
                <div class="d-flex gap-2 p-3 border-bottom">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                        <i class="bi bi-{{ $activity->icon }} small text-muted"></i>
                    </div>
                    <div>
                        <div class="small fw-semibold">{{ $activity->title }}</div>
                        <div class="text-muted" style="font-size:.75rem;">
                            {{ $activity->by }} · {{ $activity->at->diffForHumans() }}
                        </div>
                        @if($activity->notes)
                        <div class="text-muted small">{{ $activity->notes }}</div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="p-3 text-center text-muted small">No history</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
