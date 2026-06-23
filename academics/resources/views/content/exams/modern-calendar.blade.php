@extends('layouts.modernLayout', ['pageTitle' => 'Exam Calendar', 'pageSubtitle' => 'View and manage upcoming exams'])

@section('content')
<div class="row">
  {{-- Left Side: Calendar and Filters --}}
  <div class="col-lg-8">

    {{-- Filter Bar --}}
    <div class="modern-filter-bar">
      <div class="modern-filter-group">
        <button class="modern-filter-btn active">All Exams</button>
        <button class="modern-filter-btn">This Week</button>
        <button class="modern-filter-btn">This Month</button>
        <button class="modern-filter-btn">Upcoming</button>
      </div>

      <div class="ms-auto">
        <button class="modern-btn modern-btn-primary">
          <i class="bx bx-plus"></i>
          New Exam
        </button>
      </div>
    </div>

    {{-- Calendar Component --}}
    <div class="modern-calendar mb-4">
      <div class="modern-calendar-header">
        <h3 class="modern-calendar-title">
          {{ date('F Y') }}
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
          $currentMonth = date('n');
          $currentYear = date('Y');
          $firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
          $daysInMonth = date('t', $firstDayOfMonth);
          $dayOfWeek = date('w', $firstDayOfMonth);

          // Previous month days
          $prevMonthDays = date('t', mktime(0, 0, 0, $currentMonth - 1, 1, $currentYear));
          $prevMonthStart = $prevMonthDays - $dayOfWeek + 1;

          $dayCounter = 1;
          $nextMonthDay = 1;

          // Sample exam dates (replace with actual data)
          $examDates = [5, 12, 18, 25];
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
              $isToday = ($dayCounter == $today);
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

    {{-- Exam Cards Grid --}}
    <div class="modern-grid modern-grid-2">

      {{-- Exam Card 1 - Purple --}}
      <div class="exam-card purple">
        <div class="exam-card-header">
          <span class="exam-card-date">Dec 5, 2025</span>
          <button class="exam-card-menu">
            <i class="bx bx-dots-vertical-rounded"></i>
          </button>
        </div>
        <h3 class="exam-card-title">Mathematics Mid-Term</h3>
        <p class="exam-card-class">Form 1 & 2</p>
        <div class="exam-card-meta">
          <div class="exam-card-meta-item">
            <i class="bx bx-time exam-card-meta-icon"></i>
            <span>9:00 AM</span>
          </div>
          <div class="exam-card-meta-item">
            <i class="bx bx-group exam-card-meta-icon"></i>
            <span>120 Students</span>
          </div>
        </div>
      </div>

      {{-- Exam Card 2 - Blue --}}
      <div class="exam-card blue">
        <div class="exam-card-header">
          <span class="exam-card-date">Dec 12, 2025</span>
          <button class="exam-card-menu">
            <i class="bx bx-dots-vertical-rounded"></i>
          </button>
        </div>
        <h3 class="exam-card-title">English Language</h3>
        <p class="exam-card-class">Form 3</p>
        <div class="exam-card-meta">
          <div class="exam-card-meta-item">
            <i class="bx bx-time exam-card-meta-icon"></i>
            <span>11:00 AM</span>
          </div>
          <div class="exam-card-meta-item">
            <i class="bx bx-group exam-card-meta-icon"></i>
            <span>85 Students</span>
          </div>
        </div>
      </div>

      {{-- Exam Card 3 - Yellow --}}
      <div class="exam-card yellow">
        <div class="exam-card-header">
          <span class="exam-card-date">Dec 18, 2025</span>
          <button class="exam-card-menu">
            <i class="bx bx-dots-vertical-rounded"></i>
          </button>
        </div>
        <h3 class="exam-card-title">Science Practical</h3>
        <p class="exam-card-class">Form 2</p>
        <div class="exam-card-meta">
          <div class="exam-card-meta-item">
            <i class="bx bx-time exam-card-meta-icon"></i>
            <span>2:00 PM</span>
          </div>
          <div class="exam-card-meta-item">
            <i class="bx bx-group exam-card-meta-icon"></i>
            <span>60 Students</span>
          </div>
        </div>
      </div>

      {{-- Exam Card 4 - Coral --}}
      <div class="exam-card coral">
        <div class="exam-card-header">
          <span class="exam-card-date">Dec 25, 2025</span>
          <button class="exam-card-menu">
            <i class="bx bx-dots-vertical-rounded"></i>
          </button>
        </div>
        <h3 class="exam-card-title">History Final</h3>
        <p class="exam-card-class">Form 4</p>
        <div class="exam-card-meta">
          <div class="exam-card-meta-item">
            <i class="bx bx-time exam-card-meta-icon"></i>
            <span>9:00 AM</span>
          </div>
          <div class="exam-card-meta-item">
            <i class="bx bx-group exam-card-meta-icon"></i>
            <span>95 Students</span>
          </div>
        </div>
      </div>

    </div>

  </div>

  {{-- Right Side: Upcoming Exams --}}
  <div class="col-lg-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h3 class="modern-card-title">Upcoming Exams</h3>
        <span class="modern-badge modern-badge-purple">{{ date('M Y') }}</span>
      </div>

      <div class="modern-card-body">
        {{-- Upcoming Exam Item 1 --}}
        <div class="upcoming-exam-item">
          <div class="upcoming-exam-date">
            <span class="upcoming-exam-day">05</span>
            <span class="upcoming-exam-month">Dec</span>
          </div>
          <div class="upcoming-exam-details">
            <h4 class="upcoming-exam-title">Mathematics Mid-Term</h4>
            <p class="upcoming-exam-meta">Form 1 & 2 • 9:00 AM</p>
            <div class="upcoming-exam-badges">
              <span class="modern-badge modern-badge-purple">120 Students</span>
            </div>
          </div>
        </div>

        <div class="modern-divider"></div>

        {{-- Upcoming Exam Item 2 --}}
        <div class="upcoming-exam-item">
          <div class="upcoming-exam-date">
            <span class="upcoming-exam-day">12</span>
            <span class="upcoming-exam-month">Dec</span>
          </div>
          <div class="upcoming-exam-details">
            <h4 class="upcoming-exam-title">English Language</h4>
            <p class="upcoming-exam-meta">Form 3 • 11:00 AM</p>
            <div class="upcoming-exam-badges">
              <span class="modern-badge modern-badge-blue">85 Students</span>
            </div>
          </div>
        </div>

        <div class="modern-divider"></div>

        {{-- Upcoming Exam Item 3 --}}
        <div class="upcoming-exam-item">
          <div class="upcoming-exam-date">
            <span class="upcoming-exam-day">18</span>
            <span class="upcoming-exam-month">Dec</span>
          </div>
          <div class="upcoming-exam-details">
            <h4 class="upcoming-exam-title">Science Practical</h4>
            <p class="upcoming-exam-meta">Form 2 • 2:00 PM</p>
            <div class="upcoming-exam-badges">
              <span class="modern-badge modern-badge-yellow">60 Students</span>
            </div>
          </div>
        </div>

        <div class="modern-divider"></div>

        {{-- Upcoming Exam Item 4 --}}
        <div class="upcoming-exam-item">
          <div class="upcoming-exam-date">
            <span class="upcoming-exam-day">25</span>
            <span class="upcoming-exam-month">Dec</span>
          </div>
          <div class="upcoming-exam-details">
            <h4 class="upcoming-exam-title">History Final</h4>
            <p class="upcoming-exam-meta">Form 4 • 9:00 AM</p>
            <div class="upcoming-exam-badges">
              <span class="modern-badge modern-badge-coral">95 Students</span>
            </div>
          </div>
        </div>

      </div>

      <div class="modern-card-footer">
        <a href="#" class="modern-btn modern-btn-outline modern-btn-sm w-100">
          View All Exams
        </a>
      </div>
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
            <p class="stat-label">Total Exams</p>
            <h4 class="stat-value">24</h4>
          </div>
        </div>

        <div class="modern-divider"></div>

        <div class="stat-item">
          <div class="stat-icon blue">
            <i class="bx bx-time"></i>
          </div>
          <div class="stat-details">
            <p class="stat-label">This Week</p>
            <h4 class="stat-value">4</h4>
          </div>
        </div>

        <div class="modern-divider"></div>

        <div class="stat-item">
          <div class="stat-icon green">
            <i class="bx bx-check-circle"></i>
          </div>
          <div class="stat-details">
            <p class="stat-label">Completed</p>
            <h4 class="stat-value">18</h4>
          </div>
        </div>
      </div>
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
  // Implementation for navigating to previous month
  console.log('Previous month');
}

function nextMonth() {
  // Implementation for navigating to next month
  console.log('Next month');
}

// Filter button interactions
document.addEventListener('DOMContentLoaded', function() {
  const filterButtons = document.querySelectorAll('.modern-filter-btn');

  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      filterButtons.forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
      // Add filter logic here
    });
  });
});
</script>
@endpush
