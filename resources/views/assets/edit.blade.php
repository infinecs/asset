@extends('layouts.app')

@section('title', 'Edit Asset - IT Asset Management')
@section('page-title', 'Edit Asset')

@section('content')
@php $safeReturn = request('return') && str_starts_with(request('return'), '/assets') ? request('return') : null; @endphp
<div class="flex justify-center">
    <div class="w-full max-w-4xl">
        <div class="card">
            <div class="card-header">
                <div>
                    <h5 class="text-base font-semibold text-slate-900 dark:text-white">{{ $asset->name }}</h5>
                    <code class="text-slate-500 dark:text-slate-400">{{ $asset->asset_tag ?? '-' }}</code>
                </div>
                <a href="{{ route('assets.show', $asset) }}{{ $safeReturn ? '?return=' . urlencode($safeReturn) : '' }}" class="btn btn-sm btn-outline">
                    <i class="bi bi-arrow-left"></i>Back
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.update', $asset) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="return" value="{{ $safeReturn }}">
                    @php
                        $currentType = old('type', $asset->type);
                        if (!$currentType) {
                            if (str_starts_with($asset->asset_tag, 'ISSBD'))      $currentType = 'desktop';
                            elseif (str_starts_with($asset->asset_tag, 'ISSBS')) $currentType = 'smartphone';
                            elseif (str_starts_with($asset->asset_tag, 'ISSBT')) $currentType = 'tablet';
                            elseif (str_starts_with($asset->asset_tag, 'ISSBM')) $currentType = 'monitor';
                            elseif (str_starts_with($asset->asset_tag, 'ISSBP')) $currentType = 'speakerphone';
                            else                                                    $currentType = 'laptop';
                        }
                        $editPrefixMap = ['laptop'=>'ISSBL','desktop'=>'ISSBD','smartphone'=>'ISSBS','tablet'=>'ISSBT','monitor'=>'ISSBM','speakerphone'=>'ISSBP'];
                        $currentPrefix = $editPrefixMap[$currentType] ?? 'ISSBL';
                        $currentSuffix = old('asset_tag_suffix', Str::after($asset->asset_tag, $currentPrefix));
                    @endphp
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="field-label">Type <span class="text-red-500">*</span></label>
                            <select id="asset_type" name="type" class="field-input @error('type') is-invalid @enderror" required>
                                <option value="laptop"     {{ $currentType === 'laptop'     ? 'selected' : '' }}>Laptop</option>
                                <option value="desktop"    {{ $currentType === 'desktop'    ? 'selected' : '' }}>Desktop</option>
                                <option value="smartphone" {{ $currentType === 'smartphone' ? 'selected' : '' }}>Smartphone</option>
                                <option value="tablet"     {{ $currentType === 'tablet'     ? 'selected' : '' }}>Tablet</option>
                                <option value="monitor"    {{ $currentType === 'monitor'    ? 'selected' : '' }}>Monitor</option>
                                <option value="speakerphone" {{ $currentType === 'speakerphone' ? 'selected' : '' }}>Speakerphone</option>
                            </select>
                            @error('type')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Asset Tag <span class="text-red-500">*</span></label>
                            <div class="flex">
                                <span id="asset_tag_prefix" class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-100 px-3 text-sm font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $currentPrefix }}</span>
                                <input type="text" id="asset_tag_suffix" name="asset_tag_suffix"
                                       class="field-input rounded-l-none @error('asset_tag') is-invalid @enderror"
                                       value="{{ $currentSuffix }}"
                                       placeholder="023" required>
                            </div>
                            @error('asset_tag')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Asset Name <span class="text-red-500">*</span></label>
                            <input type="text" id="asset_name" name="name" class="field-input @error('name') is-invalid @enderror"
                                   value="{{ old('name', $asset->name) }}" required>
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="field-label">Status <span class="text-red-500">*</span></label>
                            <select name="status" class="field-input" required>
                                @foreach(['available', 'in_use', 'under_maintenance', 'retired', 'lost'] as $s)
                                <option value="{{ $s }}" {{ old('status', $asset->status) == $s ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $s)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Brand</label>
                            <select name="brand_id" class="field-input">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id', $asset->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Model</label>
                            <input type="text" name="model" class="field-input" value="{{ old('model', $asset->model) }}">
                        </div>
                        <div>
                            <label class="field-label">Serial Number</label>
                            <input type="text" name="serial_number" class="field-input" value="{{ old('serial_number', $asset->serial_number) }}">
                        </div>
                        <div>
                            <label class="field-label">Service Tag <span class="text-slate-400 font-normal">(optional)</span></label>
                            <input type="text" name="service_tag" class="field-input" value="{{ old('service_tag', $asset->service_tag) }}" placeholder="e.g. Dell Service Tag">
                        </div>
                        <div>
                            <label class="field-label">Category</label>
                            <select id="asset_category" name="category_id" class="field-input">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $asset->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Location</label>
                            <select name="location_id" class="field-input">
                                <option value="">Select Location</option>
                                @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id', $asset->location_id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('assets._assigned-to-field')
                        <div>
                            <label class="field-label">Last Seen At</label>
                            <input type="datetime-local" name="last_seen_at" class="field-input" value="{{ old('last_seen_at', $asset->last_seen_at?->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <hr class="my-1 border-slate-200 dark:border-slate-800">
                            <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Purchase Information</p>
                        </div>

                        <div>
                            <label class="field-label">Purchase Date</label>
                            <input type="date" name="purchase_date" class="field-input" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}">
                        </div>
                        <div>
                            <label class="field-label">Purchase Cost</label>
                            <div class="flex">
                                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-100 px-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">MYR</span>
                                <input type="number" name="purchase_cost" class="field-input rounded-l-none" value="{{ old('purchase_cost', $asset->purchase_cost) }}" step="0.01" min="0">
                            </div>
                        </div>
                        <div>
                            <label class="field-label">Warranty Expiry</label>
                            <input type="date" name="warranty_expiry" class="field-input" value="{{ old('warranty_expiry', $asset->warranty_expiry?->format('Y-m-d')) }}">
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <hr class="my-1 border-slate-200 dark:border-slate-800">
                            <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Technical Details</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="field-label">CPU</label>
                            @php
                                $currentCpu = old('cpu', $asset->cpu);
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
                                <option value="{{ $opt }}" {{ old('ram', $asset->ram) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Storage</label>
                            <select name="storage" class="field-input">
                                <option value="">— Select Storage —</option>
                                @foreach(['128 GB SSD','256 GB SSD','512 GB SSD','1 TB SSD','2 TB SSD','256 GB HDD','512 GB HDD','1 TB HDD','2 TB HDD','512 GB SSD + 1 TB HDD','1 TB SSD + 1 TB HDD'] as $opt)
                                <option value="{{ $opt }}" {{ old('storage', $asset->storage) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Display</label>
                            @php
                                $currentDisplay = old('display', $asset->display);
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
                            @if($asset->photo_path)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $asset->photo_path) }}" alt="{{ $asset->name }}" class="h-28 w-28 rounded-lg border border-slate-200 object-cover dark:border-slate-700">
                            </div>
                            @endif
                            <input type="file" name="photo" class="field-input @error('photo') is-invalid @enderror" accept="image/*">
                            @error('photo')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="field-label">Notes</label>
                            <textarea name="notes" class="field-input" rows="3">{{ old('notes', $asset->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg"></i>Save Changes
                        </button>
                        <a href="{{ route('assets.show', $asset) }}{{ $safeReturn ? '?return=' . urlencode($safeReturn) : '' }}" class="btn btn-outline px-4">Cancel</a>
                        @if(auth()->user()->isAdmin())
                        <button type="submit" form="deleteAssetForm" class="btn btn-outline-danger ml-auto px-4" onclick="return confirm('Delete this asset? This cannot be undone.')">
                            <i class="bi bi-trash"></i>Delete
                        </button>
                        @endif
                    </div>
                </form>

                @if(auth()->user()->isAdmin())
                <form id="deleteAssetForm" method="POST" action="{{ route('assets.destroy', $asset) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
                @endif
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

        typeSelect.addEventListener('change', function () {
            prefixLabel.textContent = prefixMap[this.value] || 'ISSBL';

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
        });

        suffixInput.addEventListener('input', function () {
            const suffix = this.value.trim();
            const num = parseInt(suffix, 10);
            nameInput.value = suffix ? 'Infinecs' + (isNaN(num) ? suffix : num) : '';
        });
    });
</script>
@endpush
@endsection
