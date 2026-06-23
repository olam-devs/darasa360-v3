@extends('layouts.modernLayout', [
    'pageTitle' => 'Promote Staff'
])

@section('title', 'Promote Staff')

@section('content')

@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if (session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if ($errors->any())
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="card">
  <h5 class="card-header">
    <i class="bx bx-trending-up me-2"></i>Promote Staff Members
  </h5>
  <div class="card-body">
    <p class="text-muted mb-3">Select staff members from their current role and promote them to a new role.</p>
    <form method="GET" action="{{ route('owner.staff-promotion.index') }}">
      <div class="row g-3 align-items-center">
        <div class="col-md-5">
          <label for="from_role" class="form-label">From Role</label>
          <select name="from_role_id" id="from_role" class="form-select" required>
            <option value="">-- Select Current Role --</option>
            @foreach ($roles as $role)
              <option value="{{ $role->id }}" {{ request('from_role_id') == $role->id ? 'selected' : '' }}>
                {{ ucfirst($role->name) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3 align-self-end">
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-search me-1"></i>Fetch Staff
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

@if (isset($staff) && count($staff) > 0)
  <div class="card mt-4">
    <h5 class="card-header">
      <i class="bx bx-group me-2"></i>Promote Staff from "{{ ucfirst($selectedRole->name) }}" Role
    </h5>
    <div class="card-body">
      <form method="POST" action="{{ route('owner.staff-promotion.promote') }}">
        @csrf

        <input type="hidden" name="from_role_id" value="{{ $selectedRole->id }}">

        <div class="mb-3">
          <label for="to_role" class="form-label">To Role</label>
          <select name="to_role_id" id="to_role" class="form-select" required>
            <option value="">-- Select New Role --</option>
            @foreach ($roles as $role)
              @if ($role->id != $selectedRole->id)
                <option value="{{ $role->id }}" {{ old('to_role_id') == $role->id ? 'selected' : '' }}>
                  {{ ucfirst($role->name) }}
                </option>
              @endif
            @endforeach
          </select>
          @error('to_role_id')
            <small class="text-danger">{{ $message }}</small>
          @enderror
        </div>

        <div class="table-responsive">
          <table class="table table-striped mt-3">
            <thead>
              <tr>
                <th><input type="checkbox" id="select-all"></th>
                <th>Registration No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Current Role</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($staff as $member)
                <tr>
                  <td>
                    <input type="checkbox" name="staff_ids[]" value="{{ $member->id }}">
                  </td>
                  <td>{{ $member->registration_no }}</td>
                  <td>{{ $member->username }}</td>
                  <td>{{ $member->email ?? '—' }}</td>
                  <td>{{ $member->phone ?? '—' }}</td>
                  <td>
                    <span class="badge bg-label-primary">{{ ucfirst($member->role_name) }}</span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="mt-3 d-flex align-items-center justify-content-between">
          <span class="text-muted">
            <i class="bx bx-info-circle me-1"></i>
            <span id="selected-count">0</span> staff member(s) selected
          </span>
          <button type="submit" class="btn btn-success" id="promote-btn" disabled>
            <i class="bx bx-chevron-up me-1"></i>Promote Selected Staff
          </button>
        </div>
      </form>
    </div>
  </div>
@elseif(request('from_role_id'))
  <div class="alert alert-warning mt-3">
    <i class="bx bx-info-circle me-2"></i>No staff members found with the selected role.
  </div>
@endif

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const selectAllCheckbox = document.getElementById('select-all');
    const selectedCountSpan = document.getElementById('selected-count');
    const promoteBtn = document.getElementById('promote-btn');

    function updateSelectAllState() {
      const staffCheckboxes = document.querySelectorAll('input[name="staff_ids[]"]');
      const checkedCount = Array.from(staffCheckboxes).filter(cb => cb.checked).length;
      const allChecked = staffCheckboxes.length > 0 && Array.from(staffCheckboxes).every(chk => chk.checked);
      const someChecked = checkedCount > 0;

      if (selectAllCheckbox) {
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = someChecked && !allChecked;
      }

      if (selectedCountSpan) {
        selectedCountSpan.textContent = checkedCount;
      }

      if (promoteBtn) {
        promoteBtn.disabled = checkedCount === 0;
      }
    }

    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', () => {
        const staffCheckboxes = document.querySelectorAll('input[name="staff_ids[]"]');
        staffCheckboxes.forEach(cb => {
          cb.checked = selectAllCheckbox.checked;
        });
        updateSelectAllState();
      });
    }

    function attachStaffCheckboxListeners() {
      const staffCheckboxes = document.querySelectorAll('input[name="staff_ids[]"]');
      staffCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectAllState);
      });
    }

    attachStaffCheckboxListeners();
    updateSelectAllState();
  });
</script>

@endsection
