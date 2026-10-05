@php
    $children = $byManager->get($employee->id, collect());
    // A manager can lead several teams: when their reports span more than one team, insert a
    // team node between the manager and each team's members. Reports without a team (e.g.
    // sub-managers) still hang directly off the manager.
    $teamGroups = $children->whereNotNull('team_id')->groupBy('team_id')->sortBy(fn ($members) => $members->first()->team->name);
    $groupByTeam = $teamGroups->count() > 1;
    $ungrouped = $groupByTeam ? $children->whereNull('team_id') : $children;
    // $depth counts the levels already drawn below the top of the chart; $maxDepth (null = all)
    // stops the tree there and shows how many people are hidden instead.
    $depth = $depth ?? 0;
    $expand = $children->isNotEmpty() && (empty($maxDepth) || $depth < $maxDepth);
    $isCurrent = ($highlightId ?? null) === $employee->id;
@endphp
<li>
    @include('employees._org-node-card', [
        'person' => $employee,
        'highlight' => $isCurrent,
        'showTeam' => !$groupByTeam,
        'chartLink' => $children->isNotEmpty() && !$isCurrent,
        'hiddenCount' => $expand ? 0 : $children->count(),
    ])
    @if($expand)
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
                        @include('employees._org-chart-node', ['employee' => $child, 'byManager' => $byManager, 'depth' => $depth + 1])
                    @endforeach
                </ul>
            </li>
            @endforeach
        @endif
        @foreach($ungrouped as $child)
            @include('employees._org-chart-node', ['employee' => $child, 'byManager' => $byManager, 'depth' => $depth + 1])
        @endforeach
    </ul>
    @endif
</li>
