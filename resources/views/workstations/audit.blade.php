@extends('layouts.app')
@section('title', 'Audit ' . $workstation->name)
@section('page-title', 'Desk Audit')
@section('content')
@php
    // Lookup for the "found here" box: typed asset tag (upper-cased) → asset.
    $findable = $findableAssets->mapWithKeys(fn ($a) => [strtoupper($a->asset_tag) => [
        'id' => $a->id,
        'tag' => $a->asset_tag,
        'name' => $a->name,
        'icon' => $a->type_icon,
        'where' => $a->workstation ? 'records say ' . $a->workstation->label : ($a->status === 'lost' ? 'marked lost' : 'not at a desk'),
    ]]);
    $oldFound = collect(old('found', []))->map(fn ($id) => $findableAssets->firstWhere('id', (int) $id))->filter()
        ->map(fn ($a) => $findable[strtoupper($a->asset_tag)])->values();
@endphp
<div class="mx-auto max-w-2xl">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h5 class="font-mono text-lg font-semibold text-slate-900 dark:text-white">{{ $workstation->name }}</h5>
            <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">
                {{ $workstation->location?->name }}{{ $workstation->occupant ? ' · ' . $workstation->occupant->name : '' }}
                · Last audit: {{ $workstation->latestAudit ? $workstation->latestAudit->created_at->format('d M Y') . ' by ' . ($workstation->latestAudit->user?->name ?? 'unknown') : 'never' }}
            </p>
        </div>
        <a href="{{ route('workstations.show', $workstation) }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
    </div>

    <form method="POST" action="{{ route('workstations.audit.store', $workstation) }}"
          x-data="{
              findable: @js($findable),
              found: @js($oldFound),
              tag: '',
              error: '',
              add() {
                  const key = this.tag.split(' — ')[0].trim().toUpperCase();
                  if (!key) return;
                  const asset = this.findable[key];
                  if (!asset) { this.error = 'No desktop, monitor or speakerphone with tag ' + key + ' (or it is already recorded here).'; return; }
                  if (!this.found.some(a => a.id === asset.id)) this.found.push(asset);
                  this.tag = ''; this.error = '';
              }
          }">
        @csrf

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-1-circle me-2 text-slate-400"></i>Is the recorded equipment here?</h6>
            </div>
            <div class="card-body">
                @forelse($workstation->assets as $asset)
                <label class="flex cursor-pointer items-center gap-3 border-b border-slate-100 py-3 last:border-0 dark:border-slate-800">
                    <input type="checkbox" name="present[]" value="{{ $asset->id }}" class="h-5 w-5" {{ !session()->hasOldInput() || in_array($asset->id, old('present', [])) ? 'checked' : '' }}>
                    <i class="bi bi-{{ $asset->type_icon }} text-lg text-slate-500"></i>
                    <span class="min-w-0 flex-1">
                        <span class="block font-mono text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $asset->asset_tag }}</span>
                        <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $asset->name }}{{ $asset->serial_number ? ' · S/N ' . $asset->serial_number : '' }}</span>
                    </span>
                    <span class="text-xs text-slate-400">Here</span>
                </label>
                @empty
                <p class="mb-0 text-sm text-slate-400">Nothing is recorded at this desk.</p>
                @endforelse
                @if($workstation->assets->isNotEmpty())
                <p class="field-hint mt-2">Untick anything that isn't at the desk. It will be taken off this workstation and logged as not found; its status is left unchanged.</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-2-circle me-2 text-slate-400"></i>Anything else at this desk?</h6>
            </div>
            <div class="card-body">
                <div class="flex gap-2">
                    <input type="text" class="field-input font-mono uppercase" placeholder="Type or pick an asset tag" list="audit-findable"
                           x-model="tag" @keydown.enter.prevent="add()" @change="if (tag.includes(' — ')) add()" autocomplete="off">
                    <button type="button" class="btn btn-outline" @click="add()"><i class="bi bi-plus-lg"></i>Add</button>
                </div>
                <datalist id="audit-findable">
                    @foreach($findableAssets as $asset)
                    <option value="{{ $asset->asset_tag }} — {{ $asset->name }}"></option>
                    @endforeach
                </datalist>
                <p class="field-error mt-1" x-show="error" x-text="error" x-cloak></p>
                @error('found')<p class="field-error mt-1">{{ $message }}</p>@enderror

                <template x-for="(asset, i) in found" :key="asset.id">
                    <div class="mt-2 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-900/50 dark:bg-amber-950/30">
                        <input type="hidden" name="found[]" :value="asset.id">
                        <i class="text-lg text-amber-600" :class="'bi bi-' + asset.icon"></i>
                        <span class="min-w-0 flex-1">
                            <span class="block font-mono text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="asset.tag"></span>
                            <span class="block truncate text-xs text-slate-500 dark:text-slate-400" x-text="asset.name + ' · ' + asset.where"></span>
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Remove" @click="found.splice(i, 1)"><i class="bi bi-x-lg"></i></button>
                    </div>
                </template>
                <p class="field-hint mt-2">Equipment you find here that the records put somewhere else is moved to this desk, and the move is logged.</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <label class="field-label">Notes</label>
                <textarea name="notes" rows="2" class="field-input" maxlength="1000" placeholder="e.g. Monitor cable missing, desk being renovated">{{ old('notes') }}</textarea>
            </div>
        </div>

        <button class="btn btn-primary w-full py-3"><i class="bi bi-clipboard-check"></i>Save Audit</button>
    </form>
</div>
@endsection
