@extends('layouts.modernLayout', [
    'pageTitle' => 'Parent Letter'
])

@section('title', 'Compose Parent Letter')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

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

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h4 class="mb-1"><i class="bx bx-envelope me-2"></i>Letter to Parents/Guardians</h4>
              <p class="text-muted mb-0">
                <strong>Configuration:</strong> {{ $configuration->name }} |
                <strong>Class:</strong> {{ $configuration->class->name ?? 'N/A' }} |
                <strong>Term:</strong> {{ $configuration->term }}
              </p>
            </div>
            <a href="{{ route('report-cards.show', $configuration->id) }}" class="btn btn-label-secondary">
              <i class="bx bx-arrow-back me-1"></i>Back to Configuration
            </a>
          </div>
        </div>
      </div>

      <form method="POST" action="{{ route('report-cards.parent-letter.store', $configuration->id) }}" id="parentLetterForm">
        @csrf

        <!-- Letter Title Section -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-heading me-2"></i>Letter Header</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="letter_title" class="form-label">Letter Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="letter_title" name="letter_title"
                     value="{{ old('letter_title', $parentLetter->letter_title ?? 'Dear Parents/Guardians') }}"
                     required>
              <small class="text-muted">This appears at the top of the letter</small>
            </div>
          </div>
        </div>

        <!-- Closing Semester Message -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-calendar-check me-2"></i>Closing Semester Message</h5>
            <small class="text-muted">Reflect on the term that just ended</small>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="closing_semester_message" class="form-label">Message <span class="text-danger">*</span></label>
              <textarea class="form-control" id="closing_semester_message" name="closing_semester_message"
                        rows="6" required>{{ old('closing_semester_message', $parentLetter->closing_semester_message ?? '') }}</textarea>
              <small class="text-muted">Include achievements, challenges faced, and overall performance summary</small>
            </div>

            <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertTemplate('closing_semester_message', 'closing')">
              <i class="bx bx-file-blank me-1"></i>Use Template
            </button>
          </div>
        </div>

        <!-- Upcoming Semester Plans -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-calendar-star me-2"></i>Upcoming Semester Plans</h5>
            <small class="text-muted">What parents should expect next term</small>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="upcoming_semester_message" class="form-label">Message <span class="text-danger">*</span></label>
              <textarea class="form-control" id="upcoming_semester_message" name="upcoming_semester_message"
                        rows="6" required>{{ old('upcoming_semester_message', $parentLetter->upcoming_semester_message ?? '') }}</textarea>
              <small class="text-muted">Include new subjects, activities, important dates, and preparations needed</small>
            </div>

            <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertTemplate('upcoming_semester_message', 'upcoming')">
              <i class="bx bx-file-blank me-1"></i>Use Template
            </button>
          </div>
        </div>

        <!-- Fee Payment Notice -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-money me-2"></i>Fee Payment Information</h5>
            <small class="text-muted">Payment details and deadlines</small>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="fee_payment_notice" class="form-label">Payment Notice</label>
              <textarea class="form-control" id="fee_payment_notice" name="fee_payment_notice"
                        rows="5">{{ old('fee_payment_notice', $parentLetter->fee_payment_notice ?? '') }}</textarea>
              <small class="text-muted">Include account details, payment deadlines, and any outstanding balance notices</small>
            </div>

            <button type="button" class="btn btn-sm btn-outline-primary" onclick="insertTemplate('fee_payment_notice', 'fee')">
              <i class="bx bx-file-blank me-1"></i>Use Template
            </button>
          </div>
        </div>

        <!-- Special Announcements -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-bell me-2"></i>Special Announcements</h5>
            <small class="text-muted">Important notices and updates</small>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="special_announcements" class="form-label">Announcements</label>
              <textarea class="form-control" id="special_announcements" name="special_announcements"
                        rows="5">{{ old('special_announcements', $parentLetter->special_announcements ?? '') }}</textarea>
              <small class="text-muted">School events, policy changes, important reminders, etc.</small>
            </div>
          </div>
        </div>

        <!-- Safari Trips & Excursions -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-bus me-2"></i>Safari Trips & Excursions</h5>
            <small class="text-muted">Educational trips planned for next term</small>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="safari_trips" class="form-label">Trip Details</label>
              <textarea class="form-control" id="safari_trips" name="safari_trips"
                        rows="5">{{ old('safari_trips', $parentLetter->safari_trips ?? '') }}</textarea>
              <small class="text-muted">Include destinations, dates, costs, and permission requirements</small>
            </div>
          </div>
        </div>

        <!-- Other Information -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Additional Information</h5>
            <small class="text-muted">Any other important information</small>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="other_information" class="form-label">Other Information</label>
              <textarea class="form-control" id="other_information" name="other_information"
                        rows="4">{{ old('other_information', $parentLetter->other_information ?? '') }}</textarea>
              <small class="text-muted">Any additional notices or information</small>
            </div>
          </div>
        </div>

        <!-- Signature Section -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-pen me-2"></i>Signature</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="signature_name" class="form-label">Signatory Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="signature_name" name="signature_name"
                       value="{{ old('signature_name', $parentLetter->signature_name ?? Auth::user()->first_name . ' ' . Auth::user()->last_name) }}"
                       required>
                <small class="text-muted">Usually Academic Director or Principal</small>
              </div>
              <div class="col-md-6 mb-3">
                <label for="signature_title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="signature_title" name="signature_title"
                       value="{{ old('signature_title', $parentLetter->signature_title ?? 'Academic Director') }}"
                       required>
                <small class="text-muted">Job title of the signatory</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <button type="button" class="btn btn-label-secondary" onclick="previewLetter()">
                <i class="bx bx-show me-1"></i>Preview Letter
              </button>
              <div>
                <button type="submit" class="btn btn-primary">
                  <i class="bx bx-save me-1"></i>Save & Continue
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Letter Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="letter-preview" style="padding: 30px; background: #fff; border: 1px solid #e0e0e0;">
          <div class="text-center mb-4">
            <h3 id="preview_title"></h3>
          </div>

          <div class="mb-4" id="preview_closing_section"></div>
          <div class="mb-4" id="preview_upcoming_section"></div>
          <div class="mb-4" id="preview_fee_section"></div>
          <div class="mb-4" id="preview_announcements_section"></div>
          <div class="mb-4" id="preview_safari_section"></div>
          <div class="mb-4" id="preview_other_section"></div>

          <div class="mt-5">
            <p class="mb-1"><strong>Sincerely,</strong></p>
            <p class="mb-0" id="preview_signature_name"></p>
            <p class="text-muted" id="preview_signature_title"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
