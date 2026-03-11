@extends('layouts.app')

@section('title', 'Leases - IT Asset Management')
@section('page-title', 'Leases')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">{{ auth()->user()->isStaff() ? 'All Leases' : 'My Leases' }}</h5>
        <p class="text-muted small mb-0">Create, send, and track e-sign lease forms</p>
    </div>
    @if(auth()->user()->isStaff())
    <a href="{{ route('leases.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>New Lease
    </a>
    @endif
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <h6 class="mb-0 fw-semibold">Unsigned Leases</h6>
        <form method="GET" action="{{ route('leases.index') }}" class="d-flex gap-2" style="min-width: 300px;">
            <input type="hidden" name="signed_search" value="{{ $signedSearch ?? '' }}">
            <input type="text" name="unsigned_search" class="form-control form-control-sm" value="{{ $unsignedSearch ?? '' }}" placeholder="Search unsigned leases...">
            <button type="submit" class="btn btn-sm btn-outline-primary">Search</button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Lease #</th>
                        <th>Asset</th>
                        <th>Lessee</th>
                        <th>Period</th>
                        <th>Lease Status</th>
                        <th>Laptop Status</th>
                        <th>Signed At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unsignedLeases as $lease)
                    <tr>
                        <td><code class="text-primary">{{ $lease->lease_number }}</code></td>
                        <td>{{ $lease->asset?->name ?? '-' }}</td>
                        <td>{{ $lease->lessee?->name ?? '-' }}</td>
                        <td class="small text-muted">
                            {{ $lease->lease_start?->format('d M Y') ?? '-' }}
                            <span class="mx-1">to</span>
                            {{ $lease->lease_end?->format('d M Y') ?? '-' }}
                        </td>
                        <td><span class="badge bg-{{ $lease->status_badge }}">{{ ucfirst($lease->status) }}</span></td>
                        <td>
                            @if($lease->asset)
                            <span class="badge bg-{{ $lease->asset->status_badge }}">{{ $lease->asset->status_label }}</span>
                            @else
                            -
                            @endif
                        </td>
                        <td class="small text-muted">{{ $lease->signed_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('leases.show', $lease) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->isStaff())
                                <form method="POST" action="{{ route('leases.destroy', $lease) }}" onsubmit="return confirm('Delete this lease?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No unsigned lease records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($unsignedLeases->hasPages())
    <div class="card-footer bg-white">
        {{ $unsignedLeases->links() }}
    </div>
    @endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <h6 class="mb-0 fw-semibold">Signed Leases (In Use Assets)</h6>
        <form method="GET" action="{{ route('leases.index') }}" class="d-flex gap-2" style="min-width: 300px;">
            <input type="hidden" name="unsigned_search" value="{{ $unsignedSearch ?? '' }}">
            <input type="text" name="signed_search" class="form-control form-control-sm" value="{{ $signedSearch ?? '' }}" placeholder="Search signed leases...">
            <button type="submit" class="btn btn-sm btn-outline-primary">Search</button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Lease #</th>
                        <th>Asset</th>
                        <th>Lessee</th>
                        <th>Period</th>
                        <th>Lease Status</th>
                        <th>Laptop Status</th>
                        <th>Signed At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($signedLeases as $lease)
                    <tr>
                        <td><code class="text-primary">{{ $lease->lease_number }}</code></td>
                        <td>{{ $lease->asset?->name ?? '-' }}</td>
                        <td>{{ $lease->lessee?->name ?? '-' }}</td>
                        <td class="small text-muted">
                            {{ $lease->lease_start?->format('d M Y') ?? '-' }}
                            <span class="mx-1">to</span>
                            {{ $lease->lease_end?->format('d M Y') ?? '-' }}
                        </td>
                        <td><span class="badge bg-{{ $lease->status_badge }}">{{ ucfirst($lease->status) }}</span></td>
                        <td>
                            @if($lease->asset)
                            <span class="badge bg-{{ $lease->asset->status_badge }}">{{ $lease->asset->status_label }}</span>
                            @else
                            -
                            @endif
                        </td>
                        <td class="small text-muted">{{ $lease->signed_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('leases.show', $lease) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->isStaff())
                                <form method="POST" action="{{ route('leases.destroy', $lease) }}" onsubmit="return confirm('Delete this lease?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No signed lease records with in-use assets.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($signedLeases->hasPages())
    <div class="card-footer bg-white">
        {{ $signedLeases->links() }}
    </div>
    @endif
</div>
@endsection
