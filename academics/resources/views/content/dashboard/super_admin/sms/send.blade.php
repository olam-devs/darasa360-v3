@extends('layouts.modernLayout', [
    'pageTitle' => 'SMS Management'
])

@section('title', 'Send SMS')

@section('content')
<div class="container">
  <h4 class="fw-bold py-3 mb-4">SMS Management</h4>

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

  <!-- SMS Balance Card -->
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card text-center border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="avatar flex-shrink-0 mb-3 mx-auto" style="width: 60px; height: 60px;">
            <img src="{{ asset('assets/img/icons/unicons/message.png') }}" alt="sms" class="rounded w-100 h-100" />
          </div>
          <h5 class="fw-semibold mb-2 text-muted">Available SMS</h5>
          <h3 class="card-title mb-0 text-primary">{{ $smsBalance }} <span class="fs-6 fw-normal">Messages</span></h3>
          <div class="mt-3">
            <span class="badge bg-label-primary rounded-pill">
              <i class="bx bx-refresh me-1"></i> Auto-refreshed
            </span>
          </div>
        </div>
        <div class="card-footer bg-transparent border-top py-2">
          <small class="text-muted">Last updated: {{ now()->format('M d, Y h:i A') }}</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Send SMS Button -->
  <div class="mb-3 text-end">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendSmsModal">
      <i class="bx bx-send"></i> Send SMS
    </button>
  </div>

  <!-- Sent SMS History Table -->
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Sent Messages</h5>
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>Date</th>
              <th>Audience</th>
              <th>Message</th>
              <th>SMS Units</th>
            </tr>
          </thead>
          <tbody>
            @forelse($sentMessages as $sms)
              <tr>
                <td>{{ \Carbon\Carbon::parse($sms->created_at)->format('M d, Y h:i A') }}</td>
                <td>
                  @if($sms->class)
                    Class {{ $sms->class->name }}
                  @elseif($sms->student_id)
                    Individual
                  @else
                    Whole School
                  @endif
                </td>
                <td>
                  {{ substr($sms->message, 0, 60) }}{{ strlen($sms->message) > 60 ? '...' : '' }}
                  @if(strlen($sms->message) > 60)
                    <a href="javascript:void(0);"
                       data-bs-toggle="modal"
                       data-bs-target="#viewMessageModal"
                       data-message="{{ $sms->message }}"
                       class="text-primary ms-2"
                       title="View Full Message">
                      <i class="bx bx-message-square-detail"></i>
                    </a>
                  @endif
                </td>
                <td>{{ $sms->sms_units ?? '1' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted">No SMS sent yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($sentMessages, 'links'))
        <div class="mt-3">
          {{ $sentMessages->links('pagination::bootstrap-5') }}
        </div>
      @endif
    </div>
  </div>

  <!-- Send SMS Modal -->
  <div class="modal fade" id="sendSmsModal" tabindex="-1" aria-labelledby="sendSmsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <form method="POST" action="{{ route('sms.send.process') }}" id="smsForm">
          @csrf

          <!-- Modal Header -->
          <div class="modal-header">
            <h5 class="modal-title" id="sendSmsModalLabel">Send New SMS</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body">
            <div class="mb-3">
              <label for="audience" class="form-label">Send To</label>
              <select class="form-select" name="audience" id="audience" required>
                <option value="">-- Select Recipient --</option>
                @if($role !== 'ClassTeacher')
                  <option value="all">Whole School</option>
                @endif
                @foreach($classes as $class)
                  <option value="class_{{ $class->id }}">Class {{ $class->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3" id="studentSelector" style="display: none;">
              <label class="form-label">Select Students (Optional)</label>
              <input type="text" id="studentSearchInput" class="form-control mb-2" placeholder="Type to search students..." autocomplete="off" />
              <div id="searchResults" class="border rounded mb-2" style="max-height: 150px; overflow-y: auto;"></div>
              <div id="selectedStudents" class="mb-2"></div>
              <small class="form-text text-muted">Leave empty to send to all students in the class.</small>
              <input type="hidden" name="students" id="studentsData" />
            </div>

            <div class="mb-3">
              <label for="message" class="form-label">Message</label>
              <textarea class="form-control" name="message" id="message" rows="5" maxlength="612" required></textarea>
              <div class="form-text mt-1">
                Characters: <span id="charCount">0</span> |
                SMS Count: <span id="segmentsCount">0</span>
              </div>
            </div>
          </div>

          <!-- Modal Footer (sticky to bottom) -->
          <div class="modal-footer sticky-bottom bg-white">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-send"></i> Send SMS
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- View Full Message Modal -->
  <div class="modal fade" id="viewMessageModal" tabindex="-1" aria-labelledby="viewMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewMessageModalLabel">Message</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="fullMessageContent" class="mb-0" style="white-space: pre-wrap;"></p>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const messageEl = document.getElementById('message');
  const charCountEl = document.getElementById('charCount');
  const segCountEl = document.getElementById('segmentsCount');
  const audienceEl = document.getElementById('audience');
  const studentSelector = document.getElementById('studentSelector');
  const searchInput = document.getElementById('studentSearchInput');
  const searchResults = document.getElementById('searchResults');
  const selectedStudentsDiv = document.getElementById('selectedStudents');
  const studentsDataInput = document.getElementById('studentsData');

  let selectedStudents = [];

  const schoolMessageName = @json($schoolMessageName);
  const prefix = schoolMessageName ? schoolMessageName + ': ' : '';

  function updateSmsStats() {
    const text = messageEl.value || '';
    const fullText = prefix + text;
    const isGsm = /^[A-Za-z0-9 \r\n@£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞ^{}\[~\]|€ÆæßÉ!\"#$%&'()*+,\-./:;<=>?¡¿]+$/.test(fullText);

    const userVisibleCount = text.length;
    let segments = 0, charCount = 0;

    if (isGsm) {
      charCount = fullText.length;
      segments = charCount <= 160 ? 1 : Math.ceil(charCount / 153);
    } else {
      charCount = fullText.length;
      segments = charCount <= 70 ? 1 : Math.ceil(charCount / 67);
    }

    charCountEl.textContent = userVisibleCount;
    segCountEl.textContent = segments;
    if (segments > 1) segCountEl.classList.add('text-warning');
    else segCountEl.classList.remove('text-warning');
  }

  messageEl.addEventListener('input', updateSmsStats);
  updateSmsStats();

  audienceEl.addEventListener('change', function () {
    const value = this.value;
    if (value.startsWith('class_')) {
      studentSelector.style.display = 'block';
      searchInput.value = '';
      searchResults.innerHTML = '';
      selectedStudents = [];
      updateSelectedTags();
    } else {
      studentSelector.style.display = 'none';
      searchInput.value = '';
      searchResults.innerHTML = '';
      selectedStudents = [];
      updateSelectedTags();
    }
  });

  searchInput.addEventListener('input', function () {
    const query = this.value.trim();
    if (query.length < 2) {
      searchResults.innerHTML = '';
      return;
    }
    const classId = audienceEl.value.split('_')[1];
    fetch(`/sms/search-students/${classId}?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        searchResults.innerHTML = '';
        if (data.length === 0) {
          searchResults.innerHTML = '<div class="text-muted small p-2">No students found</div>';
          return;
        }
        data.forEach(student => {
          if (!selectedStudents.find(s => s.id === student.id)) {
            const div = document.createElement('div');
            div.className = 'search-result-item p-2 border-bottom';
            const fullName = `${student.first_name} ${student.middle_name ? student.middle_name + ' ' : ''}${student.last_name}`;
            div.textContent = `${fullName} (${student.registration_no})`;
            div.style.cursor = 'pointer';
            div.onclick = () => addStudentTag(student.id, fullName);
            searchResults.appendChild(div);
          }
        });
      });
  });

  function addStudentTag(id, fullName) {
    if (!selectedStudents.find(s => s.id === id)) {
      selectedStudents.push({id, fullName});
      updateSelectedTags();
      searchInput.value = '';
      searchResults.innerHTML = '';
    }
  }

  window.removeStudent = function(id) {
    selectedStudents = selectedStudents.filter(s => s.id !== id);
    updateSelectedTags();
  }

  function updateSelectedTags() {
    selectedStudentsDiv.innerHTML = '';
    selectedStudents.forEach(({id, fullName}) => {
      const tag = document.createElement('span');
      tag.className = 'badge bg-primary me-1 mb-1';
      tag.innerHTML = `${fullName} <i class="bx bx-x ms-1" style="cursor:pointer" onclick="removeStudent(${id})"></i>`;
      selectedStudentsDiv.appendChild(tag);
    });
    studentsDataInput.value = selectedStudents.map(s => s.id).join(',');
  }
});

// View full message in modal
const viewMessageModal = document.getElementById('viewMessageModal');
viewMessageModal.addEventListener('show.bs.modal', function (event) {
  const trigger = event.relatedTarget;
  const message = trigger.getAttribute('data-message');
  const messageContent = viewMessageModal.querySelector('#fullMessageContent');
  messageContent.textContent = message;
});
</script>

<style>
/* Ensure modal body scrolls while footer stays visible */
.modal-body {
  max-height: 60vh;
  overflow-y: auto;
}
.modal-footer.sticky-bottom {
  position: sticky;
  bottom: 0;
  z-index: 10;
}
</style>
@endsection
