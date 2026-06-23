@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Students'
])
@section('title', 'Manage Students')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Header and Add Student Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Students</h4>
      @if($isAdmin)
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
          <i class="bx bx-plus"></i> Add New Student
        </button>
      </div>
      @endif
    </div>

    <!-- Search Form -->
    <form method="GET" action="{{ route('teachers.students.index') }}" class="mb-4">
      <div class="row g-3">
        <div class="col-md-4">
          <input type="text" name="search" id="liveSearch" class="form-control" placeholder="Search by name, email or phone (live search)" value="{{ request(\'search\') }}" autocomplete="off">
        </div>
        @if($isAdmin)
        <div class="col-md-2">
          <select class="form-select" name="class_id">
            <option value="">All Classes</option>
            @foreach($classes as $class)
            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
              {{ $class->name }}
            </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select" name="gender">
            <option value="">All Genders</option>
            <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
            <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
          </select>
        </div>
        @endif
        <div class="col-md-2">
          <button class="btn btn-primary w-100" type="submit"><i class="bx bx-search"></i> Search</button>
        </div>
      </div>
    </form>

    <!-- Excel Operations -->
    @if($isAdmin)
    <form method="POST" action="{{ route('teachers.students.import') }}" enctype="multipart/form-data" class="mb-4">
      @csrf
      <div class="row g-3 align-items-center">
        <div class="col-auto">
          <label class="form-label mb-0 fw-bold">Excel Operations</label>
        </div>
        <div class="col-md-4">
          <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-success">
            <i class="bx bx-upload"></i> Import
          </button>
        </div>
        <div class="col-auto">
          <a href="{{ route('teachers.students.template') }}" class="btn btn-outline-secondary">
            <i class="bx bx-download"></i> Download Template
          </a>
        </div>
      </div>
    </form>
    @endif

    <!-- Alerts -->
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
    @endif    @if(session('import_status'))
      <div class="alert alert-{{ session('import_errors') ? 'warning' : 'info' }} alert-dismissible fade show" role="alert">
        <strong>Import Status:</strong> {{ session('import_status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif



    @if(session('import_errors'))
      <div class="alert alert-danger">
        <strong>Import errors:</strong>
        <ul>
          @foreach (session('import_errors') as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
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

    <!-- Students Table -->
    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Registration</th>
              <th>Gender</th>
              <th>Class</th>
              <th>Stream</th>
              <th>Email</th>
              <th>Phone</th>
              @if($isClassTeacher)
              <th>Comments</th>
              @endif
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($students as $student)
              <tr>
                <td>{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                <td>{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</td>
                <td>{{ $student->registration_no }}</td>
                <td>{{ $student->gender }}</td>
                <td>{{ $student->class->name ?? 'N/A' }}</td>
                <td>{{ $student->stream->name ?? 'N/A' }}</td>
                <td>{{ $student->email ?? '-' }}</td>
                <td>{{ $student->phone_number }}</td>
                @if($isClassTeacher)
                <td>
                  <a href="#" class="comment-icon"
                     data-bs-toggle="modal"
                     data-bs-target="#commentModal"
                     data-student-id="{{ $student->id }}"
                     data-current-comment="{{ e($student->comments ?? '') }}">
                    <i class="bx {{ $student->comment ? 'bx-comment-text text-primary' : 'bx-comment' }}"></i>
                  </a>
                </td>

                @endif
                <td>
                  <button class="btn btn-sm btn-outline-warning me-1 edit-student-btn" data-bs-toggle="modal" data-bs-target="#editStudentModal" data-id="{{ $student->id }}" data-first_name="{{ $student->first_name }}" data-middle_name="{{ $student->middle_name }}" data-last_name="{{ $student->last_name }}" data-gender="{{ $student->gender }}" data-date_of_birth="{{ $student->date_of_birth }}" data-phone_number="{{ $student->phone_number }}" data-email="{{ $student->email }}" data-address="{{ $student->address }}" data-class_id="{{ $student->class_id }}" data-stream_id="{{ $student->stream_id }}">
                    <i class="bx bx-edit"></i>
                  </button>

                  @if($isAdmin || $package !== 'core')
                  <form action="{{ route('teachers.students.delete', $student->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure to delete this student?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="10" class="text-center">No students found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-3 mx-3">
        {{ $students->withQueryString()->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

<!-- Add Student Modal -->
@if($isAdmin)
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('teachers.students.store') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <!-- Personal Information -->
            <div class="col-md-4">
              <label class="form-label">First Name</label>
              <input type="text" class="form-control" name="first_name" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Middle Name</label>
              <input type="text" class="form-control" name="middle_name">
            </div>
            <div class="col-md-4">
              <label class="form-label">Last Name</label>
              <input type="text" class="form-control" name="last_name" required>
            </div>

            <!-- Contact Information -->
            <div class="col-md-3">
              <label class="form-label">Gender</label>
              <select class="form-select" name="gender" required>
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Date of Birth</label>
              <input type="date" class="form-control" name="date_of_birth">
            </div>
            <div class="col-md-3">
              <label class="form-label">Phone Number</label>
              <input type="text" class="form-control" name="phone_number">
            </div>

            <!-- Account Information -->
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Address</label>
              <input type="text" class="form-control" name="address">
            </div>

            <!-- Academic Information -->
            <div class="col-md-6">
              <label class="form-label">Class</label>
              <select class="form-select" name="class_id" required>
                <option value="">Select Class</option>
                @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Stream</label>
              <select class="form-select" name="stream_id" id="add_stream_id">
                <option value="">Select Stream</option>
                @foreach($streams as $stream)
                <option value="{{ $stream->id }}" data-class-id="{{ $stream->class_id }}">{{ $stream->name }}</option>
                @endforeach
              </select>
            </div>

            <!-- Password Fields (Moved Below Class & Stream) -->
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" class="form-control" name="password" id="password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm Password</label>
              <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" required>
              <div id="password-match" class="invalid-feedback">Passwords do not match</div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="submit-btn">Add Student</button>
        </div>
      </div>
    </form>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
  const password = document.getElementById('password');
  const passwordConfirmation = document.getElementById('password_confirmation');
  const passwordMatch = document.getElementById('password-match');
  const submitBtn = document.getElementById('submit-btn');

  function validatePassword() {
    if (password.value !== passwordConfirmation.value) {
      passwordConfirmation.classList.add('is-invalid');
      passwordMatch.style.display = 'block';
      submitBtn.disabled = true;
    } else {
      passwordConfirmation.classList.remove('is-invalid');
      passwordMatch.style.display = 'none';
      submitBtn.disabled = false;
    }
  }

  passwordConfirmation.addEventListener('input', validatePassword);
  password.addEventListener('input', validatePassword);
  // Filter streams by class for Add Student Modal
  const addClassSelect = document.querySelector('#addStudentModal select[name="class_id"]');
  const addStreamSelect = document.getElementById('add_stream_id');

  if (addClassSelect && addStreamSelect) {
    addClassSelect.addEventListener('change', function() {
      const selectedClassId = this.value;
      const streamOptions = addStreamSelect.querySelectorAll('option');

      streamOptions.forEach(option => {
        if (option.value === '') {
          option.style.display = 'block';
          return;
        }

        const streamClassId = option.getAttribute('data-class-id');
        if (!selectedClassId || streamClassId === selectedClassId) {
          option.style.display = 'block';
        } else {
          option.style.display = 'none';
        }
      });

      // Reset stream selection if current selection is not valid
      const currentStreamOption = addStreamSelect.querySelector(`option[value="${addStreamSelect.value}"]`);
      if (currentStreamOption && currentStreamOption.style.display === 'none') {
        addStreamSelect.value = '';
      }
    });
  }
});
</script>

@endif

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" id="editStudentForm" action="">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">First Name</label>
              <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Middle Name</label>
              <input type="text" class="form-control" name="middle_name" id="edit_middle_name">
            </div>
            <div class="col-md-4">
              <label class="form-label">Last Name</label>
              <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Gender</label>
              <select class="form-select" name="gender" id="edit_gender" required>
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Date of Birth</label>
              <input type="date" class="form-control" name="date_of_birth" id="edit_date_of_birth">
            </div>
            <div class="col-md-3">
              <label class="form-label">Phone Number</label>
              <input type="text" class="form-control" name="phone_number" id="edit_phone_number">
            </div>

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" id="edit_email">
            </div>
            <div class="col-md-6">
              <label class="form-label">Address</label>
              <input type="text" class="form-control" name="address" id="edit_address">
            </div>

            <div class="col-md-6">
              <label class="form-label">Class</label>
              <select class="form-select" name="class_id" id="edit_class_id" required>
                <option value="">Select Class</option>
                @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Stream</label>
              <select class="form-select" name="stream_id" id="edit_stream_id">
                <option value="">Select Stream</option>
                @foreach($streams as $stream)
                <option value="{{ $stream->id }}" data-class-id="{{ $stream->class_id }}">{{ $stream->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Student</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Comment Modal -->
@if($isClassTeacher)
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="commentModalLabel">Student Comments</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="commentDisplay" class="mb-3 p-2 border rounded">
          No comments yet
        </div>

        <form id="commentForm" method="POST" style="display: none;">
          @csrf
          <input type="hidden" name="student_id" id="modalStudentId">
          <div class="mb-3">
            <textarea class="form-control" id="studentComment" name="comment" rows="5"></textarea>
          </div>

           @if($isCommentEditable)
          <div class="text-end">
            <button type="button" class="btn btn-outline-secondary" id="cancelEdit">Cancel</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
          </div>
          @endif
        </form>
        @if($isCommentEditable)
        <button type="button" class="btn btn-primary" id="editCommentBtn">Edit Comment</button>
        @endif
      </div>
    </div>
  </div>
</div>
@endif

<script>
  // Filter streams by class for Edit Student Modal
  function filterEditStreams(classId) {
    const editStreamSelect = document.getElementById('edit_stream_id');
    if (!editStreamSelect) return;

    const streamOptions = editStreamSelect.querySelectorAll('option');
    streamOptions.forEach(option => {
      if (option.value === '') {
        option.style.display = 'block';
        return;
      }

      const streamClassId = option.getAttribute('data-class-id');
      if (!classId || streamClassId === classId) {
        option.style.display = 'block';
      } else {
        option.style.display = 'none';
      }
    });
  }

  // Listen to class changes in edit modal
  document.addEventListener('DOMContentLoaded', function() {
    const editClassSelect = document.getElementById('edit_class_id');
    if (editClassSelect) {
      editClassSelect.addEventListener('change', function() {
        filterEditStreams(this.value);

        // Reset stream selection if current selection is not valid
        const editStreamSelect = document.getElementById('edit_stream_id');
        const currentStreamOption = editStreamSelect.querySelector(`option[value="${editStreamSelect.value}"]`);
        if (currentStreamOption && currentStreamOption.style.display === 'none') {
          editStreamSelect.value = '';
        }
      });
    }
  });

  // Populate Edit Modal with student data
  document.querySelectorAll('.edit-student-btn').forEach(button => {
    button.addEventListener('click', () => {
      const form = document.getElementById('editStudentForm');
      const id = button.dataset.id;

      form.action =  `/teachers/students/update/${id}`;

      // Set all input values
      document.getElementById('edit_first_name').value = button.dataset.first_name || '';
      document.getElementById('edit_middle_name').value = button.dataset.middle_name || '';
      document.getElementById('edit_last_name').value = button.dataset.last_name || '';
      document.getElementById('edit_gender').value = button.dataset.gender || '';
      document.getElementById('edit_date_of_birth').value = button.dataset.date_of_birth || '';
      document.getElementById('edit_phone_number').value = button.dataset.phone_number || '';
      document.getElementById('edit_email').value = button.dataset.email || '';
      document.getElementById('edit_address').value = button.dataset.address || '';
      document.getElementById('edit_class_id').value = button.dataset.class_id || '';
      document.getElementById('edit_stream_id').value = button.dataset.stream_id || '';

      // Filter streams based on selected class
      filterEditStreams(button.dataset.class_id);
    });
  });

  // Comment Modal Handling
  @if($isClassTeacher)
  document.addEventListener('DOMContentLoaded', function() {
    // Handle comment icon click
    document.querySelectorAll('.comment-icon').forEach(icon => {
      icon.addEventListener('click', function() {
        const studentId = this.getAttribute('data-student-id');
        const currentComment = this.getAttribute('data-current-comment');

        document.getElementById('modalStudentId').value = studentId;
        document.getElementById('studentComment').value = currentComment;
        document.getElementById('commentDisplay').innerHTML = currentComment
          ? nl2br(escapeHtml(currentComment))
          : 'No comments yet';

        document.getElementById('commentForm').action = `students/${studentId}/update-comment`;
      });
    });

    // Handle comment form submit via AJAX
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
      commentForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(commentForm);
        const studentId = document.getElementById('modalStudentId').value;
        const actionUrl = commentForm.action;

        fetch(actionUrl, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          },
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // Update modal display
            document.getElementById('commentDisplay').innerHTML = data.comment
              ? nl2br(escapeHtml(data.comment))
              : 'No comments yet';

            // Reset back to view mode
            document.getElementById('commentDisplay').style.display = 'block';
            commentForm.style.display = 'none';
            document.getElementById('editCommentBtn').style.display = 'block';

            // Update the table icon immediately
            const rowIcon = document.querySelector(
              `.comment-icon[data-student-id="${studentId}"] i`
            );
          }
        })
        .catch(err => {
          console.error('Error saving comment:', err);
          alert('Failed to save comment.');
        });
      });
    }

    // Toggle between view and edit modes
    document.getElementById('editCommentBtn')?.addEventListener('click', function() {
      document.getElementById('commentDisplay').style.display = 'none';
      document.getElementById('commentForm').style.display = 'block';
      this.style.display = 'none';
    });

    document.getElementById('cancelEdit')?.addEventListener('click', function() {
      document.getElementById('commentDisplay').style.display = 'block';
      document.getElementById('commentForm').style.display = 'none';
      document.getElementById('editCommentBtn').style.display = 'block';
    });

    // Helper functions
    function escapeHtml(unsafe) {
      return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function nl2br(str) {
      return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
    }
  });
  @endif

  // Auto-show add modal if errors occurred on add
  @if($errors->any() && session('form') === 'create')
    new bootstrap.Modal(document.getElementById('addStudentModal')).show();
  @endif

  // Auto-show edit modal if errors occurred on edit
  @if($errors->any() && session('form') === 'edit' && session('student_id'))
    const editForm = document.getElementById('editStudentForm');
    editForm.action = `/students/{{ session('student_id') }}`;
    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
  @endif
</script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const commentModal = document.getElementById("commentModal");

    if (commentModal) {
      commentModal.addEventListener("hidden.bs.modal", function () {
        // Reload page when modal is closed
        location.reload();
      });
    }
  });
    // Live search functionality
    const liveSearchInput = document.getElementById('liveSearch');
    const studentsTable = document.querySelector('.table tbody');

    if (liveSearchInput && studentsTable) {
      liveSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const rows = studentsTable.querySelectorAll('tr');

        rows.forEach(row => {
          // Skip the "No students found" row
          if (row.cells.length < 2) {
            return;
          }

          const name = row.cells[1]?.textContent.toLowerCase() || '';
          const registration = row.cells[2]?.textContent.toLowerCase() || '';
          const email = row.cells[6]?.textContent.toLowerCase() || '';
          const phone = row.cells[7]?.textContent.toLowerCase() || '';

          const matchesSearch = name.includes(searchTerm) ||
                                registration.includes(searchTerm) ||
                                email.includes(searchTerm) ||
                                phone.includes(searchTerm);

          row.style.display = matchesSearch ? '' : 'none';
        });

        // Show/hide "No students found" message
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none' && row.cells.length > 2);
        const noResultsRow = studentsTable.querySelector('tr td[colspan]');

        if (visibleRows.length === 0 && searchTerm !== '') {
          if (!noResultsRow) {
            const newRow = document.createElement('tr');
            newRow.innerHTML = '<td colspan="10" class="text-center">No matching students found.</td>';
            newRow.id = 'noResultsRow';
            studentsTable.appendChild(newRow);
          }
        } else {
          const tempNoResults = document.getElementById('noResultsRow');
          if (tempNoResults) {
            tempNoResults.remove();
          }
        }
      });
    }
</script>


<!-- Success Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-primary text-white">
      <strong class="me-auto">Success</strong>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
      Operation completed successfully!
    </div>
  </div>
</div>

@endsection