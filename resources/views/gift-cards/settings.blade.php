@extends('layouts.app')
@section('title', 'Gift Card Settings')
@section('page-title', 'Gift Card Settings')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Gift Card Settings</h5>
    <a href="{{ route('gift-cards.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
</div>

<div class="card max-w-[600px]">
    <div class="card-body">
        <form method="POST" action="{{ route('gift-cards.settings.update') }}">
            @csrf @method('PUT')

            @php($selectedIds = collect(old('person_in_charge_ids', $assignedIds)))
            <div class="mb-4">
                <label class="field-label">Person(s) In Charge</label>
                <select name="person_in_charge_ids[]" class="field-input @error('person_in_charge_ids') is-invalid @enderror" multiple size="6">
                    @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ $selectedIds->contains($employee->id) ? 'selected' : '' }}>
                        {{ $employee->name }} <{{ $employee->id_number }}>
                    </option>
                    @endforeach
                </select>
                <p class="field-hint">Each person selected receives a daily reminder email starting 7 days before an employee's birthday until the Touch 'n Go eWallet reload reference is filled in.</p>
                @error('person_in_charge_ids')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <a href="{{ route('gift-cards.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
