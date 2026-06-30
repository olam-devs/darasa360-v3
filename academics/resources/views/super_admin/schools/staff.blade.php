@extends('layouts/contentNavbarLayout')

@section('title', $school->name . ' — Staff Management')

@section('content')
<div class="row">
  <div class="col-12">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('super_admin.schools.index') }}">Schools</a></li>
        <li class="breadcrumb-item"><a href="{{ route('super_admin.schools.view', $school->id) }}">{{ $school->name }}</a></li>
        <li class="breadcrumb-item active">Staff</li>
      </ol>
    </nav>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
  {{-- Staff list --}}
  <div class="col-md-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Staff ({{ $staff->count() }})</h5>
      </div>
      <div class="card-body p-0">
        @if($staff->isEmpty())
          <p class="text-muted p-3 mb-0">No staff yet. Use the form to add the first staff member.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Username</th>
                  <th>Reg No</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($staff as $i => $member)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>{{ $member->username }}</td>
                  <td><code>{{ $member->registration_no }}</code></td>
                  <td>{{ $member->role_name ?? '—' }}</td>
                  <td>
                    <span class="badge bg-{{ $member->status === 'active' ? 'success' : 'secondary' }}">
                      {{ ucfirst($member->status) }}
                    </span>
                  </td>
                  <td>
                    {{-- Toggle status --}}
                    <form method="POST" action="{{ route('super_admin.schools.staff.toggle', [$school->id, $member->id]) }}" class="d-inline">
                      @csrf
                      <button class="btn btn-sm btn-{{ $member->status === 'active' ? 'warning' : 'success' }}">
                        {{ $member->status === 'active' ? 'Disable' : 'Enable' }}
                      </button>
                    </form>

                    {{-- Reset password --}}
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#resetPwdModal{{ $member->id }}">
                      Reset Pwd
                    </button>

                    {{-- Delete --}}
                    <form method="POST" action="{{ route('super_admin.schools.staff.delete', [$school->id, $member->id]) }}" class="d-inline"
                          onsubmit="return confirm('Delete {{ $member->username }}?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>

                {{-- Reset password modal --}}
                <div class="modal fade" id="resetPwdModal{{ $member->id }}" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="POST" action="{{ route('super_admin.schools.staff.reset_password', [$school->id, $member->id]) }}">
                        @csrf
                        <div class="modal-header">
                          <h5 class="modal-title">Reset Password — {{ $member->username }}</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Add staff form --}}
  <div class="col-md-4">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Add Staff Member</h5></div>
      <div class="card-body">
        <form method="POST" action="{{ route('super_admin.schools.staff.create', $school->id) }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Registration No</label>
            <input type="text" name="registration_no" class="form-control" placeholder="e.g. S0012001" required>
            <small class="text-muted">Format: S{school_code}{role_digit}{seq}</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role_id" class="form-select" required>
              <option value="">— Select role —</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone_number" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="6">
          </div>
          <button type="submit" class="btn btn-primary w-100">Add Staff</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
