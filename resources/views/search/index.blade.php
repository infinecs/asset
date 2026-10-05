@extends('layouts.app')
@section('title', 'Search')
@section('page-title', 'Search')
@section('content')
<div class="mb-6">
    <form method="GET" action="{{ route('search.index') }}" class="relative max-w-xl">
        <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="text" name="q" value="{{ $query }}" class="field-input pl-9" placeholder="Search employees, assets, teams, roles..." autofocus>
    </form>
</div>

@if($query === '')
<div class="py-16 text-center text-slate-500 dark:text-slate-400">
    <i class="bi bi-search mb-2 block text-3xl"></i>
    Type something above to search across employees, assets, teams, and roles.
</div>
@else
    @php $totalResults = $results['employees']->count() + $results['assets']->count() + $results['teams']->count() + $results['roles']->count(); @endphp

    @if($totalResults === 0)
    <div class="py-16 text-center text-slate-500 dark:text-slate-400">
        <i class="bi bi-emoji-frown mb-2 block text-3xl"></i>
        No results for "{{ $query }}".
    </div>
    @else
    <div class="space-y-6">
        @if($results['employees']->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-people me-2 text-slate-400"></i>Employees ({{ $results['employees']->count() }})</h6>
            </div>
            <div>
                @foreach($results['employees'] as $employee)
                <a href="{{ route('employees.show', $employee) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-3 last:border-b-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600">
                            <span class="text-xs font-bold text-white">{{ substr($employee->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $employee->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $employee->id_number }} &middot; {{ $employee->email }}</div>
                        </div>
                    </div>
                    @if($employee->role)
                    <span class="badge badge-primary">{{ $employee->role->name }}</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($results['assets']->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-laptop me-2 text-slate-400"></i>Assets ({{ $results['assets']->count() }})</h6>
            </div>
            <div>
                @foreach($results['assets'] as $asset)
                <a href="{{ route('assets.show', $asset) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-3 last:border-b-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                    <div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $asset->name }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400"><code class="text-primary-600 dark:text-primary-400">{{ $asset->asset_tag }}</code> &middot; {{ $asset->category?->name ?? '-' }}</div>
                    </div>
                    <span class="badge badge-{{ $asset->status_badge }}">{{ $asset->status_label }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($results['teams']->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-diagram-3 me-2 text-slate-400"></i>Teams ({{ $results['teams']->count() }})</h6>
            </div>
            <div>
                @foreach($results['teams'] as $team)
                <a href="{{ route('teams.show', $team) }}" class="flex items-center gap-3 border-b border-slate-100 px-6 py-3 last:border-b-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $team->name }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($results['roles']->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-person-badge me-2 text-slate-400"></i>Roles ({{ $results['roles']->count() }})</h6>
            </div>
            <div>
                @foreach($results['roles'] as $role)
                <a href="{{ route('roles.edit', $role) }}" class="flex items-center gap-3 border-b border-slate-100 px-6 py-3 last:border-b-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $role->name }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif
@endif
@endsection
