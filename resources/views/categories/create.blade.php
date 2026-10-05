@extends('layouts.app')
@section('title', 'Add Category')
@section('page-title', 'Add Category')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-header"><h5 class="text-base font-semibold text-slate-900 dark:text-white">New Category</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('categories.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="field-label">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Description</label>
                        <textarea name="description" class="field-input" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Create</button>
                        <a href="{{ route('categories.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
