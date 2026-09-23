@extends('layouts.app')
@section('title', 'Roles')
@section('page-title', 'Roles')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Roles</h5>
    <a href="{{ route('roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i>Add Role</a>
</div>

<!-- Filters -->
<div class="card mb-6" x-data="{ advanced: {{ request()->hasAny(['type', 'employees']) ? 'true' : 'false' }} }">
    <div class="card-body">
        <form method="GET" action="{{ route('roles.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-10">
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" class="field-input pl-9" value="{{ request('search') }}" placeholder="Search role name...">
                </div>
            </div>
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('roles.index') }}" class="btn btn-outline">Clear</a>
            </div>

            {{-- Advanced Filter toggle --}}
            <div class="md:col-span-12">
                <button type="button" class="btn btn-sm btn-outline" @click="advanced = !advanced">
                    <i class="bi bi-sliders"></i> Advanced Filter
                    @if(request()->hasAny(['type', 'employees']))
                    <span class="badge badge-primary">active</span>
                    @endif
                </button>
            </div>

            {{-- Advanced Filter panel --}}
            <div class="md:col-span-12" x-show="advanced" x-collapse x-cloak>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="field-label">Type</label>
                            <select name="type" class="field-input">
                                <option value="">All Types</option>
                                <option value="top_level" {{ request('type') === 'top_level' ? 'selected' : '' }}>Top level (e.g. CEO)</option>
                                <option value="regular" {{ request('type') === 'regular' ? 'selected' : '' }}>Regular</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Employees</label>
                            <select name="employees" class="field-input">
                                <option value="">All</option>
                                <option value="assigned" {{ request('employees') === 'assigned' ? 'selected' : '' }}>Has employees</option>
                                <option value="unassigned" {{ request('employees') === 'unassigned' ? 'selected' : '' }}>No employees</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr><th>Name</th><th>Employees</th><th></th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $role->name }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $role->employees_count }}</td>
                    <td>
                        @if($role->is_top_level)
                        <span class="badge badge-warning"><i class="bi bi-star-fill me-1"></i>Top level</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">No roles yet. <a href="{{ route('roles.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
