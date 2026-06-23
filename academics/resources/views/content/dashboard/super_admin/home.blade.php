@extends('layouts.modernLayout', [
    'pageTitle' => 'Super Admin Dashboard'
])

@section('title', 'Dashboard - Super Admin')

@section('content')
<div class="row">
  <!-- Welcome Banner -->
  <div class="col-12 mb-4">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="text-white mb-3">Welcome Back, Super Admin!</h5>
            <p class="text-white mb-0">Managing all schools and system administration.</p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left d-none d-sm-block">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/man-with-laptop.png')}}"
                 height="200" class="scaleX-n1-rtl" alt="Super Admin">
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
          <i class="bx bx-buildings" style="font-size: 30px; color: #7C3AED;"></i>
        </div>
        <h3 class="mb-1">{{ $schools->total() }}</h3>
        <p class="text-muted mb-0">Total Schools</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-check-circle" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ $schools->where('status', 'active')->count() }}</h3>
        <p class="text-muted mb-0">Active Schools</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-yellow h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-x-circle" style="font-size: 30px; color: #F59E0B;"></i>
        </div>
        <h3 class="mb-1">{{ $schools->where('status', 'inactive')->count() }}</h3>
        <p class="text-muted mb-0">Inactive Schools</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-user-check" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">{{ $schools->count() }}</h3>
        <p class="text-muted mb-0">All Schools</p>
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
            <a href="{{ route('admins.schools.index') }}" class="btn btn-outline-purple w-100 py-3">
              <i class="bx bx-buildings d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Schools</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('admins.admins.index') }}" class="btn btn-outline-blue w-100 py-3">
              <i class="bx bx-user-check d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Admins</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('admins.passwords') }}" class="btn btn-outline-green w-100 py-3">
              <i class="bx bx-lock d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Passwords</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('admins.locations') }}" class="btn btn-outline-yellow w-100 py-3">
              <i class="bx bx-map d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Locations</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('admins.logs') }}" class="btn btn-outline-coral w-100 py-3">
              <i class="bx bx-file d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Logs</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('admins.sms.index') }}" class="btn btn-outline-pink w-100 py-3">
              <i class="bx bx-message-dots d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">SMS System</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Schools List -->
  <div class="col-md-12">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-buildings me-2 text-purple"></i>Schools List
        </h5>
      </div>
      <div class="modern-card-body">
        <div id="schoolTableSection">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>School Name</th>
                  <th>Location</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($schools as $school)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="modern-badge modern-badge-purple me-2">
                          <i class="bx bx-buildings"></i>
                        </div>
                        <strong>{{ $school->name }}</strong>
                      </div>
                    </td>
                    <td>{{ $school->location->name ?? '-' }}</td>
                    <td>
                      @if($school->status === 'active')
                        <span class="modern-badge modern-badge-green">
                          <i class="bx bx-check-circle me-1"></i>Active
                        </span>
                      @else
                        <span class="modern-badge modern-badge-yellow">
                          <i class="bx bx-x-circle me-1"></i>Inactive
                        </span>
                      @endif
                    </td>
                    <td>
                      <button class="btn btn-sm btn-purple view-stats-btn" data-id="{{ $school->id }}">
                        <i class="bx bx-show me-1"></i>View Stats
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $schools->links('pagination::bootstrap-5') }}
          </div>
        </div>

        <!-- Stats Section (Hidden by default) -->
        <div class="d-none" id="schoolStatsSection">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">School Statistics</h5>
            <button class="btn btn-sm btn-secondary" id="backToSchools">
              <i class="bx bx-arrow-back me-1"></i>Back to Schools
            </button>
          </div>
          <div class="row g-3">
            <div class="col-lg-3 col-md-6">
              <div class="modern-card modern-card-purple">
                <div class="modern-card-body text-center">
                  <p class="mb-1 text-muted">Total Students</p>
                  <h4 class="mb-0" id="totalStudents">-</h4>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="modern-card modern-card-blue">
                <div class="modern-card-body text-center">
                  <p class="mb-1 text-muted">Total Teachers</p>
                  <h4 class="mb-0" id="totalTeachers">-</h4>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="modern-card modern-card-green">
                <div class="modern-card-body text-center">
                  <p class="mb-1 text-muted">Total SMS Sent</p>
                  <h4 class="mb-0" id="totalSmsSent">-</h4>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="modern-card modern-card-yellow">
                <div class="modern-card-body text-center">
                  <p class="mb-1 text-muted">Total Assignments</p>
                  <h4 class="mb-0" id="totalAssignments">-</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const viewButtons = document.querySelectorAll('.view-stats-btn');
  const backBtn = document.getElementById('backToSchools');
  const tableSection = document.getElementById('schoolTableSection');
  const statsSection = document.getElementById('schoolStatsSection');

  const totalStudentsElem = document.getElementById('totalStudents');
  const totalTeachersElem = document.getElementById('totalTeachers');
  const totalSmsSentElem = document.getElementById('totalSmsSent');
  const totalAssignmentsElem = document.getElementById('totalAssignments');

  function setLoading() {
    totalStudentsElem.textContent = 'Loading...';
    totalTeachersElem.textContent = 'Loading...';
    totalSmsSentElem.textContent = 'Loading...';
    totalAssignmentsElem.textContent = 'Loading...';
  }

  function clearStats() {
    totalStudentsElem.textContent = '-';
    totalTeachersElem.textContent = '-';
    totalSmsSentElem.textContent = '-';
    totalAssignmentsElem.textContent = '-';
  }

  viewButtons.forEach(button => {
    button.addEventListener('click', () => {
      const schoolId = button.dataset.id;
      tableSection.classList.add('d-none');
      statsSection.classList.remove('d-none');
      setLoading();

      fetch(`/admins/school/${schoolId}/stats`)
        .then(response => {
          if (!response.ok) throw new Error('Network error');
          return response.json();
        })
        .then(data => {
          if (data.error) {
            clearStats();
            alert(data.error);
          } else {
            totalStudentsElem.textContent = data.total_students ?? '-';
            totalTeachersElem.textContent = data.total_teachers ?? '-';
            totalSmsSentElem.textContent = data.total_sms_sent ?? '-';
            totalAssignmentsElem.textContent = data.total_assignments ?? '-';
          }
        })
        .catch(() => {
          clearStats();
          alert('Failed to fetch stats.');
        });
    });
  });

  backBtn.addEventListener('click', () => {
    statsSection.classList.add('d-none');
    tableSection.classList.remove('d-none');
    clearStats();
  });
});
</script>
@endsection
