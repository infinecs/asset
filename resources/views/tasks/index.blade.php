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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">Daily Task Planner</h5>
        <p class="text-muted small mb-0">Showcase and manage your day-to-day task execution (Work Week) · Viewing: {{ $selectedUser->name ?? 'User' }}</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <form method="GET" action="{{ route('tasks.index') }}" class="d-flex align-items-center gap-2">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <label class="small text-muted mb-0">User</label>
            <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 220px; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                @foreach($viewableUsers as $viewUser)
                <option value="{{ $viewUser->id }}" {{ (int) $selectedUserId === (int) $viewUser->id ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($viewUser->name, 28) }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('tasks.index', ['date' => $weekStart->copy()->subWeek()->toDateString(), 'user_id' => $selectedUserId]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chevron-left"></i>
        </a>
        <button class="btn btn-light btn-sm border" disabled>Work Week {{ $workWeekNumber }} ({{ $workWeekYear }}) · {{ $weekStart->format('d-m-Y') }} - {{ $weekEnd->format('d-m-Y') }}</button>
        <a href="{{ route('tasks.index', ['date' => $weekStart->copy()->addWeek()->toDateString(), 'user_id' => $selectedUserId]) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Work Week {{ $workWeekNumber }} (Mon-Fri)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="text-center">
                            <tr>
                                <th>Mon</th>
                                <th>Tue</th>
                                <th>Wed</th>
                                <th>Thu</th>
                                <th>Fri</th>
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
                                    <td class="p-2 {{ $isSelected ? 'border-primary border-2' : '' }}" style="height:110px; min-width: 120px;">
                                        <a href="{{ route('tasks.index', ['date' => $dateKey, 'user_id' => $selectedUserId]) }}" class="text-decoration-none d-block h-100">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <span class="fw-semibold text-dark">{{ $day->format('D') }} {{ $day->day }}</span>
                                                @if($dayTasks->count() > 0)
                                                <span class="badge bg-primary">{{ $dayTasks->count() }}</span>
                                                @endif
                                            </div>
                                            @if($dayTasks->where('is_completed', false)->count() > 0)
                                            <div class="small text-warning">{{ $dayTasks->where('is_completed', false)->count() }} pending</div>
                                            @elseif($dayTasks->count() > 0)
                                            <div class="small text-success">All done</div>
                                            @endif
                                        </a>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Daily Cards · {{ $selectedDateObj->format('d-m-Y') }}</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#fullCardModal">
                    <i class="bi bi-card-text me-1"></i>View Full Card
                </button>
            </div>
            <div class="card-body p-0">
                @forelse($selectedTasks as $task)
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="small fw-semibold text-primary mb-1">{{ $task->category }}</div>
                            <div class="fw-semibold {{ $task->is_completed ? 'text-decoration-line-through text-muted' : '' }}">{{ $task->title }}</div>
                            @if($task->description)
                            <div class="small text-muted mt-1">{{ $task->description }}</div>
                            @endif
                        </div>
                        <span class="badge bg-{{ $task->is_completed ? 'success' : 'warning' }}">{{ $task->is_completed ? 'Done' : 'Pending' }}</span>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTaskModal{{ $task->id }}">Edit</button>
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

                <div class="modal fade" id="editTaskModal{{ $task->id }}" tabindex="-1" aria-labelledby="editTaskModalLabel{{ $task->id }}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editTaskModalLabel{{ $task->id }}">Edit Task</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="{{ route('tasks.update', $task) }}">
                                @csrf
                                @method('PATCH')
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Task Date</label>
                                        <input type="date" name="task_date" class="form-control" value="{{ \Illuminate\Support\Carbon::parse((string) $task->task_date)->toDateString() }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Category</label>
                                        <select name="category" class="form-select" data-title-select-target="editTaskTitleSelect{{ $task->id }}" data-current-title="{{ $task->title }}" required>
                                            @foreach($taskCategories as $category)
                                            <option value="{{ $category }}" {{ $task->category === $category ? 'selected' : '' }}>{{ $category }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Title (Existing)</label>
                                        <select name="title_select" id="editTaskTitleSelect{{ $task->id }}" class="form-select"></select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Create New Title</label>
                                        <input type="text" name="title_new" class="form-control" value="" placeholder="Type to create a new title (optional)">
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-semibold">Description</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Optional details...">{{ $task->description }}</textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-4 text-center text-muted small">No tasks for this date.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Add Task</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tasks.store') }}">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Task Date</label>
                        <input type="date" name="task_date" class="form-control" value="{{ old('task_date', $selectedDate) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category" id="taskCategory" class="form-select" data-title-select-target="taskTitleSelect" required>
                            @foreach($taskCategories as $category)
                            <option value="{{ $category }}" {{ old('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title (Existing)</label>
                        <select name="title_select" id="taskTitleSelect" class="form-select"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Create New Title</label>
                        <input type="text" name="title_new" class="form-control" value="{{ old('title_new') }}" placeholder="Type to create a new title (optional)">
                        <div class="form-text">Choose existing title from dropdown or type a new title.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional details...">{{ old('description') }}</textarea>
                    </div>
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i>Add Task
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="fullCardModal" tabindex="-1" aria-labelledby="fullCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fullCardModalLabel">Full Task Card · Work Week {{ $workWeekNumber }} ({{ $workWeekYear }})</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                @php($printedCategories = collect())

                @foreach($categoryOrder as $category)
                    @php($categoryTasks = $workWeekTasksByCategory->get($category, collect()))
                    @if($categoryTasks->isNotEmpty())
                        @php($printedCategories->push($category))
                        <div class="fw-bold fs-5 text-decoration-underline mb-2">{{ $categoryLabel[$category] ?? $category }}</div>
                        @php($tasksByTitle = $categoryTasks->groupBy('title'))
                        @foreach($tasksByTitle as $title => $titleTasks)
                            <div class="fw-semibold mb-1 ms-3">{{ $title }}</div>
                            <div class="mb-3 ps-4">
                                @foreach($titleTasks as $task)
                                <div class="mb-1 d-flex justify-content-between align-items-start gap-2">
                                    <span>• {{ $task->description ?: $task->title }}</span>
                                    <span class="text-{{ $task->is_completed ? 'success' : 'warning' }} fw-semibold">{{ $task->is_completed ? 'Done' : 'Pending' }}</span>
                                </div>
                                @endforeach
                            </div>
                        @endforeach
                    @endif
                @endforeach

                @foreach($workWeekTasksByCategory as $category => $categoryTasks)
                    @if(!$printedCategories->contains($category) && $categoryTasks->isNotEmpty())
                        <div class="fw-bold fs-5 text-decoration-underline mb-2">{{ $category }}</div>
                        @php($tasksByTitle = $categoryTasks->groupBy('title'))
                        @foreach($tasksByTitle as $title => $titleTasks)
                            <div class="fw-semibold mb-1 ms-3">{{ $title }}</div>
                            <div class="mb-3 ps-4">
                                @foreach($titleTasks as $task)
                                <div class="mb-1 d-flex justify-content-between align-items-start gap-2">
                                    <span>• {{ $task->description ?: $task->title }}</span>
                                    <span class="text-{{ $task->is_completed ? 'success' : 'warning' }} fw-semibold">{{ $task->is_completed ? 'Done' : 'Pending' }}</span>
                                </div>
                                @endforeach
                            </div>
                        @endforeach
                    @endif
                @endforeach

                @if($workWeekTasks->isEmpty())
                <div class="text-center text-muted py-4">No tasks for this work week.</div>
                @endif
            </div>
        </div>
    </div>
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
