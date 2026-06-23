@extends('layouts/contentNavbarLayout')

@section('title', 'Support')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">My Support Tickets</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTicketModal">
          <i class='bx bx-plus'></i> Create Ticket
        </button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Ticket #</th>
                <th>Subject</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tickets as $ticket)
              <tr>
                <td><strong>{{ $ticket->ticket_number }}</strong></td>
                <td>{{ Str::limit($ticket->subject, 40) }}</td>
                <td>
                  @php
                    $priorityColor = match($ticket->priority) {
                      'low' => 'info',
                      'medium' => 'warning',
                      'high' => 'danger',
                      'critical' => 'dark',
                      default => 'secondary'
                    };
                  @endphp
                  <span class="badge bg-{{ $priorityColor }}">{{ ucfirst($ticket->priority) }}</span>
                </td>
                <td>
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
                </td>
                <td>{{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}</td>
                <td>
                  <a href="{{ route('school_admin.support.view', $ticket->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class='bx bx-show'></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center">No support tickets</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create Ticket Modal -->
<div class="modal fade" id="createTicketModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Support Ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('school_admin.support.create') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" class="form-control" name="subject" required>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Module</label>
              <select class="form-select" name="module_id" required>
                @foreach($modules as $module)
                <option value="{{ $module->id }}">{{ $module->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Priority</label>
              <select class="form-select" name="priority" required>
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="5" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Ticket</button>
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
