@extends('layouts.app')

@section('title', 'Request Tickets - IT Asset Management')
@section('page-title', 'Request Tickets')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">{{ auth()->user()->isAdmin() ? 'All Tickets' : 'Request Tickets' }}</h5>
        <p class="text-muted small mb-0">Track and manage asset request tickets</p>
    </div>
    <a href="{{ route('tickets.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-square me-2"></i>New Request
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search ticket #, subject..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="priority" class="form-select">
                    <option value="">All Priority</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                    <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="new_asset" {{ request('type') == 'new_asset' ? 'selected' : '' }}>New Asset</option>
                    <option value="repair" {{ request('type') == 'repair' ? 'selected' : '' }}>Repair</option>
                    <option value="replacement" {{ request('type') == 'replacement' ? 'selected' : '' }}>Replacement</option>
                    <option value="return" {{ request('type') == 'return' ? 'selected' : '' }}>Return</option>
                    <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Tickets Table -->
<div class="card border-0 shadow-sm" data-bulk-container>
    @if(auth()->user()->isAdmin())
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <span class="text-muted small"><span data-bulk-count>0</span> selected</span>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('tickets.bulk-destroy') }}" data-bulk-form onsubmit="return confirm('Delete selected tickets?')">
                @csrf
                @method('DELETE')
                <span data-bulk-inputs></span>
                <button type="submit" class="btn btn-outline-danger btn-sm" data-bulk-delete-selected>
                    <i class="bi bi-trash me-1"></i>Delete Selected
                </button>
            </form>
            <form method="POST" action="{{ route('tickets.destroy-all') }}" onsubmit="return confirm('Delete all tickets? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash3 me-1"></i>Delete All
                </button>
            </form>
        </div>
    </div>
    @endif
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        @if(auth()->user()->isAdmin())
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" data-bulk-select-all>
                        </th>
                        @endif
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        @if(auth()->user()->isStaff())
                        <th>Requester</th>
                        <th>Assigned To</th>
                        @endif
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr>
                        @if(auth()->user()->isAdmin())
                        <td>
                            <input type="checkbox" class="form-check-input" data-bulk-row value="{{ $ticket->id }}">
                        </td>
                        @endif
                        <td><code class="text-primary">{{ $ticket->ticket_number }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ Str::limit($ticket->subject, 45) }}</div>
                            @if($ticket->asset)
                            <div class="text-muted small"><i class="bi bi-laptop me-1"></i>{{ $ticket->asset->name }}</div>
                            @endif
                        </td>
                        <td class="text-muted small">{{ ucwords(str_replace('_', ' ', $ticket->type)) }}</td>
                        <td><span class="badge bg-{{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span></td>
                        <td><span class="badge bg-{{ $ticket->status_badge }}">{{ ucwords(str_replace('_', ' ', $ticket->status)) }}</span></td>
                        @if(auth()->user()->isStaff())
                        <td class="small">{{ $ticket->requester->name }}</td>
                        <td class="small text-muted">{{ $ticket->assignedStaff?->name ?? 'Unassigned' }}</td>
                        @endif
                        <td class="text-muted small">{{ $ticket->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 10 : (auth()->user()->isStaff() ? 9 : 7) }}" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No tickets found.
                            <a href="{{ route('tickets.create') }}">Create your first request</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($tickets->hasPages())
    <div class="card-footer bg-white">
        {{ $tickets->links() }}
    </div>
    @endif
</div>
@endsection
