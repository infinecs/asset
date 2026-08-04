@extends('layouts.app')
@section('title', 'Edit Employee')
@section('page-title', 'Edit Employee')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Edit Employee</h5>
    <a href="{{ route('employees.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
</div>

<div class="card max-w-[600px]">
    <div class="card-body">
        <form method="POST" action="{{ route('employees.update', $employee) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="field-label">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="field-input @error('name') is-invalid @enderror"
                       value="{{ old('name', $employee->name) }}" required>
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-3">
                <label class="field-label">ID Number <span class="text-red-500">*</span></label>
                <div class="flex">
                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-100 px-3 text-sm font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">INF</span>
                    <input type="text" name="id_number_suffix"
                           class="field-input rounded-l-none @error('id_number') is-invalid @enderror"
                           value="{{ old('id_number_suffix', Str::after($employee->id_number, 'INF')) }}"
                           placeholder="0161" required>
                </div>
                @error('id_number')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-3">
                <label class="field-label">Work Location</label>
                <select name="work_location" class="field-input @error('work_location') is-invalid @enderror">
                    <option value="">— Select Location —</option>
                    @foreach($locations as $location)
                    <option value="{{ $location->name }}"
                        {{ old('work_location', $employee->work_location) === $location->name ? 'selected' : '' }}>
                        {{ $location->name }}
                    </option>
                    @endforeach
                </select>
                @error('work_location')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-3">
                <label class="field-label">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" class="field-input @error('email') is-invalid @enderror"
                       value="{{ old('email', $employee->email) }}" required>
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label class="field-label">Status <span class="text-red-500">*</span></label>
                <select name="status" class="field-input @error('status') is-invalid @enderror" required>
                    <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="retired" {{ old('status', $employee->status) === 'retired' ? 'selected' : '' }}>Retired</option>
                </select>
                @error('status')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('employees.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
