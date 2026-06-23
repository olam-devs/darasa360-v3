@extends('layouts/contentNavbarLayout')

@section('title', 'School SMS Allocations')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">School SMS Allocations</h5>
          <p class="text-muted mb-0 small">Manage SMS credits and allocations for each school</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkAllocationModal">
          <i class='bx bx-plus'></i> Bulk Allocation
        </button>
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
                <th>School Name</th>
                <th>Location</th>
                <th>Package</th>
                <th>SMS Balance</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($schools as $school)
              <tr>
                <td><strong>{{ $school->name }}</strong></td>
                <td>{{ $school->location->name ?? 'N/A' }}</td>
                <td><span class="badge bg-label-primary">{{ ucfirst($school->package) }}</span></td>
                <td>
                  <strong>{{ $school->smsBalance->sms_allocated ?? 0 }}</strong> SMS
                </td>
                <td>
                  @php
                    $balance = $school->smsBalance->sms_allocated ?? 0;
                  @endphp
                  @if($balance > 100)
                    <span class="badge bg-success">Active</span>
                  @elseif($balance > 0)
                    <span class="badge bg-warning">Low</span>
                  @else
                    <span class="badge bg-danger">Depleted</span>
                  @endif
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#allocateModal{{ $school->id }}">
                    <i class='bx bx-plus'></i> Add Credits
                  </button>
                </td>
              </tr>

              <!-- Allocation Modal for this school -->
              <div class="modal fade" id="allocateModal{{ $school->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Allocate SMS Credits</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('super_admin.sms.allocate_credits', $school->id) }}" method="POST">
                      @csrf
                      <div class="modal-body">
                        <p><strong>School:</strong> {{ $school->name }}</p>
                        <p><strong>Current Balance:</strong> {{ $school->smsBalance->sms_allocated ?? 0 }} SMS</p>

                        <div class="mb-3">
                          <label class="form-label">Credits to Add</label>
                          <input type="number" class="form-control" name="credits" min="1" required>
                        </div>

                        <div class="mb-3">
                          <label class="form-label">Notes (Optional)</label>
                          <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Allocate Credits</button>
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

<!-- Bulk Allocation Modal -->
<div class="modal fade" id="bulkAllocationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Bulk SMS Allocation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('super_admin.sms.bulk_allocate') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Target Schools</label>
            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto; background-color: rgba(0, 0, 0, 0.02);">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="selectAllBulkSchools" onchange="toggleAllBulkSchools(this)">
                <label class="form-check-label fw-bold" for="selectAllBulkSchools" style="color: var(--bs-body-color);">
                  Select All
                </label>
              </div>
              <hr class="my-2">
              @foreach($schools as $school)
              <div class="form-check mb-2">
                <input class="form-check-input bulk-school-checkbox" type="checkbox" name="school_ids[]" value="{{ $school->id }}" id="bulk_school_{{ $school->id }}">
                <label class="form-check-label" for="bulk_school_{{ $school->id }}" style="color: var(--bs-body-color);">
                  <span style="font-weight: 500;">{{ $school->name }}</span>
                  <small class="text-muted">
                    (Current: {{ $school->smsBalance->sms_allocated ?? 0 }} SMS)
                  </small>
                </label>
              </div>
              @endforeach
            </div>
            <small class="text-muted"><span id="bulkSelectedCount">0</span> school(s) selected</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Credits Per School</label>
            <input type="number" class="form-control" name="credits_per_school" min="1" required placeholder="Enter SMS credits">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Allocate</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
function toggleAllBulkSchools(checkbox) {
  const checkboxes = document.querySelectorAll('.bulk-school-checkbox');
  checkboxes.forEach(cb => cb.checked = checkbox.checked);
  updateBulkCount();
}

function updateBulkCount() {
  const checked = document.querySelectorAll('.bulk-school-checkbox:checked').length;
  document.getElementById('bulkSelectedCount').textContent = checked;
}

// Add listeners to all bulk school checkboxes
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.bulk-school-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      updateBulkCount();
      // Update "Select All" checkbox
      const allCheckboxes = document.querySelectorAll('.bulk-school-checkbox');
      const checkedCheckboxes = document.querySelectorAll('.bulk-school-checkbox:checked');
      const selectAll = document.getElementById('selectAllBulkSchools');
      if (selectAll) {
        selectAll.checked = allCheckboxes.length === checkedCheckboxes.length;
      }
    });
  });
});
</script>
@endpush
