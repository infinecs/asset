@extends('layouts.app')

@section('title', 'New Request - IT Asset Management')
@section('page-title', 'Submit Request Ticket')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-semibold mb-0">New Request Ticket</h5>
                <p class="text-muted small mb-0">Submit a request for asset support or procurement</p>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('tickets.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Request Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">Select type...</option>
                                <option value="new_asset" {{ old('type') == 'new_asset' ? 'selected' : '' }}>New Asset Request</option>
                                <option value="repair" {{ old('type') == 'repair' ? 'selected' : '' }}>Repair / Fix</option>
                                <option value="replacement" {{ old('type') == 'replacement' ? 'selected' : '' }}>Replacement</option>
                                <option value="return" {{ old('type') == 'return' ? 'selected' : '' }}>Return Asset</option>
                                <option value="transfer" {{ old('type') == 'transfer' ? 'selected' : '' }}>Transfer Asset</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category (optional)</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select category...</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject') }}" placeholder="Brief description of your request" required>
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                      rows="5" placeholder="Please provide detailed information about your request..." required>{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-2"></i>Submit Request
                        </button>
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
