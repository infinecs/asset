{{--
    Floor overview: every matching desk as a tile, grouped by office and then room.
    $floors: Collection<location name, Collection<room, Collection<Workstation>>>
--}}
@forelse($floors as $locationName => $roomsInLocation)
<div class="card mb-4">
    <div class="card-header flex items-center justify-between">
        <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-geo-alt me-2 text-slate-400"></i>{{ $locationName }}</h6>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $roomsInLocation->flatten()->count() }} desk(s)</span>
    </div>
    <div class="card-body space-y-5">
        @foreach($roomsInLocation as $room => $desks)
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <span>{{ $room !== '' ? 'Room ' . $room : 'No room' }}</span>
                <span class="font-normal normal-case tracking-normal">· {{ $desks->count() }} desk(s), {{ $desks->filter(fn ($d) => $d->assets->isEmpty())->count() }} empty</span>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                @foreach($desks as $desk)
                <a href="{{ route('workstations.show', $desk) }}" title="{{ $desk->name }}"
                   class="block rounded-lg border p-3 no-underline transition hover:border-primary-400 hover:shadow-sm {{ $desk->assets->isEmpty() ? 'border-dashed border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900' }}">
                    <div class="flex items-center justify-between gap-1">
                        <span class="font-mono text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $desk->desk_code }}</span>
                        @if($desk->isAuditDue())
                        <i class="bi bi-clipboard-x text-amber-500" title="Due for audit"></i>
                        @elseif($desk->latestAudit?->hasDiscrepancies())
                        <i class="bi bi-exclamation-triangle text-amber-500" title="Last audit found issues"></i>
                        @else
                        <i class="bi bi-clipboard-check text-emerald-500" title="Audited {{ $desk->latestAudit->created_at->format('d M Y') }}"></i>
                        @endif
                    </div>
                    <div class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $desk->occupant?->name ?? 'Hot desk' }}</div>
                    <div class="mt-2 flex min-h-5 flex-wrap gap-1 text-slate-600 dark:text-slate-300">
                        @forelse($desk->assets as $asset)
                        <i class="bi bi-{{ $asset->type_icon }}" title="{{ $asset->asset_tag }} — {{ $asset->name }}"></i>
                        @empty
                        <span class="text-xs text-slate-400">Empty</span>
                        @endforelse
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@empty
<div class="card"><div class="card-body py-8 text-center text-slate-500 dark:text-slate-400">No workstations found. <a href="{{ route('workstations.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a></div></div>
@endforelse
