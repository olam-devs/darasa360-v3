@extends('layouts.modernLayout', [
    'pageTitle' => 'Welcome Back, {{ $dashboardData["username"] ?? "Academic" }}.'
])

@section('title', 'Dashboard - Academic')

@section('content')
<div class="row">
  <!-- Welcome Banner -->
  <div class="col-xxl-8 mb-4 order-0">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="text-white mb-3">Welcome Back, {{ $dashboardData['username'] ?? 'Academic' }}!</h5>
            <p class="text-white mb-0">Your commitment to academic excellence strengthens the foundation of our students' success—keep leading with purpose.</p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}"
                 height="200" class="scaleX-n1-rtl" alt="Academic User">
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
                    <div class="modern-badge modern-badge-green me-3">
                      <i class="bx bx-book-open"></i>
                    </div>
                    <span>Total Subjects</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['subjectsCount'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-yellow me-3">
                      <i class="bx bx-notepad"></i>
                    </div>
                    <span>Active Exams</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['examsCount'] ?? 0 }}</h5>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
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
            <a href="{{ route('academic.students.index') }}" class="btn btn-outline-purple w-100 py-3">
              <i class="bx bx-user d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Students</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('academic.results.index') }}" class="btn btn-outline-blue w-100 py-3">
              <i class="bx bx-bar-chart-alt-2 d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Student Results</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('academic.verification') }}" class="btn btn-outline-green w-100 py-3">
              <i class="bx bx-check-shield d-block mb-2" style="font-size: 28px;"></i>
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
            <a href="{{ route('academic.notes') }}" class="btn btn-outline-pink w-100 py-3">
              <i class="bx bx-note d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Notes</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards Grid -->
  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-purple h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(124, 58, 237, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-user-check" style="font-size: 30px; color: #7C3AED;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['verifiedCount'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Verified Students</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-user-x" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['unverifiedCount'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Pending Verification</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-file" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['reportCardsCount'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Report Cards</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-yellow h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-clipboard" style="font-size: 30px; color: #F59E0B;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['resultsCount'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Exam Results</p>
      </div>
    </div>
  </div>

  <!-- Performance Charts -->
  <div class="col-lg-6 col-md-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-bar-chart-alt-2 me-2 text-purple"></i>Class Performance Comparison
        </h5>
      </div>
      <div class="modern-card-body">
        <div id="classPerformanceChart"></div>
      </div>
    </div>
  </div>

  <div class="col-lg-6 col-md-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-book-open me-2 text-blue"></i>Subject Performance Analysis
        </h5>
      </div>
      <div class="modern-card-body">
        <div id="subjectPerformanceChart"></div>
      </div>
    </div>
  </div>

  <div class="col-lg-6 col-md-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-pie-chart-alt-2 me-2 text-green"></i>Overall Grade Distribution
        </h5>
      </div>
      <div class="modern-card-body">
        <div id="gradeDistributionChart"></div>
      </div>
    </div>
  </div>

  <div class="col-lg-6 col-md-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-trending-up me-2 text-yellow"></i>Pass Rate Trend
        </h5>
      </div>
      <div class="modern-card-body">
        <div id="passRateTrendChart"></div>
      </div>
    </div>
  </div>

  <!-- Recent Activity -->
  <div class="col-md-12">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-time-five me-2 text-blue"></i>Recent Activities
        </h5>
      </div>
      <div class="modern-card-body">
        <div class="alert alert-info mb-0">
          <i class="bx bx-info-circle me-2"></i>
          <strong>Academic Dashboard:</strong> Monitor student verification, manage report cards, and oversee academic performance across all classes.
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const performanceData = @json($performanceData ?? []);

  // Class Performance Chart
  if (performanceData.classPerformance && performanceData.classPerformance.length > 0) {
    const classNames = performanceData.classPerformance.map(item => item.class);
    const classAverages = performanceData.classPerformance.map(item => item.average);

    const classPerformanceOptions = {
      series: [{
        name: 'Average Score',
        data: classAverages
      }],
      chart: {
        type: 'bar',
        height: 350,
        toolbar: { show: true }
      },
      colors: ['#7C3AED'],
      plotOptions: {
        bar: {
          borderRadius: 8,
          horizontal: false,
          columnWidth: '60%',
        }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return val.toFixed(1) + '%';
        }
      },
      xaxis: {
        categories: classNames,
        title: { text: 'Classes' }
      },
      yaxis: {
        title: { text: 'Average Score' },
        max: 100
      },
      tooltip: {
        y: {
          formatter: function(val) {
            return val.toFixed(2) + '%';
          }
        }
      }
    };

    const classPerformanceChart = new ApexCharts(
      document.querySelector("#classPerformanceChart"),
      classPerformanceOptions
    );
    classPerformanceChart.render();
  } else {
    document.querySelector("#classPerformanceChart").innerHTML =
      '<div class="text-center text-muted py-5"><i class="bx bx-info-circle mb-2" style="font-size: 48px;"></i><p>No performance data available</p></div>';
  }

  // Subject Performance Chart
  if (performanceData.subjectPerformance && performanceData.subjectPerformance.length > 0) {
    const subjectNames = performanceData.subjectPerformance.map(item => item.subject);
    const subjectAverages = performanceData.subjectPerformance.map(item => item.average);

    const subjectPerformanceOptions = {
      series: [{
        name: 'Average Score',
        data: subjectAverages
      }],
      chart: {
        type: 'bar',
        height: 350,
        toolbar: { show: true }
      },
      colors: ['#3B82F6'],
      plotOptions: {
        bar: {
          borderRadius: 8,
          horizontal: true,
        }
      },
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return val.toFixed(1) + '%';
        }
      },
      xaxis: {
        title: { text: 'Average Score' },
        max: 100
      },
      yaxis: {
        title: { text: 'Subjects' }
      },
      tooltip: {
        y: {
          formatter: function(val) {
            return val.toFixed(2) + '%';
          }
        }
      }
    };

    // Truncate long subject names for better display
    subjectPerformanceOptions.yaxis.categories = subjectNames.map(name =>
      name.length > 20 ? name.substring(0, 18) + '...' : name
    );
    subjectPerformanceOptions.xaxis.categories = subjectNames;

    const subjectPerformanceChart = new ApexCharts(
      document.querySelector("#subjectPerformanceChart"),
      subjectPerformanceOptions
    );
    subjectPerformanceChart.render();
  } else {
    document.querySelector("#subjectPerformanceChart").innerHTML =
      '<div class="text-center text-muted py-5"><i class="bx bx-info-circle mb-2" style="font-size: 48px;"></i><p>No performance data available</p></div>';
  }

  // Grade Distribution Chart
  if (performanceData.gradeDistribution) {
    const grades = Object.keys(performanceData.gradeDistribution);
    const counts = Object.values(performanceData.gradeDistribution);
    const totalStudents = counts.reduce((a, b) => a + b, 0);

    if (totalStudents > 0) {
      const gradeDistributionOptions = {
        series: counts,
        chart: {
          type: 'donut',
          height: 350
        },
        labels: grades,
        colors: ['#10B981', '#3B82F6', '#F59E0B', '#F97316', '#EF4444'],
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
                  formatter: function () {
                    return totalStudents;
                  }
                }
              }
            }
          }
        },
        dataLabels: {
          enabled: true,
          formatter: function(val, opts) {
            return opts.w.config.series[opts.seriesIndex];
          }
        },
        tooltip: {
          y: {
            formatter: function(val) {
              const percentage = ((val / totalStudents) * 100).toFixed(1);
              return val + ' students (' + percentage + '%)';
            }
          }
        }
      };

      const gradeDistributionChart = new ApexCharts(
        document.querySelector("#gradeDistributionChart"),
        gradeDistributionOptions
      );
      gradeDistributionChart.render();
    } else {
      document.querySelector("#gradeDistributionChart").innerHTML =
        '<div class="text-center text-muted py-5"><i class="bx bx-info-circle mb-2" style="font-size: 48px;"></i><p>No grade data available</p></div>';
    }
  }

  // Pass Rate Trend Chart
  if (performanceData.passRateTrend && performanceData.passRateTrend.length > 0) {
    const examNames = performanceData.passRateTrend.map(item => item.exam);
    const passRates = performanceData.passRateTrend.map(item => item.pass_rate);

    const passRateTrendOptions = {
      series: [{
        name: 'Pass Rate',
        data: passRates
      }],
      chart: {
        type: 'line',
        height: 350,
        toolbar: { show: true }
      },
      colors: ['#F59E0B'],
      stroke: {
        curve: 'smooth',
        width: 3
      },
      markers: {
        size: 6,
        colors: ['#F59E0B'],
        strokeColors: '#fff',
        strokeWidth: 2,
        hover: {
          size: 8
        }
      },
      xaxis: {
        categories: examNames,
        title: { text: 'Exams' }
      },
      yaxis: {
        title: { text: 'Pass Rate (%)' },
        max: 100,
        min: 0
      },
      tooltip: {
        y: {
          formatter: function(val) {
            return val.toFixed(2) + '%';
          }
        }
      },
      grid: {
        borderColor: '#e7e7e7'
      }
    };

    const passRateTrendChart = new ApexCharts(
      document.querySelector("#passRateTrendChart"),
      passRateTrendOptions
    );
    passRateTrendChart.render();
  } else {
    document.querySelector("#passRateTrendChart").innerHTML =
      '<div class="text-center text-muted py-5"><i class="bx bx-info-circle mb-2" style="font-size: 48px;"></i><p>No pass rate trend data available</p></div>';
  }
});
</script>
@endpush
@endsection
