@extends('layouts/contentNavbarLayout')

@section('title', 'School Admin Dashboard')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4" role="alert">
  {{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible mb-4" role="alert">
  {{ $errors->first() }}
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Welcome Banner -->
<div class="row">
  <div class="col-12 mb-4">
    <div class="card bg-primary text-white">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h4 class="card-title text-white mb-1">Welcome, {{ session('username') }}!</h4>
          <p class="mb-0 opacity-75">{{ $school->name }} &mdash; Admin Portal</p>
        </div>
        <i class='bx bx-building-house bx-lg opacity-50'></i>
      </div>
    </div>
  </div>
</div>

<!-- Stat Cards Row 1 -->
<div class="row mb-4">
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="card-text mb-1 text-muted small">Total Students</p>
            <h4 class="mb-0">{{ number_format($totalStudents) }}</h4>
          </div>
          <span class="badge bg-label-primary rounded p-2">
            <i class='bx bx-group bx-lg'></i>
          </span>
        </div>
        <a href="/owner/students" class="small mt-2 d-block">View Students &rarr;</a>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="card-text mb-1 text-muted small">Total Staff</p>
            <h4 class="mb-0">{{ number_format($totalStaff) }}</h4>
          </div>
          <span class="badge bg-label-success rounded p-2">
            <i class='bx bx-user bx-lg'></i>
          </span>
        </div>
        <a href="{{ route('school_admin.staff.index') }}" class="small mt-2 d-block">Manage Staff &rarr;</a>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="card-text mb-1 text-muted small">Classes</p>
            <h4 class="mb-0">{{ number_format($totalClasses) }}</h4>
          </div>
          <span class="badge bg-label-info rounded p-2">
            <i class='bx bx-chalkboard bx-lg'></i>
          </span>
        </div>
        <a href="/owner/classes" class="small mt-2 d-block">Manage Classes &rarr;</a>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="card-text mb-1 text-muted small">Subjects</p>
            <h4 class="mb-0">{{ number_format($totalSubjects) }}</h4>
          </div>
          <span class="badge bg-label-warning rounded p-2">
            <i class='bx bx-book bx-lg'></i>
          </span>
        </div>
        <a href="/owner/subjects/" class="small mt-2 d-block">Manage Subjects &rarr;</a>
      </div>
    </div>
  </div>
</div>

<!-- Stat Cards Row 2 -->
<div class="row mb-4">
  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="card-text mb-1 text-muted small">Open Tickets</p>
            <h4 class="mb-0">{{ $openTickets }}</h4>
          </div>
          <span class="badge bg-label-danger rounded p-2">
            <i class='bx bx-support bx-lg'></i>
          </span>
        </div>
        <a href="{{ route('school_admin.support.index') }}" class="small mt-2 d-block">View Tickets &rarr;</a>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="card-text mb-1 text-muted small">Upcoming Events</p>
            <h4 class="mb-0">{{ $upcomingEvents->count() }}</h4>
          </div>
          <span class="badge bg-label-secondary rounded p-2">
            <i class='bx bx-calendar-event bx-lg'></i>
          </span>
        </div>
        <a href="/calendar" class="small mt-2 d-block">View Calendar &rarr;</a>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="card-text mb-1 text-muted small">Revenue Collected</p>
            <h4 class="mb-0">TZS {{ number_format($totalRevenue) }}</h4>
          </div>
          <span class="badge bg-label-success rounded p-2">
            <i class='bx bx-money bx-lg'></i>
          </span>
        </div>
        <span class="small text-muted mt-2 d-block">Pending: TZS {{ number_format(max(0, $pendingFees)) }}</span>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="card-text mb-1 text-muted small">School</p>
            <h5 class="mb-0">{{ Str::limit($school->name, 18) }}</h5>
          </div>
          <span class="badge bg-label-primary rounded p-2">
            <i class='bx bx-buildings bx-lg'></i>
          </span>
        </div>
        <a href="{{ route('school_admin.overview') }}" class="small mt-2 d-block">School Overview &rarr;</a>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Quick Actions</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-lg-2 col-md-3 col-4">
            <a href="/owner/students" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-group bx-lg mb-1'></i>
              <span class="small">Students</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="{{ route('school_admin.staff.index') }}" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-user bx-lg mb-1'></i>
              <span class="small">Staff</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="/owner/classes" class="btn btn-outline-info w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-chalkboard bx-lg mb-1'></i>
              <span class="small">Classes</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="/owner/subjects/" class="btn btn-outline-warning w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-book bx-lg mb-1'></i>
              <span class="small">Subjects</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="/owner/promotions" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-trending-up bx-lg mb-1'></i>
              <span class="small">Promotions</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="/sms/send" class="btn btn-outline-danger w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-message-rounded-dots bx-lg mb-1'></i>
              <span class="small">Bulk SMS</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="/owner/attendance" class="btn btn-outline-info w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-check-square bx-lg mb-1'></i>
              <span class="small">Attendance</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="/owner/grades/" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-trophy bx-lg mb-1'></i>
              <span class="small">Grades</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="/calendar" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-calendar bx-lg mb-1'></i>
              <span class="small">Calendar</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="/owner/passwords/" class="btn btn-outline-warning w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-lock-alt bx-lg mb-1'></i>
              <span class="small">Passwords</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="{{ route('school_admin.support.index') }}" class="btn btn-outline-danger w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-support bx-lg mb-1'></i>
              <span class="small">Support</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-3 col-4">
            <a href="{{ route('school_admin.system_settings') }}" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center">
              <i class='bx bx-cog bx-lg mb-1'></i>
              <span class="small">Settings</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bottom Row: Tickets + Events + Announcements -->
<div class="row">

  <!-- Recent Tickets -->
  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Support Tickets</h5>
        <div>
          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTicketModal">
            <i class='bx bx-plus'></i> New
          </button>
          <a href="{{ route('school_admin.support.index') }}" class="btn btn-sm btn-outline-primary ms-1">View All</a>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Ticket #</th>
                <th>Subject</th>
                <th>Status</th>
                <th class="pe-3">Date</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentTickets as $ticket)
              <tr>
                <td class="ps-3"><strong>{{ $ticket->ticket_number }}</strong></td>
                <td>{{ Str::limit($ticket->subject, 30) }}</td>
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
                <td class="pe-3 text-muted small">{{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">No tickets yet</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Events & Announcements -->
  <div class="col-lg-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Upcoming Events</h5>
        <a href="/calendar" class="btn btn-sm btn-outline-primary">Calendar</a>
      </div>
      <div class="card-body">
        @forelse($upcomingEvents as $event)
        <div class="d-flex align-items-start mb-3">
          <span class="badge bg-label-primary me-3 p-2 mt-1"><i class='bx bx-calendar-event'></i></span>
          <div>
            <div class="fw-semibold">{{ $event->title ?? $event->name ?? 'Event' }}</div>
            <small class="text-muted">{{ \Carbon\Carbon::parse($event->start_date)->format('M d, Y') }}</small>
          </div>
        </div>
        @empty
        <p class="text-muted text-center py-3">No upcoming events</p>
        @endforelse

        @if($announcements->count())
        <hr>
        <h6 class="text-muted mb-3">Recent Announcements</h6>
        @foreach($announcements as $ann)
        <div class="d-flex align-items-start mb-2">
          <span class="badge bg-label-info me-2 p-2 mt-1"><i class='bx bx-bell'></i></span>
          <div>
            <div class="small fw-semibold">{{ Str::limit($ann->title ?? $ann->subject ?? 'Announcement', 50) }}</div>
            <small class="text-muted">{{ \Carbon\Carbon::parse($ann->created_at)->diffForHumans() }}</small>
          </div>
        </div>
        @endforeach
        @endif
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
                <option value="">Select Module</option>
                <option value="1">Academic Management</option>
                <option value="2">Student Management</option>
                <option value="3">Staff Management</option>
                <option value="4">Finance & Billing</option>
                <option value="5">Attendance</option>
                <option value="6">Exams & Results</option>
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
            <textarea class="form-control" name="description" rows="4" required></textarea>
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

@endsection
