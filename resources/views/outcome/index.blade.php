@extends('layouts.app')

@section('title', 'Outcome Based - IT Asset Management')
@section('page-title', 'Outcome Based')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3" x-data>
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Outcome Based</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Add your tasks and mark their outcome.</p>
    </div>
    <button type="button" class="btn btn-primary" @click="$dispatch('open-modal', 'addOutcomeTaskModal')">
        <i class="bi bi-plus-circle"></i>Add Task
    </button>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="text-left">
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Start Date</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">End Date</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Task</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Department</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Description</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Man Hour</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Outcome</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr class="border-b border-slate-100 last:border-b-0 dark:border-slate-800">
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->start_date->format('d-m-Y') }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->end_date->format('d-m-Y') }}</td>
                    <td class="p-3 font-semibold {{ $task->is_completed ? 'text-slate-400 line-through' : 'text-slate-800 dark:text-slate-100' }}">{{ $task->category }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->department ?: '-' }}</td>
                    <td class="p-3 text-slate-500 dark:text-slate-400">{{ $task->job_description }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ rtrim(rtrim(number_format((float) $task->man_hour, 2), '0'), '.') }}</td>
                    <td class="p-3">
                        <span class="badge badge-{{ $task->is_completed ? 'success' : 'warning' }}">{{ $task->is_completed ? 'Completed' : 'Not Completed' }}</span>
                    </td>
                    <td class="p-3">
                        <form method="POST" action="{{ route('outcome.toggle', $task) }}">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm btn-outline-primary">{{ $task->is_completed ? 'Mark Not Completed' : 'Mark Completed' }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">No tasks yet. Click "Add Task" to create one.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tasks->hasPages())
    <div class="border-t border-slate-200 p-3 dark:border-slate-800">
        {{ $tasks->links() }}
    </div>
    @endif
</div>

<x-ui.modal id="addOutcomeTaskModal" maxWidth="max-w-md">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h5 class="text-base font-semibold text-slate-900 dark:text-white">Add Task</h5>
        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="open = false"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST" action="{{ route('outcome.store') }}">
        @csrf
        <div class="space-y-3 p-6">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="field-label">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" class="field-input @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->toDateString()) }}" required>
                    @error('start_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="field-label">End Date <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" class="field-input @error('end_date') is-invalid @enderror" value="{{ old('end_date', now()->toDateString()) }}" required>
                    @error('end_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="field-label">Task <span class="text-red-500">*</span></label>
                <select name="category" class="field-input @error('category') is-invalid @enderror" required>
                    <option value="">-- Select Task --</option>
                    @foreach($categories as $category)
                    <option value="{{ $category }}" {{ old('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                    @endforeach
                </select>
                @error('category')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Department</label>
                <select name="department" class="field-input @error('department') is-invalid @enderror">
                    <option value="">-- Select Department --</option>
                    @foreach($departments as $department)
                    <option value="{{ $department }}" {{ old('department') == $department ? 'selected' : '' }}>{{ $department }}</option>
                    @endforeach
                </select>
                @error('department')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Man Hour <span class="text-red-500">*</span></label>
                <input type="number" step="0.5" min="0" name="man_hour" class="field-input @error('man_hour') is-invalid @enderror" value="{{ old('man_hour') }}" required>
                @error('man_hour')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label">Description</label>
                <textarea name="job_description" class="field-input" rows="3" placeholder="Optional details...">{{ old('job_description') }}</textarea>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
            <button type="button" class="btn btn-outline" @click="open = false">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Task</button>
        </div>
    </form>
</x-ui.modal>
@endsection
