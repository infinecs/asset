@extends('layouts.app')
@section('title', 'Add Employee')
@section('page-title', 'Add Employee')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Add Employee</h5>
    <a href="{{ route('employees.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('employees.store') }}" x-data="{ isManager: {{ old('is_manager') ? 'true' : 'false' }} }">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="field-label">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="field-input @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required>
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="field-label">ID Number <span class="text-red-500">*</span></label>
                    <div class="flex">
                        <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-100 px-3 text-sm font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">INF</span>
                        <input type="text" name="id_number_suffix"
                               class="field-input rounded-l-none @error('id_number') is-invalid @enderror"
                               value="{{ old('id_number_suffix') }}" placeholder="0161" required>
                    </div>
                    @error('id_number')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="field-label">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="field-input @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required>
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="field-label">Work Location</label>
                    <select name="work_location" class="field-input @error('work_location') is-invalid @enderror">
                        <option value="">— Select Location —</option>
                        @foreach($locations as $location)
                        <option value="{{ $location->name }}" {{ old('work_location') === $location->name ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('work_location')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="field-label">Role</label>
                    <select name="role_id" class="field-input @error('role_id') is-invalid @enderror">
                        <option value="">— Select Role —</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="field-label">Manager</label>
                    <select name="manager_id" class="field-input @error('manager_id') is-invalid @enderror">
                        <option value="">— No Manager —</option>
                        @foreach($managers as $manager)
                        <option value="{{ $manager->id }}" {{ (string) old('manager_id') === (string) $manager->id ? 'selected' : '' }}>{{ $manager->name }}</option>
                        @endforeach
                    </select>
                    @error('manager_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="field-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="field-input @error('date_of_birth') is-invalid @enderror"
                           value="{{ old('date_of_birth') }}">
                    <p class="field-hint">Used to schedule birthday gift cards.</p>
                    @error('date_of_birth')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="field-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" class="field-input @error('status') is-invalid @enderror" required>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="resigned" {{ old('status') === 'resigned' ? 'selected' : '' }}>Resigned</option>
                    </select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="gift_card_opt_out" value="1" class="h-4 w-4 rounded border-slate-300 accent-primary-600" {{ old('gift_card_opt_out') ? 'checked' : '' }}>
                        Opt out of the birthday gift card program
                    </label>
                </div>
            </div>

            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="is_manager" value="1" x-model="isManager" class="h-4 w-4 rounded border-slate-300 accent-primary-600" {{ old('is_manager') ? 'checked' : '' }}>
                    This employee is a manager
                </label>
                <p class="field-hint">Enable to choose which employees report to them, and which teams they lead.</p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2" x-show="isManager" x-cloak>
                <div class="card !shadow-none">
                    <div class="card-header">
                        <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-diagram-3 me-2 text-slate-400"></i>Subordinates</h6>
                    </div>
                    <div class="card-body">
                        <select name="subordinate_ids[]" multiple class="field-input @error('subordinate_ids') is-invalid @enderror">
                            @foreach($employees as $person)
                            <option value="{{ $person->id }}" {{ in_array($person->id, old('subordinate_ids', [])) ? 'selected' : '' }}>{{ $person->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-hint">Selected employees will report to this manager.</p>
                        @error('subordinate_ids')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="card !shadow-none">
                    <div class="card-header">
                        <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-people me-2 text-slate-400"></i>Teams Managed</h6>
                    </div>
                    <div class="card-body">
                        <select name="managed_team_ids[]" multiple class="field-input @error('managed_team_ids') is-invalid @enderror">
                            @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ in_array($team->id, old('managed_team_ids', [])) ? 'selected' : '' }}>{{ $team->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-hint">A manager can lead more than one team — select all that apply.</p>
                        @error('managed_team_ids')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn btn-primary">Add Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
