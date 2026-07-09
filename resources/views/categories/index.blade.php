@extends('layouts.app')
@section('title', 'Categories')
@section('page-title', 'Asset Categories')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Categories</h5>
    <a href="{{ route('categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Add Category</a>
</div>
<div class="card border-0 shadow-sm" data-bulk-container>
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <span class="text-muted small"><span data-bulk-count>0</span> selected</span>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('categories.bulk-destroy') }}" data-bulk-form onsubmit="return confirm('Delete selected categories?')">
                @csrf
                @method('DELETE')
                <span data-bulk-inputs></span>
                <button type="submit" class="btn btn-outline-danger btn-sm" data-bulk-delete-selected>
                    <i class="bi bi-trash me-1"></i>Delete Selected
                </button>
            </form>
            <form method="POST" action="{{ route('categories.destroy-all') }}" onsubmit="return confirm('Delete all categories? This cannot be undone.')">
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
            <thead>
                <tr><th style="width: 40px;"><input type="checkbox" class="form-check-input" data-bulk-select-all></th><th>Name</th><th>Description</th><th>Assets</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td style="width: 40px;"><input type="checkbox" class="form-check-input" data-bulk-row value="{{ $cat->id }}"></td>
                    <td class="fw-semibold">{{ $cat->name }}</td>
                    <td class="text-muted">{{ $cat->description ?? '-' }}</td>
                    <td><span class="badge bg-primary">{{ $cat->assets_count }}</span></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('categories.edit', $cat) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No categories yet. <a href="{{ route('categories.create') }}">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
