@extends('layouts/contentNavbarLayout')

@section('title', 'System Admin Dashboard')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body">
        <h4 class="card-title mb-1">Welcome, System Admin!</h4>
        <p class="card-text">Manage and support your assigned schools</p>
      </div>
    </div>
  </div>
</div>

<!-- Statistics Cards -->
<div class="row">
  <div class="col-lg-3 col-md-6 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-info">
            <p class="card-text mb-1">Assigned Schools</p>
            <h4 class="card-title mb-0">{{ $totalSchools }}</h4>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-primary rounded p-2">
              <i class='bx bx-building bx-lg'></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-info">
            <p class="card-text mb-1">Total Users</p>
            <h4 class="card-title mb-0">{{ number_format($totalUsers) }}</h4>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-success rounded p-2">
              <i class='bx bx-user bx-lg'></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-info">
            <p class="card-text mb-1">Escalated Tickets</p>
            <h4 class="card-title mb-0">{{ $escalatedTickets }}</h4>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-warning rounded p-2">
              <i class='bx bx-support bx-lg'></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-info">
            <p class="card-text mb-1">System Status</p>
            <h4 class="card-title mb-0"><span class="badge bg-success">Active</span></h4>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-info rounded p-2">
              <i class='bx bx-check-circle bx-lg'></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Assigned Schools -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">My Assigned Schools</h5>
        <a href="{{ route('system_admin.my_schools.index') }}" class="btn btn-sm btn-primary">
          <i class='bx bx-show'></i> View All
        </a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>School Name</th>
                <th>Location</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($assignedSchools as $school)
              <tr>
                <td>
                  <strong>{{ $school->name }}</strong><br>
                  <small class="text-muted">{{ $school->school_code }}</small>
                </td>
                <td>{{ $school->location->name ?? 'N/A' }}</td>
                <td>
                  @if($school->status === 'active')
                    <span class="badge bg-success">Active</span>
                  @else
                    <span class="badge bg-secondary">Inactive</span>
                  @endif
                </td>
                <td>
                  <a href="{{ route('system_admin.my_schools.view', $school->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class='bx bx-show'></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center">No schools assigned yet</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Support Tickets -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Escalated Tickets</h5>
        <a href="{{ route('system_admin.support.index') }}" class="btn btn-sm btn-primary">
          <i class='bx bx-support'></i> View All Tickets
        </a>
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
              @forelse($recentTickets as $ticket)
              <tr>
                <td>
                  <strong>{{ $ticket->ticket_number }}</strong><br>
                  <small class="text-muted">{{ $ticket->school_name ?? '' }}</small>
                </td>
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
                  <span class="badge bg-{{ $priorityColor }}">
                    {{ ucfirst($ticket->priority) }}
                  </span>
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
                  <span class="badge bg-{{ $statusColor }}">
                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                  </span>
                </td>
                <td>{{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}</td>
                <td>
                  <a href="{{ route('system_admin.support.view', $ticket->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class='bx bx-show'></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center">No escalated tickets</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
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

@if($errors->any())
<script>
  document.addEventListener('DOMContentLoaded', function() {
    alert('{{ $errors->first() }}');
  });
</script>
@endif

@endsection
