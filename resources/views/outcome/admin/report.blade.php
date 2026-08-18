@extends('layouts.app')

@section('title', 'Outcome Based Report - IT Asset Management')
@section('page-title', 'Outcome Based Report')

@section('content')
<div class="mb-6">
    <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Outcome Based Report</h5>
    <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">All tasks logged by normal-role users.</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('outcome.report') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
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
                <label class="field-label">From</label>
                <input type="date" name="date_from" class="field-input" value="{{ request('date_from') }}">
            </div>
            <div>
                <label class="field-label">To</label>
                <input type="date" name="date_to" class="field-input" value="{{ request('date_to') }}">
            </div>
            <div class="flex gap-2 lg:col-span-5">
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
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">User</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Start Date</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">End Date</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Task</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Department</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Man Hour</th>
                    <th class="border-b border-slate-200 p-3 text-slate-500 dark:border-slate-800 dark:text-slate-400">Outcome</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr class="border-b border-slate-100 last:border-b-0 dark:border-slate-800">
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->user->name ?? '-' }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->start_date->format('d-m-Y') }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->end_date->format('d-m-Y') }}</td>
                    <td class="p-3 font-semibold text-slate-800 dark:text-slate-100">{{ $task->category }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ $task->department ?: '-' }}</td>
                    <td class="p-3 text-slate-700 dark:text-slate-300">{{ rtrim(rtrim(number_format((float) $task->man_hour, 2), '0'), '.') }}</td>
                    <td class="p-3">
                        <span class="badge badge-{{ $task->is_completed ? 'success' : 'warning' }}">{{ $task->is_completed ? 'Completed' : 'Not Completed' }}</span>
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
