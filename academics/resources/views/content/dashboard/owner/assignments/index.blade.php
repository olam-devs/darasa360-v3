@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Assignments'
])
@section('title', 'Manage Assignments')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Assignments</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
        <i class="bx bx-plus"></i> Add Assignment
      </button>
    </div>


    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
    {{-- Search --}}
    <form method="GET" action="{{ route('assignments.index') }}" class="mb-4 row g-3">
      <div class="col-md-6">
        <input
          type="text"
          name="search"
          class="form-control"
          placeholder="Search by title"
          value="{{ request('search') }}"
        />
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit"><i class="bx bx-search"></i> Search</button>
      </div>
    </form>


    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Class</th>
              <th>Subject</th>
              <th>Uploaded</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($assignments as $assignment)
              <tr>
                <td>{{ $loop->iteration + ($assignments->currentPage() - 1) * $assignments->perPage() }}</td>
                <td>{{ $assignment->title }}</td>
                <td>{{ $assignment->class->name ?? 'N/A' }}</td>
                <td>{{ $assignment->subject->name ?? 'N/A' }}</td>
                <td>{{ $assignment->uploaded_at }}</td>
                <td>
                  <a href="{{ route('assignments.download', $assignment->id) }}" class="btn btn-sm btn-outline-info me-1">
                    <i class="bx bx-download"></i>
                  </a>

                  <a href="{{ route('assignments.edit', $assignment->id) }}" class="btn btn-sm btn-outline-warning me-1">
                    <i class="bx bx-edit"></i>
                  </a>

                  <form action="{{ route('assignments.destroy', $assignment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this assignment?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center">No assignments found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-3 mx-3">
        {{ $assignments->withQueryString()->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('assignments.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Assignment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Class</label>
            <select name="class_id" id="classSelect" class="form-control" required>
              <option value="">Select Class</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label>Subject</label>
            <select name="subject_id" id="subjectSelect" class="form-control" required>
              <option value="">Select Subject</option>
              @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label>File</label>
            <input type="file" name="assignment_file" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit">Add</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const classSelect = document.getElementById('classSelect');
  const subjectSelect = document.getElementById('subjectSelect');

  classSelect?.addEventListener('change', function() {
    fetchSubjects(this.value, subjectSelect);
  });

  function fetchSubjects(classId, subjectElement) {
    if(!classId) return Promise.resolve();
    return fetch(`/assignments/class-subjects/${classId}`)
      .then(res => res.json())
      .then(data => {
        subjectElement.innerHTML = '';
        if(data.length){
          data.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            opt.text = sub.name;
            subjectElement.appendChild(opt);
          });
        } else {
          const opt = document.createElement('option');
          opt.text = 'No subjects available';
          opt.disabled = true;
          subjectElement.appendChild(opt);
        }
      });
  }
});
</script>
@endsection
