@extends('layouts.modernLayout', [
    'pageTitle' => 'Report Card Comments'
])

@section('title', 'Manage Comments')

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

  <!-- Student Info Card -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-lg me-3">
            <span class="avatar-initial rounded-circle bg-label-primary fs-4">
              {{ substr($student->first_name ?? 'N', 0, 1) }}
            </span>
          </div>
          <div>
            <h5 class="mb-0">{{ $student->first_name ?? '' }} {{ $student->last_name ?? '' }}</h5>
            <small class="text-muted">Reg. No: {{ $student->registration_no ?? 'N/A' }}</small>
          </div>
        </div>
        <a href="{{ route('report-cards.show', $configuration->id) }}" class="btn btn-sm btn-secondary">
          <i class="bx bx-arrow-back"></i> Back to Report
        </a>
      </div>
    </div>
  </div>

  <!-- Comments Card -->
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0"><i class="bx bx-comment-edit me-2"></i>Manage Comments for {{ $configuration->name }}</h5>
    </div>

    <div class="card-body">
      <!-- Nav Tabs -->
      <ul class="nav nav-pills mb-4" id="commentTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="accountant-tab" data-bs-toggle="tab"
                  data-bs-target="#accountant" type="button" role="tab">
            <i class="bx bx-dollar-circle me-1"></i>Accountant
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="discipline-tab" data-bs-toggle="tab"
                  data-bs-target="#discipline" type="button" role="tab">
            <i class="bx bx-shield me-1"></i>Discipline
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="class-teacher-tab" data-bs-toggle="tab"
                  data-bs-target="#class-teacher" type="button" role="tab">
            <i class="bx bx-chalkboard me-1"></i>Class Teacher
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="subject-teacher-tab" data-bs-toggle="tab"
                  data-bs-target="#subject-teacher" type="button" role="tab">
            <i class="bx bx-book me-1"></i>Subject Teacher
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="academic-tab" data-bs-toggle="tab"
                  data-bs-target="#academic" type="button" role="tab">
            <i class="bx bx-brain me-1"></i>Academic
          </button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content" id="commentTabContent">
        <!-- Accountant Tab -->
        <div class="tab-pane fade show active" id="accountant" role="tabpanel">
          <form action="{{ route('report-cards.comments.store') }}" method="POST" class="comment-form">
            @csrf
            <input type="hidden" name="report_card_config_id" value="{{ $configuration->id }}">
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            <input type="hidden" name="stakeholder_type" value="accountant">

            <div class="mb-3">
              <label class="form-label">Select Template (Optional)</label>
              <select class="form-select template-select" data-target="accountant-comment">
                <option value="">-- Custom Comment --</option>
                @foreach($commentTemplates->where('stakeholder_type', 'accountant') as $template)
                <option value="{{ $template->content }}">{{ $template->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Comment <span class="text-danger">*</span></label>
              <textarea class="form-control" name="comment" id="accountant-comment" rows="4"
                        placeholder="Enter accountant's comment about fee status..." required>{{ $existingComments['accountant']->comment ?? '' }}</textarea>
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i>Save Comment
              </button>
            </div>
          </form>
        </div>

        <!-- Discipline Tab -->
        <div class="tab-pane fade" id="discipline" role="tabpanel">
          <form action="{{ route('report-cards.comments.store') }}" method="POST" class="comment-form">
            @csrf
            <input type="hidden" name="report_card_config_id" value="{{ $configuration->id }}">
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            <input type="hidden" name="stakeholder_type" value="discipline">

            <div class="mb-3">
              <label class="form-label">Select Template (Optional)</label>
              <select class="form-select template-select" data-target="discipline-comment">
                <option value="">-- Custom Comment --</option>
                @foreach($commentTemplates->where('stakeholder_type', 'discipline') as $template)
                <option value="{{ $template->content }}">{{ $template->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Comment <span class="text-danger">*</span></label>
              <textarea class="form-control" name="comment" id="discipline-comment" rows="4"
                        placeholder="Enter discipline teacher's comment about behavior and conduct..." required>{{ $existingComments['discipline']->comment ?? '' }}</textarea>
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i>Save Comment
              </button>
            </div>
          </form>
        </div>

        <!-- Class Teacher Tab -->
        <div class="tab-pane fade" id="class-teacher" role="tabpanel">
          <form action="{{ route('report-cards.comments.store') }}" method="POST" class="comment-form">
            @csrf
            <input type="hidden" name="report_card_config_id" value="{{ $configuration->id }}">
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            <input type="hidden" name="stakeholder_type" value="class_teacher">

            <div class="mb-3">
              <label class="form-label">Select Template (Optional)</label>
              <select class="form-select template-select" data-target="class-teacher-comment">
                <option value="">-- Custom Comment --</option>
                @foreach($commentTemplates->where('stakeholder_type', 'class_teacher') as $template)
                <option value="{{ $template->content }}">{{ $template->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Comment <span class="text-danger">*</span></label>
              <textarea class="form-control" name="comment" id="class-teacher-comment" rows="4"
                        placeholder="Enter class teacher's comment about overall performance..." required>{{ $existingComments['class_teacher']->comment ?? '' }}</textarea>
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i>Save Comment
              </button>
            </div>
          </form>
        </div>

        <!-- Subject Teacher Tab -->
        <div class="tab-pane fade" id="subject-teacher" role="tabpanel">
          <div class="alert alert-info mb-4">
            <i class="bx bx-info-circle me-2"></i>
            Add comments for each subject the student takes.
          </div>

          @foreach($subjects as $subject)
          <div class="card mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bx bx-book-bookmark me-2"></i>{{ $subject->name }}</h6>
            </div>
            <div class="card-body">
              <form action="{{ route('report-cards.comments.store') }}" method="POST" class="comment-form">
                @csrf
                <input type="hidden" name="report_card_config_id" value="{{ $configuration->id }}">
                <input type="hidden" name="student_id" value="{{ $student->id }}">
                <input type="hidden" name="stakeholder_type" value="subject_teacher">
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">

                <div class="mb-3">
                  <label class="form-label">Select Template (Optional)</label>
                  <select class="form-select template-select" data-target="subject-{{ $subject->id }}-comment">
                    <option value="">-- Custom Comment --</option>
                    @foreach($commentTemplates->where('stakeholder_type', 'subject_teacher') as $template)
                    <option value="{{ $template->content }}">{{ $template->name }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label">Comment <span class="text-danger">*</span></label>
                  <textarea class="form-control" name="comment" id="subject-{{ $subject->id }}-comment" rows="3"
                            placeholder="Enter subject teacher's comment..." required>{{ $existingComments['subject_teacher'][$subject->id]->comment ?? '' }}</textarea>
                </div>

                <div class="text-end">
                  <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bx bx-save me-1"></i>Save
                  </button>
                </div>
              </form>
            </div>
          </div>
          @endforeach
        </div>

        <!-- Academic Tab -->
        <div class="tab-pane fade" id="academic" role="tabpanel">
          <form action="{{ route('report-cards.comments.store') }}" method="POST" class="comment-form">
            @csrf
            <input type="hidden" name="report_card_config_id" value="{{ $configuration->id }}">
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            <input type="hidden" name="stakeholder_type" value="academic">

            <div class="mb-3">
              <label class="form-label">Select Template (Optional)</label>
              <select class="form-select template-select" data-target="academic-comment">
                <option value="">-- Custom Comment --</option>
                @foreach($commentTemplates->where('stakeholder_type', 'academic') as $template)
                <option value="{{ $template->content }}">{{ $template->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Comment <span class="text-danger">*</span></label>
              <textarea class="form-control" name="comment" id="academic-comment" rows="4"
                        placeholder="Enter academic comment about overall academic performance..." required>{{ $existingComments['academic']->comment ?? '' }}</textarea>
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i>Save Comment
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@push('page-style')
<style>
  .nav-pills .nav-link {
    border-radius: 0.375rem;
    margin-right: 0.5rem;
  }

  .nav-pills .nav-link.active {
    background-color: #696cff;
  }

  @media (max-width: 768px) {
    .nav-pills {
      flex-wrap: wrap;
    }

    .nav-pills .nav-link {
      font-size: 0.875rem;
      padding: 0.5rem 0.75rem;
      margin-bottom: 0.5rem;
    }
  }

  .card-header.bg-light {
    background-color: #f8f9fa !important;
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

  [data-bs-theme="dark"] .card-header.bg-light {
    background-color: #373851 !important;
  }

  [data-bs-theme="dark"] .card-body {
    background-color: #2b2c40 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .form-control,
  [data-bs-theme="dark"] .form-select,
  [data-bs-theme="dark"] textarea.form-control {
    background-color: #373851 !important;
    border-color: #3b3b55 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .form-control:focus,
  [data-bs-theme="dark"] textarea.form-control:focus {
    background-color: #373851 !important;
    border-color: #7367f0 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .form-label {
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .text-muted,
  [data-bs-theme="dark"] small.text-muted {
    color: #a1a3b7 !important;
  }

  [data-bs-theme="dark"] .nav-pills .nav-link {
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .nav-pills .nav-link.active {
    background-color: #7367f0 !important;
  }

  [data-bs-theme="dark"] .alert-info {
    background-color: rgba(0, 207, 232, 0.15) !important;
    border-color: #00cfe8 !important;
    color: #60dfed !important;
  }

  [data-bs-theme="dark"] .btn-secondary {
    background-color: #3b3b55 !important;
    border-color: #3b3b55 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] h5 {
    color: #e7e7f0 !important;
  }
</style>
@endpush

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Template selection functionality
  const templateSelects = document.querySelectorAll('.template-select');

  templateSelects.forEach(select => {
    select.addEventListener('change', function() {
      const targetId = this.getAttribute('data-target');
      const targetTextarea = document.getElementById(targetId);

      if (this.value && targetTextarea) {
        targetTextarea.value = this.value;
      }
    });
  });

  // Form submission with loading state
  const forms = document.querySelectorAll('.comment-form');

  forms.forEach(form => {
    form.addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;

      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

      // Re-enable after a timeout if submission fails
      setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }, 5000);
    });
  });
});
</script>
@endpush

@endsection
