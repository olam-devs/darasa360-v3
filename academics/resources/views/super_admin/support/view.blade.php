@extends('layouts/contentNavbarLayout')

@section('title', 'View Support Ticket')

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
          <span class="badge bg-{{ $statusColor }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span><br>
          <strong>Created:</strong> {{ \Carbon\Carbon::parse($ticket->created_at)->format('Y-m-d H:i') }}<br>
          <strong>Escalated:</strong> {{ \Carbon\Carbon::parse($ticket->escalated_at)->format('Y-m-d H:i') }}
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
        <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#resolveModal">
          <i class='bx bx-check-circle'></i> Resolve Ticket
        </button>
        @endif

        <a href="{{ route('super_admin.support.index') }}" class="btn btn-secondary w-100">
          <i class='bx bx-arrow-back'></i> Back to List
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Resolve Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Resolve Ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('super_admin.support.resolve', $ticket->id) }}" method="POST">
        @csrf
        <input type="hidden" name="school_id" value="{{ $school->id }}">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Resolution Notes</label>
            <textarea class="form-control" name="resolution_notes" rows="5" required placeholder="Describe how the issue was resolved..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Resolve Ticket</button>
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
