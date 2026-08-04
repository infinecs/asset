@extends('layouts.app')

@section('title', 'Add Directory Contact')
@section('page-title', 'Add Directory Contact')

@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-3xl">
        <div class="card">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">New Directory Contact</h5>
                <a href="{{ route('directory.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('directory-contacts.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" class="field-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Department</label>
                            <select name="department" class="field-input">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->name }}" {{ old('department') == $department->name ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Phone</label>
                            <input type="text" name="phone" class="field-input" value="{{ old('phone') }}">
                        </div>
                        <div>
                            <label class="field-label">Location</label>
                            <select name="location_id" class="field-input">
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Create Contact</button>
                        <a href="{{ route('directory.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
