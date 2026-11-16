{{--
    Single-workstation fields with a live preview of the generated code (e.g. INFP1ARAWS01).
    Params: $locations, $employees, $roomsByLocation, $workstation (optional).
--}}
@php
    $workstation = $workstation ?? null;
    $locationCodes = $locations->pluck('code', 'id');
@endphp
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2"
     x-data="{
        codes: @js($locationCodes),
        rooms: @js($roomsByLocation),
        locationId: @js((string) old('location_id', $workstation?->location_id ?? request('location', ''))),
        room: @js(old('room', $workstation?->room ?? '')),
        number: @js((string) old('number', $workstation?->number ?? '')),
        get preview() {
            const code = this.codes[this.locationId];
            if (!code || !this.number) return null;
            return '{{ \App\Models\Workstation::COMPANY_PREFIX }}' + code + this.room.toUpperCase() + '{{ \App\Models\Workstation::MARKER }}' + String(parseInt(this.number, 10) || 0).padStart(2, '0');
        }
     }">
    <div class="sm:col-span-2">
        <label class="field-label">Location <span class="text-red-500">*</span></label>
        <select name="location_id" class="field-input @error('location_id') is-invalid @enderror" @change="locationId = $event.target.value">
            <option value="">Select Location</option>
            @foreach($locations as $loc)
            <option value="{{ $loc->id }}" {{ old('location_id', $workstation?->location_id ?? request('location')) == $loc->id ? 'selected' : '' }}>
                {{ $loc->name }}{{ $loc->code ? ' (' . $loc->code . ')' : ' — no code yet' }}
            </option>
            @endforeach
        </select>
        @error('location_id')<p class="field-error">{{ $message }}</p>@enderror
        <p class="field-hint mt-1" x-show="locationId && !codes[locationId]" x-cloak>
            This location has no workstation code yet. <a href="{{ route('locations.index') }}" class="text-primary-600 hover:underline dark:text-primary-400">Set one on the Locations page</a> (e.g. P1A).
        </p>
    </div>
    <div>
        <label class="field-label">Room <span class="font-normal text-slate-400">(optional)</span></label>
        <input type="text" name="room" class="field-input uppercase @error('room') is-invalid @enderror" x-model="room" maxlength="10" placeholder="e.g. RA" list="workstation-rooms">
        <datalist id="workstation-rooms">
            <template x-for="r in (rooms[locationId] || [])" :key="r"><option :value="r"></option></template>
        </datalist>
        @error('room')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="field-label">Number <span class="text-red-500">*</span></label>
        <input type="number" name="number" class="field-input @error('number') is-invalid @enderror" x-model="number" min="1" max="999" placeholder="e.g. 1" required>
        @error('number')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
        <span class="text-sm text-slate-500 dark:text-slate-400">Workstation code:</span>
        <code class="ms-1 font-semibold text-primary-600 dark:text-primary-400" x-text="preview ?? '—'"></code>
    </div>
    <div class="sm:col-span-2">
        <label class="field-label">Occupant <span class="font-normal text-slate-400">(optional)</span></label>
        <select name="occupant_id" class="field-input">
            <option value="">Nobody / hot desk</option>
            @foreach($employees as $employee)
            <option value="{{ $employee->id }}" {{ old('occupant_id', $workstation?->occupant_id) == $employee->id ? 'selected' : '' }}>{{ $employee->name }} <{{ $employee->id_number }}></option>
            @endforeach
        </select>
        @error('occupant_id')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label class="field-label">Notes</label>
        <textarea name="notes" class="field-input" rows="2" placeholder="e.g. Near the window, dual-monitor arm">{{ old('notes', $workstation?->notes) }}</textarea>
    </div>
</div>
