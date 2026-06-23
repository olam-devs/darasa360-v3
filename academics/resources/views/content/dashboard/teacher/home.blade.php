@extends('layouts.modernLayout', [
    'pageTitle' => 'Teacher Dashboard',
    'pageSubtitle' => 'Welcome back, ' . ($dashboardData['username'] ?? 'Teacher')
])

@section('title', 'Dashboard - Teacher')

@section('vendor-style')
@vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('page-script')
@vite('resources/assets/js/dashboards-analytics.js')
@endsection

@section('content')
<div class="row">
  <div class="col-xxl-8 mb-6 order-0">
    <div class="card">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="card-title text-primary mb-3">Welcome Back, {{ $dashboardData['username'] }}.</h5>
            <p class="mb-6">Empowering minds and shaping futures—thank you for your dedication!</p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}" height="270" class="scaleX-n1-rtl" alt="View Badge User">
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="col-xxl-4 order-1 order-xxl-1">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-4">Overview</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Metric</th>
                <th class="text-end">Count</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><i class="bx bx-user text-primary me-2"></i> Students</td>
                <td class="text-end">
                  @if($dashboardData['userRole'] === 'Teacher')
                    {{ $dashboardData['classStats']->last()['total_students'] ?? 0 }}
                  @else
                    {{ $dashboardData['classStats']->sum('studentsCount') }}
                  @endif
                </td>
              </tr>
              <tr>
                <td><i class="bx bx-chalkboard text-primary me-2"></i> Classes</td>
                <td class="text-end">
                  @if($dashboardData['userRole'] === 'Teacher')
                    {{ $dashboardData['classStats']->last()['classes_taught'] ?? 0 }}
                  @else
                    {{ $dashboardData['classStats']->count() }}
                  @endif
                </td>
              </tr>
              <tr>
                <td><i class="bx bx-book-open text-primary me-2"></i> Subjects</td>
                <td class="text-end">
                  @if($dashboardData['userRole'] === 'Teacher')
                    {{ $dashboardData['classStats']->last()['subjects_taught'] ?? 0 }}
                  @else
                    {{ $dashboardData['classStats']->sum('subjectsCount') }}
                  @endif
                </td>
              </tr>
              @if($dashboardData['userRole'] === 'Teacher')
              <tr>
                <td><i class="bx bx-task text-primary me-2"></i> Assignments</td>
                <td class="text-end">
                  {{ $dashboardData['assignments']->flatMap(fn($group) => $group['assignments'])->count() }}
                </td>
              </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Class-wise Breakdown for ClassTeacher and other roles -->
  @if($dashboardData['classStats']->isNotEmpty() && $dashboardData['userRole'] !== 'Teacher')
    <div class="col-12 mt-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            @if($dashboardData['userRole'] === 'ClassTeacher')
              Your Class Overview
            @else
              Classes Overview
            @endif
          </h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>Class</th>
                  <th>Students</th>
                  <th>Subjects</th>
                  <th>Assignments</th>
                </tr>
              </thead>
              <tbody>
                @foreach($dashboardData['classStats'] as $class)
                  <tr>
                    <td>{{ $class['class_name'] }}</td>
                    <td>{{ $class['studentsCount'] }}</td>
                    <td>{{ $class['subjectsCount'] }}</td>
                    <td>{{ $class['assignmentsCount'] }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- Teacher-specific statistics -->
  @if($dashboardData['userRole'] === 'Teacher' && $dashboardData['classStats']->isNotEmpty())

  @php
    $teacherStats = $dashboardData['classStats']->last();
    $classStats = $dashboardData['classStats']->filter(function($stat) {
      return isset($stat['class_name']);
    });
  @endphp

    <!-- Classes you teach (if available) -->
    @if($classStats->isNotEmpty())
      <div class="col-12 mt-4">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Classes You Teach</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Class</th>
                    <th>Students</th>
                    <th>Subjects</th>
                    <th>Assignments</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($classStats as $class)
                    <tr>
                      <td>{{ $class['class_name'] }}</td>
                      <td>{{ $class['studentsCount'] }}</td>
                      <td>{{ $class['subjectsCount'] }}</td>
                      <td>
                        {{ isset($dashboardData['assignments'][$class['class_id']]['assignments'])
                            ? count($dashboardData['assignments'][$class['class_id']]['assignments'])
                            : 0 }}
                    </td>


                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @endif
  @endif
</div>
@endsection
