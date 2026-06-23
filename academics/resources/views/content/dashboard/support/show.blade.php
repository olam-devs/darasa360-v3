@extends('layouts.modernLayout', [
    'pageTitle' => 'Support Ticket'
])

@section('title', 'Ticket Details')

@section('vendor-style')
<style>
  .ticket-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
    padding: 2rem;
  }

  .comment-box {
    background: #f8f9fa;
    border-left: 4px solid #00A8E8;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
  }

  [data-theme="dark"] .comment-box {
    background: var(--neutral-200);
    border-left-color: #60A5FA;
  }

  .comment-box.internal {
    background: #fff3cd;
    border-left-color: #ffc107;
  }

  [data-theme="dark"] .comment-box.internal {
    background: rgba(252, 211, 77, 0.2);
    border-left-color: #FCD34D;
  }

  .timeline-item {
    position: relative;
    padding-left: 2rem;
    padding-bottom: 1.5rem;
    border-left: 2px solid #e3e6f0;
  }

  .timeline-item:last-child {
    border-left-color: transparent;
  }

  .timeline-icon {
    position: absolute;
    left: -0.75rem;
    top: 0;
    width: 1.5rem;
    height: 1.5rem;
    background: #00A8E8;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
  }

  .info-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 8px;
    margin-right: 1rem;
    margin-bottom: 0.5rem;
  }

  [data-theme="dark"] .info-badge {
    background: var(--neutral-200);
  }
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Back Button -->
    <div class="mb-3">
      <a href="{{ route('support.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i>Back to Tickets
      </a>
    </div>

    <!-- Messages -->
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

    <!-- Ticket Header -->
    <div class="card mb-4">
      <div class="ticket-header">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h3 class="text-white mb-2">{{ $ticket->subject }}</h3>
            <p class="text-white-50 mb-3">{{ $ticket->ticket_number }}</p>
            <div class="d-flex flex-wrap">
              <div class="info-badge">
                <i class="bx bx-user me-2"></i>
                <span>{{ $ticket->user->username ?? 'Unknown' }}</span>
              </div>
              <div class="info-badge">
                <i class="{{ $ticket->module->icon }} me-2"></i>
                <span>{{ $ticket->module->name }}</span>
              </div>
              @if($ticket->sub_module)
              <div class="info-badge">
                <i class="bx bx-subdirectory-right me-2"></i>
                <span>{{ $ticket->sub_module }}</span>
              </div>
              @endif
              <div class="info-badge">
                <i class="bx bx-calendar me-2"></i>
                <span>{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
              </div>
            </div>
          </div>
          <div class="text-end">
            <span class="badge bg-{{ $ticket->priorityColor }} mb-2" style="font-size: 0.9rem;">
              {{ ucfirst($ticket->priority) }} Priority
            </span>
            <br>
            <span class="badge bg-{{ $ticket->statusColor }}" style="font-size: 0.9rem;">
              {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
            </span>
          </div>
        </div>
      </div>

      <div class="card-body">
        <h6 class="fw-bold mb-3">Description</h6>
        <div class="mb-4">
          {{ $ticket->description }}
        </div>

        @if($ticket->assigned_to)
        <div class="alert alert-info">
          <i class="bx bx-user-check me-2"></i>
          Assigned to: <strong>{{ $ticket->assignedTo->username ?? 'Unknown' }}</strong>
        </div>
        @endif

        @if($ticket->resolution_notes)
        <div class="alert alert-success">
          <h6 class="alert-heading"><i class="bx bx-check-circle me-2"></i>Resolution</h6>
          <p class="mb-0">{{ $ticket->resolution_notes }}</p>
          <hr>
          <small class="text-muted">
            Resolved by {{ $ticket->resolvedBy->username ?? 'Unknown' }} on {{ $ticket->resolved_at->format('M d, Y h:i A') }}
          </small>
        </div>
        @endif

        <!-- Admin Actions -->
        @if(in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin']))
        <div class="card bg-label-secondary mb-4">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Admin Actions</h6>
            <div class="row">
              <!-- Update Status -->
              <div class="col-md-4 mb-3">
                <form action="{{ route('support.status', $ticket->id) }}" method="POST">
                  @csrf
                  <label class="form-label">Update Status</label>
                  <select name="status" class="form-select mb-2" required>
                    <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="pending" {{ $ticket->status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                  </select>
                  <textarea name="resolution_notes" class="form-control mb-2" placeholder="Resolution notes (optional)"></textarea>
                  <button type="submit" class="btn btn-sm btn-primary w-100">Update Status</button>
                </form>
              </div>

              <!-- Assign Ticket -->
              <div class="col-md-4 mb-3">
                <form action="{{ route('support.assign', $ticket->id) }}" method="POST">
                  @csrf
                  <label class="form-label">Assign To</label>
                  <select name="assigned_to" class="form-select mb-2" required>
                    <option value="">Select Staff</option>
                    @foreach($supportStaff as $staff)
                    <option value="{{ $staff->id }}" {{ $ticket->assigned_to == $staff->id ? 'selected' : '' }}>
                      {{ $staff->username }}
                    </option>
                    @endforeach
                  </select>
                  <button type="submit" class="btn btn-sm btn-warning w-100">Assign</button>
                </form>
              </div>

              <!-- Update Priority -->
              <div class="col-md-4 mb-3">
                <form action="{{ route('support.priority', $ticket->id) }}" method="POST">
                  @csrf
                  <label class="form-label">Update Priority</label>
                  <select name="priority" class="form-select mb-2" required>
                    <option value="low" {{ $ticket->priority == 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ $ticket->priority == 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ $ticket->priority == 'high' ? 'selected' : '' }}>High</option>
                    <option value="critical" {{ $ticket->priority == 'critical' ? 'selected' : '' }}>Critical</option>
                  </select>
                  <button type="submit" class="btn btn-sm btn-danger w-100">Update Priority</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        @endif

        <!-- Escalation Toggle & Form (admin only) -->
        @if(in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin']) && $ticket->escalation_level !== 'super_admin')
        <button type="button" class="btn btn-outline-warning" onclick="document.getElementById('escalationSection').style.display = document.getElementById('escalationSection').style.display === 'none' ? 'block' : 'none'">
          <i class="bx bx-up-arrow-alt me-1"></i>Escalate Ticket
        </button>
        @include('content.dashboard.support.escalation_form')
        @endif
      </div>
    </div>

    <!-- Comments Section -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-message-dots me-2"></i>Comments</h5>
      </div>
      <div class="card-body">
        <!-- Add Comment Form -->
        <div class="mb-4">
          <form action="{{ route('support.comment', $ticket->id) }}" method="POST">
            @csrf
            <div class="mb-3">
              <label for="comment" class="form-label">Add a Comment</label>
              <textarea name="comment" id="comment" class="form-control" rows="4" placeholder="Type your comment here..." required></textarea>
            </div>

            @if(in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin']))
            <div class="form-check mb-3">
              <input type="checkbox" class="form-check-input" id="is_internal" name="is_internal" value="1">
              <label class="form-check-label" for="is_internal">
                Internal Note (Only visible to staff)
              </label>
            </div>
            @endif

            <button type="submit" class="btn btn-primary">
              <i class="bx bx-send me-1"></i>Post Comment
            </button>
          </form>
        </div>

        <hr>

        <!-- Comments List -->
        @if(count($ticket->comments) > 0)
        <h6 class="fw-bold mb-3">Activity Timeline</h6>
        <div class="timeline">
          @foreach($ticket->comments as $comment)
          @if(!$comment->is_internal || in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin']))
          <div class="timeline-item">
            <div class="timeline-icon">
              <i class="bx bx-message-square-dots"></i>
            </div>
            <div class="comment-box {{ $comment->is_internal ? 'internal' : '' }}">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <strong>{{ $comment->user->username ?? 'Unknown' }}</strong>
                  @if($comment->is_internal)
                  <span class="badge bg-warning ms-2">Internal Note</span>
                  @endif
                </div>
                <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
              </div>
              <p class="mb-0">{{ $comment->comment }}</p>
            </div>
          </div>
          @endif
          @endforeach
        </div>
        @else
        <div class="text-center py-4">
          <i class="bx bx-message" style="font-size: 3rem; color: #ddd;"></i>
          <p class="text-muted mt-2">No comments yet. Be the first to comment!</p>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
