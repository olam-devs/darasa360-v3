@extends('layouts.modernLayout', [
    'pageTitle' => 'Class Attendance'
])

@section('title', 'Attendance Overview')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Class Attendance Overview</h5>
        <small class="text-muted">Track and analyze student attendance patterns</small>
      </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    {{-- Filter Card --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Filter Attendance Data</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('student.classAttendance') }}" method="GET" class="row g-3" id="attendanceForm">
          <div class="col-md-6">
            <label for="class_id" class="form-label">Select Class</label>
            <select name="class_id" id="class_id" class="form-select" required>
              <option value="">-- Choose Class --</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                  {{ $class->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label for="attendance_id" class="form-label">Attendance Type</label>
            <select name="attendance_id" id="attendance_id" class="form-select" required>
              <option value="">-- Select Attendance Type --</option>
              {{-- Dynamically loaded via JS --}}
            </select>
          </div>

          <div class="col-md-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-search-alt me-1"></i> Fetch Attendance
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- Attendance Data --}}
    @if(isset($attendanceData) && count($attendanceData) > 0)
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Attendance Records</h5>
          <div class="dropdown">
            <button class="btn p-0" type="button" id="actionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bx bx-dots-vertical-rounded"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown">
              <li><a class="dropdown-item" href="javascript:void(0);">Export as PDF</a></li>
              <li><a class="dropdown-item" href="javascript:void(0);">Export as Excel</a></li>
            </ul>
          </div>
        </div>

        <div class="table-responsive text-nowrap">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Student Name</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Total</th>
                <th class="text-center">Attendance %</th>
                <th class="text-center">Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              @foreach($attendanceData as $student)
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-sm me-2">
                        <span class="avatar-initial rounded-circle bg-label-primary">{{ substr($student['student_name'], 0, 1) }}</span>
                      </div>
                      <span>{{ $student['student_name'] }}</span>
                    </div>
                  </td>
                  <td class="text-center text-success">{{ $student['presentCount'] }}</td>
                  <td class="text-center text-danger">{{ $student['absentCount'] }}</td>
                  <td class="text-center">{{ count($student['attendanceRecords']) }}</td>
                  <td class="text-center">
                    <div class="progress" style="height: 6px;">
                      <div class="progress-bar bg-{{ $student['attendancePercentage'] >= 75 ? 'success' : ($student['attendancePercentage'] >= 50 ? 'warning' : 'danger') }}"
                           role="progressbar" style="width: {{ $student['attendancePercentage'] }}%"
                           aria-valuenow="{{ $student['attendancePercentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small>{{ $student['attendancePercentage'] }}%</small>
                  </td>
                  <td class="text-center">
                    @if($student['attendancePercentage'] >= 75)
                      <span class="badge bg-label-success">Good</span>
                    @elseif($student['attendancePercentage'] >= 50)
                      <span class="badge bg-label-warning">Fair</span>
                    @else
                      <span class="badge bg-label-danger">Poor</span>
                    @endif
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary view-history" data-student='@json($student)'>
                      <i class="bx bx-history me-1"></i> Details
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Summary Card --}}
        <div class="card-footer">
          <div class="row">
            <div class="col-md-6">
              <h6 class="mb-3">Class Summary</h6>
              @php
                $totalDays = 0;
                $presentDays = 0;
                $absentDays = 0;
              @endphp
              @foreach($attendanceData as $student)
                @php
                  $totalDays += count($student['attendanceRecords']);
                  $presentDays += $student['presentCount'];
                  $absentDays += $student['absentCount'];
                @endphp
              @endforeach
              @php
                $attendancePercentage = $totalDays > 0 ? round((($presentDays) / $totalDays) * 100, 2) : 0;
              @endphp
              <div class="d-flex justify-content-between mb-2">
                <span>Total Days:</span>
                <span class="fw-bold">{{ $totalDays }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Present Days:</span>
                <span class="fw-bold text-success">{{ $presentDays }}</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Absent Days:</span>
                <span class="fw-bold text-danger">{{ $absentDays }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Overall Attendance:</span>
                <span class="fw-bold">{{ $attendancePercentage }}%</span>
              </div>
            </div>
            <div class="col-md-6">
              <h6 class="mb-3">Attendance Distribution</h6>
              <canvas id="attendanceChart" height="200"></canvas>
            </div>
          </div>
        </div>
      </div>
    @elseif(request()->has(['class_id', 'attendance_id']))
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="bx bx-clipboard bx-lg text-muted mb-3"></i>
          <h5>No attendance data found</h5>
          <p class="text-muted">There are no records for the selected class and attendance type.</p>
        </div>
      </div>
    @endif
  </div>
</div>

{{-- Modal for attendance history --}}
<div class="modal fade" id="attendanceHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="attendanceHistoryModalLabel">Attendance History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-borderless">
            <thead class="border-bottom">
              <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="attendanceHistoryBody">
              {{-- Filled dynamically --}}
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const classSelect = document.getElementById('class_id');
  const attendanceSelect = document.getElementById('attendance_id');

  function loadAttendanceTypes(classId, selectedAttendanceId = null) {
    attendanceSelect.innerHTML = '<option>Loading...</option>';
    if (!classId) {
      attendanceSelect.innerHTML = '<option value="">-- Select Attendance Type --</option>';
      return;
    }
    fetch(`/students/attendances/by-class/${classId}`)
      .then(response => response.json())
      .then(data => {
        attendanceSelect.innerHTML = '<option value="">-- Select Attendance Type --</option>';
        if (data.length === 0) {
          attendanceSelect.innerHTML = '<option value="">No attendance types found</option>';
        } else {
          data.forEach(attendance => {
            const option = document.createElement('option');
            option.value = attendance.id;
            const fromDate = new Date(attendance.from_date).toLocaleDateString();
            const toDate = new Date(attendance.to_date).toLocaleDateString();
            option.textContent = `${attendance.title} (${fromDate} - ${toDate})`;
            if (selectedAttendanceId && selectedAttendanceId == attendance.id) {
              option.selected = true;
            }
            attendanceSelect.appendChild(option);
          });
        }
      })
      .catch(() => {
        attendanceSelect.innerHTML = '<option value="">Failed to load attendance types</option>';
      });
  }

  classSelect.addEventListener('change', function () {
    loadAttendanceTypes(this.value);
  });

  @if(request('class_id'))
    loadAttendanceTypes(@json(request('class_id')), @json(request('attendance_id')));
  @endif

  // Handle View History button clicks
  document.querySelectorAll('.view-history').forEach(btn => {
    btn.addEventListener('click', function () {
      const studentData = JSON.parse(this.dataset.student);
      document.getElementById('attendanceHistoryModalLabel').innerText = `${studentData.student_name}'s Attendance History`;

      const tbody = document.getElementById('attendanceHistoryBody');
      tbody.innerHTML = '';

      studentData.attendanceRecords.forEach(record => {
        const tr = document.createElement('tr');

        // Date
        const dateTd = document.createElement('td');
        const recordDate = new Date(record.date);
        dateTd.textContent = recordDate.toLocaleDateString();

        // Day
        const dayTd = document.createElement('td');
        dayTd.textContent = recordDate.toLocaleString('default', { weekday: 'short' });

        // Status
        const statusTd = document.createElement('td');
        const statusBadge = document.createElement('span');
        statusBadge.className = `badge bg-label-${record.status === 'absent' ? 'danger' : (record.status === 'late' ? 'warning' : 'success')}`;
        statusBadge.textContent = record.status.charAt(0).toUpperCase() + record.status.slice(1);
        statusTd.appendChild(statusBadge);

        // Notes (assuming record might have notes)


        tr.appendChild(dateTd);
        tr.appendChild(dayTd);
        tr.appendChild(statusTd);
        tbody.appendChild(tr);
      });

      new bootstrap.Modal(document.getElementById('attendanceHistoryModal')).show();
    });
  });

  // Initialize chart if attendance data exists
  @if(isset($attendanceData) && count($attendanceData) > 0)
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Present', 'Absent'],
        datasets: [{
          data: [{{ $presentDays }}, {{ $absentDays }}],
          backgroundColor: [
            'rgba(40, 199, 111, 0.8)',
            'rgba(234, 84, 85, 0.8)'
          ],
          borderColor: [
            'rgba(40, 199, 111, 1)',
            'rgba(234, 84, 85, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} (${percentage}%)`;
              }
            }
          }
        }
      }
    });
  @endif
});
</script>
@endsection