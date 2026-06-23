@extends('layouts.modernLayout', [
    'pageTitle' => 'Class Teacher Dashboard',
    'pageSubtitle' => 'Welcome back, ' . (auth()->user()->username ?? 'Class Teacher')
])

@section('title', 'Class Teacher Dashboard')

@section('vendor-style')
@vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('content')
<div class="row">
  <!-- Welcome Banner -->
  <div class="col-xxl-8 mb-4 order-0">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="text-white mb-3">Welcome Back, {{ auth()->user()->username ?? 'Class Teacher' }}!</h5>
            <p class="text-white mb-1"><strong>Your Class:</strong> {{ $class->name ?? 'N/A' }}</p>
            <p class="text-white mb-0">Guiding and nurturing the future—your dedication shapes their success!</p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}"
                 height="200" class="scaleX-n1-rtl" alt="Class Teacher">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Overview -->
  <div class="col-xxl-4 order-1 order-xxl-1 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Class Overview</h5>
        <i class="bx bx-chalkboard text-purple" style="font-size: 24px;"></i>
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
                  <h5 class="mb-0">{{ $totalStudents }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-blue me-3">
                      <i class="bx bx-trending-up"></i>
                    </div>
                    <span>Class Average</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ round($overallAverage, 1) }}%</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-green me-3">
                      <i class="bx bx-check-circle"></i>
                    </div>
                    <span>Passing Students</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $passCount }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-coral me-3">
                      <i class="bx bx-error-circle"></i>
                    </div>
                    <span>Need Attention</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $failCount }}</h5>
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
        <h3 class="mb-1">{{ $totalStudents }}</h3>
        <p class="text-muted mb-0">Total Students</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-trending-up" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ round($overallAverage, 1) }}%</h3>
        <p class="text-muted mb-0">Class Average</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-check-circle" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">{{ $passCount }}</h3>
        <p class="text-muted mb-0">Passing</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-coral h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(255, 107, 107, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-error-circle" style="font-size: 30px; color: #FF6B6B;"></i>
        </div>
        <h3 class="mb-1">{{ $failCount }}</h3>
        <p class="text-muted mb-0">Need Attention</p>
      </div>
    </div>
  </div>

  <!-- Class Ranking Card -->
  @if($classRanking && $classRanking['rank'] !== 'N/A')
  <div class="col-12 mb-4">
    <div class="modern-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
      <div class="modern-card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h5 class="text-white mb-3">
              <i class="bx bx-trophy me-2"></i>School-Wide Class Ranking
            </h5>
            <div class="row text-white">
              <div class="col-md-4">
                <h2 class="text-white mb-1">#{{ $classRanking['rank'] }}</h2>
                <p class="mb-0">Out of {{ $classRanking['total_classes'] }} classes</p>
              </div>
              <div class="col-md-4">
                <h2 class="text-white mb-1">{{ $classRanking['average'] }}%</h2>
                <p class="mb-0">Class Average</p>
              </div>
              <div class="col-md-4">
                @php
                  $performanceLevel = $classRanking['rank'] <= 3 ? 'Excellent' : ($classRanking['rank'] <= $classRanking['total_classes'] / 2 ? 'Good' : 'Needs Improvement');
                  $performanceIcon = $classRanking['rank'] <= 3 ? 'bx-star' : ($classRanking['rank'] <= $classRanking['total_classes'] / 2 ? 'bx-trending-up' : 'bx-trending-down');
                @endphp
                <h4 class="text-white mb-1"><i class="bx {{ $performanceIcon }} me-2"></i>{{ $performanceLevel }}</h4>
                <p class="mb-0">Performance Level</p>
              </div>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <i class="bx bx-trophy" style="font-size: 100px; color: rgba(255,255,255,0.2);"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  <!-- Subject-wise Ranking Comparison -->
  @if(count($subjectComparisons) > 0)
  <div class="col-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-bar-chart me-2 text-blue"></i>Subject-Wise Performance Comparison
        </h5>
      </div>
      <div class="modern-card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Subject</th>
                <th class="text-center">Class Rank</th>
                <th class="text-center">My Average</th>
                <th class="text-center">School Average</th>
                <th class="text-center">Top Class</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($subjectComparisons as $comparison)
              <tr>
                <td><strong>{{ $comparison['subject'] }}</strong></td>
                <td class="text-center">
                  <span class="modern-badge modern-badge-{{ $comparison['rank'] <= 3 ? 'purple' : 'blue' }}">
                    #{{ $comparison['rank'] }} / {{ $comparison['total_classes'] }}
                  </span>
                </td>
                <td class="text-center">
                  <strong class="text-{{ $comparison['my_average'] >= 70 ? 'success' : ($comparison['my_average'] >= 50 ? 'warning' : 'danger') }}">
                    {{ $comparison['my_average'] }}%
                  </strong>
                </td>
                <td class="text-center">{{ $comparison['school_average'] }}%</td>
                <td class="text-center">{{ $comparison['top_class_average'] }}%</td>
                <td class="text-center">
                  @php
                    $diff = $comparison['my_average'] - $comparison['school_average'];
                    $status = $diff > 0 ? 'Above' : ($diff < 0 ? 'Below' : 'Equal');
                    $statusColor = $diff > 0 ? 'success' : ($diff < 0 ? 'danger' : 'warning');
                    $statusIcon = $diff > 0 ? 'bx-trending-up' : ($diff < 0 ? 'bx-trending-down' : 'bx-minus');
                  @endphp
                  <span class="text-{{ $statusColor }}">
                    <i class="bx {{ $statusIcon }}"></i> {{ $status }}
                  </span>
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

  <!-- Subject Performance with Ranking -->
  <div class="col-md-8 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-bar-chart-alt me-2 text-purple"></i>Subject Performance Analysis
        </h5>
      </div>
      <div class="modern-card-body">
        @forelse($classPerformance as $perf)
          @php
            $avgScore = round($perf->average_score ?? 0, 1);
            $passRate = round($perf->pass_rate ?? 0, 1);
            $statusColor = $passRate >= 70 ? 'green' : ($passRate >= 50 ? 'yellow' : 'coral');
          @endphp
          <div class="mb-3 p-3 subject-performance-box" style="border-radius: 8px; border-left: 4px solid #7C3AED;">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h6 class="mb-1">{{ $perf->subject->name ?? 'N/A' }}</h6>
                <small class="text-muted">Average Score: {{ $avgScore }}%</small>
              </div>
              <span class="modern-badge modern-badge-{{ $statusColor }}">
                {{ $passRate }}% Pass Rate
              </span>
            </div>
            <div class="progress" style="height: 8px;">
              <div class="progress-bar bg-{{ $statusColor === 'green' ? 'success' : ($statusColor === 'yellow' ? 'warning' : 'danger') }}"
                   role="progressbar"
                   style="width: {{ min($avgScore, 100) }}%"
                   aria-valuenow="{{ $avgScore }}"
                   aria-valuemin="0"
                   aria-valuemax="100">
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-4">
                <small class="text-muted">Students</small>
                <div><strong>{{ $perf->total_students ?? 0 }}</strong></div>
              </div>
              <div class="col-4">
                <small class="text-muted">Passed</small>
                <div><strong>{{ $perf->students_passed ?? 0 }}</strong></div>
              </div>
              <div class="col-4">
                <small class="text-muted">Failed</small>
                <div><strong>{{ ($perf->total_students ?? 0) - ($perf->students_passed ?? 0) }}</strong></div>
              </div>
            </div>
          </div>
        @empty
          <div class="text-center text-muted py-4">
            <i class="bx bx-book-open" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No subject performance data available</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Top Performing Students -->
  <div class="col-md-4 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-trophy me-2 text-yellow"></i>Top Students
        </h5>
      </div>
      <div class="modern-card-body">
        @forelse(array_slice($studentsData, 0, 10) as $index => $student)
          <div class="d-flex align-items-center mb-3">
            <div class="modern-badge modern-badge-{{ $index < 3 ? 'purple' : 'blue' }} me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
              <strong>#{{ $index + 1 }}</strong>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-0">{{ $student['name'] }}</h6>
              <small class="text-muted">{{ $student['subjects_count'] }} subjects</small>
            </div>
            <div class="text-end">
              <strong class="text-{{ $student['average'] >= 70 ? 'success' : ($student['average'] >= 50 ? 'warning' : 'danger') }}">
                {{ $student['average'] }}%
              </strong>
              <br>
              <small class="text-muted">{{ $student['status'] }}</small>
            </div>
          </div>
        @empty
          <div class="text-center text-muted py-4">
            <i class="bx bx-user" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No student data available</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Upcoming Exams & Events -->
  <div class="col-md-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-calendar-check me-2 text-blue"></i>Upcoming Exams
        </h5>
      </div>
      <div class="modern-card-body">
        @forelse($upcomingExams->take(5) as $exam)
          <div class="d-flex align-items-center mb-3">
            <div class="modern-badge modern-badge-purple me-3">
              <i class="bx bx-notepad"></i>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-0">{{ $exam->name ?? 'Exam' }}</h6>
              <small class="text-muted">{{ \Carbon\Carbon::parse($exam->start_date)->format('M d, Y') }}</small>
            </div>
            <span class="modern-badge modern-badge-blue">
              {{ \Carbon\Carbon::parse($exam->start_date)->diffForHumans() }}
            </span>
          </div>
        @empty
          <div class="text-center text-muted py-4">
            <i class="bx bx-calendar" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No upcoming exams</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="col-md-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-calendar-event me-2 text-green"></i>Upcoming Events
        </h5>
      </div>
      <div class="modern-card-body">
        @forelse($upcomingEvents->take(5) as $event)
          <div class="d-flex align-items-center mb-3">
            <div class="modern-badge modern-badge-green me-3">
              <i class="bx bx-calendar"></i>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-0">{{ $event->title }}</h6>
              <small class="text-muted">{{ \Carbon\Carbon::parse($event->start_date)->format('M d, Y') }}</small>
            </div>
            <span class="modern-badge modern-badge-green">
              {{ \Carbon\Carbon::parse($event->start_date)->diffForHumans() }}
            </span>
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
            <a href="{{ route('teachers.students.index') }}" class="btn btn-outline-purple w-100 py-3">
              <i class="bx bx-user d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Students</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('attendance.home') }}" class="btn btn-outline-blue w-100 py-3">
              <i class="bx bx-check-circle d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Attendance</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('classTeacher.reportCards.index') }}" class="btn btn-outline-green w-100 py-3">
              <i class="bx bx-file d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Report Cards</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('notes.home') }}" class="btn btn-outline-yellow w-100 py-3">
              <i class="bx bx-note d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Notes</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('calendar.index') }}" class="btn btn-outline-coral w-100 py-3">
              <i class="bx bx-calendar d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Calendar</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('assignments.index') }}" class="btn btn-outline-pink w-100 py-3">
              <i class="bx bx-task d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Assignments</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
