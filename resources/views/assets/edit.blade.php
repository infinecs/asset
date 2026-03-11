@extends('layouts.app')

@section('title', 'Edit Asset - IT Asset Management')
@section('page-title', 'Edit Asset')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-semibold mb-0">{{ $asset->name }}</h5>
                    <code class="text-muted">{{ $asset->asset_tag }}</code>
                </div>
                <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('assets.update', $asset) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Asset Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $asset->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach(['available', 'in_use', 'under_maintenance', 'retired', 'lost'] as $s)
                                <option value="{{ $s }}" {{ old('status', $asset->status) == $s ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $s)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id', $asset->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Model</label>
                            <input type="text" name="model" class="form-control" value="{{ old('model', $asset->model) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number', $asset->serial_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $asset->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location</label>
                            <select name="location_id" class="form-select">
                                <option value="">Select Location</option>
                                @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id', $asset->location_id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12"><hr class="my-1"><p class="text-muted small mb-0">Purchase Information</p></div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Purchase Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">MYR</span>
                                <input type="number" name="purchase_cost" class="form-control" value="{{ old('purchase_cost', $asset->purchase_cost) }}" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Warranty Expiry</label>
                            <input type="date" name="warranty_expiry" class="form-control" value="{{ old('warranty_expiry', $asset->warranty_expiry?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Asset Photo</label>
                            @if($asset->photo_path)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $asset->photo_path) }}" alt="{{ $asset->name }}" class="rounded border" style="width: 120px; height: 120px; object-fit: cover;">
                            </div>
                            @endif
                            <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $asset->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                        <a href="{{ route('assets.show', $asset) }}" class="btn btn-outline-secondary px-4">Cancel</a>
                        @if(auth()->user()->isAdmin())
                        <button type="submit" form="deleteAssetForm" class="btn btn-outline-danger px-4 ms-auto" onclick="return confirm('Delete this asset? This cannot be undone.')">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endif
                    </div>
                </form>

                @if(auth()->user()->isAdmin())
                <form id="deleteAssetForm" method="POST" action="{{ route('assets.destroy', $asset) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
