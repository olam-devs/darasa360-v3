@extends('layouts.modernLayout', [
    'pageTitle' => 'Academic Dashboard',
    'pageSubtitle' => 'Welcome back, Academic Officer!'
])

@section('title', 'Academic Dashboard')

@section('content')
<div class="row">
  <!-- Welcome Banner -->
  <div class="col-xxl-8 mb-4 order-0">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="text-white mb-3">Welcome Back, Academic Officer!</h5>
            <p class="text-white mb-0">Empowering academic excellence through data-driven insights and management.</p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}"
                 height="200" class="scaleX-n1-rtl" alt="Academic">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Overview -->
  <div class="col-xxl-4 order-1 order-xxl-1 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Overview</h5>
        <i class="bx bx-bar-chart-alt-2 text-purple" style="font-size: 24px;"></i>
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
                  <h5 class="mb-0">{{ $dashboardData['total_students'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-blue me-3">
                      <i class="bx bx-book-open"></i>
                    </div>
                    <span>Total Classes</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['total_classes'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-green me-3">
                      <i class="bx bx-book"></i>
                    </div>
                    <span>Total Subjects</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['total_subjects'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-yellow me-3">
                      <i class="bx bx-user"></i>
                    </div>
                    <span>Total Teachers</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['total_teachers'] ?? 0 }}</h5>
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
        <h3 class="mb-1">{{ $dashboardData['total_students'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Total Students</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-book-open" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['total_classes'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Total Classes</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-book" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['total_subjects'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Total Subjects</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-yellow h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-user" style="font-size: 30px; color: #F59E0B;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['total_teachers'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Total Teachers</p>
      </div>
    </div>
  </div>

  <!-- Top Performing Subjects -->
  <div class="col-md-8 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-trophy me-2 text-purple"></i>Top Performing Subjects
        </h5>
      </div>
      <div class="modern-card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Rank</th>
                <th>Subject</th>
                <th>Class</th>
                <th>Average</th>
                <th>Pass Rate</th>
                <th>Students</th>
              </tr>
            </thead>
            <tbody>
              @forelse($dashboardData['top_subjects'] ?? [] as $index => $perf)
              <tr>
                <td>
                  <span class="modern-badge modern-badge-{{ $index < 3 ? 'purple' : 'blue' }}">
                    #{{ $index + 1 }}
                  </span>
                </td>
                <td><strong>{{ $perf->subject->name ?? 'N/A' }}</strong></td>
                <td>{{ $perf->classModel->name ?? 'N/A' }}</td>
                <td>{{ $perf->average_score }}%</td>
                <td>
                  <span class="modern-badge modern-badge-{{ $perf->pass_rate >= 70 ? 'green' : ($perf->pass_rate >= 50 ? 'yellow' : 'coral') }}">
                    {{ $perf->pass_rate }}%
                  </span>
                </td>
                <td>{{ $perf->total_students }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  <i class="bx bx-book-open" style="font-size: 48px; opacity: 0.3;"></i>
                  <p class="mt-2">No subject data available</p>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Upcoming Events & Exams -->
  <div class="col-md-4 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-calendar-event me-2 text-blue"></i>Upcoming Items
        </h5>
      </div>
      <div class="modern-card-body">
        <h6 class="mb-3">Upcoming Exams</h6>
        @forelse($upcomingExams ?? [] as $exam)
          <div class="d-flex align-items-center mb-3">
            <div class="modern-badge modern-badge-purple me-2">
              <i class="bx bx-notepad"></i>
            </div>
            <div class="flex-grow-1">
              <div class="fw-semibold">{{ $exam->name ?? 'Exam' }}</div>
              <small class="text-muted">{{ \Carbon\Carbon::parse($exam->start_date)->format('M d, Y') }}</small>
            </div>
          </div>
        @empty
          <p class="text-muted small">No upcoming exams</p>
        @endforelse

        <hr class="my-3">

        <h6 class="mb-3">Pending Verifications</h6>
        @forelse($pendingExams ?? [] as $exam)
          <div class="d-flex align-items-center mb-3">
            <div class="modern-badge modern-badge-yellow me-2">
              <i class="bx bx-time-five"></i>
            </div>
            <div class="flex-grow-1">
              <div class="fw-semibold">{{ $exam->name }}</div>
            </div>
          </div>
        @empty
          <p class="text-muted small">No pending verifications</p>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Performance Filters & Insights -->
  <div class="col-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-filter me-2 text-purple"></i>Performance Analysis Filters
        </h5>
      </div>
      <div class="modern-card-body">
        <form method="GET" action="{{ route('academic.dashboard') }}" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Select Class</label>
            <select name="class_filter" class="form-select" onchange="this.form.submit()">
              <option value="">All Classes</option>
              @foreach(\App\Models\ClassModel::orderBy('name')->get() as $cls)
                <option value="{{ $cls->id }}" {{ request('class_filter') == $cls->id ? 'selected' : '' }}>
                  {{ $cls->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Select Student</label>
            <select name="student_filter" class="form-select" onchange="this.form.submit()">
              <option value="">All Students</option>
              @foreach(\App\Models\Student::with('user')->orderBy('id')->get() as $std)
                <option value="{{ $std->id }}" {{ request('student_filter') == $std->id ? 'selected' : '' }}>
                  {{ $std->user->username ?? 'N/A' }} ({{ $std->classModel->name ?? 'N/A' }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Select Subject</label>
            <select name="subject_filter" class="form-select" onchange="this.form.submit()">
              <option value="">All Subjects</option>
              @foreach(\App\Models\Subject::orderBy('name')->get() as $subj)
                <option value="{{ $subj->id }}" {{ request('subject_filter') == $subj->id ? 'selected' : '' }}>
                  {{ $subj->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Select Exam</label>
            <select name="exam_filter" class="form-select" onchange="this.form.submit()">
              <option value="">Latest Exam</option>
              @foreach(\App\Models\Exam::orderBy('created_at', 'desc')->get() as $ex)
                <option value="{{ $ex->id }}" {{ request('exam_filter') == $ex->id ? 'selected' : '' }}>
                  {{ $ex->name ?? 'Exam' }}
                </option>
              @endforeach
            </select>
          </div>
          @if(request('class_filter') || request('student_filter') || request('subject_filter') || request('exam_filter'))
          <div class="col-12">
            <a href="{{ route('academic.dashboard') }}" class="btn btn-outline-secondary">
              <i class="bx bx-x me-1"></i> Clear Filters
            </a>
          </div>
          @endif
        </form>
      </div>
    </div>
  </div>

  <!-- Filtered Results Display -->
  @if(request('student_filter'))
  <div class="col-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-user me-2 text-blue"></i>Individual Student Performance
        </h5>
      </div>
      <div class="modern-card-body">
        @php
          $student = \App\Models\Student::with('user', 'classModel')->find(request('student_filter'));
          $studentMarks = collect();
          if ($student) {
            $examId = request('exam_filter') ?: \App\Models\Exam::latest()->first()?->id;
            if ($examId) {
              $studentMarks = \App\Models\ExamMark::where('student_id', $student->id)
                ->where('exam_id', $examId)
                ->with('subject')
                ->get();
            }
          }
        @endphp
        @if($student)
          <div class="row mb-4">
            <div class="col-md-6">
              <h6><strong>Student:</strong> {{ $student->user->username ?? 'N/A' }}</h6>
              <p class="text-muted mb-1"><strong>Class:</strong> {{ $student->classModel->name ?? 'N/A' }}</p>
              <p class="text-muted"><strong>Registration No:</strong> {{ $student->user->registration_no ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6 text-end">
              @if($studentMarks->count() > 0)
                <h3 class="text-{{ $studentMarks->avg('marks') >= 70 ? 'success' : ($studentMarks->avg('marks') >= 50 ? 'warning' : 'danger') }}">
                  {{ round($studentMarks->avg('marks'), 1) }}%
                </h3>
                <p class="text-muted">Average Score</p>
              @endif
            </div>
          </div>
          @if($studentMarks->count() > 0)
            <div class="table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Subject</th>
                    <th class="text-center">Marks</th>
                    <th class="text-center">Grade</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($studentMarks as $mark)
                  <tr>
                    <td>{{ $mark->subject->name ?? 'N/A' }}</td>
                    <td class="text-center"><strong>{{ $mark->marks }}%</strong></td>
                    <td class="text-center">
                      <span class="modern-badge modern-badge-{{ $mark->marks >= 80 ? 'purple' : ($mark->marks >= 60 ? 'blue' : ($mark->marks >= 50 ? 'yellow' : 'coral')) }}">
                        {{ $mark->marks >= 80 ? 'A' : ($mark->marks >= 60 ? 'B' : ($mark->marks >= 50 ? 'C' : ($mark->marks >= 40 ? 'D' : 'F'))) }}
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="text-{{ $mark->marks >= 50 ? 'success' : 'danger' }}">
                        {{ $mark->marks >= 50 ? 'Pass' : 'Fail' }}
                      </span>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="alert alert-info">No exam results found for this student.</div>
          @endif
        @endif
      </div>
    </div>
  </div>
  @endif

  @if(request('class_filter'))
  <div class="col-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-chalkboard me-2 text-green"></i>Class Performance Overview
        </h5>
      </div>
      <div class="modern-card-body">
        @php
          $selectedClass = \App\Models\ClassModel::find(request('class_filter'));
          $examId = request('exam_filter') ?: \App\Models\Exam::latest()->first()?->id;
          $classPerformance = collect();
          if ($selectedClass && $examId) {
            $classPerformance = \App\Models\SubjectPerformance::where('class_id', $selectedClass->id)
              ->where('exam_id', $examId)
              ->with('subject')
              ->get();
          }
        @endphp
        @if($selectedClass)
          <h6 class="mb-3">Class: <strong>{{ $selectedClass->name }}</strong></h6>
          @if($classPerformance->count() > 0)
            <div class="row mb-3">
              <div class="col-md-4">
                <div class="text-center p-3 performance-stat-box" style="border-radius: 8px;">
                  <h4 class="text-purple mb-1">{{ round($classPerformance->avg('average_score'), 1) }}%</h4>
                  <p class="text-muted mb-0">Class Average</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-center p-3 performance-stat-box" style="border-radius: 8px;">
                  <h4 class="text-blue mb-1">{{ round($classPerformance->avg('pass_rate'), 1) }}%</h4>
                  <p class="text-muted mb-0">Pass Rate</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-center p-3 performance-stat-box" style="border-radius: 8px;">
                  <h4 class="text-green mb-1">{{ $classPerformance->sum('total_students') }}</h4>
                  <p class="text-muted mb-0">Total Students</p>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Subject</th>
                    <th class="text-center">Average Score</th>
                    <th class="text-center">Pass Rate</th>
                    <th class="text-center">Students</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($classPerformance as $perf)
                  <tr>
                    <td>{{ $perf->subject->name ?? 'N/A' }}</td>
                    <td class="text-center">
                      <strong class="text-{{ $perf->average_score >= 70 ? 'success' : ($perf->average_score >= 50 ? 'warning' : 'danger') }}">
                        {{ round($perf->average_score, 1) }}%
                      </strong>
                    </td>
                    <td class="text-center">{{ round($perf->pass_rate, 1) }}%</td>
                    <td class="text-center">{{ $perf->total_students }}</td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="alert alert-info">No performance data available for this class.</div>
          @endif
        @endif
      </div>
    </div>
  </div>
  @endif

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
            <a href="{{ route('academic.students.index') }}" class="btn btn-outline-purple w-100 py-3">
              <i class="bx bx-user d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Students</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('academic.studentResults') }}" class="btn btn-outline-blue w-100 py-3">
              <i class="bx bx-trophy d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Student Results</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('academic.verification') }}" class="btn btn-outline-green w-100 py-3">
              <i class="bx bx-check-circle d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Verification</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('report-cards.index') }}" class="btn btn-outline-yellow w-100 py-3">
              <i class="bx bx-file d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Report Cards</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('calendar.index') }}" class="btn btn-outline-coral w-100 py-3">
              <i class="bx bx-calendar d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Calendar</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('notes.home') }}" class="btn btn-outline-pink w-100 py-3">
              <i class="bx bx-note d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Notes</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
