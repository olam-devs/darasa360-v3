@extends('layouts.modernLayout', [
    'pageTitle' => 'Generate Results'
])

@section('title', 'Generate Results')

@section('content')


<div class="container-xxl flex-grow-1 container-p-y">
  @if(isset($successMessage))
<div class="alert alert-success alert-dismissible fade show" role="alert">
  {{ $successMessage }}
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif


@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  {{ session('error') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
  <ul class="mb-0">
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Generate Results</h4>
    </div>

    {{-- Selection Form --}}
    <div class="card-body">
      <form method="POST" action="{{ route('academic.generateResults.process') }}" id="resultsForm">
        @csrf
        <div class="row mb-3">
          <div class="col-md-4 mb-3">
            <label for="class_id" class="form-label">Class</label>
            <select name="class_id" id="class_id" class="form-select" required>
              <option value="">Select Class</option>
              @foreach($availableClassExamPairs as $pair)
                <option value="{{ $pair['class']->id }}"
                  {{ isset($class) && $class->id == $pair['class']->id ? 'selected' : '' }}>
                  {{ $pair['class']->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4 mb-3">
            <label for="exam_id" class="form-label">Exam</label>
            <select name="exam_id" id="exam_id" class="form-select" required>
              <option value="">Select Exam</option>
              @foreach($availableClassExamPairs as $pair)
                <option value="{{ $pair['exam']->id }}"
                  {{ isset($exam) && $exam->id == $pair['exam']->id ? 'selected' : '' }}>
                  {{ $pair['exam']->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4 mb-3">
            <label for="grading_type" class="form-label">Grading System</label>
            <select name="grading_type" id="grading_type" class="form-select" required>
              <option value="">Select Grading System</option>
              @foreach($gradingTypes as $type)
                @if($type !== 'individual')
                  <option value="{{ $type }}"
                    {{ isset($gradingType) && $gradingType == $type ? 'selected' : '' }}>
                    {{ ucfirst($type) }}
                  </option>
                @endif
              @endforeach
            </select>
          </div>
        </div>

        <input type="hidden" name="send_to_roles" id="send_to_roles" value="">

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-calculator me-1"></i> Generate Results
          </button>
        </div>
      </form>
    </div>
  </div>

  @isset($results)
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Results for {{ $class->name }} - {{ $exam->name }} ({{ ucfirst($gradingType) }})</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-light">
              <tr>
                <th>Student</th>
                @if(count($results) > 0)
                  @foreach($results[0]->subjects as $subject)
                    <th class="text-center">{{ $subject['subject_name'] }}</th>
                  @endforeach
                @endif
                <th class="text-center">Total</th>
                <th class="text-center">Average</th>
                <th class="text-center">Grade</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              @foreach($results as $student)
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-sm me-3">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                          {{ substr($student->student_name, 0, 1) }}
                        </span>
                      </div>
                      <div>{{ $student->student_name }}</div>
                    </div>
                  </td>

                  @foreach($student->subjects as $sub)
                    <td class="text-center">
                      {{ $sub['marks'] }}<span class="badge {{ $sub['grade'] === 'F' ? 'bg-danger' : 'bg-primary' }} ms-1">
                        ({{ $sub['grade'] }})
                      </span>
                    </td>
                  @endforeach

                  <td class="text-center fw-semibold">{{ $student->total_marks }}</td>
                  <td class="text-center">{{ $student->average_marks ?? '-' }}</td>
                  <td class="text-center">
                    <span class="badge {{ $student->final_grade === 'Fail' ? 'bg-label-danger' : 'bg-label-success' }}">
                      {{ $student->final_grade }}
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer text-end">
        <div class="d-flex flex-wrap justify-content-end gap-2">
          <button class="btn btn-outline-primary" onclick="window.print()">
            <i class="bx bx-printer me-1"></i>
            <span class="d-none d-sm-inline">Print</span>
          </button>

          <button type="button" class="btn btn-primary me-2" id="exportPdfBtn" onclick="document.getElementById('downloadForm').submit()">
            <i class="bx bx-download me-1"></i> Export as PDF
        </button>

        <form id="downloadForm" method="POST" action="{{ route('academic.download.exam_results') }}" style="display: none;">
            @csrf
            <input type="hidden" name="class_id" value="{{ $class->id ?? '' }}">
            <input type="hidden" name="exam_id" value="{{ $exam->id ?? '' }}">
            <input type="hidden" name="grading_type" value="{{ $gradingType ?? '' }}">
        </form>

          <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#sendToModal">
            <i class="bx bx-send me-1"></i>
            <span class="d-none d-sm-inline">Send To</span>
          </button>
        </div>
      </div>
    </div>
  @endisset
</div>

<!-- Send To Modal -->
<!-- Send To Modal -->
<div class="modal fade" id="sendToModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Send Results To</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Select Recipients</label>
          @foreach($schoolRoles as $role)
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="roles[]"
                     value="{{ $role }}" id="role-{{ $role }}"
                     @if(in_array($role, $selectedRoles ?? [])) checked @endif>
              <label class="form-check-label" for="role-{{ $role }}">
                {{ ucwords(str_replace('_', ' ', $role)) }}
              </label>
            </div>
          @endforeach
        </div>

        <div class="alert alert-info mt-3">
          <i class="bx bx-info-circle me-2"></i> Selecting recipients will mark these results as verified.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmSendBtn">
          <i class="bx bx-send me-1"></i> Send Results
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  @media (max-width: 768px) {
    table {
      display: block;
      overflow-x: auto;
      white-space: nowrap;
    }
  }

  .badge {
    font-size: 0.75em;
    padding: 0.25em 0.4em;
  }

  @media (max-width: 768px) {
    table {
      display: block;
      overflow-x: auto;
      white-space: nowrap;
    }
    /* Responsive button adjustments */
    .card-footer .btn {
      padding: 0.375rem 0.75rem;
      font-size: 0.875rem;
    }
    .card-footer .btn i {
      margin-right: 0 !important;
    }
  }

  .badge {
    font-size: 0.75em;
    padding: 0.25em 0.4em;
  }

  /* Ensure consistent button spacing */
  .gap-2 {
    gap: 0.5rem;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sendToModal = document.getElementById('sendToModal');
    const confirmSendBtn = document.getElementById('confirmSendBtn');
    const sendToRolesInput = document.getElementById('send_to_roles');
    const resultsForm = document.getElementById('resultsForm');

    confirmSendBtn.addEventListener('click', function() {
        const selectedRoles = [];
        document.querySelectorAll('#sendToModal input[name="roles[]"]:checked').forEach(checkbox => {
            selectedRoles.push(checkbox.value);
        });

        if (selectedRoles.length === 0) {
            alert('Please select at least one recipient to send the results to.');
            return;
        }

        // Add a hidden field to the form
        if (!document.getElementById('send_to_roles_field')) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'send_to_roles';
            hiddenInput.id = 'send_to_roles_field';
            hiddenInput.value = JSON.stringify(selectedRoles);
            resultsForm.appendChild(hiddenInput);
        } else {
            document.getElementById('send_to_roles_field').value = JSON.stringify(selectedRoles);
        }

        // Submit the form
        resultsForm.submit();

        // Show loading state
        confirmSendBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
        confirmSendBtn.disabled = true;
    });

    // Initialize modal if it exists
    if (sendToModal) {
        const modal = new bootstrap.Modal(sendToModal);

        // When modal is shown, check if we have previous selections
        sendToModal.addEventListener('show.bs.modal', function() {
            const currentRoles = JSON.parse(sendToRolesInput.value || '[]');
            currentRoles.forEach(role => {
                const checkbox = document.querySelector(`#sendToModal input[value="${role}"]`);
                if (checkbox) checkbox.checked = true;
            });
        });
    }
});
</script>
@endsection