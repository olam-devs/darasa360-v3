@extends('layouts/contentNavbarLayout')

@section('title', 'School Details')

@section('content')
<div class="row">
  <div class="col-md-8">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ $school->name }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <strong>School Code:</strong><br>
            {{ $school->school_code }}
          </div>
          <div class="col-md-6 mb-3">
            <strong>Location:</strong><br>
            {{ $school->location->name ?? 'N/A' }}
          </div>
          <div class="col-md-6 mb-3">
            <strong>Package:</strong><br>
            <span class="badge bg-label-primary">{{ ucfirst($school->package ?? 'Standard') }}</span>
          </div>
          <div class="col-md-6 mb-3">
            <strong>Status:</strong><br>
            <span class="badge bg-{{ $school->status === 'active' ? 'success' : 'secondary' }}">
              {{ ucfirst($school->status) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">School Statistics</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-primary rounded p-2 me-2">
                <i class='bx bx-user bx-sm'></i>
              </div>
              <div>
                <p class="mb-0 text-muted small">Total Users</p>
                <h4 class="mb-0">{{ number_format($school->total_users ?? 0) }}</h4>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-success rounded p-2 me-2">
                <i class='bx bx-group bx-sm'></i>
              </div>
              <div>
                <p class="mb-0 text-muted small">Teachers</p>
                <h4 class="mb-0">{{ number_format($school->total_teachers ?? 0) }}</h4>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-info rounded p-2 me-2">
                <i class='bx bx-user-circle bx-sm'></i>
              </div>
              <div>
                <p class="mb-0 text-muted small">Students</p>
                <h4 class="mb-0">{{ number_format($school->total_students ?? 0) }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Actions</h5>
      </div>
      <div class="card-body">
        <a href="{{ route('system_admin.my_schools.index') }}" class="btn btn-secondary w-100 mb-2">
          <i class='bx bx-arrow-back'></i> Back to Schools
        </a>
        <a href="{{ route('system_admin.support.index') }}" class="btn btn-outline-primary w-100">
          <i class='bx bx-support'></i> View Support Tickets
        </a>
      </div>
    </div>
  </div>
</div>

@endsection
