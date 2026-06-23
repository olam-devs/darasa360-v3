@extends('layouts/contentNavbarLayout')

@section('title', 'School Overview')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-body">
        <h4 class="card-title mb-1">{{ $school->name }}</h4>
        <p class="card-text text-muted">{{ $school->location->name ?? 'N/A' }}</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-3 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="badge bg-label-primary rounded p-2 me-3">
            <i class='bx bx-user bx-lg'></i>
          </div>
          <div>
            <p class="mb-0 text-muted small">Total Users</p>
            <h4 class="mb-0">{{ number_format($stats['total_users']) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="badge bg-label-success rounded p-2 me-3">
            <i class='bx bx-group bx-lg'></i>
          </div>
          <div>
            <p class="mb-0 text-muted small">Teachers</p>
            <h4 class="mb-0">{{ number_format($stats['teachers']) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="badge bg-label-info rounded p-2 me-3">
            <i class='bx bx-user-circle bx-lg'></i>
          </div>
          <div>
            <p class="mb-0 text-muted small">Students</p>
            <h4 class="mb-0">{{ number_format($stats['students']) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="badge bg-label-warning rounded p-2 me-3">
            <i class='bx bx-check-circle bx-lg'></i>
          </div>
          <div>
            <p class="mb-0 text-muted small">Active Users</p>
            <h4 class="mb-0">{{ number_format($stats['active_users']) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">School Information</h5>
      </div>
      <div class="card-body">
        <table class="table table-borderless">
          <tr>
            <td><strong>School Code:</strong></td>
            <td>{{ $school->school_code }}</td>
          </tr>
          <tr>
            <td><strong>Package:</strong></td>
            <td><span class="badge bg-label-primary">{{ ucfirst($school->package ?? 'Standard') }}</span></td>
          </tr>
          <tr>
            <td><strong>Status:</strong></td>
            <td><span class="badge bg-{{ $school->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($school->status) }}</span></td>
          </tr>
          <tr>
            <td><strong>Location:</strong></td>
            <td>{{ $school->location->name ?? 'N/A' }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Quick Actions</h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="{{ route('school_admin.support.index') }}" class="btn btn-primary">
            <i class='bx bx-support'></i> Get Support
          </a>
          <a href="{{ route('school_admin.staff.index') }}" class="btn btn-outline-success">
            <i class='bx bx-group'></i> Manage Staff
          </a>
          <a href="{{ route('school_admin.training') }}" class="btn btn-outline-info">
            <i class='bx bx-book-reader'></i> Training Resources
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
