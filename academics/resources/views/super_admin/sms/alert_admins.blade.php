@extends('layouts/contentNavbarLayout')

@section('title', 'Alert School Admins')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Send Alert to School Admins</h5>
        <p class="text-muted mb-0 small">Send system updates, maintenance alerts, or urgent notifications to school administrators</p>
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

        <form action="{{ route('super_admin.sms.send_alert') }}" method="POST">
          @csrf

          <div class="mb-3">
            <label class="form-label">Alert Type</label>
            <select class="form-select" name="alert_type" required>
              <option value="">Select Alert Type</option>
              <option value="system_update">System Update</option>
              <option value="scheduled_maintenance">Scheduled Maintenance</option>
              <option value="urgent_security">Urgent Security Alert</option>
              <option value="feature_announcement">New Feature Announcement</option>
              <option value="general">General Notification</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Target Recipients</label>
            <select class="form-select" name="recipient_type" id="recipientType" required>
              <option value="all_admins">All School Admins (System + School)</option>
              <option value="system_admins">System Admins Only</option>
              <option value="school_admins">School Admins Only</option>
              <option value="specific_schools">Specific Schools</option>
            </select>
          </div>

          <div class="mb-3 d-none" id="schoolSelector">
            <label class="form-label">Select Schools</label>
            <select class="form-select" name="school_ids[]" multiple size="6">
              @foreach(\App\Models\School::orderBy('name')->get() as $school)
                <option value="{{ $school->id }}">{{ $school->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple schools</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Alert Priority</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="priority" value="normal" id="priorityNormal" checked>
              <label class="form-check-label" for="priorityNormal">
                Normal
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="priority" value="high" id="priorityHigh">
              <label class="form-check-label" for="priorityHigh">
                High Priority
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="priority" value="urgent" id="priorityUrgent">
              <label class="form-check-label text-danger" for="priorityUrgent">
                Urgent
              </label>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea class="form-control" name="message" id="messageText" rows="5" maxlength="160" required
                      placeholder="Enter your alert message (max 160 characters)"></textarea>
            <small class="text-muted">
              <span id="charCount">0</span>/160 characters
            </small>
          </div>

          <div class="alert alert-warning">
            <i class='bx bx-info-circle'></i>
            <strong>Note:</strong> This will send SMS to all admins matching your criteria.
          </div>

          <div class="mb-3">
            <button type="submit" class="btn btn-primary">
              <i class='bx bx-bell'></i> Send Alert
            </button>
            <a href="{{ route('super_admin.sms.index') }}" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Character counter
document.getElementById('messageText').addEventListener('input', function() {
  document.getElementById('charCount').textContent = this.value.length;
});

// Show/hide school selector
document.getElementById('recipientType').addEventListener('change', function() {
  const schoolSelector = document.getElementById('schoolSelector');
  if (this.value === 'specific_schools') {
    schoolSelector.classList.remove('d-none');
    schoolSelector.querySelector('select').required = true;
  } else {
    schoolSelector.classList.add('d-none');
    schoolSelector.querySelector('select').required = false;
  }
});
</script>

@endsection
