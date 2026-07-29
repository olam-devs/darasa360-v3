@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Notes'
])

@section('title', 'Notes Management')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Notes</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
        <i class="bx bx-plus"></i> Add Note
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
    <form method="GET" action="{{ route('notes.home') }}" class="mb-4 row g-3">
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
              <th>File</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @php
              $counter = ($notes->currentPage() - 1) * $notes->perPage();
            @endphp
            @forelse($notes as $classData)
              @foreach($classData['subjects'] as $subjectData)
                @foreach($subjectData['notes'] as $note)
                  @php $counter++; @endphp
                  <tr>
                    <td>{{ $counter }}</td>
                    <td>{{ $note['title'] ?? '' }}</td>
                    <td>{{ $note['class']['name'] ?? 'N/A' }}</td>
                    <td>{{ $note['subject']['name'] ?? 'N/A' }}</td>
                    <td>{{ $note['uploaded_at'] ?? 'N/A' }}</td>
                    <td>
                      <a href="{{ route('notes.download', $note['id']) }}" class="btn btn-sm btn-outline-info me-1">
                        <i class="bx bx-download"></i>
                      </a>
                    </td>
                    <td>
                      <a href="{{ route('notes.edit', $note['id']) }}" class="btn btn-sm btn-outline-warning me-1">
                        <i class="bx bx-edit"></i>
                      </a>
                      <form action="{{ route('notes.destroy', $note['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this note?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              @endforeach
            @empty
              <tr><td colspan="7" class="text-center">No notes found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-3 mx-3">
        {{ $notes->withQueryString()->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('notes.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Note</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Class</label>
            <select name="class_id" id="addClassSelect" class="form-control" required>
              <option value="">Select Class</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label>Subject</label>
            <select name="subject_id" id="addSubjectSelect" class="form-control" required>
              <option value="">Select Subject</option>
            </select>
          </div>
          <div class="mb-3">
            <label>File</label>
            <input type="file" name="notes_file" class="form-control" required>
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
  const addClassSelect = document.getElementById('addClassSelect');
  const addSubjectSelect = document.getElementById('addSubjectSelect');

  function fetchSubjects(classId, selectEl, selectedId = null) {
    if(!classId) return;
    fetch(`/notes/class-subjects/${classId}`)
      .then(res => res.json())
      .then(data => {
        selectEl.innerHTML = '<option value="">Select Subject</option>';
        data.forEach(sub => {
          const opt = document.createElement('option');
          opt.value = sub.id;
          opt.textContent = sub.name;
          if(selectedId && selectedId == sub.id) opt.selected = true;
          selectEl.appendChild(opt);
        });
      });
  }

  addClassSelect?.addEventListener('change', e => fetchSubjects(e.target.value, addSubjectSelect));
});
</script>
@endsection
