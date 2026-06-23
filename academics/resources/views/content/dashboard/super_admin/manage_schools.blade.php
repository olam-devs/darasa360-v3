@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Schools'
])
@section('title', 'Manage Schools')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Schools</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSchoolModal">
        <i class="bx bx-plus"></i> Add New School
      </button>
    </div>

    <!-- Success message -->
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <!-- Error messages -->
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

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>School</th>
              <th>Message Name</th>
              <th>Location</th>
              <th>Database URL</th>
              <th>Package</th>
              <th>Administrator</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($schools as $school)
              <tr>
                <td>{{ $loop->iteration + ($schools->currentPage() - 1) * $schools->perPage() }}</td>
                <td><strong>{{ $school->name }}</strong></td>
                <td>{{ $school->message_name ?? 'N/A' }}</td>
                <td>{{ $school->location->name ?? 'N/A' }}</td>
                <td>{{ $school->database_url }}</td>
                <td>
                  <form method="POST" action="{{ route('schools.update.package', $school->id) }}" class="d-flex gap-1">
                    @csrf
                    @method('PATCH')

                    @foreach (['core', 'standard', 'premium'] as $pkg)
                      <button type="submit" name="package" value="{{ $pkg }}"
                        class="btn btn-sm {{ $school->package === $pkg ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ ucfirst($pkg) }}
                      </button>
                    @endforeach
                  </form>
                </td>

                <td>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-info fetch-admin-btn"
                    data-school-id="{{ $school->id }}">
                    View
                  </button>
                </td>
                <td>
                  <form method="POST" action="{{ route('admin.updateSchool', $school->id) }}">
                    @csrf
                    <input type="hidden" name="toggle_status" value="1">
                    <button type="submit"
                      class="btn btn-sm border-1 {{ $school->status === 'active' ? 'btn-outline-success' : 'btn-outline-danger' }}">
                      {{ $school->status === 'active' ? 'Active' : 'Inactive' }}
                    </button>
                  </form>
                </td>
                <td>
                  <a href="#" class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editSchoolModal{{ $school->id }}">
                    <i class="bx bx-edit"></i>
                  </a>

                  <form action="{{ route('admin.deleteSchool', $school->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this school?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center">No schools found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 mx-3">
        {{ $schools->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

{{-- Edit School Modals --}}
@foreach($schools as $school)
  <div class="modal fade" id="editSchoolModal{{ $school->id }}" tabindex="-1" aria-labelledby="editSchoolModalLabel{{ $school->id }}" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="{{ route('admin.updateSchool', $school->id) }}">
        @csrf
        <div class="modal-content">
          <div class="modal-header position-relative">
            <h5 class="modal-title">Edit School</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">School Name</label>
              <input type="text" name="name" class="form-control" value="{{ $school->name }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Message Name</label>
              <input type="text" name="message_name" class="form-control" value="{{ $school->message_name }}" required>
            </div>
            <div class="mb-3">
              <label for="location_id_{{ $school->id }}" class="form-label">Location</label>
              <select name="location_id" id="location_id_{{ $school->id }}" class="form-select" required>
                @foreach($locations as $location)
                  <option value="{{ $location->id }}" {{ $school->location_id == $location->id ? 'selected' : '' }}>
                    {{ $location->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Database URL</label>
              <input type="text" name="database_url" class="form-control" value="{{ $school->database_url }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Package</label>
              <select name="package" class="form-select" required>
                <option value="core" {{ $school->package === 'core' ? 'selected' : '' }}>Core</option>
                <option value="standard" {{ $school->package === 'standard' ? 'selected' : '' }}>Standard</option>
                <option value="premium" {{ $school->package === 'premium' ? 'selected' : '' }}>Premium</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Admin Username</label>
              <input type="text" name="admin_username" class="form-control" value="{{ old('admin_username') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Admin Email</label>
              <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Admin Phone</label>
              <input type="text" name="admin_phone" class="form-control" value="{{ old('admin_phone') }}" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Update</button>
          </div>
        </div>
      </form>
    </div>
  </div>
@endforeach

<!-- Add School Modal -->
<div class="modal fade" id="addSchoolModal" tabindex="-1" aria-labelledby="addSchoolModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ url('/admins/add-school') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="addSchoolModalLabel">Add New School</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">
          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label">School Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Message Name</label>
            <input type="text" name="message_name" class="form-control" value="{{ old('message_name') }}" required>
          </div>
          <div class="mb-3">
            <label for="location_id" class="form-label">Location</label>
            <select name="location_id" id="location_id" class="form-select" required>
              <option value="" disabled selected>Select location</option>
              @foreach($locations as $location)
                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                  {{ $location->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Database Name</label>
            <input type="text" name="db_name" class="form-control" value="{{ old('db_name') }}" required
                   placeholder="school_example_db" pattern="[a-zA-Z0-9_]+" maxlength="64">
            <small class="text-muted">Use lowercase letters, numbers, and underscores only</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Database Username</label>
            <input type="text" name="db_username" class="form-control" value="{{ old('db_username') }}" required
                   placeholder="school_user" pattern="[a-zA-Z0-9_]+" maxlength="32">
            <small class="text-muted">MySQL username for this school</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Package</label>
            <select name="package" class="form-select" required>
              <option value="core" {{ old('package') === 'core' ? 'selected' : '' }}>Core</option>
              <option value="standard" {{ old('package') === 'standard' ? 'selected' : '' }}>Standard</option>
              <option value="premium" {{ old('package') === 'premium' ? 'selected' : '' }}>Premium</option>
            </select>
          </div>

          <hr>
          <h6 class="fw-bold">Admin Info</h6>
          <div class="mb-3">
            <label class="form-label">Admin Username</label>
            <input type="text" name="admin_username" class="form-control" value="{{ old('admin_username') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Admin Phone</label>
            <input type="text" name="admin_phone" class="form-control" value="{{ old('admin_phone') }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Admin Email</label>
            <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Admin Password</label>
            <input type="password" name="admin_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="admin_password_confirmation" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create School</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Shared Admin Info Modal -->
<div class="modal fade" id="adminInfoModal" tabindex="-1" aria-labelledby="adminInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="adminInfoModalLabel">Administrator Information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="adminInfoContent">
        Loading...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const adminInfoModal = new bootstrap.Modal(document.getElementById('adminInfoModal'));
  const adminInfoContent = document.getElementById('adminInfoContent');

  document.querySelectorAll('.fetch-admin-btn').forEach(button => {
    button.addEventListener('click', function () {
      const schoolId = this.dataset.schoolId;

      // Show modal with loading text
      adminInfoContent.innerHTML = 'Loading...';
      adminInfoModal.show();

      // Fetch admin info from backend
      fetch(`/admins/schools/${schoolId}/admin-info`)
        .then(response => response.json())
        .then(data => {
          if(data.error){
            adminInfoContent.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
          } else {
            adminInfoContent.innerHTML = `
              <p><strong>Username:</strong> ${data.username}</p>
              <p><strong>Registration No:</strong> ${data.registration_no}</p>
              <p><strong>Email:</strong> ${data.email}</p>
              <p><strong>Phone Number:</strong> ${data.phone_number}</p>
            `;
          }
        })
        .catch(error => {
          console.error('Error fetching admin info:', error);
          adminInfoContent.innerHTML = `<div class="alert alert-danger">Failed to fetch admin info.</div>`;
        });
    });
  });
});
</script>

@endsection
