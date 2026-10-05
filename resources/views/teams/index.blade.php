@extends('layouts.app')
@section('title', 'Teams')
@section('page-title', 'Teams')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Teams</h5>
    <a href="{{ route('teams.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i>Add Team</a>
</div>
<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr><th>Name</th><th>Type</th><th>Manager</th><th>Employees</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($teams as $team)
                <tr>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">
                        <a href="{{ route('teams.show', $team) }}" class="hover:underline">{{ $team->name }}</a>
                    </td>
                    <td>
                        @if($team->type === 'client_placement')
                        <span class="badge badge-warning"><i class="bi bi-briefcase me-1"></i>{{ $team->client->name ?? 'Client Placement' }}</span>
                        @else
                        <span class="badge badge-secondary">Internal</span>
                        @endif
                    </td>
                    <td class="text-slate-500 dark:text-slate-400">
                        @if($team->manager)
                        <a href="{{ route('employees.show', $team->manager) }}" class="text-primary-600 hover:underline dark:text-primary-400">{{ $team->manager->name }}</a>
                        @else
                        -
                        @endif
                    </td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $team->employees_count }}</td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('teams.show', $team) }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('teams.edit', $team) }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('teams.destroy', $team) }}" onsubmit="return confirm('Delete this team?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 text-center text-slate-500 dark:text-slate-400">No teams yet. <a href="{{ route('teams.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
