@extends('layouts/contentNavbarLayout')

@section('title', 'My Schools')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">My Assigned Schools</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">Schools assigned to you for management and support</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  @forelse($schools as $school)
  <div class="col-md-4 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h5 class="card-title mb-1">{{ $school->name }}</h5>
            <p class="text-muted mb-0"><small>{{ $school->school_code }}</small></p>
          </div>
          <span class="badge bg-label-{{ $school->status === 'active' ? 'success' : 'secondary' }}">
            {{ ucfirst($school->status) }}
          </span>
        </div>

        <div class="mb-3">
          <small class="text-muted">Location:</small><br>
          <strong>{{ $school->location->name ?? 'N/A' }}</strong>
        </div>

        <div class="mb-3">
          <small class="text-muted">Package:</small><br>
          <span class="badge bg-label-primary">{{ ucfirst($school->package ?? 'Standard') }}</span>
        </div>

        <div class="d-grid">
          <a href="{{ route('system_admin.my_schools.view', $school->id) }}" class="btn btn-outline-primary btn-sm">
            <i class='bx bx-show'></i> View Details
          </a>
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="col-12">
    <div class="card">
      <div class="card-body text-center py-5">
        <i class='bx bx-building bx-lg text-muted mb-3'></i>
        <p class="text-muted">No schools assigned yet</p>
      </div>
    </div>
  </div>
  @endforelse
</div>

@endsection
