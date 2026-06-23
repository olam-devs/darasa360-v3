@php
    $username = session('username', session('name', 'Headmaster'));
@endphp
@extends('layouts.modernLayout', [
    'pageTitle' => 'Welcome Back, ' . $username . '!'
])

@section('title', 'Dashboard - Headmaster')

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
                  <h5 class="mb-0">{{ $dashboardData['studentsCount'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-blue me-3">
                      <i class="bx bx-chalkboard"></i>
                    </div>
                    <span>Total Teachers</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['teachersCount'] ?? 0 }}</h5>
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
                  <h5 class="mb-0">{{ $dashboardData['classesCount'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-yellow me-3">
                      <i class="bx bx-message-dots"></i>
                    </div>
                    <span>SMS Sent</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['smsCount'] ?? 0 }}</h5>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards Grid -->
  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-purple h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(124, 58, 237, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-user" style="font-size: 30px; color: #7C3AED;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['studentsCount'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Students</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items-center; justify-content: center;">
          <i class="bx bx-chalkboard" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['teachersCount'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Teachers</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-building" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['classesCount'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Classes</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-yellow h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-message-dots" style="font-size: 30px; color: #F59E0B;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['smsCount'] ?? 0 }}</h3>
        <p class="text-muted mb-0">SMS Sent</p>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="col-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-rocket me-2 text-purple"></i>Quick Actions
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
            <a href="{{ route('headmaster.notes') }}" class="btn btn-outline-blue w-100 py-3">
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
            <a href="{{ route('owner.manage.staff') }}" class="btn btn-outline-coral w-100 py-3">
              <i class="bx bx-user-check d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Staff</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('owner.classes') }}" class="btn btn-outline-pink w-100 py-3">
              <i class="bx bx-chalkboard d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Classes</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Management Overview -->
  <div class="col-md-12">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-bar-chart-alt-2 me-2 text-blue"></i>School Management
        </h5>
      </div>
      <div class="modern-card-body">
        <div class="alert alert-info mb-0">
          <i class="bx bx-info-circle me-2"></i>
          <strong>Headmaster Dashboard:</strong> Monitor school performance, manage staff and students, and oversee all academic and administrative activities.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
