@extends('layouts.modernLayout', [
    'pageTitle' => 'Student Report Cards'
])

@section('title', $configuration->name)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
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

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h4 class="mb-0">{{ $configuration->name }}</h4>
        <p class="text-muted mb-0">{{ $configuration->class->name }} - {{ $configuration->term }} {{ $configuration->academic_year }}</p>
      </div>
      <div>
        @if($configuration->status === 'published')
        <span class="badge bg-success"><i class="bx bx-check"></i> Published</span>
        @else
        <span class="badge bg-secondary">Draft</span>
        @endif
      </div>
    </div>

    <div class="card-footer">
      <div class="d-flex flex-wrap gap-2 justify-content-end">
        <!-- Send SMS Button - Only shown if published -->
        @if($configuration->status === 'published' && count($students) > 0)
        <button type="button" class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#sendSmsModal">
          <i class="bx bx-message-rounded-dots me-1"></i>Send via SMS to Parents
        </button>
        @endif
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0"><i class="bx bx-group me-2"></i>Student Report Cards ({{ count($students) }})</h5>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-light">
            <tr>
              <th>Student</th>
              <th>Registration No</th>
              <th>Average</th>
              <th>Grade</th>
              <th>Position</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($students as $student)
            @php
              $reportCard = $reportCards[$student->id] ?? null;
            @endphp
            <tr>
              <td>{{ $student->first_name }} {{ $student->last_name }}</td>
              <td>{{ $student->registration_no }}</td>
              @if($reportCard)
              <td>{{ number_format($reportCard->average_marks, 2) }}%</td>
              <td><span class="badge bg-{{ $reportCard->overall_grade == 'A' ? 'success' : ($reportCard->overall_grade == 'F' ? 'danger' : 'warning') }}">{{ $reportCard->overall_grade }}</span></td>
              <td>{{ $reportCard->class_position }} / {{ $reportCard->total_students_in_class }}</td>
              <td>
                <a href="{{ route('classTeacher.reportCards.download', $reportCard->id) }}"
                   class="btn btn-sm btn-primary">
                  <i class="bx bx-download me-1"></i>Download PDF
                </a>
              </td>
              @else
              <td colspan="4" class="text-muted">Not yet generated</td>
              @endif
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Send SMS Modal -->
<div class="modal fade" id="sendSmsModal" tabindex="-1" aria-labelledby="sendSmsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('classTeacher.reportCards.send-sms', $configuration->id) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="sendSmsModalLabel">
            <i class="bx bx-message-rounded-dots me-2"></i>Send Report Cards via SMS
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="class_id" value="{{ $configuration->class_id }}">

          <div class="alert alert-info">
            <i class="bx bx-info-circle me-2"></i>
            <strong>SMS Report Format:</strong>
            <ul class="mb-0 mt-2">
              <li>Student name & registration number</li>
              <li>Subject marks (up to 6 subjects)</li>
              <li>Average score & class position</li>
              <li>Teacher's comment</li>
            </ul>
          </div>

          <div class="alert alert-warning">
            <i class="bx bx-bell me-2"></i>
            This will send SMS to <strong>{{ count($students) }}</strong> students in <strong>{{ $configuration->class->name ?? 'this class' }}</strong>.
            <br><small>SMS will be sent to parent phone numbers if available, otherwise to student phone numbers.</small>
          </div>

          <p class="text-muted mb-0">
            <i class="bx bx-check me-1"></i> Only students with valid phone numbers will receive SMS.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" onclick="return confirm('Send report cards via SMS to all students in this class?')">
            <i class="bx bx-send me-1"></i>Send SMS Now
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
