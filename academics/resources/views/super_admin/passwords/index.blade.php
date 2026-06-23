@extends('layouts/contentNavbarLayout')

@section('title', 'Password Management')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Password Management</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Reset passwords for any user in the system</p>

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($users as $user)
              <tr>
                <td><strong>{{ $user->username }}</strong></td>
                <td>{{ $user->email }}</td>
                <td>
                  <span class="badge bg-label-primary">
                    {{ ucfirst(str_replace('_', ' ', $user->userRole->name ?? 'N/A')) }}
                  </span>
                </td>
                <td>{{ $user->phone_number }}</td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal{{ $user->id }}">
                    <i class='bx bx-lock-open'></i> Reset Password
                  </button>
                </td>
              </tr>

              <!-- Reset Password Modal -->
              <div class="modal fade" id="resetPasswordModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Reset Password for {{ $user->username }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('super_admin.passwords.reset') }}" method="POST">
                      @csrf
                      <input type="hidden" name="user_id" value="{{ $user->id }}">
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">New Password</label>
                          <input type="password" class="form-control" name="new_password" required minlength="6">
                          <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Confirm Password</label>
                          <input type="password" class="form-control" name="confirm_password" required minlength="6">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reset Password</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              @empty
              <tr>
                <td colspan="5" class="text-center">No users found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="mt-3">
          {{ $users->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

@if(session('success'))
<script>
  document.addEventListener('DOMContentLoaded', function() {
    alert('{{ session('success') }}');
  });
</script>
@endif

@endsection
