@extends('layouts/contentNavbarLayout')

@section('title', 'System Settings')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">System Settings for {{ $school->name }}</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">View system configuration and settings</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">School Information</h5>
      </div>
      <div class="card-body">
        <table class="table table-borderless">
          <tr>
            <td><strong>School Name:</strong></td>
            <td>{{ $school->name }}</td>
          </tr>
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
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">System Status</h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="mb-3">
            <i class='bx bx-check-circle text-success'></i>
            <strong>Database:</strong> Connected
          </li>
          <li class="mb-3">
            <i class='bx bx-check-circle text-success'></i>
            <strong>Authentication:</strong> Active
          </li>
          <li class="mb-3">
            <i class='bx bx-check-circle text-success'></i>
            <strong>Storage:</strong> Available
          </li>
          <li>
            <i class='bx bx-check-circle text-success'></i>
            <strong>System:</strong> Operational
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Need Help?</h5>
      </div>
      <div class="card-body">
        <p>If you need to change system settings or configuration, please create a support ticket.</p>
        <a href="{{ route('school_admin.support.index') }}" class="btn btn-primary">
          <i class='bx bx-support'></i> Create Support Ticket
        </a>
      </div>
    </div>
  </div>
</div>

@endsection
