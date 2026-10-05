@extends('layouts.app')
@section('title', $employee->name . ' - Org Chart')
@section('page-title', 'Organization Chart')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Reporting line and everyone under {{ $employee->name }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @include('employees._org-levels')
        <button type="button" class="btn btn-outline btn-sm" onclick="window.print()"><i class="bi bi-printer"></i>Print / Export PDF</button>
        <a href="{{ route('employees.org-chart') }}" class="btn btn-outline btn-sm"><i class="bi bi-diagram-3"></i>Full Company Chart</a>
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back to Profile</a>
    </div>
</div>

<div class="card print-plain">
    <div class="card-body overflow-x-auto">
        <div class="flex justify-[safe_center]">
            <ul class="org-tree">
                @foreach($orgAncestors as $ancestor)
                <li>
                    @include('employees._org-node-card', ['person' => $ancestor, 'chartLink' => true])
                    <ul>
                @endforeach
                @include('employees._org-chart-node', ['employee' => $employee, 'byManager' => $orgByManager, 'highlightId' => $employee->id, 'depth' => 0])
                @foreach($orgAncestors as $ancestor)
                    </ul>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
