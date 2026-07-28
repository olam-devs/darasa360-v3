@extends('layouts.modernLayout', [
    'pageTitle' => 'Report Card Details'
])

@section('title', 'Report Card Details')

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

  <!-- Configuration Details Card -->
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0"><i class="bx bx-file me-2"></i>{{ $configuration->name }}</h4>
      <div class="d-flex gap-2">
        <a href="{{ route('report-cards.index') }}" class="btn btn-sm btn-secondary">
          <i class="bx bx-arrow-back"></i> Back
        </a>
      </div>
    </div>

    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-3">
          <strong>Class:</strong><br>
          <span class="badge bg-primary">{{ $configuration->class->name ?? 'N/A' }}</span>
        </div>
        <div class="col-md-3">
          <strong>Term:</strong><br>
          {{ $configuration->term }}
        </div>
        <div class="col-md-3">
          <strong>Academic Year:</strong><br>
          {{ $configuration->academic_year }}
        </div>
        <div class="col-md-3">
          <strong>Status:</strong><br>
          @if($configuration->status === 'active')
          <span class="badge bg-success">Active</span>
          @elseif($configuration->status === 'published')
          <span class="badge bg-info">Published</span>
          @else
          <span class="badge bg-secondary">Draft</span>
          @endif
        </div>
      </div>

      @if($configuration->description)
      <div class="row">
        <div class="col-12">
          <strong>Description:</strong><br>
          <p class="text-muted">{{ $configuration->description }}</p>
        </div>
      </div>
      @endif

      <hr>

      <h6 class="mb-3"><i class="bx bx-notepad me-2"></i>Exam Configuration</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr>
              <th>Exam Name</th>
              <th class="text-center">Weight</th>
              <th class="text-center">Order</th>
            </tr>
          </thead>
          <tbody>
            @foreach($configuration->examConfigs as $examConfig)
            <tr>
              <td>{{ $examConfig->exam->name ?? 'N/A' }}</td>
              <td class="text-center">
                <span class="badge bg-primary">{{ $examConfig->percentage_weight }}%</span>
              </td>
              <td class="text-center">{{ $examConfig->order }}</td>
            </tr>
            @endforeach
          </tbody>
          <tfoot class="table-light">
            <tr>
              <td class="text-end"><strong>Total:</strong></td>
              <td class="text-center">
                <span class="badge bg-success">
                  {{ $configuration->examConfigs->sum('percentage_weight') }}%
                </span>
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="card-footer">
      <div class="d-flex flex-wrap gap-2 justify-content-end">
        <!-- Step 1: Generate Reports -->
        @if($reportCards->count() === 0)
        <form action="{{ route('report-cards.generate', $configuration->id) }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-primary" onclick="return confirm('Generate report cards for all students in this class?')">
            <i class="bx bx-cog me-1"></i>Step 1: Generate Report Cards
          </button>
        </form>
        @endif

        <!-- Step 2: Before Publishing -->
        @if($reportCards->count() > 0 && $configuration->status !== 'published')
        <form action="{{ route('report-cards.publish', $configuration->id) }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Publish all report cards? This will finalize them and allow SMS sending.')">
            <i class="bx bx-check-circle me-1"></i>Step 2: Publish Report Cards
          </button>
        </form>
        @endif

        <!-- Step 3: After Publishing - Send & Download -->
        @if($reportCards->count() > 0 && $configuration->status === 'published')
        <button type="button" class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#sendSmsModal">
          <i class="bx bx-message-rounded-dots me-1"></i>Send via SMS to Parents
        </button>

        <a href="{{ route('report-cards.bulk-pdf', $configuration->id) }}" class="btn btn-info" target="_blank">
          <i class="bx bx-download me-1"></i>Download All PDFs
        </a>
        @endif
      </div>
    </div>
  </div>

  <!-- Student Report Cards -->
  @if($reportCards->count() > 0)
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0"><i class="bx bx-user me-2"></i>Student Report Cards ({{ $reportCards->count() }})</h5>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-light">
            <tr>
              <th>Student Name</th>
              <th class="text-center">Reg. No</th>
              <th class="text-center">Total Marks</th>
              <th class="text-center">Average</th>
              <th class="text-center">Grade</th>
              <th class="text-center">Position</th>
              <th class="text-center">Status</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($reportCards as $reportCard)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-3">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      {{ substr($reportCard->student->first_name ?? 'N', 0, 1) }}
                    </span>
                  </div>
                  <div>
                    {{ $reportCard->student->first_name ?? '' }} {{ $reportCard->student->last_name ?? '' }}
                  </div>
                </div>
              </td>
              <td class="text-center">
                <small class="text-muted">{{ $reportCard->student->registration_no ?? 'N/A' }}</small>
              </td>
              <td class="text-center">
                <strong>{{ number_format($reportCard->total_marks, 1) }}</strong>
              </td>
              <td class="text-center">
                {{ number_format($reportCard->average_marks, 1) }}%
              </td>
              <td class="text-center">
                <span class="badge
                  @if($reportCard->overall_grade === 'A') bg-success
                  @elseif($reportCard->overall_grade === 'B') bg-info
                  @elseif($reportCard->overall_grade === 'C') bg-primary
                  @elseif($reportCard->overall_grade === 'D') bg-warning
                  @else bg-danger
                  @endif
                ">
                  {{ $reportCard->overall_grade }}
                </span>
              </td>
              <td class="text-center">
                @if($reportCard->class_position)
                <span class="badge bg-label-dark">{{ $reportCard->class_position }}</span>
                @else
                <span class="text-muted">-</span>
                @endif
              </td>
              <td class="text-center">
                @if($reportCard->status === 'published')
                <span class="badge bg-success"><i class="bx bx-check"></i> Published</span>
                @else
                <span class="badge bg-secondary">Draft</span>
                @endif
              </td>
              <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                  <a href="{{ route('report-cards.comments', ['configId' => $configuration->id, 'studentId' => $reportCard->student_id]) }}"
                     class="btn btn-sm btn-primary" title="Manage Comments">
                    <i class="bx bx-comment-edit"></i>
                  </a>
                  <a href="{{ route('report-cards.pdf', $reportCard->id) }}"
                     class="btn btn-sm btn-info" target="_blank" title="Download PDF">
                    <i class="bx bx-download"></i>
                  </a>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @else
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="bx bx-user-x" style="font-size: 4rem; color: #ccc;"></i>
      <p class="text-muted mt-3">No report cards generated yet.</p>
      <form action="{{ route('report-cards.generate', $configuration->id) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-primary" onclick="return confirm('Generate report cards for all students in this class?')">
          <i class="bx bx-cog me-1"></i>Generate Report Cards Now
        </button>
      </form>
    </div>
  </div>
  @endif
