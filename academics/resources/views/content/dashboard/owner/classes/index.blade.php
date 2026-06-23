@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Classes'
])

@section('title', 'Manage Classes')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold">Manage Classes</h4>

  {{-- Show success/error message below heading --}}
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="row mb-3">
    <div class="col text-end">
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
        <i class="fas fa-plus me-2"></i>Add Class
      </button>
    </div>
  </div>

  <div class="row">
    @foreach ($classes as $class)
    <div class="col-md-6 col-lg-4 mb-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-2">{{ $class->name }}</h5>
          <p class="card-subtitle mb-3 text-muted">
            @if ($class->streams->count())
              {{ $class->streams->count() }} Stream(s):
              {{ $class->streams->pluck('name')->join(', ') }}
            @else
              No Streams
            @endif
          </p>

          <div class="d-flex justify-content-between">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#subjectsModal-{{ $class->id }}">
              <i class="fas fa-book-open me-1"></i> Subjects
            </button>

            <div>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editClassModal-{{ $class->id }}">
                <i class="bx bx-edit"></i>
              </button>
              <form action="{{ route('classes.delete', $class->id) }}" method="POST" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this class?')">
                  <i class="bx bx-trash"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal: Subjects --}}
    <div class="modal fade" id="subjectsModal-{{ $class->id }}" tabindex="-1" aria-labelledby="subjectsModalLabel-{{ $class->id }}" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('classes.subjects.save', $class->id) }}">
          @csrf
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Subjects for {{ $class->name }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label>Select Subjects</label>
                <div class="form-check">
                  @foreach ($allSubjects as $subject)
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="subjects[]" value="{{ $subject->id }}"
                        id="subject-{{ $class->id }}-{{ $subject->id }}"
                        {{ $class->subjects->contains($subject->id) ? 'checked' : '' }}>
                      <label class="form-check-label" for="subject-{{ $class->id }}-{{ $subject->id }}">
                        {{ $subject->name }} ({{ $subject->code ?? 'No code' }})
                      </label>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Subjects</button>
            </div>
          </div>
        </form>
      </div>
    </div>

{{-- Modal: Edit Class --}}
<div class="modal fade" id="editClassModal-{{ $class->id }}" tabindex="-1" aria-labelledby="editClassModalLabel-{{ $class->id }}" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('classes.update', $class->id) }}">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Class Name</label>
            <input type="text" name="name" value="{{ $class->name }}" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control">{{ $class->description }}</textarea>
          </div>

          {{-- Streams Section --}}
          <hr>
          <label>Streams</label>
          <ul class="list-group mb-3">
            @foreach ($class->streams as $stream)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                {{ $stream->name }}
                <button type="button" class="btn btn-sm btn-outline-danger delete-stream-btn"
                        data-stream-id="{{ $stream->id }}">
                  <i class="bx bx-trash"></i>
                </button>
              </li>
            @endforeach
          </ul>

          {{-- Add New Stream --}}
          <div class="input-group">
            <input type="text" name="new_stream" class="form-control" placeholder="Add new stream (name)">
            <button type="submit" name="add_stream" value="1" class="btn btn-primary">Add Stream</button>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Class</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Global hidden delete form --}}
<form id="delete-stream-form" method="POST" style="display: none;">
  @csrf
  @method('DELETE')
</form>

    @endforeach
  </div>
</div>

{{-- Modal: Add Class --}}
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('classes.store') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Class</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Class Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Description (Optional)</label>
            <textarea name="description" class="form-control"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add</button>
        </div>
      </div>
    </form>
  </div>
</div>


<script>
  document.addEventListener('DOMContentLoaded', function () {
    const deleteForm = document.getElementById('delete-stream-form');

    document.querySelectorAll('.delete-stream-btn').forEach(button => {
      button.addEventListener('click', function () {
        const streamId = this.getAttribute('data-stream-id');

        if (confirm('Delete this stream?')) {
          deleteForm.setAttribute('action', `/owner/classes/delete-stream/${streamId}`);
          deleteForm.submit();
        }
      });
    });
  });
</script>

@endsection
