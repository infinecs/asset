@extends('layouts.app')
@section('title', $user->name . ' - Users')
@section('page-title', 'User Profile')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">{{ $user->name }}</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:64px;height:64px;">
                <span class="text-white fw-bold fs-3">{{ substr($user->name,0,1) }}</span>
            </div>
            <h5 class="mb-1">{{ $user->name }}</h5>
            <p class="text-muted mb-2">{{ $user->email }}</p>
            <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'staff' ? 'warning' : 'secondary') }} mb-3">{{ ucfirst($user->role) }}</span>
            <dl class="text-start small">
                <dt class="text-muted">Department</dt><dd>{{ $user->department ?? '-' }}</dd>
                <dt class="text-muted">Phone</dt><dd>{{ $user->phone ?? '-' }}</dd>
                <dt class="text-muted">Joined</dt><dd>{{ $user->created_at->format('d M Y') }}</dd>
            </dl>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3"><h6 class="mb-0 fw-semibold">Assigned Assets ({{ $user->assets->count() }})</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Asset</th><th>Category</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($user->assets as $asset)
                        <tr>
                            <td><div class="fw-semibold small">{{ $asset->name }}</div><code class="text-muted" style="font-size:.7rem;">{{ $asset->asset_tag }}</code></td>
                            <td class="small">{{ $asset->category?->name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $asset->status_badge }}">{{ $asset->status_label }}</span></td>
                            <td><a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No assets assigned</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3"><h6 class="mb-0 fw-semibold">Recent Tickets ({{ $user->requestTickets->count() }})</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Ticket #</th><th>Subject</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($user->requestTickets->take(10) as $ticket)
                        <tr>
                            <td><code class="small">{{ $ticket->ticket_number }}</code></td>
                            <td class="small">{{ Str::limit($ticket->subject, 40) }}</td>
                            <td><span class="badge bg-{{ $ticket->status_badge }}">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span></td>
                            <td><a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No tickets submitted</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
