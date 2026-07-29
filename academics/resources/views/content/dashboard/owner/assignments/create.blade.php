@extends('layouts.modernLayout', [
    'pageTitle' => 'Add Assignment'
])
@section('title', 'Add Assignment')

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
      <h4 class="mb-0"><i class="bx bx-plus-circle me-2 text-primary"></i>Add Assignment</h4>
      <a href="{{ route('assignments.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to List
      </a>
    </div>

    <div class="card-body">
      <form method="POST" action="{{ route('assignments.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
          <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
          @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="class_id" class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
            <select name="class_id" id="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
              <option value="">Select Class</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
              @endforeach
            </select>
            @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label for="subject_id" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
            <select name="subject_id" id="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
              <option value="">Select Subject</option>
              @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
              @endforeach
            </select>
            @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="mb-3">
          <label for="description" class="form-label fw-semibold">Description</label>
          <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
        </div>

        <div class="mb-4">
          <label for="assignment_file" class="form-label fw-semibold">File <span class="text-danger">*</span></label>
          <input type="file" class="form-control @error('assignment_file') is-invalid @enderror" id="assignment_file" name="assignment_file" accept=".pdf,.doc,.docx" required>
          <div class="form-text">PDF or Word document, max 10MB.</div>
          @error('assignment_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Add Assignment</button>
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
      subjectSelect.innerHTML = '<option value="">Select Subject</option>';
      if (data.length) {
        data.forEach(sub => {
          const opt = document.createElement('option');
          opt.value = sub.id;
          opt.text = sub.name;
          subjectSelect.appendChild(opt);
        });
      } else {
        const opt = document.createElement('option');
        opt.text = 'No subjects available';
        opt.disabled = true;
        subjectSelect.appendChild(opt);
      }
    });
});
</script>
@endsection
