@php
    $children = $byManager->get($employee->id, collect());
    // A manager can lead several teams: when their reports span more than one team, insert a
    // team node between the manager and each team's members. Reports without a team (e.g.
    // sub-managers) still hang directly off the manager.
    $teamGroups = $children->whereNotNull('team_id')->groupBy('team_id')->sortBy(fn ($members) => $members->first()->team->name);
    $groupByTeam = $teamGroups->count() > 1;
    $ungrouped = $groupByTeam ? $children->whereNull('team_id') : $children;
@endphp
<li>
    <a href="{{ route('employees.show', $employee) }}" class="org-node no-underline {{ ($highlightId ?? null) === $employee->id ? 'org-node-current' : '' }}">
        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600">
            <span class="text-xs font-bold text-white">{{ substr($employee->name, 0, 1) }}</span>
        </div>
        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $employee->name }}</span>
        @if($employee->role)
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $employee->role->name }}</span>
        @endif
        @if($employee->team && !$groupByTeam)
        <span class="text-xs text-slate-400 dark:text-slate-500">{{ $employee->team->name }}</span>
        @endif
        @if($employee->is_manager)
        <span class="badge badge-secondary mt-1"><i class="bi bi-diagram-3 me-1"></i>Manager</span>
        @endif
    </a>
    @if($children->isNotEmpty())
    <ul>
        @if($groupByTeam)
            @foreach($teamGroups as $members)
            @php $team = $members->first()->team; @endphp
            <li>
                <div class="org-node org-team-node">
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200"><i class="bi bi-people me-1 text-slate-400"></i>{{ $team->name }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $team->type_label }} · {{ $members->count() }} {{ Str::plural('member', $members->count()) }}</span>
                </div>
                <ul>
                    @foreach($members as $child)
                        @include('employees._org-chart-node', ['employee' => $child, 'byManager' => $byManager])
                    @endforeach
                </ul>
            </li>
            @endforeach
        @endif
        @foreach($ungrouped as $child)
            @include('employees._org-chart-node', ['employee' => $child, 'byManager' => $byManager])
        @endforeach
    </ul>
    @endif
</li>
