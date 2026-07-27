@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage SMS for Schools'
])
@section('title', 'Manage SMS Balances')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage SMS for Schools</h4>
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
              <th>School Name</th>
              <th>SMS Allocated</th>
              <th>SMS Used</th>
              <th>SMS Remaining</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @foreach($smsBalances as $balance)
              <tr>
                <td>{{ $loop->iteration + ($smsBalances->currentPage() - 1) * $smsBalances->perPage() }}</td>
                <td><strong>{{ $balance->school_name }}</strong></td>
                <td>{{ $balance->sms_allocated }}</td>
                <td>{{ $balance->sms_used }}</td>
                <td>{{ $balance->sms_remaining }}</td>
                <td>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#editSmsModal{{ $balance->school_id }}"
                  >
                    <i class="bx bx-edit"></i> Update
                  </button>
                </td>
              </tr>

              <!-- Edit Modal -->
              <div
                class="modal fade"
                id="editSmsModal{{ $balance->school_id }}"
                tabindex="-1"
                aria-labelledby="editSmsModalLabel{{ $balance->school_id }}"
                aria-hidden="true"
              >
                <div class="modal-dialog modal-dialog-centered modal-md">
                  <form method="POST" action="{{ route('super.sms.update', $balance->school_id) }}">
                    @csrf
                    {{-- Use PUT if record exists, else POST for creation --}}
                    @if($balance->has_sms_record)
                      @method('PUT')
                    @else
                      @method('POST')
                    @endif

                    <input type="hidden" name="school_id" value="{{ $balance->school_id }}">

                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title text-wrap" id="editSmsModalLabel{{ $balance->school_id }}">
                          Update SMS Allocation for {{ $balance->school_name }}
                        </h5>

                        <button
                          type="button"
                          class="btn-close"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Operation Type</label>
                          <select class="form-select" name="operation_type" id="operation_type_{{ $balance->school_id }}" onchange="toggleSmsInput({{ $balance->school_id }})">
                            <option value="set">Set Total Allocated</option>
                            <option value="add">Add Credits</option>
                          </select>
                        </div>

                        <div class="mb-3">
                          <label for="sms_allocated_{{ $balance->school_id }}" class="form-label" id="label_{{ $balance->school_id }}">
                            Total SMS Allocated
                          </label>
                          <input
                            type="number"
                            min="0"
                            name="sms_allocated"
                            id="sms_allocated_{{ $balance->school_id }}"
                            value="{{ old('sms_allocated', $balance->sms_allocated) }}"
                            class="form-control"
                            required
                          >
                          <small class="text-muted" id="current_info_{{ $balance->school_id }}">
                            Current: {{ $balance->sms_allocated }} allocated, {{ $balance->sms_used }} used, {{ $balance->sms_remaining }} remaining
                          </small>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button
                          type="button"
                          class="btn btn-outline-secondary me-auto"
                          data-bs-dismiss="modal"
                        >Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="mt-3">
        {{ $smsBalances->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
function toggleSmsInput(schoolId) {
  const select = document.getElementById('operation_type_' + schoolId);
  const input = document.getElementById('sms_allocated_' + schoolId);
  const label = document.getElementById('label_' + schoolId);

  if (select.value === 'add') {
    label.textContent = 'Credits to Add';
    input.value = 0;
    input.placeholder = 'Enter amount to add';
  } else {
    label.textContent = 'Total SMS Allocated';
    input.placeholder = 'Enter total allocated';
  }
}
</script>
@endsection
