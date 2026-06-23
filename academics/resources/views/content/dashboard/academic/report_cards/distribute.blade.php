<!-- Mass Distribution Modal -->
<div class="modal fade" id="distributeModal{{ $config->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Distribute Report Cards</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('reports.cards.distribute', $config->id) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info">
            <i class='bx bx-info-circle'></i>
            <strong>Distribution Details:</strong><br>
            Class: {{ $config->class->name ?? 'All Classes' }}<br>
            Term: {{ $config->term }}<br>
            Total Students: {{ $studentCount ?? 'Calculating...' }}
          </div>

          <div class="mb-3">
            <label class="form-label">Distribution Method *</label>
            <select class="form-select" name="distribution_type" required>
              <option value="sms">SMS Only</option>
              <option value="email">Email Only</option>
              <option value="both">Both SMS & Email</option>
            </select>
            <small class="text-muted">
              SMS will be sent to parent phone numbers<br>
              Email will include PDF attachment
            </small>
          </div>

          <div class="mb-3">
            <label class="form-label">Filter by Class (Optional)</label>
            <select class="form-select" name="class_id">
              <option value="">All Classes in Configuration</option>
              @foreach($classes ?? [] as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="alert alert-warning">
            <i class='bx bx-time'></i>
            <strong>Important:</strong><br>
            - Notifications will be queued and processed automatically<br>
            - Parents without SMS/Email will be skipped<br>
            - You can check distribution status after submission<br>
            - Remember: Student Portal = Parents Portal
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class='bx bx-send'></i> Queue Distribution
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Distribution Status Button -->
<button type="button" class="btn btn-info btn-sm" onclick="checkDistributionStatus({{ $config->id }})">
  <i class='bx bx-info-circle'></i> Check Status
</button>

<script>
function checkDistributionStatus(configId) {
  fetch(`/reports/report-cards/distribution-status/${configId}`)
    .then(response => response.json())
    .then(data => {
      alert(`Distribution Status:\n\nTotal: ${data.total_queued}\nSent: ${data.sent}\nPending: ${data.pending}\nFailed: ${data.failed}`);
    })
    .catch(error => {
      alert('Error checking status');
    });
}
</script>
