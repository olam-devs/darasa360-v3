@extends('layouts.modernLayout', [
    'pageTitle' => 'Owner Dashboard',
    'pageSubtitle' => 'Welcome back, ' . session('username', session('name', 'School Owner')) . '!'
])

@section('title', 'Owner Dashboard')

@section('content')
<div class="row">
  <!-- Welcome Banner -->
  <div class="col-xxl-8 mb-4 order-0">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="text-white mb-3">Welcome Back, {{ session('username', session('name', 'School Owner')) }}!</h5>
            <p class="text-white mb-0">Leading your institution to excellence with vision and dedication.</p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}"
                 height="200" class="scaleX-n1-rtl" alt="Owner">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Overview -->
  <div class="col-xxl-4 order-1 order-xxl-1 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Business Overview</h5>
        <i class="bx bx-building text-purple" style="font-size: 24px;"></i>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-borderless align-middle mb-0">
            <tbody>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-purple me-3">
                      <i class="bx bx-group"></i>
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
                      <i class="bx bx-user"></i>
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
                      <i class="bx bx-dollar"></i>
                    </div>
                    <span>Total Revenue</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">TSh {{ number_format($stats['total_revenue'] ?? 0) }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-yellow me-3">
                      <i class="bx bx-book-open"></i>
                    </div>
                    <span>Total Classes</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $stats['total_classes'] ?? 0 }}</h5>
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
          <i class="bx bx-group" style="font-size: 30px; color: #7C3AED;"></i>
        </div>
        <h3 class="mb-1">{{ $stats['total_students'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Total Students</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-user" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ $stats['total_staff'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Total Staff</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-dollar" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">TSh {{ number_format($stats['total_revenue'] ?? 0) }}</h3>
        <p class="text-muted mb-0">Total Revenue</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-yellow h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-book-open" style="font-size: 30px; color: #F59E0B;"></i>
        </div>
        <h3 class="mb-1">{{ $stats['total_classes'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Total Classes</p>
      </div>
    </div>
  </div>

  <!-- Staff Breakdown -->
  <div class="col-lg-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-group me-2 text-purple"></i>Staff Breakdown
        </h5>
      </div>
      <div class="modern-card-body">
        @php
          try {
            $staffBreakdown = \App\Models\SchoolUser::select('role_id', \DB::raw('count(*) as count'))
              ->groupBy('role_id')
              ->with('role')
              ->get();
          } catch (\Exception $e) {
            $staffBreakdown = collect();
          }
        @endphp
        @if($staffBreakdown->count() > 0)
          <div class="table-responsive">
            <table class="table table-borderless align-middle">
              <tbody>
                @foreach($staffBreakdown as $staff)
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="modern-badge modern-badge-{{ ['purple', 'blue', 'green', 'yellow', 'coral'][$loop->index % 5] }} me-3">
                        <i class="bx bx-user"></i>
                      </div>
                      <span>{{ ucfirst($staff->role->name ?? 'Unknown') }}</span>
                    </div>
                  </td>
                  <td class="text-end">
                    <strong>{{ $staff->count }}</strong>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="text-center text-muted py-4">
            <i class="bx bx-user" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No staff members</p>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Financial Summary -->
  <div class="col-lg-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-dollar-circle me-2 text-green"></i>Financial Summary
        </h5>
      </div>
      <div class="modern-card-body">
        @php
          try {
            $totalExpectedFees = 0;
            $totalCollected = 0;
            $collectionRate = 0;
            $recentPayments = collect();
          } catch (\Exception $e) {
            $totalExpectedFees = 0;
            $totalCollected = 0;
            $collectionRate = 0;
            $recentPayments = collect();
          }
        @endphp
        <div class="row mb-3">
          <div class="col-6">
            <div class="p-3 text-center" style="background: rgba(16, 185, 129, 0.1); border-radius: 8px;">
              <h6 class="text-success mb-1">{{ $collectionRate }}%</h6>
              <small class="text-muted">Collection Rate</small>
            </div>
          </div>
          <div class="col-6">
            <div class="p-3 text-center" style="background: rgba(245, 158, 11, 0.1); border-radius: 8px;">
              <h6 class="text-warning mb-1">TSh {{ number_format($stats['pending_fees']) }}</h6>
              <small class="text-muted">Pending</small>
            </div>
          </div>
        </div>
        <h6 class="mb-2">Recent Payments</h6>
        @if($recentPayments->count() > 0)
          @foreach($recentPayments as $payment)
          <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
            <div>
              <small class="text-muted">{{ $payment->created_at->format('M d, Y') }}</small>
            </div>
            <strong class="text-success">TSh {{ number_format($payment->amount) }}</strong>
          </div>
          @endforeach
        @else
          <p class="text-muted small">No recent payments</p>
        @endif
      </div>
    </div>
  </div>

  <!-- Class Distribution -->
  <div class="col-lg-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-chalkboard me-2 text-blue"></i>Student Distribution by Class
        </h5>
      </div>
      <div class="modern-card-body">
        @php
          try {
            $classDistribution = \App\Models\Student::select('class_id', \DB::raw('count(*) as count'))
              ->groupBy('class_id')
              ->with('classModel')
              ->orderBy('count', 'desc')
              ->get();
          } catch (\Exception $e) {
            $classDistribution = collect();
          }
        @endphp
        @if($classDistribution->count() > 0)
          <div class="table-responsive">
            <table class="table table-sm table-borderless align-middle">
              <tbody>
                @foreach($classDistribution as $dist)
                <tr>
                  <td>{{ $dist->classModel->name ?? 'N/A' }}</td>
                  <td class="text-end">
                    <strong>{{ $dist->count }}</strong> students
                  </td>
                  <td style="width: 100px;">
                    @php
                      $percentage = $stats['total_students'] > 0 ? round(($dist->count / $stats['total_students']) * 100, 1) : 0;
                    @endphp
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-primary" style="width: {{ $percentage }}%"></div>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="text-center text-muted py-4">
            <i class="bx bx-chalkboard" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No class data</p>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Recent Announcements & Upcoming Events -->
  <div class="col-lg-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-bell me-2 text-purple"></i>Recent Announcements
        </h5>
      </div>
      <div class="modern-card-body">
        @forelse($announcements as $announcement)
          <div class="alert alert-{{ ['primary', 'info', 'success'][loop->index % 3] }} mb-3">
            <div class="d-flex align-items-start">
              <div class="modern-badge modern-badge-purple me-3">
                <i class="bx bx-bell"></i>
              </div>
              <div class="flex-grow-1">
                <strong>{{ $announcement->title ?? 'Announcement' }}</strong><br>
                <small class="text-muted">{{ $announcement->created_at->diffForHumans() }}</small>
              </div>
            </div>
          </div>
        @empty
          <div class="text-center text-muted py-4">
            <i class="bx bx-bell" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No announcements</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="col-lg-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-calendar-event me-2 text-blue"></i>Upcoming Events
        </h5>
      </div>
      <div class="modern-card-body">
        @forelse($upcomingEvents as $event)
          <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
            <div class="modern-badge modern-badge-blue me-3">
              <i class="bx bx-calendar"></i>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-0">{{ $event->title }}</h6>
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
            <a href="{{ route('students.index') }}" class="btn btn-outline-purple w-100 py-3">
              <i class="bx bx-user d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Students</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('owner.manageStaff') }}" class="btn btn-outline-blue w-100 py-3">
              <i class="bx bx-user-check d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Staff</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('classes.index') }}" class="btn btn-outline-green w-100 py-3">
              <i class="bx bx-chalkboard d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Classes</span>
            </a>
          </div>
          {{-- Announcements route not yet defined for owners --}}
          {{-- <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('announcements.index') }}" class="btn btn-outline-yellow w-100 py-3">
              <i class="bx bx-bell d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Announcements</span>
            </a>
          </div> --}}
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('calendar.index') }}" class="btn btn-outline-coral w-100 py-3">
              <i class="bx bx-calendar d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Calendar</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('sms.send') }}" class="btn btn-outline-pink w-100 py-3">
              <i class="bx bx-message-dots d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">SMS</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
