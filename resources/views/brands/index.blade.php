@extends('layouts.app')
@section('title', 'Brands')
@section('page-title', 'Asset Brands')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Brands</h5>
    <a href="{{ route('brands.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i>Add Brand</a>
</div>
<div class="card" data-bulk-container>
    <div class="card-header">
        <span class="text-sm text-slate-500 dark:text-slate-400"><span data-bulk-count>0</span> selected</span>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('brands.bulk-destroy') }}" data-bulk-form onsubmit="return confirm('Delete selected brands?')">
                @csrf
                @method('DELETE')
                <span data-bulk-inputs></span>
                <button type="submit" class="btn btn-outline-danger btn-sm" data-bulk-delete-selected>
                    <i class="bi bi-trash"></i>Delete Selected
                </button>
            </form>
            <form method="POST" action="{{ route('brands.destroy-all') }}" onsubmit="return confirm('Delete all brands? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash3"></i>Delete All
                </button>
            </form>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr><th class="w-10"><input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" data-bulk-select-all></th><th>Name</th><th>Assets</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($brands as $brand)
                <tr>
                    <td><input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" data-bulk-row value="{{ $brand->id }}"></td>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $brand->name }}</td>
                    <td><span class="badge badge-primary">{{ $brand->assets_count }}</span></td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('brands.edit', $brand) }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('brands.destroy', $brand) }}" onsubmit="return confirm('Delete this brand?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">No brands yet. <a href="{{ route('brands.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
