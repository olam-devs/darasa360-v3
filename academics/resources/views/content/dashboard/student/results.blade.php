@extends('layouts.modernLayout', [
    'pageTitle' => 'My Exam Results'
])

@section('title', 'My Exam Results')

@section('content')
<div class="container">
  <h4 class="fw-bold py-3 mb-4">My Exam Results</h4>

  <form method="GET" action="{{ route('student.results') }}" class="mb-4">
    <div class="row g-3 align-items-center">
      <div class="col-auto">
        <label for="exam_id" class="col-form-label">Select Exam:</label>
      </div>
      <div class="col-auto">
        <select name="exam_id" id="exam_id" class="form-select" onchange="this.form.submit()">
          <option value="">-- Choose Exam --</option>
          @foreach($exams as $exam)
            <option value="{{ $exam->id }}" {{ $selectedExamId == $exam->id ? 'selected' : '' }}>
              {{ $exam->name }}
            </option>
          @endforeach
        </select>
      </div>

      @if ($selectedExamId && count($results))
      <div class="col-auto">
        <label for="grading_type" class="col-form-label">Grading Type:</label>
      </div>
      <div class="col-auto">
        <select name="grading_type" id="grading_type" class="form-select" onchange="this.form.submit()">
          <option value="standard" {{ $gradeType == 'standard' ? 'selected' : '' }}>Standard</option>
          <option value="division" {{ $gradeType == 'division' ? 'selected' : '' }}>Division</option>
        </select>
      </div>
      @endif
    </div>
  </form>

  @if ($selectedExamId && count($results))
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Results for Selected Exam</h5>

        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Marks Obtained</th>
              <th>Grade</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($results as $result)
              <tr>
                <td>{{ $result->subject->name ?? 'N/A' }}</td>
                <td>{{ $result->marks }}</td>
                <td>{{ $result->grade }}</td>
              </tr>
            @endforeach

            <tr>
              <td><strong>Total</strong></td>
              <td><strong>{{ $totalMarks }}</strong></td>
              <td></td>
            </tr>
          </tbody>
        </table>

        <!-- Download PDF Button -->
        <div class="mt-3 d-flex justify-content-end">
          <a href="{{ route('student.results.download', ['exam_id' => $selectedExamId, 'grading_type' => $gradeType]) }}"
             class="btn btn-danger" target="_blank">
            <i class="bx bx-download"></i> Download PDF
          </a>
        </div>

        <div class="mt-3">
          <h6>Overall Grade</h6>
          @if ($overallGrade)
            <p><strong>{{ $overallGrade }}</strong> ({{ ucfirst($gradeType) }} based)</p>
          @else
            <p><em>No grade found for total marks using {{ $gradeType }} grading.</em></p>
          @endif
        </div>

      </div>
    </div>
  @elseif($selectedExamId)
    <div class="alert alert-warning">No results found for the selected exam.</div>
  @endif
</div>
@endsection
