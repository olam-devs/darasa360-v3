@extends('layouts/contentNavbarLayout')

@section('title', 'Manage Admins')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Manage System Admins</h5>
        <div>
          <a href="{{ route('super_admin.admins.allocations') }}" class="btn btn-outline-primary btn-sm">
            <i class='bx bx-grid-alt'></i> Manage Allocations
          </a>
          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSuperAdminModal">
            <i class='bx bx-plus'></i> Create Super Admin
          </button>
          <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#createSystemAdminModal">
            <i class='bx bx-plus'></i> Create System Admin
          </button>
          <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createSchoolAdminModal">
            <i class='bx bx-plus'></i> Create School Admin
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Assigned Schools</th>
                <th>Actions</th>
              </tr>
            </thead>
            <thead>
              <tr>
                <th>Username</th>
                <th>Login (Reg No.)</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Assigned Schools</th>
              </tr>
            </thead>
            <tbody>
              @forelse($systemAdmins as $admin)
              <tr>
                <td><strong>{{ $admin->username }}</strong></td>
                <td><code>{{ $admin->registration_no ?? '—' }}</code></td>
                <td>{{ $admin->email }}</td>
                <td>{{ $admin->phone_number }}</td>
                <td>
                  <span class="badge bg-label-primary">{{ ucfirst(str_replace('_', ' ', $admin->userRole->name ?? 'N/A')) }}</span>
                </td>
                <td>
                  {{ $admin->schoolAdmins->count() }} school(s)
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center">No admins found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create Super Admin Modal -->
<div class="modal fade" id="createSuperAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Super Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('super_admin.admins.create_super') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control" name="phone_number" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Create System Admin Modal -->
<div class="modal fade" id="createSystemAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create System Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('super_admin.admins.create_system') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control" name="phone_number" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Assign to Schools (Optional)</label>
            <select class="form-select" name="school_ids[]" multiple size="5">
              @foreach(\App\Models\School::orderBy('name')->get() as $school)
                <option value="{{ $school->id }}">{{ $school->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple schools. You can assign more schools later.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Create School Admin Modal -->
<div class="modal fade" id="createSchoolAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create School Admin (tenant-level)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('super_admin.admins.create_school') }}" method="POST">
        @csrf
        <div class="modal-body">
          <p class="text-muted small">Creates an <strong>Admin</strong> user directly in a school's database. The registration number is auto-generated.</p>
          <div class="mb-3">
            <label class="form-label">School</label>
            <select class="form-select" name="school_id" required>
              <option value="">— Select school —</option>
              @foreach(\App\Models\School::orderBy('name')->get() as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control" name="phone_number">
          </div>
          <div class="mb-3">
            <label class="form-label">Gender</label>
            <select class="form-select" name="gender"><option value="Male">Male</option><option value="Female">Female</option></select>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Create</button>
        </div>
      </form>
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
