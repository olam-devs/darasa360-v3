@extends('layouts.modernLayout', [
    'pageTitle' => 'Payment Methods'
])
@section('title', 'Payment Methods')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Payment Methods</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentMethodModal">
        <i class="bx bx-plus"></i> Add New Method
      </button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

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
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Payment Method Name</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($paymentMethods as $method)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $method->name }}</strong></td>
                <td>
                  <!-- Edit Button -->
                  <button type="button" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#editPaymentMethodModal{{ $method->id }}">
                    <i class="bx bx-edit"></i> Edit
                  </button>

                  <!-- Delete Button -->
                  <form action="{{ route('accountant.payment.methods.delete', $method->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this method?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">
                      <i class="bx bx-trash"></i> Delete
                    </button>
                  </form>
                </td>
              </tr>

              <!-- Edit Modal -->
              <div class="modal fade" id="editPaymentMethodModal{{ $method->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <form method="POST" action="{{ route('accountant.payment.methods.update', $method->id) }}">
                    @csrf
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Edit Payment Method</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Method Name</label>
                          <input type="text" name="name" class="form-control" value="{{ $method->name }}" required>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Method</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

            @empty
              <tr>
                <td colspan="4" class="text-center text-muted">No payment methods found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Payment Method Modal -->
<div class="modal fade" id="addPaymentMethodModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('accountant.payment.methods.store') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Payment Method</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Method Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Cash, Bank Transfer, M-Pesa" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Method</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
