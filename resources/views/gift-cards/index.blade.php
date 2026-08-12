@extends('layouts.app')
@section('title', 'Gift Cards')
@section('page-title', 'Birthday Gift Cards')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Birthday Gift Cards</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Upcoming employee birthdays and Touch 'n Go eWallet reload status</p>
    </div>
    @if(auth()->user()->isAdmin())
    <div class="flex gap-2">
        <a href="{{ route('employees.bulk-edit-birthdays', ['missing' => 1]) }}" class="btn btn-outline"><i class="bi bi-calendar-heart"></i>Fill Missing Birthdays</a>
        <a href="{{ route('gift-cards.preview.birthday') }}" target="_blank" class="btn btn-outline"><i class="bi bi-eye"></i>Preview Template</a>
        <a href="{{ route('gift-cards.settings') }}" class="btn btn-outline"><i class="bi bi-gear"></i>Settings</a>
    </div>
    @endif
</div>

@if(auth()->user()->isAdmin() && $picCount === 0)
<div class="alert alert-danger mb-4">
    <i class="bi bi-exclamation-triangle mt-0.5"></i>
    <span class="flex-1">No person in charge is configured — birthday reminders won't be sent. <a href="{{ route('gift-cards.settings') }}" class="font-semibold hover:underline">Set one up</a>.</span>
</div>
@endif

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('gift-cards.index') }}" class="flex flex-wrap gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" class="field-input pl-9" value="{{ request('search') }}" placeholder="Search employee name...">
            </div>
            <select name="range" class="field-input w-auto" onchange="this.form.submit()" {{ $view !== 'upcoming' ? 'disabled' : '' }}>
                <option value="upcoming" {{ request('range', 'upcoming') === 'upcoming' ? 'selected' : '' }}>Next 60 days</option>
                <option value="all" {{ request('range') === 'all' ? 'selected' : '' }}>Full year</option>
            </select>
            <select name="view" class="field-input w-auto" onchange="this.form.submit()">
                <option value="upcoming" {{ $view === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                <option value="sent" {{ $view === 'sent' ? 'selected' : '' }}>Sent this year</option>
                <option value="opted_out" {{ $view === 'opted_out' ? 'selected' : '' }}>Opted out</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ route('gift-cards.index') }}" class="btn btn-outline">Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>ID Number</th>
                    <th>Birthday</th>
                    @if($view === 'opted_out')
                    <th>Program</th>
                    @elseif($view === 'sent')
                    <th>Sent On</th>
                    <th>TNG eWallet Reload</th>
                    <th>Status</th>
                    @else
                    <th>Days Left</th>
                    <th>TNG eWallet Reload</th>
                    <th>Status</th>
                    @endif
                    @if(auth()->user()->isAdmin())
                    <th class="text-right">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                @php $giftCard = $row['gift_card']; @endphp
                <tr x-data="{ editing: false }">
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $row['employee']->name }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $row['employee']->id_number }}</td>
                    <td class="text-slate-600 dark:text-slate-300">{{ $row['occurrence']->format('d M Y') }}</td>
                    @if($view === 'opted_out')
                    <td><span class="badge badge-secondary">Opted Out</span></td>
                    @else
                        @if($view === 'sent')
                        <td class="text-slate-600 dark:text-slate-300">{{ $giftCard?->sent_at?->format('d M Y') ?? '—' }}</td>
                        @else
                        <td>
                            @if($row['days_until'] === 0)
                            <span class="badge badge-primary">Today 🎉</span>
                            @else
                            <span class="text-slate-600 dark:text-slate-300">{{ $row['days_until'] }} day{{ $row['days_until'] === 1 ? '' : 's' }}</span>
                            @endif
                        </td>
                        @endif
                    <td>
                        <span x-show="!editing">{{ $giftCard?->gift_card_code ?: '—' }}</span>
                        @if(auth()->user()->isAdmin())
                        <form x-show="editing" x-cloak method="POST" action="{{ route('gift-cards.update', $row['employee']) }}" class="flex items-center gap-1">
                            @csrf @method('PUT')
                            <input type="hidden" name="occasion_year" value="{{ $row['occurrence']->year }}">
                            <input type="text" name="gift_card_code" class="field-input !py-1 !text-xs" value="{{ $giftCard?->gift_card_code }}" placeholder="Reload reference">
                            <button type="submit" class="btn btn-sm btn-primary btn-icon"><i class="bi bi-check-lg"></i></button>
                        </form>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $giftCard?->status_badge ?? 'secondary' }}">{{ $giftCard?->status_label ?? 'Pending' }}</span>
                    </td>
                    @endif
                    @if(auth()->user()->isAdmin())
                    <td class="text-right">
                        @if($view === 'opted_out')
                        <a href="{{ route('employees.edit', $row['employee']) }}?return={{ urlencode(request()->fullUrlWithQuery([])) }}" class="btn btn-sm btn-outline">
                            <i class="bi bi-pencil"></i>Manage
                        </a>
                        @else
                        <div class="inline-flex gap-1">
                            @if($giftCard?->gift_card_code)
                            <form id="test-send-form-{{ $row['employee']->id }}-{{ $row['occurrence']->year }}" method="POST" action="{{ route('gift-cards.test-send', $row['employee']) }}">
                                @csrf
                                <input type="hidden" name="occasion_year" value="{{ $row['occurrence']->year }}">
                            </form>
                            <button type="button" class="btn btn-sm btn-outline btn-icon" title="Send test email to yourself"
                                    onclick="openTestSendModal('test-send-form-{{ $row['employee']->id }}-{{ $row['occurrence']->year }}', @js($row['employee']->name))">
                                <i class="bi bi-send"></i>
                            </button>
                            @else
                            <button type="button" class="btn btn-sm btn-outline btn-icon" disabled title="Set a reload reference first">
                                <i class="bi bi-send"></i>
                            </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline btn-icon" @click="editing = !editing">
                                <i class="bi" :class="editing ? 'bi-x-lg' : 'bi-pencil'"></i>
                            </button>
                        </div>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                        <i class="bi bi-gift mb-2 block text-3xl"></i>
                        @if($view === 'opted_out')
                        No employees have opted out.
                        @elseif($view === 'sent')
                        No gift cards sent yet this year.
                        @else
                        No upcoming birthdays in this range.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())
    <div class="card-footer">
        {{ $rows->links() }}
    </div>
    @endif
</div>

@if(auth()->user()->isAdmin())
<x-ui.modal id="testSendModal" maxWidth="max-w-md">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <h5 class="text-base font-semibold text-slate-900 dark:text-white"><i class="bi bi-send me-2"></i>Send Test Email</h5>
        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="open = false"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="p-6">
        <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
            Send a test birthday email (with the current Touch 'n Go eWallet reload reference) to your own email address for <strong id="testSendEmployeeName"></strong>?
        </p>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-outline" @click="open = false">Cancel</button>
            <button type="button" class="btn btn-primary" @click="submitTestSend(); open = false;"><i class="bi bi-send"></i>Send Test</button>
        </div>
    </div>
</x-ui.modal>
@endif
@endsection

@push('scripts')
<script>
    let pendingTestSendFormId = null;

    function openTestSendModal(formId, employeeName) {
        pendingTestSendFormId = formId;
        document.getElementById('testSendEmployeeName').textContent = employeeName;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'testSendModal' }));
    }

    function submitTestSend() {
        if (pendingTestSendFormId) {
            document.getElementById(pendingTestSendFormId).submit();
        }
    }
</script>
@endpush
