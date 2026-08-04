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
                <form method="POST" action="{{ route('assets.store') }}" enctype="multipart/form-data" x-data="{ assetType: '{{ old('type', 'laptop') }}' }">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="field-label">Type <span class="text-red-500">*</span></label>
                            <select id="asset_type" name="type" class="field-input @error('type') is-invalid @enderror" x-model="assetType" required>
                                <option value="laptop"     {{ old('type', 'laptop') === 'laptop'     ? 'selected' : '' }}>Laptop</option>
                                <option value="desktop"    {{ old('type') === 'desktop'    ? 'selected' : '' }}>Desktop</option>
                                <option value="smartphone" {{ old('type') === 'smartphone' ? 'selected' : '' }}>Smartphone</option>
                                <option value="tablet"     {{ old('type') === 'tablet'     ? 'selected' : '' }}>Tablet</option>
                                <option value="monitor"    {{ old('type') === 'monitor'    ? 'selected' : '' }}>Monitor</option>
                                <option value="digital_product" {{ old('type') === 'digital_product' ? 'selected' : '' }}>Digital Product</option>
                            </select>
                            @error('type')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div x-show="assetType !== 'digital_product'" x-cloak>
                            <label class="field-label">Asset Tag <span class="text-red-500">*</span></label>
                            <div class="flex">
                                <span id="asset_tag_prefix" class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-100 px-3 text-sm font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ old('type','laptop') === 'desktop' ? 'ISSBD' : (old('type') === 'smartphone' ? 'ISSBS' : (old('type') === 'tablet' ? 'ISSBT' : 'ISSBL')) }}</span>
                                <input type="text" id="asset_tag_suffix" name="asset_tag_suffix"
                                       class="field-input rounded-l-none @error('asset_tag') is-invalid @enderror"
                                       value="{{ old('asset_tag_suffix') }}" placeholder="023"
                                       :required="assetType !== 'digital_product'">
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
                            <label class="field-label">Category</label>
                            <select name="category_id" class="field-input">
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
                            <select name="cpu" class="field-input">
                                <option value="">— Select CPU —</option>
                                @foreach([
                                    'Intel Core Ultra (14th Gen)' => [
                                        'Intel Core Ultra 5 125U','Intel Core Ultra 5 125H', 'Intel Core Ultra 5 135U',
                                        'Intel Core Ultra 7 155U','Intel Core Ultra 7 155H','Intel Core Ultra 7 165H',
                                        'Intel Core Ultra 9 185H',
                                    ],
                                    'Intel 13th Gen (Raptor Lake)' => [
                                        'Intel Core i3-1305U','Intel Core i3-1315U',
                                        'Intel Core i5-1335U','Intel Core i5-1345U','Intel Core i5-13500H','Intel Core i5-13600H',
                                        'Intel Core i7-1355U','Intel Core i7-1365U','Intel Core i7-13700H','Intel Core i7-13800H',
                                        'Intel Core i9-13900H','Intel Core i9-13950HX',
                                    ],
                                    'Intel 12th Gen (Alder Lake)' => [
                                        'Intel Core i3-1215U','Intel Core i3-1220P',
                                        'Intel Core i5-1235U','Intel Core i5-1240P','Intel Core i5-1250P',
                                        'Intel Core i5-12450H','Intel Core i5-12450HX','Intel Core i5-12500H','Intel Core i5-12600H',
                                        'Intel Core i7-1260P','Intel Core i7-1270P',
                                        'Intel Core i7-12700H','Intel Core i7-12800H','Intel Core i7-12800HX',
                                        'Intel Core i9-12900H','Intel Core i9-12900HK',
                                    ],
                                    'Intel 11th Gen (Tiger Lake)' => [
                                        'Intel Core i3-1115G4','Intel Core i3-1125G4',
                                        'Intel Core i5-1135G7','Intel Core i5-1155G7',
                                        'Intel Core i5-11300H','Intel Core i5-11400H',
                                        'Intel Core i7-1165G7','Intel Core i7-1185G7',
                                        'Intel Core i7-11370H','Intel Core i7-11800H',
                                        'Intel Core i9-11900H',
                                    ],
                                    'Intel 10th Gen (Comet/Ice Lake)' => [
                                        'Intel Core i3-10110U','Intel Core i3-1005G1',
                                        'Intel Core i5-10210U','Intel Core i5-10310U','Intel Core i5-10500H',
                                        'Intel Core i7-10510U','Intel Core i7-10750H','Intel Core i7-10850H',
                                    ],
                                    'AMD Ryzen 7000 Series' => [
                                        'AMD Ryzen 3 7330U',
                                        'AMD Ryzen 5 7530U','AMD Ryzen 5 7535U','AMD Ryzen 5 7600H',
                                        'AMD Ryzen 7 7730U','AMD Ryzen 7 7735U','AMD Ryzen 7 7745HX',
                                        'AMD Ryzen 9 7940HS','AMD Ryzen 9 7945HX',
                                    ],
                                    'AMD Ryzen 6000 Series' => [
                                        'AMD Ryzen 5 6600U','AMD Ryzen 5 6600H',
                                        'AMD Ryzen 7 6800U','AMD Ryzen 7 6800H',
                                        'AMD Ryzen 9 6900HX',
                                    ],
                                    'AMD Ryzen 5000 Series' => [
                                        'AMD Ryzen 3 5300U',
                                        'AMD Ryzen 5 5500U','AMD Ryzen 5 5600U','AMD Ryzen 5 5600H',
                                        'AMD Ryzen 7 5700U','AMD Ryzen 7 5800H',
                                        'AMD Ryzen 9 5900HS','AMD Ryzen 9 5900HX',
                                    ],
                                    'AMD Ryzen 3000 Series' => [
                                        'AMD Ryzen 5 3500U','AMD Ryzen 5 3550H',
                                        'AMD Ryzen 7 3700U','AMD Ryzen 7 3750H',
                                    ],
                                    'Apple' => [
                                        'Apple M1','Apple M1 Pro','Apple M1 Max',
                                        'Apple M2','Apple M2 Pro','Apple M2 Max',
                                        'Apple M3','Apple M3 Pro','Apple M3 Max',
                                    ],
                                ] as $group => $cpus)
                                <optgroup label="{{ $group }}">
                                    @foreach($cpus as $cpu)
                                    <option value="{{ $cpu }}" {{ old('cpu') === $cpu ? 'selected' : '' }}>{{ $cpu }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
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
                            <select name="display" class="field-input">
                                <option value="">— Select Display —</option>
                                @foreach(['11.6"','13.3"','13.6"','14.0"','14.2"','15.6"','16.0"','17.3"'] as $opt)
                                <option value="{{ $opt }}" {{ old('display') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="field-label">Asset Photo</label>
                            <input type="file" name="photo" class="field-input @error('photo') is-invalid @enderror" accept="image/*">
                            @error('photo')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="field-label">Signed Document</label>
                            <input type="file" name="signed_document" class="field-input @error('signed_document') is-invalid @enderror" accept=".pdf,.doc,.docx">
                            <p class="field-hint">Signed handover/acceptance document (PDF or Word, max 10MB).</p>
                            @error('signed_document')<p class="field-error">{{ $message }}</p>@enderror
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
        const typeSelect   = document.getElementById('asset_type');
        const prefixLabel  = document.getElementById('asset_tag_prefix');
        const suffixInput  = document.getElementById('asset_tag_suffix');
        const nameInput    = document.getElementById('asset_name');

        const prefixMap = {
            laptop:     'ISSBL',
            desktop:    'ISSBD',
            smartphone: 'ISSBS',
            tablet:     'ISSBT',
            monitor:    'ISSBM',
        };

        typeSelect.addEventListener('change', function () {
            prefixLabel.textContent = prefixMap[this.value] || 'ISSBL';
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
