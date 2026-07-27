@extends('layouts.modernLayout', [
    'pageTitle' => 'Create Report Card'
])

@section('title', 'Create Report Card Configuration')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Error Messages --}}
  @if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <h5 class="alert-heading mb-2"><i class='bx bx-error-circle me-2'></i>Please fix the following errors:</h5>
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  {{-- Main Card --}}
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="bx bx-plus-circle me-2 text-primary"></i>
        Create Report Card Configuration
      </h4>
      <a href="{{ route('report-cards.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to List
      </a>
    </div>

    <div class="card-body">
      <form method="POST" action="{{ route('report-cards.store') }}" id="reportCardForm">
        @csrf

        {{-- Step 1: Basic Information --}}
        <div class="form-section mb-5">
          <div class="section-header mb-4">
            <h5 class="fw-bold text-primary mb-2">
              <span class="badge bg-primary me-2">1</span>
              Basic Information
            </h5>
            <p class="text-muted mb-0">Enter the basic details for this report card configuration</p>
          </div>

          <div class="row g-3">
            <div class="col-md-8">
              <label for="name" class="form-label fw-semibold">
                Configuration Name <span class="text-danger">*</span>
                <i class="bx bx-info-circle text-muted" data-bs-toggle="tooltip"
                   title="Give this configuration a descriptive name, e.g., 'Term 1 Report Card 2025/2026'"></i>
              </label>
              <input type="text"
                     class="form-control form-control-lg @error('name') is-invalid @enderror"
                     id="name"
                     name="name"
                     placeholder="e.g., Term 1 Report Card 2025/2026"
                     value="{{ old('name') }}"
                     required
                     autofocus>
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">
                <i class="bx bx-bulb"></i> Tip: Use a clear, descriptive name that includes the term and year
              </div>
            </div>

            <div class="col-md-4">
              <label for="status" class="form-label fw-semibold">
                Status <span class="text-danger">*</span>
              </label>
              <select name="status" id="status" class="form-select form-select-lg @error('status') is-invalid @enderror" required>
                <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>
                  📝 Draft (Save for later)
                </option>
                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>
                  ✅ Active (Ready to use)
                </option>
              </select>
              @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4">
              <label for="class_id" class="form-label fw-semibold">
                Class <span class="text-danger">*</span>
              </label>
              <select name="class_id"
                      id="class_id"
                      class="form-select form-select-lg @error('class_id') is-invalid @enderror"
                      required>
                <option value="">-- Select Class --</option>
                @foreach($classes as $class)
                <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                  {{ $class->name }}
                </option>
                @endforeach
              </select>
              @error('class_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4">
              <label for="term" class="form-label fw-semibold">
                Term <span class="text-danger">*</span>
              </label>
              <input type="text"
                     class="form-control form-control-lg @error('term') is-invalid @enderror"
                     id="term"
                     name="term"
                     placeholder="e.g., Term 1, Term 2, Term 3"
                     value="{{ old('term') }}"
                     required>
              @error('term')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4">
              <label for="academic_year" class="form-label fw-semibold">
                Academic Year <span class="text-danger">*</span>
              </label>
              <input type="text"
                     class="form-control form-control-lg @error('academic_year') is-invalid @enderror"
                     id="academic_year"
                     name="academic_year"
                     placeholder="e.g., 2025/2026"
                     value="{{ old('academic_year', date('Y') . '/' . (date('Y') + 1)) }}"
                     required>
              @error('academic_year')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label for="description" class="form-label fw-semibold">
                Description <span class="text-muted">(Optional)</span>
              </label>
              <textarea class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="2"
                        placeholder="Brief description of this report card configuration (optional)">{{ old('description') }}</textarea>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <hr class="my-5">

        {{-- Step 2: Exam Configuration --}}
        <div class="form-section mb-5">
          <div class="section-header mb-4">
            <h5 class="fw-bold text-primary mb-2">
              <span class="badge bg-primary me-2">2</span>
              Exam Configuration
            </h5>
            <p class="text-muted mb-0">Add exams and assign percentage weights. <strong>Total must equal 100%</strong></p>
          </div>

          {{-- Percentage Progress Indicator --}}
          <div class="card bg-light border-2 mb-4" id="percentageCard">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <h6 class="mb-1 fw-bold">Total Percentage</h6>
                  <small class="text-muted">Must equal 100% to proceed</small>
                </div>
                <div class="text-end">
                  <h3 class="mb-0 fw-bold" id="totalPercentageDisplay">
                    <span id="currentPercentage">0</span>%
                    <span class="text-muted">/</span>
                    <span class="text-muted">100%</span>
                  </h3>
                  <small id="percentageStatus" class="text-muted">Add exams below</small>
                </div>
              </div>

              {{-- Visual Progress Bar --}}
              <div class="progress" style="height: 30px; border-radius: 10px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     id="percentageProgressBar"
                     role="progressbar"
                     style="width: 0%; transition: all 0.3s ease;"
                     aria-valuenow="0"
                     aria-valuemin="0"
                     aria-valuemax="100">
                  <span class="fw-bold" id="progressBarText">0%</span>
                </div>
              </div>

              {{-- Status Messages --}}
              <div class="mt-3">
                <div id="percentageMessage" class="alert alert-info mb-0 d-none" role="alert">
                  <i class="bx bx-info-circle me-2"></i>
                  <span id="percentageMessageText"></span>
                </div>
              </div>
            </div>
          </div>

          {{-- Exam Configuration Items --}}
          <div id="examConfigs">
            {{-- Exam items will be added here dynamically --}}
          </div>

          <button type="button" class="btn btn-lg btn-primary shadow-sm" id="addExamBtn">
            <i class="bx bx-plus-circle me-2"></i>Add Exam
          </button>
        </div>

        <hr class="my-5">

        {{-- Form Actions --}}
        <div class="d-flex justify-content-between align-items-center">
          <a href="{{ route('report-cards.index') }}" class="btn btn-lg btn-outline-secondary">
            <i class="bx bx-x me-2"></i>Cancel
          </a>

          <div>
            <button type="button" class="btn btn-lg btn-outline-primary me-2" id="saveDraftBtn">
              <i class="bx bx-save me-2"></i>Save as Draft
            </button>
            <button type="submit" class="btn btn-lg btn-primary shadow" id="submitBtn" disabled>
              <i class="bx bx-check-circle me-2"></i>Create Configuration
            </button>
          </div>
        </div>
      </form>

      {{-- Exam Template (Hidden) - kept outside the form: it's only ever used as an
           innerHTML string source for cloning real rows, and a required/named field
           in here (even display:none) still participates in this browser's native
           form constraint validation, silently blocking every submission. --}}
      <div id="examTemplate" class="d-none">
        <div class="exam-config-item card mb-3 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0 fw-bold">
                <i class="bx bx-notepad text-primary me-2"></i>
                Exam <span class="exam-number"></span>
              </h6>
              <button type="button" class="btn btn-sm btn-outline-danger remove-exam-btn">
                <i class="bx bx-trash"></i> Remove
              </button>
            </div>

            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label fw-semibold">Select Exam <span class="text-danger">*</span></label>
                <select name="exams[INDEX][exam_id]" class="form-select exam-select" required>
                  <option value="">-- Select Exam --</option>
                  @foreach($exams as $exam)
                  <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold">
                  Weight (%) <span class="text-danger">*</span>
                </label>
                <div class="input-group input-group-lg">
                  <input type="number"
                         name="exams[INDEX][percentage_weight]"
                         class="form-control percentage-input"
                         min="0"
                         max="100"
                         step="0.01"
                         placeholder="0.00"
                         required>
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let examCount = 0;

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Add Exam Button
    document.getElementById('addExamBtn').addEventListener('click', function() {
        addExamConfig();
    });

    // Save Draft Button
    document.getElementById('saveDraftBtn').addEventListener('click', function() {
        document.getElementById('status').value = 'draft';
        document.getElementById('reportCardForm').submit();
    });

    // Add exam configuration function
    function addExamConfig() {
        examCount++;
        const template = document.getElementById('examTemplate').innerHTML;
        const newExam = template.replace(/INDEX/g, examCount).replace(/exam-number"><\/span>/g, `exam-number">${examCount}</span>`);

        const container = document.getElementById('examConfigs');
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = newExam;
        const examElement = tempDiv.firstElementChild;

        container.appendChild(examElement);

        // Add event listeners
        const removeBtn = examElement.querySelector('.remove-exam-btn');
        const percentageInput = examElement.querySelector('.percentage-input');

        removeBtn.addEventListener('click', function() {
            examElement.remove();
            updateExamNumbers();
            calculateTotalPercentage();
        });

        percentageInput.addEventListener('input', function() {
            calculateTotalPercentage();
        });

        calculateTotalPercentage();
    }

    // Update exam numbers after removal
    function updateExamNumbers() {
        const examItems = document.querySelectorAll('.exam-config-item');
        examItems.forEach((item, index) => {
            item.querySelector('.exam-number').textContent = index + 1;
        });
        examCount = examItems.length;
    }

    // Calculate total percentage
    function calculateTotalPercentage() {
        const percentageInputs = document.querySelectorAll('.percentage-input');
        let total = 0;

        percentageInputs.forEach(input => {
            const value = parseFloat(input.value) || 0;
            total += value;
        });

        updatePercentageDisplay(total);
    }

    // Update percentage display and progress bar
    function updatePercentageDisplay(total) {
        const currentPercentageEl = document.getElementById('currentPercentage');
        const progressBar = document.getElementById('percentageProgressBar');
        const progressBarText = document.getElementById('progressBarText');
        const percentageStatus = document.getElementById('percentageStatus');
        const percentageMessage = document.getElementById('percentageMessage');
        const percentageMessageText = document.getElementById('percentageMessageText');
        const submitBtn = document.getElementById('submitBtn');
        const percentageCard = document.getElementById('percentageCard');

        // Update display
        currentPercentageEl.textContent = total.toFixed(2);
        progressBar.style.width = Math.min(total, 100) + '%';
        progressBar.setAttribute('aria-valuenow', total);
        progressBarText.textContent = total.toFixed(1) + '%';

        // Remove existing classes
        progressBar.classList.remove('bg-success', 'bg-warning', 'bg-danger', 'bg-primary');
        percentageCard.classList.remove('border-success', 'border-warning', 'border-danger');
        percentageMessage.classList.remove('d-none', 'alert-success', 'alert-warning', 'alert-danger', 'alert-info');

        // Status logic
        if (total === 0) {
            percentageStatus.innerHTML = '<i class="bx bx-info-circle me-1"></i>Add exams below';
            percentageStatus.className = 'text-muted';
            progressBar.classList.add('bg-primary');
            submitBtn.disabled = true;
            percentageMessage.classList.add('d-none');
        } else if (total < 100) {
            const remaining = 100 - total;
            percentageStatus.innerHTML = `<i class="bx bx-error-circle me-1"></i>Add ${remaining.toFixed(1)}% more`;
            percentageStatus.className = 'text-warning fw-bold';
            progressBar.classList.add('bg-warning');
            percentageCard.classList.add('border-warning');
            percentageMessage.classList.remove('d-none');
            percentageMessage.classList.add('alert-warning');
            percentageMessageText.textContent = `You need to add ${remaining.toFixed(1)}% more to reach 100%.`;
            submitBtn.disabled = true;
        } else if (total === 100) {
            percentageStatus.innerHTML = '<i class="bx bx-check-circle me-1"></i>Perfect! Total is 100%';
            percentageStatus.className = 'text-success fw-bold';
            progressBar.classList.add('bg-success');
            percentageCard.classList.add('border-success');
            percentageMessage.classList.remove('d-none');
            percentageMessage.classList.add('alert-success');
            percentageMessageText.innerHTML = '<i class="bx bx-check-double me-2"></i><strong>Excellent!</strong> Your exam percentages total exactly 100%. You can now create this configuration.';
            submitBtn.disabled = false;
        } else {
            const excess = total - 100;
            percentageStatus.innerHTML = `<i class="bx bx-error-circle me-1"></i>Reduce by ${excess.toFixed(1)}%`;
            percentageStatus.className = 'text-danger fw-bold';
            progressBar.classList.add('bg-danger');
            percentageCard.classList.add('border-danger');
            percentageMessage.classList.remove('d-none');
            percentageMessage.classList.add('alert-danger');
            percentageMessageText.textContent = `Total exceeds 100% by ${excess.toFixed(1)}%. Please reduce the percentages.`;
            submitBtn.disabled = true;
        }
    }

    // Add first exam by default
    addExamConfig();

    // Form validation before submit
    document.getElementById('reportCardForm').addEventListener('submit', function(e) {
        const totalPercentage = parseFloat(document.getElementById('currentPercentage').textContent);

        if (Math.abs(totalPercentage - 100) > 0.01) {
            e.preventDefault();
            alert('Exam percentages must total exactly 100%. Current total: ' + totalPercentage.toFixed(2) + '%');
            return false;
        }

        // Show loading state
        document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
        document.getElementById('submitBtn').disabled = true;
    });
});
</script>
@endsection

@push('page-style')
<style>
.form-section {
    position: relative;
}

.section-header {
    padding-bottom: 15px;
    border-bottom: 2px solid #e7e9ed;
}

.exam-config-item {
    transition: all 0.3s ease;
    border-left: 4px solid #7367f0;
}

.exam-config-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    transform: translateY(-2px);
}

.progress {
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

.progress-bar {
    font-size: 14px;
    line-height: 30px;
}

#percentageCard {
    transition: all 0.3s ease;
}

#percentageCard.border-success {
    border-color: #28c76f !important;
    background: linear-gradient(135deg, #f8fff9 0%, #e8fff0 100%);
}

#percentageCard.border-warning {
    border-color: #ff9f43 !important;
    background: linear-gradient(135deg, #fffbf5 0%, #fff4e6 100%);
}

#percentageCard.border-danger {
    border-color: #ea5455 !important;
    background: linear-gradient(135deg, #fff8f8 0%, #ffe6e6 100%);
}

.badge {
    font-size: 16px;
    padding: 8px 12px;
}

.form-control-lg, .form-select-lg {
    font-size: 1rem;
}

.input-group-lg .input-group-text {
    font-size: 1.1rem;
    font-weight: bold;
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

[data-bs-theme="dark"] .form-control,
[data-bs-theme="dark"] .form-select {
    background-color: #373851 !important;
    border-color: #3b3b55 !important;
    color: #d4d4e8 !important;
}

[data-bs-theme="dark"] .form-control:focus,
[data-bs-theme="dark"] .form-select:focus {
    background-color: #373851 !important;
    border-color: #7367f0 !important;
    color: #d4d4e8 !important;
}

[data-bs-theme="dark"] .form-label {
    color: #d4d4e8 !important;
}

[data-bs-theme="dark"] .input-group-text {
    background-color: #373851 !important;
    border-color: #3b3b55 !important;
    color: #d4d4e8 !important;
}

[data-bs-theme="dark"] .exam-config-item {
    background-color: #373851 !important;
    border-color: #3b3b55 !important;
}

[data-bs-theme="dark"] .section-header {
    border-bottom-color: #3b3b55 !important;
    color: #d4d4e8 !important;
}

[data-bs-theme="dark"] #percentageCard {
    background-color: #373851 !important;
}

[data-bs-theme="dark"] #percentageCard.border-success {
    border-color: #28c76f !important;
    background: linear-gradient(135deg, rgba(40, 199, 111, 0.1) 0%, rgba(40, 199, 111, 0.05) 100%) !important;
}

[data-bs-theme="dark"] #percentageCard.border-warning {
    border-color: #ff9f43 !important;
    background: linear-gradient(135deg, rgba(255, 159, 67, 0.1) 0%, rgba(255, 159, 67, 0.05) 100%) !important;
}

[data-bs-theme="dark"] #percentageCard.border-danger {
    border-color: #ea5455 !important;
    background: linear-gradient(135deg, rgba(234, 84, 85, 0.1) 0%, rgba(234, 84, 85, 0.05) 100%) !important;
}

[data-bs-theme="dark"] .progress {
    background-color: #373851 !important;
}

[data-bs-theme="dark"] .alert-success {
    background-color: rgba(40, 199, 111, 0.15) !important;
    border-color: #28c76f !important;
    color: #6effa9 !important;
}

[data-bs-theme="dark"] .alert-warning {
    background-color: rgba(255, 159, 67, 0.15) !important;
    border-color: #ff9f43 !important;
    color: #ffc17d !important;
}

[data-bs-theme="dark"] .alert-danger {
    background-color: rgba(234, 84, 85, 0.15) !important;
    border-color: #ea5455 !important;
    color: #ff8a8b !important;
}

[data-bs-theme="dark"] .text-muted {
    color: #a1a3b7 !important;
}

[data-bs-theme="dark"] strong {
    color: #e7e7f0 !important;
}

[data-bs-theme="dark"] textarea.form-control {
    background-color: #373851 !important;
    color: #d4d4e8 !important;
}
</style>
@endpush
@endsection
