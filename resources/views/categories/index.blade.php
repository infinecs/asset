@extends('layouts.app')
@section('title', 'Categories')
@section('page-title', 'Asset Categories')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Categories</h5>
    <a href="{{ route('categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i>Add Category</a>
</div>
<div class="card" data-bulk-container>
    <div class="card-header">
        <span class="text-sm text-slate-500 dark:text-slate-400"><span data-bulk-count>0</span> selected</span>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('categories.bulk-destroy') }}" data-bulk-form onsubmit="return confirm('Delete selected categories?')">
                @csrf
                @method('DELETE')
                <span data-bulk-inputs></span>
                <button type="submit" class="btn btn-outline-danger btn-sm" data-bulk-delete-selected>
                    <i class="bi bi-trash"></i>Delete Selected
                </button>
            </form>
            <form method="POST" action="{{ route('categories.destroy-all') }}" onsubmit="return confirm('Delete all categories? This cannot be undone.')">
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
                <tr><th class="w-10"><input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" data-bulk-select-all></th><th>Name</th><th>Description</th><th>Assets</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td><input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" data-bulk-row value="{{ $cat->id }}"></td>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $cat->name }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $cat->description ?? '-' }}</td>
                    <td><span class="badge badge-primary">{{ $cat->assets_count }}</span></td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('categories.edit', $cat) }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-8 text-center text-slate-500 dark:text-slate-400">No categories yet. <a href="{{ route('categories.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
