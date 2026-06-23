@extends('layouts/contentNavbarLayout')

@section('title', 'Reports')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">School Reports</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Performance and usage reports for your assigned schools</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  @foreach($schoolStats as $stat)
  <div class="col-md-6 col-12 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">{{ $stat['school']->name }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-6 mb-3">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-primary rounded p-2 me-2">
                <i class='bx bx-user'></i>
              </div>
              <div>
                <p class="mb-0 small text-muted">Total Users</p>
                <h4 class="mb-0">{{ number_format($stat['total_users']) }}</h4>
              </div>
            </div>
          </div>
          <div class="col-6 mb-3">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-warning rounded p-2 me-2">
                <i class='bx bx-error-circle'></i>
              </div>
              <div>
                <p class="mb-0 small text-muted">Active Tickets</p>
                <h4 class="mb-0">{{ $stat['active_tickets'] }}</h4>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-success rounded p-2 me-2">
                <i class='bx bx-check-circle'></i>
              </div>
              <div>
                <p class="mb-0 small text-muted">Resolved</p>
                <h4 class="mb-0">{{ $stat['resolved_tickets'] }}</h4>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="d-flex align-items-center">
              <div class="badge bg-label-info rounded p-2 me-2">
                <i class='bx bx-map'></i>
              </div>
              <div>
                <p class="mb-0 small text-muted">Location</p>
                <p class="mb-0">{{ $stat['school']->location->name ?? 'N/A' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

@endsection
