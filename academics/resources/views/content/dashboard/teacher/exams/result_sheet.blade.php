@extends('layouts.modernLayout', [
    'pageTitle' => 'Result Sheet'
])

@section('title', 'Result Sheet')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Result Sheet</h4>
    </div>

    {{-- Alerts --}}
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

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('examMarks.results') }}" class="row g-3 mb-4">
      <div class="col-md-3">
        <select name="exam_id" class="form-select" required>
          <option value="">-- Select Exam --</option>
          @foreach($exams as $exam)
            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>{{ $exam->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="class_id" class="form-select" required>
          <option value="">-- Select Class --</option>
          @foreach($classes as $class)
            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="subject_id" class="form-select" required>
          <option value="">-- Select Subject --</option>
          <option value="all" {{ request('subject_id') === 'all' ? 'selected' : '' }}>All Subjects</option>
          @foreach($subjects as $subject)
            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100">
          <i class="bx bx-show-alt"></i> View Result Sheet
        </button>
      </div>
    </form>

    {{-- Download PDF Button --}}
    @if($results->count() > 0)
      <div class="mb-3 d-flex justify-content-end">
        <a href="{{ route('examMarks.download', [
            'exam_id' => request('exam_id'),
            'class_id' => request('class_id'),
            'subject_id' => request('subject_id')
        ]) }}"
           class="btn btn-danger"
           target="_blank"
           id="downloadPdfBtn">
          <i class="bx bx-download"></i> Download PDF
        </a>
      </div>
    @endif

    {{-- Results --}}
    @if(request()->filled(['exam_id', 'class_id', 'subject_id']))
      @if($results->count() > 0)
        <div class="card">
          <div class="table-responsive text-nowrap">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Student Name</th>
                  <th>Registration No</th>
                  @if(request('subject_id') === 'all')
                    @php
                      $subjectNames = $results->pluck('subject.name')->unique();
                    @endphp
                    @foreach($subjectNames as $subject)
                      <th>{{ $subject }}</th>
                    @endforeach
                  @else
                    <th>Marks</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @php
                  $students = $results->groupBy('student.id');
                @endphp
                @foreach($students as $studentMarks)
                  @php
                    $student = $studentMarks->first()->student;
                  @endphp
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name) }}</td>
                    <td>{{ $student->registration_no }}</td>
                    @if(request('subject_id') === 'all')
                      @foreach($subjectNames as $subject)
                        @php
                          $mark = $studentMarks->firstWhere('subject.name', $subject);
                        @endphp
                        <td>{{ $mark ? $mark->marks : '-' }}</td>
                      @endforeach
                    @else
                      <td>{{ $studentMarks->first()->marks }}</td>
                    @endif
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @else
        <div class="alert alert-warning mt-3">
          No marks found for the selected exam, class, and subject.
        </div>
      @endif
    @endif
  </div>
</div>

{{-- Confirm Download --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const downloadBtn = document.getElementById('downloadPdfBtn');
  if (downloadBtn) {
    downloadBtn.addEventListener('click', function(event) {
      if (!confirm('Do you want to download this file?')) {
        event.preventDefault();
      }
    });
  }
});
</script>
@endsection
