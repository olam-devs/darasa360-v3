@extends('layouts.modernLayout', [
    'pageTitle' => 'Dashboard',
    'pageSubtitle' => 'Welcome back, ' . session('username', 'User') . '!'
])

@section('content')
{{-- Page Header with Actions --}}
<div class="modern-page-header">
    <div class="modern-page-header-top">
        <div>
            <h1 class="modern-page-title">Dashboard</h1>
            <p class="modern-page-subtitle">{{ date('l, F j, Y') }}</p>
        </div>
        <div class="modern-page-actions">
            <button class="modern-btn modern-btn-outline" onclick="window.print()">
                <i class="bx bx-printer"></i>
                Print
            </button>
            <a href="/calendar" class="modern-btn modern-btn-primary">
                <i class="bx bx-calendar"></i>
                View Calendar
            </a>
        </div>
    </div>
</div>

{{-- Stats Cards Row --}}
<div class="modern-grid modern-grid-4 mb-4">
    {{-- Students Card --}}
    <div class="modern-card">
        <div class="stat-item">
            <div class="stat-icon purple">
                <i class="bx bx-group"></i>
            </div>
            <div class="stat-details">
                <p class="stat-label">Total Students</p>
                <h4 class="stat-value">{{ $stats['total_students'] ?? 0 }}</h4>
            </div>
        </div>
    </div>

    {{-- Exams Card --}}
    <div class="modern-card">
        <div class="stat-item">
            <div class="stat-icon blue">
                <i class="bx bx-calendar"></i>
            </div>
            <div class="stat-details">
                <p class="stat-label">Upcoming Exams</p>
                <h4 class="stat-value">{{ $stats['upcoming_exams'] ?? 0 }}</h4>
            </div>
        </div>
    </div>

    {{-- Classes Card --}}
    <div class="modern-card">
        <div class="stat-item">
            <div class="stat-icon green">
                <i class="bx bx-book"></i>
            </div>
            <div class="stat-details">
                <p class="stat-label">Total Classes</p>
                <h4 class="stat-value">{{ $stats['total_classes'] ?? 0 }}</h4>
            </div>
        </div>
    </div>

    {{-- Staff Card --}}
    <div class="modern-card">
        <div class="stat-item">
            <div class="stat-icon coral">
                <i class="bx bx-user-check"></i>
            </div>
            <div class="stat-details">
                <p class="stat-label">Staff Members</p>
                <h4 class="stat-value">{{ $stats['total_staff'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        {{-- Recent Activity Card --}}
        <div class="modern-card mb-4">
            <div class="modern-card-header">
                <h3 class="modern-card-title">Recent Activity</h3>
                <div class="modern-filter-group">
                    <button class="modern-filter-btn active">Today</button>
                    <button class="modern-filter-btn">This Week</button>
                    <button class="modern-filter-btn">This Month</button>
                </div>
            </div>
            <div class="modern-card-body">
                @if(isset($recentActivities) && count($recentActivities) > 0)
                    @foreach($recentActivities as $activity)
                        <div class="activity-item">
                            <div class="activity-icon {{ $activity['color'] ?? 'purple' }}">
                                <i class="bx {{ $activity['icon'] ?? 'bx-bell' }}"></i>
                            </div>
                            <div class="activity-content">
                                <h4 class="activity-title">{{ $activity['title'] }}</h4>
                                <p class="activity-description">{{ $activity['description'] }}</p>
                                <span class="activity-time">{{ $activity['time'] }}</span>
                            </div>
                        </div>
                        @if (!$loop->last)
                            <div class="modern-divider"></div>
                        @endif
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-info-circle" style="font-size: 48px; color: var(--neutral-400);"></i>
                        <p class="modern-text-muted mt-2">No recent activity</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="modern-card">
            <div class="modern-card-header">
                <h3 class="modern-card-title">Quick Actions</h3>
            </div>
            <div class="modern-card-body">
                <div class="modern-grid modern-grid-3">
                    @foreach($quickLinks ?? [] as $link)
                        <a href="{{ $link['url'] }}" class="quick-link-card {{ $link['color'] ?? 'purple' }}">
                            <i class="bx {{ $link['icon'] }}"></i>
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Calendar Widget --}}
        <div class="modern-card mb-4">
            <div class="modern-card-header">
                <h3 class="modern-card-title">This Month</h3>
                <a href="/calendar" class="modern-text-primary" style="font-size: 0.875rem;">View All</a>
            </div>
            <div class="modern-card-body">
                <div class="mini-calendar">
                    <div class="mini-calendar-header">
                        <h4>{{ date('F Y') }}</h4>
                    </div>
                    <div class="mini-calendar-grid">
                        <div class="mini-calendar-day-header">S</div>
                        <div class="mini-calendar-day-header">M</div>
                        <div class="mini-calendar-day-header">T</div>
                        <div class="mini-calendar-day-header">W</div>
                        <div class="mini-calendar-day-header">T</div>
                        <div class="mini-calendar-day-header">F</div>
                        <div class="mini-calendar-day-header">S</div>

                        @php
                            $today = date('j');
                            $currentMonth = date('n');
                            $currentYear = date('Y');
                            $firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
                            $daysInMonth = date('t', $firstDayOfMonth);
                            $dayOfWeek = date('w', $firstDayOfMonth);

                            for ($i = 0; $i < $dayOfWeek; $i++) {
                                echo '<div class="mini-calendar-day empty"></div>';
                            }

                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $isToday = ($day == $today) ? 'today' : '';
                                echo "<div class='mini-calendar-day {$isToday}'>{$day}</div>";
                            }
                        @endphp
                    </div>
                </div>
            </div>
        </div>

        {{-- Upcoming Events --}}
        <div class="modern-card">
            <div class="modern-card-header">
                <h3 class="modern-card-title">Upcoming Events</h3>
                <span class="modern-badge modern-badge-purple">{{ count($upcomingEvents ?? []) }}</span>
            </div>
            <div class="modern-card-body">
                @if(isset($upcomingEvents) && count($upcomingEvents) > 0)
                    @foreach(array_slice($upcomingEvents, 0, 5) as $event)
                        <div class="upcoming-exam-item">
                            <div class="upcoming-exam-date">
                                <span class="upcoming-exam-day">{{ date('d', strtotime($event['date'])) }}</span>
                                <span class="upcoming-exam-month">{{ date('M', strtotime($event['date'])) }}</span>
                            </div>
                            <div class="upcoming-exam-details">
                                <h4 class="upcoming-exam-title">{{ $event['title'] }}</h4>
                                <p class="upcoming-exam-meta">{{ $event['description'] ?? 'No description' }}</p>
                                <span class="modern-badge modern-badge-{{ $event['color'] ?? 'gray' }}">
                                    {{ $event['type'] ?? 'Event' }}
                                </span>
                            </div>
                        </div>
                        @if (!$loop->last)
                            <div class="modern-divider"></div>
                        @endif
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="bx bx-calendar-x" style="font-size: 36px; color: var(--neutral-400);"></i>
                        <p class="modern-text-muted mt-2" style="font-size: 0.875rem;">No upcoming events</p>
                    </div>
                @endif
            </div>
            @if(isset($upcomingEvents) && count($upcomingEvents) > 5)
                <div class="modern-card-footer">
                    <a href="/calendar" class="modern-btn modern-btn-outline modern-btn-sm w-100">
                        View All Events
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('page-style')
<style>
/* Activity Item Styles */
.activity-item {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-md) 0;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: var(--white);
    flex-shrink: 0;
}

