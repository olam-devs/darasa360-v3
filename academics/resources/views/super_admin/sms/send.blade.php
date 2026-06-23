@extends('layouts/contentNavbarLayout')

@section('title', 'Send SMS')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Send Bulk SMS</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('super_admin.sms.send_post') }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label">Recipient Type</label>
            <select class="form-select" name="recipients" required>
              <option value="all_schools">All Schools</option>
              <option value="specific_school">Specific School</option>
              <option value="all_admins">All Admins</option>
              <option value="system_admins">System Admins Only</option>
              <option value="school_admins">School Admins Only</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Schools (Optional)</label>
            <div class="border rounded p-3" style="max-height: 250px; overflow-y: auto; background-color: rgba(0, 0, 0, 0.02);">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="selectAllSchools" onchange="toggleAllSchools(this)">
                <label class="form-check-label fw-bold" for="selectAllSchools" style="color: var(--bs-body-color);">
                  Select All
                </label>
              </div>
              <hr class="my-2">
              @foreach($schools as $school)
              <div class="form-check mb-2">
                <input class="form-check-input school-checkbox" type="checkbox" name="school_ids[]" value="{{ $school->id }}" id="school_{{ $school->id }}">
                <label class="form-check-label" for="school_{{ $school->id }}" style="color: var(--bs-body-color);">
                  <span style="font-weight: 500;">{{ $school->name }}</span> <small class="text-muted">({{ $school->school_code }})</small>
                </label>
              </div>
              @endforeach
            </div>
            <small class="text-muted"><span id="selectedSchoolCount">0</span> school(s) selected</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea class="form-control" name="message" rows="5" maxlength="160" required placeholder="Enter your message (max 160 characters)"></textarea>
            <small class="text-muted"><span id="charCount">0</span>/160 characters</small>
          </div>

          <div class="mb-3">
            <button type="submit" class="btn btn-primary">
              <i class='bx bx-send'></i> Send SMS
            </button>
            <a href="{{ route('super_admin.sms.index') }}" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelector('textarea[name="message"]').addEventListener('input', function() {
  document.getElementById('charCount').textContent = this.value.length;
});

function toggleAllSchools(checkbox) {
  const schoolCheckboxes = document.querySelectorAll('.school-checkbox');
  schoolCheckboxes.forEach(cb => cb.checked = checkbox.checked);
  updateSchoolCount();
}

function updateSchoolCount() {
  const checked = document.querySelectorAll('.school-checkbox:checked').length;
  document.getElementById('selectedSchoolCount').textContent = checked;
}

// Add event listeners to all school checkboxes
document.querySelectorAll('.school-checkbox').forEach(checkbox => {
  checkbox.addEventListener('change', function() {
    updateSchoolCount();
    // Update "Select All" checkbox
    const allCheckboxes = document.querySelectorAll('.school-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.school-checkbox:checked');
    document.getElementById('selectAllSchools').checked = allCheckboxes.length === checkedCheckboxes.length;
  });
});
</script>

@endsection
