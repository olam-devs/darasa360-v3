@php
  $studentName = $dashboardData['student']->username ?? $dashboardData['student']->name ?? session('username', 'Student');
@endphp

@extends('layouts.modernLayout', [
    'pageTitle' => 'Welcome, ' . $studentName . '!'
])

@section('title', 'Student Dashboard')

@section('content')
<div class="row">
  <!-- Welcome Banner -->
  <div class="col-12 mb-4">
    <div class="modern-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
      <div class="modern-card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h3 class="text-white mb-2">Welcome Back, {{ $studentName }}!</h3>
            <p class="text-white mb-2">
              <i class="bx bx-chalkboard me-2"></i>
              <strong>Class:</strong> {{ $dashboardData['class']->name ?? 'N/A' }}
            </p>

            @if(!empty($dashboardData['student']->comments))
              <div class="mt-3 p-3" style="background: rgba(255,255,255,0.2); border-radius: 8px;">
                <h6 class="text-white mb-2"><i class="bx bx-message-detail me-2"></i>Teacher's Comments:</h6>
                <div class="p-2" style="background: rgba(255,255,255,0.9); border-radius: 6px; color: #333;">
                  {!! nl2br(e($dashboardData['student']->comments)) !!}
                </div>
                @if(!empty($dashboardData['student']->updated_at))
                <small class="text-white d-block mt-2">
                  <i class="bx bx-time-five me-1"></i>
                  Last updated: {{ \Carbon\Carbon::parse($dashboardData['student']->updated_at)->format('M d, Y') }}
                </small>
                @endif
              </div>
            @endif
          </div>
          <div class="col-md-4 text-center d-none d-md-block">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender', 'male')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}"
                 height="150" alt="Student">
          </div>
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
        <h3 class="mb-1">{{ $dashboardData['subjects']->count() }}</h3>
        <p class="text-muted mb-0">Total Subjects</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-bullhorn" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['announcements']->count() }}</h3>
        <p class="text-muted mb-0">Announcements</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-calendar" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['class']->name ?? 'N/A' }}</h3>
        <p class="text-muted mb-0">Your Class</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-yellow h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-user" style="font-size: 30px; color: #F59E0B;"></i>
        </div>
        <h3 class="mb-1">Active</h3>
        <p class="text-muted mb-0">Status</p>
      </div>
    </div>
  </div>

  <!-- Your Subjects Card -->
  <div class="col-md-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-book-open me-2 text-purple"></i>Your Subjects
        </h5>
      </div>
      <div class="modern-card-body">
        @if($dashboardData['subjects']->isEmpty())
          <div class="text-center text-muted py-4">
            <i class="bx bx-book" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No subjects assigned to your class yet.</p>
          </div>
        @else
          <div class="list-group list-group-flush">
            @foreach($dashboardData['subjects'] as $subject)
              <div class="list-group-item d-flex align-items-center px-0">
                <div class="modern-badge modern-badge-purple me-3">
                  <i class="bx bx-book"></i>
                </div>
                <div>
                  <h6 class="mb-0">{{ $subject->name }}</h6>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Latest Announcements Card -->
  <div class="col-md-6 mb-4">
    <div class="modern-card h-100">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-bullhorn me-2 text-blue"></i>Latest Announcements
        </h5>
      </div>
      <div class="modern-card-body">
        @if($dashboardData['announcements']->isEmpty())
          <div class="text-center text-muted py-4">
            <i class="bx bx-bullhorn" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No announcements at the moment.</p>
          </div>
        @else
          <div class="list-group list-group-flush">
            @foreach($dashboardData['announcements']->take(5) as $announcement)
              <div class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="flex-grow-1">
                    <h6 class="mb-1">{{ \Illuminate\Support\Str::limit($announcement->title, 50) }}</h6>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">
                      @php
                          $words = explode(' ', strip_tags($announcement->content));
                          $preview = implode(' ', array_slice($words, 0, 10)) . (count($words) > 10 ? '...' : '');
                      @endphp
                      {{ $preview }}
                      @if(count($words) > 10)
                        <a href="#" class="text-purple" data-bs-toggle="modal" data-bs-target="#announcementModal"
                           data-title="{{ $announcement->title }}"
                           data-content="{{ htmlspecialchars($announcement->content) }}">
                          Read More
                        </a>
                      @endif
                    </p>
                  </div>
                  <small class="text-muted ms-3">{{ \Carbon\Carbon::parse($announcement->created_at)->format('d M') }}</small>
                </div>
              </div>
            @endforeach
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
          <div class="col-md-3 col-sm-6">
            <a href="{{ route('student.results') }}" class="btn btn-outline-purple w-100 py-3">
              <i class="bx bx-bar-chart-alt-2 d-block mb-2" style="font-size: 24px;"></i>
              View Results
            </a>
          </div>
          <div class="col-md-3 col-sm-6">
            <a href="{{ route('student.attendance') }}" class="btn btn-outline-blue w-100 py-3">
              <i class="bx bx-calendar-check d-block mb-2" style="font-size: 24px;"></i>
              Attendance
            </a>
          </div>
          <div class="col-md-3 col-sm-6">
            <a href="{{ route('student.assignments') }}" class="btn btn-outline-green w-100 py-3">
              <i class="bx bx-task d-block mb-2" style="font-size: 24px;"></i>
              Assignments
            </a>
          </div>
          <div class="col-md-3 col-sm-6">
            <a href="{{ route('calendar.index') }}" class="btn btn-outline-yellow w-100 py-3">
              <i class="bx bx-calendar d-block mb-2" style="font-size: 24px;"></i>
              Calendar
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Announcement Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="announcementModalLabel">Announcement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h5 id="modalTitle"></h5>
        <div id="modalContent" style="white-space: pre-wrap;"></div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  var announcementModal = document.getElementById('announcementModal');
  if (announcementModal) {
    announcementModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var title = button.getAttribute('data-title');
      var content = button.getAttribute('data-content');

      var modalTitle = announcementModal.querySelector('#modalTitle');
      var modalContent = announcementModal.querySelector('#modalContent');

      modalTitle.textContent = title;
      modalContent.innerHTML = content;
    });
  }
</script>
@endsection
