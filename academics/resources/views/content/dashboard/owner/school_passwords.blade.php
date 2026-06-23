@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Passwords'
])
@section('title', 'Manage User Passwords')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Passwords</h4>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    {{-- ✅ Show search only if allowed --}}
    @if($isSearch)
      <form method="GET" action="{{ route('schooladmin.passwords') }}" class="row g-3 mb-4">
        <div class="col-md-6">
          <input type="text" name="search" class="form-control" placeholder="Search users..." value="{{ $search }}">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100" type="submit">
            <i class="bx bx-search"></i> Search
          </button>
        </div>
      </form>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Username</th>
              <th>Registration No</th>
              <th>Role</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $user)
              <tr>
                <td>{{ $user->username }}</td>
                <td>{{ $user->registration_no }}</td>
                <td>{{ $user->role_name->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->phone_number }}</td>
                <td>
                  <button class="btn btn-sm btn-outline-primary change-password-btn"
                    data-user-id="{{ $user->registration_no }}"
                    data-user-name="{{ $user->username }}"
                    data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    Change Password
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center">No users found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- ✅ Hide pagination for Teacher/Student (only one record) --}}
      @if($isSearch)
        <div class="mt-3 mx-3">
          {{ $users->appends(['search' => $search])->links('pagination::bootstrap-5') }}
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('schooladmin.passwords.change') }}">
      @csrf
      <input type="hidden" name="user_id" id="changePasswordUserId">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p><strong>User:</strong> <span id="changePasswordUserName"></span></p>
          <div class="mb-3">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Confirm Password</label>
            <input type="password" name="new_password_confirmation" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit">Change Password</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('changePasswordModal');
  modal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('changePasswordUserId').value = button.dataset.userId;
    document.getElementById('changePasswordUserName').textContent = button.dataset.userName;
  });
});
</script>
@endsection
