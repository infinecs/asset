{{--
    Form that moves $asset to another workstation (or off its workstation). Needs $workstationOptions.
    Optional $formId lets a button elsewhere on the page open the desk scanner for it (data-ws-scan="<id>").
--}}
<form method="POST" action="{{ route('assets.move', $asset) }}" class="space-y-3"
      @isset($formId) id="{{ $formId }}" @endisset
      data-asset-label="{{ $asset->asset_tag ?? $asset->name }}"
      data-current-workstation="{{ $asset->workstation_id }}">
    @csrf
    <button type="button" data-ws-scan class="btn btn-outline-primary w-full py-3 text-base sm:py-2 sm:text-sm">
        <i class="bi bi-qr-code-scan"></i>Scan desk label
    </button>
    <div class="flex items-center gap-3 text-xs text-slate-400">
        <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>or choose<span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
    </div>
    <div>
        <label class="field-label">Move to</label>
        @include('workstations._select', [
            'selected' => null,
            'exclude' => $asset->workstation_id,
            'noneLabel' => $asset->workstation_id ? 'Remove from workstation (to storage)' : 'Choose a workstation',
        ])
    </div>
    <div>
        <label class="field-label">Note <span class="font-normal text-slate-400">(optional)</span></label>
        <input type="text" name="notes" class="field-input" maxlength="500" placeholder="e.g. Swapped with faulty monitor at WS-07">
    </div>
    <button class="btn btn-primary w-full py-3 text-base sm:py-2 sm:text-sm"><i class="bi bi-arrows-move"></i>Move Asset</button>
</form>
@include('workstations._scan-desk')
