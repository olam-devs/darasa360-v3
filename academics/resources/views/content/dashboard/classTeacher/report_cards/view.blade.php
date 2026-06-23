@extends('layouts.modernLayout', [
    'pageTitle' => 'View Report Card'
])

@section('title', 'Student Report Card')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h4 class="mb-0">{{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}</h4>
        <p class="text-muted mb-0">{{ $reportCard->configuration->term }} - {{ $reportCard->configuration->academic_year }}</p>
      </div>
      <a href="{{ route('classTeacher.reportCards.download', $reportCard->id) }}" class="btn btn-primary">
        <i class="bx bx-download me-1"></i>Download PDF
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5>Performance Summary</h5>
      <div class="row mt-3">
        <div class="col-md-3">
          <div class="d-flex align-items-center">
            <div class="badge bg-label-primary me-3 rounded p-2">
              <i class="bx bx-bar-chart-alt-2 bx-sm"></i>
            </div>
            <div>
              <small class="text-muted d-block">Average</small>
              <h5 class="mb-0">{{ number_format($reportCard->average_marks, 2) }}%</h5>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="d-flex align-items-center">
            <div class="badge bg-label-success me-3 rounded p-2">
              <i class="bx bx-award bx-sm"></i>
            </div>
            <div>
              <small class="text-muted d-block">Grade</small>
              <h5 class="mb-0">{{ $reportCard->overall_grade }}</h5>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="d-flex align-items-center">
            <div class="badge bg-label-info me-3 rounded p-2">
              <i class="bx bx-trophy bx-sm"></i>
            </div>
            <div>
              <small class="text-muted d-block">Position</small>
              <h5 class="mb-0">{{ $reportCard->class_position }} / {{ $reportCard->total_students_in_class }}</h5>
            </div>
          </div>
        </div>
      </div>

      <h5 class="mt-4">Subject Performance</h5>
      <div class="table-responsive mt-3">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Marks</th>
              <th>Grade</th>
            </tr>
          </thead>
          <tbody>
            @if(isset($reportCard->subject_performance) && is_array($reportCard->subject_performance))
              @foreach($reportCard->subject_performance as $subject)
              <tr>
                <td>{{ $subject['subject_name'] }}</td>
                <td>{{ number_format($subject['total_weighted_marks'], 2) }}%</td>
                <td><span class="badge bg-{{ $subject['grade'] == 'A' ? 'success' : ($subject['grade'] == 'F' ? 'danger' : 'warning') }}">{{ $subject['grade'] }}</span></td>
              </tr>
              @endforeach
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
