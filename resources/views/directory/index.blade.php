@extends('layouts.app')

@section('title', 'Directory - IT Asset Management')
@section('page-title', 'Directory')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Organization Directory</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Centralized internal contact list for all staff</p>
    </div>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('directory-contacts.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus"></i>Add Directory Contact
    </a>
    @endif
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-7">
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" class="field-input pl-9" placeholder="Search name, email, department, phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="md:col-span-3">
                <select name="location" class="field-input">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                    <option value="{{ $location->id }}" {{ request('location') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="btn btn-primary flex-1">Search</button>
                <a href="{{ route('directory.index') }}" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Name (System User)</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center justify-center rounded-full bg-primary-600" style="width:30px;height:30px;">
                                <span class="text-xs font-bold text-white">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $user->name }}</div>
                                @if($user->id === auth()->id())
                                <div class="text-xs text-slate-500 dark:text-slate-400">You</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->department ?? '-' }}</td>
                    <td>{{ $user->phone ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $user->role === 'admin' ? 'danger' : 'warning' }}">{{ ucfirst($user->role) }}</span>
                    </td>
                    <td class="text-right">
                        <button type="button" class="btn btn-sm btn-outline copy-email-btn" data-email="{{ $user->email }}" title="Copy email">
                            <i class="bi bi-clipboard"></i>Copy Email
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-500 dark:text-slate-400">
                        <i class="bi bi-inbox mb-2 block text-3xl"></i>
                        No directory entries found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer">
        {{ $users->links() }}
    </div>
    @endif
</div>

<div class="card mt-6">
    <div class="card-header">
        <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Additional Directory Contacts</h6>
        <span class="text-sm text-slate-500 dark:text-slate-400">Non-user contacts</span>
    </div>
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                <tr>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $contact->name }}</td>
                    <td>{{ $contact->email }}</td>
                    <td>{{ $contact->department ?? '-' }}</td>
                    <td>{{ $contact->phone ?? '-' }}</td>
                    <td>{{ $contact->location?->name ?? '-' }}</td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline copy-email-btn" data-email="{{ $contact->email }}" title="Copy email">
                                <i class="bi bi-clipboard"></i>Copy Email
                            </button>
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('directory-contacts.edit', $contact) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('directory-contacts.destroy', $contact) }}" onsubmit="return confirm('Delete this contact?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-slate-500 dark:text-slate-400">
                        No additional directory contacts found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.copy-email-btn').forEach(function (button) {
            button.addEventListener('click', async function () {
                const email = button.getAttribute('data-email') || '';
                if (!email) {
                    return;
                }

                const originalHtml = button.innerHTML;
                const showState = function (html, btnClass) {
                    button.innerHTML = html;
                    button.classList.remove('btn-outline', 'btn-success', 'btn-danger');
                    button.classList.add(btnClass);
                };

                try {
                    await navigator.clipboard.writeText(email);
                    showState('<i class="bi bi-check2"></i>Copied', 'btn-success');
                } catch (error) {
                    try {
                        const tempInput = document.createElement('input');
                        tempInput.value = email;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempInput);
                        showState('<i class="bi bi-check2"></i>Copied', 'btn-success');
                    } catch (fallbackError) {
                        showState('<i class="bi bi-x-circle"></i>Failed', 'btn-danger');
                    }
                }

                setTimeout(function () {
                    button.innerHTML = originalHtml;
                    button.classList.remove('btn-success', 'btn-danger');
                    button.classList.add('btn-outline');
                }, 1500);
            });
        });
    });
</script>
@endpush
