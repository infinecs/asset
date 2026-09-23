@extends('layouts.app')
@section('title', $team->name)
@section('page-title', 'Team')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <div class="mb-1 flex items-center gap-2">
            <h5 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $team->name }}</h5>
            @if($team->type === 'client_placement')
            <span class="badge badge-warning"><i class="bi bi-briefcase me-1"></i>{{ $team->client->name ?? 'Client Placement' }}</span>
            @else
            <span class="badge badge-secondary">Internal</span>
            @endif
        </div>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">{{ $employees->count() + ($team->manager ? 1 : 0) }} member(s)</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('teams.edit', $team) }}" class="btn btn-outline btn-sm"><i class="bi bi-pencil"></i>Edit</a>
        <a href="{{ route('teams.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
    <div class="lg:col-span-4">
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-person-arms-up me-2 text-slate-400"></i>Manager</h6>
            </div>
            <div class="card-body">
                @if($team->manager)
                <a href="{{ route('employees.show', $team->manager) }}" class="flex items-center gap-3 no-underline">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-600">
                        <span class="text-xs font-bold text-white">{{ substr($team->manager->name, 0, 1) }}</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $team->manager->name }}</span>
                </a>
                @else
                <span class="text-sm text-slate-500 dark:text-slate-400">No manager assigned.</span>
                @endif
            </div>
        </div>
    </div>

    <div class="lg:col-span-8">
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-people me-2 text-slate-400"></i>Team Members</h6>
            </div>
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID Number</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($team->manager)
                        <tr class="bg-primary-50/50 dark:bg-primary-900/10">
                            <td class="font-semibold text-slate-800 dark:text-slate-100">
                                {{ $team->manager->name }}
                                <span class="badge badge-secondary ms-1"><i class="bi bi-star-fill me-1"></i>Manager</span>
                            </td>
                            <td><code class="text-primary-600 dark:text-primary-400">{{ $team->manager->id_number }}</code></td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $team->manager->role->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $team->manager->status_badge }}">{{ $team->manager->status_label }}</span></td>
                            <td class="text-right"><a href="{{ route('employees.show', $team->manager) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @endif
                        @forelse($employees as $employee)
                        <tr>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $employee->name }}</td>
                            <td><code class="text-primary-600 dark:text-primary-400">{{ $employee->id_number }}</code></td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $employee->role->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $employee->status_badge }}">{{ $employee->status_label }}</span></td>
                            <td class="text-right"><a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @empty
                        @if(!$team->manager)
                        <tr><td colspan="5" class="py-8 text-center text-slate-500 dark:text-slate-400">No members in this team yet.</td></tr>
                        @endif
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-laptop me-2 text-slate-400"></i>Team Assets ({{ $assets->count() }})</h6>
    </div>
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Asset Tag</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                <tr>
                    <td><code class="text-primary-600 dark:text-primary-400">{{ $asset->asset_tag ?? '-' }}</code></td>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->name }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $asset->category?->name ?? '-' }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $asset->assignedEmployee?->name ?? '-' }}</td>
                    <td><span class="badge badge-{{ $asset->status_badge }}">{{ $asset->status_label }}</span></td>
                    <td class="text-right"><a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-8 text-center text-slate-500 dark:text-slate-400">No assets assigned to this team's members yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
