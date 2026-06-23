@extends('layouts/contentNavbarLayout')

@section('title', 'Training Resources')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Training Resources</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Guides and materials to help you support your school's adoption of the system</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-4 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="badge bg-label-primary rounded p-2 me-2">
            <i class='bx bx-book-open bx-lg'></i>
          </div>
          <div>
            <h5 class="mb-0">User Manuals</h5>
          </div>
        </div>
        <p class="text-muted">Comprehensive guides for all system features.</p>
        <ul>
          <li>Teacher Guide</li>
          <li>Student Guide</li>
          <li>Parent Guide</li>
        </ul>
        <a href="#" class="btn btn-outline-primary btn-sm">Download Manuals</a>
      </div>
    </div>
  </div>

  <div class="col-md-4 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="badge bg-label-success rounded p-2 me-2">
            <i class='bx bx-video bx-lg'></i>
          </div>
          <div>
            <h5 class="mb-0">Video Tutorials</h5>
          </div>
        </div>
        <p class="text-muted">Step-by-step video guides.</p>
        <ul>
          <li>Getting Started</li>
          <li>Daily Operations</li>
          <li>Advanced Features</li>
        </ul>
        <a href="#" class="btn btn-outline-success btn-sm">Watch Videos</a>
      </div>
    </div>
  </div>

  <div class="col-md-4 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="badge bg-label-info rounded p-2 me-2">
            <i class='bx bx-question-mark bx-lg'></i>
          </div>
          <div>
            <h5 class="mb-0">FAQ</h5>
          </div>
        </div>
        <p class="text-muted">Frequently asked questions and answers.</p>
        <ul>
          <li>Common Issues</li>
          <li>Best Practices</li>
          <li>Tips & Tricks</li>
        </ul>
        <a href="#" class="btn btn-outline-info btn-sm">View FAQ</a>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Training Schedule</h5>
      </div>
      <div class="card-body">
        <p>Contact your System Admin to schedule training sessions for your school staff.</p>
        <a href="{{ route('school_admin.support.index') }}" class="btn btn-primary">
          <i class='bx bx-calendar'></i> Request Training Session
        </a>
      </div>
    </div>
  </div>
</div>

@endsection
