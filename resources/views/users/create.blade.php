@extends('layouts.app')
@section('title', 'Add User')
@section('page-title', 'Add User')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-2xl">
        <div class="card">
            <div class="card-header"><h5 class="text-base font-semibold text-slate-900 dark:text-white">New User</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="sm:col-span-1 lg:col-span-1">
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
                            <label class="field-label">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" class="field-input @error('password') is-invalid @enderror" required>
                            @error('password')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Confirm Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password_confirmation" class="field-input" required>
                        </div>
                        <div>
                            <label class="field-label">Role <span class="text-red-500">*</span></label>
                            <select name="role" class="field-input" required>
                                <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
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
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Create User</button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
