@extends('layouts.app')
@section('title', 'Employees')
@section('page-title', 'Employees')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">Employees</h5>
        <p class="text-muted small mb-0">Manage and track all employees</p>
    </div>
    @if(auth()->user()->isAdmin())
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload me-2"></i>Import Excel
        </button>
        <a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Add Employee</a>
    </div>
    @endif
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('employees.index') }}" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search name, ID number, email, work location...">
                </div>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID Number</th>
                    <th>Work Location</th>
                    <th>Email</th>
                    @if(auth()->user()->isAdmin())
                    <th class="text-end">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                <span class="text-white fw-bold" style="font-size:.75rem;">{{ substr($employee->name, 0, 1) }}</span>
                            </div>
                            <span class="fw-semibold">{{ $employee->name }}</span>
                        </div>
                    </td>
                    <td><code class="text-primary">{{ $employee->id_number }}</code></td>
                    <td class="text-muted">{{ $employee->work_location ?? '-' }}</td>
                    <td class="text-muted">{{ $employee->email }}</td>
                    @if(auth()->user()->isAdmin())
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ auth()->user()->isAdmin() ? 5 : 4 }}" class="text-center py-4 text-muted">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
    <div class="card-footer bg-transparent">
        {{ $employees->links() }}
    </div>
    @endif
</div>

{{-- Import Modal --}}
@if(auth()->user()->isAdmin())
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-semibold"><i class="bi bi-upload me-2"></i>Import Employees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary border mb-4">
                    <p class="small fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>CSV Format</p>
                    <p class="small text-muted mb-2">Upload a <strong>.csv</strong> file with these columns in order:</p>
                    <ol class="small text-muted mb-2">
                        <li><strong>Name</strong> — full name (required)</li>
                        <li><strong>ID Suffix</strong> — the part after <code>INF</code>, e.g. <code>001</code> (required)</li>
                        <li><strong>Work Location</strong> — location name (optional)</li>
                        <li><strong>Email</strong> — email address (required)</li>
                    </ol>
                    <a href="{{ route('employees.template') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download me-1"></i>Download Template
                    </a>
                </div>

                <form method="POST" action="{{ route('employees.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">Max 5 MB. Save your Excel file as <em>CSV UTF-8</em> before uploading.</div>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
