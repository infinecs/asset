@extends('layouts.app')
@section('title', 'Bulk Edit Birthdays')
@section('page-title', 'Bulk Edit Birthdays')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Bulk Edit Birthdays</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Fill in dates below and save — only changed rows are updated.</p>
    </div>
    <a href="{{ route('employees.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back to Employees</a>
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('employees.bulk-edit-birthdays') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" class="field-input pl-9" value="{{ request('search') }}" placeholder="Search name or ID number...">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="missing" value="1" {{ request('missing') === '1' ? 'checked' : '' }} onchange="this.form.submit()">
                Missing only
            </label>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ route('employees.bulk-edit-birthdays') }}" class="btn btn-outline">Clear</a>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('employees.update-birthdays') }}">
    @csrf
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Work Location</th>
                        <th class="w-[180px]">Date of Birth</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr class="{{ !$employee->date_of_birth ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                        <td><code class="text-primary-600 dark:text-primary-400">{{ $employee->id_number }}</code></td>
                        <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $employee->name }}</td>
                        <td class="text-slate-500 dark:text-slate-400">{{ $employee->work_location ?? '-' }}</td>
                        <td>
                            <input type="text" name="birthdays[{{ $employee->id }}]" class="field-input bulk-dob-input" placeholder="dd/mm/yyyy" pattern="\d{2}/\d{2}/\d{4}" maxlength="10" inputmode="numeric" value="{{ $employee->date_of_birth?->format('d/m/Y') }}">
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-500 dark:text-slate-400">No employees found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
        <div class="card-footer">
            {{ $employees->links() }}
        </div>
        @endif
    </div>

    @if($employees->isNotEmpty())
    <div class="mt-6 flex items-center gap-2">
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save This Page</button>
        <span class="text-sm text-slate-500 dark:text-slate-400">Saves only the {{ $employees->count() }} row(s) shown above. Change pages to edit more.</span>
    </div>
    @endif
</form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.bulk-dob-input').forEach(function (input) {
        input.addEventListener('input', function () {
            let digits = this.value.replace(/\D/g, '').slice(0, 8);
            let formatted = digits;
            if (digits.length > 4) {
                formatted = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
            } else if (digits.length > 2) {
                formatted = digits.slice(0, 2) + '/' + digits.slice(2);
            }
            this.value = formatted;
        });
    });
</script>
@endpush
