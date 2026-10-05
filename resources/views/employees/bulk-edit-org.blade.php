@extends('layouts.app')
@section('title', 'Bulk Edit Org Structure')
@section('page-title', 'Bulk Edit Org Structure')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Bulk Edit Org Structure</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Assign Role, Team, and Manager for many employees at once — only changed rows are saved.</p>
    </div>
    <a href="{{ route('employees.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back to Employees</a>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('employees.bulk-edit-org') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" class="field-input pl-9" value="{{ request('search') }}" placeholder="Search name or ID number...">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="unassigned" value="1" {{ request('unassigned') === '1' ? 'checked' : '' }} onchange="this.form.submit()">
                No manager only
            </label>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ route('employees.bulk-edit-org') }}" class="btn btn-outline">Clear</a>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('employees.update-org') }}">
    @csrf
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th class="w-[180px]">Role</th>
                        <th class="w-[180px]">Team</th>
                        <th class="w-[180px]">Manager</th>
                        <th class="w-8 text-center">Manager?</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr x-data="{ isManager: {{ $employee->is_manager ? 'true' : 'false' }} }" class="{{ !$employee->manager_id && !$employee->is_manager ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                        <td><code class="text-primary-600 dark:text-primary-400">{{ $employee->id_number }}</code></td>
                        <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $employee->name }}</td>
                        <td>
                            <select name="rows[{{ $employee->id }}][role_id]" class="field-input">
                                <option value="">—</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ $employee->role_id === $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td x-show="!isManager">
                            <select name="rows[{{ $employee->id }}][team_id]" class="field-input">
                                <option value="">—</option>
                                @foreach($teams as $team)
                                <option value="{{ $team->id }}" {{ $employee->team_id === $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td x-show="isManager" x-cloak class="text-xs text-slate-400">Set via Teams Managed</td>
                        <td>
                            <select name="rows[{{ $employee->id }}][manager_id]" class="field-input">
                                <option value="">— No Manager —</option>
                                @foreach($managers as $manager)
                                @continue($manager->id === $employee->id)
                                <option value="{{ $manager->id }}" {{ $employee->manager_id === $manager->id ? 'selected' : '' }}>{{ $manager->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="rows[{{ $employee->id }}][is_manager]" value="1" x-model="isManager" class="h-4 w-4 rounded border-slate-300 accent-primary-600">
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-500 dark:text-slate-400">No employees found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
        <div class="card-footer">
            {{ $employees->links() }}
        </div>
        @endif
    </div>

    @if($employees->isNotEmpty())
    <div class="mt-6 flex items-center gap-2">
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save This Page</button>
        <span class="text-sm text-slate-500 dark:text-slate-400">Saves only the {{ $employees->count() }} row(s) shown above. Change pages to edit more.</span>
    </div>
    @endif
</form>
@endsection
