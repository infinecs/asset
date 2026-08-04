@extends('layouts.app')
@section('title', 'Edit Department')
@section('page-title', 'Edit Department')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">Edit: {{ $department->name }}</h5>
                <a href="{{ route('departments.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('departments.update', $department) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="field-label">Department Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name', $department->name) }}" required>
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save</button>
                        <a href="{{ route('departments.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
