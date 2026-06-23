@extends('layouts.modernLayout', [
    'pageTitle' => 'Teacher Dashboard',
    'pageSubtitle' => 'Welcome back, ' . (isset($teacher->user->username) ? $teacher->user->username : (auth()->user()->username ?? 'Teacher'))
])

@section('title', 'Teacher Dashboard')

@section('content')
<div class="row">
  <!-- Welcome Banner -->
  <div class="col-xxl-8 mb-4 order-0">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="text-white mb-3">Welcome Back, {{ $teacher->user->username ?? auth()->user()->username ?? 'Teacher' }}!</h5>
            <p class="text-white mb-0">Empowering minds and shaping futures—thank you for your dedication!</p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}"
                 height="200" class="scaleX-n1-rtl" alt="Teacher">
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
                      <i class="bx bx-book-open"></i>
                    </div>
                    <span>Subjects Teaching</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $subjectsCount }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-blue me-3">
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
                    <div class="modern-badge modern-badge-green me-3">
                      <i class="bx bx-trending-up"></i>
                    </div>
                    <span>Overall Average</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ round($overallAverage, 1) }}%</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-yellow me-3">
                      <i class="bx bx-calendar"></i>
                    </div>
                    <span>Upcoming Exams</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $upcomingExams->count() }}</h5>
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
          <i class="bx bx-book-open" style="font-size: 30px; color: #7C3AED;"></i>
        </div>
        <h3 class="mb-1">{{ $subjectsCount }}</h3>
        <p class="text-muted mb-0">Subjects</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-user" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ $totalStudents }}</h3>
        <p class="text-muted mb-0">Students</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-trending-up" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">{{ round($overallAverage, 1) }}%</h3>
        <p class="text-muted mb-0">Average Score</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-yellow h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-calendar" style="font-size: 30px; color: #F59E0B;"></i>
        </div>
        <h3 class="mb-1">{{ $upcomingExams->count() }}</h3>
        <p class="text-muted mb-0">Upcoming Exams</p>
      </div>
    </div>
  </div>

  <!-- Subject Performance -->
  <div class="col-md-8 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-line-chart me-2 text-purple"></i>My Subjects Performance
        </h5>
      </div>
      <div class="modern-card-body">
        @forelse($dashboardData as $data)
          <div class="mb-3 p-3 subject-performance-box" style="border-radius: 8px; border-left: 4px solid #7C3AED;">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="mb-1">{{ $data['subject'] }}</h6>
                <small class="text-muted">{{ $data['class'] }}</small>
              </div>
              <span class="modern-badge modern-badge-{{ $data['pass_rate'] >= 70 ? 'green' : ($data['pass_rate'] >= 50 ? 'yellow' : 'coral') }}">
                {{ $data['pass_rate'] }}% Pass Rate
              </span>
            </div>
            <div class="row mt-2">
              <div class="col-4">
                <small class="text-muted">Students</small>
                <div><strong>{{ $data['students_count'] }}</strong></div>
              </div>
              <div class="col-4">
                <small class="text-muted">Average</small>
                <div><strong>{{ $data['average_score'] }}%</strong></div>
              </div>
              <div class="col-4">
                <small class="text-muted">Range</small>
                <div><strong>{{ $data['lowest'] }}-{{ $data['highest'] }}</strong></div>
              </div>
            </div>
          </div>
        @empty
          <div class="text-center text-muted py-4">
            <i class="bx bx-book-open" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No performance data available</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Upcoming Events & Exams -->
  <div class="col-md-4 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-calendar-event me-2 text-blue"></i>Upcoming Events
        </h5>
      </div>
      <div class="modern-card-body">
        <h6 class="mb-3">Exams</h6>
        @forelse($upcomingExams->take(3) as $exam)
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

        <h6 class="mb-3">Events</h6>
        @forelse($upcomingEvents->take(3) as $event)
          <div class="d-flex align-items-center mb-3">
            <div class="modern-badge modern-badge-blue me-2">
              <i class="bx bx-calendar"></i>
            </div>
            <div class="flex-grow-1">
              <div class="fw-semibold">{{ $event->title }}</div>
              <small class="text-muted">{{ \Carbon\Carbon::parse($event->start_date)->format('M d, Y') }}</small>
            </div>
          </div>
        @empty
          <p class="text-muted small">No upcoming events</p>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Performance Trends Chart -->
  <div class="col-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-line-chart-down me-2 text-blue"></i>Student Performance Trends
        </h5>
      </div>
      <div class="modern-card-body">
        @if(count($dashboardData) > 0)
          <div id="performanceTrendsChart"></div>
        @else
          <div class="text-center text-muted py-5">
            <i class="bx bx-line-chart" style="font-size: 64px; opacity: 0.3;"></i>
            <p class="mt-3">No performance data available to display trends</p>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Grade Distribution Chart -->
  <div class="col-md-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-pie-chart-alt me-2 text-purple"></i>Grade Distribution
        </h5>
      </div>
      <div class="modern-card-body">
        @if(count($dashboardData) > 0)
          <div id="gradeDistributionChart"></div>
        @else
          <div class="text-center text-muted py-5">
            <i class="bx bx-pie-chart" style="font-size: 64px; opacity: 0.3;"></i>
            <p class="mt-3">No grade data available</p>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Pass vs Fail Chart -->
  <div class="col-md-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-bar-chart me-2 text-green"></i>Pass Rate by Subject
        </h5>
      </div>
      <div class="modern-card-body">
        @if(count($dashboardData) > 0)
          <div id="passRateChart"></div>
        @else
          <div class="text-center text-muted py-5">
            <i class="bx bx-bar-chart" style="font-size: 64px; opacity: 0.3;"></i>
            <p class="mt-3">No pass rate data available</p>
          </div>
        @endif
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
            <a href="{{ route('exams.index') }}" class="btn btn-outline-blue w-100 py-3">
              <i class="bx bx-notepad d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Exams</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('assignments.index') }}" class="btn btn-outline-green w-100 py-3">
              <i class="bx bx-task d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Assignments</span>
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
            <a href="{{ route('attendance.home') }}" class="btn btn-outline-pink w-100 py-3">
              <i class="bx bx-check-circle d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Attendance</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const dashboardData = @json($dashboardData);

  if (dashboardData && dashboardData.length > 0) {
    // Performance Trends Chart
    const subjectNames = dashboardData.map(d => d.subject + ' (' + d.class + ')');
    const averageScores = dashboardData.map(d => d.average_score);
    const passRates = dashboardData.map(d => d.pass_rate);

    if (document.getElementById('performanceTrendsChart')) {
      const performanceTrendsOptions = {
        series: [{
          name: 'Average Score',
          data: averageScores
        }, {
          name: 'Pass Rate',
          data: passRates
        }],
        chart: {
          height: 350,
          type: 'line',
          toolbar: {
            show: true
          }
        },
        colors: ['#7C3AED', '#3B82F6'],
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth',
          width: 3
        },
        xaxis: {
          categories: subjectNames
        },
        yaxis: {
          title: {
            text: 'Percentage (%)'
          },
          min: 0,
          max: 100
        },
        legend: {
          position: 'top'
        },
        tooltip: {
          y: {
            formatter: function(val) {
              return val + '%';
            }
          }
        }
      };
      const performanceTrendsChart = new ApexCharts(document.querySelector("#performanceTrendsChart"), performanceTrendsOptions);
      performanceTrendsChart.render();
    }

    // Grade Distribution Chart (aggregate all subjects)
    const totalGrades = dashboardData.reduce((acc, d) => {
      return {
        A: acc.A + (d.grades.A || 0),
        B: acc.B + (d.grades.B || 0),
        C: acc.C + (d.grades.C || 0),
        D: acc.D + (d.grades.D || 0),
        F: acc.F + (d.grades.F || 0)
      };
    }, {A: 0, B: 0, C: 0, D: 0, F: 0});

    if (document.getElementById('gradeDistributionChart')) {
      const gradeDistributionOptions = {
        series: [totalGrades.A, totalGrades.B, totalGrades.C, totalGrades.D, totalGrades.F],
        chart: {
          type: 'donut',
          height: 300
        },
        labels: ['Grade A (80-100)', 'Grade B (60-79)', 'Grade C (50-59)', 'Grade D (40-49)', 'Grade F (0-39)'],
        colors: ['#10B981', '#3B82F6', '#F59E0B', '#FF9800', '#FF6B6B'],
        legend: {
          position: 'bottom'
        },
        plotOptions: {
          pie: {
            donut: {
              size: '65%',
              labels: {
                show: true,
                total: {
                  show: true,
                  label: 'Total Students',
                  formatter: function (w) {
                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                  }
                }
              }
            }
          }
        }
      };
      const gradeDistributionChart = new ApexCharts(document.querySelector("#gradeDistributionChart"), gradeDistributionOptions);
      gradeDistributionChart.render();
    }

    // Pass Rate by Subject Chart
    if (document.getElementById('passRateChart')) {
      const passRateOptions = {
        series: [{
          name: 'Pass Rate',
          data: passRates
        }],
        chart: {
          type: 'bar',
          height: 300,
          toolbar: {
            show: false
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 8,
            dataLabels: {
              position: 'top'
            },
            colors: {
              ranges: [{
                from: 0,
                to: 50,
                color: '#FF6B6B'
              }, {
                from: 50,
                to: 70,
                color: '#F59E0B'
              }, {
                from: 70,
                to: 100,
                color: '#10B981'
              }]
            }
          }
        },
        dataLabels: {
          enabled: true,
          formatter: function (val) {
            return val + '%';
          },
          offsetY: -20,
          style: {
            fontSize: '12px',
            colors: ["#304758"]
          }
        },
        xaxis: {
          categories: subjectNames,
          labels: {
            rotate: -45,
            rotateAlways: true
          }
        },
        yaxis: {
          title: {
            text: 'Pass Rate (%)'
          },
          min: 0,
          max: 100
        }
      };
      const passRateChart = new ApexCharts(document.querySelector("#passRateChart"), passRateOptions);
      passRateChart.render();
    }
  }
});
</script>
@endpush
@endsection
