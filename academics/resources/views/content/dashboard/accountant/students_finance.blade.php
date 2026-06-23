@extends('layouts.modernLayout', [
    'pageTitle' => 'Students Financial Records'
])
@section('title', 'Students Finance')

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="mb-4">Students Financial Records</h4>

    {{-- Add Student Fee Button --}}
    <div class="mb-3">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentFeeModal">
        <i class="bx bx-plus"></i> Add Student Fee
      </button>
    </div>

    {{-- Students Fees Table --}}
    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-bordered table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Fee</th>
              <th>Total Fee</th>
              <th>Paid</th>
              <th>Balance</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($studentFees as $sf)
            <tr @if($sf->balance() > 0 && $sf->due_date < now()) class="table-danger" @endif>
              <td>{{ $loop->iteration + ($studentFees->currentPage() - 1) * $studentFees->perPage() }}</td>
              <td>{{ $sf->student->full_name }} ({{ $sf->student->registration_no }})</td>
              <td>{{ $sf->fee->name }}</td>
              <td>{{ number_format($sf->amount,2) }}</td>
              <td>{{ number_format($sf->totalPaid(),2) }}</td>
              <td>{{ number_format($sf->balance(),2) }}</td>
              <td>{{ $sf->paymentStatus() }}</td>
              <td>
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal"
                  data-bs-target="#paymentModal" 
                  onclick="populatePayment({{ $sf->id }}, {{ $sf->balance() }})">
                  Record Payment
                </button>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8" class="text-center">No student fees found.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 mx-3">
        {{ $studentFees->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

{{-- Add Student Fee Modal --}}
<div class="modal fade" id="addStudentFeeModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('accountant.students-fee.store') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Student Fee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          {{-- Class Selection --}}
          <div class="mb-3">
            <label class="form-label">Class</label>
            <select name="class_id" id="classSelect" class="form-select">
              <option value="">-- Select Class (optional) --</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Student Selection --}}
          <div class="mb-3">
            <label class="form-label">Students</label>
            <select name="student_id[]" id="studentSelect" class="form-select" multiple>
              <option value="">-- Select Student(s) --</option>
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple students.</small>
          </div>

          {{-- Fee Selection --}}
          <div class="mb-3">
            <label class="form-label">Fee</label>
            <select name="fee_id" class="form-select" required>
              <option value="">-- Select Fee --</option>
              @foreach($fees as $fee)
                <option value="{{ $fee->id }}">{{ $fee->name }} - {{ number_format($fee->amount,2) }}</option>
              @endforeach
            </select>
          </div>

          {{-- Amount & Due Date --}}
          <div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" class="form-control" name="amount" step="0.01" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Due Date</label>
            <input type="date" class="form-control" name="due_date" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Fee</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Record Payment Modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" id="paymentForm" enctype="multipart/form-data">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Record Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="student_fee_id" id="payment_fee_id">
          <div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" class="form-control" name="amount" step="0.01" id="payment_amount" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="method" class="form-select" required>
              <option value="cash">Cash</option>
              <option value="mpesa">Mpesa</option>
              <option value="bank">Bank</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Receipt (optional)</label>
            <input type="file" class="form-control" name="receipt">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Payment</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Populate payment modal with student fee and balance
function populatePayment(studentFeeId, balance) {
    const form = document.getElementById('paymentForm');
    form.action = `/accountant/students-finance/${studentFeeId}/pay`;
    document.getElementById('payment_fee_id').value = studentFeeId;
    document.getElementById('payment_amount').value = balance.toFixed(2);
}

// Dependent dropdown: Class -> Students
document.getElementById('classSelect').addEventListener('change', function() {
    const classId = this.value;
    const studentSelect = document.getElementById('studentSelect');
    studentSelect.innerHTML = '<option value="">Loading...</option>';

    if (classId) {
        fetch(`/accountant/students-by-class/${classId}`)
            .then(res => res.json())
            .then(data => {
                let options = '';
                data.forEach(student => {
                    options += `<option value="${student.id}">${student.full_name} (${student.registration_no})</option>`;
                });
                studentSelect.innerHTML = options;
            });
    } else {
        studentSelect.innerHTML = '<option value="">-- Select Student(s) --</option>';
    }
});
</script>
@endsection