.activity-icon.purple { background-color: var(--primary-purple); }
.activity-icon.blue { background-color: var(--primary-blue); }
.activity-icon.green { background-color: var(--primary-green); }
.activity-icon.coral { background-color: var(--primary-coral); }

.activity-content {
    flex: 1;
}

.activity-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--neutral-900);
    margin: 0 0 4px 0;
}

.activity-description {
    font-size: 0.875rem;
    color: var(--neutral-600);
    margin: 0 0 4px 0;
}

.activity-time {
    font-size: 0.75rem;
    color: var(--neutral-500);
}

/* Quick Link Cards */
.quick-link-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-lg);
    border-radius: var(--radius-md);
    border: 2px solid var(--border-color);
    text-decoration: none;
    transition: all 0.2s ease;
    gap: var(--spacing-sm);
}

.quick-link-card:hover {
    border-color: var(--primary-purple);
    background-color: rgba(124, 58, 237, 0.05);
    transform: translateY(-2px);
}

.quick-link-card i {
    font-size: 28px;
    color: var(--primary-purple);
}

.quick-link-card span {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--neutral-700);
    text-align: center;
}

/* Mini Calendar */
.mini-calendar-header {
    text-align: center;
    margin-bottom: var(--spacing-md);
}

.mini-calendar-header h4 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--neutral-900);
    margin: 0;
}

.mini-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.mini-calendar-day-header {
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--neutral-500);
    padding: 4px 0;
}

.mini-calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
    color: var(--neutral-700);
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mini-calendar-day:not(.empty):hover {
    background-color: var(--neutral-100);
}

.mini-calendar-day.today {
    background-color: var(--primary-purple);
    color: var(--white);
    font-weight: 600;
}

.mini-calendar-day.empty {
    cursor: default;
}
</style>
@endpush
