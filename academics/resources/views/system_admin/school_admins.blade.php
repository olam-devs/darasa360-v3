@extends('layouts/contentNavbarLayout')

@section('title', 'School Admins')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">School Admins</h5>
      </div>
      <div class="card-body">
        <p class="text-muted">School Admins assigned to your schools</p>

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Admin Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>School</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($schoolAdmins as $assignment)
              <tr>
                <td><strong>{{ $assignment->admin->username ?? 'N/A' }}</strong></td>
                <td>{{ $assignment->admin->email ?? 'N/A' }}</td>
                <td>{{ $assignment->admin->phone_number ?? 'N/A' }}</td>
                <td>{{ $assignment->school->name ?? 'N/A' }}</td>
                <td>
                  <span class="badge bg-{{ $assignment->is_active ? 'success' : 'secondary' }}">
                    {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <a href="#" class="btn btn-sm btn-outline-primary">
                    <i class='bx bx-show'></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center">No school admins found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
