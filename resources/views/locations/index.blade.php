@extends('layouts.app')
@section('title', 'Locations')
@section('page-title', 'Locations')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">Locations</h5>
    <a href="{{ route('locations.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i>Add Location</a>
</div>
<div class="card" data-bulk-container>
    <div class="card-header">
        <span class="text-sm text-slate-500 dark:text-slate-400"><span data-bulk-count>0</span> selected</span>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('locations.bulk-destroy') }}" data-bulk-form onsubmit="return confirm('Delete selected locations?')">
                @csrf
                @method('DELETE')
                <span data-bulk-inputs></span>
                <button type="submit" class="btn btn-outline-danger btn-sm" data-bulk-delete-selected>
                    <i class="bi bi-trash"></i>Delete Selected
                </button>
            </form>
            <form method="POST" action="{{ route('locations.destroy-all') }}" onsubmit="return confirm('Delete all locations? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash3"></i>Delete All
                </button>
            </form>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr><th class="w-10"><input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" data-bulk-select-all></th><th>Name</th><th>Building</th><th>Floor / Room</th><th>City / Postcode</th><th>State</th><th>Assets</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($locations as $loc)
                <tr>
                    <td><input type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-primary-600" data-bulk-row value="{{ $loc->id }}"></td>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $loc->name }}</td>
                    <td>{{ $loc->building ?? '-' }}</td>
                    <td>{{ implode(' / ', array_filter([$loc->floor, $loc->room])) ?: '-' }}</td>
                    <td>{{ implode(' / ', array_filter([$loc->city, $loc->postcode])) ?: '-' }}</td>
                    <td>{{ $loc->state ?? '-' }}</td>
                    <td><span class="badge badge-primary">{{ $loc->assets_count }}</span></td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('locations.edit', $loc) }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('locations.destroy', $loc) }}" onsubmit="return confirm('Delete this location?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="py-8 text-center text-slate-500 dark:text-slate-400">No locations yet. <a href="{{ route('locations.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
