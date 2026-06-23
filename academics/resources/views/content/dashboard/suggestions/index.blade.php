@extends('layouts.modernLayout', [
    'pageTitle' => 'Add New Suggestion'
])

@section('title', 'My Suggestions')

@section('content')
<div class="row">
  <div class="col-12">

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

    @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <!-- New Suggestion Card -->
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Add New Suggestion</h5>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#suggestionForm">
          <i class="bx bx-chevron-down"></i>
        </button>
      </div>
      <div class="collapse show" id="suggestionForm">
        <div class="card-body">
          <form method="POST" action="{{ route('suggestions.store') }}">
            @csrf
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="destination_role" class="form-label">Destination Role</label>
                <select name="destination_role" id="destination_role" class="form-select @error('destination_role') is-invalid @enderror" required>
                  <option value="">Select Role</option>
                  @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ old('destination_role') == $role->name ? 'selected' : '' }}>
                      {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                    </option>
                  @endforeach
                </select>
                @error('destination_role')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6 mb-3" id="class-select-wrapper" style="display: none;">
                <label for="class_id" class="form-label">Select Class</label>
                <select name="class_id" id="class_id" class="form-select @error('class_id') is-invalid @enderror">
                  <option value="">Select Class</option>
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

              <div class="col-md-6 mb-3" id="subject-select-wrapper" style="display: none;">
                <label for="subject_id" class="form-label">Select Subject (for Teacher)</label>
                <select name="subject_id" id="subject_id" class="form-select @error('subject_id') is-invalid @enderror">
                  <option value="">Select Subject</option>
                  @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                      {{ $subject->name }}
                    </option>
                  @endforeach
                </select>
                @error('subject_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="mb-3">
              <label for="content" class="form-label">Suggestion Content</label>
              <textarea name="content" id="content" rows="4" class="form-control @error('content') is-invalid @enderror" placeholder="Type your suggestion here..." required>{{ old('content') }}</textarea>
              @error('content')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary">Submit Suggestion</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- All Suggestions Section for Headmaster and Academic -->
    @if(in_array($userRoleName, ['Headmaster', 'Academic']))
    <div class="card mb-4">
      <div class="card-header">
        <h4 class="card-title">All Suggestions</h4>
        <p class="card-subtitle">View all suggestions and replies in the system</p>
      </div>
      <div class="card-body">
        @if($allSuggestions->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>From</th>
                  <th>To</th>
                  <th>Class/Subject</th>
                  <th>Content Preview</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($allSuggestions as $suggestion)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                          <span class="avatar-initial rounded-circle bg-label-primary">
                            <i class="bx bx-user"></i>
                          </span>
                        </div>
                        <div>
                          <div class="fw-semibold">
                            {{ $suggestion->sender->first_name ?? $suggestion->sender->username ?? 'N/A' }}
                            {{ $suggestion->sender->last_name ?? '' }}
                          </div>
                          <small class="text-muted">
                            @if($suggestion->sender && $suggestion->sender->role_name)
                              {{ ucfirst(str_replace('_', ' ', $suggestion->sender->role_name->name)) }}
                            @else
                              N/A
                            @endif
                          </small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                          <span class="avatar-initial rounded-circle bg-label-info">
                            <i class="bx bx-user"></i>
                          </span>
                        </div>
                        <div>
                          <div class="fw-semibold">{{ $suggestion->replier->first_name ?? $suggestion->replier->username ?? 'Staff' }}</div>
                          <small class="text-muted">{{ optional($suggestion->destinationRole)->name ? ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)) : 'Staff' }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <small class="text-muted">
                        {{ $suggestion->class->name ?? '-' }} /
                        {{ $suggestion->subject->name ?? '-' }}
                      </small>
                    </td>
                    <td>
                      <div class="text-truncate" style="max-width: 200px;">
                        {{ $suggestion->content }}
                      </div>
                    </td>
                    <td>
                      @if($suggestion->reply)
                        <span class="badge bg-success">Replied</span>
                      @else
                        <span class="badge bg-label-warning">Pending</span>
                      @endif
                    </td>
                    <td>
                      <small class="text-muted">{{ $suggestion->created_at->format('M d, Y') }}</small>
                    </td>
                    <td>
                      <div class="d-flex">
                        <button type="button" class="btn btn-sm btn-icon view-message"
                                data-message="{{ $suggestion->content }}"
                                data-reply="{{ $suggestion->reply ?? '' }}"
                                data-replier="{{ $suggestion->replier->username ?? ''}}"
                                data-bs-toggle="tooltip" title="View Details">
                          <i class="bx bx-show"></i>
                        </button>
                        <form method="POST" action="{{ route('suggestions.destroy', $suggestion->id) }}" class="d-inline ms-1">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-icon" onclick="return confirm('Are you sure you want to delete this suggestion?')" data-bs-toggle="tooltip" title="Delete">
                            <i class="bx bx-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <!-- Pagination Links -->
          <div class="d-flex justify-content-center mt-3">
            {{ $allSuggestions->links('pagination::bootstrap-5') }}
          </div>
        @else
          <div class="alert alert-info">No suggestions found in the system.</div>
        @endif
      </div>
    </div>
    @endif

    <!-- Tabs for different suggestion types -->
    <div class="nav-align-top mb-4">
      <ul class="nav nav-tabs flex-nowrap overflow-auto" role="tablist" style="overflow-y: hidden; -webkit-overflow-scrolling: touch;">
        <li class="nav-item flex-shrink-0">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#sent-suggestions">
            <span class="d-none d-md-inline">Sent Suggestions</span>
            <span class="d-inline d-md-none">Sent</span>
          </button>
        </li>
        <li class="nav-item flex-shrink-0">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#assigned-suggestions">
            <span class="d-none d-md-inline">Assigned to You</span>
            <span class="d-inline d-md-none">Assigned</span>
          </button>
        </li>
        <li class="nav-item flex-shrink-0">
          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#replies">
            <span class="d-none d-md-inline">Replies Received</span>
            <span class="d-inline d-md-none">Replies</span>
          </button>
        </li>
      </ul>
      <div class="tab-content">
        <!-- Sent Suggestions Tab -->
        <div class="tab-pane fade show active" id="sent-suggestions" role="tabpanel">
          @if($sentSuggestions->isEmpty())
            <div class="text-center py-4">
              <i class="bx bx-message-rounded bx-lg text-muted mb-3"></i>
              <p class="text-muted">No sent suggestions found.</p>
            </div>
          @else
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>To</th>
                    <th>Class/Subject</th>
                    <th>Content Preview</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($sentSuggestions as $suggestion)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded-circle bg-label-primary">
                              <i class="bx bx-user"></i>
                            </span>
                          </div>
                          <div>
                            <div class="fw-semibold">{{ $suggestion->replier->username ?? 'Staff' }}</div>
                            <small class="text-muted">{{ optional($suggestion->destinationRole)->name ? ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)) : 'Staff' }}</small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <small class="text-muted">
                          {{ $suggestion->class->name ?? '-' }} /
                          {{ $suggestion->subject->name ?? '-' }}
                        </small>
                      </td>
                      <td>
                        <div class="text-truncate" style="max-width: 200px;">
                          {{ $suggestion->content }}
                        </div>
                      </td>
                      <td>
                        @if($suggestion->reply)
                          <span class="badge bg-success">Replied</span>
                        @elseif($suggestion->status === 'seen')
                          <span class="badge bg-label-info">Seen</span>
                        @else
                          <span class="badge bg-label-warning">Unseen</span>
                        @endif
                      </td>
                      <td>
                        <small class="text-muted">{{ $suggestion->created_at->format('M d, Y') }}</small>
                      </td>
                      <td>
                        <div class="d-flex">
                          <button type="button" class="btn btn-sm btn-icon view-message"
                                  data-message="{{ $suggestion->content }}"
                                  data-reply="{{ $suggestion->reply ?? '' }}"
                                  data-replier="{{ $suggestion->replier->username ?? '' }}"
                                  data-bs-toggle="tooltip" title="View Details">
                            <i class="bx bx-show"></i>
                          </button>
                          @if(!in_array($userRoleName, ['Teacher', 'ClassTeacher']))
                            <form method="POST" action="{{ route('suggestions.destroy', $suggestion->id) }}" class="d-inline ms-1">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-sm btn-icon" onclick="return confirm('Are you sure you want to delete this suggestion?')" data-bs-toggle="tooltip" title="Delete">
                                <i class="bx bx-trash"></i>
                              </button>
                            </form>
                          @endif
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>

        <!-- Assigned Suggestions Tab -->
        <div class="tab-pane fade" id="assigned-suggestions" role="tabpanel">
          @if($assignedSuggestions->isEmpty())
            <div class="text-center py-4">
              <i class="bx bx-check-circle bx-lg text-muted mb-3"></i>
              <p class="text-muted">No suggestions have been assigned to you.</p>
            </div>
          @else
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>From</th>
                    <th>Class/Subject</th>
                    <th>Content Preview</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($assignedSuggestions as $suggestion)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded-circle bg-label-info">
                              <i class="bx bx-user"></i>
                            </span>
                          </div>
                          <div>
                            <div class="fw-semibold">
                              {{ $suggestion->sender->first_name ?? $suggestion->sender->username ?? '-' }}
                              {{ $suggestion->sender->last_name ?? '' }}
                            </div>
                            <small class="text-muted">
                              @if($suggestion->sender && $suggestion->sender->role_name)
                                {{ ucfirst(str_replace('_', ' ', $suggestion->sender->role_name->name)) }}
                              @else
                                -
                              @endif
                            </small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <small class="text-muted">
                          {{ $suggestion->class->name ?? '-' }} /
                          {{ $suggestion->subject->name ?? '-' }}
                        </small>
                      </td>
                      <td>
                        <div class="text-truncate" style="max-width: 200px;">
                          {{ $suggestion->content }}
                        </div>
                      </td>
                      <td>
                        @if($suggestion->reply)
                          <span class="badge bg-success">Replied</span>
                        @elseif($suggestion->status === 'unseen')
                          <span class="badge bg-label-warning">Unseen</span>
                        @else
                          <span class="badge bg-label-info">Seen</span>
                        @endif
                      </td>
                      <td>
                        <small class="text-muted">{{ $suggestion->created_at->format('M d, Y') }}</small>
                      </td>
                      <td>
                        <div class="d-flex">
                          <button type="button" class="btn btn-sm btn-icon view-message"
                                  data-message="{{ $suggestion->content }}"
                                  data-reply="{{ $suggestion->reply ?? '' }}"
                                  data-replier="{{ $suggestion->replier->username ?? '' }}"
                                  data-bs-toggle="tooltip" title="View Details">
                            <i class="bx bx-show"></i>
                          </button>

                          @if($suggestion->status === 'unseen' && !$suggestion->reply)
                            <form method="POST" action="{{ route('suggestions.markSeen', $suggestion->id) }}" class="d-inline ms-1">
                              @csrf
                              <button type="submit" class="btn btn-sm btn-icon" data-bs-toggle="tooltip" title="Mark as Seen">
                                <i class="bx bx-check"></i>
                              </button>
                            </form>
                          @endif

                          @if(in_array($userRoleName, ['Teacher', 'ClassTeacher', 'Headmaster', 'Academic']) &&
                              $suggestion->sender && !$suggestion->reply)
                            <button type="button" class="btn btn-sm btn-icon ms-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#replyModal{{ $suggestion->id }}"
                                    data-bs-toggle="tooltip" title="Reply">
                              <i class="bx bx-reply"></i>
                            </button>
                          @endif
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>

        <!-- Replies Tab -->
        <div class="tab-pane fade" id="replies" role="tabpanel">
          @if($replies->isEmpty())
            <div class="text-center py-4">
              <i class="bx bx-message-check bx-lg text-muted mb-3"></i>
              <p class="text-muted">No replies received yet.</p>
            </div>
          @else
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>From</th>
                    <th>Class/Subject</th>
                    <th>Your Message</th>
                    <th>Reply</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($replies as $suggestion)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded-circle bg-label-success">
                              <i class="bx bx-user"></i>
                            </span>
                          </div>
                          <div>
                            <div class="fw-semibold">
                              {{ $suggestion->replier->first_name ?? $suggestion->replier->username ?? 'Staff' }}
                              {{ $suggestion->replier->last_name ?? '' }}
                            </div>
                            <small class="text-muted">{{ optional($suggestion->destinationRole)->name ? ucfirst(str_replace('_', ' ', $suggestion->destinationRole->name)) : 'Staff' }}</small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <small class="text-muted">
                          {{ $suggestion->class->name ?? '-' }} /
                          {{ $suggestion->subject->name ?? '-' }}
                        </small>
                      </td>
                      <td>
                        <div class="text-truncate" style="max-width: 150px;">
                          {{ $suggestion->content }}
                        </div>
                      </td>
                      <td>
                        <div class="text-truncate" style="max-width: 150px;">
                          {{ $suggestion->reply }}
                        </div>
                      </td>
                      <td>
                        <small class="text-muted">{{ $suggestion->updated_at->format('M d, Y') }}</small>
                      </td>
                      <td>
                        <div class="d-flex">
                          <button type="button" class="btn btn-sm btn-icon view-message"
                                  data-message="{{ $suggestion->content }}"
                                  data-reply="{{ $suggestion->reply ?? '' }}"
                                  data-replier="{{ $suggestion->replier->username ?? '' }}"
                                  data-bs-toggle="tooltip" title="View Conversation">
                            <i class="bx bx-conversation"></i>
                          </button>

                          @if(!in_array($userRoleName, ['Teacher', 'ClassTeacher']) && $suggestion->sender_id == session('user_id'))
                            <form method="POST" action="{{ route('suggestions.destroy', $suggestion->id) }}" class="d-inline ms-1">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-sm btn-icon" onclick="return confirm('Are you sure you want to delete this suggestion?')" data-bs-toggle="tooltip" title="Delete">
                                <i class="bx bx-trash"></i>
                              </button>
                            </form>
                          @endif
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Message View Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Suggestion Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-4">
          <h6 class="text-primary mb-2">Original Message:</h6>
          <div class="border p-3 rounded bg-light" id="modal-message-content"></div>
        </div>
        <div id="reply-section" class="d-none">
          <h6 class="text-success mb-2">Reply:</h6>
          <div class="border p-3 rounded bg-light" id="modal-reply-content"></div>
          <small class="text-muted mt-1 d-block" id="modal-replier-info"></small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Reply Modals -->
