<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * One month of people events: birthdays, work anniversaries, people joining and last days.
 */
class PeopleCalendarController extends Controller
{
    /**
     * Event types, in their fixed legend order (each keeps its colour slot).
     */
    public const TYPES = [
        'birthday' => ['label' => 'Birthday', 'icon' => 'cake2'],
        'anniversary' => ['label' => 'Work anniversary', 'icon' => 'award'],
        'joining' => ['label' => 'Joins', 'icon' => 'box-arrow-in-right'],
        'leaving' => ['label' => 'Last day', 'icon' => 'box-arrow-right'],
    ];

    /**
     * Anniversaries worth calling out.
     */
    private const MILESTONES = [1, 3, 5, 10, 15, 20, 25, 30, 35, 40];

    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $month = $this->parseMonth($request->input('month')) ?? Carbon::today()->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $employees = Employee::with(['team', 'role'])
            ->when($request->filled('team'), fn ($q) => $q->where(fn ($t) => $t->where('team_id', $request->team)
                ->orWhereHas('managedTeams', fn ($m) => $m->whereKey($request->team))))
            ->when($request->filled('manager'), fn ($q) => $q->where('manager_id', $request->manager))
            ->get();

        $events = collect();
        foreach ($employees as $employee) {
            foreach ($this->eventsFor($employee, $month) as $event) {
                $events->push($event);
            }
        }

        $events = $events->sortBy(fn ($e) => [$e['date']->format('Y-m-d'), array_search($e['type'], array_keys(self::TYPES)), $e['employee']->name])->values();
        $byDay = $events->groupBy(fn ($e) => $e['date']->format('Y-m-d'));

        // Monday-first weeks covering the month.
        $days = [];
        $day = $start->copy()->startOfWeek(Carbon::MONDAY);
        $last = $end->copy()->endOfWeek(Carbon::SUNDAY);
        while ($day->lte($last)) {
            $days[] = $day->copy();
            $day->addDay();
        }
        $weeks = array_chunk($days, 7);

        $counts = collect(self::TYPES)->map(fn ($t, $type) => $events->where('type', $type)->count());
        $teams = Team::orderBy('name')->get(['id', 'name']);
        $managers = Employee::where('is_manager', true)->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('employees.calendar', compact('month', 'events', 'byDay', 'weeks', 'counts', 'teams', 'managers'));
    }

    /**
     * This employee's events in $month. Birthdays and anniversaries only for people still here;
     * a 29 February date falls on the 28th in other years.
     *
     * @return list<array{date: Carbon, type: string, employee: Employee, detail: ?string, milestone: bool}>
     */
    private function eventsFor(Employee $employee, Carbon $month): array
    {
        $events = [];
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $onDay = fn (Carbon $date) => $month->copy()->day(min($date->day, $month->daysInMonth));
        $active = $employee->status === 'active';

        if ($active && $employee->date_of_birth && $employee->date_of_birth->month === $month->month) {
            $events[] = ['date' => $onDay($employee->date_of_birth), 'type' => 'birthday', 'employee' => $employee, 'detail' => null, 'milestone' => false];
        }

        if ($active && $employee->join_date && $employee->join_date->month === $month->month) {
            $years = $month->year - $employee->join_date->year;
            if ($years >= 1) {
                $events[] = [
                    'date' => $onDay($employee->join_date),
                    'type' => 'anniversary',
                    'employee' => $employee,
                    'detail' => $years . ' ' . ($years === 1 ? 'year' : 'years'),
                    'milestone' => in_array($years, self::MILESTONES, true),
                ];
            }
        }

        if ($employee->join_date && $employee->join_date->between($start, $end)) {
            $events[] = ['date' => $employee->join_date->copy(), 'type' => 'joining', 'employee' => $employee, 'detail' => $employee->role?->name, 'milestone' => false];
        }

        if ($employee->status === 'resigned' && $employee->resigned_date && $employee->resigned_date->between($start, $end)) {
            $events[] = ['date' => $employee->resigned_date->copy(), 'type' => 'leaving', 'employee' => $employee, 'detail' => $employee->role?->name, 'milestone' => false];
        }

        return $events;
    }

    private function parseMonth(?string $value): ?Carbon
    {
        if (!$value || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('!Y-m', $value);
    }
}
