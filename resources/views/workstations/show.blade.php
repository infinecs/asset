@extends('layouts.app')
@section('title', $workstation->name . ' - Workstation')
@section('page-title', 'Workstation')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <div class="mb-1 flex items-center gap-2">
            <h5 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $workstation->name }}</h5>
            <span class="badge badge-secondary"><i class="bi bi-geo-alt me-1"></i>{{ $workstation->location?->name }}</span>
        </div>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">{{ $workstation->assets->count() }} piece(s) of equipment</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('workstations.audit', $workstation) }}" class="btn btn-primary btn-sm"><i class="bi bi-clipboard-check"></i>Audit Desk</a>
        <a href="{{ route('workstations.floor-plan', ['location' => $workstation->location_id, 'desk' => $workstation->id]) }}" class="btn btn-outline btn-sm" title="{{ $workstation->isPlaced() ? 'See where this desk is in the office' : 'This desk is not on the floor plan yet' }}"><i class="bi bi-map"></i>{{ $workstation->isPlaced() ? 'Show on Floor Plan' : 'Floor Plan' }}</a>
        <a href="{{ route('workstations.label', $workstation) }}" class="btn btn-outline btn-sm" target="_blank"><i class="bi bi-qr-code"></i>Label</a>
        <a href="{{ route('workstations.edit', $workstation) }}" class="btn btn-outline btn-sm"><i class="bi bi-pencil"></i>Edit</a>
        <a href="{{ route('workstations.index', ['location' => $workstation->location_id]) }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
    </div>
</div>

