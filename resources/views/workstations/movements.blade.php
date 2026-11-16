@extends('layouts.app')
@section('title', 'Movement Log')
@section('page-title', 'Movement Log')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Movement Log</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Every time a monitor, desktop or speakerphone changed workstation</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('workstations.movements.export', request()->except('page')) }}" class="btn btn-outline" title="Download the filtered log as CSV"><i class="bi bi-download"></i>Export</a>
        <a href="{{ route('workstations.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i>Workstations</a>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-3">
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" class="field-input pl-9" placeholder="Asset tag, serial, desk..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="md:col-span-2">
                <select name="location" class="field-input">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" {{ request('location') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="type" class="field-input">
                    <option value="">All Types</option>
                    @foreach(\App\Models\Asset::FIXED_TYPES as $type)
                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <input type="date" name="from" class="field-input" value="{{ request('from') }}" title="From date">
            </div>
            <div class="md:col-span-2">
                <input type="date" name="to" class="field-input" value="{{ request('to') }}" title="To date">
            </div>
            <div class="flex gap-2 md:col-span-1">
                <button class="btn btn-primary btn-icon flex-1" title="Filter"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('workstations.movements') }}" class="btn btn-outline btn-icon" title="Reset"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr><th>When</th><th>Asset</th><th>From</th><th></th><th>To</th><th>Note</th><th>By</th></tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                <tr>
                    <td class="whitespace-nowrap text-slate-500 dark:text-slate-400">{{ $movement->created_at->format('d M Y, h:i A') }}</td>
                    <td>
                        @if($movement->asset)
                        <a href="{{ route('assets.show', $movement->asset) }}" class="font-semibold hover:text-primary-600 dark:hover:text-primary-400">
                            <i class="bi bi-{{ $movement->asset->type_icon }} me-1 text-slate-400"></i>{{ $movement->asset->asset_tag }}
                        </a>
                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $movement->asset->name }}</div>
                        @else
                        <span class="text-slate-400">Deleted asset</span>
                        @endif
                    </td>
                    <td>
                        @if($movement->fromWorkstation)
                        <a href="{{ route('workstations.show', $movement->fromWorkstation) }}" class="hover:text-primary-600 dark:hover:text-primary-400">{{ $movement->from_label }}</a>
                        @else
                        <span class="text-slate-500 dark:text-slate-400">{{ $movement->from_label ?? 'Storage' }}</span>
                        @endif
                    </td>
                    <td class="text-slate-400"><i class="bi bi-arrow-right"></i></td>
                    <td>
                        @if($movement->toWorkstation)
                        <a href="{{ route('workstations.show', $movement->toWorkstation) }}" class="hover:text-primary-600 dark:hover:text-primary-400">{{ $movement->to_label }}</a>
                        @else
                        <span class="text-slate-500 dark:text-slate-400">{{ $movement->to_label ?? 'Storage' }}</span>
                        @endif
                    </td>
                    <td class="text-sm text-slate-500 dark:text-slate-400">{{ $movement->notes ?? '' }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $movement->user?->name ?? 'System' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-8 text-center text-slate-500 dark:text-slate-400">No movements found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())
    <div class="card-footer">{{ $movements->links() }}</div>
    @endif
</div>
@endsection
