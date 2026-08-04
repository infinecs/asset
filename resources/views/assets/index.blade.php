@extends('layouts.app')

@section('title', 'Assets - IT Asset Management')
@section('page-title', 'Asset Inventory')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="text-lg font-semibold text-slate-900 dark:text-white">All Assets</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Manage and track all IT assets</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('assets.export', request()->query()) }}" class="btn btn-outline">
            <i class="bi bi-download"></i>Export CSV
        </a>
        @if(auth()->user()->isStaff())
        <a href="{{ route('assets.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i>Add Asset
        </a>
        @endif
    </div>
</div>

<!-- Filters -->
<div class="card mb-6" x-data="{ advanced: {{ request()->hasAny(['cpu','ram','storage','display']) ? 'true' : 'false' }} }">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-4">
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" class="field-input pl-9" placeholder="Search name, tag, serial..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="md:col-span-2">
                <select name="status" class="field-input">
                    <option value="">All Status</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="in_use" {{ request('status') == 'in_use' ? 'selected' : '' }}>In Use</option>
                    <option value="under_maintenance" {{ request('status') == 'under_maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="retired" {{ request('status') == 'retired' ? 'selected' : '' }}>Retired</option>
                    <option value="lost" {{ request('status') == 'lost' ? 'selected' : '' }}>Lost</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="category" class="field-input">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="location" class="field-input">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" {{ request('location') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('assets.index') }}" class="btn btn-outline">Clear</a>
            </div>

            {{-- Advanced Filter toggle --}}
            <div class="md:col-span-12">
                <button type="button" class="btn btn-sm btn-outline" @click="advanced = !advanced">
                    <i class="bi bi-sliders"></i> Advanced Filter
                    @if(request()->hasAny(['cpu','ram','storage','display']))
                    <span class="badge badge-primary">active</span>
                    @endif
                </button>
            </div>

            {{-- Advanced Filter panel --}}
            <div class="md:col-span-12" x-show="advanced" x-collapse x-cloak>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                    <p class="mb-2 text-sm font-semibold text-slate-500 dark:text-slate-400"><i class="bi bi-cpu me-1"></i>Technical Details</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="field-label">CPU</label>
                            <select name="cpu" class="field-input">
                                <option value="">All CPUs</option>
                                @foreach($filterCpus as $opt)
                                <option value="{{ $opt }}" {{ request('cpu') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">RAM</label>
                            <select name="ram" class="field-input">
                                <option value="">All RAM</option>
                                @foreach($filterRams as $opt)
                                <option value="{{ $opt }}" {{ request('ram') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Storage</label>
                            <select name="storage" class="field-input">
                                <option value="">All Storage</option>
                                @foreach($filterStorages as $opt)
                                <option value="{{ $opt }}" {{ request('storage') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Display</label>
                            <select name="display" class="field-input">
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
<div class="card" data-bulk-container>
    @if(auth()->user()->isStaff())
    <div class="card-header">
        <span class="text-sm text-slate-500 dark:text-slate-400"><span data-bulk-count>0</span> selected</span>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('assets.bulk-destroy') }}" data-bulk-form onsubmit="return confirm('Delete selected assets?')">
                @csrf
                @method('DELETE')
                <span data-bulk-inputs></span>
                <button type="submit" class="btn btn-outline-danger btn-sm" data-bulk-delete-selected>
                    <i class="bi bi-trash"></i>Delete Selected
                </button>
            </form>
            <form method="POST" action="{{ route('assets.destroy-all') }}" onsubmit="return confirm('Delete all assets? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash3"></i>Delete All
                </button>
            </form>
        </div>
    </div>
    @endif
    <div class="overflow-x-auto">
        <table class="table-clean">
            @php
                $sortDir = request('sort') && request('direction') !== 'desc' ? 'desc' : 'asc';
                $sortLink = fn($column) => request()->fullUrlWithQuery(['sort' => $column, 'direction' => request('sort') === $column ? $sortDir : 'asc']);
                $sortIcon = fn($column) => request('sort') === $column ? (request('direction') === 'desc' ? 'bi-sort-down' : 'bi-sort-up') : 'bi-arrow-down-up text-slate-400';
            @endphp
            <thead>
                <tr>
                    @if(auth()->user()->isStaff())
                    <th class="w-10">
                        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" data-bulk-select-all>
                    </th>
                    @endif
                    <th>Photo</th>
                    <th><a href="{{ $sortLink('asset_tag') }}" class="inline-flex items-center gap-1 text-inherit hover:text-primary-600">Asset Tag <i class="bi {{ $sortIcon('asset_tag') }} text-xs"></i></a></th>
                    <th><a href="{{ $sortLink('name') }}" class="inline-flex items-center gap-1 text-inherit hover:text-primary-600">Name <i class="bi {{ $sortIcon('name') }} text-xs"></i></a></th>
                    <th><a href="{{ $sortLink('category') }}" class="inline-flex items-center gap-1 text-inherit hover:text-primary-600">Category <i class="bi {{ $sortIcon('category') }} text-xs"></i></a></th>
                    <th><a href="{{ $sortLink('location') }}" class="inline-flex items-center gap-1 text-inherit hover:text-primary-600">Location <i class="bi {{ $sortIcon('location') }} text-xs"></i></a></th>
                    <th><a href="{{ $sortLink('assigned_to') }}" class="inline-flex items-center gap-1 text-inherit hover:text-primary-600">Assigned To <i class="bi {{ $sortIcon('assigned_to') }} text-xs"></i></a></th>
                    <th><a href="{{ $sortLink('status') }}" class="inline-flex items-center gap-1 text-inherit hover:text-primary-600">Status <i class="bi {{ $sortIcon('status') }} text-xs"></i></a></th>
                    <th><a href="{{ $sortLink('last_seen_at') }}" class="inline-flex items-center gap-1 text-inherit hover:text-primary-600">Last Seen <i class="bi {{ $sortIcon('last_seen_at') }} text-xs"></i></a></th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                <tr>
                    @if(auth()->user()->isStaff())
                    <td>
                        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" data-bulk-row value="{{ $asset->id }}">
                    </td>
                    @endif
                    <td>
                        @if($asset->photo_path)
                        <img src="{{ asset('storage/' . $asset->photo_path) }}" alt="{{ $asset->name }}" class="h-11 w-11 rounded-lg border border-slate-200 object-cover dark:border-slate-700">
                        @else
                        <span class="text-sm text-slate-400">-</span>
                        @endif
                    </td>
                    <td><code class="text-primary-600 dark:text-primary-400">{{ $asset->asset_tag }}</code></td>
                    <td>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->name }}</div>
                        @if($asset->brand_label !== '-' || $asset->model)
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ implode(' ', array_filter([$asset->brand_label !== '-' ? $asset->brand_label : null, $asset->model])) }}</div>
                        @endif
                    </td>
                    <td>{{ $asset->category?->name ?? '-' }}</td>
                    <td>{{ $asset->location?->name ?? '-' }}</td>
                    <td>{{ $asset->assignedEmployee?->name ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $asset->status_badge }}">
                            <span class="status-dot {{ $asset->status }}"></span>
                            {{ $asset->status_label }}
                        </span>
                    </td>
                    <td class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $asset->last_seen_at ? $asset->last_seen_at->diffForHumans() : 'Never' }}
                    </td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary btn-icon" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if(auth()->user()->isStaff())
                            <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-outline btn-icon" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->isStaff() ? 10 : 9 }}" class="py-12 text-center text-slate-500 dark:text-slate-400">
                        <i class="bi bi-inbox mb-2 block text-3xl"></i>
                        No assets found.
                        @if(auth()->user()->isStaff())
                        <a href="{{ route('assets.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add the first asset</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($assets->hasPages())
    <div class="card-footer">
        {{ $assets->links() }}
    </div>
    @endif
</div>
@endsection
