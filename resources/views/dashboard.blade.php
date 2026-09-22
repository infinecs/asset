@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Stats --}}
<div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <div class="mb-1 text-sm text-slate-500 dark:text-slate-400">Total Assets</div>
                <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_assets'] }}</div>
            </div>
            <div class="rounded-xl bg-primary-100 p-3 dark:bg-primary-900/40">
                <i class="bi bi-laptop text-lg text-primary-600 dark:text-primary-400"></i>
            </div>
        </div>
        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $stats['total_users'] }} registered users</div>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <div class="mb-1 text-sm text-slate-500 dark:text-slate-400">Available</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['available_assets'] }}</div>
            </div>
            <div class="rounded-xl bg-green-100 p-3 dark:bg-green-900/40">
                <i class="bi bi-check-circle text-lg text-green-600 dark:text-green-400"></i>
            </div>
        </div>
        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
            {{ $stats['total_assets'] > 0 ? round($stats['available_assets'] / $stats['total_assets'] * 100) : 0 }}% of total
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <div class="mb-1 text-sm text-slate-500 dark:text-slate-400">In Use</div>
                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $stats['in_use_assets'] }}</div>
            </div>
            <div class="rounded-xl bg-primary-100 p-3 dark:bg-primary-900/40">
                <i class="bi bi-person-check text-lg text-primary-600 dark:text-primary-400"></i>
            </div>
        </div>
        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
            {{ $stats['total_assets'] > 0 ? round($stats['in_use_assets'] / $stats['total_assets'] * 100) : 0 }}% of total
        </div>
    </div>
    <div class="card p-5">
        <div class="flex items-center justify-between">
            <div>
                <div class="mb-1 text-sm text-slate-500 dark:text-slate-400">In Maintenance</div>
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['maintenance_assets'] }}</div>
            </div>
            <div class="rounded-xl bg-amber-100 p-3 dark:bg-amber-900/40">
                <i class="bi bi-wrench text-lg text-amber-600 dark:text-amber-400"></i>
            </div>
        </div>
        <div class="mt-2 text-xs">
            @if($stats['lost_assets'] > 0)
                <span class="text-red-600 dark:text-red-400">{{ $stats['lost_assets'] }} lost</span>
            @else
                <span class="text-slate-500 dark:text-slate-400">{{ $stats['retired_assets'] }} retired</span>
            @endif
        </div>
    </div>
</div>

