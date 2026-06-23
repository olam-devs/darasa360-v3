@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Admins'
])
@section('title', 'Manage Admins')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Admins</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
        <i class="bx bx-plus"></i> Add New Admin
      </button>
    </div>

    <!-- Success message -->
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <!-- Error messages -->
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    {{--  --}}
    @if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif


    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Username</th>
              <th>Registration No.</th>
              <th>PhoneNumber</th>
              <th>Email</th>
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($admins as $admin)
              <tr>
                <td>{{ $loop->iteration + ($admins->currentPage() - 1) * $admins->perPage() }}</td>
                <td><strong>{{ $admin->username }}</strong></td>
                <td>{{ $admin->registration_no }}</td>
                 <td>{{ $admin->phone_number }}</td>
                <td>{{ $admin->email }}</td>
                <td>{{ ucfirst($admin->role_name->name) }}</td>
                <td>
                  <button
                    class="btn btn-sm btn-outline-warning me-1 edit-admin-btn"
                    data-id="{{ $admin->id }}"
                    data-username="{{ $admin->username }}"
                    data-email="{{ $admin->email }}"
                    data-phone="{{ $admin->phone_number }}"
                    title="Edit"
                  >
                    <i class="bx bx-edit"></i>
                  </button>

                  <form action="{{ url('/admins/delete-admin/' . $admin->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this admin?');">
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
                <td colspan="5" class="text-center">No admins found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 mx-3">
        {{ $admins->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ url('/admins/store-admin') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="addAdminModalLabel">Add New Admin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">
          @if($errors->any() && session('form') === 'add')
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input
              type="text"
              id="username"
              name="username"
              class="form-control"
              value="{{ old('username') }}"
              required
            >
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              value="{{ old('email') }}"
              required
            >
          </div>

          <div class="mb-3">
          <label for="phone_number" class="form-label">Phone Number</label>
          <input
            type="text"
            id="phone_number"
            name="phone_number"
            class="form-control"
            value="{{ old('phone_number') }}"
            required
          >
        </div>



          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              required
            >
          </div>
          <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input
              type="password"
              id="password_confirmation"
              name="password_confirmation"
              class="form-control"
              required
            >
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Admin</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="editAdminForm" action="">
      @csrf
      @method('POST')
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="editAdminModalLabel">Edit Admin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">
          <!-- You can add validation errors display here similarly, if needed -->

          <div class="mb-3">
            <label for="edit_username" class="form-label">Username</label>
            <input type="text" id="edit_username" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="edit_email" class="form-label">Email Address</label>
            <input type="email" id="edit_email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="edit_phone" class="form-label">Phone Number</label>
            <input type="text" id="edit_phone" name="phone_number" class="form-control" required>
          </div>

          <!-- Optional: password fields for update -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Admin</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.edit-admin-btn').forEach(button => {
    button.addEventListener('click', function () {
      const id = this.dataset.id;
      const username = this.dataset.username;
      const email = this.dataset.email;
      const phone = this.dataset.phone;

      const form = document.getElementById('editAdminForm');
      form.action = `/admins/update-admin/${id}`;

      document.getElementById('edit_username').value = username;
      document.getElementById('edit_email').value = email;
      document.getElementById('edit_phone').value = phone;

      const editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
      editModal.show();
    });
  });

  @if($errors->any() && session('form') === 'add')
    const addModal = new bootstrap.Modal(document.getElementById('addAdminModal'));
    addModal.show();
  @endif
});
</script>
@endsection
