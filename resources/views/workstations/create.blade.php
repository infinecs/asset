@extends('layouts.app')
@section('title', 'Add Workstation')
@section('page-title', 'Add Workstation')
@section('content')
<div class="flex justify-center" x-data="{ mode: '{{ old('mode') === 'bulk' ? 'bulk' : 'single' }}' }">
    <div class="w-full max-w-lg">
        <div class="mb-4 inline-flex rounded-lg border border-slate-200 bg-white p-1 dark:border-slate-800 dark:bg-slate-900">
            <button type="button" class="btn btn-sm" :class="mode === 'single' ? 'btn-primary' : 'text-slate-600 dark:text-slate-300'" @click="mode = 'single'"><i class="bi bi-plus"></i>Single</button>
            <button type="button" class="btn btn-sm" :class="mode === 'bulk' ? 'btn-primary' : 'text-slate-600 dark:text-slate-300'" @click="mode = 'bulk'"><i class="bi bi-grid-3x3-gap"></i>Bulk add</button>
        </div>

        <div class="card" x-show="mode === 'single'">
            <div class="card-header"><h5 class="text-base font-semibold text-slate-900 dark:text-white">New Workstation</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('workstations.store') }}">
                    @csrf
                    @include('workstations._form-fields')
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Create</button>
                        <a href="{{ route('workstations.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" x-show="mode === 'bulk'" x-cloak
             x-data="{
                codes: @js($locations->pluck('code', 'id')),
                rooms: @js($roomsByLocation),
                locationId: @js((string) old('location_id', request('location', ''))),
                room: @js(old('room', '')),
                from: @js((int) old('from', 1)),
                to: @js((int) old('to', 20)),
                code(n) {
                    const loc = this.codes[this.locationId];
                    return loc ? '{{ \App\Models\Workstation::COMPANY_PREFIX }}' + loc + this.room.toUpperCase() + '{{ \App\Models\Workstation::MARKER }}' + String(n || 0).padStart(2, '0') : '—';
                }
             }">
            <div class="card-header"><h5 class="text-base font-semibold text-slate-900 dark:text-white">Add a Room's Workstations</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('workstations.store') }}">
                    @csrf
                    <input type="hidden" name="mode" value="bulk">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-3">
                            <label class="field-label">Location <span class="text-red-500">*</span></label>
                            <select name="location_id" class="field-input @error('location_id') is-invalid @enderror" @change="locationId = $event.target.value">
                                <option value="">Select Location</option>
                                @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id', request('location')) == $loc->id ? 'selected' : '' }}>
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
                            <label class="field-label">Room</label>
                            <input type="text" name="room" class="field-input uppercase @error('room') is-invalid @enderror" x-model="room" maxlength="10" placeholder="e.g. RA" list="bulk-workstation-rooms">
                            <datalist id="bulk-workstation-rooms">
                                <template x-for="r in (rooms[locationId] || [])" :key="r"><option :value="r"></option></template>
                            </datalist>
                            @error('room')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">From</label>
                            <input type="number" name="from" class="field-input" x-model.number="from" min="1" max="999" required>
                        </div>
                        <div>
                            <label class="field-label">To</label>
                            <input type="number" name="to" class="field-input @error('to') is-invalid @enderror" x-model.number="to" min="1" max="999" required>
                        </div>
                        @error('to')<p class="field-error sm:col-span-3">{{ $message }}</p>@enderror
                    </div>
                    <p class="field-hint mt-3">
                        Creates <span class="font-semibold" x-text="Math.max(0, to - from + 1)"></span> workstation(s):
                        <code class="text-primary-600 dark:text-primary-400" x-text="code(from)"></code> … <code class="text-primary-600 dark:text-primary-400" x-text="code(to)"></code>.
                        Codes that already exist are skipped.
                    </p>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Create All</button>
                        <a href="{{ route('workstations.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
