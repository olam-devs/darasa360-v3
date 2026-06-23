@extends('layouts/contentNavbarLayout')

@section('title', 'Manage Admin Allocations')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Manage System Admin School Allocations</h5>
        <p class="text-muted mb-0 small">Assign or remove schools from system administrators</p>
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

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Admin Name</th>
                <th>Email</th>
                <th>Assigned Schools</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($systemAdmins as $admin)
              <tr>
                <td><strong>{{ $admin->username }}</strong></td>
                <td>{{ $admin->email }}</td>
                <td>
                  @if($admin->schoolAdmins->count() > 0)
                    <ul class="mb-0">
                      @foreach($admin->schoolAdmins as $assignment)
                        <li>
                          {{ $assignment->school->name }}
                          <form action="{{ route('super_admin.admins.remove_school', ['adminId' => $admin->id, 'schoolId' => $assignment->school_id]) }}"
                                method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"
                                    onclick="return confirm('Remove this school assignment?')">
                              <i class='bx bx-x'></i>
                            </button>
                          </form>
                        </li>
                      @endforeach
                    </ul>
                  @else
                    <span class="text-muted">No schools assigned</span>
                  @endif
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#addSchoolModal{{ $admin->id }}">
                    <i class='bx bx-plus'></i> Add Schools
                  </button>
                </td>
              </tr>

              <!-- Add School Modal for this admin -->
              <div class="modal fade" id="addSchoolModal{{ $admin->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Add Schools to {{ $admin->username }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('super_admin.admins.add_schools', $admin->id) }}" method="POST">
                      @csrf
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Select Schools</label>
                          <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto; background: var(--bs-body-bg);">
                            @php
                              $assignedSchoolIds = $admin->schoolAdmins->pluck('school_id')->toArray();
                              $availableSchools = $allSchools->filter(function($school) use ($assignedSchoolIds) {
                                return !in_array($school->id, $assignedSchoolIds);
                              });
                            @endphp
                            @if($availableSchools->count() > 1)
                            <div class="form-check mb-2">
                              <input class="form-check-input" type="checkbox" id="selectAllSchools{{ $admin->id }}" onchange="toggleAllCheckboxes(this, 'school-checkbox-{{ $admin->id }}')">
                              <label class="form-check-label fw-bold" for="selectAllSchools{{ $admin->id }}">
                                Select All
                              </label>
                            </div>
                            <hr class="my-2">
                            @endif
                            @foreach($availableSchools as $school)
                            <div class="form-check mb-2">
                              <input class="form-check-input school-checkbox-{{ $admin->id }}" type="checkbox" name="school_ids[]" value="{{ $school->id }}" id="school_{{ $admin->id }}_{{ $school->id }}">
                              <label class="form-check-label" for="school_{{ $admin->id }}_{{ $school->id }}">
                                {{ $school->name }}
                              </label>
                            </div>
                            @endforeach
                          </div>
                          <small class="text-muted"><span id="selectedCount{{ $admin->id }}">0</span> school(s) selected</small>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Schools</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function toggleAllCheckboxes(masterCheckbox, className) {
  const checkboxes = document.querySelectorAll('.' + className);
  checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
  updateCount(className);
}

function updateCount(className) {
  const checkboxes = document.querySelectorAll('.' + className);
  const checked = Array.from(checkboxes).filter(cb => cb.checked).length;
  const adminId = className.split('-').pop();
  const countEl = document.getElementById('selectedCount' + adminId);
  if (countEl) countEl.textContent = checked;
}

// Add listeners to all checkboxes
document.querySelectorAll('[class*="school-checkbox-"]').forEach(checkbox => {
  checkbox.addEventListener('change', function() {
    const className = Array.from(this.classList).find(c => c.startsWith('school-checkbox-'));
    if (className) updateCount(className);
  });
});
</script>
@endpush
