@extends('layouts.app')
@section('title', 'Edit Role')
@section('page-title', 'Edit Role')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">Edit: {{ $role->name }}</h5>
                <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('roles.update', $role) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="field-label">Role Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <input type="checkbox" name="is_top_level" value="1" class="h-4 w-4 rounded border-slate-300 accent-primary-600" {{ old('is_top_level', $role->is_top_level) ? 'checked' : '' }}>
                            This role supersedes all managers (e.g. CEO)
                        </label>
                        <p class="field-hint">Employees with this role always appear at the very top of the org chart, above every other manager.</p>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save</button>
                        <a href="{{ route('roles.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
