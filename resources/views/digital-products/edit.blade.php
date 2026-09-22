@extends('layouts.app')
@section('title', 'Edit Digital Product')
@section('page-title', 'Edit Digital Product')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-2xl">
        <div class="card">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">Edit: {{ $digitalProduct->name }}</h5>
                <a href="{{ route('digital-products.show', $digitalProduct) }}" class="btn btn-sm btn-outline"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('digital-products.update', $digitalProduct) }}">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name', $digitalProduct->name) }}" required>
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Brand</label>
                            <select name="brand_id" class="field-input">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id', $digitalProduct->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Plan</label>
                            <input type="text" name="plan" class="field-input" value="{{ old('plan', $digitalProduct->plan) }}" placeholder="e.g. Microsoft Desktop License">
                        </div>
                        <div>
                            <label class="field-label">Purchase Date</label>
                            <input type="date" name="purchase_date" class="field-input" value="{{ old('purchase_date', $digitalProduct->purchase_date?->format('Y-m-d')) }}">
                        </div>
                        <div>
                            <label class="field-label">Purchase Cost</label>
                            <div class="flex">
                                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-100 px-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">MYR</span>
                                <input type="number" name="purchase_cost" class="field-input rounded-l-none" value="{{ old('purchase_cost', $digitalProduct->purchase_cost) }}" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Renewal Date</label>
                            <input type="date" name="renewal_date" class="field-input" value="{{ old('renewal_date', $digitalProduct->renewal_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Assigned To</label>
                            @php($selectedIds = collect(old('employee_ids', $assignedEmployeeIds)))
                            <select name="employee_ids[]" class="field-input" multiple size="6">
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ $selectedIds->contains($employee->id) ? 'selected' : '' }}>{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <p class="field-hint">Hold Ctrl (Cmd on Mac) to select multiple employees.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Notes</label>
                            <textarea name="notes" class="field-input" rows="3">{{ old('notes', $digitalProduct->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center gap-2">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Save Changes</button>
                        <a href="{{ route('digital-products.show', $digitalProduct) }}" class="btn btn-outline px-4">Cancel</a>
                        @if(auth()->user()->isAdmin())
                        <button type="submit" form="deleteDigitalProductForm" class="btn btn-outline-danger ml-auto px-4" onclick="return confirm('Delete this digital product? This cannot be undone.')">
                            <i class="bi bi-trash"></i>Delete
                        </button>
                        @endif
                    </div>
                </form>

                @if(auth()->user()->isAdmin())
                <form id="deleteDigitalProductForm" method="POST" action="{{ route('digital-products.destroy', $digitalProduct) }}" class="hidden">
                    @csrf @method('DELETE')
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
