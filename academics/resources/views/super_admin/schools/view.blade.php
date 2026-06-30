@extends('layouts/contentNavbarLayout')

@section('title', $school->name . ' — School Details')

@section('content')
<div class="row">
  <div class="col-12">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('super_admin.schools') }}">Schools</a></li>
        <li class="breadcrumb-item active">{{ $school->name }}</li>
      </ol>
    </nav>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif
@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

{{-- School Info --}}
<div class="row mb-4">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">School Information</h5>
        <span class="badge bg-{{ $school->status === 'active' ? 'success' : 'secondary' }}">
          {{ ucfirst($school->status) }}
        </span>
      </div>
      <div class="card-body">
        <table class="table table-borderless mb-0">
          <tr>
            <th width="35%">Name</th>
            <td>{{ $school->name }}</td>
          </tr>
          <tr>
            <th>School Code</th>
            <td><code>{{ $school->school_code }}</code></td>
          </tr>
          <tr>
            <th>Package</th>
            <td>{{ ucfirst($school->package) }}</td>
          </tr>
          <tr>
            <th>Database</th>
            <td><code>{{ $school->database_url }}</code></td>
          </tr>
          <tr>
            <th>Billing Status</th>
            <td>
              <span class="badge bg-{{ $school->billing_status === 'active' ? 'success' : ($school->billing_status === 'overdue' ? 'danger' : 'warning') }}">
                {{ ucfirst($school->billing_status) }}
              </span>
            </td>
          </tr>
          @if($school->billing_start_date)
          <tr>
            <th>Billing Start</th>
            <td>{{ \Carbon\Carbon::parse($school->billing_start_date)->format('d M Y') }}</td>
          </tr>
          @endif
          @if($school->next_billing_date)
          <tr>
            <th>Next Billing</th>
            <td>{{ \Carbon\Carbon::parse($school->next_billing_date)->format('d M Y') }}</td>
          </tr>
          @endif
        </table>
      </div>
    </div>
  </div>

  {{-- Billing Summary --}}
  <div class="col-md-4">
    <div class="card bg-primary text-white mb-3">
      <div class="card-body text-center">
        <h6 class="text-white-50 mb-1">Monthly Charge</h6>
        <h3 class="mb-0">TZS {{ number_format($school->monthly_charge, 0) }}</h3>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Total Users</span>
          <strong>{{ $school->total_users }}</strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Price / User</span>
          <strong>TZS {{ number_format($school->price_per_user, 0) }}</strong>
        </div>
        <div class="d-flex justify-content-between">
          <span class="text-muted">Max Users</span>
          <strong>{{ number_format($school->total_users) }}</strong>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Users in this school --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Users ({{ $school->users->count() }})</h5>
      </div>
      <div class="card-body p-0">
        @if($school->users->isEmpty())
          <p class="text-muted p-3 mb-0">No users registered for this school yet.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Name / Username</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Reg No</th>
                </tr>
              </thead>
              <tbody>
                @foreach($school->users as $i => $user)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>{{ $user->username ?? ($user->first_name . ' ' . $user->last_name) }}</td>
                  <td>{{ $user->email ?? '—' }}</td>
                  <td>{{ $user->role->name ?? '—' }}</td>
                  <td><code>{{ $user->registration_no ?? '—' }}</code></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Assign System Admin --}}
<div class="row mb-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Assign System Admin</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('super_admin.schools.assignSystemAdmin', $school->id) }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="admin_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="admin_email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="admin_phone" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="admin_username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="admin_password" class="form-control" required minlength="6">
          </div>
          <button type="submit" class="btn btn-primary">Assign Admin</button>
        </form>
      </div>
    </div>
  </div>

  {{-- Existing System Admins --}}
  @if($school->systemAdmins->isNotEmpty() || $school->schoolSystemAdmins->isNotEmpty())
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><h5 class="mb-0">Current Admins</h5></div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead class="table-light">
            <tr><th>Name</th><th>Type</th><th>Email</th></tr>
          </thead>
          <tbody>
            @foreach($school->systemAdmins as $sa)
            <tr>
              <td>{{ $sa->admin->username ?? '—' }}</td>
              <td><span class="badge bg-info">System Admin</span></td>
              <td>{{ $sa->admin->email ?? '—' }}</td>
            </tr>
            @endforeach
            @foreach($school->schoolSystemAdmins as $sa)
            <tr>
              <td>{{ $sa->admin->username ?? '—' }}</td>
              <td><span class="badge bg-secondary">School Admin</span></td>
              <td>{{ $sa->admin->email ?? '—' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection
