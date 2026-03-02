@extends('layouts.app')

@section('title', $ticket->ticket_number . ' - IT Asset Management')
@section('page-title', 'Ticket Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">{{ $ticket->subject }}</h5>
        <code class="text-muted">{{ $ticket->ticket_number }}</code>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->isStaff())
        <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        @endif
        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Main Info -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Ticket Information</h6>
                <div class="d-flex gap-2">
                    <span class="badge bg-{{ $ticket->priority_badge }} px-3 py-2">{{ ucfirst($ticket->priority) }}</span>
                    <span class="badge bg-{{ $ticket->status_badge }} px-3 py-2">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Ticket Number</label>
                        <div class="fw-semibold"><code>{{ $ticket->ticket_number }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Type</label>
                        <div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $ticket->type)) }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Submitted By</label>
                        <div class="fw-semibold">{{ $ticket->requester->name }}</div>
                        <div class="text-muted small">{{ $ticket->requester->department }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Assigned To</label>
                        <div class="fw-semibold">{{ $ticket->assignedStaff?->name ?? 'Unassigned' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Created</label>
                        <div class="fw-semibold">{{ $ticket->created_at->format('d M Y H:i') }}</div>
                    </div>
                    @if($ticket->resolved_at)
                    <div class="col-md-6">
                        <label class="text-muted small">Resolved</label>
                        <div class="fw-semibold text-success">{{ $ticket->resolved_at->format('d M Y H:i') }}</div>
                    </div>
                    @endif
                    @if($ticket->asset)
                    <div class="col-12">
                        <label class="text-muted small">Related Asset</label>
                        <div>
                            <a href="{{ route('assets.show', $ticket->asset) }}" class="fw-semibold text-decoration-none">
                                <i class="bi bi-laptop me-1 text-primary"></i>{{ $ticket->asset->name }}
                                <code class="text-muted ms-1">{{ $ticket->asset->asset_tag }}</code>
                            </a>
                        </div>
                    </div>
                    @endif
                    <div class="col-12">
                        <label class="text-muted small">Description</label>
                        <div class="bg-light rounded p-3 mt-1">{{ $ticket->description }}</div>
                    </div>
                    @if($ticket->resolution_notes)
                    <div class="col-12">
                        <label class="text-muted small">Resolution Notes</label>
                        <div class="bg-success bg-opacity-10 rounded p-3 mt-1 border-start border-success border-3">
                            {{ $ticket->resolution_notes }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Actions -->
    <div class="col-lg-4">
        @if(auth()->user()->isStaff() && in_array($ticket->status, ['open', 'in_progress']))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tickets.update-status', $ticket) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Status</label>
                        <select name="status" class="form-select" required>
                            <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                            <option value="rejected" {{ $ticket->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Resolution Notes</label>
                        <textarea name="resolution_notes" class="form-control" rows="3"
                                  placeholder="Add notes about the resolution...">{{ $ticket->resolution_notes }}</textarea>
                    </div>
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-arrow-repeat me-1"></i>Update Status
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Ticket Info Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Ticket Summary</h6>
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $ticket->status_badge }}">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span>
                    </dd>
                    <dt class="col-5 text-muted">Priority</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span>
                    </dd>
                    <dt class="col-5 text-muted">Type</dt>
                    <dd class="col-7">{{ ucwords(str_replace('_',' ',$ticket->type)) }}</dd>
                    <dt class="col-5 text-muted">Category</dt>
                    <dd class="col-7">{{ $ticket->category?->name ?? '-' }}</dd>
                    <dt class="col-5 text-muted">Opened</dt>
                    <dd class="col-7">{{ $ticket->created_at->diffForHumans() }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
