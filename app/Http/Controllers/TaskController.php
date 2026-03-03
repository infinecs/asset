<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    private const APP_TIMEZONE = 'Asia/Kuala_Lumpur';
    private const TASK_CATEGORIES = ['IT', 'Website', 'Graphics Designs', 'Server'];

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $selectedDateObj = $request->filled('date')
            ? Carbon::parse($request->date, self::APP_TIMEZONE)
            : now(self::APP_TIMEZONE);

        if ($selectedDateObj->isWeekend()) {
            $selectedDateObj = $selectedDateObj->copy()->startOfWeek(Carbon::MONDAY);
        }

        $selectedDate = $selectedDateObj->toDateString();
        $weekStart = $selectedDateObj->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(4);
        $workWeekNumber = $weekStart->copy()->locale('ms_MY')->isoWeek;
        $workWeekYear = $weekStart->copy()->locale('ms_MY')->isoWeekYear;

        $tasks = Task::where('user_id', auth()->id())
            ->whereBetween('task_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('task_date')
            ->orderBy('is_completed')
            ->orderBy('created_at')
            ->get();

        $titleSourceTasks = Task::where('user_id', auth()->id())
            ->orderBy('title')
            ->get(['category', 'title']);

        $canonicalCategoryMap = [
            'it' => 'IT',
            'website' => 'Website',
            'websites' => 'Website',
            'server' => 'Server',
            'servers' => 'Server',
            'graphicsdesign' => 'Graphics Designs',
            'graphicsdesigns' => 'Graphics Designs',
        ];

        $titlesByCategory = collect(self::TASK_CATEGORIES)
            ->mapWithKeys(fn ($category) => [$category => collect()]);

        foreach ($titleSourceTasks as $taskTitleSource) {
            $normalizedCategoryKey = strtolower(trim((string) $taskTitleSource->category));
            $normalizedCategoryKey = preg_replace('/\s+/', '', $normalizedCategoryKey) ?? $normalizedCategoryKey;
            $canonicalCategory = $canonicalCategoryMap[$normalizedCategoryKey] ?? null;
            $title = trim((string) $taskTitleSource->title);

            if (!$canonicalCategory || $title === '') {
                continue;
            }

            $titlesByCategory[$canonicalCategory]->push($title);
        }

        $titlesByCategory = $titlesByCategory
            ->map(fn ($titles) => $titles->unique()->values()->all())
            ->all();

        $tasksByDate = $tasks->groupBy(fn ($task) => Carbon::parse((string) $task->task_date)->toDateString());
        $selectedTasks = $tasksByDate->get($selectedDate, collect());

        return view('tasks.index', [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'workWeekNumber' => $workWeekNumber,
            'workWeekYear' => $workWeekYear,
            'selectedDate' => $selectedDate,
            'tasksByDate' => $tasksByDate,
            'selectedTasks' => $selectedTasks,
            'taskCategories' => self::TASK_CATEGORIES,
            'titlesByCategory' => $titlesByCategory,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'task_date' => 'required|date',
            'category' => 'required|in:IT,Website,Graphics Designs,Server',
            'title_select' => 'nullable|string|max:255|required_without:title_new',
            'title_new' => 'nullable|string|max:255|required_without:title_select',
            'description' => 'nullable|string',
        ]);

        $title = filled($validated['title_new'] ?? null)
            ? trim((string) $validated['title_new'])
            : trim((string) ($validated['title_select'] ?? ''));

        Task::create([
            'user_id' => auth()->id(),
            'task_date' => $validated['task_date'],
            'category' => $validated['category'],
            'title' => $title,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('tasks.index', [
            'date' => $validated['task_date'],
        ])->with('success', 'Task added successfully.');
    }

    public function toggle(Task $task)
    {
        $this->authorizeTask($task);

        $task->update([
            'is_completed' => !$task->is_completed,
        ]);

        return redirect()->route('tasks.index', [
            'date' => Carbon::parse((string) $task->task_date, self::APP_TIMEZONE)->toDateString(),
        ])->with('success', 'Task status updated.');
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'task_date' => 'required|date',
            'category' => 'required|in:IT,Website,Graphics Designs,Server',
            'title_select' => 'nullable|string|max:255|required_without:title_new',
            'title_new' => 'nullable|string|max:255|required_without:title_select',
            'description' => 'nullable|string',
        ]);

        $title = filled($validated['title_new'] ?? null)
            ? trim((string) $validated['title_new'])
            : trim((string) ($validated['title_select'] ?? ''));

        $task->update([
            'task_date' => $validated['task_date'],
            'category' => $validated['category'],
            'title' => $title,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('tasks.index', [
            'date' => $validated['task_date'],
        ])->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $this->authorizeTask($task);

        $date = Carbon::parse((string) $task->task_date, self::APP_TIMEZONE)->toDateString();
        $task->delete();

        return redirect()->route('tasks.index', [
            'date' => $date,
        ])->with('success', 'Task deleted.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    private function authorizeTask(Task $task): void
    {
        $this->authorizeAdmin();

        if ($task->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
