@extends('layouts/contentNavbarLayout')

@section('title', 'Password Management')

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="mb-4">Password Management</h4>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Search & Filter Users</h5>
      </div>
      <div class="card-body">
        <!-- Filters Row -->
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <label class="form-label small">User Type</label>
            <select class="form-select" id="filterUserType">
              <option value="all">All Users</option>
              <option value="admins">Admins Only</option>
              <option value="regular">Regular Users</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label small">Role</label>
            <select class="form-select" id="filterRole">
              <option value="all">All Roles</option>
              <option value="super_admin">Super Admin</option>
              <option value="system_admin">System Admin</option>
              <option value="owner">Owner</option>
              <option value="academic">Academic</option>
              <option value="teacher">Teacher</option>
              <option value="student">Student</option>
              <option value="accountant">Accountant</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label small">School</label>
            <select class="form-select" id="filterSchool">
              <option value="all">All Schools</option>
              @foreach($schools as $school)
                <option value="{{ $school->id }}">{{ $school->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label small">&nbsp;</label>
            <button type="button" class="btn btn-outline-secondary w-100" id="resetFilters">
              <i class='bx bx-reset'></i> Reset Filters
            </button>
          </div>
        </div>

        <!-- Live Search -->
        <div class="input-group">
          <span class="input-group-text"><i class='bx bx-search'></i></span>
          <input
            type="text"
            id="liveSearch"
            class="form-control"
            placeholder="Type to search by name, email, reg no, phone, or school..."
            autocomplete="off"
          >
          <button class="btn btn-outline-secondary" type="button" id="clearSearch">
            <i class='bx bx-x'></i>
          </button>
        </div>
        <small class="text-muted">
          <i class='bx bx-info-circle'></i>
          Results update automatically as you type. Showing <strong id="resultCount">0</strong> users.
        </small>
      </div>
    </div>

    <!-- Results Table -->
    <div class="card mt-3">
      <div class="card-body">
        <div id="loadingSpinner" class="text-center d-none my-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle" id="usersTable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Registration No</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>School</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="usersTableBody">
              <!-- Populated by JavaScript -->
            </tbody>
          </table>
        </div>

        <div id="noResults" class="text-center my-4 d-none">
          <i class='bx bx-search-alt-2 bx-lg text-muted'></i>
          <p class="text-muted mb-0">No users found matching your criteria</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('super_admin.passwords.reset') }}">
      @csrf
      <input type="hidden" name="user_id" id="changePasswordUserId">

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <p><strong>User:</strong> <span id="changePasswordUserName"></span></p>

          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" id="new_password" required>
          </div>

          <div class="mb-3">
            <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="new_password_confirmation" id="new_password_confirmation" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Change Password</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const liveSearch = document.getElementById('liveSearch');
  const filterUserType = document.getElementById('filterUserType');
  const filterRole = document.getElementById('filterRole');
  const filterSchool = document.getElementById('filterSchool');
  const usersTableBody = document.getElementById('usersTableBody');
  const loadingSpinner = document.getElementById('loadingSpinner');
  const noResults = document.getElementById('noResults');
  const resultCount = document.getElementById('resultCount');
  const resetFilters = document.getElementById('resetFilters');
  const clearSearch = document.getElementById('clearSearch');

  let searchTimeout = null;
  let currentFilters = {
    search: '',
    userType: 'all',
    role: 'all',
    school: 'all'
  };

  // Fetch users with current filters
  function fetchUsers() {
    loadingSpinner.classList.remove('d-none');
    noResults.classList.add('d-none');

    const params = new URLSearchParams({
      ajax: '1',
      search: currentFilters.search,
      user_type: currentFilters.userType,
      role: currentFilters.role,
      school: currentFilters.school
    });

    fetch(`{{ route('super_admin.passwords.index') }}?${params.toString()}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
      loadingSpinner.classList.add('d-none');

      if (data.users.length === 0) {
        usersTableBody.innerHTML = '';
        noResults.classList.remove('d-none');
        resultCount.textContent = '0';
      } else {
        renderUsers(data.users);
        resultCount.textContent = data.users.length;
      }
    })
    .catch(error => {
      console.error('Error:', error);
      loadingSpinner.classList.add('d-none');
      usersTableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading users</td></tr>';
    });
  }

  // Render users in table
  function renderUsers(users) {
    usersTableBody.innerHTML = users.map(user => `
      <tr>
        <td><strong>${escapeHtml(user.username)}</strong></td>
        <td>${escapeHtml(user.registration_no || 'N/A')}</td>
        <td>${escapeHtml(user.email || 'N/A')}</td>
        <td>${escapeHtml(user.phone_number || user.phone || 'N/A')}</td>
        <td>
          <span class="badge bg-label-${getRoleBadgeColor(user.role)}">
            ${escapeHtml(formatRole(user.role))}
          </span>
        </td>
        <td>${escapeHtml(user.school_name || 'N/A')}</td>
        <td>
          <button
            class="btn btn-sm btn-outline-primary change-password-btn"
            data-user-id="${user.id}"
            data-user-name="${escapeHtml(user.username)}"
            data-bs-toggle="modal"
            data-bs-target="#changePasswordModal"
          >
            Change Password
          </button>
        </td>
      </tr>
    `).join('');
  }

  // Helper functions
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function formatRole(role) {
    if (!role) return 'N/A';
    return role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  }

  function getRoleBadgeColor(role) {
    const colors = {
      'super_admin': 'danger',
      'system_admin': 'warning',
      'owner': 'primary',
      'academic': 'success',
      'teacher': 'secondary',
      'student': 'dark'
    };
    return colors[role] || 'secondary';
  }

  // Event listeners
  liveSearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    currentFilters.search = this.value.trim();
    searchTimeout = setTimeout(fetchUsers, 300); // Debounce 300ms
  });

  filterUserType.addEventListener('change', function() {
    currentFilters.userType = this.value;
    fetchUsers();
  });

  filterRole.addEventListener('change', function() {
    currentFilters.role = this.value;
    fetchUsers();
  });

  filterSchool.addEventListener('change', function() {
    currentFilters.school = this.value;
    fetchUsers();
  });

  resetFilters.addEventListener('click', function() {
    liveSearch.value = '';
    filterUserType.value = 'all';
    filterRole.value = 'all';
    filterSchool.value = 'all';
    currentFilters = {
      search: '',
      userType: 'all',
      role: 'all',
      school: 'all'
    };
    fetchUsers();
  });

  clearSearch.addEventListener('click', function() {
    liveSearch.value = '';
    currentFilters.search = '';
    fetchUsers();
  });

  // Modal handling
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('change-password-btn') ||
        e.target.closest('.change-password-btn')) {
      const button = e.target.classList.contains('change-password-btn') ?
                     e.target : e.target.closest('.change-password-btn');

      document.getElementById('changePasswordUserId').value = button.dataset.userId;
      document.getElementById('changePasswordUserName').textContent = button.dataset.userName;
    }
  });

  // Initial load
  fetchUsers();

  @if(session('change_password_error'))
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
  @endif
});
</script>

@endsection
