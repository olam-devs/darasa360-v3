@extends('layouts/contentNavbarLayout')

@section('title', 'School Calendar')

@section('vendor-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">
@endsection

@section('vendor-script')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class='bx bx-calendar'></i> School Calendar
          @if($activeCalendar)
            <span class="badge bg-label-primary ms-2">{{ $activeCalendar->academic_year }}</span>
          @endif
        </h5>
        <div>
          <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#createEventModal">
            <i class='bx bx-plus'></i> Add Event
          </button>
          <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#createCalendarModal">
            <i class='bx bx-calendar-plus'></i> New Calendar
          </button>
        </div>
      </div>
      <div class="card-body">
        <div id="calendar"></div>
      </div>
    </div>
  </div>
</div>

<!-- Upcoming Events -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Upcoming Events</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Event</th>
                <th>Type</th>
                <th>Date</th>
                <th>Time</th>
                <th>Location</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($upcomingEvents as $event)
              <tr>
                <td>
                  <strong>{{ $event->title }}</strong>
                  @if($event->description)
                    <br><small class="text-muted">{{ Str::limit($event->description, 50) }}</small>
                  @endif
                </td>
                <td>
                  <span class="badge bg-label-{{ $event->event_type === 'exam' ? 'danger' : ($event->event_type === 'holiday' ? 'success' : 'primary') }}">
                    {{ ucfirst($event->event_type) }}
                  </span>
                </td>
                <td>{{ $event->start_date->format('M d, Y') }}</td>
                <td>{{ $event->start_time ? $event->start_time : 'All Day' }}</td>
                <td>{{ $event->location ?? '-' }}</td>
                <td>
                  <button class="btn btn-sm btn-icon btn-primary" onclick="viewEvent({{ $event->id }})">
                    <i class='bx bx-show'></i>
                  </button>
                  <button class="btn btn-sm btn-icon btn-warning" onclick="editEvent({{ $event->id }})">
                    <i class='bx bx-edit'></i>
                  </button>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center">No upcoming events</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create Calendar Modal -->
<div class="modal fade" id="createCalendarModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Academic Calendar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('calendar.create') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Academic Year *</label>
            <input type="text" class="form-control" name="academic_year" placeholder="2024/2025" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Start Date *</label>
            <input type="date" class="form-control" name="start_date" required>
          </div>
          <div class="mb-3">
            <label class="form-label">End Date *</label>
            <input type="date" class="form-control" name="end_date" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Number of Terms *</label>
            <select class="form-select" name="term_count" required>
              <option value="2">2 Terms</option>
              <option value="3" selected>3 Terms</option>
              <option value="4">4 Terms</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Calendar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('calendar.events.create') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Event Title *</label>
              <input type="text" class="form-control" name="title" required>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Event Type *</label>
              <select class="form-select" name="event_type" required>
                <option value="exam">Exam</option>
                <option value="meeting">Meeting</option>
                <option value="holiday">Holiday</option>
                <option value="sport">Sport</option>
                <option value="cultural">Cultural</option>
                <option value="academic">Academic</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Location</label>
              <input type="text" class="form-control" name="location">
            </div>
            <div class="col-md-6">
              <label class="form-label">Start Date *</label>
              <input type="date" class="form-control" name="start_date" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date</label>
              <input type="date" class="form-control" name="end_date">
            </div>
            <div class="col-md-6">
              <label class="form-label">Start Time</label>
              <input type="time" class="form-control" name="start_time">
            </div>
            <div class="col-md-6">
              <label class="form-label">End Time</label>
              <input type="time" class="form-control" name="end_time">
            </div>
            <div class="col-12">
              <hr>
              <h6>Notifications</h6>
            </div>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="notify_staff" id="notifyStaff">
                <label class="form-check-label" for="notifyStaff">Notify Staff</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="notify_students" id="notifyStudents">
                <label class="form-check-label" for="notifyStudents">Notify Students</label>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="notify_parents" id="notifyParents">
                <label class="form-check-label" for="notifyParents">Notify Parents</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Reminder (days before)</label>
              <input type="number" class="form-control" name="reminder_days_before" min="1" placeholder="e.g., 3">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Event</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listWeek'
    },
    events: '{{ route("calendar.events.json") }}',
    eventClick: function(info) {
      alert('Event: ' + info.event.title);
    }
  });
  calendar.render();
});

function viewEvent(id) {
  alert('View event ' + id);
}

function editEvent(id) {
  alert('Edit event ' + id);
}
</script>

@endsection
