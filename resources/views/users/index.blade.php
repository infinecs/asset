@extends('layouts.app')
@section('title', 'Users')
@section('page-title', 'User Management')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Users</h5>
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Add User</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Assets</th><th>Tickets</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                <span class="text-white fw-bold" style="font-size:.75rem;">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <span class="fw-semibold">{{ $user->name }}</span>
                            @if($user->id === auth()->id())<span class="badge bg-light text-muted ms-1">You</span>@endif
                        </div>
                    </td>
                    <td class="text-muted">{{ $user->email }}</td>
                    <td>
                        <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'staff' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="text-muted">{{ $user->department ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $user->assets_count }}</span></td>
                    <td><span class="badge bg-light text-dark">{{ $user->request_tickets_count }}</span></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('users.show', $user) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
