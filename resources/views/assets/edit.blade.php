@extends('layouts.app')

@section('title', 'Edit Asset - IT Asset Management')
@section('page-title', 'Edit Asset')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-semibold mb-0">{{ $asset->name }}</h5>
                    <code class="text-muted">{{ $asset->asset_tag }}</code>
                </div>
                <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('assets.update', $asset) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @php
                        $currentType = old('type', $asset->type);
                        if (!$currentType) {
                            if (str_starts_with($asset->asset_tag, 'ISSB-D'))      $currentType = 'desktop';
                            elseif (str_starts_with($asset->asset_tag, 'ISSB-S')) $currentType = 'smartphone';
                            elseif (str_starts_with($asset->asset_tag, 'ISSB-T')) $currentType = 'tablet';
                            elseif (str_starts_with($asset->asset_tag, 'ISSB-M')) $currentType = 'monitor';
                            else                                                    $currentType = 'laptop';
                        }
                        $editPrefixMap = ['laptop'=>'ISSB-L','desktop'=>'ISSB-D','smartphone'=>'ISSB-S','tablet'=>'ISSB-T','monitor'=>'ISSB-M'];
                        $currentPrefix = $editPrefixMap[$currentType] ?? 'ISSB-L';
                        $currentSuffix = old('asset_tag_suffix', Str::after($asset->asset_tag, $currentPrefix));
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select id="asset_type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="laptop"     {{ $currentType === 'laptop'     ? 'selected' : '' }}>Laptop</option>
                                <option value="desktop"    {{ $currentType === 'desktop'    ? 'selected' : '' }}>Desktop</option>
                                <option value="smartphone" {{ $currentType === 'smartphone' ? 'selected' : '' }}>Smartphone</option>
                                <option value="tablet"     {{ $currentType === 'tablet'     ? 'selected' : '' }}>Tablet</option>
                                <option value="monitor"    {{ $currentType === 'monitor'    ? 'selected' : '' }}>Monitor</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Asset Tag <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span id="asset_tag_prefix" class="input-group-text fw-semibold">{{ $currentPrefix }}</span>
                                <input type="text" id="asset_tag_suffix" name="asset_tag_suffix"
                                       class="form-control @error('asset_tag') is-invalid @enderror"
                                       value="{{ $currentSuffix }}"
                                       placeholder="023" required>
                                @error('asset_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Asset Name <span class="text-danger">*</span></label>
                            <input type="text" id="asset_name" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $asset->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach(['available', 'in_use', 'under_maintenance', 'retired', 'lost'] as $s)
                                <option value="{{ $s }}" {{ old('status', $asset->status) == $s ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $s)) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id', $asset->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Model</label>
                            <input type="text" name="model" class="form-control" value="{{ old('model', $asset->model) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number', $asset->serial_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $asset->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location</label>
                            <select name="location_id" class="form-select">
                                <option value="">Select Location</option>
                                @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id', $asset->location_id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">Not Assigned</option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('assigned_to', $asset->assigned_to) == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }} <{{ $employee->id_number }}>
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last Seen At</label>
                            <input type="datetime-local" name="last_seen_at" class="form-control" value="{{ old('last_seen_at', $asset->last_seen_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-12"><hr class="my-1"><p class="text-muted small mb-0">Purchase Information</p></div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Purchase Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">MYR</span>
                                <input type="number" name="purchase_cost" class="form-control" value="{{ old('purchase_cost', $asset->purchase_cost) }}" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Warranty Expiry</label>
                            <input type="date" name="warranty_expiry" class="form-control" value="{{ old('warranty_expiry', $asset->warranty_expiry?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12"><hr class="my-1"><p class="text-muted small mb-0">Technical Details</p></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CPU</label>
                            <select name="cpu" class="form-select">
                                <option value="">— Select CPU —</option>
                                @foreach([
                                    'Intel Core Ultra Series 2 (Arrow Lake/Lunar Lake)' => [
                                        'Intel Core Ultra 5 225H',
                                    ],
                                    'Intel Core Ultra Series 1 (Meteor Lake)' => [
                                        'Intel Core Ultra 5 120U','Intel Core Ultra 5 125U','Intel Core Ultra 5 125H', 'Intel Core Ultra 5 135U',
                                        'Intel Core Ultra 7 155U','Intel Core Ultra 7 155H','Intel Core Ultra 7 165H',
                                        'Intel Core Ultra 9 185H',
                                    ],
                                    'Intel 14th Gen (Raptor Lake Refresh)' => [
                                        'Intel Core i5-14500HX',
                                        'Intel Core i7-14700HX',
                                        'Intel Core i9-14900HX',
                                    ],
                                    'Intel 13th Gen (Raptor Lake)' => [
                                        'Intel Core i3-1305U','Intel Core i3-1315U',
                                        'Intel Core i5-1335U','Intel Core i5-1345U','Intel Core i5-13500H','Intel Core i5-13600H',
                                        'Intel Core i7-1355U','Intel Core i7-1365U','Intel Core i7-13700H','Intel Core i7-13800H',
                                        'Intel Core i9-13900H','Intel Core i9-13950HX',
                                    ],
                                    'Intel 12th Gen (Alder Lake)' => [
                                        'Intel Core i3-1215U','Intel Core i3-1220P',
                                        'Intel Core i5-1235U','Intel Core i5-1240P','Intel Core i5-1245U','Intel Core i5-1250P',
                                        'Intel Core i5-12450H','Intel Core i5-12450HX','Intel Core i5-12500H','Intel Core i5-12600H',
                                        'Intel Core i7-1260P','Intel Core i7-1270P',
                                        'Intel Core i7-12700H','Intel Core i7-12800H','Intel Core i7-12800HX',
                                        'Intel Core i9-12900H','Intel Core i9-12900HK',
                                    ],
                                    'Intel 11th Gen (Rocket/Tiger Lake)' => [
                                        'Intel Core i3-1115G4','Intel Core i3-1125G4',
                                        'Intel Core i5-1135G7','Intel Core i5-1145G7','Intel Core i5-1155G7',
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
                                    'Intel 9th Gen (Coffee Lake Refresh)' => [
                                        'Intel Core i5-9300H',
                                        'Intel Core i7-9750H',
                                        'Intel Core i9-9880H','Intel Core i9-9980HK',
                                    ],
                                    'Intel 8th Gen (Coffee/Kaby Lake)' => [
                                        'Intel Core i3-8145U',
                                        'Intel Core i5-8250U','Intel Core i5-8265U','Intel Core i5-8300H',
                                        'Intel Core i7-8550U','Intel Core i7-8565U','Intel Core i7-8750H','Intel Core i7-8850H',
                                        'Intel Core i9-8950HK',
                                    ],
                                    'Intel 7th Gen (Kaby Lake)' => [
                                        'Intel Core i3-7100U','Intel Core i3-7130U',
                                        'Intel Core i5-7200U','Intel Core i5-7300HQ','Intel Core i5-7440HQ',
                                        'Intel Core i7-7500U','Intel Core i7-7600U','Intel Core i7-7700HQ','Intel Core i7-7820HQ',
                                    ],
                                    'AMD Ryzen AI 300 Series' => [
                                        'AMD Ryzen AI 7 350',
                                        'AMD Ryzen AI 9 365','AMD Ryzen AI 9 HX 370',
                                    ],
                                    'AMD Ryzen 8000 Series' => [
                                        'AMD Ryzen 5 8540U','AMD Ryzen 5 8640U',
                                        'AMD Ryzen 7 8840U','AMD Ryzen 7 8840HS',
                                        'AMD Ryzen 9 8945HS',
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
                                        'Apple M4','Apple M4 Pro','Apple M4 Max',
                                    ],
                                ] as $group => $cpus)
                                <optgroup label="{{ $group }}">
                                    @foreach($cpus as $cpu)
                                    <option value="{{ $cpu }}" {{ old('cpu', $asset->cpu) === $cpu ? 'selected' : '' }}>{{ $cpu }}</option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">RAM</label>
                            <select name="ram" class="form-select">
                                <option value="">— Select RAM —</option>
                                @foreach(['4 GB','8 GB','16 GB','32 GB','64 GB'] as $opt)
                                <option value="{{ $opt }}" {{ old('ram', $asset->ram) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Storage</label>
                            <select name="storage" class="form-select">
                                <option value="">— Select Storage —</option>
                                @foreach(['128 GB SSD','256 GB SSD','512 GB SSD','1 TB SSD','2 TB SSD','256 GB HDD','512 GB HDD','1 TB HDD','2 TB HDD','512 GB SSD + 1 TB HDD','1 TB SSD + 1 TB HDD'] as $opt)
                                <option value="{{ $opt }}" {{ old('storage', $asset->storage) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Display</label>
                            <select name="display" class="form-select">
                                <option value="">— Select Display —</option>
                                @foreach(['11.6"','13.0"','13.3"','13.6"','14.0"','14.2"','15.6"','16.0"','17.3"'] as $opt)
                                <option value="{{ $opt }}" {{ old('display', $asset->display) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Asset Photo</label>
                            <div class="mb-2">
                                <img id="photo_preview" src="{{ $asset->photo_path ? asset('storage/' . $asset->photo_path) : '' }}" alt="{{ $asset->name }}" class="rounded border {{ $asset->photo_path ? '' : 'd-none' }}" style="width: 120px; height: 120px; object-fit: cover;">
                            </div>
                            <input type="file" name="photo" id="photo_input" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Signed Document</label>
                            @if($asset->signed_document_path)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $asset->signed_document_path) }}" target="_blank" class="text-decoration-none">
                                    <i class="bi bi-file-earmark-text me-1"></i>View current document
                                </a>
                            </div>
                            @endif
                            <input type="file" name="signed_document" class="form-control @error('signed_document') is-invalid @enderror" accept=".pdf,.doc,.docx">
                            <div class="form-text">Signed handover/acceptance document (PDF or Word, max 10MB). Uploading a new file replaces the current one.</div>
                            @error('signed_document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $asset->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                        <a href="{{ route('assets.show', $asset) }}" class="btn btn-outline-secondary px-4">Cancel</a>
                        @if(auth()->user()->isAdmin())
                        <button type="submit" form="deleteAssetForm" class="btn btn-outline-danger px-4 ms-auto" onclick="return confirm('Delete this asset? This cannot be undone.')">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        @endif
                    </div>
                </form>

                @if(auth()->user()->isAdmin())
                <form id="deleteAssetForm" method="POST" action="{{ route('assets.destroy', $asset) }}" class="d-none">
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
        const typeSelect  = document.getElementById('asset_type');
        const prefixLabel = document.getElementById('asset_tag_prefix');
        const suffixInput = document.getElementById('asset_tag_suffix');
        const nameInput   = document.getElementById('asset_name');

        const prefixMap = {
            laptop:     'ISSB-L',
            desktop:    'ISSB-D',
            smartphone: 'ISSB-S',
            tablet:     'ISSB-T',
            monitor:    'ISSB-M',
        };

        typeSelect.addEventListener('change', function () {
            prefixLabel.textContent = prefixMap[this.value] || 'ISSB-L';
        });

        suffixInput.addEventListener('input', function () {
            const suffix = this.value.trim();
            const num = parseInt(suffix, 10);
            nameInput.value = suffix ? 'Infinecs' + (isNaN(num) ? suffix : num) : '';
        });

        const photoInput   = document.getElementById('photo_input');
        const photoPreview = document.getElementById('photo_preview');

        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                photoPreview.src = e.target.result;
                photoPreview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    });
</script>
@endpush
@endsection
