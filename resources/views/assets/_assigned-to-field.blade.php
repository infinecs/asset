@php
    $asset = $asset ?? null;
    $selectedAssignee = old('assigned_to', $asset?->assigned_to ?? ($asset?->assigned_to_other ? '__other__' : ''));
@endphp
<div>
    <label class="field-label">Assigned To</label>
    <select name="assigned_to" class="field-input" data-assigned-to-select>
        <option value="">Not Assigned</option>
        <option value="__other__" {{ $selectedAssignee === '__other__' ? 'selected' : '' }}>Office / Non-employee…</option>
        @foreach($employees as $employee)
        <option value="{{ $employee->id }}" {{ $selectedAssignee == $employee->id ? 'selected' : '' }}>
            {{ $employee->name }} <{{ $employee->id_number }}>
        </option>
        @endforeach
    </select>
    <div class="mt-2 {{ $selectedAssignee === '__other__' ? '' : 'hidden' }}" data-assigned-to-other>
        <input type="text" name="assigned_to_other" class="field-input @error('assigned_to_other') is-invalid @enderror" maxlength="255"
               placeholder="e.g. Security PC - Block A/C"
               value="{{ old('assigned_to_other', $asset?->assigned_to_other) }}">
        @error('assigned_to_other')<p class="field-error">{{ $message }}</p>@enderror
    </div>
</div>

@once
@push('scripts')
<script>
    document.addEventListener('change', function (event) {
        const select = event.target.closest('[data-assigned-to-select]');
        if (!select) {
            return;
        }
        const wrapper = select.parentElement.querySelector('[data-assigned-to-other]');
        const isOther = select.value === '__other__';
        wrapper.classList.toggle('hidden', !isOther);
        if (isOther) {
            wrapper.querySelector('input').focus();
        }
    });
</script>
@endpush
@endonce
