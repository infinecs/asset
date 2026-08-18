@extends('layouts.app')

@section('title', 'Task Log - IT Asset Management')
@section('page-title', 'Task Log')

@section('content')
@php
    $sortLink = function (string $column) {
        $direction = (request('sort') === $column && request('direction') === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction]);
    };
    $sortIcon = function (string $column) use ($sort, $direction) {
        if ($sort !== $column) {
            return 'bi-arrow-down-up text-slate-300 dark:text-slate-600';
        }
        return $direction === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
    };
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Task Log</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">All tasks logged by normal-role users.</p>
    </div>
    <a href="{{ route('outcome.report.export', request()->query()) }}" class="btn btn-outline">
        <i class="bi bi-download"></i>Export CSV
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('outcome.report') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <label class="field-label">User</label>
                <select name="user_id" class="field-input">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Task</label>
                <select name="category" class="field-input">
                    <option value="">All Tasks</option>
                    @foreach($categories as $category)
                    <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Department</label>
                <select name="department" class="field-input">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                    <option value="{{ $department }}" {{ request('department') === $department ? 'selected' : '' }}>{{ $department }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Status</label>
                <select name="status" class="field-input">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="not_completed" {{ request('status') === 'not_completed' ? 'selected' : '' }}>Not Completed</option>
                </select>
            </div>
            <div>
                <label class="field-label">From</label>
                <input type="date" name="date_from" class="field-input" value="{{ request('date_from') }}">
            </div>
            <div>
                <label class="field-label">To</label>
                <input type="date" name="date_to" class="field-input" value="{{ request('date_to') }}">
            </div>
            <div class="flex gap-2 lg:col-span-6">
                <button type="submit" class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('outcome.report') }}" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="mb-4">
    <span class="badge badge-primary text-sm">Total Man Hours: {{ rtrim(rtrim(number_format((float) $totalManHours, 2), '0'), '.') }}</span>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="text-left">
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400"><a href="{{ $sortLink('user') }}" class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">User <i class="bi {{ $sortIcon('user') }}"></i></a></th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400"><a href="{{ $sortLink('start_date') }}" class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">Start Date <i class="bi {{ $sortIcon('start_date') }}"></i></a></th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400"><a href="{{ $sortLink('end_date') }}" class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">End Date <i class="bi {{ $sortIcon('end_date') }}"></i></a></th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400"><a href="{{ $sortLink('category') }}" class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">Task <i class="bi {{ $sortIcon('category') }}"></i></a></th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400"><a href="{{ $sortLink('department') }}" class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">Department <i class="bi {{ $sortIcon('department') }}"></i></a></th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Description</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400"><a href="{{ $sortLink('man_hour') }}" class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">Man Hour <i class="bi {{ $sortIcon('man_hour') }}"></i></a></th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400"><a href="{{ $sortLink('is_completed') }}" class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">Outcome <i class="bi {{ $sortIcon('is_completed') }}"></i></a></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                @php $overdue = !$task->is_completed && $task->end_date->lt(today()); @endphp
                <tr class="border-b border-slate-100 last:border-b-0 dark:border-slate-800 {{ $overdue ? 'bg-red-50 dark:bg-red-950/20' : '' }}">
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->user_name ?: '-' }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->start_date->format('d-m-Y') }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->end_date->format('d-m-Y') }}</td>
                    <td class="p-3 font-semibold text-slate-800 dark:text-slate-100">{{ $task->category }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->department ?: '-' }}</td>
                    <td class="p-3 max-w-xs truncate text-slate-500 dark:text-slate-400" title="{{ $task->job_description }}">{{ $task->job_description ?: '-' }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ rtrim(rtrim(number_format((float) $task->man_hour, 2), '0'), '.') }}</td>
                    <td class="p-3">
                        @if($overdue)
                        <span class="badge badge-danger">Overdue</span>
                        @else
                        <span class="badge badge-{{ $task->is_completed ? 'success' : 'warning' }}">{{ $task->is_completed ? 'Completed' : 'Not Completed' }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">No matching tasks.</td>
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
@endsection
