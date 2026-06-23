@extends('layouts/contentNavbarLayout')

@section('title', 'Staff Management')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">School Staff</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
          <i class='bx bx-plus'></i> Add Staff Member
        </button>
      </div>
      <div class="card-body">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
          {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <p class="text-muted">View and manage school staff members</p>

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Username</th>
                <th>Registration No</th>
                <th>Role</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($staff as $member)
              <tr>
                <td><strong>{{ $member->username }}</strong></td>
                <td>{{ $member->registration_no }}</td>
                <td>
                  <span class="badge bg-label-primary">
                    {{ $member->role_name ?? 'Staff' }}
                  </span>
                </td>
                <td>{{ $member->email ?? 'N/A' }}</td>
                <td>{{ $member->phone_number ?? 'N/A' }}</td>
                <td>
                  <span class="badge bg-{{ $member->status === 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($member->status ?? 'Active') }}
                  </span>
                </td>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                      Actions
                    </button>
                    <ul class="dropdown-menu">
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="viewStaff({{ json_encode($member) }})">
                          <i class='bx bx-show'></i> View Details
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="editStaff({{ json_encode($member) }})">
                          <i class='bx bx-edit'></i> Edit
                        </a>
                      </li>
                      <li>
                        <form action="{{ route('school_admin.staff.toggle_status', $member->id) }}" method="POST" style="display: inline;">
                          @csrf
                          <button type="submit" class="dropdown-item">
                            <i class='bx bx-{{ $member->status === 'active' ? 'block' : 'check' }}'></i>
                            {{ $member->status === 'active' ? 'Deactivate' : 'Activate' }}
                          </button>
                        </form>
                      </li>
                      <li>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="resetPassword({{ $member->id }}, '{{ $member->username }}')">
                          <i class='bx bx-lock-open'></i> Reset Password
                        </a>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteStaff({{ $member->id }}, '{{ $member->username }}')">
                          <i class='bx bx-trash'></i> Delete
                        </a>
                      </li>
                    </ul>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center">No staff members found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Staff Member</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('school_admin.staff.create') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="registration_no" class="form-label">Registration No <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="registration_no" name="registration_no" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="col-md-6 mb-3">
              <label for="phone_number" class="form-label">Phone Number</label>
              <input type="text" class="form-control" id="phone_number" name="phone_number">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
              <select class="form-select" id="role_id" name="role_id" required>
                <option value="">Select Role</option>
                @foreach($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="gender" class="form-label">Gender</label>
              <select class="form-select" id="gender" name="gender">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="password" name="password" required minlength="6">
            </div>
            <div class="col-md-6 mb-3">
              <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Staff Member</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Staff Modal -->
<div class="modal fade" id="viewStaffModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Staff Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table table-borderless">
          <tr>
            <td><strong>Username:</strong></td>
            <td id="view_username"></td>
          </tr>
          <tr>
            <td><strong>Registration No:</strong></td>
            <td id="view_registration_no"></td>
          </tr>
          <tr>
            <td><strong>Role:</strong></td>
            <td id="view_role"></td>
          </tr>
          <tr>
            <td><strong>Email:</strong></td>
            <td id="view_email"></td>
          </tr>
          <tr>
            <td><strong>Phone:</strong></td>
            <td id="view_phone"></td>
          </tr>
          <tr>
            <td><strong>Gender:</strong></td>
            <td id="view_gender"></td>
          </tr>
          <tr>
            <td><strong>Status:</strong></td>
            <td id="view_status"></td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Staff Member</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editStaffForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_username" class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit_username" name="username" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_registration_no" class="form-label">Registration No <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="edit_registration_no" name="registration_no" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="edit_email" name="email">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_phone_number" class="form-label">Phone Number</label>
              <input type="text" class="form-control" id="edit_phone_number" name="phone_number">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit_role_id" class="form-label">Role <span class="text-danger">*</span></label>
              <select class="form-select" id="edit_role_id" name="role_id" required>
                @foreach($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit_gender" class="form-label">Gender</label>
              <select class="form-select" id="edit_gender" name="gender">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Staff Member</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reset Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="resetPasswordForm" method="POST">
        @csrf
        <div class="modal-body">
          <p>Reset password for <strong id="reset_username"></strong></p>
          <div class="mb-3">
            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
          </div>
          <div class="mb-3">
            <label for="new_password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Staff Modal -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="deleteStaffForm" method="POST">
        @csrf
        @method('DELETE')
        <div class="modal-body">
          <p>Are you sure you want to delete staff member <strong id="delete_username"></strong>?</p>
          <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete Staff Member</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function viewStaff(staff) {
  document.getElementById('view_username').textContent = staff.username;
  document.getElementById('view_registration_no').textContent = staff.registration_no;
  document.getElementById('view_role').innerHTML = '<span class="badge bg-label-primary">' + (staff.role_name || 'Staff') + '</span>';
  document.getElementById('view_email').textContent = staff.email || 'N/A';
  document.getElementById('view_phone').textContent = staff.phone_number || 'N/A';
  document.getElementById('view_gender').textContent = staff.gender || 'N/A';
  document.getElementById('view_status').innerHTML = '<span class="badge bg-' + (staff.status === 'active' ? 'success' : 'secondary') + '">' + (staff.status ? staff.status.charAt(0).toUpperCase() + staff.status.slice(1) : 'Active') + '</span>';

  var modal = new bootstrap.Modal(document.getElementById('viewStaffModal'));
  modal.show();
}

function editStaff(staff) {
  document.getElementById('edit_username').value = staff.username;
  document.getElementById('edit_registration_no').value = staff.registration_no;
  document.getElementById('edit_email').value = staff.email || '';
  document.getElementById('edit_phone_number').value = staff.phone_number || '';
  document.getElementById('edit_role_id').value = staff.role_id;
  document.getElementById('edit_gender').value = staff.gender || 'Male';

  document.getElementById('editStaffForm').action = '{{ route('school_admin.staff.index') }}/' + staff.id;

  var modal = new bootstrap.Modal(document.getElementById('editStaffModal'));
  modal.show();
}

function resetPassword(staffId, username) {
  document.getElementById('reset_username').textContent = username;
  document.getElementById('resetPasswordForm').action = '{{ route('school_admin.staff.index') }}/' + staffId + '/reset-password';

  var modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
  modal.show();
}

function deleteStaff(staffId, username) {
  document.getElementById('delete_username').textContent = username;
  document.getElementById('deleteStaffForm').action = '{{ route('school_admin.staff.index') }}/' + staffId;

  var modal = new bootstrap.Modal(document.getElementById('deleteStaffModal'));
  modal.show();
}
</script>

@endsection
