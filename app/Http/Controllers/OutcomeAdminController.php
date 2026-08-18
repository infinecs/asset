<?php

namespace App\Http\Controllers;

use App\Models\OutcomeCategory;
use App\Models\OutcomeDepartment;
use App\Models\OutcomeTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OutcomeAdminController extends Controller
{
    public function categories()
    {
        $this->authorizeOutcomeManager();

        $categories = OutcomeCategory::orderBy('name')->get();

        return view('outcome.admin.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $this->authorizeOutcomeManager();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:outcome_categories,name',
        ]);

        OutcomeCategory::create($validated);

        return redirect()->route('outcome.categories.index')->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, OutcomeCategory $outcomeCategory)
    {
        $this->authorizeOutcomeManager();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:outcome_categories,name,' . $outcomeCategory->id,
        ]);

        $outcomeCategory->update($validated);

        return redirect()->route('outcome.categories.index')->with('success', 'Category updated.');
    }

    public function destroyCategory(OutcomeCategory $outcomeCategory)
    {
        $this->authorizeOutcomeManager();

        $outcomeCategory->delete();

        return redirect()->route('outcome.categories.index')->with('success', 'Category deleted.');
    }

    public function departments()
    {
        $this->authorizeOutcomeManager();

        $departments = OutcomeDepartment::orderBy('name')->get();

        return view('outcome.admin.departments', compact('departments'));
    }

    public function storeDepartment(Request $request)
    {
        $this->authorizeOutcomeManager();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:outcome_departments,name',
        ]);

        OutcomeDepartment::create($validated);

        return redirect()->route('outcome.departments.index')->with('success', 'Department added.');
    }

    public function updateDepartment(Request $request, OutcomeDepartment $outcomeDepartment)
    {
        $this->authorizeOutcomeManager();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:outcome_departments,name,' . $outcomeDepartment->id,
        ]);

        $outcomeDepartment->update($validated);

        return redirect()->route('outcome.departments.index')->with('success', 'Department updated.');
    }

    public function destroyDepartment(OutcomeDepartment $outcomeDepartment)
    {
        $this->authorizeOutcomeManager();

        $outcomeDepartment->delete();

        return redirect()->route('outcome.departments.index')->with('success', 'Department deleted.');
    }

    public function report(Request $request)
    {
        $this->authorizeOutcomeManager();

        $query = OutcomeTask::with('user')->orderByDesc('start_date');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }

        $tasks = $query->paginate(20)->withQueryString();
        $totalManHours = (clone $query)->sum('man_hour');

        return view('outcome.admin.report', [
            'tasks' => $tasks,
            'totalManHours' => $totalManHours,
            'users' => User::where('role', 'normal')->orderBy('name')->get(['id', 'name']),
            'categories' => OutcomeCategory::orderBy('name')->pluck('name'),
            'departments' => OutcomeDepartment::orderBy('name')->pluck('name'),
        ]);
    }

    private const HOURS_PER_MAN_DAY = 8;

    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    public function summary(Request $request)
    {
        $this->authorizeOutcomeManager();

        $view = $request->input('view') === 'yearly' ? 'yearly' : 'monthly';
        $normalUsers = User::where('role', 'normal')->orderBy('name')->get(['id', 'name']);

        if ($view === 'yearly') {
            $data = $this->buildYearlyData($request);
            $yearlyMode = $request->input('mode') === 'daily' ? 'daily' : 'category';

            return view('outcome.admin.summary', [
                'view' => 'yearly',
                'yearlyMode' => $yearlyMode,
                'normalUsers' => $normalUsers,
                'byUserYearly' => $data['byUser'],
                'byUserYearlyDaily' => $data['byUserDaily'],
                'dailyRange' => $data['dailyRange'],
                'accumulated' => $data['accumulated'],
                'selectedYear' => $data['year'],
                'monthLabels' => self::MONTH_LABELS,
            ]);
        }

        $data = $this->buildMonthlyData($request);
        $monthlyMode = $request->input('mode') === 'daily' ? 'daily' : 'category';

        return view('outcome.admin.summary', [
            'view' => 'monthly',
            'monthlyMode' => $monthlyMode,
            'normalUsers' => $normalUsers,
            'byUser' => $data['byUser'],
            'byUserMonthlyCategory' => $data['byUserCategory'],
            'accumulated' => $data['accumulated'],
            'selectedMonth' => $data['month']->format('Y-m'),
            'monthLabel' => $data['month']->format('F Y'),
        ]);
    }

    public function exportSummary(Request $request)
    {
        $this->authorizeOutcomeManager();

        if ($request->input('view') === 'yearly') {
            return $this->exportYearly($request);
        }

        return $this->exportMonthly($request);
    }

    private function exportMonthly(Request $request)
    {
        $data = $this->buildMonthlyData($request);
        $mode = $request->input('mode') === 'daily' ? 'daily' : 'category';

        $filenameSuffix = $data['byUser']->count() === 1
            ? '_' . \Illuminate\Support\Str::slug($data['byUser']->first()['user_name'])
            : '';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="outcome_summary_' . $data['month']->format('Y-m') . $filenameSuffix . '.csv"',
        ];

        $callback = function () use ($data, $mode) {
            $h = fopen('php://output', 'w');

            fputcsv($h, ['Outcome Summary Report - ' . $data['month']->format('F Y')]);

            if ($mode === 'daily') {
                foreach ($data['byUser'] as $user) {
                    fputcsv($h, []);
                    fputcsv($h, [$user['user_name']]);
                    fputcsv($h, ['Date', 'Task', 'Department', 'Man Day']);
                    foreach ($user['rows'] as $row) {
                        fputcsv($h, [$row->start_date->format('d-m-Y'), $row->category, $row->department, $this->toManDays($row->man_hour)]);
                    }
                    fputcsv($h, ['Total Man Day', '', '', $user['total_man_days']]);
                }
            } else {
                foreach ($data['byUserCategory'] as $user) {
                    fputcsv($h, []);
                    fputcsv($h, [$user['user_name']]);
                    fputcsv($h, ['Task', 'Man Day']);
                    foreach ($user['tasks'] as $task) {
                        fputcsv($h, [$task['category'], $task['man_days']]);
                    }
                    fputcsv($h, ['Total', $user['total_man_days']]);
                }
            }

            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportYearly(Request $request)
    {
        $data = $this->buildYearlyData($request);
        $mode = $request->input('mode') === 'daily' ? 'daily' : 'category';

        $filenameSuffix = $data['byUser']->count() === 1
            ? '_' . \Illuminate\Support\Str::slug($data['byUser']->first()['user_name'])
            : '';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="outcome_summary_' . $data['year'] . $filenameSuffix . '.csv"',
        ];

        $callback = function () use ($data, $mode) {
            $h = fopen('php://output', 'w');

            fputcsv($h, ['Outcome Summary Report - ' . $data['year'] . ' (Man Days)']);

            if ($mode === 'daily') {
                foreach ($data['byUserDaily'] as $user) {
                    fputcsv($h, []);
                    fputcsv($h, [$user['user_name']]);
                    fputcsv($h, ['Date', 'Task', 'Department', 'Man Day']);
                    foreach ($user['rows'] as $row) {
                        fputcsv($h, [$row->start_date->format('d-m-Y'), $row->category, $row->department, $this->toManDays($row->man_hour)]);
                    }
                    fputcsv($h, ['Total Man Day', '', '', $user['total_man_days']]);
                }
            } else {
                foreach ($data['byUser'] as $user) {
                    fputcsv($h, []);
                    fputcsv($h, [$user['user_name']]);
                    fputcsv($h, array_merge(['Task', $data['year'] . ' YTD'], array_values(self::MONTH_LABELS)));
                    foreach ($user['tasks'] as $task) {
                        fputcsv($h, array_merge([$task['category'], $task['ytd']], array_values($task['months'])));
                    }
                    fputcsv($h, array_merge(['Total', $user['ytd']], array_values($user['monthly_totals'])));
                }
            }

            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function toManDays($hours): float
    {
        return round(((float) $hours) / self::HOURS_PER_MAN_DAY, 2);
    }

    private function buildMonthlyData(Request $request): array
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : now()->startOfMonth();

        $monthEnd = $month->copy()->endOfMonth();

        $entries = OutcomeTask::query()
            ->join('users', 'users.id', '=', 'outcome_tasks.user_id')
            ->where('users.role', 'normal')
            ->whereBetween('outcome_tasks.start_date', [$month->toDateString(), $monthEnd->toDateString()])
            ->when($request->filled('user_id'), fn ($q) => $q->where('outcome_tasks.user_id', $request->user_id))
            ->orderBy('users.name')
            ->orderBy('outcome_tasks.start_date')
            ->get([
                'users.id as user_id',
                'users.name as user_name',
                'outcome_tasks.start_date',
                'outcome_tasks.category',
                'outcome_tasks.department',
                'outcome_tasks.man_hour',
                'outcome_tasks.is_completed',
            ]);

        $byUser = $entries->groupBy('user_id')->map(function ($rows, $userId) {
            return [
                'user_id' => $userId,
                'user_name' => $rows->first()->user_name,
                'rows' => $rows,
                'total_man_days' => $this->toManDays($rows->sum('man_hour')),
            ];
        })->sortBy('user_name')->values();

        $byUserCategory = $entries->groupBy('user_id')->map(function ($rows) {
            $tasks = $rows->groupBy('category')->map(function ($categoryRows, $category) {
                return [
                    'category' => $category,
                    'count' => $categoryRows->count(),
                    'man_days' => $this->toManDays($categoryRows->sum('man_hour')),
                ];
            })->sortByDesc('man_days')->values();

            return [
                'user_id' => $rows->first()->user_id,
                'user_name' => $rows->first()->user_name,
                'tasks' => $tasks,
                'total_count' => $rows->count(),
                'total_man_days' => $this->toManDays($rows->sum('man_hour')),
            ];
        })->sortBy('user_name')->values();

        $accumulated = [
            'task_count' => $entries->count(),
            'completed_count' => $entries->sum('is_completed'),
            'total_man_days' => $this->toManDays($entries->sum('man_hour')),
        ];

        return compact('byUser', 'byUserCategory', 'accumulated', 'month');
    }

    private function buildYearlyData(Request $request): array
    {
        $year = $request->filled('year') ? (int) $request->year : (int) now()->format('Y');

        $entries = OutcomeTask::query()
            ->join('users', 'users.id', '=', 'outcome_tasks.user_id')
            ->where('users.role', 'normal')
            ->whereYear('outcome_tasks.start_date', $year)
            ->when($request->filled('user_id'), fn ($q) => $q->where('outcome_tasks.user_id', $request->user_id))
            ->orderBy('outcome_tasks.start_date')
            ->get([
                'users.id as user_id',
                'users.name as user_name',
                'outcome_tasks.start_date',
                'outcome_tasks.category',
                'outcome_tasks.department',
                'outcome_tasks.man_hour',
                'outcome_tasks.is_completed',
                \Illuminate\Support\Facades\DB::raw('MONTH(outcome_tasks.start_date) as month_num'),
            ]);

        $dailyFrom = $request->filled('daily_from')
            ? Carbon::createFromFormat('Y-m', $request->daily_from)->startOfMonth()
            : null;
        $dailyTo = $request->filled('daily_to')
            ? Carbon::createFromFormat('Y-m', $request->daily_to)->endOfMonth()
            : null;

        $dailyRangeSelected = $dailyFrom && $dailyTo;

        $dailyEntries = $dailyRangeSelected
            ? $entries->filter(fn ($e) => $e->start_date->between($dailyFrom, $dailyTo))
            : collect();

        $byUserDaily = $dailyEntries->groupBy('user_id')->map(function ($rows, $userId) {
            return [
                'user_id' => $userId,
                'user_name' => $rows->first()->user_name,
                'rows' => $rows,
                'total_man_days' => $this->toManDays($rows->sum('man_hour')),
            ];
        })->sortBy('user_name')->values();

        $byUser = $entries->groupBy('user_id')->map(function ($userEntries) {
            $tasks = $userEntries->groupBy('category')->map(function ($categoryEntries, $category) {
                $monthsHours = array_fill(1, 12, 0.0);
                foreach ($categoryEntries as $entry) {
                    $monthsHours[(int) $entry->month_num] += (float) $entry->man_hour;
                }

                return [
                    'category' => $category,
                    'months' => array_map(fn ($h) => $this->toManDays($h), $monthsHours),
                    'ytd' => $this->toManDays(array_sum($monthsHours)),
                ];
            })->sortBy('category')->values();

            $monthlyTotalsHours = array_fill(1, 12, 0.0);
            foreach ($userEntries as $entry) {
                $monthlyTotalsHours[(int) $entry->month_num] += (float) $entry->man_hour;
            }

            return [
                'user_id' => $userEntries->first()->user_id,
                'user_name' => $userEntries->first()->user_name,
                'tasks' => $tasks,
                'monthly_totals' => array_map(fn ($h) => $this->toManDays($h), $monthlyTotalsHours),
                'ytd' => $this->toManDays(array_sum($monthlyTotalsHours)),
            ];
        })->sortBy('user_name')->values();

        $accumulated = [
            'task_count' => $entries->count(),
            'completed_count' => $entries->sum('is_completed'),
            'total_man_days' => $this->toManDays($entries->sum('man_hour')),
        ];

        $dailyRange = [
            'selected' => $dailyRangeSelected,
            'from' => $dailyFrom ? $dailyFrom->format('Y-m') : '',
            'to' => $dailyTo ? $dailyTo->format('Y-m') : '',
        ];

        return compact('byUser', 'byUserDaily', 'dailyRange', 'accumulated', 'year');
    }

    private function authorizeOutcomeManager(): void
    {
        if (!auth()->user()->canManageOutcome()) {
            abort(403);
        }
    }
}