</div>

<!-- Send SMS Modal -->
<div class="modal fade" id="sendSmsModal" tabindex="-1" aria-labelledby="sendSmsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('report-cards.send-sms', $configuration->id) }}" method="POST">
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
            This will send SMS to <strong>{{ $reportCards->count() }}</strong> students in <strong>{{ $configuration->class->name ?? 'this class' }}</strong>.
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

@push('page-style')
<style>
  @media (max-width: 768px) {
    .table {
      font-size: 0.875rem;
    }
    .btn-sm {
      padding: 0.25rem 0.5rem;
      font-size: 0.75rem;
    }
    .gap-1 {
      gap: 0.25rem !important;
    }
  }

  /* Dark Theme Support */
  [data-bs-theme="dark"] .card {
    background-color: #2b2c40 !important;
    border-color: #3b3b55 !important;
  }

  [data-bs-theme="dark"] .card-header {
    background-color: #373851 !important;
    border-bottom-color: #3b3b55 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .card-body {
    background-color: #2b2c40 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .card-footer {
    background-color: #373851 !important;
    border-top-color: #3b3b55 !important;
  }

  [data-bs-theme="dark"] .table {
    color: #d4d4e8 !important;
    background-color: transparent !important;
  }

  [data-bs-theme="dark"] .table-light {
    background-color: #373851 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(255, 255, 255, 0.02) !important;
  }

  [data-bs-theme="dark"] .table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05) !important;
  }

  [data-bs-theme="dark"] .table td,
  [data-bs-theme="dark"] .table th {
    border-color: #3b3b55 !important;
  }

  [data-bs-theme="dark"] .text-muted {
    color: #a1a3b7 !important;
  }

  [data-bs-theme="dark"] strong {
    color: #e7e7f0 !important;
  }

  [data-bs-theme="dark"] .avatar-initial {
    background-color: rgba(115, 103, 240, 0.2) !important;
    color: #a8aaff !important;
  }

  [data-bs-theme="dark"] .badge {
    color: white !important;
  }

  [data-bs-theme="dark"] .btn-secondary {
    background-color: #3b3b55 !important;
    border-color: #3b3b55 !important;
  }
</style>
@endpush
