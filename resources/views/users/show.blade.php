@extends('layouts.app')
@section('title', $user->name . ' - Users')
@section('page-title', 'User Profile')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">{{ $user->name }}</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>
<div class="card border-0 shadow-sm text-center p-4" style="max-width:480px;">
    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:64px;height:64px;">
        <span class="text-white fw-bold fs-3">{{ substr($user->name,0,1) }}</span>
    </div>
    <h5 class="mb-1">{{ $user->name }}</h5>
    <p class="text-muted mb-2">{{ $user->email }}</p>
    <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : 'warning' }} mb-3">{{ ucfirst($user->role) }}</span>
    <dl class="text-start small">
        <dt class="text-muted">Department</dt><dd>{{ $user->department ?? '-' }}</dd>
        <dt class="text-muted">Phone</dt><dd>{{ $user->phone ?? '-' }}</dd>
        <dt class="text-muted">Joined</dt><dd>{{ $user->created_at->format('d M Y') }}</dd>
    </dl>
</div>
@endsection
