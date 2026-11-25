<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Headcount and turnover, worked out from join and resignation dates.
 *
 * Someone is on staff on a day when they joined on or before it (or their join date is unknown)
 * and, if they've left, their resignation date is after it. So a leaver is off the headcount
 * at the end of their last day, which keeps each month-end headcount equal to the previous
 * one plus that month's joiners minus its leavers. People marked resigned without a date can't be placed in
 * time and are left out; active people without a join date count in every month but never as
 * joiners.
 */
class HeadcountController extends Controller
{
    public const PERIODS = [6, 12, 24, 36];

    private const TOP = 10;

    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $months = in_array((int) $request->input('months'), self::PERIODS, true) ? (int) $request->input('months') : 12;
        $today = Carbon::today();
        $periodStart = $today->copy()->startOfMonth()->subMonths($months - 1);

        $employees = Employee::with(['team', 'role', 'managedTeams'])
            ->when($request->filled('team'), fn ($q) => $q->where(fn ($t) => $t->where('team_id', $request->team)
                ->orWhereHas('managedTeams', fn ($m) => $m->whereKey($request->team))))
            ->when($request->filled('location'), fn ($q) => $q->where('work_location', $request->location))
            ->get();

        $placeable = $employees->reject(fn (Employee $e) => $e->status === 'resigned' && !$e->resigned_date);
        $onStaff = fn (Employee $e, Carbon $day) => (!$e->join_date || $e->join_date->lte($day))
            && ($e->status !== 'resigned' || $e->resigned_date->gt($day));

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $monthStart = $periodStart->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth()->min($today);

            $series[] = [
                'key' => $monthStart->format('Y-m'),
                'label' => $monthStart->format('M Y'),
                'short' => $monthStart->format('M'),
                'headcount' => $placeable->filter(fn ($e) => $onStaff($e, $monthEnd))->count(),
                'joined' => $placeable->filter(fn ($e) => $e->join_date && $e->join_date->between($monthStart, $monthEnd))->count(),
                'left' => $placeable->filter(fn ($e) => $e->resigned_date && $e->status === 'resigned' && $e->resigned_date->between($monthStart, $monthEnd))->count(),
            ];
        }

        $current = $placeable->filter(fn ($e) => $onStaff($e, $today));
        $startHeadcount = $placeable->filter(fn ($e) => $onStaff($e, $periodStart->copy()->subDay()))->count();
        $joined = array_sum(array_column($series, 'joined'));
        $left = array_sum(array_column($series, 'left'));
        $averageHeadcount = (array_sum(array_column($series, 'headcount')) + $startHeadcount) / ($months + 1);

        $summary = [
            'headcount' => $current->count(),
            'change' => $current->count() - $startHeadcount,
            'joined' => $joined,
            'left' => $left,
            'turnover' => $averageHeadcount > 0 ? round($left / $averageHeadcount * 100, 1) : null,
            'starting' => $placeable->filter(fn ($e) => $e->status === 'active' && $e->join_date && $e->join_date->gt($today))->count(),
        ];

        // Today's staff, broken down. A manager's team is the first team they lead.
        $breakdowns = [
            'Team' => $this->ranked($current->map(fn (Employee $e) => $e->team?->name ?? $e->managedTeams->first()?->name ?? 'No team')),
            'Role' => $this->ranked($current->map(fn (Employee $e) => $e->role?->name ?? 'No role')),
            'Work location' => $this->ranked($current->map(fn (Employee $e) => $e->work_location ?: 'Not set')),
        ];

        $withJoinDate = $current->filter(fn (Employee $e) => $e->join_date);
        $years = $withJoinDate->map(fn (Employee $e) => $e->join_date->diffInDays($today) / 365.25);
        $tenureBands = ['Under 1 year' => [0, 1], '1 to 2 years' => [1, 2], '2 to 5 years' => [2, 5], '5 to 10 years' => [5, 10], '10 years or more' => [10, INF]];
        $tenure = collect($tenureBands)->map(fn ($band) => $years->filter(fn ($y) => $y >= $band[0] && $y < $band[1])->count());

        $inPeriod = fn (?Carbon $date) => $date && $date->between($periodStart, $today);
        $joiners = $placeable->filter(fn ($e) => $inPeriod($e->join_date))->sortByDesc('join_date')->values();
        $leavers = $placeable->filter(fn ($e) => $e->status === 'resigned' && $inPeriod($e->resigned_date))->sortByDesc('resigned_date')->values();

        $caveats = [
            'noJoinDate' => $current->reject(fn (Employee $e) => $e->join_date)->count(),
            'noResignedDate' => $employees->count() - $placeable->count(),
        ];

        $filters = [
            'teams' => Team::orderBy('name')->get(['id', 'name']),
            'locations' => Employee::whereNotNull('work_location')->where('work_location', '!=', '')->distinct()->orderBy('work_location')->pluck('work_location'),
        ];

        return view('employees.headcount', [
            'months' => $months,
            'periodStart' => $periodStart,
            'today' => $today,
            'series' => $series,
            'summary' => $summary,
            'breakdowns' => $breakdowns,
            'tenure' => $tenure,
            'averageTenure' => $years->isNotEmpty() ? round($years->avg(), 1) : null,
            'joiners' => $joiners,
            'leavers' => $leavers,
            'caveats' => $caveats,
            'filters' => $filters,
        ]);
    }

    /**
     * Counts per value, largest first; past the top ten the rest fold into "Other".
     *
     * @return Collection<string, int>
     */
    private function ranked(Collection $values): Collection
    {
        $counts = $values->countBy()->sortDesc();

        if ($counts->count() <= self::TOP + 1) {
            return $counts;
        }

        return $counts->take(self::TOP)->put('Other', $counts->skip(self::TOP)->sum());
    }
}
