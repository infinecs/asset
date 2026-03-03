@extends('layouts.app')

@section('title', 'Lease Details - IT Asset Management')
@section('page-title', 'Lease Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">{{ $lease->lease_number }}</h5>
        <p class="text-muted small mb-0">Lease status: <span class="badge bg-{{ $lease->status_badge }}">{{ ucfirst($lease->status) }}</span></p>
    </div>
    <a href="{{ route('leases.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Lease Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 small">
                    <div class="col-md-6"><span class="text-muted d-block">Asset</span><span class="fw-semibold">{{ $lease->asset?->name ?? '-' }}</span></div>
                    <div class="col-md-6"><span class="text-muted d-block">Asset Tag</span><span class="fw-semibold">{{ $lease->asset?->asset_tag ?? '-' }}</span></div>
                    <div class="col-md-6"><span class="text-muted d-block">Lessee</span><span class="fw-semibold">{{ $lease->lessee?->name ?? '-' }}</span></div>
                    <div class="col-md-6"><span class="text-muted d-block">Issued By</span><span class="fw-semibold">{{ $lease->issuer?->name ?? '-' }}</span></div>
                    <div class="col-md-6"><span class="text-muted d-block">Lease Start</span><span class="fw-semibold">{{ $lease->lease_start?->format('d M Y') ?? '-' }}</span></div>
                    <div class="col-md-6"><span class="text-muted d-block">Lease End</span><span class="fw-semibold">{{ $lease->lease_end?->format('d M Y') ?? '-' }}</span></div>
                    <div class="col-md-6"><span class="text-muted d-block">Signed At</span><span class="fw-semibold">{{ $lease->signed_at?->format('d M Y H:i') ?? '-' }}</span></div>
                    <div class="col-md-6"><span class="text-muted d-block">E-Signed Name</span><span class="fw-semibold">{{ $lease->signed_name ?? '-' }}</span></div>
                    <div class="col-md-6"><span class="text-muted d-block">Returned At</span><span class="fw-semibold">{{ $lease->returned_at?->format('d M Y H:i') ?? '-' }}</span></div>
                    <div class="col-md-6"><span class="text-muted d-block">Returned By</span><span class="fw-semibold">{{ $lease->returnedBy?->name ?? '-' }}</span></div>
                    @if($lease->returned_notes)
                    <div class="col-12"><span class="text-muted d-block">Return Notes</span><span class="fw-semibold">{{ $lease->returned_notes }}</span></div>
                    @endif
                </div>
            </div>
        </div>

        @if(auth()->user()->isAdmin() && $lease->signed_at && !$lease->returned_at)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Return Asset</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('leases.return', $lease) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Return Notes</label>
                        <textarea name="returned_notes" class="form-control" rows="2" placeholder="Optional notes about return..."></textarea>
                    </div>
                    <button class="btn btn-outline-success btn-sm" onclick="return confirm('Mark this lease as returned?')">
                        <i class="bi bi-box-arrow-in-left me-1"></i>Mark Returned
                    </button>
                </form>
            </div>
        </div>
        @endif

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Terms & Conditions</h6>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-wrap;">{{ $lease->terms ?: 'No terms provided.' }}</p>
            </div>
        </div>
    </div>

    @if($lease->status === 'pending')
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Signing Link</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small">Send this HTTPS link to the user for login and e-sign:</p>
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" value="{{ $signUrl }}" readonly>
                </div>
                @if(auth()->id() === $lease->lessee_id && $lease->status === 'pending')
                <a href="{{ route('leases.sign', $lease) }}" class="btn btn-primary btn-sm mt-3" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-pen me-1"></i>Open E-Sign Page
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
