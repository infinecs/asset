@extends('layouts.app')

@section('title', $asset->name . ' - IT Asset Management')
@section('page-title', 'Asset Details')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $asset->name }}</h5>
        <code class="text-slate-500 dark:text-slate-400">{{ $asset->asset_tag }}</code>
    </div>
    <div class="flex gap-2">
        @if(auth()->user()->isStaff())
        <a href="{{ route('assets.edit', $asset) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil"></i>Edit
        </a>
        @endif
        <a href="{{ route('assets.index') }}" class="btn btn-outline btn-sm">
            <i class="bi bi-arrow-left"></i>Back
        </a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
    <!-- Asset Details -->
    <div class="lg:col-span-8">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Asset Information</h6>
                <span class="badge badge-{{ $asset->status_badge }} px-3 py-1.5">
                    <span class="status-dot {{ $asset->status }}"></span>{{ $asset->status_label }}
                </span>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Asset Tag</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100"><code>{{ $asset->asset_tag }}</code></div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Serial Number</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->serial_number ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Brand</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->brand_label }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Model</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->model ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Category</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->category?->name ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Location</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->location?->name ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Assigned To</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->assignedEmployee?->name ?? 'Unassigned' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Last Seen</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->last_seen_at ? $asset->last_seen_at->format('d M Y H:i') : 'Never' }}</div>
                    </div>
                    @if($asset->notes)
                    <div class="sm:col-span-2">
                        <label class="text-sm text-slate-500 dark:text-slate-400">Notes</label>
                        <div class="text-slate-700 dark:text-slate-300">{{ $asset->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Purchase Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Purchase Information</h6>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Purchase Date</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->purchase_date ? $asset->purchase_date->format('d M Y') : '-' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Purchase Cost</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->purchase_cost ? 'MYR ' . number_format($asset->purchase_cost, 2) : '-' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Warranty Expiry</label>
                        <div class="font-semibold">
                            @if($asset->warranty_expiry)
                                @if($asset->isWarrantyExpired())
                                    <span class="text-red-600 dark:text-red-400"><i class="bi bi-x-circle me-1"></i>{{ $asset->warranty_expiry->format('d M Y') }} (Expired)</span>
                                @elseif($asset->isWarrantyExpiringSoon())
                                    <span class="text-amber-600 dark:text-amber-400"><i class="bi bi-exclamation-triangle me-1"></i>{{ $asset->warranty_expiry->format('d M Y') }} (Expiring soon)</span>
                                @else
                                    <span class="text-green-600 dark:text-green-400"><i class="bi bi-check-circle me-1"></i>{{ $asset->warranty_expiry->format('d M Y') }}</span>
                                @endif
                            @else
                                <span class="text-slate-800 dark:text-slate-100">-</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technical Details -->
        @if($asset->cpu || $asset->ram || $asset->storage || $asset->display)
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-cpu me-2 text-slate-400"></i>Technical Details</h6>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">CPU</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->cpu ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">RAM</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->ram ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Storage</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->storage ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500 dark:text-slate-400">Display</label>
                        <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->display ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Asset Photo</h6>
            </div>
            <div class="card-body text-center">
                @if($asset->photo_path)
                <img src="{{ asset('storage/' . $asset->photo_path) }}" alt="{{ $asset->name }}" class="mx-auto max-h-60 w-full rounded-lg border border-slate-200 object-cover dark:border-slate-700">
                @else
                <div class="py-8 text-sm text-slate-400">No photo uploaded.</div>
                @endif
            </div>
        </div>

        <!-- Signed Document -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Signed Document</h6>
            </div>
            <div class="card-body text-center">
                @if($asset->signed_document_path)
                <a href="{{ asset('storage/' . $asset->signed_document_path) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-text"></i>View / Download
                </a>
                @else
                <div class="py-8 text-sm text-slate-400">No document uploaded.</div>
                @endif
            </div>
        </div>

        <!-- Quick Status Update -->
        @if(auth()->user()->isStaff())
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.update-status', $asset) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <select name="status" class="field-input">
                            @foreach(['available', 'in_use', 'under_maintenance', 'retired', 'lost'] as $s)
                            <option value="{{ $s }}" {{ $asset->status == $s ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $s)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <textarea name="notes" class="field-input" rows="2" placeholder="Update notes..."></textarea>
                    </div>
                    <button class="btn btn-primary w-full">
                        <i class="bi bi-arrow-repeat"></i>Update Status
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- History -->
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Activity History</h6>
            </div>
            <div>
                @forelse($activityTimeline as $activity)
                <div class="flex gap-2 border-b border-slate-100 p-3 last:border-b-0 dark:border-slate-800">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40">
                        <i class="bi bi-{{ $activity->icon }} text-sm text-slate-500 dark:text-slate-400"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $activity->title }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $activity->by }} · {{ $activity->at->diffForHumans() }}
                        </div>
                        @if($activity->notes)
                        <div class="text-sm text-slate-500 dark:text-slate-400">{{ $activity->notes }}</div>
                        @endif
                        @if(!empty($activity->changes))
                        <ul class="mt-1 list-none space-y-0.5 p-0">
                            @foreach($activity->changes as $change)
                            <li class="text-xs text-slate-500 dark:text-slate-400">
                                <span class="font-semibold">{{ $change['label'] }}:</span>
                                {{ $change['old'] }} <i class="bi bi-arrow-right mx-1"></i> {{ $change['new'] }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
                @empty
                <div class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">No history</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
