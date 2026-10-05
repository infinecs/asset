@extends('layouts.app')
@section('title', 'Digital Products')
@section('page-title', 'Digital Products')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Digital Products</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">Software licenses assigned to employees</p>
    </div>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('digital-products.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i>Add Digital Product</a>
    @endif
</div>

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('digital-products.index') }}" class="flex gap-2">
            <div class="relative flex-1">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" class="field-input pl-9" value="{{ request('search') }}" placeholder="Search name, plan, brand...">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ route('digital-products.index') }}" class="btn btn-outline">Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-clean">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Plan</th>
                    <th>In Use</th>
                    <th>Assigned To</th>
                    <th>Renewal</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($digitalProducts as $product)
                <tr>
                    <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $product->name }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $product->brand?->name ?? '-' }}</td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $product->plan ?? '-' }}</td>
                    <td>
                        <span class="badge badge-primary">{{ $product->employees_count }}</span>
                    </td>
                    <td>
                        @if($product->employees->isEmpty())
                        <span class="text-slate-400">-</span>
                        @else
                        <div class="flex flex-wrap gap-1">
                            @foreach($product->employees->take(3) as $employee)
                            <span class="badge badge-secondary">{{ $employee->name }}</span>
                            @endforeach
                            @if($product->employees->count() > 3)
                            <a href="{{ route('digital-products.show', $product) }}" class="text-xs text-primary-600 hover:underline dark:text-primary-400">+{{ $product->employees->count() - 3 }} more</a>
                            @endif
                        </div>
                        @endif
                    </td>
                    <td>
                        @if($product->renewal_date)
                            @if($product->isRenewalExpired())
                                <span class="text-red-600 dark:text-red-400"><i class="bi bi-x-circle me-1"></i>{{ $product->renewal_date->format('d M Y') }}</span>
                            @elseif($product->isRenewalExpiringSoon())
                                <span class="text-amber-600 dark:text-amber-400"><i class="bi bi-exclamation-triangle me-1"></i>{{ $product->renewal_date->format('d M Y') }}</span>
                            @else
                                <span class="text-slate-600 dark:text-slate-300">{{ $product->renewal_date->format('d M Y') }}</span>
                            @endif
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="inline-flex gap-1">
                            <a href="{{ route('digital-products.show', $product) }}" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye"></i></a>
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('digital-products.edit', $product) }}" class="btn btn-sm btn-outline btn-icon"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('digital-products.destroy', $product) }}" onsubmit="return confirm('Delete this digital product?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger btn-icon"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                        <i class="bi bi-key mb-2 block text-3xl"></i>
                        No digital products yet.
                        @if(auth()->user()->isAdmin())
                        <a href="{{ route('digital-products.create') }}" class="text-primary-600 hover:underline dark:text-primary-400">Add one</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($digitalProducts->hasPages())
    <div class="card-footer">
        {{ $digitalProducts->links() }}
    </div>
    @endif
</div>
@endsection
