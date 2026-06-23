@extends('layouts/contentNavbarLayout')

@section('title', 'View Ticket')

@section('content')
<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Ticket #{{ $ticket->ticket_number }}</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <strong>School:</strong> {{ $school->name }}<br>
          <strong>Subject:</strong> {{ $ticket->subject }}<br>
          <strong>Priority:</strong>
          @php
            $priorityColor = match($ticket->priority) {
              'low' => 'info',
              'medium' => 'warning',
              'high' => 'danger',
              'critical' => 'dark',
              default => 'secondary'
            };
          @endphp
          <span class="badge bg-{{ $priorityColor }}">{{ ucfirst($ticket->priority) }}</span><br>
          <strong>Status:</strong>
          @php
            $statusColor = match($ticket->status) {
              'open' => 'primary',
              'in_progress' => 'warning',
              'pending' => 'info',
              'resolved' => 'success',
              'closed' => 'secondary',
              default => 'secondary'
            };
          @endphp
          <span class="badge bg-{{ $statusColor }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
        </div>

        <hr>

        <div class="mb-3">
          <h6>Description:</h6>
          <p>{{ $ticket->description }}</p>
        </div>

        @if($ticket->escalation_reason)
        <div class="mb-3">
          <h6>Escalation Reason:</h6>
          <p class="text-warning">{{ $ticket->escalation_reason }}</p>
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Actions</h5>
      </div>
      <div class="card-body">
        @if($ticket->status !== 'resolved')
        <button type="button" class="btn btn-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
          <i class='bx bx-edit'></i> Update Status
        </button>

        <button type="button" class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#escalateModal">
          <i class='bx bx-up-arrow-alt'></i> Escalate to Super Admin
        </button>
        @endif

        <a href="{{ route('system_admin.support.index') }}" class="btn btn-secondary w-100">
          <i class='bx bx-arrow-back'></i> Back to List
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Ticket Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('system_admin.support.update_status', $ticket->id) }}" method="POST">
        @csrf
        <input type="hidden" name="school_id" value="{{ $school->id }}">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" required>
              <option value="in_progress">In Progress</option>
              <option value="pending">Pending</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Comment</label>
            <textarea class="form-control" name="comment" rows="3" placeholder="Add a comment..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Escalate Modal -->
<div class="modal fade" id="escalateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Escalate to Super Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('system_admin.support.escalate', $ticket->id) }}" method="POST">
        @csrf
        <input type="hidden" name="school_id" value="{{ $school->id }}">
        <input type="hidden" name="super_admin_id" value="2">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Escalation Reason</label>
            <textarea class="form-control" name="escalation_reason" rows="4" required placeholder="Explain why this ticket needs to be escalated..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Escalate</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if(session('success'))
<script>
  document.addEventListener('DOMContentLoaded', function() {
    alert('{{ session('success') }}');
  });
</script>
@endif

@endsection
