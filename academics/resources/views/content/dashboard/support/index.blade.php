@extends('layouts.modernLayout', [
    'pageTitle' => 'Support'
])

@section('title', 'Support')

@section('vendor-style')
<style>
  .module-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .module-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }

  .module-badge.active {
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transform: scale(1.05);
  }

  .ticket-card {
    transition: all 0.3s ease;
    border-left: 4px solid #00A8E8;
  }

  .ticket-card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }

  .priority-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
  }

  .status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
  }

  .stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
  }

  .stats-card.open {
    background: linear-gradient(135deg, #00A8E8 0%, #0086B3 100%);
  }

  .stats-card.in-progress {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  }

  .stats-card.resolved {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  }
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light">Dashboard /</span> Support
    </h4>

    <!-- Success/Error Messages -->
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

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Statistics (for admins only) -->
    @if($stats)
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="stats-card">
          <h6 class="text-white mb-2">Total Tickets</h6>
          <h2 class="text-white fw-bold mb-0">{{ $stats['total'] }}</h2>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stats-card open">
          <h6 class="text-white mb-2">Open</h6>
          <h2 class="text-white fw-bold mb-0">{{ $stats['open'] }}</h2>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stats-card in-progress">
          <h6 class="text-white mb-2">In Progress</h6>
          <h2 class="text-white fw-bold mb-0">{{ $stats['in_progress'] }}</h2>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stats-card resolved">
          <h6 class="text-white mb-2">Resolved</h6>
          <h2 class="text-white fw-bold mb-0">{{ $stats['resolved'] }}</h2>
        </div>
      </div>
    </div>
    @endif

    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-3">
        <div class="card mb-4">
          <div class="card-body">
            <button class="btn btn-primary w-100 mb-4" data-bs-toggle="modal" data-bs-target="#createTicketModal">
              <i class="bx bx-plus me-2"></i>Create a Ticket
            </button>

            <h6 class="fw-bold mb-3">Status</h6>
            <div class="mb-3">
              <div class="form-check mb-2">
                <input class="form-check-input status-filter" type="radio" name="statusFilter" value="" id="statusAll" checked>
                <label class="form-check-label" for="statusAll">All</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input status-filter" type="radio" name="statusFilter" value="open" id="statusOpen">
                <label class="form-check-label" for="statusOpen">Open</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input status-filter" type="radio" name="statusFilter" value="in_progress" id="statusInProgress">
                <label class="form-check-label" for="statusInProgress">In Progress</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input status-filter" type="radio" name="statusFilter" value="pending" id="statusPending">
                <label class="form-check-label" for="statusPending">Pending</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input status-filter" type="radio" name="statusFilter" value="resolved" id="statusResolved">
                <label class="form-check-label" for="statusResolved">Resolved</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input status-filter" type="radio" name="statusFilter" value="closed" id="statusClosed">
                <label class="form-check-label" for="statusClosed">Closed</label>
              </div>
            </div>

            <hr>

            <h6 class="fw-bold mb-3">Modules</h6>
            <div class="mb-3">
              @foreach($modules as $module)
              <div class="module-badge mb-2" style="background-color: {{ $module->color }}20; color: {{ $module->color }}; border: 1px solid {{ $module->color }}40;" data-module-id="{{ $module->id }}">
                <i class="{{ $module->icon }} me-2"></i>{{ $module->name }}
                <span class="badge rounded-pill ms-2" style="background-color: {{ $module->color }};">{{ $module->activeTicketsCount }}</span>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="col-md-9">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ in_array($userRole, ['Headmaster', 'Academic', 'Owner', 'Admin']) ? 'All Tickets' : 'My Tickets' }}</h5>
            <div>
              <span class="badge bg-label-primary" id="ticketCount">{{ count($tickets) }} tickets</span>
            </div>
          </div>

          <div class="card-body p-0">
            @if(count($tickets) == 0)
            <div class="text-center py-5">
              <i class="bx bx-inbox" style="font-size: 4rem; color: #ddd;"></i>
              <p class="text-muted mt-3">No tickets found</p>
            </div>
            @else
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Ticket #</th>
                    <th>Subject</th>
                    <th>Module</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="ticketsTableBody">
                  @foreach($tickets as $ticket)
                  <tr class="ticket-row" data-status="{{ $ticket->status }}" data-module="{{ $ticket->module_id }}">
                    <td>
                      <a href="{{ route('support.show', $ticket->id) }}" class="fw-bold text-primary">
                        {{ $ticket->ticket_number }}
                      </a>
                    </td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="fw-semibold">{{ Str::limit($ticket->subject, 40) }}</span>
                        <small class="text-muted">by {{ $ticket->user->username ?? 'Unknown' }}</small>
                      </div>
                    </td>
                    <td>
                      <span class="badge" style="background-color: {{ $ticket->module->color }}20; color: {{ $ticket->module->color }};">
                        <i class="{{ $ticket->module->icon }} me-1"></i>{{ $ticket->module->name }}
                      </span>
                    </td>
                    <td>
                      <span class="priority-badge badge bg-{{ $ticket->priorityColor }}">
                        {{ ucfirst($ticket->priority) }}
                      </span>
                    </td>
                    <td>
                      <span class="status-badge badge bg-{{ $ticket->statusColor }}">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                      </span>
                    </td>
                    <td>
                      <small>{{ $ticket->created_at->format('M d, Y') }}</small><br>
                      <small class="text-muted">{{ $ticket->created_at->format('h:i A') }}</small>
                    </td>
                    <td>
                      <a href="{{ route('support.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-show me-1"></i>View
                      </a>
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
  </div>
</div>

@include('content.dashboard.support.modals.create')

@endsection

@section('page-script')
<script>
  // Status filtering
  document.querySelectorAll('.status-filter').forEach(radio => {
    radio.addEventListener('change', function() {
      filterTickets();
    });
  });

  // Module filtering
  let selectedModule = null;
  document.querySelectorAll('.module-badge').forEach(badge => {
    badge.addEventListener('click', function() {
      const moduleId = this.getAttribute('data-module-id');

      // Remove active class from all badges
      document.querySelectorAll('.module-badge').forEach(b => b.classList.remove('active'));

      // Toggle selection
      if (selectedModule === moduleId) {
        selectedModule = null;
      } else {
        selectedModule = moduleId;
        this.classList.add('active');
      }

      filterTickets();
    });
  });

  function filterTickets() {
    const selectedStatus = document.querySelector('.status-filter:checked').value;
    const rows = document.querySelectorAll('.ticket-row');
    let visibleCount = 0;

    rows.forEach(row => {
      const rowStatus = row.getAttribute('data-status');
      const rowModule = row.getAttribute('data-module');

      let statusMatch = !selectedStatus || rowStatus === selectedStatus;
      let moduleMatch = !selectedModule || rowModule === selectedModule;

      if (statusMatch && moduleMatch) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    document.getElementById('ticketCount').textContent = visibleCount + ' tickets';
  }
</script>
@endsection
