@extends('layouts.modernLayout', [
    'pageTitle' => 'Edit Assignment'
])
@section('title', 'Edit Assignment')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0"><i class="bx bx-edit me-2 text-primary"></i>Edit Assignment</h4>
      <a href="{{ route('assignments.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to List
      </a>
    </div>

    <div class="card-body">
      <form method="POST" action="{{ route('assignments.update', $assignment) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $assignment->title) }}" required>
          @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="class_id" class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
            <select name="class_id" id="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
              <option value="">Select Class</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}" {{ old('class_id', $assignment->class_id) == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
              @endforeach
            </select>
            @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label for="subject_id" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
            <select name="subject_id" id="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
              <option value="{{ $assignment->subject_id }}" selected>{{ $assignment->subject->name ?? 'Current subject' }}</option>
            </select>
            @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="form-text mb-3">To replace the uploaded file, delete this assignment and add a new one — editing only updates the title, class, and subject.</div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('class_id')?.addEventListener('change', function () {
  const subjectSelect = document.getElementById('subject_id');
  const classId = this.value;
  if (!classId) return;
  fetch(`/assignments/class-subjects/${classId}`)
    .then(res => res.json())
    .then(data => {
      subjectSelect.innerHTML = '';
      if (data.length) {
        data.forEach(sub => {
          const opt = document.createElement('option');
          opt.value = sub.id;
          opt.text = sub.name;
          subjectSelect.appendChild(opt);
        });
      } else {
        subjectSelect.innerHTML = '<option disabled selected>No subjects available</option>';
      }
    });
});
</script>
@endsection
