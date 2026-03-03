@extends('layouts.app')
@section('title', 'Brands')
@section('page-title', 'Asset Brands')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Brands</h5>
    <a href="{{ route('brands.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Add Brand</a>
</div>
<div class="card border-0 shadow-sm" data-bulk-container>
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <span class="text-muted small"><span data-bulk-count>0</span> selected</span>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('brands.bulk-destroy') }}" data-bulk-form onsubmit="return confirm('Delete selected brands?')">
                @csrf
                @method('DELETE')
                <span data-bulk-inputs></span>
                <button type="submit" class="btn btn-outline-danger btn-sm" data-bulk-delete-selected>
                    <i class="bi bi-trash me-1"></i>Delete Selected
                </button>
            </form>
            <form method="POST" action="{{ route('brands.destroy-all') }}" onsubmit="return confirm('Delete all brands? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash3 me-1"></i>Delete All
                </button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th style="width: 40px;"><input type="checkbox" class="form-check-input" data-bulk-select-all></th><th>Name</th><th>Assets</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($brands as $brand)
                <tr>
                    <td><input type="checkbox" class="form-check-input" data-bulk-row value="{{ $brand->id }}"></td>
                    <td class="fw-semibold">{{ $brand->name }}</td>
                    <td><span class="badge bg-primary">{{ $brand->assets_count }}</span></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('brands.edit', $brand) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('brands.destroy', $brand) }}" onsubmit="return confirm('Delete this brand?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">No brands yet. <a href="{{ route('brands.create') }}">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
