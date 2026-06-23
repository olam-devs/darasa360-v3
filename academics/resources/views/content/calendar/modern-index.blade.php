@extends('layouts.modernLayout', ['pageTitle' => 'School Calendar', 'pageSubtitle' => 'View and manage school events, exams, and activities'])

@section('content')

{{-- Success/Error Messages --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <strong>Success!</strong> {{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <strong>Error!</strong> {{ session('error') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <strong>Validation Errors:</strong>
  <ul class="mb-0">
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
  {{-- Left Side: Calendar and Filters --}}
  <div class="col-lg-8">

    {{-- Filter Bar --}}
    <div class="modern-filter-bar">
      <div class="modern-filter-group">
        <button class="modern-filter-btn active" data-filter="all">All Events</button>
        <button class="modern-filter-btn" data-filter="week">This Week</button>
        <button class="modern-filter-btn" data-filter="month">This Month</button>
        <button class="modern-filter-btn" data-filter="upcoming">Upcoming</button>
      </div>

      @if($canEditCalendar ?? false)
      <div class="ms-auto">
        <button class="modern-btn modern-btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
          <i class="bx bx-plus"></i>
          New Event
        </button>
      </div>
      @endif
    </div>

    {{-- Calendar Component --}}
    <div class="modern-calendar mb-4">
      <div class="modern-calendar-header">
        <h3 class="modern-calendar-title">
          {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
        </h3>
        <div class="modern-calendar-nav">
          <button class="modern-calendar-nav-btn" onclick="previousMonth()">
            <i class="bx bx-chevron-left"></i>
          </button>
          <button class="modern-calendar-nav-btn" onclick="nextMonth()">
            <i class="bx bx-chevron-right"></i>
          </button>
        </div>
      </div>

      <div class="modern-calendar-grid">
        {{-- Day Headers --}}
        <div class="modern-calendar-day-header">Sun</div>
        <div class="modern-calendar-day-header">Mon</div>
        <div class="modern-calendar-day-header">Tue</div>
        <div class="modern-calendar-day-header">Wed</div>
        <div class="modern-calendar-day-header">Thu</div>
        <div class="modern-calendar-day-header">Fri</div>
        <div class="modern-calendar-day-header">Sat</div>

        {{-- Calendar Days (dynamically generated) --}}
        @php
          $today = date('j');
          $currentMonth = $month; // Use month from controller
          $currentYear = $year; // Use year from controller
          $firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
          $daysInMonth = date('t', $firstDayOfMonth);
          $dayOfWeek = date('w', $firstDayOfMonth);

          // Previous month days
          $prevMonthDays = date('t', mktime(0, 0, 0, $currentMonth - 1, 1, $currentYear));
          $prevMonthStart = $prevMonthDays - $dayOfWeek + 1;

          $dayCounter = 1;
          $nextMonthDay = 1;

          // Get actual event dates for this month
          $examDates = $exams->pluck('start_date')->map(function($date) {
              return \Carbon\Carbon::parse($date)->format('j');
          })->toArray();
        @endphp

        @for ($i = 0; $i < 42; $i++)
          @php
            $isOtherMonth = false;
            $displayDay = '';
            $isToday = false;
            $hasEvent = false;

            if ($i < $dayOfWeek) {
              // Previous month
              $displayDay = $prevMonthStart + $i;
              $isOtherMonth = true;
            } elseif ($dayCounter <= $daysInMonth) {
              // Current month
              $displayDay = $dayCounter;
              // Only highlight today if we're viewing the current month and year
              $isToday = ($dayCounter == $today && $currentMonth == date('n') && $currentYear == date('Y'));
              $hasEvent = in_array($dayCounter, $examDates);
              $dayCounter++;
            } else {
              // Next month
              $displayDay = $nextMonthDay;
              $isOtherMonth = true;
              $nextMonthDay++;
            }

            if ($i >= $dayOfWeek + $daysInMonth && $i % 7 == 0) break;
          @endphp

          <div class="modern-calendar-day
                      {{ $isOtherMonth ? 'other-month' : '' }}
                      {{ $isToday ? 'today' : '' }}
                      {{ $hasEvent ? 'has-event' : '' }}">
            {{ $displayDay }}
          </div>
        @endfor
      </div>
    </div>

    {{-- Event Cards Grid --}}
    <div class="modern-grid modern-grid-2">
      @forelse($exams as $exam)
      <div class="exam-card {{ $exam->color ?? 'purple' }}">
        <div class="exam-card-header">
          <span class="exam-card-date">{{ \Carbon\Carbon::parse($exam->start_date)->format('M d, Y') }}</span>
          @if($canEditCalendar ?? false)
          <div class="dropdown">
            <button class="exam-card-menu" type="button" data-bs-toggle="dropdown">
              <i class="bx bx-dots-vertical-rounded"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#" onclick="editEvent({{ $exam->id }})"><i class="bx bx-edit me-1"></i> Edit</a></li>
              <li><a class="dropdown-item text-danger" href="#" onclick="deleteEvent({{ $exam->id }})"><i class="bx bx-trash me-1"></i> Delete</a></li>
            </ul>
          </div>
          @endif
        </div>
        <h3 class="exam-card-title">{{ $exam->title }}</h3>
        <p class="exam-card-class">{{ $exam->class ? $exam->class->name : 'All Classes' }}</p>
        <div class="exam-card-meta">
          <div class="exam-card-meta-item">
            <i class="bx bx-time exam-card-meta-icon"></i>
            <span>{{ $exam->time ?? '9:00 AM' }}</span>
          </div>
          @if($exam->students_count > 0)
          <div class="exam-card-meta-item">
            <i class="bx bx-group exam-card-meta-icon"></i>
            <span>{{ $exam->students_count }} Students</span>
          </div>
          @endif
        </div>
      </div>
      @empty
      <div class="col-12">
        <div class="alert alert-info">
          <i class="bx bx-info-circle me-2"></i>
          No events scheduled for this period.
        </div>
      </div>
      @endforelse
    </div>

  </div>

  {{-- Right Side: Upcoming Events --}}
  <div class="col-lg-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h3 class="modern-card-title">Upcoming Events</h3>
        <span class="modern-badge modern-badge-purple">{{ date('M Y') }}</span>
      </div>

      <div class="modern-card-body">
        @forelse($upcomingExams as $index => $event)
        <div class="upcoming-exam-item">
          <div class="upcoming-exam-date">
            <span class="upcoming-exam-day">{{ \Carbon\Carbon::parse($event->start_date)->format('d') }}</span>
            <span class="upcoming-exam-month">{{ \Carbon\Carbon::parse($event->start_date)->format('M') }}</span>
          </div>
          <div class="upcoming-exam-details">
            <h4 class="upcoming-exam-title">{{ $event->title }}</h4>
            <p class="upcoming-exam-meta">
              {{ $event->class ? $event->class->name : 'All Classes' }} • {{ $event->time ?? '9:00 AM' }}
            </p>
            @if($event->students_count > 0)
            <div class="upcoming-exam-badges">
              <span class="modern-badge modern-badge-{{ $event->color ?? 'purple' }}">{{ $event->students_count }} Students</span>
            </div>
            @endif
          </div>
        </div>
        @if(!$loop->last)
        <div class="modern-divider"></div>
        @endif
        @empty
        <div class="text-center py-4 text-muted">
          <i class="bx bx-calendar-x bx-lg mb-2"></i>
          <p>No upcoming events</p>
        </div>
        @endforelse
      </div>

      @if(count($upcomingExams) > 0)
      <div class="modern-card-footer">
        <a href="#" class="modern-btn modern-btn-outline modern-btn-sm w-100">
          View All Events
        </a>
      </div>
      @endif
    </div>

    {{-- Quick Stats Card --}}
    <div class="modern-card mt-4">
      <div class="modern-card-header">
        <h3 class="modern-card-title">Quick Stats</h3>
      </div>
      <div class="modern-card-body">
        <div class="stat-item">
          <div class="stat-icon purple">
            <i class="bx bx-calendar"></i>
          </div>
          <div class="stat-details">
            <p class="stat-label">Total Events</p>
            <h4 class="stat-value">{{ $stats['total_exams'] ?? 0 }}</h4>
          </div>
        </div>

        <div class="modern-divider"></div>

        <div class="stat-item">
          <div class="stat-icon blue">
            <i class="bx bx-time"></i>
          </div>
          <div class="stat-details">
            <p class="stat-label">This Week</p>
            <h4 class="stat-value">{{ $stats['this_week'] ?? 0 }}</h4>
          </div>
        </div>

        <div class="modern-divider"></div>

        <div class="stat-item">
          <div class="stat-icon green">
            <i class="bx bx-check-circle"></i>
          </div>
          <div class="stat-details">
            <p class="stat-label">Completed</p>
            <h4 class="stat-value">{{ $stats['completed'] ?? 0 }}</h4>
          </div>
        </div>

        <div class="modern-divider"></div>

        <div class="stat-item">
          <div class="stat-icon yellow">
            <i class="bx bx-calendar-event"></i>
          </div>
          <div class="stat-details">
            <p class="stat-label">Upcoming</p>
            <h4 class="stat-value">{{ $stats['upcoming'] ?? 0 }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Add Event Modal --}}
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('calendar.events.create') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="addEventModalLabel">Add New Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="event_title" class="form-label">Event Title *</label>
              <input type="text" class="form-control" id="event_title" name="title" required>
            </div>

            <div class="col-md-6 mb-3">
              <label for="event_type" class="form-label">Event Type *</label>
              <select class="form-select" id="event_type" name="event_type" required>
                <option value="exam">Exam</option>
                <option value="meeting">Meeting</option>
                <option value="holiday">Holiday</option>
                <option value="sport">Sports Event</option>
                <option value="cultural">Cultural Event</option>
                <option value="academic">Academic Event</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label for="event_location" class="form-label">Location</label>
              <input type="text" class="form-control" id="event_location" name="location">
            </div>

            <div class="col-md-6 mb-3">
              <label for="start_date" class="form-label">Start Date *</label>
              <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>

            <div class="col-md-6 mb-3">
              <label for="end_date" class="form-label">End Date</label>
              <input type="date" class="form-control" id="end_date" name="end_date">
            </div>

            <div class="col-md-6 mb-3">
              <label for="start_time" class="form-label">Start Time</label>
              <input type="time" class="form-control" id="start_time" name="start_time">
            </div>

            <div class="col-md-6 mb-3">
              <label for="end_time" class="form-label">End Time</label>
              <input type="time" class="form-control" id="end_time" name="end_time">
            </div>

            <div class="col-md-12 mb-3">
              <label for="event_description" class="form-label">Description</label>
              <textarea class="form-control" id="event_description" name="description" rows="3"></textarea>
            </div>

            <div class="col-md-12 mb-3">
              <label class="form-label">Notifications</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="notify_staff" name="notify_staff" value="1">
                <label class="form-check-label" for="notify_staff">Notify Staff</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="notify_students" name="notify_students" value="1">
                <label class="form-check-label" for="notify_students">Notify Students</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="notify_parents" name="notify_parents" value="1">
                <label class="form-check-label" for="notify_parents">Notify Parents</label>
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <label for="reminder_days" class="form-label">Remind Before (days)</label>
              <input type="number" class="form-control" id="reminder_days" name="reminder_days_before" min="1" max="30">
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
@endsection

@push('page-style')
<style>
/* Upcoming Exam Items */
.upcoming-exam-item {
  display: flex;
  gap: var(--spacing-md);
  padding: var(--spacing-md) 0;
}

.upcoming-exam-date {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 50px;
  height: 50px;
  background-color: var(--neutral-100);
  border-radius: var(--radius-md);
  flex-shrink: 0;
}

.upcoming-exam-day {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--neutral-900);
  line-height: 1;
}

.upcoming-exam-month {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--neutral-600);
  text-transform: uppercase;
  line-height: 1;
  margin-top: 2px;
}

.upcoming-exam-details {
  flex: 1;
  min-width: 0;
}

.upcoming-exam-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--neutral-900);
  margin: 0 0 4px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.upcoming-exam-meta {
  font-size: 0.8125rem;
  color: var(--neutral-600);
  margin: 0 0 8px 0;
}

.upcoming-exam-badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

/* Stat Items */
.stat-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-sm) 0;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: var(--white);
  flex-shrink: 0;
}

.stat-icon.purple {
  background-color: var(--primary-purple);
}

.stat-icon.blue {
  background-color: var(--primary-blue);
}

.stat-icon.green {
  background-color: var(--primary-green);
}

.stat-icon.yellow {
  background-color: var(--primary-yellow);
}

.stat-details {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--neutral-600);
  margin: 0 0 4px 0;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--neutral-900);
  margin: 0;
}
</style>

