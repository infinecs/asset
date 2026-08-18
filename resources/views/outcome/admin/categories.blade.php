@extends('layouts.app')

@section('title', 'Outcome Categories - IT Asset Management')
@section('page-title', 'Outcome Categories')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3" x-data>
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Task Categories</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Manage the categories available on the Outcome Based task form.</p>
    </div>
    <button type="button" class="btn btn-primary" @click="$dispatch('open-modal', 'addCategoryModal')">
        <i class="bi bi-plus-circle"></i>Add Category
    </button>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr><th>Name</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $category->name }}</td>
                    <td class="text-right">
                        <div class="inline-flex gap-1" x-data>
                            <button type="button" class="btn btn-sm btn-outline btn-icon" @click="$dispatch('open-modal', 'editCategoryModal{{ $category->id }}')"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('outcome.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>

                <x-ui.modal id="editCategoryModal{{ $category->id }}">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                        <h5 class="text-base font-semibold text-slate-900 dark:text-white">Edit Category</h5>
                        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="open = false"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <form method="POST" action="{{ route('outcome.categories.update', $category) }}">
                        @csrf @method('PATCH')
                        <div class="p-6">
                            <label class="field-label">Name</label>
                            <input type="text" name="name" class="field-input" value="{{ $category->name }}" required>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
                            <button type="button" class="btn btn-outline" @click="open = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </x-ui.modal>
                @empty
                <tr><td colspan="2" class="py-8 text-center text-slate-500 dark:text-slate-400">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<x-ui.modal id="addCategoryModal">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h5 class="text-base font-semibold text-slate-900 dark:text-white">Add Category</h5>
        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="open = false"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST" action="{{ route('outcome.categories.store') }}">
        @csrf
        <div class="p-6">
            <label class="field-label">Name</label>
            <input type="text" name="name" class="field-input" value="{{ old('name') }}" required>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
            <button type="button" class="btn btn-outline" @click="open = false">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Category</button>
        </div>
    </form>
</x-ui.modal>
@endsection
