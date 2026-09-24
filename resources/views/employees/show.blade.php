@extends('layouts.app')
@section('title', $employee->name . ' - Employees')
@section('page-title', 'Employee Profile')
@section('content')
@php $safeReturn = request('return') && str_starts_with(request('return'), '/employees') ? request('return') : null; @endphp
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</h5>
    <div class="flex gap-2">
        @if(auth()->user()->isAdmin())
        <a href="{{ route('employees.edit', $employee) }}{{ $safeReturn ? '?return=' . urlencode($safeReturn) : '' }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i>Edit</a>
        @endif
        <a href="{{ $safeReturn ? url($safeReturn) : route('employees.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
    <div class="lg:col-span-4">
        <!-- Profile Card -->
        <div class="card">
            <div class="p-6 text-center">
                <div class="mx-auto mb-3 flex items-center justify-center rounded-full bg-primary-600" style="width:72px;height:72px;">
                    <span class="text-2xl font-bold text-white">{{ substr($employee->name, 0, 1) }}</span>
                </div>
                <h5 class="mb-1 font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</h5>
                <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">{{ $employee->email }}</p>
                <div class="flex flex-wrap items-center justify-center gap-1.5">
                    <span class="badge badge-{{ $employee->status_badge }} px-3 py-1">{{ $employee->status_label }}</span>
                    @if($employee->role)
                    <span class="badge badge-primary px-3 py-1">{{ $employee->role->name }}</span>
                    @endif
                    @if($employee->team)
                    <span class="badge badge-secondary px-3 py-1">{{ $employee->team->name }}</span>
                    @endif
                    @if($employee->is_manager)
                    <span class="badge badge-secondary px-3 py-1"><i class="bi bi-diagram-3 me-1"></i>Manager</span>
                    @endif
                </div>
            </div>
            <div class="border-t border-slate-200 px-6 py-3 dark:border-slate-800">
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-slate-500 dark:text-slate-400"><i class="bi bi-person-vcard me-2"></i>ID Number</span>
                    <code class="text-sm">{{ $employee->id_number }}</code>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 py-2 dark:border-slate-800">
                    <span class="text-sm text-slate-500 dark:text-slate-400"><i class="bi bi-geo-alt me-2"></i>Location</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $employee->work_location ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 py-2 dark:border-slate-800">
                    <span class="text-sm text-slate-500 dark:text-slate-400"><i class="bi bi-people me-2"></i>Team</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $employee->team->name ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 py-2 dark:border-slate-800">
                    <span class="text-sm text-slate-500 dark:text-slate-400"><i class="bi bi-person-arms-up me-2"></i>Manager</span>
                    @if($employee->manager)
                    <a href="{{ route('employees.show', $employee->manager) }}" class="text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400">{{ $employee->manager->name }}</a>
                    @else
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">-</span>
                    @endif
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 py-2 dark:border-slate-800">
                    <span class="text-sm text-slate-500 dark:text-slate-400"><i class="bi bi-box-arrow-in-right me-2"></i>Joined</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $employee->join_date?->format('d M Y') ?? '—' }}</span>
                </div>
                @if($employee->status === 'resigned')
                <div class="flex items-center justify-between border-t border-slate-100 py-2 dark:border-slate-800">
                    <span class="text-sm text-slate-500 dark:text-slate-400"><i class="bi bi-box-arrow-right me-2"></i>Resigned</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $employee->resigned_date?->format('d M Y') ?? '—' }}</span>
                </div>
                @endif
                <div class="flex items-center justify-between border-t border-slate-100 py-2 dark:border-slate-800">
                    <span class="text-sm text-slate-500 dark:text-slate-400"><i class="bi bi-calendar3 me-2"></i>Added</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $employee->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        @if($employee->status === 'resigned')
        @php
            $offboardAssetsRemaining = $employee->assets->count();
            $offboardProductsRemaining = $employee->digitalProducts->count();
            $offboardReportsRemaining = $employee->is_manager ? $employee->subordinates->count() : 0;
            $offboardTeamsRemaining = $employee->is_manager ? $employee->managedTeams->count() : 0;
        @endphp
        <!-- Offboarding Checklist -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-clipboard-check me-2 text-slate-400"></i>Offboarding Checklist</h6>
            </div>
            <div class="card-body space-y-3">
                <div class="flex items-start gap-2">
                    <i class="bi bi-{{ $offboardAssetsRemaining === 0 ? 'check-circle-fill text-green-600' : 'exclamation-circle text-amber-500' }} mt-0.5"></i>
                    <div class="flex-1 text-sm">
                        <span class="text-slate-700 dark:text-slate-200">Reclaim assigned assets</span>
                        @if($offboardAssetsRemaining > 0)
                        <a href="#assigned-assets" class="ms-1 text-primary-600 hover:underline dark:text-primary-400">({{ $offboardAssetsRemaining }} remaining)</a>
                        @else
                        <span class="text-slate-400"> — done</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <i class="bi bi-{{ $offboardProductsRemaining === 0 ? 'check-circle-fill text-green-600' : 'exclamation-circle text-amber-500' }} mt-0.5"></i>
                    <div class="flex-1 text-sm">
                        <span class="text-slate-700 dark:text-slate-200">Revoke digital product licenses</span>
                        @if($offboardProductsRemaining > 0)
                        <span class="text-slate-400">({{ $offboardProductsRemaining }} assigned)</span>
                        @if(auth()->user()->isAdmin())
                        <form action="{{ route('employees.revoke-digital-products', $employee) }}" method="POST" class="mt-1" onsubmit="return confirm('Revoke all {{ $offboardProductsRemaining }} license(s) from {{ $employee->name }}?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">Revoke All</button>
                        </form>
                        @endif
                        @else
                        <span class="text-slate-400"> — done</span>
                        @endif
                    </div>
                </div>
                @if($employee->is_manager)
                <div class="flex items-start gap-2">
                    <i class="bi bi-{{ $offboardReportsRemaining === 0 ? 'check-circle-fill text-green-600' : 'exclamation-circle text-amber-500' }} mt-0.5"></i>
                    <div class="flex-1 text-sm">
                        <span class="text-slate-700 dark:text-slate-200">Reassign direct reports</span>
                        @if($offboardReportsRemaining > 0)
                        <a href="{{ route('employees.bulk-edit-org') }}" class="ms-1 text-primary-600 hover:underline dark:text-primary-400">({{ $offboardReportsRemaining }} still reporting to them)</a>
                        @else
                        <span class="text-slate-400"> — done</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <i class="bi bi-{{ $offboardTeamsRemaining === 0 ? 'check-circle-fill text-green-600' : 'exclamation-circle text-amber-500' }} mt-0.5"></i>
                    <div class="flex-1 text-sm">
                        <span class="text-slate-700 dark:text-slate-200">Reassign managed teams</span>
                        @if($offboardTeamsRemaining > 0)
                        <a href="{{ route('teams.index') }}" class="ms-1 text-primary-600 hover:underline dark:text-primary-400">({{ $offboardTeamsRemaining }} team(s) still assigned)</a>
                        @else
                        <span class="text-slate-400"> — done</span>
                        @endif
                    </div>
                </div>
                @endif
                <div class="flex items-start gap-2">
                    <i class="bi bi-info-circle text-slate-400 mt-0.5"></i>
                    <div class="flex-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ $employee->documents->count() }} document(s) on file — kept for records, no action needed.
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($employee->is_manager && $employee->managedTeams->isNotEmpty())
        <!-- Teams Managed Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-people me-2 text-slate-400"></i>Teams Managed ({{ $employee->managedTeams->count() }})</h6>
            </div>
            <div>
                @foreach($employee->managedTeams as $managedTeam)
                <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-3 last:border-b-0 dark:border-slate-800">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-500">
                        <i class="bi bi-people text-xs text-white"></i>
                    </div>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $managedTeam->name }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($employee->is_manager)
        <!-- Direct Reports Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-diagram-3 me-2 text-slate-400"></i>Direct Reports ({{ $employee->subordinates->count() }})</h6>
            </div>
            <div>
                @php $groupedSubordinates = $employee->subordinates->groupBy(fn ($s) => $s->team->name ?? 'No Team')->sortKeys(); @endphp
                @forelse($groupedSubordinates as $teamName => $members)
                <div class="border-b-2 border-slate-200 last:border-b-0 dark:border-slate-700">
                    <div class="bg-slate-50 px-6 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">{{ $teamName }}</div>
                    @foreach($members as $subordinate)
                    <a href="{{ route('employees.show', $subordinate) }}" class="flex items-center gap-3 border-t border-slate-100 px-6 py-3 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600">
                            <span class="text-xs font-bold text-white">{{ substr($subordinate->name, 0, 1) }}</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $subordinate->name }}</span>
                    </a>
                    @endforeach
                </div>
                @empty
                <div class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">No direct reports yet.</div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- HR Documents Upload Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-cloud-upload me-2 text-slate-400"></i>Upload Document</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('employees.upload-document', $employee) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="document_type" class="field-label">Document Type</label>
                        <select name="document_type" id="document_type" class="field-input @error('document_type') is-invalid @enderror">
                            <option value="">Select document type</option>
                            <option value="contract">Contract</option>
                            <option value="certification">Certification</option>
                            <option value="resume">Resume</option>
                            <option value="id_proof">ID Proof</option>
                            <option value="medical">Medical Records</option>
                            <option value="nda">NDA</option>
                            <option value="training">Training Certificate</option>
                            <option value="other">Other</option>
                        </select>
                        @error('document_type')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="file" class="field-label">File</label>
                        <input type="file" name="file" id="file" class="field-input @error('file') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                        <p class="field-hint">Max 10MB. Allowed: PDF, DOC, DOCX, JPG, PNG, TXT</p>
                        @error('file')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="field-label">Description (Optional)</label>
                        <textarea name="description" id="description" class="field-input @error('description') is-invalid @enderror" rows="2" placeholder="Add notes about this document..."></textarea>
                        @error('description')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full"><i class="bi bi-cloud-upload"></i>Upload Document</button>
                </form>
            </div>
        </div>
    </div>

    <div class="lg:col-span-8">
        <!-- Assets Section -->
        @php $canReclaim = auth()->user()->isAdmin() && $employee->status === 'resigned' && $employee->assets->isNotEmpty(); @endphp
        <div id="assigned-assets" class="card mb-4">
            <div class="card-header flex items-center justify-between">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-laptop me-2 text-slate-400"></i>Assigned Assets ({{ $employee->assets->count() }})</h6>
                @if($canReclaim)
                <span class="badge badge-warning"><i class="bi bi-exclamation-triangle me-1"></i>Needs reclaim</span>
                @endif
            </div>
            @if($canReclaim)
            <form method="POST" action="{{ route('employees.reclaim-assets', $employee) }}" onsubmit="return confirm('Reclaim the selected assets from {{ $employee->name }}?')">
                @csrf
            @endif
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
                            @if($canReclaim)
                            <th class="w-8"><input type="checkbox" onclick="document.querySelectorAll('.reclaim-checkbox').forEach(c => c.checked = this.checked)" checked></th>
                            @endif
                            <th>Asset Tag</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->assets as $asset)
                        <tr>
                            @if($canReclaim)
                            <td><input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}" class="reclaim-checkbox" checked></td>
                            @endif
                            <td><code class="text-primary-600 dark:text-primary-400">{{ $asset->asset_tag ?? '-' }}</code></td>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->name }}</td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $asset->category?->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $asset->status_badge }}">{{ $asset->status_label }}</span></td>
                            <td class="text-right"><a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $canReclaim ? 6 : 5 }}" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                <i class="bi bi-laptop mb-2 block text-2xl"></i>
                                No assets assigned
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($canReclaim)
                <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Reclaim selected and set status to</span>
                    <select name="status" class="field-input w-auto">
                        <option value="available">Available</option>
                        <option value="under_maintenance">Under Maintenance</option>
                        <option value="retired">Retired</option>
                        <option value="lost">Lost</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-box-arrow-in-left"></i>Reclaim Selected</button>
                </div>
            </form>
            @endif
        </div>

        <!-- Assigned Digital Products -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-key me-2 text-slate-400"></i>Assigned Digital Products ({{ $employee->digitalProducts->count() }})</h6>
            </div>
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Plan</th>
                            <th>Assigned On</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->digitalProducts as $product)
                        <tr>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $product->name }}</td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $product->plan ?? '-' }}</td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $product->pivot->assigned_at ? \Illuminate\Support\Carbon::parse($product->pivot->assigned_at)->format('d M Y') : '-' }}</td>
                            <td class="text-right"><a href="{{ route('digital-products.show', $product) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                <i class="bi bi-key mb-2 block text-2xl"></i>
                                No digital products assigned
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- HR Documents Display Section -->
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-folder2 me-2 text-slate-400"></i>Document Files ({{ $employee->documents->count() }})</h6>
            </div>
            <div>
                @forelse($employee->documents->sortByDesc('created_at') as $document)
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-3 last:border-b-0 dark:border-slate-800">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40">
                            @if(in_array(pathinfo($document->file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png']))
                            <i class="bi bi-image text-green-600 dark:text-green-400"></i>
                            @elseif(pathinfo($document->file_path, PATHINFO_EXTENSION) === 'pdf')
                            <i class="bi bi-file-pdf text-red-600 dark:text-red-400"></i>
                            @else
                            <i class="bi bi-file-text text-primary-600 dark:text-primary-400"></i>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $document->file_name }}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                                <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</span>
                                {{ number_format($document->file_size / 1024, 2) }} KB · {{ $document->created_at->format('d M Y, H:i') }}
                            </div>
                            @if($document->description)
                            <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $document->description }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <a href="{{ route('employees.download-document', [$employee, $document]) }}" class="btn btn-sm btn-outline btn-icon" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                        @if(auth()->user()->isAdmin())
                        <form action="{{ route('employees.delete-document', [$employee, $document]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this document?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-slate-500 dark:text-slate-400">
                    <i class="bi bi-folder2-open mb-2 block text-2xl"></i>
                    No documents uploaded
                </div>
                @endforelse
            </div>
        </div>

        <!-- Activity History -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-clock-history me-2 text-slate-400"></i>Activity History</h6>
            </div>
            <div class="card-body">
                @forelse($activityTimeline as $activity)
                <div class="flex gap-3">
                    <div class="flex flex-col items-center">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40">
                            <i class="bi bi-{{ $activity->icon }} text-sm text-primary-600 dark:text-primary-400"></i>
                        </div>
                        @if(!$loop->last)
                        <div class="mt-1 w-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 pb-5 last:pb-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $activity->title }}</div>
                            <div class="text-xs text-slate-400 dark:text-slate-500">{{ $activity->at->diffForHumans() }}</div>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">by {{ $activity->by }}</div>
                        @if($activity->notes)
                        <div class="mt-1 text-sm text-slate-500 dark:text-slate-400 break-words">{{ $activity->notes }}</div>
                        @endif
                        @if($activity->changes->isNotEmpty())
                        <div class="mt-2 space-y-1.5 rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                            @foreach($activity->changes as $change)
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $change['label'] }}:</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ $change['old'] }}</span>
                                <i class="bi bi-arrow-right text-slate-400"></i>
                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $change['new'] }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">No history</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
