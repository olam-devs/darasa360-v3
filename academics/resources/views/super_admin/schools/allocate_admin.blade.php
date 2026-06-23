@extends('layouts/contentNavbarLayout')

@section('title', 'Allocate School Admin')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="alert alert-info">
      <i class='bx bx-info-circle'></i>
      <strong>School Created Successfully!</strong>
      Now allocate a school administrator to manage {{ $school->name }}.
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">School Details</h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">School Name:</dt>
          <dd class="col-sm-8">{{ $school->name }}</dd>

          <dt class="col-sm-4">School Code:</dt>
          <dd class="col-sm-8"><code>{{ $school->school_code }}</code></dd>

          <dt class="col-sm-4">Package:</dt>
          <dd class="col-sm-8"><span class="badge bg-label-primary">{{ ucfirst($school->package) }}</span></dd>

          <dt class="col-sm-4">Owner:</dt>
          <dd class="col-sm-8">{{ $school->administrator->username ?? 'N/A' }}</dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Allocate School Admin</h5>
      </div>
      <div class="card-body">
        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <div class="mb-4">
          <h6>Option 1: Create New School Admin</h6>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
            <i class='bx bx-plus'></i> Create New Admin
          </button>
        </div>

        <hr>

        <div>
          <h6>Option 2: Assign Existing School Admin</h6>
          @if($existingAdmins->count() > 0)
            <form action="{{ route('super_admin.schools.assign_existing_admin', $school->id) }}" method="POST">
              @csrf
              <div class="mb-3">
                <label class="form-label">Select Admin</label>
                <select class="form-select" name="admin_id" required>
                  <option value="">Choose...</option>
                  @foreach($existingAdmins as $admin)
                    <option value="{{ $admin->id }}">
                      {{ $admin->username }} ({{ $admin->email }})
                    </option>
                  @endforeach
                </select>
              </div>
              <button type="submit" class="btn btn-success">
                <i class='bx bx-check'></i> Assign Admin
              </button>
            </form>
          @else
            <p class="text-muted mb-0">No existing school admins available.</p>
          @endif
        </div>

        <hr>

        <div>
          <a href="{{ route('super_admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class='bx bx-skip-next'></i> Skip for Now
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create School Admin for {{ $school->name }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('super_admin.schools.create_and_assign_admin', $school->id) }}" method="POST">
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
          <button type="submit" class="btn btn-primary">Create & Assign</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
