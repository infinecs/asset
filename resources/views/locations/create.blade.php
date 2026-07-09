@extends('layouts.app')
@section('title', 'Add Location')
@section('page-title', 'Add Location')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-4 px-4"><h5 class="fw-semibold mb-0">New Location</h5></div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('locations.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Building</label>
                            <input type="text" name="building" class="form-control" value="{{ old('building') }}" placeholder="e.g. HQ Building A">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Floor</label>
                            <input type="text" name="floor" class="form-control" value="{{ old('floor') }}" placeholder="e.g. 3rd">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Room</label>
                            <input type="text" name="room" class="form-control" value="{{ old('room') }}" placeholder="e.g. 301">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Postcode</label>
                            <input type="text" name="postcode" class="form-control" value="{{ old('postcode') }}" placeholder="e.g. 43000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city') }}" placeholder="e.g. Kajang">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">State</label>
                            <input type="text" name="state" class="form-control" value="{{ old('state') }}" placeholder="e.g. Selangor">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-2"></i>Create</button>
                        <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
