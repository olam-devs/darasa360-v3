@extends('layouts.modernLayout', [
    'pageTitle' => 'Select Mark Entry'
])

@section('title', 'Enter Marks')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold mb-4">Enter Marks</h4>

  {{-- Flash Messages --}}
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

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Trigger Modal --}}
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#selectMarkEntryModal">
    <i class="bx bx-edit-alt"></i> Select Exam, Class & Subject
  </button>

  {{-- Modal --}}
  <div class="modal fade" id="selectMarkEntryModal" tabindex="-1" aria-labelledby="selectMarkEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">

        <form action="{{ route('examMarks.select') }}" method="GET">
          <div class="modal-header">
            <h5 class="modal-title" id="selectMarkEntryModalLabel">Select Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label for="exam_id" class="form-label">Exam</label>
              <select class="form-select" id="exam_id" name="exam_id" required>
                <option disabled selected>Select exam</option>
                @foreach($exams as $exam)
                  <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>
                    {{ $exam->name }} ({{ $exam->term }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="class_id" class="form-label">Class</label>
              <select class="form-select" id="class_id" name="class_id" required>
                <option disabled selected>Select class</option>
                @foreach($classes as $class)
                  <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                    {{ $class->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="subject_id" class="form-label">Subject</label>
              <select class="form-select" id="subject_id" name="subject_id" required>
                <option disabled selected>Select subject</option>
                @foreach($subjects as $subject)
                  <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                    {{ $subject->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-success">
              <i class="bx bx-search"></i> Load Students
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

  {{-- Show student mark entry form if selection is made --}}
  @if(request('exam_id') && request('class_id') && request('subject_id'))
    @php
      $students = \App\Models\Student::on('tenant')->where('class_id', request('class_id'))->get();
    @endphp

    <form action="{{ route('examMarks.submit') }}" method="POST">
      @csrf
      <input type="hidden" name="exam_id" value="{{ request('exam_id') }}">
      <input type="hidden" name="class_id" value="{{ request('class_id') }}">
      <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">

      <div class="card">
        <div class="card-header">
          <strong>Enter Marks for {{ $students->count() }} Students</strong>
        </div>
        <div class="card-body">

          @foreach($students as $student)
  <div class="row align-items-center mb-3">
    <div class="col-md-6">
      <strong>{{ trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name) }}</strong>
    </div>
    <div class="col-md-4">
      <input type="hidden" name="marks[{{ $loop->index }}][student_id]" value="{{ $student->id }}">
      <input type="number" name="marks[{{ $loop->index }}][marks]" class="form-control mark-input" placeholder="Enter mark" min="0" max="100"
        value="{{ old('marks.'.$loop->index.'.marks', $marks->has($student->id) ? $marks[$student->id]->marks : '') }}" required data-index="{{ $loop->index }}">
    </div>
  </div>
@endforeach


        </div>
        <div class="card-footer text-end">
          <button type="submit" class="btn btn-success">
            <i class="bx bx-save"></i> Submit Marks
          </button>
        </div>
      </div>
    </form>
  @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Get all mark input fields
  const markInputs = document.querySelectorAll('.mark-input');

  markInputs.forEach((input, index) => {
    input.addEventListener('keydown', function(e) {
      // Check if Enter key was pressed
      if (e.key === 'Enter') {
        e.preventDefault(); // Prevent form submission

        // Move to next input
        if (index < markInputs.length - 1) {
          markInputs[index + 1].focus();
          markInputs[index + 1].select(); // Select the content for easy editing
        } else {
          // If last input, focus on submit button
          const submitBtn = document.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.focus();
          }
        }
      }
    });
  });

  // Auto-focus first input when marks form loads
  if (markInputs.length > 0) {
    markInputs[0].focus();
  }
});
</script>

@endsection
