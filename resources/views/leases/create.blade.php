@extends('layouts.app')

@section('title', 'Create Lease - IT Asset Management')
@section('page-title', 'Create Lease')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-semibold mb-0">New Lease Form</h5>
                <a href="{{ route('leases.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('leases.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Asset <span class="text-danger">*</span></label>
                            <select name="asset_id" class="form-select" required>
                                <option value="">Select Asset</option>
                                @foreach($assets as $asset)
                                <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                    {{ $asset->asset_tag }} - {{ $asset->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">User (Lessee) <span class="text-danger">*</span></label>
                            <select name="lessee_id" class="form-select" required>
                                <option value="">Select User</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('lessee_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lease Start</label>
                            <input type="date" name="lease_start" class="form-control" value="{{ old('lease_start') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lease End</label>
                            <input type="date" name="lease_end" class="form-control" value="{{ old('lease_end') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Terms & Conditions</label>
                            <textarea name="terms" class="form-control" rows="16" placeholder="Type leasing terms for e-sign...">{{ old('terms', "IT ASSET LEASE TERMS & CONDITIONS

1. LEASED ASSET DETAILS
This agreement covers the IT asset identified in this lease form (including serial number, asset tag, accessories, and any bundled software/licenses). The lessee confirms receipt of the asset in good working condition unless otherwise noted.

2. OWNERSHIP AND PERMITTED USE
The asset remains the sole property of the organization at all times. The lessee is granted temporary use for approved business purposes only. Personal, illegal, or unauthorized commercial use is prohibited.

3. ACCOUNTABILITY OF LESSEE
The lessee is responsible for proper handling, safe storage, and reasonable care of the asset. The lessee must prevent theft, loss, unauthorized access, and physical damage. The asset must not be shared, reassigned, sold, lent, or modified without written approval from IT/Admin.

4. SECURITY AND COMPLIANCE
The lessee must comply with all IT security policies, including password security, MFA requirements, endpoint protection, encryption, and software update practices. The lessee must not disable security controls, install unauthorized software, jailbreak/root devices, or bypass monitoring and access policies.

5. SOFTWARE, LICENSES, AND DATA
Only licensed and approved software may be installed. All business data created or stored on the leased asset belongs to the organization. The lessee must store organizational data in approved systems and follow data handling/classification requirements. IT may audit software and security compliance when required.

6. INCIDENT REPORTING
The lessee must report incidents immediately via request ticket, including loss, theft, damage, malware infection, suspected compromise, or unauthorized access. The report should be submitted as soon as possible, and no later than 24 hours after discovery.

7. MAINTENANCE AND SUPPORT
IT/Admin may require periodic maintenance, patching, inspection, or replacement. The lessee agrees to provide timely access to the asset when requested and to follow official support procedures for troubleshooting and repair.

8. RETURN CONDITIONS
The asset must be returned upon lease end date, role change, employment termination, replacement cycle, or upon written request by IT/Admin. Returned asset must include all accessories (charger, adapter, peripherals, case, SIM, etc.) and be in fair condition considering normal wear and tear.

9. DAMAGES, LOSS, AND LIABILITY
The lessee may be held accountable for negligent or intentional damage, unauthorized modifications, repeated non-compliance, unreported loss, or missing accessories. The organization reserves the right to apply internal disciplinary actions or cost recovery according to policy and applicable law.

10. PRIVACY, MONITORING, AND AUDIT
The asset may be subject to logging, endpoint monitoring, remote management, and compliance auditing for security and operational reasons. By signing, the lessee acknowledges this management and monitoring as part of organizational IT governance.

11. TERMINATION OF LEASE
This lease may be terminated by IT/Admin for policy violations, security risk, misuse, or business needs. Upon termination, the lessee must immediately stop using and promptly return the asset.

12. ACCEPTANCE
By e-signing, the lessee confirms they have read, understood, and agreed to these terms and accepts responsibility for the leased asset throughout the lease period.") }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-2"></i>Create Lease</button>
                        <a href="{{ route('leases.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