<script>
// Calendar navigation functions
function previousMonth() {
  const currentUrl = new URL(window.location.href);
  const currentMonth = parseInt(currentUrl.searchParams.get('month') || {{ $month }});
  const currentYear = parseInt(currentUrl.searchParams.get('year') || {{ $year }});

  let newMonth = currentMonth - 1;
  let newYear = currentYear;

  if (newMonth < 1) {
    newMonth = 12;
    newYear--;
  }

  window.location.href = `?month=${newMonth}&year=${newYear}`;
}

function nextMonth() {
  const currentUrl = new URL(window.location.href);
  const currentMonth = parseInt(currentUrl.searchParams.get('month') || {{ $month }});
  const currentYear = parseInt(currentUrl.searchParams.get('year') || {{ $year }});

  let newMonth = currentMonth + 1;
  let newYear = currentYear;

  if (newMonth > 12) {
    newMonth = 1;
    newYear++;
  }

  window.location.href = `?month=${newMonth}&year=${newYear}`;
}

// Edit event function
function editEvent(eventId) {
  // TODO: Implement edit event modal
  console.log('Edit event:', eventId);
  alert('Edit event functionality coming soon!');
}

// Delete event function
function deleteEvent(eventId) {
  if (confirm('Are you sure you want to delete this event?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/calendar/events/${eventId}`;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;

    form.appendChild(methodInput);
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
  }
}

// Filter button interactions
document.addEventListener('DOMContentLoaded', function() {
  const filterButtons = document.querySelectorAll('.modern-filter-btn');

  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      filterButtons.forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');

      const filter = this.getAttribute('data-filter');
      // Filter logic can be implemented here
      console.log('Filter:', filter);
    });
  });

  // Auto-dismiss alerts after 5 seconds
  setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    });
  }, 5000);
});
</script>
@endpush
