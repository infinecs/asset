@extends('layouts.app')
@section('title', 'People Calendar - ' . $month->format('F Y'))
@section('page-title', 'People Calendar')
@section('content')
@php
    use App\Http\Controllers\PeopleCalendarController;

    $types = PeopleCalendarController::TYPES;
    $filters = array_filter(request()->only(['team', 'manager']));
    $prev = route('employees.calendar', $filters + ['month' => $month->copy()->subMonth()->format('Y-m')]);
    $next = route('employees.calendar', $filters + ['month' => $month->copy()->addMonth()->format('Y-m')]);
    $isThisMonth = $month->isSameMonth(now());
@endphp
<style>
    /* Event-type colours: categorical slots 1-4 in fixed order, light and dark steps. Text never wears them; they mark a dot beside it. */
    .people-cal { --ev-birthday: #2a78d6; --ev-anniversary: #eb6834; --ev-joining: #1baf7a; --ev-leaving: #eda100; }
    .dark .people-cal { --ev-birthday: #3987e5; --ev-anniversary: #d95926; --ev-joining: #199e70; --ev-leaving: #c98500; }
    .people-cal .ev-dot { display: inline-block; width: 8px; height: 8px; border-radius: 9999px; flex-shrink: 0; }
    @foreach(array_keys($types) as $type)
    .people-cal .ev-{{ $type }} .ev-dot, .people-cal .ev-dot.ev-{{ $type }} { background: var(--ev-{{ $type }}); }
    @endforeach
</style>

<div class="people-cal" x-data="{ show: { birthday: true, anniversary: true, joining: true, leaving: true } }">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h5 class="text-lg font-semibold text-slate-900 dark:text-white">People Calendar</h5>
            <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Birthdays, work anniversaries, people joining and last days</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('employees.headcount') }}" class="btn btn-outline"><i class="bi bi-graph-up"></i>Headcount</a>
            <a href="{{ route('employees.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i>Employees</a>
        </div>
    </div>

    {{-- Filters: one row above the calendar --}}
    <div class="card mb-4">
        <div class="card-body flex flex-wrap items-center gap-3">
            <div class="inline-flex items-center gap-1">
                <a href="{{ $prev }}" class="btn btn-outline btn-sm btn-icon" title="Previous month" aria-label="Previous month"><i class="bi bi-chevron-left"></i></a>
                <h6 class="w-40 text-center text-base font-semibold text-slate-800 dark:text-slate-100">{{ $month->format('F Y') }}</h6>
                <a href="{{ $next }}" class="btn btn-outline btn-sm btn-icon" title="Next month" aria-label="Next month"><i class="bi bi-chevron-right"></i></a>
                @unless($isThisMonth)
                <a href="{{ route('employees.calendar', $filters) }}" class="btn btn-outline btn-sm ms-1">Today</a>
                @endunless
            </div>
            <form method="GET" class="ms-auto flex flex-wrap items-center gap-2">
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <label for="cal-team" class="sr-only">Team</label>
                <select id="cal-team" name="team" class="field-input w-auto" onchange="this.form.submit()">
                    <option value="">All teams</option>
                    @foreach($teams as $team)
                    <option value="{{ $team->id }}" @selected(request('team') == $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
                <label for="cal-manager" class="sr-only">Reports to</label>
                <select id="cal-manager" name="manager" class="field-input w-auto" onchange="this.form.submit()">
                    <option value="">Anyone's reports</option>
                    @foreach($managers as $manager)
                    <option value="{{ $manager->id }}" @selected(request('manager') == $manager->id)>Reports to {{ $manager->name }}</option>
                    @endforeach
                </select>
                <noscript><button class="btn btn-primary btn-sm">Filter</button></noscript>
            </form>
        </div>
        {{-- Legend, doubling as show/hide toggles --}}
        <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 px-5 py-2.5 text-xs dark:border-slate-800" role="group" aria-label="Show event types">
            @foreach($types as $type => $meta)
            <button type="button" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 transition"
                    :class="show.{{ $type }} ? 'border-slate-300 bg-white text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200' : 'border-dashed border-slate-300 text-slate-400 dark:border-slate-700'"
                    :aria-pressed="show.{{ $type }}" @click="show.{{ $type }} = !show.{{ $type }}">
                <span class="ev-dot ev-{{ $type }}" :class="show.{{ $type }} ? '' : 'opacity-30'"></span>
                <i class="bi bi-{{ $meta['icon'] }}"></i>{{ $meta['label'] }}
                <span class="tabular-nums text-slate-500 dark:text-slate-400">{{ $counts[$type] }}</span>
            </button>
            @endforeach
            <span class="ms-auto text-slate-500 dark:text-slate-400"><i class="bi bi-star-fill me-1 text-slate-400"></i>Milestone anniversary (1, 3, 5, 10 years…)</span>
        </div>
    </div>

    {{-- Month grid (wide screens) --}}
    <div class="card mb-4 hidden overflow-hidden md:block">
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
            <div class="py-2">{{ $weekday }}</div>
            @endforeach
        </div>
        @foreach($weeks as $week)
        <div class="grid grid-cols-7 border-b border-slate-100 last:border-b-0 dark:border-slate-800">
            @foreach($week as $day)
            @php
                $inMonth = $day->isSameMonth($month);
                $dayEvents = $byDay->get($day->format('Y-m-d'), collect());
            @endphp
            <div class="min-h-28 border-e border-slate-100 p-1.5 last:border-e-0 dark:border-slate-800 {{ $inMonth ? '' : 'bg-slate-50/60 dark:bg-slate-950/40' }} {{ $day->isWeekend() && $inMonth ? 'bg-slate-50/40 dark:bg-slate-900/60' : '' }}">
                <div class="mb-1 flex justify-end">
                    <span class="flex h-6 min-w-6 items-center justify-center rounded-full px-1 text-xs tabular-nums
                        {{ $day->isToday() ? 'bg-primary-600 font-semibold text-white' : ($inMonth ? 'text-slate-600 dark:text-slate-300' : 'text-slate-300 dark:text-slate-600') }}"
                        @if($day->isToday()) title="Today" @endif>{{ $day->day }}</span>
                </div>
                @if($inMonth)
                <div class="space-y-0.5">
                    @foreach($dayEvents as $event)
                    <a href="{{ route('employees.show', $event['employee']) }}" x-show="show.{{ $event['type'] }}"
                       class="ev-{{ $event['type'] }} flex items-center gap-1.5 rounded px-1 py-0.5 text-xs text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                       title="{{ $types[$event['type']]['label'] }}: {{ $event['employee']->name }}{{ $event['detail'] ? ' (' . $event['detail'] . ')' : '' }}">
                        <span class="ev-dot"></span>
                        <span class="min-w-0 truncate {{ $event['milestone'] ? 'font-semibold' : '' }}">{{ $event['employee']->name }}</span>
                        @if($event['type'] === 'anniversary')
                        <span class="shrink-0 text-[10px] text-slate-500 dark:text-slate-400">{{ (int) $event['detail'] }}y</span>
                        @endif
                        @if($event['milestone'])<i class="bi bi-star-fill shrink-0 text-[9px] text-slate-400" aria-label="Milestone"></i>@endif
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    {{-- The same events as a list: the whole view on phones, and a readable list on wide screens --}}
    <div class="card">
        <div class="card-header">
            <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-list-ul me-2 text-slate-400"></i>{{ $month->format('F Y') }}, day by day</h6>
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $events->count() }} event(s)</span>
        </div>
        @forelse($byDay as $date => $dayEvents)
        @php $day = \Carbon\Carbon::parse($date); @endphp
        <div class="flex gap-4 border-b border-slate-100 px-5 py-3 last:border-b-0 dark:border-slate-800"
             x-show="{{ $dayEvents->pluck('type')->unique()->map(fn ($t) => 'show.' . $t)->implode(' || ') }}">
            <div class="w-14 shrink-0 text-center">
                <div class="text-xs uppercase text-slate-500 dark:text-slate-400">{{ $day->format('D') }}</div>
                <div class="text-xl font-semibold leading-tight {{ $day->isToday() ? 'text-primary-600 dark:text-primary-400' : 'text-slate-800 dark:text-slate-100' }}">{{ $day->day }}</div>
            </div>
            <ul class="min-w-0 flex-1 space-y-1.5">
                @foreach($dayEvents as $event)
                <li class="ev-{{ $event['type'] }} flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm" x-show="show.{{ $event['type'] }}">
                    <span class="ev-dot"></span>
                    <span class="text-xs text-slate-500 dark:text-slate-400"><i class="bi bi-{{ $types[$event['type']]['icon'] }} me-1"></i>{{ $types[$event['type']]['label'] }}</span>
                    <a href="{{ route('employees.show', $event['employee']) }}" class="font-semibold text-slate-800 hover:text-primary-600 dark:text-slate-100 dark:hover:text-primary-400">{{ $event['employee']->name }}</a>
                    @if($event['detail'])
                    <span class="text-slate-500 dark:text-slate-400">{{ $event['type'] === 'anniversary' ? $event['detail'] . ' with the company' : $event['detail'] }}</span>
                    @endif
                    @if($event['milestone'])<span class="badge badge-secondary"><i class="bi bi-star-fill me-1"></i>Milestone</span>@endif
                    @if($event['employee']->team)
                    <span class="text-xs text-slate-400">· {{ $event['employee']->team->name }}</span>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @empty
        <div class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
            <i class="bi bi-calendar-x mb-2 block text-2xl text-slate-300 dark:text-slate-600"></i>
            Nothing this month{{ $filters ? ' for these filters' : '' }}.
        </div>
        @endforelse
    </div>

    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
        Birthdays and anniversaries are shown for current staff only. People without a date of birth or join date can't appear;
        <a href="{{ route('employees.index', ['issue' => 'no_join_date']) }}" class="text-primary-600 hover:underline dark:text-primary-400">see who is missing a join date</a>.
    </p>
</div>
@endsection
