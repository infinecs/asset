@extends('layouts.app')
@section('title', $employee->name . ' - Employees')
@section('page-title', 'Employee Profile')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">{{ $employee->name }}</h5>
    <div class="d-flex gap-2">
        @if(auth()->user()->isAdmin())
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endif
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;">
                    <span class="text-white fw-bold fs-3">{{ substr($employee->name, 0, 1) }}</span>
                </div>
                <h5 class="mb-1">{{ $employee->name }}</h5>
                <p class="text-muted mb-2">{{ $employee->email }}</p>
                <span class="badge bg-{{ $employee->status_badge }} mb-3">{{ $employee->status_label }}</span>
                <dl class="text-start small row mb-0">
                    <dt class="col-sm-5 text-muted">ID Number</dt>
                    <dd class="col-sm-7"><code>{{ $employee->id_number }}</code></dd>
                    <dt class="col-sm-5 text-muted">Work Location</dt>
                    <dd class="col-sm-7">{{ $employee->work_location ?? '-' }}</dd>
                    <dt class="col-sm-5 text-muted">Added</dt>
                    <dd class="col-sm-7">{{ $employee->created_at->format('d M Y') }}</dd>
                </dl>
            </div>
        </div>

        <!-- HR Documents Upload Card -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="mb-0 fw-semibold">HR Documents</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('employees.upload-document', $employee) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="document_type" class="form-label small fw-semibold">Document Type</label>
                        <select name="document_type" id="document_type" class="form-select form-select-sm @error('document_type') is-invalid @enderror">
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
                        @error('document_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label small fw-semibold">File</label>
                        <input type="file" name="file" id="file" class="form-control form-control-sm @error('file') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                        <small class="text-muted">Max 10MB. Allowed: PDF, DOC, DOCX, JPG, PNG, TXT</small>
                        @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label small fw-semibold">Description (Optional)</label>
                        <textarea name="description" id="description" class="form-control form-control-sm @error('description') is-invalid @enderror" rows="2" placeholder="Add notes about this document..."></textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-cloud-upload me-1"></i>Upload Document</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Assets Section -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Assigned Assets ({{ $employee->assets->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Asset Tag</th><th>Name</th><th>Category</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($employee->assets as $asset)
                        <tr>
                            <td><code class="text-primary small">{{ $asset->asset_tag }}</code></td>
                            <td class="fw-semibold small">{{ $asset->name }}</td>
                            <td class="small text-muted">{{ $asset->category?->name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $asset->status_badge }}">{{ $asset->status_label }}</span></td>
                            <td><a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No assets assigned</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- HR Documents Display Section -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-3">
                <h6 class="mb-0 fw-semibold">Document Files ({{ $employee->documents->count() }})</h6>
            </div>
            <div class="card-body">
                @forelse($employee->documents->sortByDesc('created_at') as $document)
                <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2">
                            <div>
                                @if(in_array(pathinfo($document->file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png']))
                                <i class="bi bi-image text-success"></i>
                                @elseif(pathinfo($document->file_path, PATHINFO_EXTENSION) === 'pdf')
                                <i class="bi bi-file-pdf text-danger"></i>
                                @else
                                <i class="bi bi-file-text text-primary"></i>
                                @endif
                            </div>
                            <div>
                                <h6 class="mb-1 small fw-semibold">{{ $document->file_name }}</h6>
                                <small class="text-muted d-block">
                                    Type: <span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</span>
                                    • Size: {{ number_format($document->file_size / 1024, 2) }}KB
                                    • Uploaded: {{ $document->created_at->format('d M Y, H:i') }}
                                </small>
                                @if($document->description)
                                <small class="text-muted d-block mt-1">{{ $document->description }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('employees.download-document', [$employee, $document]) }}" class="btn btn-sm btn-outline-secondary" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                        @if(auth()->user()->isAdmin())
                        <form action="{{ route('employees.delete-document', [$employee, $document]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this document?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-center text-muted py-3">No documents uploaded</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