// Template content
const templates = {
  closing: `We are pleased to present the academic report for {{ $configuration->term }}, {{ $configuration->academic_year }}. This term has been filled with remarkable achievements and valuable learning experiences for our students.

Our students have shown dedication and commitment to their studies. We have seen significant progress in various subjects, and we commend both students and parents for their continued support and cooperation.

We acknowledge the challenges faced during this term and appreciate how our school community came together to overcome them. Thank you for your continued trust in our educational mission.`,

  upcoming: `As we look forward to {{ $configuration->term == 'Term 1' ? 'Term 2' : ($configuration->term == 'Term 2' ? 'Term 3' : 'the next academic year') }}, we have exciting plans and opportunities ahead.

The upcoming term will focus on building upon the foundation established this term. Students will be introduced to new topics and engaging activities designed to enhance their learning experience.

Important dates for next term:
- Term begins: [Date to be announced]
- Mid-term break: [Date to be announced]
- Parent-teacher conferences: [Date to be announced]
- End of term: [Date to be announced]

We encourage parents to ensure students are well-prepared and arrive on time for the new term.`,

  fee: `School Fee Payment Information:

Payment Account Details:
Bank Name: [Your Bank Name]
Account Number: [Account Number]
Account Name: [School Name]

Payment Deadline: [Date]

Please ensure all fees are paid by the specified deadline to avoid any disruption to your child's education. For any fee-related inquiries, please contact the accounts office.

We appreciate your prompt payment and continued financial support.`
};

