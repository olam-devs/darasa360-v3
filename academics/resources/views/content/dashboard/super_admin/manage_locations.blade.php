@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Locations'
])
@section('title', 'Manage Locations')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Locations</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
        <i class="bx bx-plus"></i> Add New Location
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Location Name</th>
              <th>Code</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($locations as $location)
              <tr>
                <td>{{ $loop->iteration + ($locations->currentPage() - 1) * $locations->perPage() }}</td>
                <td>{{ $location->name }}</td>
                <td>{{ $location->code }}</td>
                <td>
                  {{-- <button
                    class="btn btn-sm btn-outline-warning me-1 edit-location-btn"
                    data-id="{{ $location->id }}"
                    data-name="{{ $location->name }}"
                    data-code="{{ $location->code }}"
                    title="Edit"
                  >
                    <i class="bx bx-edit"></i>
                  </button> --}}

                  <form action="{{ route('locations.delete', $location->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this location?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center">No locations found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 mx-3">
        {{ $locations->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1" aria-labelledby="addLocationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('locations.store') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="addLocationModalLabel">Add New Location</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && session('form') === 'add_location')
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label for="location_name" class="form-label">Location Name</label>
            <input
              type="text"
              id="location_name"
              name="name"
              class="form-control"
              value="{{ old('name') }}"
              required
            >
          </div>
          <div class="mb-3">
            <label for="location_code" class="form-label">Code</label>
            <input
              type="text"
              id="location_code"
              name="code"
              class="form-control"
              value="{{ old('code') }}"
              required
            >
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Location</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Location Modal -->
<div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="editLocationForm" action="">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="editLocationModalLabel">Edit Location</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">

          <div class="mb-3">
            <label for="edit_location_name" class="form-label">Location Name</label>
            <input type="text" id="edit_location_name" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="edit_location_code" class="form-label">Code</label>
            <input type="text" id="edit_location_code" name="code" class="form-control" required>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Location</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Edit location buttons
  document.querySelectorAll('.edit-location-btn').forEach(button => {
    button.addEventListener('click', function () {
      const id = this.dataset.id;
      const name = this.dataset.name;
      const code = this.dataset.code;

      const form = document.getElementById('editLocationForm');
      form.action = `/admins/locations/${id}`;

      document.getElementById('edit_location_name').value = name;
      document.getElementById('edit_location_code').value = code;

      const editModal = new bootstrap.Modal(document.getElementById('editLocationModal'));
      editModal.show();
    });
  });

  // Show add modal on validation errors
  @if($errors->any() && session('form') === 'add_location')
    const addModal = new bootstrap.Modal(document.getElementById('addLocationModal'));
    addModal.show();
  @endif
});
</script>
@endsection
