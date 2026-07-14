@extends('layouts.app')

@section('title', 'Assets - IT Asset Management')
@section('page-title', 'Asset Inventory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">All Assets</h5>
        <p class="text-muted small mb-0">Manage and track all IT assets</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('assets.export', request()->query()) }}" class="btn btn-outline-secondary">
            <i class="bi bi-download me-2"></i>Export CSV
        </a>
        @if(auth()->user()->isStaff())
        <a href="{{ route('assets.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add Asset
        </a>
        @endif
    </div>
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

            {{-- Advanced Filter toggle --}}
            <div class="col-12 pt-0">
                <button type="button"
                    class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                    data-bs-toggle="collapse" data-bs-target="#advancedFilters"
                    aria-expanded="{{ request()->hasAny(['cpu','ram','storage','display']) ? 'true' : 'false' }}">
                    <i class="bi bi-sliders"></i> Advanced Filter
                    @if(request()->hasAny(['cpu','ram','storage','display']))
                    <span class="badge bg-primary ms-1">active</span>
                    @endif
                </button>
            </div>

            {{-- Advanced Filter panel --}}
            <div class="col-12 pt-0 collapse {{ request()->hasAny(['cpu','ram','storage','display']) ? 'show' : '' }}" id="advancedFilters">
                <div class="border rounded p-3 bg-body-tertiary">
                    <p class="text-muted small fw-semibold mb-2"><i class="bi bi-cpu me-1"></i>Technical Details</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">CPU</label>
                            <select name="cpu" class="form-select form-select-sm">
                                <option value="">All CPUs</option>
                                @foreach($filterCpus as $opt)
                                <option value="{{ $opt }}" {{ request('cpu') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">RAM</label>
                            <select name="ram" class="form-select form-select-sm">
                                <option value="">All RAM</option>
                                @foreach($filterRams as $opt)
                                <option value="{{ $opt }}" {{ request('ram') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Storage</label>
                            <select name="storage" class="form-select form-select-sm">
                                <option value="">All Storage</option>
                                @foreach($filterStorages as $opt)
                                <option value="{{ $opt }}" {{ request('storage') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Display</label>
                            <select name="display" class="form-select form-select-sm">
                                <option value="">All Displays</option>
                                @foreach($filterDisplays as $opt)
                                <option value="{{ $opt }}" {{ request('display') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Assets Table -->
<div class="card border-0 shadow-sm" data-bulk-container>
    @if(auth()->user()->isStaff())
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <span class="text-muted small"><span data-bulk-count>0</span> selected</span>
        <div class="d-flex gap-2">
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
        </div>
    </div>
    @endif
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                @php
                    $sortDir = request('sort') && request('direction') !== 'desc' ? 'desc' : 'asc';
                    $sortLink = fn($column) => request()->fullUrlWithQuery(['sort' => $column, 'direction' => request('sort') === $column ? $sortDir : 'asc']);
                @endphp
                <thead>
                    <tr>
                        @if(auth()->user()->isStaff())
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" data-bulk-select-all>
                        </th>
                        @endif
                        <th>Photo</th>
                        <th>
                            <a href="{{ $sortLink('asset_tag') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Asset Tag
                                <i class="bi {{ request('sort') === 'asset_tag' ? (request('direction') === 'desc' ? 'bi-sort-down' : 'bi-sort-up') : 'bi-arrow-down-up text-muted' }} small"></i>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortLink('name') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Name
                                <i class="bi {{ request('sort') === 'name' ? (request('direction') === 'desc' ? 'bi-sort-down' : 'bi-sort-up') : 'bi-arrow-down-up text-muted' }} small"></i>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortLink('category') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Category
                                <i class="bi {{ request('sort') === 'category' ? (request('direction') === 'desc' ? 'bi-sort-down' : 'bi-sort-up') : 'bi-arrow-down-up text-muted' }} small"></i>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortLink('location') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Location
                                <i class="bi {{ request('sort') === 'location' ? (request('direction') === 'desc' ? 'bi-sort-down' : 'bi-sort-up') : 'bi-arrow-down-up text-muted' }} small"></i>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortLink('assigned_to') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Assigned To
                                <i class="bi {{ request('sort') === 'assigned_to' ? (request('direction') === 'desc' ? 'bi-sort-down' : 'bi-sort-up') : 'bi-arrow-down-up text-muted' }} small"></i>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortLink('status') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Status
                                <i class="bi {{ request('sort') === 'status' ? (request('direction') === 'desc' ? 'bi-sort-down' : 'bi-sort-up') : 'bi-arrow-down-up text-muted' }} small"></i>
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortLink('last_seen_at') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                                Last Seen
                                <i class="bi {{ request('sort') === 'last_seen_at' ? (request('direction') === 'desc' ? 'bi-sort-down' : 'bi-sort-up') : 'bi-arrow-down-up text-muted' }} small"></i>
                            </a>
                        </th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                    <tr>
                        @if(auth()->user()->isStaff())
                        <td>
                            <input type="checkbox" class="form-check-input" data-bulk-row value="{{ $asset->id }}">
                        </td>
                        @endif
                        <td>
                            @if($asset->photo_path)
                            <img src="{{ asset('storage/' . $asset->photo_path) }}" alt="{{ $asset->name }}" class="rounded border" style="width: 44px; height: 44px; object-fit: cover;">
                            @else
                            <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td><code class="text-primary">{{ $asset->asset_tag }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $asset->name }}</div>
                            @if($asset->brand_label !== '-' || $asset->model)
                            <div class="text-muted small">{{ implode(' ', array_filter([$asset->brand_label !== '-' ? $asset->brand_label : null, $asset->model])) }}</div>
                            @endif
                        </td>
                        <td>{{ $asset->category?->name ?? '-' }}</td>
                        <td>{{ $asset->location?->name ?? '-' }}</td>
                        <td>{{ $asset->assignedEmployee?->name ?? '-' }}</td>
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
                        <td colspan="{{ auth()->user()->isStaff() ? 10 : 9 }}" class="text-center py-5 text-muted">
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
    <div class="card-footer bg-transparent">
        {{ $assets->links() }}
    </div>
    @endif
</div>
@endsection
