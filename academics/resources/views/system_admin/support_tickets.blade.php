@extends('layouts/contentNavbarLayout')

@section('title', 'Support Tickets')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Escalated Support Tickets</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Tickets escalated to you from School Admins</p>

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Ticket #</th>
                <th>School</th>
                <th>Subject</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Escalated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tickets as $ticket)
              <tr>
                <td><strong>{{ $ticket->ticket_number }}</strong></td>
                <td>{{ $ticket->school_name }}</td>
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
                <td>{{ \Carbon\Carbon::parse($ticket->escalated_at)->diffForHumans() }}</td>
                <td>
                  <a href="{{ route('system_admin.support.view', $ticket->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class='bx bx-show'></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center">No escalated tickets</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
