@extends('layouts.app')
@section('title', 'Users')
@section('page-title', 'User Management')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Users</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Manage and track all users</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i>Add User</a>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-4">
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" class="field-input pl-9" value="{{ request('search') }}" placeholder="Search name, email, phone...">
                </div>
            </div>
            <div class="md:col-span-2">
                <select name="role" class="field-input">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="department" class="field-input">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                    <option value="{{ $department }}" {{ request('department') === $department ? 'selected' : '' }}>{{ $department }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 md:col-span-4">
                <button type="submit" class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Phone</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600">
                                <span class="text-xs font-bold text-white">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $user->name }}</span>
                            @if($user->id === auth()->id())<span class="badge badge-secondary">You</span>@endif
                        </div>
                    </td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $user->email }}</td>
                    <td>
                        <span class="badge badge-{{ $user->role == 'admin' ? 'danger' : 'warning' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $user->department ?? '-' }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $user->phone ?? '-' }}</td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-8 text-center text-slate-500 dark:text-slate-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
