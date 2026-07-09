@extends('layouts.app')
@section('title', 'Add Department')
@section('page-title', 'Add Department')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-4 px-4"><h5 class="fw-semibold mb-0">New Department</h5></div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('departments.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-2"></i>Create</button>
                        <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