function insertTemplate(fieldId, templateKey) {
  if (templates[templateKey]) {
    document.getElementById(fieldId).value = templates[templateKey];
  }
}

function previewLetter() {
  const title = document.getElementById('letter_title').value;
  const closing = document.getElementById('closing_semester_message').value;
  const upcoming = document.getElementById('upcoming_semester_message').value;
  const fee = document.getElementById('fee_payment_notice').value;
  const announcements = document.getElementById('special_announcements').value;
  const safari = document.getElementById('safari_trips').value;
  const other = document.getElementById('other_information').value;
  const signatureName = document.getElementById('signature_name').value;
  const signatureTitle = document.getElementById('signature_title').value;

  document.getElementById('preview_title').textContent = title;

  document.getElementById('preview_closing_section').innerHTML = closing ?
    `<h6>Closing Semester</h6><p style="white-space: pre-wrap;">${closing}</p>` : '';

  document.getElementById('preview_upcoming_section').innerHTML = upcoming ?
    `<h6>Upcoming Semester</h6><p style="white-space: pre-wrap;">${upcoming}</p>` : '';

  document.getElementById('preview_fee_section').innerHTML = fee ?
    `<h6>Fee Payment Information</h6><p style="white-space: pre-wrap;">${fee}</p>` : '';

  document.getElementById('preview_announcements_section').innerHTML = announcements ?
    `<h6>Special Announcements</h6><p style="white-space: pre-wrap;">${announcements}</p>` : '';

  document.getElementById('preview_safari_section').innerHTML = safari ?
    `<h6>Safari Trips & Excursions</h6><p style="white-space: pre-wrap;">${safari}</p>` : '';

  document.getElementById('preview_other_section').innerHTML = other ?
    `<h6>Additional Information</h6><p style="white-space: pre-wrap;">${other}</p>` : '';

  document.getElementById('preview_signature_name').textContent = signatureName;
  document.getElementById('preview_signature_title').textContent = signatureTitle;

  const modal = new bootstrap.Modal(document.getElementById('previewModal'));
  modal.show();
}

// Auto-save functionality (optional)
let autoSaveTimeout;
document.querySelectorAll('textarea, input[type="text"]').forEach(element => {
  element.addEventListener('input', function() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
      // Could implement auto-save to localStorage here
      console.log('Auto-saving...');
    }, 2000);
  });
});
</script>
@endpush

@push('page-style')
<style>
  /* Dark Theme Support for Parent Letter Page */
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
  [data-bs-theme="dark"] .form-select,
  [data-bs-theme="dark"] textarea.form-control {
    background-color: #373851 !important;
    border-color: #3b3b55 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .form-control:focus,
  [data-bs-theme="dark"] .form-select:focus,
  [data-bs-theme="dark"] textarea.form-control:focus {
    background-color: #373851 !important;
    border-color: #7367f0 !important;
    color: #d4d4e8 !important;
    box-shadow: 0 0 0 0.25rem rgba(115, 103, 240, 0.25) !important;
  }

  [data-bs-theme="dark"] .form-label {
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .text-muted,
  [data-bs-theme="dark"] small.text-muted {
    color: #a1a3b7 !important;
  }

  [data-bs-theme="dark"] .input-group-text {
    background-color: #373851 !important;
    border-color: #3b3b55 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .alert-warning {
    background-color: rgba(255, 159, 67, 0.15) !important;
    border-color: #ff9f43 !important;
    color: #ffc17d !important;
  }

  [data-bs-theme="dark"] .btn-secondary {
    background-color: #3b3b55 !important;
    border-color: #3b3b55 !important;
    color: #d4d4e8 !important;
  }
</style>
@endpush
@endsection
