@extends('layouts/contentNavbarLayout')

@section('title', 'Help Resources')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Help Resources</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Documentation and guides for System Admins</p>
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
            <h5 class="mb-0">User Guide</h5>
          </div>
        </div>
        <p class="text-muted">Complete guide for managing schools and handling support tickets.</p>
        <a href="#" class="btn btn-outline-primary btn-sm">View Guide</a>
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
        <p class="text-muted">Step-by-step video guides for common tasks.</p>
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
        <p class="text-muted">Answers to frequently asked questions.</p>
        <a href="#" class="btn btn-outline-info btn-sm">View FAQ</a>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Quick Tips</h5>
      </div>
      <div class="card-body">
        <ul>
          <li><strong>Handling Escalations:</strong> Review ticket history before taking action</li>
          <li><strong>School Support:</strong> Regular check-ins with schools help prevent issues</li>
          <li><strong>Documentation:</strong> Always document resolutions for future reference</li>
          <li><strong>Communication:</strong> Keep School Admins informed of ticket status</li>
        </ul>
      </div>
    </div>
  </div>
</div>

@endsection
