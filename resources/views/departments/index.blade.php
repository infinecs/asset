@extends('layouts.app')
@section('title', 'Departments')
@section('page-title', 'Departments')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Departments</h5>
    <a href="{{ route('departments.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i>Add Department</a>
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
                        <div class="inline-flex gap-1">
                            <a href="{{ route('departments.edit', $department) }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="2" class="py-8 text-center text-slate-500 dark:text-slate-400">No departments yet. <a href="{{ route('departments.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
