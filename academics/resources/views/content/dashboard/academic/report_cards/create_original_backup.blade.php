@extends('layouts.modernLayout', [
    'pageTitle' => 'Create Report Card'
])

@section('title', 'Create Report Card Configuration')

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

  <div class="card">
    <div class="card-header">
      <h4 class="mb-0"><i class="bx bx-plus-circle me-2"></i>Create Report Card Configuration</h4>
    </div>

    <div class="card-body">
      <form method="POST" action="{{ route('report-cards.store') }}" id="reportCardForm">
        @csrf

        <div class="row mb-4">
          <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Configuration Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name"
                   placeholder="e.g., Term 1 Report Card 2024"
                   value="{{ old('name') }}" required>
            <small class="text-muted">Give this configuration a descriptive name</small>
          </div>

          <div class="col-md-6 mb-3">
            <label for="class_id" class="form-label">Class <span class="text-danger">*</span></label>
            <select name="class_id" id="class_id" class="form-select" required>
              <option value="">Select Class</option>
              @foreach($classes as $class)
              <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                {{ $class->name }}
              </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-4 mb-3">
            <label for="term" class="form-label">Term <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="term" name="term"
                   placeholder="e.g., Term 1, Term 2"
                   value="{{ old('term') }}" required>
          </div>

          <div class="col-md-4 mb-3">
            <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="academic_year" name="academic_year"
                   placeholder="e.g., 2024/2025"
                   value="{{ old('academic_year', date('Y') . '/' . (date('Y') + 1)) }}" required>
          </div>

          <div class="col-md-4 mb-3">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" id="status" class="form-select" required>
              <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
            </select>
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-12">
            <label for="description" class="form-label">Description (Optional)</label>
            <textarea class="form-control" id="description" name="description" rows="2"
                      placeholder="Brief description of this report card configuration">{{ old('description') }}</textarea>
          </div>
        </div>

        <hr class="my-4">

        <div class="mb-3">
          <h5 class="mb-3">
            <i class="bx bx-notepad me-2"></i>Exam Configuration
            <span class="badge bg-primary" id="totalPercentage">0%</span>
          </h5>
          <p class="text-muted">Add exams and their percentage weights. Total must equal 100%.</p>
        </div>

        <div id="examConfigs">
          <!-- Exam configs will be added here -->
        </div>

        <button type="button" class="btn btn-outline-primary mb-4" id="addExamBtn">
          <i class="bx bx-plus me-1"></i>Add Exam
        </button>

        <div class="alert alert-info" id="percentageWarning" style="display: none;">
          <i class="bx bx-info-circle me-2"></i>
          <span id="warningText"></span>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('report-cards.index') }}" class="btn btn-secondary">
            <i class="bx bx-x me-1"></i>Cancel
          </a>
          <button type="submit" class="btn btn-primary" id="submitBtn">
            <i class="bx bx-save me-1"></i>Create Configuration
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .exam-config-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid #e0e0e0;
  }

  .exam-config-item:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  #totalPercentage.text-success {
    background-color: #28a745 !important;
  }

  #totalPercentage.text-danger {
    background-color: #dc3545 !important;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  let examCount = 0;
  const examsData = @json($exams);
  const examConfigsContainer = document.getElementById('examConfigs');
  const addExamBtn = document.getElementById('addExamBtn');
  const totalPercentageBadge = document.getElementById('totalPercentage');
  const percentageWarning = document.getElementById('percentageWarning');
  const warningText = document.getElementById('warningText');
  const submitBtn = document.getElementById('submitBtn');

  function calculateTotalPercentage() {
    let total = 0;
    document.querySelectorAll('.percentage-input').forEach(input => {
      total += parseFloat(input.value) || 0;
    });

    totalPercentageBadge.textContent = total + '%';

    // Update badge color
    totalPercentageBadge.classList.remove('bg-primary', 'bg-success', 'bg-danger');
    if (total === 100) {
      totalPercentageBadge.classList.add('bg-success');
      percentageWarning.style.display = 'none';
      submitBtn.disabled = false;
    } else {
      totalPercentageBadge.classList.add('bg-danger');
      percentageWarning.style.display = 'block';
      if (total > 100) {
        warningText.textContent = `Total percentage is ${total}%. Please reduce to exactly 100%.`;
      } else {
        warningText.textContent = `Total percentage is ${total}%. Please add ${100 - total}% more.`;
      }
      submitBtn.disabled = true;
    }
  }

  function addExamConfig() {
    examCount++;
    const examConfig = document.createElement('div');
    examConfig.className = 'exam-config-item';
    examConfig.setAttribute('data-exam-id', examCount);

    examConfig.innerHTML = `
      <div class="row align-items-end">
        <div class="col-md-6 mb-3">
          <label class="form-label">Select Exam <span class="text-danger">*</span></label>
          <select name="exams[${examCount}][exam_id]" class="form-select exam-select" required>
            <option value="">Choose an exam</option>
            ${examsData.map(exam => `<option value="${exam.id}">${exam.name}</option>`).join('')}
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Percentage Weight <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="number" name="exams[${examCount}][percentage_weight]"
                   class="form-control percentage-input"
                   min="0" max="100" step="0.1"
                   placeholder="40" required>
            <span class="input-group-text">%</span>
          </div>
        </div>
        <div class="col-md-2 mb-3">
          <button type="button" class="btn btn-danger w-100 remove-exam-btn" data-exam-id="${examCount}">
            <i class="bx bx-trash"></i> Remove
          </button>
        </div>
      </div>
    `;

    examConfigsContainer.appendChild(examConfig);

    // Add event listener for percentage calculation
    const percentageInput = examConfig.querySelector('.percentage-input');
    percentageInput.addEventListener('input', calculateTotalPercentage);

    // Add event listener for remove button
    const removeBtn = examConfig.querySelector('.remove-exam-btn');
    removeBtn.addEventListener('click', function() {
      examConfig.remove();
      calculateTotalPercentage();
    });

    calculateTotalPercentage();
  }

  // Add first exam by default
  addExamConfig();

  addExamBtn.addEventListener('click', addExamConfig);

  // Form validation
  document.getElementById('reportCardForm').addEventListener('submit', function(e) {
    const total = parseFloat(totalPercentageBadge.textContent);
    if (total !== 100) {
      e.preventDefault();
      alert('Total percentage must equal exactly 100% before submitting.');
      return false;
    }

    // Check for duplicate exams
    const selectedExams = [];
    const examSelects = document.querySelectorAll('.exam-select');
    let hasDuplicates = false;

    examSelects.forEach(select => {
      if (select.value && selectedExams.includes(select.value)) {
        hasDuplicates = true;
      }
      if (select.value) {
        selectedExams.push(select.value);
      }
    });

    if (hasDuplicates) {
      e.preventDefault();
      alert('You cannot select the same exam multiple times.');
      return false;
    }

    if (selectedExams.length === 0) {
      e.preventDefault();
      alert('Please add at least one exam.');
      return false;
    }
  });
});
</script>
@endsection
