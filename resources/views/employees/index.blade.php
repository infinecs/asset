@extends('layouts.app')
@section('title', 'Employees')
@section('page-title', 'Employees')
@section('content')
@php $returnTo = urlencode(request()->getRequestUri()); @endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Employees</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Manage and track all employees</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('employees.org-chart') }}" class="btn btn-outline">
            <i class="bi bi-diagram-3"></i>Org Chart
        </a>
        <a href="{{ route('employees.export', request()->query()) }}" class="btn btn-outline">
            <i class="bi bi-download"></i>Export CSV
        </a>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('employees.bulk-edit-org') }}" class="btn btn-outline">
            <i class="bi bi-diagram-3"></i>Bulk Edit Org Structure
        </a>
        <a href="{{ route('employees.bulk-edit-birthdays') }}" class="btn btn-outline">
            <i class="bi bi-calendar-heart"></i>Bulk Edit Birthdays
        </a>
        <button type="button" class="btn btn-outline" @click="$dispatch('open-modal', 'importModal')">
            <i class="bi bi-upload"></i>Import Excel
        </button>
        <a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i>Add Employee</a>
        @endif
    </div>
</div>

@php
    $advancedActive = collect(\App\Http\Controllers\EmployeeController::ADVANCED_FILTERS)->filter(fn ($key) => request()->filled($key));
    $filterLabels = [
        'role' => fn ($v) => 'Role: ' . ($filterOptions['roles']->firstWhere('id', $v)->name ?? $v),
        'team' => fn ($v) => 'Team: ' . ($filterOptions['teams']->firstWhere('id', $v)->name ?? $v),
        'team_type' => fn ($v) => 'Team type: ' . ($v === 'client_placement' ? 'Client Placement' : 'Internal'),
        'client' => fn ($v) => 'Client: ' . ($filterOptions['clients']->firstWhere('id', $v)->name ?? $v),
        'manager' => fn ($v) => 'Reports to: ' . ($filterOptions['managers']->firstWhere('id', $v)->name ?? $v),
        'location' => fn ($v) => 'Location: ' . $v,
        'is_manager' => fn ($v) => $v === 'yes' ? 'Managers only' : 'Non-managers only',
        'assets' => fn ($v) => $v === 'with' ? 'Has assets' : 'No assets',
        'dob_month' => fn ($v) => 'Birthday in ' . \Carbon\Carbon::create(null, (int) $v, 1)->format('F'),
        'missing_dob' => fn ($v) => 'Missing date of birth',
    ];
    $sort = request('sort', request()->filled('search') ? 'relevance' : 'id');
