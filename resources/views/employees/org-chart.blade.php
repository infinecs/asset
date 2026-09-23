@extends('layouts.app')
@section('title', 'Org Chart')
@section('page-title', 'Organization Chart')
@section('content')
<div class="mb-6 flex items-center justify-between print:hidden">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Organization Chart</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Visual reporting structure of active employees</p>
    </div>
    <div class="flex gap-2">
        <button type="button" class="btn btn-outline btn-sm" onclick="window.print()"><i class="bi bi-printer"></i>Print / Export PDF</button>
        <a href="{{ route('employees.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
    </div>
</div>

<div class="card print-plain">
    <div class="card-body overflow-x-auto">
        @forelse($roots as $root)
        <div class="mb-8 flex justify-[safe_center] last:mb-0">
            <ul class="org-tree">
                @include('employees._org-chart-node', ['employee' => $root, 'byManager' => $byManager])
            </ul>
        </div>
        @empty
        <div class="py-8 text-center text-slate-500 dark:text-slate-400">
            <i class="bi bi-diagram-3 mb-2 block text-2xl"></i>
            No active employees to display yet.
        </div>
        @endforelse
    </div>
</div>
@endsection
