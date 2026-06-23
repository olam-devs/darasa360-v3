@extends('layouts.modernLayout', [
    'pageTitle' => 'Headmaster Dashboard',
    'pageSubtitle' => 'Welcome back, ' . session('username', session('name', 'Headmaster')) . '!'
])

@section('title', 'Headmaster Dashboard')

@section('content')
<div class="row">
  <!-- Welcome Banner -->
  <div class="col-xxl-8 mb-4 order-0">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="text-white mb-3">Welcome Back, {{ session('username', session('name', 'Headmaster')) }}!</h5>
            <p class="text-white mb-0">Your leadership is driving success—keep inspiring excellence every day.</p>

            @if(!empty($canJumpToFinance))
            <form method="POST" action="{{ route('handoff.issue') }}" class="mt-3">
              @csrf
              <button type="submit"
                class="btn btn-light btn-sm fw-semibold d-inline-flex align-items-center gap-1">
                <i class="bx bx-link-external"></i> Open Finance Portal
              </button>
            </form>
            @endif
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}"
                 height="200" class="scaleX-n1-rtl" alt="Headmaster">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Overview -->
  <div class="col-xxl-4 order-1 order-xxl-1 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">School Overview</h5>
        <i class="bx bx-buildings text-purple" style="font-size: 24px;"></i>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-borderless align-middle mb-0">
            <tbody>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-purple me-3">
                      <i class="bx bx-user"></i>
                    </div>
                    <span>Total Students</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $stats['total_students'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-blue me-3">
                      <i class="bx bx-chalkboard"></i>
                    </div>
                    <span>Total Staff</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $stats['total_staff'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-green me-3">
                      <i class="bx bx-building"></i>
                    </div>
                    <span>Total Classes</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $stats['total_classes'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-yellow me-3">
                      <i class="bx bx-calendar"></i>
                    </div>
                    <span>Upcoming Events</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $stats['upcoming_events'] ?? 0 }}</h5>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-purple h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(124, 58, 237, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-user" style="font-size: 30px; color: #7C3AED;"></i>
        </div>
        <h3 class="mb-1">{{ $stats['total_students'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Students</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-chalkboard" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ $stats['total_staff'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Staff Members</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-building" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">{{ $stats['total_classes'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Classes</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-yellow h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-calendar" style="font-size: 30px; color: #F59E0B;"></i>
        </div>
        <h3 class="mb-1">{{ $stats['upcoming_events'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Upcoming Events</p>
      </div>
    </div>
  </div>

  <!-- Academic Overview & Upcoming Events -->
  <div class="col-md-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-line-chart me-2 text-purple"></i>Academic Overview
        </h5>
      </div>
      <div class="modern-card-body">
        @if(isset($academicData['stats']))
          <div class="mb-3 p-3 academic-stats-box" style="border-radius: 8px; border-left: 4px solid #7C3AED;">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold">Overall Performance</span>
              <span class="modern-badge modern-badge-{{ $academicData['stats']['overall_performance'] >= 70 ? 'green' : ($academicData['stats']['overall_performance'] >= 50 ? 'yellow' : 'coral') }}">
                {{ number_format($academicData['stats']['overall_performance'], 1) }}%
              </span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-semibold">Average Pass Rate</span>
              <span class="modern-badge modern-badge-{{ $academicData['stats']['average_pass_rate'] >= 70 ? 'green' : ($academicData['stats']['average_pass_rate'] >= 50 ? 'yellow' : 'coral') }}">
                {{ number_format($academicData['stats']['average_pass_rate'], 1) }}%
              </span>
            </div>
          </div>
        @else
          <div class="text-center text-muted py-4">
            <i class="bx bx-line-chart" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No academic data available</p>
          </div>
        @endif

        <div class="alert alert-info mt-3 mb-0">
          <i class="bx bx-info-circle me-2"></i>
          <strong>Tip:</strong> Regular monitoring of academic performance helps identify areas for improvement and celebrate successes.
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-calendar-event me-2 text-blue"></i>Upcoming Events
        </h5>
      </div>
      <div class="modern-card-body">
        @forelse($upcomingEvents as $event)
          <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
            <div class="modern-badge modern-badge-purple me-3">
              <i class="bx bx-calendar"></i>
            </div>
            <div class="flex-grow-1">
              <div class="fw-semibold">{{ $event->title }}</div>
              <small class="text-muted">{{ $event->start_date->format('M d, Y') }}</small>
            </div>
          </div>
        @empty
          <div class="text-center text-muted py-4">
            <i class="bx bx-calendar" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No upcoming events</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="col-12">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-rocket me-2 text-green"></i>Quick Actions
        </h5>
      </div>
      <div class="modern-card-body">
        <div class="row g-3">
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('headmaster.students.index') }}" class="btn btn-outline-purple w-100 py-3">
              <i class="bx bx-user d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Students</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('notes.home') }}" class="btn btn-outline-blue w-100 py-3">
              <i class="bx bx-note d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Notes</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('calendar.index') }}" class="btn btn-outline-green w-100 py-3">
              <i class="bx bx-calendar d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Calendar</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('support.index') }}" class="btn btn-outline-yellow w-100 py-3">
              <i class="bx bx-support d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Support</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('headMaster.manageStaff') }}" class="btn btn-outline-coral w-100 py-3">
              <i class="bx bx-user-check d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Staff</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('classes.index') }}" class="btn btn-outline-pink w-100 py-3">
              <i class="bx bx-chalkboard d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Classes</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
