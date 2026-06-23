@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Subjects'
])
@section('title', 'Manage Subjects')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Subjects</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
        <i class="bx bx-plus"></i> Add New Subject
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="GET" action="{{ route('subjects.list') }}" class="row g-3 mb-4">
      <div class="col-md-6">
        <input
          type="text"
          name="search"
          value="{{ request('search') }}"
          class="form-control"
          placeholder="Search by subject name or code"
        >
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit">
          <i class="bx bx-search"></i> Search
        </button>
      </div>
    </form>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Code</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($subjects as $subject)
              <tr>
                <td>{{ $loop->iteration + ($subjects->currentPage() - 1) * $subjects->perPage() }}</td>
                <td>{{ $subject->name }}</td>
                <td>{{ $subject->code ?? '-' }}</td>
                <td>
                  <button
                    class="btn btn-sm btn-outline-warning me-1 edit-subject-btn"
                    data-id="{{ $subject->id }}"
                    data-name="{{ $subject->name }}"
                    data-code="{{ $subject->code }}"
                    title="Edit"
                  >
                    <i class="bx bx-edit"></i>
                  </button>

                  <form action="{{ route('subjects.delete', $subject->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this subject?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center">No subjects found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 mx-3">
        {{ $subjects->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('subjects.store') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="subject_name" class="form-label">Subject Name</label>
            <input
              type="text"
              id="subject_name"
              name="name"
              class="form-control"
              value="{{ old('name') }}"
              required
            >
          </div>
          <div class="mb-3">
            <label for="subject_code" class="form-label">Subject Code</label>
            <input
              type="text"
              id="subject_code"
              name="code"
              class="form-control"
              value="{{ old('code') }}"
            >
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Subject</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Subject Modal -->
<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="editSubjectForm" action="">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_subject_name" class="form-label">Subject Name</label>
            <input type="text" id="edit_subject_name" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="edit_subject_code" class="form-label">Subject Code</label>
            <input type="text" id="edit_subject_code" name="code" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Subject</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  // Laravel route template with placeholder :id
  const updateRouteTemplate = "{{ route('subjects.update', ':id') }}";

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-subject-btn').forEach(button => {
      button.addEventListener('click', function () {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const code = this.dataset.code;

        const form = document.getElementById('editSubjectForm');
        // Replace placeholder with actual ID to get the correct URL
        form.action = updateRouteTemplate.replace(':id', id);

        document.getElementById('edit_subject_name').value = name;
        document.getElementById('edit_subject_code').value = code;

        const editModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
        editModal.show();
      });
    });

    @if($errors->any() && session('form') === 'add')
      const addModal = new bootstrap.Modal(document.getElementById('addSubjectModal'));
      addModal.show();
    @endif
  });
</script>
@endsection
