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
          <span class="badge bg-{{ $statusColor }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span><br>
          <strong>Created:</strong> {{ \Carbon\Carbon::parse($ticket->created_at)->format('Y-m-d H:i') }}
        </div>

        <hr>

        <div class="mb-3">
          <h6>Description:</h6>
          <p>{{ $ticket->description }}</p>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Actions</h5>
      </div>
      <div class="card-body">
        @if($ticket->status !== 'resolved' && !$ticket->is_escalated)
        <button type="button" class="btn btn-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#escalateModal">
          <i class='bx bx-up-arrow-alt'></i> Escalate to System Admin
        </button>
        @endif

        @if($ticket->is_escalated)
        <div class="alert alert-info">
          <strong>Escalated</strong><br>
          This ticket has been escalated to System Admin.
        </div>
        @endif

        <a href="{{ route('school_admin.support.index') }}" class="btn btn-secondary w-100">
          <i class='bx bx-arrow-back'></i> Back to List
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Escalate Modal -->
<div class="modal fade" id="escalateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Escalate to System Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('school_admin.support.escalate', $ticket->id) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Escalation Reason</label>
            <textarea class="form-control" name="escalation_reason" rows="4" required placeholder="Explain why you need to escalate this ticket..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Escalate Ticket</button>
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
