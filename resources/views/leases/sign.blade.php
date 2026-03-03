@extends('layouts.app')

@section('title', 'E-Sign Lease - IT Asset Management')
@section('page-title', 'E-Sign Lease')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-semibold mb-0">E-Sign Lease {{ $lease->lease_number }}</h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info">
                    <div><strong>Asset:</strong> {{ $lease->asset?->name ?? '-' }} ({{ $lease->asset?->asset_tag ?? '-' }})</div>
                    <div><strong>Lessee:</strong> {{ $lease->lessee?->name ?? '-' }}</div>
                    <div><strong>Period:</strong> {{ $lease->lease_start?->format('d M Y') ?? '-' }} to {{ $lease->lease_end?->format('d M Y') ?? '-' }}</div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-semibold">Terms & Conditions</h6>
                    <div class="border rounded p-3 bg-light" style="white-space: pre-wrap;">{{ $lease->terms ?: 'No terms provided.' }}</div>
                </div>

                <form method="POST" action="{{ route('leases.sign.store', $lease) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name (E-Signature) <span class="text-danger">*</span></label>
                        <input type="text" name="signed_name" class="form-control" value="{{ old('signed_name', auth()->user()->name) }}" required>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="1" id="agree" name="agree" required>
                        <label class="form-check-label" for="agree">
                            I confirm this e-signature is legally binding and I agree to the lease terms above.
                        </label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success px-4"><i class="bi bi-pen me-2"></i>E-Sign Lease</button>
                        <a href="{{ route('leases.show', $lease) }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
