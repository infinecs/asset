@extends('layouts.app')
@php($isSettings = $isSettings ?? false)
@section('title', $isSettings ? 'Settings' : 'Edit User')
@section('page-title', $isSettings ? 'Settings' : 'Edit User')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-semibold mb-0">{{ $user->name }}</h5>
                <a href="{{ $isSettings ? route('dashboard') : route('users.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ $isSettings ? route('settings.update') : route('users.update', $user) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" value="1" id="change-password-toggle" name="change_password" {{ old('change_password') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="change-password-toggle">
                                    Change Password
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New Password</label>
                            <input type="password" name="password" id="new-password-input" class="form-control" {{ old('change_password') ? '' : 'disabled' }}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="confirm-password-input" class="form-control" {{ old('change_password') ? '' : 'disabled' }}>
                        </div>
                        @if(!$isSettings)
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                        @endif
                        @if(!$isSettings)
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Department</label>
                            <select name="department" class="form-select">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->name }}" {{ old('department', $user->department) == $department->name ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-{{ $isSettings ? '12' : '4' }}">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
                        <a href="{{ $isSettings ? route('dashboard') : route('users.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
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
