@extends('layouts.app')
@section('title', 'Workstations')
@section('page-title', 'Workstations')
@section('content')
@php $filters = request()->except(['page', 'view']); @endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Workstations</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Desks in each office and the fixed equipment (monitors, desktops, speakerphones) on them</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('workstations.movements') }}" class="btn btn-outline"><i class="bi bi-arrow-left-right"></i>Movement Log</a>
        <a href="{{ route('workstations.export', $filters) }}" class="btn btn-outline" title="Download the filtered list as CSV"><i class="bi bi-download"></i>Export</a>
        <a href="{{ route('workstations.labels', $filters) }}" class="btn btn-outline" target="_blank" title="Print QR labels for the filtered desks"><i class="bi bi-qr-code"></i>Print Labels</a>
        <a href="{{ route('workstations.create', ['location' => request('location')]) }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i>Add Workstation</a>
    </div>
</div>

@if($auditDueCount > 0 && request('audit') !== 'due')
<div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
    <i class="bi bi-clipboard-check"></i>
    <span class="flex-1">{{ $auditDueCount }} workstation(s) haven't been audited in the last {{ \App\Models\Workstation::AUDIT_INTERVAL_DAYS }} days.</span>
    <a href="{{ route('workstations.index', ['audit' => 'due', 'view' => $view]) }}" class="font-semibold hover:underline">Show them</a>
</div>
@endif

@if(request()->boolean('attention'))
<div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-300">
    <i class="bi bi-exclamation-triangle"></i>
    <span class="flex-1">Showing only desks that need attention: someone who has left is still seated, a seated desk has no equipment recorded, or equipment is assigned to someone who doesn't sit there.</span>
    <a href="{{ route('workstations.index', request()->except(['attention', 'page'])) }}" class="font-semibold hover:underline">Show all desks</a>
</div>
@elseif($attentionCount > 0)
<div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-300">
    <i class="bi bi-exclamation-triangle"></i>
    <span class="flex-1">{{ $attentionCount }} workstation(s) need attention, such as staff who have left still being seated, or equipment assigned to someone else.</span>
    <a href="{{ route('workstations.index', ['attention' => 1, 'view' => $view]) }}" class="font-semibold hover:underline">Show them</a>
</div>
@endif

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <input type="hidden" name="view" value="{{ $view }}">
            @if(request()->boolean('attention'))<input type="hidden" name="attention" value="1">@endif
            <div class="md:col-span-3">
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" class="field-input pl-9" placeholder="Code, occupant, asset tag..." value="{{ request('search') }}">
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
                <select name="room" class="field-input">
                    <option value="">All Rooms</option>
                    @foreach($rooms as $room)
                    <option value="{{ $room }}" {{ request('room') === $room ? 'selected' : '' }}>{{ $room }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="occupancy" class="field-input">
                    <option value="">Any equipment</option>
                    <option value="equipped" {{ request('occupancy') === 'equipped' ? 'selected' : '' }}>Has equipment</option>
                    <option value="empty" {{ request('occupancy') === 'empty' ? 'selected' : '' }}>No equipment</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="audit" class="field-input">
                    <option value="">Any audit</option>
                    <option value="due" {{ request('audit') === 'due' ? 'selected' : '' }}>Due for audit</option>
                    <option value="never" {{ request('audit') === 'never' ? 'selected' : '' }}>Never audited</option>
                </select>
            </div>
            <div class="flex gap-2 md:col-span-1">
                <button class="btn btn-primary btn-icon flex-1" title="Filter"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('workstations.index', ['view' => $view]) }}" class="btn btn-outline btn-icon" title="Reset"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="mb-4 inline-flex rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-800 dark:bg-slate-900">
    <a href="{{ route('workstations.index', $filters + ['view' => 'list']) }}" class="btn btn-sm {{ $view === 'list' ? 'btn-primary' : 'text-slate-600 dark:text-slate-300' }}"><i class="bi bi-list-ul"></i>List</a>
    <a href="{{ route('workstations.index', $filters + ['view' => 'rooms']) }}" class="btn btn-sm {{ $view === 'rooms' ? 'btn-primary' : 'text-slate-600 dark:text-slate-300' }}"><i class="bi bi-grid-3x3-gap"></i>Room view</a>
    <a href="{{ route('workstations.floor-plan', array_filter(['location' => request('location')])) }}" class="btn btn-sm text-slate-600 dark:text-slate-300"><i class="bi bi-map"></i>Floor plan</a>
</div>

@if($view === 'rooms')
    @include('workstations._room-view', ['floors' => $floors])
@else
<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr><th>Workstation</th><th>Location</th><th>Room</th><th>Occupant</th><th>Equipment</th><th>Last Audit</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($workstations as $ws)
                <tr>
                    <td>
                        <a href="{{ route('workstations.show', $ws) }}" class="font-semibold text-slate-800 hover:text-primary-600 dark:text-slate-100 dark:hover:text-primary-400">{{ $ws->name }}</a>
                        @if($warnings = $ws->warnings())
                        <i class="bi bi-exclamation-triangle-fill ms-1 text-orange-500" title="{{ implode("\n", $warnings) }}"></i>
                        <span class="sr-only">Needs attention: {{ implode(' ', $warnings) }}</span>
                        @endif
                    </td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $ws->location?->name ?? '-' }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $ws->room ?? '-' }}</td>
                    <td>
                        @if($ws->occupant)
                        <a href="{{ route('employees.show', $ws->occupant) }}" class="hover:text-primary-600 dark:hover:text-primary-400">{{ $ws->occupant->name }}</a>
                        @else
                        <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            @forelse($ws->assets as $asset)
                            <a href="{{ route('assets.show', $asset) }}" class="badge badge-primary no-underline" title="{{ $asset->name }}">
                                <i class="bi bi-{{ $asset->type_icon }} me-1"></i>{{ $asset->asset_tag }}
                            </a>
                            @empty
                            <span class="text-sm text-slate-400">None</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="whitespace-nowrap">
                        @include('workstations._audit-status', ['workstation' => $ws])
                    </td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('workstations.audit', $ws) }}" class="btn btn-sm btn-outline btn-icon" title="Audit"><i class="bi bi-clipboard-check"></i></a>
                            <a href="{{ route('workstations.show', $ws) }}" class="btn btn-sm btn-outline-primary btn-icon" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('workstations.edit', $ws) }}" class="btn btn-sm btn-outline btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('workstations.destroy', $ws) }}" onsubmit="return confirm('Delete workstation {{ $ws->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-8 text-center text-slate-500 dark:text-slate-400">No workstations found. <a href="{{ route('workstations.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($workstations->hasPages())
    <div class="card-footer">{{ $workstations->links() }}</div>
    @endif
</div>
@endif
@endsection
