@extends('layouts/contentNavbarLayout')

@section('title', 'Manage Locations')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Manage Locations</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createLocationModal">
          <i class='bx bx-plus'></i> Add Location
        </button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Location Name</th>
                <th>Schools</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($locations as $location)
              <tr>
                <td>{{ $location->id }}</td>
                <td><strong>{{ $location->name }}</strong></td>
                <td>{{ $location->schools->count() }} schools</td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editLocationModal{{ $location->id }}">
                    <i class='bx bx-edit'></i> Edit
                  </button>
                  <form action="{{ route('super_admin.locations.delete', $location->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                      <i class='bx bx-trash'></i> Delete
                    </button>
                  </form>
                </td>
              </tr>

              <!-- Edit Modal -->
              <div class="modal fade" id="editLocationModal{{ $location->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Location</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('super_admin.locations.update', $location->id) }}" method="POST">
                      @csrf
                      @method('PUT')
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Location Name</label>
                          <input type="text" class="form-control" name="name" value="{{ $location->name }}" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Location Code</label>
                          <input type="text" class="form-control" name="code" value="{{ $location->code }}" required maxlength="10">
                          <small class="text-muted">Short code for the location</small>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              @empty
              <tr>
                <td colspan="4" class="text-center">No locations found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create Location Modal -->
<div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Location</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('super_admin.locations.create') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Location Name</label>
            <input type="text" class="form-control" name="name" required placeholder="e.g., Dar es Salaam">
          </div>
          <div class="mb-3">
            <label class="form-label">Location Code</label>
            <input type="text" class="form-control" name="code" required placeholder="e.g., DSM" maxlength="10">
            <small class="text-muted">Short code for the location (e.g., DSM for Dar es Salaam)</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if(session('success'))
<script>
  document.addEventListener('DOMContentLoaded', function() {
    alert('{{ session('success') }}');
  });
</script>
@endif

@endsection
