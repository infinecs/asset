@extends('layouts.app')

@section('title', 'Tasks - IT Asset Management')
@section('page-title', 'Tasks')

@section('content')
@php
    $weekStart = isset($weekStart)
        ? \Illuminate\Support\Carbon::parse($weekStart)
        : \Illuminate\Support\Carbon::parse($selectedDate)->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
    $weekEnd = isset($weekEnd)
        ? \Illuminate\Support\Carbon::parse($weekEnd)
        : $weekStart->copy()->addDays(4);
    $workWeekNumber = $workWeekNumber ?? $weekStart->isoWeek;
    $workWeekYear = $workWeekYear ?? $weekStart->isoWeekYear;
    $workWeekDays = collect(range(0, 4))->map(fn ($offset) => $weekStart->copy()->addDays($offset));
    $selectedDateObj = \Illuminate\Support\Carbon::parse($selectedDate);
    $workWeekTasks = $tasksByDate->flatten(1);
    $selectedTasksByCategory = $selectedTasks->groupBy('category');
    $workWeekTasksByCategory = $workWeekTasks->groupBy('category');
    $categoryOrder = ['IT', 'Website', 'Server', 'Graphics Designs'];
    $categoryLabel = [
        'IT' => 'IT',
        'Website' => 'Website',
        'Server' => 'Server',
        'Graphics Designs' => 'Graphics Design',
    ];
@endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Daily Task Planner</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Showcase and manage your day-to-day task execution (Work Week) · Viewing: {{ $selectedUser->name ?? 'User' }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <form method="GET" action="{{ route('tasks.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <label class="mb-0 text-sm text-slate-500 dark:text-slate-400">User</label>
            <select name="user_id" class="field-input truncate" onchange="this.form.submit()" style="width: 220px;">
                @foreach($viewableUsers as $viewUser)
                <option value="{{ $viewUser->id }}" {{ (int) $selectedUserId === (int) $viewUser->id ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($viewUser->name, 28) }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('tasks.index', ['date' => $weekStart->copy()->subWeek()->toDateString(), 'user_id' => $selectedUserId]) }}" class="btn btn-outline btn-sm btn-icon">
            <i class="bi bi-chevron-left"></i>
        </a>
        <button class="btn btn-outline btn-sm" disabled>Work Week {{ $workWeekNumber }} ({{ $workWeekYear }}) · {{ $weekStart->format('d-m-Y') }} - {{ $weekEnd->format('d-m-Y') }}</button>
        <a href="{{ route('tasks.index', ['date' => $weekStart->copy()->addWeek()->toDateString(), 'user_id' => $selectedUserId]) }}" class="btn btn-outline btn-sm btn-icon">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12" x-data>
    <div class="lg:col-span-7">
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Work Week {{ $workWeekNumber }} (Mon-Fri)</h6>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-center">
                            <th class="border border-slate-200 p-2 text-slate-500 dark:border-slate-800 dark:text-slate-400">Mon</th>
                            <th class="border border-slate-200 p-2 text-slate-500 dark:border-slate-800 dark:text-slate-400">Tue</th>
                            <th class="border border-slate-200 p-2 text-slate-500 dark:border-slate-800 dark:text-slate-400">Wed</th>
                            <th class="border border-slate-200 p-2 text-slate-500 dark:border-slate-800 dark:text-slate-400">Thu</th>
                            <th class="border border-slate-200 p-2 text-slate-500 dark:border-slate-800 dark:text-slate-400">Fri</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach($workWeekDays as $day)
                                @php
                                    $dateKey = $day->toDateString();
                                    $dayTasks = $tasksByDate->get($dateKey, collect());
                                    $isSelected = $dateKey === $selectedDate;
                                @endphp
                                <td class="border p-2 align-top {{ $isSelected ? 'border-2 border-primary-500' : 'border-slate-200 dark:border-slate-800' }}" style="height:110px; min-width: 120px;">
                                    <a href="{{ route('tasks.index', ['date' => $dateKey, 'user_id' => $selectedUserId]) }}" class="block h-full">
                                        <div class="mb-1 flex items-start justify-between">
                                            <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $day->format('D') }} {{ $day->day }}</span>
                                            @if($dayTasks->count() > 0)
                                            <span class="badge badge-primary">{{ $dayTasks->count() }}</span>
                                            @endif
                                        </div>
                                        @if($dayTasks->where('is_completed', false)->count() > 0)
                                        <div class="text-xs text-amber-600 dark:text-amber-400">{{ $dayTasks->where('is_completed', false)->count() }} pending</div>
                                        @elseif($dayTasks->count() > 0)
                                        <div class="text-xs text-green-600 dark:text-green-400">All done</div>
                                        @endif
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Daily Cards · {{ $selectedDateObj->format('d-m-Y') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="$dispatch('open-modal', 'fullCardModal')">
                    <i class="bi bi-card-text"></i>View Full Card
                </button>
            </div>
            <div>
                @forelse($selectedTasks as $task)
                <div class="border-b border-slate-100 p-4 last:border-b-0 dark:border-slate-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="mb-1 text-sm font-semibold text-primary-600 dark:text-primary-400">{{ $task->category }}</div>
                            <div class="font-semibold {{ $task->is_completed ? 'text-slate-400 line-through' : 'text-slate-800 dark:text-slate-100' }}">{{ $task->title }}</div>
                            @if($task->description)
                            <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $task->description }}</div>
                            @endif
                        </div>
                        <span class="badge badge-{{ $task->is_completed ? 'success' : 'warning' }}">{{ $task->is_completed ? 'Done' : 'Pending' }}</span>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline" @click="$dispatch('open-modal', 'editTaskModal{{ $task->id }}')">Edit</button>
                        <form method="POST" action="{{ route('tasks.toggle', $task) }}">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm btn-outline-primary">{{ $task->is_completed ? 'Mark Pending' : 'Mark Done' }}</button>
                        </form>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </div>

                <x-ui.modal id="editTaskModal{{ $task->id }}">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                        <h5 class="text-base font-semibold text-slate-900 dark:text-white">Edit Task</h5>
                        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="open = false"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <form method="POST" action="{{ route('tasks.update', $task) }}">
                        @csrf
                        @method('PATCH')
                        <div class="space-y-3 p-6">
                            <div>
                                <label class="field-label">Task Date</label>
                                <input type="date" name="task_date" class="field-input" value="{{ \Illuminate\Support\Carbon::parse((string) $task->task_date)->toDateString() }}" required>
                            </div>
                            <div>
                                <label class="field-label">Category</label>
                                <select name="category" class="field-input" data-title-select-target="editTaskTitleSelect{{ $task->id }}" data-current-title="{{ $task->title }}" required>
                                    @foreach($taskCategories as $category)
                                    <option value="{{ $category }}" {{ $task->category === $category ? 'selected' : '' }}>{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="field-label">Title (Existing)</label>
                                <select name="title_select" id="editTaskTitleSelect{{ $task->id }}" class="field-input"></select>
                            </div>
                            <div>
                                <label class="field-label">Create New Title</label>
                                <input type="text" name="title_new" class="field-input" value="" placeholder="Type to create a new title (optional)">
                            </div>
                            <div>
                                <label class="field-label">Description</label>
                                <textarea name="description" class="field-input" rows="3" placeholder="Optional details...">{{ $task->description }}</textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
                            <button type="button" class="btn btn-outline" @click="open = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </x-ui.modal>
                @empty
                <div class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">No tasks for this date.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="lg:col-span-5">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Add Task</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tasks.store') }}">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                    <div class="mb-3">
                        <label class="field-label">Task Date</label>
                        <input type="date" name="task_date" class="field-input" value="{{ old('task_date', $selectedDate) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Category</label>
                        <select name="category" id="taskCategory" class="field-input" data-title-select-target="taskTitleSelect" required>
                            @foreach($taskCategories as $category)
                            <option value="{{ $category }}" {{ old('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Title (Existing)</label>
                        <select name="title_select" id="taskTitleSelect" class="field-input"></select>
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Create New Title</label>
                        <input type="text" name="title_new" class="field-input" value="{{ old('title_new') }}" placeholder="Type to create a new title (optional)">
                        <p class="field-hint">Choose existing title from dropdown or type a new title.</p>
                    </div>
                    <div class="mb-3">
                        <label class="field-label">Description</label>
                        <textarea name="description" class="field-input" rows="3" placeholder="Optional details...">{{ old('description') }}</textarea>
                    </div>
                    <button class="btn btn-primary w-full">
                        <i class="bi bi-plus-circle"></i>Add Task
                    </button>
                </form>
            </div>
        </div>

    </div>

    <x-ui.modal id="fullCardModal" maxWidth="max-w-3xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <h5 class="text-base font-semibold text-slate-900 dark:text-white">Full Task Card · Work Week {{ $workWeekNumber }} ({{ $workWeekYear }})</h5>
            <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="open = false"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="max-h-[70vh] overflow-y-auto p-6">
            @php($printedCategories = collect())

            @foreach($categoryOrder as $category)
                @php($categoryTasks = $workWeekTasksByCategory->get($category, collect()))
                @if($categoryTasks->isNotEmpty())
                    @php($printedCategories->push($category))
                    <div class="mb-2 text-lg font-bold text-slate-900 underline dark:text-white">{{ $categoryLabel[$category] ?? $category }}</div>
                    @php($tasksByTitle = $categoryTasks->groupBy('title'))
                    @foreach($tasksByTitle as $title => $titleTasks)
                        <div class="mb-1 ml-3 font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</div>
                        <div class="mb-3 pl-4">
                            @foreach($titleTasks as $task)
                            <div class="mb-1 flex items-start justify-between gap-2">
                                <span class="text-slate-700 dark:text-slate-300">• {{ $task->description ?: $task->title }}</span>
                                <span class="font-semibold {{ $task->is_completed ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $task->is_completed ? 'Done' : 'Pending' }}</span>
                            </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            @endforeach

            @foreach($workWeekTasksByCategory as $category => $categoryTasks)
                @if(!$printedCategories->contains($category) && $categoryTasks->isNotEmpty())
                    <div class="mb-2 text-lg font-bold text-slate-900 underline dark:text-white">{{ $category }}</div>
                    @php($tasksByTitle = $categoryTasks->groupBy('title'))
                    @foreach($tasksByTitle as $title => $titleTasks)
                        <div class="mb-1 ml-3 font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</div>
                        <div class="mb-3 pl-4">
                            @foreach($titleTasks as $task)
                            <div class="mb-1 flex items-start justify-between gap-2">
                                <span class="text-slate-700 dark:text-slate-300">• {{ $task->description ?: $task->title }}</span>
                                <span class="font-semibold {{ $task->is_completed ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $task->is_completed ? 'Done' : 'Pending' }}</span>
                            </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            @endforeach

            @if($workWeekTasks->isEmpty())
            <div class="py-8 text-center text-slate-500 dark:text-slate-400">No tasks for this work week.</div>
            @endif
        </div>
    </x-ui.modal>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const titlesByCategory = @json($titlesByCategory);

    function refreshTitleOptions(categorySelect) {
        if (!categorySelect) {
            return;
        }

        const selectId = categorySelect.getAttribute('data-title-select-target');
        const titleSelect = selectId ? document.getElementById(selectId) : null;
        if (!titleSelect) {
            return;
        }

        const category = categorySelect.value;
        const rawTitles = Array.isArray(titlesByCategory[category]) ? titlesByCategory[category] : [];
        const titles = [...new Set(rawTitles.filter(function (title) {
            return typeof title === 'string' && title.trim() !== '';
        }))];
        const previousValue = titleSelect.value;
        const currentTitle = categorySelect.getAttribute('data-current-title') || '';

        titleSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Select existing title --';
        titleSelect.appendChild(placeholder);

        titles.forEach(function (title) {
            const option = document.createElement('option');
            option.value = title;
            option.textContent = title;
            titleSelect.appendChild(option);
        });

        if (currentTitle && !titles.includes(currentTitle)) {
            const customOption = document.createElement('option');
            customOption.value = currentTitle;
            customOption.textContent = currentTitle;
            titleSelect.appendChild(customOption);
        }

        if (previousValue && Array.from(titleSelect.options).some(function (option) { return option.value === previousValue; })) {
            titleSelect.value = previousValue;
        } else if (currentTitle) {
            titleSelect.value = currentTitle;
        }
    }

    document.querySelectorAll('select[data-title-select-target]').forEach(function (categorySelect) {
        categorySelect.addEventListener('change', function () {
            refreshTitleOptions(categorySelect);
        });

        refreshTitleOptions(categorySelect);
    });
});
</script>
@endsection
