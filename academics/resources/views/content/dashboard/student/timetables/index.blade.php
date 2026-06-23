@extends('layouts.modernLayout', [
    'pageTitle' => 'Timetables'
])

@section('title', 'Timetable Documents')

@section('content')
<div class="container">
    <h4 class="fw-bold py-3">Timetable Documents</h4>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Error Message --}}
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('timetables.index') }}" class="mb-4 row g-3">
        <div class="col-md-4">
            <select name="class_id" class="form-select">
                <option value="">-- Filter by Class --</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                        {{ $class->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <select name="type" class="form-select">
                <option value="">-- Filter by Type --</option>
                <option value="class" {{ request('type') == 'class' ? 'selected' : '' }}>Class</option>
                <option value="exam" {{ request('type') == 'exam' ? 'selected' : '' }}>Exam</option>
            </select>
        </div>

        <div class="col-md-4 d-flex">
            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
            <a href="{{ route('timetables.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    {{-- Upload Button --}}
    @if ($isAdmin)
    <div class="mb-3">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bx bx-upload me-1"></i> Upload New Timetable
        </button>
    </div>
    @endif

    {{-- Timetable Table --}}
    <div class="card">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Class</th>
                        <th>Type</th>
                        <th>File Name</th>
                        <th>Description</th>
                        <th>Uploaded At</th>
                        @if ($isAdmin)
                        <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $index => $document)
                        <tr>
                            <td>{{ $documents->firstItem() + $index }}</td>
                            <td>{{ $document->class->name ?? '-' }}</td>
                            <td>{{ ucfirst($document->type) }}</td>
                            <td>
                              <a href="{{ route('timetables.download', $document->id) }}" class="btn btn-sm btn-outline-primary">
                                {{ $document->original_name ?? basename($document->document_path) }}
                              </a>
                            </td>

                            <td>{{ $document->description ?? '-' }}</td>
                            <td>{{ $document->created_at->format('d M Y') }}</td>
                            @if ($isAdmin)
                            <td>
                                <form action="{{ route('timetables.destroy', $document->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this timetable?');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Timetable">
                                        <i class="bx bx-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No documents found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $documents->appends(request()->query())->links() }}
    </div>
</div>

{{-- Upload Modal --}}
@if ($isAdmin)
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('timetables.store') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload Timetable</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Class --}}
                <div class="mb-3">
                    <label for="class_id" class="form-label">Class</label>
                    <select name="class_id" id="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                        <option value="">-- Select Class --</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('class_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Type --}}
                <div class="mb-3">
                    <label for="type" class="form-label">Timetable Type</label>
                    <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="class" {{ old('type') == 'class' ? 'selected' : '' }}>Class</option>
                        <option value="exam" {{ old('type') == 'exam' ? 'selected' : '' }}>Exam</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="mb-3">
                    <label for="description" class="form-label">Description (optional)</label>
                    <textarea name="description" id="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- File --}}
                <div class="mb-3">
                    <label for="file" class="form-label">PDF File</label>
                    <input type="file" name="file" id="file" accept="application/pdf" class="form-control @error('file') is-invalid @enderror" required>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Upload</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Keep modal open if validation errors --}}
@if ($errors->any())
<script>
    var uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
    uploadModal.show();
</script>
@endif

@endsection
