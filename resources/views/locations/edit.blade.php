@extends('layouts.app')
@section('title', 'Edit Location')
@section('page-title', 'Edit Location')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">Edit: {{ $location->name }}</h5>
                <a href="{{ route('locations.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('locations.update', $location) }}">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="field-label">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" class="field-input" value="{{ old('name', $location->name) }}" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Workstation Code</label>
                            <input type="text" name="code" class="field-input uppercase @error('code') is-invalid @enderror" value="{{ old('code', $location->code) }}" maxlength="20" placeholder="e.g. P1A">
                            @error('code')<p class="field-error">{{ $message }}</p>@enderror
                            <p class="field-hint mt-1">
                                Site + block, used in workstation codes: P1A → INF<strong>P1A</strong>RAWS01.
                                @if($location->workstations()->exists())
                                Changing it renames this location's {{ $location->workstations()->count() }} workstation(s); past movement records keep the old name.
                                @endif
                            </p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Building</label>
                            <input type="text" name="building" class="field-input" value="{{ old('building', $location->building) }}">
                        </div>
                        <div>
                            <label class="field-label">Floor</label>
                            <input type="text" name="floor" class="field-input" value="{{ old('floor', $location->floor) }}">
                        </div>
                        <div>
                            <label class="field-label">Room</label>
                            <input type="text" name="room" class="field-input" value="{{ old('room', $location->room) }}">
                        </div>
                        <div>
                            <label class="field-label">Postcode</label>
                            <input type="text" name="postcode" class="field-input" value="{{ old('postcode', $location->postcode) }}">
                        </div>
                        <div>
                            <label class="field-label">City</label>
                            <input type="text" name="city" class="field-input" value="{{ old('city', $location->city) }}">
                        </div>
                        <div>
                            <label class="field-label">State</label>
                            <input type="text" name="state" class="field-input" value="{{ old('state', $location->state) }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Description</label>
                            <textarea name="description" class="field-input" rows="2">{{ old('description', $location->description) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save</button>
                        <a href="{{ route('locations.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