{{-- Pending Tasks Alert --}}
@if(auth()->user()->isAdmin() && $pendingTaskAlert && $pendingTaskAlert['count'] > 0)
<div class="card mb-6 border-l-4 !border-l-amber-400">
    <div class="card-header">
        <h6 class="text-sm font-semibold text-amber-600 dark:text-amber-400">
            <i class="bi bi-bell-fill me-1"></i>
            {{ $pendingTaskAlert['count'] }} pending task{{ $pendingTaskAlert['count'] > 1 ? 's' : '' }} · Week {{ $pendingTaskAlert['weekNumber'] }}
            <span class="ml-1 text-xs font-normal text-slate-500 dark:text-slate-400">({{ $pendingTaskAlert['weekStart']->format('d M') }} – {{ $pendingTaskAlert['weekEnd']->format('d M Y') }})</span>
        </h6>
        <a href="{{ route('tasks.index', ['date' => now('Asia/Kuala_Lumpur')->toDateString()]) }}" class="btn btn-sm btn-outline">View Tasks</a>
    </div>
    <div class="px-5 py-3">
        <div class="flex flex-wrap gap-2">
            @foreach($pendingTaskAlert['tasks'] as $task)
            <span class="badge badge-warning font-normal">
                {{ $task->title }}
                <span class="opacity-75">· {{ \Illuminate\Support\Carbon::parse((string)$task->task_date)->format('d M') }}</span>
            </span>
            @endforeach
            @if($pendingTaskAlert['count'] > $pendingTaskAlert['tasks']->count())
            <span class="badge badge-secondary font-normal">
                +{{ $pendingTaskAlert['count'] - $pendingTaskAlert['tasks']->count() }} more
            </span>
            @endif
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

    {{-- Recent Assets --}}
    <div class="lg:col-span-7">
        <div class="card h-full">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Recent Assets</h6>
                <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div>
                @forelse($recentAssets as $asset)
                <a href="{{ route('assets.show', $asset) }}" class="flex items-center border-b border-slate-100 p-3 transition-colors last:border-b-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                    <div class="mr-3 shrink-0 rounded-lg bg-primary-100 p-2 dark:bg-primary-900/40">
                        <i class="bi bi-laptop text-primary-600 dark:text-primary-400"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $asset->name }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            <code class="text-[.7rem]">{{ $asset->asset_tag ?? '-' }}</code>
                            @if($asset->category) · {{ $asset->category->name }}@endif
                            @if($asset->assignedEmployee) · <span class="text-primary-600 dark:text-primary-400">{{ $asset->assignedEmployee->name }}</span>@endif
                        </div>
                    </div>
                    <span class="badge badge-{{ $asset->status_badge }} ml-3 shrink-0">{{ $asset->status_label }}</span>
                </a>
                @empty
                <div class="p-10 text-center text-slate-500 dark:text-slate-400">
                    <i class="bi bi-inbox mb-2 block text-3xl opacity-25"></i>
                    <div class="text-sm">No assets added yet</div>
                    @if(auth()->user()->isStaff())
                    <a href="{{ route('assets.create') }}" class="btn btn-primary btn-sm mt-3">Add First Asset</a>
                    @endif
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right column: Status Breakdown + Quick Actions --}}
    <div class="flex flex-col gap-4 lg:col-span-5">

        {{-- Asset Status Breakdown --}}
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Asset Status</h6>
            </div>
            <div class="card-body">
                @php
                    $statuses = [
                        ['label' => 'Available',    'count' => $stats['available_assets'],    'swatch' => 'bg-green-500'],
                        ['label' => 'In Use',        'count' => $stats['in_use_assets'],        'swatch' => 'bg-primary-500'],
                        ['label' => 'Maintenance',   'count' => $stats['maintenance_assets'],   'swatch' => 'bg-amber-500'],
                        ['label' => 'Retired',       'count' => $stats['retired_assets'],       'swatch' => 'bg-slate-400'],
                        ['label' => 'Lost',          'count' => $stats['lost_assets'],          'swatch' => 'bg-red-500'],
                    ];
                @endphp
                @foreach($statuses as $s)
                <div class="mb-3 last:mb-0">
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <span class="inline-block h-2 w-2 shrink-0 rounded-full {{ $s['swatch'] }}"></span>
                            {{ $s['label'] }}
                        </span>
                        <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $s['count'] }}</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full {{ $s['swatch'] }}" style="width:{{ $stats['total_assets'] > 0 ? ($s['count'] / $stats['total_assets'] * 100) : 0 }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Quick Actions</h6>
            </div>
            <div class="card-body flex flex-col gap-2">
                @if(auth()->user()->isStaff())
                <a href="{{ route('assets.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Asset
                </a>
                @endif
                @if(auth()->user()->isAdmin())
                <a href="{{ route('assets.live') }}" class="btn btn-outline !border-green-300 !text-green-700 hover:!bg-green-50 dark:!border-green-800 dark:!text-green-400 dark:hover:!bg-green-950">
                    <i class="bi bi-activity"></i> Live Tracker
                </a>
                <a href="{{ route('users.index') }}" class="btn btn-outline">
                    <i class="bi bi-people"></i> Manage Users
                </a>
                @endif
            </div>
        </div>

    </div>

    {{-- Assets by Category --}}
    @if($assetsByCategory->count() > 0)
    <div class="lg:col-span-7">
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Assets by Category</h6>
            </div>
            <div class="card-body">
                @foreach($assetsByCategory->sortByDesc('assets_count') as $cat)
                <div class="mb-3 last:mb-0">
                    <div class="mb-1 flex justify-between text-sm text-slate-600 dark:text-slate-300">
                        <span>{{ $cat->name }}</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $cat->assets_count }}</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-primary-500" style="width:{{ $stats['total_assets'] > 0 ? ($cat->assets_count / $stats['total_assets'] * 100) : 0 }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Warranty Expiring --}}
    @if($warrantyExpiring->count() > 0)
    <div class="lg:col-span-12">
        <div class="card border-l-4 !border-l-amber-400">
            <div class="card-header">
                <h6 class="flex items-center gap-2 text-sm font-semibold text-amber-600 dark:text-amber-400">
                    <i class="bi bi-exclamation-triangle"></i>Warranties Expiring Soon
                    <span class="badge badge-warning">{{ $warrantyExpiring->count() }}</span>
                </h6>
            </div>
            <div class="overflow-x-auto">
                <table class="table-clean">
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
                            <td class="font-semibold">{{ $asset->name }}</td>
                            <td><code class="text-xs">{{ $asset->asset_tag ?? '-' }}</code></td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $asset->category?->name ?? '-' }}</td>
                            <td>
                                <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $asset->warranty_expiry->format('d M Y') }}</span>
                                <span class="ml-1 text-xs text-slate-500 dark:text-slate-400">{{ $asset->warranty_expiry->diffForHumans() }}</span>
                            </td>
                            <td class="text-right"><a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
