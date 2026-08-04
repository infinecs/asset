@extends('layouts.app')
@section('title', 'Add Digital Product')
@section('page-title', 'Add Digital Product')
@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-2xl">
        <div class="card" x-data="{ bulk: false }">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">New Digital Product</h5>
                <a href="{{ route('digital-products.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-arrow-left"></i>Back</a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('digital-products.store') }}">
                    @csrf

                    <label class="mb-4 flex items-center gap-2">
                        <input type="checkbox" x-model="bulk" class="h-4 w-4 rounded border-slate-300 accent-primary-600">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Create multiple at once (e.g. infinecs1 … infinecs6)</span>
                    </label>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div x-show="!bulk" x-cloak>
                            <label class="field-label">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" class="field-input @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. infinecs1" :required="!bulk">
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div x-show="bulk" x-cloak class="sm:col-span-2">
                            <label class="field-label">Bulk Range</label>
                            <p class="field-hint mt-0 mb-2">e.g. prefix "infinecs" from 1 to 6 creates infinecs1 … infinecs6, sharing the details below</p>
                            <div class="grid grid-cols-3 gap-2">
                                <input type="text" name="bulk_prefix" class="field-input @error('bulk_prefix') is-invalid @enderror" placeholder="infinecs" value="{{ old('bulk_prefix') }}" :required="bulk">
                                <input type="number" name="bulk_range_start" class="field-input @error('bulk_range_start') is-invalid @enderror" placeholder="1" min="0" value="{{ old('bulk_range_start') }}" :required="bulk">
                                <input type="number" name="bulk_range_end" class="field-input @error('bulk_range_end') is-invalid @enderror" placeholder="6" min="0" value="{{ old('bulk_range_end') }}" :required="bulk">
                            </div>
                            @error('bulk_prefix')<p class="field-error">{{ $message }}</p>@enderror
                            @error('bulk_range_start')<p class="field-error">{{ $message }}</p>@enderror
                            @error('bulk_range_end')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Brand</label>
                            <select name="brand_id" class="field-input">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Plan</label>
                            <input type="text" name="plan" class="field-input" value="{{ old('plan') }}" placeholder="e.g. Microsoft Desktop License">
                        </div>
                        <div>
                            <label class="field-label">Purchase Date</label>
                            <input type="date" name="purchase_date" class="field-input" value="{{ old('purchase_date') }}">
                        </div>
                        <div>
                            <label class="field-label">Purchase Cost</label>
                            <div class="flex">
                                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-100 px-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">MYR</span>
                                <input type="number" name="purchase_cost" class="field-input rounded-l-none" value="{{ old('purchase_cost') }}" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Renewal Date</label>
                            <input type="date" name="renewal_date" class="field-input" value="{{ old('renewal_date') }}">
                        </div>
                        <div class="sm:col-span-2" x-show="!bulk" x-cloak>
                            <label class="field-label">Assigned To</label>
                            <select name="employee_ids[]" class="field-input" multiple size="6">
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ collect(old('employee_ids'))->contains($employee->id) ? 'selected' : '' }}>{{ $employee->name }}</option>
                                @endforeach
                            </select>
                            <p class="field-hint">Hold Ctrl (Cmd on Mac) to select multiple employees.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label">Notes</label>
                            <textarea name="notes" class="field-input" rows="3" placeholder="Additional notes...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-2">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg"></i>Create</button>
                        <a href="{{ route('digital-products.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
