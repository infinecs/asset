@extends('layouts.app')
@section('title', 'Add Client')
@section('page-title', 'Add Client')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-lg">
        <div class="card">
            <div class="card-header"><h5 class="text-base font-semibold text-slate-900 dark:text-white">New Client</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('clients.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="field-label">Client Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Intel" required>
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Create</button>
                        <a href="{{ route('clients.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
