@extends('layouts/contentNavbarLayout')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body">
        <h3 class="card-title mb-1">Super Admin Dashboard</h3>
        <p class="card-text text-muted">Complete system oversight and management</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Statistics Cards -->
  <div class="col-lg-3 col-md-6 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-info">
            <p class="card-text mb-1">Total Schools</p>
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
            <p class="card-text mb-1">Monthly Revenue</p>
            <h4 class="card-title mb-0">TZS {{ number_format($totalRevenue, 2) }}</h4>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-warning rounded p-2">
              <i class='bx bx-dollar bx-lg'></i>
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
            <p class="card-text mb-1">Active Schools</p>
            <h4 class="card-title mb-0">{{ $activeSchools }}</h4>
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

<!-- Escalated Tickets -->
@if($escalatedTickets->count() > 0)
<div class="row mb-4">
  <div class="col-12">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-white"><i class='bx bx-error-circle'></i> Escalated Support Tickets</h5>
        <span class="badge bg-white text-danger">{{ $escalatedTickets->count() }}</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Ticket ID</th>
                <th>School</th>
                <th>Module</th>
                <th>Subject</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Escalated</th>
                <th>Reason</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($escalatedTickets as $ticket)
              <tr>
                <td><strong>#{{ $ticket->id }}</strong></td>
                <td>{{ $ticket->school_name }}</td>
                <td>
                  <span class="badge bg-label-primary">{{ $ticket->module_name ?? 'General' }}</span>
                </td>
                <td>{{ Str::limit($ticket->subject, 40) }}</td>
                <td>
                  @if($ticket->priority === 'urgent')
                    <span class="badge bg-danger">Urgent</span>
                  @elseif($ticket->priority === 'high')
                    <span class="badge bg-warning">High</span>
                  @else
                    <span class="badge bg-info">{{ ucfirst($ticket->priority) }}</span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-{{ $ticket->status === 'open' ? 'danger' : 'warning' }}">
                    {{ ucfirst($ticket->status) }}
                  </span>
                </td>
                <td>
                  <small class="text-muted">
                    {{ \Carbon\Carbon::parse($ticket->escalated_at)->diffForHumans() }}
                  </small>
                </td>
                <td>
                  <small>{{ Str::limit($ticket->escalation_reason ?? 'N/A', 30) }}</small>
                </td>
                <td>
                  <a href="{{ route('super_admin.support.view', $ticket->id) }}?db={{ $ticket->tenant_db }}&school={{ $ticket->school_id }}"
                     class="btn btn-sm btn-outline-primary">
                    <i class='bx bx-show'></i> View
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

<!-- Schools Table -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Schools</h5>
        <a href="{{ route('super_admin.schools.create') }}" class="btn btn-primary btn-sm">
          <i class='bx bx-plus'></i> Create New School
        </a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>School Name</th>
                <th>Location</th>
                <th>Package</th>
                <th>Total Users</th>
                <th>Active Users</th>
                <th>Monthly Charge</th>
                <th>Billing Status</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($schools as $school)
              <tr>
                <td>
                  <strong>{{ $school->name }}</strong><br>
                  <small class="text-muted">{{ $school->school_code }}</small>
                </td>
                <td>{{ $school->location->name ?? 'N/A' }}</td>
                <td>
                  <span class="badge bg-label-primary">{{ ucfirst($school->package) }}</span>
                </td>
                <td>{{ $school->total_users }}</td>
                <td>{{ $school->active_users }}</td>
                <td>TZS {{ number_format($school->monthly_charge, 2) }}</td>
                <td>
                  @if($school->billing_status === 'active')
                    <span class="badge bg-success">Active</span>
                  @elseif($school->billing_status === 'suspended')
                    <span class="badge bg-warning">Suspended</span>
                  @else
                    <span class="badge bg-danger">Overdue</span>
                  @endif
                </td>
                <td>
                  @if($school->status === 'active')
                    <span class="badge bg-success">Active</span>
                  @else
                    <span class="badge bg-secondary">Inactive</span>
                  @endif
                </td>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                      <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('super_admin.schools.view', $school->id) }}">
                        <i class="bx bx-show me-1"></i> View Details
                      </a>
                      <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#assignAdminModal{{ $school->id }}">
                        <i class="bx bx-user-plus me-1"></i> Assign Admin
                      </a>
                      <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#billingModal{{ $school->id }}">
                        <i class="bx bx-dollar me-1"></i> Update Billing
                      </a>
                    </div>
                  </div>
                </td>
              </tr>

              <!-- Assign Admin Modal -->
              <div class="modal fade" id="assignAdminModal{{ $school->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Assign System Admin to {{ $school->name }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('super_admin.schools.assign_admin', $school->id) }}" method="POST">
                      @csrf
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Admin Name</label>
                          <input type="text" class="form-control" name="admin_name" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Email</label>
                          <input type="email" class="form-control" name="admin_email" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Phone Number</label>
                          <input type="text" class="form-control" name="admin_phone" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Username</label>
                          <input type="text" class="form-control" name="admin_username" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Password</label>
                          <input type="password" class="form-control" name="admin_password" required>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Admin</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Billing Modal -->
              <div class="modal fade" id="billingModal{{ $school->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Update Billing for {{ $school->name }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('super_admin.schools.update_billing', $school->id) }}" method="POST">
                      @csrf
                      @method('PUT')
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Price Per User (TZS)</label>
                          <input type="number" step="0.01" class="form-control" name="price_per_user" value="{{ $school->price_per_user }}" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Billing Status</label>
                          <select class="form-select" name="billing_status" required>
                            <option value="active" {{ $school->billing_status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ $school->billing_status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="overdue" {{ $school->billing_status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                          </select>
                        </div>
                        <div class="alert alert-info">
                          <strong>Current Stats:</strong><br>
                          Active Users: {{ $school->active_users }}<br>
                          Monthly Charge: TZS {{ number_format($school->monthly_charge, 2) }}
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Billing</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              @empty
              <tr>
                <td colspan="9" class="text-center">No schools found</td>
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
