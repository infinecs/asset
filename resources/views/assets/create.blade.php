@extends('layouts.app')

@section('title', 'Add Asset - IT Asset Management')
@section('page-title', 'Add New Asset')

@section('content')
<div class="flex justify-center">
    <div class="w-full max-w-4xl">
        <div class="card">
            <div class="card-header">
                <h5 class="text-base font-semibold text-slate-900 dark:text-white">New Asset</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="field-label">Type <span class="text-red-500">*</span></label>
                            <select id="asset_type" name="type" class="field-input @error('type') is-invalid @enderror" required>
                                <option value="" disabled {{ old('type') ? '' : 'selected' }}>Choose</option>
                                <option value="laptop"     {{ old('type') === 'laptop'     ? 'selected' : '' }}>Laptop</option>
                                <option value="desktop"    {{ old('type') === 'desktop'    ? 'selected' : '' }}>Desktop</option>
                                <option value="smartphone" {{ old('type') === 'smartphone' ? 'selected' : '' }}>Smartphone</option>
                                <option value="tablet"     {{ old('type') === 'tablet'     ? 'selected' : '' }}>Tablet</option>
                                <option value="monitor"    {{ old('type') === 'monitor'    ? 'selected' : '' }}>Monitor</option>
                                <option value="speakerphone" {{ old('type') === 'speakerphone' ? 'selected' : '' }}>Speakerphone</option>
                            </select>
                            @error('type')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Asset Tag <span class="text-red-500">*</span></label>
                            <div class="flex">
                                @php
                                    $newTypePrefixMap = ['laptop'=>'ISSBL','desktop'=>'ISSBD','smartphone'=>'ISSBS','tablet'=>'ISSBT','monitor'=>'ISSBM','speakerphone'=>'ISSBP'];
                                @endphp
                                <span id="asset_tag_prefix" class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-100 px-3 text-sm font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $newTypePrefixMap[old('type')] ?? '—' }}</span>
                                <input type="text" id="asset_tag_suffix" name="asset_tag_suffix"
                                       class="field-input rounded-l-none @error('asset_tag') is-invalid @enderror"
                                       value="{{ old('asset_tag_suffix') }}" placeholder="023" required>
                            </div>
                            @error('asset_tag')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Asset Name <span class="text-red-500">*</span></label>
                            <input type="text" id="asset_name" name="name" class="field-input @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="Auto-filled from tag" required>
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Status <span class="text-red-500">*</span></label>
                            <select name="status" class="field-input @error('status') is-invalid @enderror" required>
                                <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                                <option value="in_use" {{ old('status') == 'in_use' ? 'selected' : '' }}>In Use</option>
                                <option value="under_maintenance" {{ old('status') == 'under_maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                <option value="retired" {{ old('status') == 'retired' ? 'selected' : '' }}>Retired</option>
                                <option value="lost" {{ old('status') == 'lost' ? 'selected' : '' }}>Lost</option>
                            </select>
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
                            <label class="field-label">Model</label>
                            <input type="text" name="model" class="field-input" value="{{ old('model') }}" placeholder="e.g. XPS 15 9530">
                        </div>
                        <div>
                            <label class="field-label">Serial Number</label>
                            <input type="text" name="serial_number" class="field-input" value="{{ old('serial_number') }}" placeholder="Manufacturer serial">
                        </div>
                        <div>
                            <label class="field-label">Service Tag <span class="text-slate-400 font-normal">(optional)</span></label>
                            <input type="text" name="service_tag" class="field-input" value="{{ old('service_tag') }}" placeholder="e.g. Dell Service Tag">
                        </div>
                        <div>
                            <label class="field-label">Category</label>
                            <select id="asset_category" name="category_id" class="field-input">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Location</label>
                            <select name="location_id" class="field-input">
                                <option value="">Select Location</option>
                                @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('assets._assigned-to-field')

                        <div class="sm:col-span-2 lg:col-span-3">
                            <hr class="my-1 border-slate-200 dark:border-slate-800">
                            <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Purchase Information</p>
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
                        <div>
                            <label class="field-label">Warranty Expiry</label>
                            <input type="date" name="warranty_expiry" class="field-input" value="{{ old('warranty_expiry') }}">
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <hr class="my-1 border-slate-200 dark:border-slate-800">
                            <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Technical Details</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="field-label">CPU</label>
                            @php
                                $currentCpu = old('cpu');
                            @endphp
                            <select name="cpu" class="field-input" data-cpu-create-url="{{ route('assets.cpus.store') }}">
                                <option value="">— Select CPU —</option>
                                @foreach($cpuGroups as $group => $cpus)
                                <optgroup label="{{ $group }}">
                                @foreach($cpus as $cpu)
                                <option value="{{ $cpu }}" {{ $currentCpu === $cpu ? 'selected' : '' }}>{{ $cpu }}</option>
                                @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                            <p data-cpu-create-error role="alert" aria-live="polite" class="field-error hidden"></p>
                        </div>
                        <div>
                            <label class="field-label">RAM</label>
                            <select name="ram" class="field-input">
                                <option value="">— Select RAM —</option>
                                @foreach(['4 GB','8 GB','16 GB','32 GB','64 GB'] as $opt)
                                <option value="{{ $opt }}" {{ old('ram') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Storage</label>
                            <select name="storage" class="field-input">
                                <option value="">— Select Storage —</option>
                                @foreach(['128 GB SSD','256 GB SSD','512 GB SSD','1 TB SSD','2 TB SSD','256 GB HDD','512 GB HDD','1 TB HDD','2 TB HDD','512 GB SSD + 1 TB HDD','1 TB SSD + 1 TB HDD'] as $opt)
                                <option value="{{ $opt }}" {{ old('storage') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Display</label>
                            @php
                                $currentDisplay = old('display');
                                $currentDisplay = $currentDisplay ? \App\Support\DisplayCatalog::normalize($currentDisplay) : null;
                            @endphp
                            <select name="display" class="field-input" data-inline-create-url="{{ route('assets.displays.store') }}" data-inline-create-label="Display">
                                <option value="">— Select Display —</option>
                                @foreach($displayOptions as $opt)
                                <option value="{{ $opt }}" {{ $currentDisplay === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                            <p data-inline-create-error role="alert" aria-live="polite" class="field-error hidden"></p>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="field-label">Asset Photo</label>
                            <input type="file" name="photo" class="field-input @error('photo') is-invalid @enderror" accept="image/*">
                            @error('photo')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="field-label">Notes</label>
                            <textarea name="notes" class="field-input" rows="3" placeholder="Additional notes...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg"></i>Create Asset
                        </button>
                        <a href="{{ route('assets.index') }}" class="btn btn-outline px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect     = document.getElementById('asset_type');
        const prefixLabel    = document.getElementById('asset_tag_prefix');
        const suffixInput    = document.getElementById('asset_tag_suffix');
        const nameInput      = document.getElementById('asset_name');
        const categorySelect = document.getElementById('asset_category');

        const prefixMap = {
            laptop:       'ISSBL',
            desktop:      'ISSBD',
            smartphone:   'ISSBS',
            tablet:       'ISSBT',
            monitor:      'ISSBM',
            speakerphone: 'ISSBP',
        };

        const categoryMap = {
            laptop:       'Laptop',
            desktop:      'Desktop',
            smartphone:   'Mobile Device',
            tablet:       'Mobile Device',
            monitor:      'Monitor',
            speakerphone: 'Peripherals',
        };

        const nameLabelMap = {
            laptop:       '',
            desktop:      'D',
            smartphone:   'S',
            tablet:       'T',
            monitor:      'M',
            speakerphone: 'SP',
        };

        function updateAssetName() {
            const suffix = suffixInput.value.trim();
            if (!suffix) {
                nameInput.value = '';
                return;
            }
            const num = parseInt(suffix, 10);
            const suffixPart = isNaN(num) ? suffix : num;
            const label = nameLabelMap[typeSelect.value] || '';
            nameInput.value = 'Infinecs' + label + suffixPart;
        }

        typeSelect.addEventListener('change', function () {
            prefixLabel.textContent = prefixMap[this.value] || '—';

            const categoryName = categoryMap[this.value];
            if (categoryName && categorySelect) {
                const match = Array.from(categorySelect.options).find(opt => opt.text.trim() === categoryName);
                if (match) {
                    if (categorySelect.tomselect) {
                        categorySelect.tomselect.setValue(match.value);
                    } else {
                        categorySelect.value = match.value;
                    }
                }
            }

            updateAssetName();
        });

        suffixInput.addEventListener('input', updateAssetName);
    });
</script>
@endpush
@endsection
