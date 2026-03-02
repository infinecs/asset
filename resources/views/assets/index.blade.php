@extends('layouts.app')

@section('title', 'Assets - IT Asset Management')
@section('page-title', 'Asset Inventory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">All Assets</h5>
        <p class="text-muted small mb-0">Manage and track all IT assets</p>
    </div>
    @if(auth()->user()->isStaff())
    <a href="{{ route('assets.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add Asset
    </a>
    @endif
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name, tag, serial..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="in_use" {{ request('status') == 'in_use' ? 'selected' : '' }}>In Use</option>
                    <option value="under_maintenance" {{ request('status') == 'under_maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="retired" {{ request('status') == 'retired' ? 'selected' : '' }}>Retired</option>
                    <option value="lost" {{ request('status') == 'lost' ? 'selected' : '' }}>Lost</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="location" class="form-select">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" {{ request('location') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Assets Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Asset Tag</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Last Seen</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                    <tr>
                        <td><code class="text-primary">{{ $asset->asset_tag }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $asset->name }}</div>
                            @if($asset->brand || $asset->model)
                            <div class="text-muted small">{{ implode(' ', array_filter([$asset->brand, $asset->model])) }}</div>
                            @endif
                        </td>
                        <td>{{ $asset->category?->name ?? '-' }}</td>
                        <td>{{ $asset->location?->name ?? '-' }}</td>
                        <td>{{ $asset->assignedUser?->name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $asset->status_badge }}">
                                <span class="status-dot {{ $asset->status }} me-1"></span>
                                {{ $asset->status_label }}
                            </span>
                        </td>
                        <td class="text-muted small">
                            {{ $asset->last_seen_at ? $asset->last_seen_at->diffForHumans() : 'Never' }}
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('assets.show', $asset) }}" class="btn btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->isStaff())
                                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No assets found. 
                            @if(auth()->user()->isStaff())
                            <a href="{{ route('assets.create') }}">Add the first asset</a>
                            @endif
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
