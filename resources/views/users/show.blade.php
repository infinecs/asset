@extends('layouts.app')
@section('title', $user->name . ' - Users')
@section('page-title', 'User Profile')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $user->name }}</h5>
    <div class="flex gap-2">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i>Edit</a>
        <a href="{{ route('users.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
    </div>
</div>
<div class="card w-full max-w-[480px] p-6 text-center">
    <div class="mx-auto mb-3 flex items-center justify-center rounded-full bg-primary-600" style="width:64px;height:64px;">
        <span class="text-2xl font-bold text-white">{{ substr($user->name,0,1) }}</span>
    </div>
    <h5 class="mb-1 font-semibold text-slate-900 dark:text-white">{{ $user->name }}</h5>
    <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
    <span class="badge badge-{{ $user->role == 'admin' ? 'danger' : 'warning' }} mx-auto mb-3">{{ ucfirst($user->role) }}</span>
    <dl class="space-y-2 text-left text-sm">
        <div><dt class="text-slate-500 dark:text-slate-400">Department</dt><dd class="font-semibold text-slate-800 dark:text-slate-100">{{ $user->department ?? '-' }}</dd></div>
        <div><dt class="text-slate-500 dark:text-slate-400">Phone</dt><dd class="font-semibold text-slate-800 dark:text-slate-100">{{ $user->phone ?? '-' }}</dd></div>
        <div><dt class="text-slate-500 dark:text-slate-400">Joined</dt><dd class="font-semibold text-slate-800 dark:text-slate-100">{{ $user->created_at->format('d M Y') }}</dd></div>
    </dl>
</div>
@endsection
