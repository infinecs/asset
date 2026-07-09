@extends('layouts.app')
@section('title', 'Departments')
@section('page-title', 'Departments')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Departments</h5>
    <a href="{{ route('departments.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Add Department</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Name</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($departments as $department)
                <tr>
                    <td class="fw-semibold">{{ $department->name }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('departments.edit', $department) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="2" class="text-center py-4 text-muted">No departments yet. <a href="{{ route('departments.create') }}">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
