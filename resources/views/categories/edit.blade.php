@extends('layouts.app')
@section('title', 'Edit Category')
@section('page-title', 'Edit Category')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">Edit: {{ $category->name }}</h5>
                <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('categories.update', $category) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="field-label">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Description</label>
                        <textarea name="description" class="field-input" rows="3">{{ old('description', $category->description) }}</textarea>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save</button>
                        <a href="{{ route('categories.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
