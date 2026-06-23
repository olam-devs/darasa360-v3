@extends('layouts/contentNavbarLayout')

@section('title', 'Create New School')

@section('content')
<div class="row">
  <div class="col-12">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Create School</li>
      </ol>
    </nav>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Create New School</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('super_admin.schools.store') }}" method="POST">
          @csrf

          <h6 class="mb-3">School Information</h6>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">School Name *</label>
              <input type="text" class="form-control @error('school_name') is-invalid @enderror" 
                     name="school_name" value="{{ old('school_name') }}" required>
              @error('school_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Location *</label>
              <select class="form-select @error('location_id') is-invalid @enderror" 
                      name="location_id" required>
                <option value="">Select Location</option>
                @foreach($locations as $location)
                  <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                    {{ $location->name }}
                  </option>
                @endforeach
              </select>
              @error('location_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Price Per User (TZS)</label>
              <input type="number" step="0.01" class="form-control @error('price_per_user') is-invalid @enderror"
                     name="price_per_user" value="{{ old('price_per_user') }}" placeholder="e.g. 5000">
              @error('price_per_user')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">Monthly charge per active user (optional)</small>
            </div>
          </div>

          <hr class="my-4">

          <h6 class="mb-3">Database Configuration</h6>
          <p class="text-muted small">Configure the database credentials for this school's tenant database</p>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Database Name *</label>
              <input type="text" class="form-control @error('db_name') is-invalid @enderror"
                     name="db_name" id="db_name" value="{{ old('db_name') }}" required
                     placeholder="school_example_db">
              @error('db_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">Use lowercase letters, numbers, and underscores only (max 64 chars)</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Database Username *</label>
              <input type="text" class="form-control @error('db_username') is-invalid @enderror"
                     name="db_username" id="db_username" value="{{ old('db_username') }}" required
                     placeholder="school_user">
              @error('db_username')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">MySQL username for this school (max 32 chars)</small>
            </div>
          </div>

          <div class="alert alert-info">
            <i class='bx bx-info-circle'></i>
            <strong>Note:</strong> The database host and port will use system defaults.
            The database password will be configured from environment settings.
          </div>

          <hr class="my-4">

          <h6 class="mb-3">Owner Account Information</h6>
          <p class="text-muted small">An owner account will be automatically created for this school</p>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Owner Name *</label>
              <input type="text" class="form-control @error('owner_name') is-invalid @enderror" 
                     name="owner_name" value="{{ old('owner_name') }}" required>
              @error('owner_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Owner Email *</label>
              <input type="email" class="form-control @error('owner_email') is-invalid @enderror" 
                     name="owner_email" value="{{ old('owner_email') }}" required>
              @error('owner_email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Owner Phone Number *</label>
              <input type="text" class="form-control @error('owner_phone') is-invalid @enderror" 
                     name="owner_phone" value="{{ old('owner_phone') }}" required 
                     placeholder="+255XXXXXXXXX">
              @error('owner_phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Owner Username *</label>
              <input type="text" class="form-control @error('owner_username') is-invalid @enderror" 
                     name="owner_username" value="{{ old('owner_username') }}" required>
              @error('owner_username')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">Owner Password *</label>
              <input type="password" class="form-control @error('owner_password') is-invalid @enderror" 
                     name="owner_password" required>
              @error('owner_password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">Minimum 6 characters</small>
            </div>

            <div class="col-md-6">
              <label class="form-label">Confirm Password *</label>
              <input type="password" class="form-control" 
                     name="owner_password_confirmation" required>
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary">
              <i class='bx bx-save'></i> Create School
            </button>
            <a href="{{ route('super_admin.dashboard') }}" class="btn btn-secondary">
              <i class='bx bx-x'></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@if($errors->any())
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div class="toast show" role="alert">
    <div class="toast-header bg-danger text-white">
      <strong class="me-auto">Error</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
</div>
@endif

@endsection

@section('page-script')
<script>
// Auto-generate database name and username from school name
document.querySelector('input[name="school_name"]').addEventListener('input', function() {
  const schoolName = this.value.trim();
  if (schoolName) {
    // Generate database name: school_<name>_<timestamp>
    const sanitized = schoolName.toLowerCase()
      .replace(/[^a-z0-9]/g, '_')
      .replace(/_+/g, '_')
      .replace(/^_|_$/g, '');

    const dbName = 'school_' + sanitized;
    const dbUsername = 'user_' + sanitized;

    // Only auto-fill if fields are empty
    const dbNameField = document.getElementById('db_name');
    const dbUsernameField = document.getElementById('db_username');

    if (!dbNameField.value || dbNameField.value.startsWith('school_')) {
      dbNameField.value = dbName.substring(0, 64); // Max 64 chars
    }

    if (!dbUsernameField.value || dbUsernameField.value.startsWith('user_')) {
      dbUsernameField.value = dbUsername.substring(0, 32); // Max 32 chars
    }
  }
});
</script>
@endsection
