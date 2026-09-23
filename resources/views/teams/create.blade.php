@extends('layouts.app')
@section('title', 'Add Team')
@section('page-title', 'Add Team')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-header"><h5 class="text-base font-semibold text-slate-900 dark:text-white">New Team</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('teams.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="field-label">Team Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. HR Team, IT & Facility Team" required>
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Manager</label>
                        <select name="manager_id" class="field-input @error('manager_id') is-invalid @enderror">
                            <option value="">— No Manager —</option>
                            @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ (string) old('manager_id') === (string) $manager->id ? 'selected' : '' }}>{{ $manager->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-hint">A manager can be assigned to lead more than one team.</p>
                        @error('manager_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Create</button>
                        <a href="{{ route('teams.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
