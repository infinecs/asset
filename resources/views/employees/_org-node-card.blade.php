{{-- One person's box on an org chart. Expects $person; optional $highlight, $showTeam, $chartLink, $hiddenCount. --}}
<div class="relative">
    <a href="{{ route('employees.show', $person) }}" class="org-node no-underline {{ ($highlight ?? false) ? 'org-node-current' : '' }}">
        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600">
            <span class="text-xs font-bold text-white">{{ substr($person->name, 0, 1) }}</span>
        </div>
        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $person->name }}</span>
        @if($person->role)
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $person->role->name }}</span>
        @endif
        @if(($showTeam ?? true) && $person->team)
        <span class="text-xs text-slate-400 dark:text-slate-500">{{ $person->team->name }}</span>
        @endif
        @if($person->is_manager)
        <span class="badge badge-secondary mt-1"><i class="bi bi-diagram-3 me-1"></i>Manager</span>
        @endif
        @if(!empty($hiddenCount))
        <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500">+{{ $hiddenCount }} more below</span>
        @endif
    </a>
    @if($chartLink ?? false)
    <a href="{{ route('employees.individual-org-chart', $person) }}" class="org-chart-link" title="Open {{ $person->name }}'s org chart"><i class="bi bi-diagram-3"></i></a>
    @endif
</div>
