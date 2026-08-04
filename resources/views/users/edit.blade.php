@extends('layouts.app')
@php($isSettings = $isSettings ?? false)
@section('title', $isSettings ? 'Settings' : 'Edit User')
@section('page-title', $isSettings ? 'Settings' : 'Edit User')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-2xl">
        <div class="card">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">{{ $user->name }}</h5>
                <a href="{{ $isSettings ? route('dashboard') : route('users.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $isSettings ? route('settings.update') : route('users.update', $user) }}">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="field-label">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" class="field-input @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-center">
                            <label class="mt-4 flex items-center gap-2">
                                <input class="h-4 w-4 rounded border-slate-300 accent-primary-600" type="checkbox" value="1" id="change-password-toggle" name="change_password" {{ old('change_password') ? 'checked' : '' }}>
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Change Password</span>
                            </label>
                        </div>
                        <div>
                            <label class="field-label">New Password</label>
                            <input type="password" name="password" id="new-password-input" class="field-input" {{ old('change_password') ? '' : 'disabled' }}>
                        </div>
                        <div>
                            <label class="field-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="confirm-password-input" class="field-input" {{ old('change_password') ? '' : 'disabled' }}>
                        </div>
                        @if(!$isSettings)
                        <div>
                            <label class="field-label">Role <span class="text-red-500">*</span></label>
                            <select name="role" class="field-input" required>
                                <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Department</label>
                            <select name="department" class="field-input">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->name }}" {{ old('department', $user->department) == $department->name ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div>
                            <label class="field-label">Phone</label>
                            <input type="text" name="phone" class="field-input" value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save Changes</button>
                        <a href="{{ $isSettings ? route('dashboard') : route('users.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('change-password-toggle');
        const passwordInput = document.getElementById('new-password-input');
        const confirmInput = document.getElementById('confirm-password-input');

        if (!toggle || !passwordInput || !confirmInput) {
            return;
        }

        const syncPasswordFields = function () {
            const enabled = toggle.checked;
            passwordInput.disabled = !enabled;
            confirmInput.disabled = !enabled;

            if (!enabled) {
                passwordInput.value = '';
                confirmInput.value = '';
            }
        };

        toggle.addEventListener('change', syncPasswordFields);
        syncPasswordFields();
    });
</script>
@endpush
