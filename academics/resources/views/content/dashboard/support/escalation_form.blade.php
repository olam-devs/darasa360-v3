<!-- Escalation Section -->
<div class="card mt-3" id="escalationSection" style="display: none;">
  <div class="card-header bg-warning">
    <h6 class="mb-0 text-white">
      <i class='bx bx-up-arrow-alt'></i> Escalate Ticket
    </h6>
  </div>
  <div class="card-body">
    <form action="{{ route('support.escalate', $ticket->id) }}" method="POST">
      @csrf

      <div class="alert alert-info">
        <strong>Current Level:</strong> {{ ucfirst(str_replace('_', ' ', $ticket->escalation_level ?? 'system_admin')) }}<br>
        @if($ticket->is_escalated)
          <strong>Previously Escalated:</strong> {{ $ticket->escalated_at->diffForHumans() }}<br>
          <strong>Escalated By:</strong> {{ $ticket->escalatedBy->username ?? 'Unknown' }}
        @endif
      </div>

      <div class="mb-3">
        <label class="form-label">Escalation Reason *</label>
        <textarea class="form-control" name="escalation_reason" rows="3" required
                  placeholder="Explain why this ticket needs to be escalated..."></textarea>
        <small class="text-muted">Be specific about why this requires higher-level attention</small>
      </div>

      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="notify_sms" id="notifySms" checked>
          <label class="form-check-label" for="notifySms">Send SMS Notification</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="notify_email" id="notifyEmail" checked>
          <label class="form-check-label" for="notifyEmail">Send Email Notification</label>
        </div>
      </div>

      <div class="alert alert-warning">
        <i class='bx bx-info-circle'></i>
        <strong>Escalation Path:</strong><br>
        School Admin → System Admin → Super Admin<br>
        <small>This ticket will be escalated to the next level administrator who will be notified immediately.</small>
      </div>

      <button type="submit" class="btn btn-warning">
        <i class='bx bx-up-arrow-alt'></i> Escalate Ticket
      </button>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('escalationSection').style.display='none'">
        Cancel
      </button>
    </form>
  </div>
</div>
