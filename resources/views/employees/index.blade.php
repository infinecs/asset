@extends('layouts.app')
@section('title', 'Employees')
@section('page-title', 'Employees')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Employees</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Manage and track all employees</p>
    </div>
    @if(auth()->user()->isAdmin())
    <div class="flex gap-2">
        <button type="button" class="btn btn-outline" @click="$dispatch('open-modal', 'importModal')">
            <i class="bi bi-upload"></i>Import Excel
        </button>
        <a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i>Add Employee</a>
    </div>
    @endif
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('employees.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-6">
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" class="field-input pl-9" value="{{ request('search') }}" placeholder="Search name, ID number, email, work location...">
                </div>
            </div>
            <div class="md:col-span-2">
                <select name="status" class="field-input">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="retired" {{ request('status') === 'retired' ? 'selected' : '' }}>Retired</option>
                </select>
            </div>
            <div class="flex gap-2 md:col-span-4">
                <button type="submit" class="btn btn-primary flex-1">Filter</button>
                <a href="{{ route('employees.index') }}" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID Number</th>
                    <th>Work Location</th>
                    <th>Email</th>
                    <th>Status</th>
                    @if(auth()->user()->isAdmin())
                    <th class="text-right">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600">
                                <span class="text-xs font-bold text-white">{{ substr($employee->name, 0, 1) }}</span>
                            </div>
                            <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $employee->name }}</span>
                        </div>
                    </td>
                    <td><code class="text-primary-600 dark:text-primary-400">{{ $employee->id_number }}</code></td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $employee->work_location ?? '-' }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $employee->email }}</td>
                    <td>
                        @if(auth()->user()->isAdmin())
                        @php
                            $statusSelectClass = $employee->status === 'active'
                                ? 'bg-green-600 hover:bg-green-700'
                                : 'bg-slate-500 hover:bg-slate-600';
                        @endphp
                        <form method="POST" action="{{ route('employees.update-status', $employee) }}">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()"
                                    class="w-auto rounded-full border-0 px-3 py-1 text-xs font-medium text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1 {{ $statusSelectClass }}">
                                <option value="active" {{ $employee->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="retired" {{ $employee->status === 'retired' ? 'selected' : '' }}>Retired</option>
                            </select>
                        </form>
                        @else
                        <span class="badge badge-{{ $employee->status_badge }}">{{ $employee->status_label }}</span>
                        @endif
                    </td>
                    @if(auth()->user()->isAdmin())
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}" class="py-8 text-center text-slate-500 dark:text-slate-400">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
    <div class="card-footer">
        {{ $employees->links() }}
    </div>
    @endif
</div>

{{-- Import Modal --}}
@if(auth()->user()->isAdmin())
<x-ui.modal id="importModal">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h5 class="text-base font-semibold text-slate-900 dark:text-white"><i class="bi bi-upload me-2"></i>Import Employees</h5>
        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="open = false"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="p-6">
        <div class="alert alert-success mb-4 !border-slate-200 !bg-slate-50 !text-slate-700 dark:!border-slate-700 dark:!bg-slate-800 dark:!text-slate-300">
            <i class="bi bi-info-circle mt-0.5"></i>
            <div>
                <p class="mb-1 text-sm font-semibold">CSV Format</p>
                <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">Upload a <strong>.csv</strong> file with these columns in order:</p>
                <ol class="mb-2 list-decimal space-y-0.5 pl-4 text-sm text-slate-500 dark:text-slate-400">
                    <li><strong>Name</strong> — full name (required)</li>
                    <li><strong>ID Suffix</strong> — the part after <code>INF</code>, e.g. <code>0161</code> (required)</li>
                    <li><strong>Work Location</strong> — location name (optional)</li>
                    <li><strong>Email</strong> — email address (required)</li>
                </ol>
                <a href="{{ route('employees.template') }}" class="btn btn-sm btn-outline">
                    <i class="bi bi-download"></i>Download Template
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('employees.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="field-label">Select CSV File <span class="text-red-500">*</span></label>
                <input type="file" name="file" class="field-input" accept=".csv,.txt" required>
                <p class="field-hint">Max 5 MB. Save your Excel file as <em>CSV UTF-8</em> before uploading.</p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-outline" @click="open = false">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i>Import</button>
            </div>
        </form>
    </div>
</x-ui.modal>
@endif
@endsection
