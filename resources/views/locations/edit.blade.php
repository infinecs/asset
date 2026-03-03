@extends('layouts.app')
@section('title', 'Edit Location')
@section('page-title', 'Edit Location')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-semibold mb-0">Edit: {{ $location->name }}</h5>
                <a href="{{ route('locations.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('locations.update', $location) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $location->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Building</label>
                            <input type="text" name="building" class="form-control" value="{{ old('building', $location->building) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Floor</label>
                            <input type="text" name="floor" class="form-control" value="{{ old('floor', $location->floor) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Room</label>
                            <input type="text" name="room" class="form-control" value="{{ old('room', $location->room) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $location->description) }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-2"></i>Save</button>
                        <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
