@extends('layouts.app')
@section('title', $employee->name . ' - Employees')
@section('page-title', 'Employee Profile')
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h5 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</h5>
    <div class="flex gap-2">
        @if(auth()->user()->isAdmin())
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i>Edit</a>
        @endif
        <a href="{{ route('employees.index') }}" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i>Back</a>
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
                <span class="badge badge-{{ $employee->status_badge }} px-3 py-1">{{ $employee->status_label }}</span>
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
                    <span class="text-sm text-slate-500 dark:text-slate-400"><i class="bi bi-calendar3 me-2"></i>Added</span>
                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $employee->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>

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
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><i class="bi bi-laptop me-2 text-slate-400"></i>Assigned Assets ({{ $employee->assets->count() }})</h6>
            </div>
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
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
                            <td><code class="text-primary-600 dark:text-primary-400">{{ $asset->asset_tag }}</code></td>
                            <td class="font-semibold text-slate-800 dark:text-slate-100">{{ $asset->name }}</td>
                            <td class="text-slate-500 dark:text-slate-400">{{ $asset->category?->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $asset->status_badge }}">{{ $asset->status_label }}</span></td>
                            <td class="text-right"><a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                <i class="bi bi-laptop mb-2 block text-2xl"></i>
                                No assets assigned
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
    </div>
</div>
@endsection
