@php $children = $byManager->get($employee->id, collect()); @endphp
<li>
    <a href="{{ route('employees.show', $employee) }}" class="org-node no-underline">
        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600">
            <span class="text-xs font-bold text-white">{{ substr($employee->name, 0, 1) }}</span>
        </div>
        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $employee->name }}</span>
        @if($employee->role)
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $employee->role->name }}</span>
        @endif
        @if($employee->team)
        <span class="text-xs text-slate-400 dark:text-slate-500">{{ $employee->team->name }}</span>
        @endif
        @if($employee->is_manager)
        <span class="badge badge-secondary mt-1"><i class="bi bi-diagram-3 me-1"></i>Manager</span>
        @endif
    </a>
    @if($children->isNotEmpty())
    <ul>
        @foreach($children as $child)
            @include('employees._org-chart-node', ['employee' => $child, 'byManager' => $byManager])
        @endforeach
    </ul>
    @endif
</li>
