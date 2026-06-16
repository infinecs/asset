@extends('layouts.app')
@section('title', $employee->name . ' - Employees')
@section('page-title', 'Employee Profile')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">{{ $employee->name }}</h5>
    <div class="d-flex gap-2">
        @if(auth()->user()->isAdmin())
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endif
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;">
                    <span class="text-white fw-bold fs-3">{{ substr($employee->name, 0, 1) }}</span>
                </div>
                <h5 class="mb-1">{{ $employee->name }}</h5>
                <p class="text-muted mb-3">{{ $employee->email }}</p>
                <dl class="text-start small row mb-0">
                    <dt class="col-sm-5 text-muted">ID Number</dt>
                    <dd class="col-sm-7"><code>{{ $employee->id_number }}</code></dd>
                    <dt class="col-sm-5 text-muted">Work Location</dt>
                    <dd class="col-sm-7">{{ $employee->work_location ?? '-' }}</dd>
                    <dt class="col-sm-5 text-muted">Added</dt>
                    <dd class="col-sm-7">{{ $employee->created_at->format('d M Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Assigned Assets ({{ $employee->assets->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Asset Tag</th><th>Name</th><th>Category</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($employee->assets as $asset)
                        <tr>
                            <td><code class="text-primary small">{{ $asset->asset_tag }}</code></td>
                            <td class="fw-semibold small">{{ $asset->name }}</td>
                            <td class="small text-muted">{{ $asset->category?->name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $asset->status_badge }}">{{ $asset->status_label }}</span></td>
                            <td><a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No assets assigned</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
