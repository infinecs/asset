@extends('layouts.app')
@section('title', 'Edit Team')
@section('page-title', 'Edit Team')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">Edit: {{ $team->name }}</h5>
                <a href="{{ route('teams.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('teams.update', $team) }}" x-data="{ type: '{{ old('type', $team->type) }}' }">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="field-label">Team Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name', $team->name) }}" required>
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Team Type <span class="text-red-500">*</span></label>
                        <select name="type" x-model="type" class="field-input @error('type') is-invalid @enderror">
                            <option value="internal" {{ old('type', $team->type) === 'internal' ? 'selected' : '' }}>Internal</option>
                            <option value="client_placement" {{ old('type', $team->type) === 'client_placement' ? 'selected' : '' }}>Client Placement</option>
                        </select>
                        <p class="field-hint">Client Placement is for contingent workers placed at an external client (e.g. Intel).</p>
                        @error('type')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-3" x-show="type === 'client_placement'" x-cloak>
                        <label class="field-label">Client <span class="text-red-500">*</span></label>
                        <select name="client_id" class="field-input @error('client_id') is-invalid @enderror">
                            <option value="">— Select Client —</option>
                            @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ (string) old('client_id', $team->client_id) === (string) $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-hint">No client yet? <a href="{{ route('clients.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a>.</p>
                        @error('client_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Manager</label>
                        <select name="manager_id" class="field-input @error('manager_id') is-invalid @enderror">
                            <option value="">— No Manager —</option>
                            @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" {{ (string) old('manager_id', $team->manager_id) === (string) $manager->id ? 'selected' : '' }}>{{ $manager->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-hint">A manager can be assigned to lead more than one team.</p>
                        @error('manager_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save</button>
                        <a href="{{ route('teams.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