@if($warnings = $workstation->warnings())
<div class="mb-4 flex gap-2 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-300">
    <i class="bi bi-exclamation-triangle mt-0.5"></i>
    <div class="flex-1">
        <div class="font-semibold">This desk needs attention</div>
        <ul class="mt-1 list-disc space-y-0.5 ps-4">
            @foreach($warnings as $warning)
            <li>{{ $warning }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
    <div class="space-y-4 lg:col-span-4">
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-info-circle me-2 text-slate-400"></i>Details</h6>
            </div>
            <div class="card-body space-y-3">
                <div>
                    <label class="text-sm text-slate-500 dark:text-slate-400">Location</label>
                    <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $workstation->location?->name ?? '-' }}</div>
                </div>
                <div>
                    <label class="text-sm text-slate-500 dark:text-slate-400">Occupant</label>
                    <div class="font-semibold text-slate-800 dark:text-slate-100">
                        @if($workstation->occupant)
                        <a href="{{ route('employees.show', $workstation->occupant) }}" class="hover:text-primary-600 dark:hover:text-primary-400">{{ $workstation->occupant->name }}</a>
                        @else
                        Nobody / hot desk
                        @endif
                    </div>
                </div>
                <div>
                    <label class="text-sm text-slate-500 dark:text-slate-400">Last Audit</label>
                    <div class="font-semibold text-slate-800 dark:text-slate-100">
                        @include('workstations._audit-status')
                        @if($workstation->latestAudit?->user)
                        <span class="text-sm font-normal text-slate-500 dark:text-slate-400">by {{ $workstation->latestAudit->user->name }}</span>
                        @endif
                    </div>
                    @if($workstation->isAuditDue())
                    <p class="field-hint mt-1">Due: desks should be checked every {{ \App\Models\Workstation::AUDIT_INTERVAL_DAYS }} days.</p>
                    @endif
                </div>
                @if($workstation->notes)
                <div>
                    <label class="text-sm text-slate-500 dark:text-slate-400">Notes</label>
                    <div class="text-slate-700 dark:text-slate-300">{{ $workstation->notes }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-box-arrow-in-down me-2 text-slate-400"></i>Place Equipment Here</h6>
            </div>
            <div class="card-body">
                @if($placeableAssets->isEmpty())
                <p class="mb-0 text-sm text-slate-400">No other desktops, monitors or speakerphones in service.</p>
                @else
                <button type="button" data-scan-assets class="btn btn-outline-primary mb-3 w-full py-3 text-base sm:py-2 sm:text-sm">
                    <i class="bi bi-qr-code-scan"></i>Scan asset labels
                </button>
                <div class="mb-3 flex items-center gap-3 text-xs text-slate-400">
                    <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>or choose<span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
                </div>
                <form method="POST" action="{{ route('workstations.place-asset', $workstation) }}" class="space-y-3">
                    @csrf
                    <div>
                        <select name="asset_id" class="field-input" required>
                            <option value="">Choose an asset…</option>
                            @foreach($placeableAssets as $asset)
                            <option value="{{ $asset->id }}">
                                {{ $asset->asset_tag }} — {{ $asset->name }} ({{ $asset->workstation ? 'now at ' . $asset->workstation->label : 'not at a desk' }})
                            </option>
                            @endforeach
                        </select>
                        <p class="field-hint mt-1">An asset already at another desk is moved here, and the move is logged.</p>
                    </div>
                    <input type="text" name="notes" class="field-input" maxlength="500" placeholder="Note (optional)">
                    <button class="btn btn-primary w-full py-3 text-base sm:py-2 sm:text-sm"><i class="bi bi-box-arrow-in-down"></i>Place Here</button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-4 lg:col-span-8">
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-display me-2 text-slate-400"></i>Equipment ({{ $workstation->assets->count() }})</h6>
            </div>
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr><th>Asset Tag</th><th>Name</th><th>Status</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($workstation->assets as $asset)
                        <tr>
                            <td><code class="text-primary-600 dark:text-primary-400"><i class="bi bi-{{ $asset->type_icon }} me-1"></i>{{ $asset->asset_tag }}</code></td>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->name }}</td>
                            <td><span class="badge badge-{{ $asset->status_badge }}">{{ $asset->status_label }}</span></td>
                            <td class="text-right">
                                <div class="inline-flex gap-1">
                                    <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary btn-icon" title="View"><i class="bi bi-eye"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline" @click="$dispatch('open-modal', 'move-{{ $asset->id }}')"><i class="bi bi-arrows-move"></i>Move</button>
                                    <form method="POST" action="{{ route('assets.move', $asset) }}" onsubmit="return confirm('Take {{ $asset->asset_tag }} off {{ $workstation->name }}?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger btn-icon" title="Remove from workstation"><i class="bi bi-box-arrow-up"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">No equipment at this workstation yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-clipboard-check me-2 text-slate-400"></i>Audits</h6>
                <a href="{{ route('workstations.audit', $workstation) }}" class="text-sm text-primary-600 hover:underline dark:text-primary-400">Audit now</a>
            </div>
            <div class="card-body">
                @forelse($audits as $audit)
                <div class="border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0 dark:border-slate-800">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-semibold text-slate-800 dark:text-slate-100" title="{{ $audit->created_at->format('d M Y, h:i A') }}">{{ $audit->created_at->format('d M Y') }}</span>
                        <span class="text-slate-500 dark:text-slate-400">by {{ $audit->user?->name ?? 'unknown' }}</span>
                        <span class="badge badge-success">{{ $audit->present_count }} present</span>
                        @if(!empty($audit->missing))<span class="badge badge-danger">{{ count($audit->missing) }} missing</span>@endif
                        @if(!empty($audit->found))<span class="badge badge-warning">{{ count($audit->found) }} found</span>@endif
                    </div>
                    @if(!empty($audit->missing))
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Missing: {{ collect($audit->missing)->map(fn ($a) => $a['tag'])->implode(', ') }}</div>
                    @endif
                    @if(!empty($audit->found))
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Found: {{ collect($audit->found)->map(fn ($a) => $a['tag'] . ($a['from'] ? ' (was ' . $a['from'] . ')' : ''))->implode(', ') }}</div>
                    @endif
                    @if($audit->notes)<div class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ $audit->notes }}</div>@endif
                </div>
                @empty
                <p class="mb-0 text-sm text-slate-400">Not audited yet. Walk up to the desk and check its equipment against the list above.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-arrow-left-right me-2 text-slate-400"></i>Movement History</h6>
            </div>
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr><th>When</th><th>Asset</th><th></th><th>From / To</th><th>By</th></tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                        @php $incoming = $movement->to_workstation_id === $workstation->id; @endphp
                        <tr>
                            <td class="whitespace-nowrap text-slate-500 dark:text-slate-400" title="{{ $movement->created_at->format('d M Y, h:i A') }}">{{ $movement->created_at->format('d M Y') }}</td>
                            <td>
                                @if($movement->asset)
                                <a href="{{ route('assets.show', $movement->asset) }}" class="font-semibold hover:text-primary-600 dark:hover:text-primary-400">{{ $movement->asset->asset_tag }}</a>
                                @else
                                <span class="text-slate-400">Deleted asset</span>
                                @endif
                            </td>
                            <td><span class="badge badge-{{ $incoming ? 'success' : 'warning' }}">{{ $incoming ? 'In' : 'Out' }}</span></td>
                            <td>
                                <div class="text-slate-700 dark:text-slate-300">{{ $incoming ? 'from ' . ($movement->from_label ?? 'storage') : 'to ' . ($movement->to_label ?? 'storage') }}</div>
                                @if($movement->notes)<div class="text-xs text-slate-500 dark:text-slate-400">{{ $movement->notes }}</div>@endif
                            </td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $movement->user?->name ?? 'System' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-500 dark:text-slate-400">No movements recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($movements->hasPages())
            <div class="card-footer">{{ $movements->links() }}</div>
            @endif
        </div>
    </div>
</div>

@foreach($workstation->assets as $asset)
<x-ui.modal id="move-{{ $asset->id }}">
    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
        <h5 class="text-base font-semibold text-slate-900 dark:text-white">Move {{ $asset->asset_tag }}</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Currently at {{ $workstation->label }}</p>
    </div>
    <div class="p-5">
        @include('workstations._move-form', ['asset' => $asset])
    </div>
</x-ui.modal>
@endforeach

@if($placeableAssets->isNotEmpty())
@include('workstations._scan-assets')
{{-- Phones: this page is what opens when a desk label is scanned, so put "Scan equipment" within thumb reach. --}}
<div class="h-20 lg:hidden"></div>
<div class="fixed inset-x-0 bottom-0 z-30 flex items-center gap-3 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur dark:border-slate-800 dark:bg-slate-900/95 lg:hidden">
    <div class="min-w-0 flex-1">
        <div class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $workstation->name }}</div>
        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $workstation->assets->count() }} piece(s) of equipment</div>
    </div>
    <button type="button" data-scan-assets class="btn btn-primary shrink-0 px-4 py-3 text-base">
        <i class="bi bi-qr-code-scan"></i>Scan equipment
    </button>
</div>
@endif
@endsection
