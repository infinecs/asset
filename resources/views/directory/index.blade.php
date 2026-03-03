@extends('layouts.app')

@section('title', 'Directory - IT Asset Management')
@section('page-title', 'Directory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">Organization Directory</h5>
        <p class="text-muted small mb-0">Centralized internal contact list for all staff</p>
    </div>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('directory-contacts.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i>Add Directory Contact
    </a>
    @endif
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name, email, department, phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="location" class="form-select">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                    <option value="{{ $location->id }}" {{ request('location') == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Search</button>
                <a href="{{ route('directory.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name (System User)</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;">
                                    <span class="text-white fw-bold" style="font-size:.72rem;">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    @if($user->id === auth()->id())
                                    <div class="text-muted" style="font-size:.75rem;">You</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->department ?? '-' }}</td>
                        <td>{{ $user->phone ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : 'warning' }}">{{ ucfirst($user->role) }}</span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary copy-email-btn" data-email="{{ $user->email }}" title="Copy email">
                                <i class="bi bi-clipboard me-1"></i>Copy Email
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No directory entries found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white">
        {{ $users->links() }}
    </div>
    @endif
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Additional Directory Contacts</h6>
        <span class="text-muted small">Non-user contacts</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                    <tr>
                        <td class="fw-semibold">{{ $contact->name }}</td>
                        <td>{{ $contact->email }}</td>
                        <td>{{ $contact->department ?? '-' }}</td>
                        <td>{{ $contact->phone ?? '-' }}</td>
                        <td>{{ $contact->location?->name ?? '-' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary copy-email-btn" data-email="{{ $contact->email }}" title="Copy email">
                                    <i class="bi bi-clipboard me-1"></i>Copy Email
                                </button>
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('directory-contacts.edit', $contact) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('directory-contacts.destroy', $contact) }}" onsubmit="return confirm('Delete this contact?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No additional directory contacts found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
                    button.classList.remove('btn-outline-secondary', 'btn-success', 'btn-danger');
                    button.classList.add(btnClass);
                };

                try {
                    await navigator.clipboard.writeText(email);
                    showState('<i class="bi bi-check2 me-1"></i>Copied', 'btn-success');
                } catch (error) {
                    try {
                        const tempInput = document.createElement('input');
                        tempInput.value = email;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempInput);
                        showState('<i class="bi bi-check2 me-1"></i>Copied', 'btn-success');
                    } catch (fallbackError) {
                        showState('<i class="bi bi-x-circle me-1"></i>Failed', 'btn-danger');
                    }
                }

                setTimeout(function () {
                    button.innerHTML = originalHtml;
                    button.classList.remove('btn-success', 'btn-danger');
                    button.classList.add('btn-outline-secondary');
                }, 1500);
            });
        });
    });
</script>
@endpush
