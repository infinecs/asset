@extends('layouts.app')
@section('title', $digitalProduct->name . ' - Digital Products')
@section('page-title', 'Digital Product Details')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h5 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $digitalProduct->name }}</h5>
        <p class="mb-0 text-sm text-slate-500 dark:text-slate-400">{{ $digitalProduct->brand?->name ?? 'No brand' }}@if($digitalProduct->plan) · {{ $digitalProduct->plan }}@endif</p>
    </div>
    <div class="flex gap-2">
        @if(auth()->user()->isAdmin())
        <a href="{{ route('digital-products.edit', $digitalProduct) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i>Edit</a>
        @endif
        <a href="{{ route('digital-products.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
    <div class="lg:col-span-4">
        <!-- Product Info -->
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Product Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">In Use</span>
                    <span class="badge badge-primary">{{ $digitalProduct->employees->count() }} {{ Str::plural('employee', $digitalProduct->employees->count()) }}</span>
                </div>
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Purchase Date</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $digitalProduct->purchase_date?->format('d M Y') ?? '-' }}</span>
                </div>
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Purchase Cost</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $digitalProduct->purchase_cost ? 'MYR ' . number_format($digitalProduct->purchase_cost, 2) : '-' }}</span>
                </div>
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Renewal Date</span>
                    <span class="text-sm font-semibold">
                        @if($digitalProduct->renewal_date)
                            @if($digitalProduct->isRenewalExpired())
                                <span class="text-red-600 dark:text-red-400">{{ $digitalProduct->renewal_date->format('d M Y') }} (Expired)</span>
                            @elseif($digitalProduct->isRenewalExpiringSoon())
                                <span class="text-amber-600 dark:text-amber-400">{{ $digitalProduct->renewal_date->format('d M Y') }} (Soon)</span>
                            @else
                                <span class="text-green-600 dark:text-green-400">{{ $digitalProduct->renewal_date->format('d M Y') }}</span>
                            @endif
                        @else
                            <span class="text-slate-800 dark:text-slate-100">-</span>
                        @endif
                    </span>
                </div>
                @if($digitalProduct->notes)
                <div class="border-t border-slate-100 pt-3 dark:border-slate-800">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Notes</span>
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $digitalProduct->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="lg:col-span-8">
        <div class="card">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-people me-2 text-slate-400"></i>Assigned Employees ({{ $digitalProduct->employees->count() }})</h6>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('digital-products.edit', $digitalProduct) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i>Edit Assignments</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>ID Number</th>
                            <th>Assigned On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($digitalProduct->employees as $employee)
                        <tr>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $employee->name }}</td>
                            <td><code class="text-primary-600 dark:text-primary-400">{{ $employee->id_number }}</code></td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $employee->pivot->assigned_at ? \Illuminate\Support\Carbon::parse($employee->pivot->assigned_at)->format('d M Y') : '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                <i class="bi bi-people mb-2 block text-2xl"></i>
                                No employees assigned yet.
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('digital-products.edit', $digitalProduct) }}" class="text-primary-600 hover:underline dark:text-primary-400">Assign someone</a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
