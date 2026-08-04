@extends('layouts.app')

@section('title', 'Live Asset Tracker - IT Asset Management')
@section('page-title', 'Live Asset Tracker')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Live Asset Tracker</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Real-time view of all asset statuses and locations</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500 dark:text-slate-400">Last updated: <strong id="last-updated" class="text-slate-800 dark:text-slate-100">{{ now()->format('H:i:s') }}</strong></span>
        <a href="{{ route('assets.live') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-clockwise"></i>Refresh
        </a>
    </div>
</div>

<!-- Status Summary Cards -->
<div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
    @php
        $statusConfig = [
            'available' => ['label' => 'Available', 'badge' => 'success', 'border' => 'border-b-green-400'],
            'in_use' => ['label' => 'In Use', 'badge' => 'primary', 'border' => 'border-b-primary-400'],
            'under_maintenance' => ['label' => 'Maintenance', 'badge' => 'warning', 'border' => 'border-b-amber-400'],
            'retired' => ['label' => 'Retired', 'badge' => 'secondary', 'border' => 'border-b-slate-400'],
            'lost' => ['label' => 'Lost', 'badge' => 'danger', 'border' => 'border-b-red-400'],
        ];
    @endphp
    @foreach($statusConfig as $key => $config)
    <div class="card border-b-4 {{ $config['border'] }} p-4">
        <div class="flex items-center gap-2">
            <span class="status-dot {{ $key }}"></span>
            <div>
                <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $statusCounts[$key] ?? 0 }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ $config['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Live Asset Grid -->
<div class="card" data-bulk-container>
    <div class="card-header">
        <h6 class="flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-slate-100">
            <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
            Live Status
        </h6>
        <div class="flex items-center gap-2">
            @if(auth()->user()->isStaff())
            <span class="text-sm text-slate-500 dark:text-slate-400"><span data-bulk-count>0</span> selected</span>
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
            @endif
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $assets->total() }} total assets</span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr>
                    @if(auth()->user()->isStaff())
                    <th class="w-10">
                        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" data-bulk-select-all>
                    </th>
                    @endif
                    <th>Photo</th>
                    <th>Asset</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Warranty</th>
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
                    <td>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->name }}</div>
                        <code class="text-xs text-slate-500 dark:text-slate-400">{{ $asset->asset_tag }}</code>
                    </td>
                    <td>{{ $asset->category?->name ?? '-' }}</td>
                    <td>
                        @if($asset->location)
                        <i class="bi bi-geo-alt me-1 text-slate-400"></i>{{ $asset->location->name }}
                        @else
                        <span class="text-slate-400">-</span>
                        @endif
                    </td>
                    <td>
                        @if($asset->assignedEmployee)
                        <div class="flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-600">
                                <span class="text-[.65rem] text-white">{{ substr($asset->assignedEmployee->name, 0, 1) }}</span>
                            </div>
                            <span class="text-sm">{{ $asset->assignedEmployee->name }}</span>
                        </div>
                        @else
                        <span class="text-sm text-slate-400">Unassigned</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $asset->status_badge }}" data-status="{{ $asset->status }}">
                            <span class="status-dot {{ $asset->status }}"></span>
                            {{ $asset->status_label }}
                        </span>
                    </td>
                    <td>
                        @if(!$asset->warranty_expiry)
                            <span class="badge badge-secondary">No Warranty</span>
                        @elseif($asset->isWarrantyExpired())
                            <span class="badge badge-danger">Expired</span>
                        @elseif($asset->isWarrantyExpiringSoon())
                            <span class="badge badge-warning">Expiring Soon</span>
                        @else
                            <span class="badge badge-success">Active</span>
                        @endif
                        @if($asset->warranty_expiry)
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $asset->warranty_expiry->format('d M Y') }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="text-sm text-slate-500 dark:text-slate-400">
                            @if($asset->last_seen_at)
                                <i class="bi bi-clock me-1"></i>{{ $asset->last_seen_at->diffForHumans() }}
                            @else
                                <i class="bi bi-dash"></i>
                            @endif
                        </span>
                    </td>
                    @if(auth()->user()->isStaff())
                    <td>
                        <div class="inline-flex gap-1">
                            <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary btn-icon" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-outline btn-icon" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->isStaff() ? 10 : 8 }}" class="py-12 text-center text-slate-500 dark:text-slate-400">
                        <i class="bi bi-inbox mb-2 block text-3xl"></i>No assets to track
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