@foreach($assignedSuggestions as $suggestion)
  @if(in_array($userRoleName, ['Teacher', 'ClassTeacher', 'Headmaster', 'Academic']) &&
      $suggestion->sender && !$suggestion->reply)
    <div class="modal fade" id="replyModal{{ $suggestion->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Reply to {{ $suggestion->sender->first_name ?? $suggestion->sender->username }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('suggestions.reply', $suggestion->id) }}">
              @csrf
              <div class="mb-3">
                <label class="form-label">Student's Message:</label>
                <p class="border p-2 rounded bg-light">{{ $suggestion->content }}</p>
              </div>
              <div class="mb-3">
                <label for="reply_content{{ $suggestion->id }}" class="form-label">Your Reply:</label>
                <textarea name="reply_content" id="reply_content{{ $suggestion->id }}" class="form-control" rows="4" required
                          placeholder="Type your reply to the student here..."></textarea>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Send Reply</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  @endif
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
  const roleSelect = document.getElementById('destination_role');
  const classWrapper = document.getElementById('class-select-wrapper');
  const subjectWrapper = document.getElementById('subject-select-wrapper');
  const classSelect = document.getElementById('class_id');
  const subjectSelect = document.getElementById('subject_id');

  function toggleFields() {
    const val = roleSelect.value.toLowerCase();

    // Show class select for ClassTeacher and Teacher
    if (val === 'classteacher' || val === 'teacher') {
      classWrapper.style.display = 'block';
    } else {
      classWrapper.style.display = 'none';
      classSelect.value = '';
    }

    // Show subject select only for Teacher
    if (val === 'teacher') {
      subjectWrapper.style.display = 'block';
      // If class is already selected, load subjects
      if (classSelect.value) {
        loadSubjects(classSelect.value);
      }
    } else {
      subjectWrapper.style.display = 'none';
      subjectSelect.innerHTML = '<option value="">Select Subject</option>';
    }
  }

  // Function to load subjects via AJAX
  function loadSubjects(classId) {
    if (!classId) return;

    fetch(`/get-subjects-by-class/${classId}`)
      .then(response => response.json())
      .then(data => {
        subjectSelect.innerHTML = '<option value="">Select Subject</option>';
        data.forEach(subject => {
          const option = document.createElement('option');
          option.value = subject.id;
          option.textContent = subject.name;
          subjectSelect.appendChild(option);
        });

        // Preselect old value if exists
        const oldValue = "{{ old('subject_id') }}";
        if (oldValue) {
          subjectSelect.value = oldValue;
        }
      })
      .catch(error => {
        console.error('Error loading subjects:', error);
        subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
      });
  }

  roleSelect.addEventListener('change', toggleFields);

  // Load subjects when class changes (for Teacher role)
  classSelect.addEventListener('change', function() {
    if (roleSelect.value.toLowerCase() === 'teacher') {
      loadSubjects(this.value);
    }
  });

  // Initial run on page load to handle old input or edit case
  toggleFields();

  // If class and teacher role are already selected on page load, load subjects
  const initialClassId = classSelect.value;
  const initialRole = roleSelect.value.toLowerCase();
  if (initialClassId && initialRole === 'teacher') {
    loadSubjects(initialClassId);
  }

  // Message view modal functionality
  const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
  const viewMessageButtons = document.querySelectorAll('.view-message');

  viewMessageButtons.forEach(button => {
    button.addEventListener('click', function() {
      const message = this.getAttribute('data-message');
      const reply = this.getAttribute('data-reply');
      const replier = this.getAttribute('data-replier');

      document.getElementById('modal-message-content').textContent = message;

      if (reply) {
        document.getElementById('reply-section').classList.remove('d-none');
        document.getElementById('modal-reply-content').textContent = reply;
        document.getElementById('modal-replier-info').textContent = 'Replied by: ' + (replier || 'Staff');
      } else {
        document.getElementById('reply-section').classList.add('d-none');
      }

      messageModal.show();
    });
  });

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Initialize tabs if there are any
  const triggerTabList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tab"]'));
  triggerTabList.forEach(function (triggerEl) {
    new bootstrap.Tab(triggerEl);
  });
});
</script>
@endsection