@extends('layouts.app')

@section('title', 'Edit Ticket - IT Asset Management')
@section('page-title', 'Edit Ticket')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-semibold mb-0">{{ $ticket->subject }}</h5>
                    <code class="text-muted">{{ $ticket->ticket_number }}</code>
                </div>
                <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('tickets.update', $ticket) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Request Type</label>
                            <select name="type" class="form-select" required>
                                @foreach(['new_asset' => 'New Asset Request', 'repair' => 'Repair / Fix', 'replacement' => 'Replacement', 'return' => 'Return Asset', 'transfer' => 'Transfer Asset'] as $val => $label)
                                <option value="{{ $val }}" {{ old('type', $ticket->type) == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Priority</label>
                            <select name="priority" class="form-select" required>
                                @foreach(['low', 'medium', 'high', 'critical'] as $p)
                                <option value="{{ $p }}" {{ old('priority', $ticket->priority) == $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach(['open', 'in_progress', 'resolved', 'closed', 'rejected'] as $s)
                                <option value="{{ $s }}" {{ old('status', $ticket->status) == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Subject</label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject', $ticket->subject) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Related Asset</label>
                            <select name="asset_id" class="form-select">
                                <option value="">None</option>
                                @foreach($assets as $asset)
                                <option value="{{ $asset->id }}" {{ old('asset_id', $ticket->asset_id) == $asset->id ? 'selected' : '' }}>
                                    {{ $asset->name }} ({{ $asset->asset_tag }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned To (Staff)</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach($staffUsers as $staff)
                                <option value="{{ $staff->id }}" {{ old('assigned_to', $ticket->assigned_to) == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }} ({{ ucfirst($staff->role) }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select category...</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $ticket->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="4" required>{{ old('description', $ticket->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Resolution Notes</label>
                            <textarea name="resolution_notes" class="form-control" rows="3"
                                      placeholder="Add resolution notes...">{{ old('resolution_notes', $ticket->resolution_notes) }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                        <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-outline-secondary px-4">Cancel</a>
                        @if(auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" class="ms-auto"
                              onsubmit="return confirm('Delete this ticket permanently?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger px-4"><i class="bi bi-trash me-1"></i>Delete</button>
                        </form>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
