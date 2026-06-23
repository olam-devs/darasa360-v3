@extends('layouts.modernLayout', [
    'pageTitle' => 'Escalated Tickets'
])

@section('title', 'Escalated Support Tickets')

@section('vendor-style')
<style>
  .escalation-badge {
    font-size: 0.8rem;
    padding: 0.35rem 0.8rem;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .escalation-badge.system-admin {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
  }

  .escalation-badge.super-admin {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
  }

  .ticket-card {
    transition: all 0.3s ease;
    border-left: 4px solid #ff6b6b;
  }

  .ticket-card:hover {
    transform: translateX(5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
  }

  .escalation-timeline {
    position: relative;
    padding-left: 30px;
    border-left: 2px solid #e9ecef;
  }

  .escalation-timeline::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ff6b6b;
  }

  .priority-critical {
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.7;
    }
  }
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light">Support /</span> Escalated Tickets
    </h4>

    <!-- Alert Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #ff6b6b;">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">Total Escalated</h6>
                <h3 class="mb-0 text-danger">{{ $tickets->count() }}</h3>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-danger">
                  <i class="bx bx-trending-up bx-md"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #ffa94d;">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">To System Admin</h6>
                <h3 class="mb-0 text-warning">{{ $tickets->where('escalation_level', 'system_admin')->count() }}</h3>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-warning">
                  <i class="bx bx-user-circle bx-md"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card" style="border-left: 4px solid #ff6347;">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-1">To Super Admin</h6>
                <h3 class="mb-0 text-danger">{{ $tickets->where('escalation_level', 'super_admin')->count() }}</h3>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-danger">
                  <i class="bx bx-shield bx-md"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Escalated Tickets List -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="bx bx-trending-up me-2"></i>Escalated Tickets
        </h5>
        <a href="{{ route('support.index') }}" class="btn btn-sm btn-outline-primary">
          <i class="bx bx-arrow-back me-1"></i>Back to All Tickets
        </a>
      </div>

      <div class="card-body">
        @if($tickets->isEmpty())
        <div class="text-center py-5">
          <i class="bx bx-check-shield" style="font-size: 5rem; color: #d1d5db;"></i>
          <h5 class="text-muted mt-3">No Escalated Tickets</h5>
          <p class="text-muted">All support tickets are being handled at the appropriate level.</p>
        </div>
        @else
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Ticket #</th>
                <th>Subject</th>
                <th>Priority</th>
                <th>Escalation Level</th>
                <th>Escalated By</th>
                <th>Escalated At</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($tickets as $ticket)
              <tr class="ticket-row {{ $ticket->priority === 'critical' ? 'priority-critical' : '' }}">
                <td>
                  <a href="{{ route('support.show', $ticket->id) }}" class="fw-bold text-danger">
                    #{{ $ticket->ticket_number }}
                  </a>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ Str::limit($ticket->subject, 35) }}</span>
                    <small class="text-muted">{{ $ticket->module->name ?? 'N/A' }}</small>
                  </div>
                </td>
                <td>
                  <span class="badge bg-{{ $ticket->priority === 'critical' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : 'secondary') }}">
                    {{ ucfirst($ticket->priority) }}
                  </span>
                </td>
                <td>
                  <span class="escalation-badge {{ str_replace('_', '-', $ticket->escalation_level) }}">
                    {{ ucfirst(str_replace('_', ' ', $ticket->escalation_level)) }}
                  </span>
                </td>
                <td>
                  @if($ticket->escalatedBy)
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-xs me-2">
                      <span class="avatar-initial rounded-circle bg-label-primary">
                        {{ substr($ticket->escalatedBy->username, 0, 1) }}
                      </span>
                    </div>
                    <span>{{ $ticket->escalatedBy->username }}</span>
                  </div>
                  @else
                  <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  <small>{{ $ticket->escalated_at ? $ticket->escalated_at->format('M d, Y') : '-' }}</small><br>
                  <small class="text-muted">{{ $ticket->escalated_at ? $ticket->escalated_at->format('h:i A') : '' }}</small>
                </td>
                <td>
                  <small class="text-muted" data-bs-toggle="tooltip" title="{{ $ticket->escalation_reason }}">
                    {{ Str::limit($ticket->escalation_reason, 30) }}
                  </small>
                </td>
                <td>
                  <span class="badge bg-{{ $ticket->status === 'resolved' ? 'success' : ($ticket->status === 'in_progress' ? 'primary' : 'secondary') }}">
                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                  </span>
                </td>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                      <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                      <a class="dropdown-item" href="{{ route('support.show', $ticket->id) }}">
                        <i class="bx bx-show me-2"></i>View Details
                      </a>
                      @if(in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin']))
                      <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal{{ $ticket->id }}">
                        <i class="bx bx-edit me-2"></i>Update Status
                      </a>
                      @endif
                    </div>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>
    </div>

  </div>
</div>

<!-- Modals for updating status would go here -->
@foreach($tickets as $ticket)
<div class="modal fade" id="updateStatusModal{{ $ticket->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Ticket Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('support.status', $ticket->id) }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
              <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
              <option value="pending" {{ $ticket->status === 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
              <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Resolution Notes (Optional)</label>
            <textarea name="resolution_notes" class="form-control" rows="3" placeholder="Describe how the issue was resolved..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@endsection

@section('page-script')
<script>
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })
</script>
@endsection
