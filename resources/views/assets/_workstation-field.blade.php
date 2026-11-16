{{--
    Workstation picker for the asset form. Only shown for fixed asset types (monitor, desktop,
    speakerphone); choosing a workstation also sets the Location field to that workstation's office.
    Needs $workstationOptions and $currentType; $asset is optional.
--}}
@php
    $asset = $asset ?? null;
    $isFixedType = \App\Models\Asset::isFixedType($currentType ?? null);
    $workstationLocations = $workstationOptions->flatten()->mapWithKeys(fn ($ws) => [$ws->id => $ws->location_id]);
@endphp
<div id="asset_workstation_field" class="{{ $isFixedType ? '' : 'hidden' }}">
    <label class="field-label">Workstation</label>
    @include('workstations._select', [
        'selectId' => 'asset_workstation',
        'selected' => old('workstation_id', $asset?->workstation_id),
        'noneLabel' => 'Not at a workstation',
    ])
    @error('workstation_id')<p class="field-error">{{ $message }}</p>@enderror
    <p class="field-hint mt-1">Location follows the workstation. Moves are logged.</p>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fixedTypes = @json(\App\Models\Asset::FIXED_TYPES);
        const workstationLocations = @json($workstationLocations);
        const typeSelect = document.getElementById('asset_type');
        const field = document.getElementById('asset_workstation_field');
        const workstationSelect = document.getElementById('asset_workstation');
        const locationSelect = document.querySelector('select[name="location_id"]');

        typeSelect?.addEventListener('change', function () {
            const isFixed = fixedTypes.includes(this.value);
            field.classList.toggle('hidden', !isFixed);
            // A half-picked workstation in the hidden field would block the form (its steps are required).
            if (!isFixed) {
                field.querySelector('[data-ws-picker]')?.wsPicker?.reset();
            }
        });

        workstationSelect?.addEventListener('change', function () {
            const locationId = workstationLocations[this.value];
            if (!locationId || !locationSelect) {
                return;
            }
            if (locationSelect.tomselect) {
                locationSelect.tomselect.setValue(String(locationId));
            } else {
                locationSelect.value = locationId;
            }
        });
    });
</script>
@endpush
