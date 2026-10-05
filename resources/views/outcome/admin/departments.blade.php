@extends('layouts.app')

@section('title', 'Outcome Departments - IT Asset Management')
@section('page-title', 'Outcome Departments')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3" x-data>
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Departments</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Manage the departments available on the Outcome Based task form.</p>
    </div>
    <button type="button" class="btn btn-primary" @click="$dispatch('open-modal', 'addDepartmentModal')">
        <i class="bi bi-plus-circle"></i>Add Department
    </button>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr><th>Name</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($departments as $department)
                <tr>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $department->name }}</td>
                    <td class="text-right">
                        <div class="inline-flex gap-1" x-data>
                            <button type="button" class="btn btn-sm btn-outline btn-icon" @click="$dispatch('open-modal', 'editDepartmentModal{{ $department->id }}')"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('outcome.departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>

                <x-ui.modal id="editDepartmentModal{{ $department->id }}">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                        <h5 class="text-base font-semibold text-slate-900 dark:text-white">Edit Department</h5>
                        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="open = false"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <form method="POST" action="{{ route('outcome.departments.update', $department) }}">
                        @csrf @method('PATCH')
                        <div class="p-6">
                            <label class="field-label">Name</label>
                            <input type="text" name="name" class="field-input" value="{{ $department->name }}" required>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
                            <button type="button" class="btn btn-outline" @click="open = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </x-ui.modal>
                @empty
                <tr><td colspan="2" class="py-8 text-center text-slate-500 dark:text-slate-400">No departments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<x-ui.modal id="addDepartmentModal">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h5 class="text-base font-semibold text-slate-900 dark:text-white">Add Department</h5>
        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="open = false"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST" action="{{ route('outcome.departments.store') }}">
        @csrf
        <div class="p-6">
            <label class="field-label">Name</label>
            <input type="text" name="name" class="field-input" value="{{ old('name') }}" required>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
            <button type="button" class="btn btn-outline" @click="open = false">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Department</button>
        </div>
    </form>
</x-ui.modal>
@endsection
