@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Staff'
])
@section('title', 'Manage Staff')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Staff</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
        <i class="bx bx-plus"></i> Add New Staff
      </button>
    </div>

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

    <form method="GET" action="{{ route('staffs.view') }}" class="row g-3 mb-4">
      <div class="col-md-4">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name, email, or phone">
      </div>

      <div class="col-md-3">
        <select name="role_id" class="form-select">
          <option value="">All Roles</option>
          @foreach($roles as $r)
            <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>
              {{ ucfirst($r->name) }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit">
          <i class="bx bx-search"></i> Search
        </button>
      </div>
    </form>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Gender</th>
              <th>Registration No</th>
              <th>Phone Number</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($staffs as $staff)
              <tr>
                <td>{{ $loop->iteration + ($staffs->currentPage() - 1) * $staffs->perPage() }}</td>
                <td><strong>{{ $staff->username }}</strong></td>
                <td><strong>{{ $staff->gender ?? 'N/A' }}</strong></td>
                <td>{{ $staff->registration_no }}</td>
                <td>{{ $staff->phone_number }}</td>
                <td>{{ $staff->email }}</td>
                <td>{{ ucfirst($staff->role_name->name) }}</td>
                <td>
                  <form method="POST" action="{{ route('owner.update.user.status', $staff->id) }}">
                    @csrf
                    <input type="hidden" name="toggle_status" value="1">
                    <button type="submit"
                      class="btn btn-sm border-1 {{ $staff->status === 'active' ? 'btn-outline-success' : 'btn-outline-danger' }}">
                      {{ $staff->status === 'active' ? 'Active' : 'Inactive' }}
                    </button>
                  </form>
                </td>
                <td>
                  <button
                    class="btn btn-sm btn-outline-warning me-1 edit-staff-btn"
                    data-id="{{ $staff->id }}"
                    data-name="{{ $staff->username }}"
                    data-email="{{ $staff->email }}"
                    data-phone="{{ $staff->phone_number }}"
                    data-role-id="{{ $staff->role_id }}"
                    data-class-id="{{ $staff->class_id ?? '' }}"
                    title="Edit"
                  >
                    <i class="bx bx-edit"></i>
                  </button>

                  <form action="{{ url('/owner/delete-staff/' . $staff->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this staff?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center">No staff found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 mx-3">
        {{ $staffs->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ url('/owner/add-staff') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="addStaffModalLabel">Add New Staff</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && session('form') === 'add')
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input
              type="text"
              id="name"
              name="name"
              class="form-control"
              value="{{ old('name') }}"
              required
            >
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              value="{{ old('email') }}"
              required
            >
          </div>

           <div class="mb-3">
          <label class="form-label">Gender</label>
            <select class="form-select" name="gender" required>
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
          </div>

          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input
              type="text"
              id="phone"
              name="phone"
              class="form-control"
              value="{{ old('phone') }}"
              required
            >
          </div>

          <div class="mb-3">
            <label for="role_id" class="form-label">Role</label>
            <select id="role_id" name="role_id" class="form-select" required>
              <option value="">Select Role</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                  {{ ucfirst($role->name) }}
                </option>
              @endforeach
            </select>
          </div>

          <!-- Dynamic Class Dropdown: hidden by default -->
          <div class="mb-3 d-none" id="classDropdownContainer">
            <label for="class_id" class="form-label">Assign Class</label>
            <select id="class_id" class="form-select">
              <option value="">Select Class</option>
              <!-- dynamically filled -->
            </select>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              required
            >
          </div>
          <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input
              type="password"
              id="password_confirmation"
              name="password_confirmation"
              class="form-control"
              required
            >
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Staff</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="editStaffForm" action="">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_name" class="form-label">Name</label>
            <input type="text" id="edit_name" name="name" class="form-control" required>
          </div>

           <div class="mb-3">
          <label class="form-label">Gender</label>
            <select class="form-select" name="gender" required>
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
          </div>
          <div class="mb-3">
            <label for="edit_email" class="form-label">Email Address</label>
            <input type="email" id="edit_email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="edit_phone" class="form-label">Phone Number</label>
            <input type="text" id="edit_phone" name="phone" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="edit_role_id" class="form-label">Role</label>
            <select id="edit_role_id" name="role_id" class="form-select" required>
              @foreach($roles as $role)
                <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
              @endforeach
            </select>
          </div>

          <!-- Dynamic Class Dropdown in Edit Modal -->
          <div class="mb-3 d-none" id="editClassDropdownContainer">
            <label for="edit_class_id" class="form-label">Assign Class</label>
            <select id="edit_class_id" class="form-select">
              <option value="">Select Class</option>
              <!-- dynamically filled -->
            </select>
          </div>

          <!-- Optionally add password fields for updating password here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Staff</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Utility to toggle name attribute for selects
  function toggleNameAttribute(select, shouldHaveName) {
    if (shouldHaveName) {
      select.name = 'class_id';
    } else {
      select.name = '';
    }
  }

  // Handle Add Staff Role change
  const addRoleSelect = document.getElementById('role_id');
  const addClassContainer = document.getElementById('classDropdownContainer');
  const addClassSelect = document.getElementById('class_id');

  addRoleSelect.addEventListener('change', function () {
    if (this.value === '5') { // role 5 = Class Teacher
      fetchClasses('class_id', 'classDropdownContainer').then(() => {
        toggleNameAttribute(addClassSelect, true);
      });
    } else {
      addClassContainer.classList.add('d-none');
      toggleNameAttribute(addClassSelect, false);
      addClassSelect.innerHTML = '<option value="">Select Class</option>';
    }
  });

  // Handle Edit Staff Role change
  const editRoleSelect = document.getElementById('edit_role_id');
  const editClassContainer = document.getElementById('editClassDropdownContainer');
  const editClassSelect = document.getElementById('edit_class_id');

  editRoleSelect.addEventListener('change', function () {
    if (this.value === '5') {
      fetchClasses('edit_class_id', 'editClassDropdownContainer').then(() => {
        toggleNameAttribute(editClassSelect, true);
      });
    } else {
      editClassContainer.classList.add('d-none');
      toggleNameAttribute(editClassSelect, false);
      editClassSelect.innerHTML = '<option value="">Select Class</option>';
    }
  });

  // Fetch classes from backend API and populate select options
  function fetchClasses(selectElementId, containerId, selectedId = null) {
    return fetch("{{ route('owner.get-classes') }}")
      .then(res => res.json())
      .then(classes => {
        const select = document.getElementById(selectElementId);
        const container = document.getElementById(containerId);

        select.innerHTML = '<option value="">Select Class</option>';
        classes.forEach(cls => {
          const option = document.createElement('option');
          option.value = cls.id;
          option.textContent = cls.name;
          select.appendChild(option);
        });

        container.classList.remove('d-none');

        if (selectedId) {
          select.value = selectedId;
        }
      })
      .catch(err => {
        console.error("Failed to load classes:", err);
      });
  }

  // Handle Edit buttons click to open Edit Modal with pre-filled data
  document.querySelectorAll('.edit-staff-btn').forEach(button => {
    button.addEventListener('click', function () {
      const id = this.dataset.id;
      const name = this.dataset.name;
      const gender = this.dataset.gender;
      const email = this.dataset.email;
      const phone = this.dataset.phone;
      const roleId = this.dataset.roleId;
      const assignedClassId = this.dataset.classId;

      const form = document.getElementById('editStaffForm');
      form.action = `/owner/update-staff/${id}`;

      document.getElementById('edit_name').value = name;
      document.getElementById('edit_email').value = email;
      document.getElementById('edit_phone').value = phone;

      editRoleSelect.value = roleId;

      if (roleId === '5') {
        fetchClasses('edit_class_id', 'editClassDropdownContainer', assignedClassId).then(() => {
          toggleNameAttribute(editClassSelect, true);
        });
      } else {
        editClassContainer.classList.add('d-none');
        toggleNameAttribute(editClassSelect, false);
        editClassSelect.innerHTML = '<option value="">Select Class</option>';
      }

      const editModal = new bootstrap.Modal(document.getElementById('editStaffModal'));
      editModal.show();
    });
  });

  // Show Add modal if validation errors from 'add' form submission
  @if($errors->any() && session('form') === 'add')
    const addModal = new bootstrap.Modal(document.getElementById('addStaffModal'));
    addModal.show();
  @endif
});
</script>
@endsection