@endphp
<div class="card mb-6" x-data="{ advanced: {{ $advancedActive->isNotEmpty() ? 'true' : 'false' }} }">
    <div class="card-body">
        <form method="GET" action="{{ route('employees.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-5">
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" class="field-input pl-9" value="{{ request('search') }}" placeholder="Search name, ID, email, role, team, client, manager...">
                </div>
            </div>
            <div class="md:col-span-2">
                <select name="status" class="field-input">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="resigned" {{ request('status') === 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="sort" class="field-input" aria-label="Sort by">
                    <option value="relevance" {{ $sort === 'relevance' ? 'selected' : '' }}>Sort: Best match</option>
                    <option value="id" {{ $sort === 'id' ? 'selected' : '' }}>Sort: ID number</option>
                    <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Sort: Name A–Z</option>
                    <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>Sort: Name Z–A</option>
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Sort: Newest added</option>
                </select>
            </div>
            <div class="flex gap-2 md:col-span-3">
                <button type="submit" class="btn btn-primary flex-1">Search</button>
                <a href="{{ route('employees.index') }}" class="btn btn-outline">Clear</a>
            </div>

            {{-- Advanced Search toggle --}}
            <div class="md:col-span-12">
                <button type="button" class="btn btn-sm btn-outline" @click="advanced = !advanced">
                    <i class="bi bi-sliders"></i> Advanced Search
                    @if($advancedActive->isNotEmpty())
                    <span class="badge badge-primary">{{ $advancedActive->count() }} active</span>
                    @endif
                </button>
            </div>

            {{-- Advanced Search panel --}}
            <div class="md:col-span-12" x-show="advanced" x-collapse x-cloak>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                    <p class="mb-2 text-sm font-semibold text-slate-500 dark:text-slate-400"><i class="bi bi-diagram-3 me-1"></i>Organization</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="field-label">Role</label>
                            <select name="role" class="field-input">
                                <option value="">All Roles</option>
                                @foreach($filterOptions['roles'] as $opt)
                                <option value="{{ $opt->id }}" {{ request('role') == $opt->id ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Team</label>
                            <select name="team" class="field-input">
                                <option value="">All Teams</option>
                                @foreach($filterOptions['teams'] as $opt)
                                <option value="{{ $opt->id }}" {{ request('team') == $opt->id ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Team Type</label>
                            <select name="team_type" class="field-input">
                                <option value="">All Types</option>
                                <option value="internal" {{ request('team_type') === 'internal' ? 'selected' : '' }}>Internal</option>
                                <option value="client_placement" {{ request('team_type') === 'client_placement' ? 'selected' : '' }}>Client Placement</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Client</label>
                            <select name="client" class="field-input">
                                <option value="">All Clients</option>
                                @foreach($filterOptions['clients'] as $opt)
                                <option value="{{ $opt->id }}" {{ request('client') == $opt->id ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Reports To</label>
                            <select name="manager" class="field-input">
                                <option value="">Any Manager</option>
                                @foreach($filterOptions['managers'] as $opt)
                                <option value="{{ $opt->id }}" {{ request('manager') == $opt->id ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Manager</label>
                            <select name="is_manager" class="field-input">
                                <option value="">Everyone</option>
                                <option value="yes" {{ request('is_manager') === 'yes' ? 'selected' : '' }}>Managers only</option>
                                <option value="no" {{ request('is_manager') === 'no' ? 'selected' : '' }}>Non-managers only</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Work Location</label>
                            <select name="location" class="field-input">
                                <option value="">All Locations</option>
                                @foreach($filterOptions['locations'] as $opt)
                                <option value="{{ $opt }}" {{ request('location') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <p class="mb-2 mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400"><i class="bi bi-person-lines-fill me-1"></i>Records</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="field-label">Assigned Assets</label>
                            <select name="assets" class="field-input">
                                <option value="">Any</option>
                                <option value="with" {{ request('assets') === 'with' ? 'selected' : '' }}>Has assets</option>
                                <option value="without" {{ request('assets') === 'without' ? 'selected' : '' }}>No assets</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Birthday Month</label>
                            <select name="dob_month" class="field-input">
                                <option value="">Any Month</option>
                                @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ request('dob_month') == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 pb-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="checkbox" name="missing_dob" value="1" class="rounded border-slate-300" {{ request('missing_dob') === '1' ? 'checked' : '' }}>
                                Missing date of birth
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if($advancedActive->isNotEmpty() || request()->filled('search'))
        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
            <span class="text-slate-500 dark:text-slate-400">{{ $employees->total() }} {{ Str::plural('result', $employees->total()) }}</span>
            @if(request()->filled('search'))
            <a href="{{ route('employees.index', request()->except(['search', 'page'])) }}" class="badge badge-primary no-underline" title="Remove">“{{ request('search') }}” <i class="bi bi-x"></i></a>
            @endif
            @foreach($advancedActive as $key)
            <a href="{{ route('employees.index', request()->except([$key, 'page'])) }}" class="badge badge-secondary no-underline" title="Remove">{{ $filterLabels[$key](request($key)) }} <i class="bi bi-x"></i></a>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID Number</th>
                    <th>Role</th>
                    <th>Team</th>
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
                    <td class="text-slate-500 dark:text-slate-400">
                        @if($employee->role)
                        <span class="badge badge-primary">{{ $employee->role->name }}</span>
                        @if($employee->is_manager)
                        <span class="badge badge-secondary"><i class="bi bi-diagram-3 me-1"></i>Manager</span>
                        @endif
                        @elseif($employee->is_manager)
                        <span class="badge badge-secondary"><i class="bi bi-diagram-3 me-1"></i>Manager</span>
                        @else
                        -
                        @endif
                    </td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $employee->team->name ?? '-' }}</td>
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
                                <option value="resigned" {{ $employee->status === 'resigned' ? 'selected' : '' }}>Resigned</option>
                            </select>
                        </form>
                        @else
                        <span class="badge badge-{{ $employee->status_badge }}">{{ $employee->status_label }}</span>
                        @endif
                    </td>
                    @if(auth()->user()->isAdmin())
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('employees.show', $employee) }}?return={{ $returnTo }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('employees.edit', $employee) }}?return={{ $returnTo }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ auth()->user()->isAdmin() ? 8 : 7 }}" class="py-8 text-center text-slate-500 dark:text-slate-400">No employees found.</td></tr>
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
