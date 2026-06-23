@extends('layouts.modernLayout', [
    'pageTitle' => 'Payments'
])

@section('title', 'My Payments')

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="fw-bold mb-4">My Fees & Payments</h4>

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

    <!-- Summary Cards -->
    <div class="row mb-4 g-3">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <h6 class="text-muted">Total Dues</h6>
            <h4 class="fw-bold text-danger">Tsh {{ number_format($totalDues, 2) }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <h6 class="text-muted">Total Paid</h6>
            <h4 class="fw-bold text-success">Tsh {{ number_format($totalPaid, 2) }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <h6 class="text-muted">Overdue</h6>
            <h4 class="fw-bold text-danger">Tsh {{ number_format($overdueAmount, 2) }}</h4>
          </div>
        </div>
      </div>
    </div>

    <!-- Student Info -->
    <div class="card mb-4">
      <div class="card-body row">
        <div class="col-md-6">
          <h6 class="text-muted">Student</h6>
          <p class="mb-1"><strong>Name:</strong> {{ $student->first_name }} {{ $student->last_name }}</p>
          <p class="mb-0"><strong>Class:</strong> {{ $class->name ?? '-' }}</p>
        </div>
        <div class="col-md-6">
          <h6 class="text-muted">Payment Status</h6>
          <p class="mb-1"><strong>Last Payment:</strong> {{ $lastPaymentDate ? $lastPaymentDate->format('M d, Y') : 'No payments yet' }}</p>
          <p class="mb-0"><strong>Next Due:</strong> {{ $nextDueDate ? \Carbon\Carbon::parse($nextDueDate)->format('M d, Y') : 'No upcoming dues' }}</p>
        </div>
      </div>
    </div>

    <!-- Due Payments -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Due Payments</h5>
        <span class="badge bg-danger">Pending: {{ $duePayments->count() }}</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Fee Type</th>
              <th>Due Date</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($duePayments as $payment)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $payment->fee_name ?? '-' }}</td>
                <td>
                  <span class="{{ $payment->due_date && $payment->due_date->isPast() ? 'text-danger' : 'text-warning' }}">
                    {{ $payment->due_date ? $payment->due_date->format('M d, Y') : '-' }}
                  </span>
                </td>
                <td>Tsh {{ number_format($payment->amount, 2) }}</td>
                <td>
                  <span class="badge bg-{{ $payment->due_date && $payment->due_date->isPast() ? 'danger' : 'warning' }}">
                    {{ $payment->due_date && $payment->due_date->isPast() ? 'Overdue' : 'Pending' }}
                  </span>
                </td>
                <td>
                  <button type="button" class="btn btn-primary btn-sm" 
                          data-bs-toggle="modal" 
                          data-bs-target="#uploadReceiptModal"
                          data-fee-id="{{ $payment->fee_id }}"
                          data-fee-type="{{ $payment->fee_name ?? 'Fee' }}"
                          data-amount="{{ $payment->amount }}">
                    <i class="bx bx-upload me-1"></i> Upload Receipt
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-3">No dues available 🎉</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payment History -->
    <div class="card mt-4">
      <div class="card-header"><h5 class="mb-0">Payment History</h5></div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Fee Type</th>
              <th>Amount</th>
              <th>Date</th>
              <th>Receipt</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($paymentHistory as $payment)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $payment->fee_name ?? '-' }}</td>
                <td>Tsh {{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->paid_at ? $payment->paid_at->format('M d, Y') : '-' }}</td>
                <td>
                  @if($payment->receipt_path)
                    <button class="btn btn-sm btn-outline-success" 
                            onclick="downloadReceipt({{ $payment->id }}, event)"
                            title="Download Receipt">
                      <i class="bx bx-download"></i>
                    </button>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-{{ $payment->status === 'verified' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'secondary') }}">
                    {{ ucfirst($payment->status) }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-3">No payment history yet</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Upload Receipt Modal -->
<div class="modal fade" id="uploadReceiptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('parent.payments.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Record Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="student_id" value="{{ $student->id }}">
          <input type="hidden" name="fee_id" id="modal_fee_id">

          <div class="alert alert-info mb-3">
            <i class="bx bx-info-circle"></i>
            <span id="fee_info_display">Select a fee to pay</span>
          </div>

          <div class="mb-3">
            <label for="payment_amount" class="form-label">Amount *</label>
            <input type="number" class="form-control" name="amount" id="payment_amount" step="0.01" required>
          </div>

          <!-- Read-only formatted amount -->
          <div class="mb-3">
            <label class="form-label">Formatted Amount</label>
            <input type="text" class="form-control" id="formatted_amount" readonly>
          </div>

          <!-- Read-only remaining balance -->
          <div class="mb-3">
            <label class="form-label">Remaining Balance</label>
            <input type="text" class="form-control" id="remaining_balance" readonly>
          </div>

          <div class="mb-3">
            <label for="payment_method" class="form-label">Payment Method *</label>
            <select class="form-select" name="payment_method" id="payment_method" required>
              <option value="">Select Method</option>
              @foreach($paymentMethods as $method)
                <option value="{{ $method->name }}">
                  {{ ucfirst($method->display_name ?? $method->name) }}
                </option>
              @endforeach
            </select>
          </div>


          <div class="mb-3">
            <label for="payment_date" class="form-label">Payment Date *</label>
            <input type="date" class="form-control" name="payment_date" id="payment_date" value="{{ date('Y-m-d') }}" required>
          </div>

          <div class="mb-3">
            <label for="transaction_id" class="form-label">Transaction ID (Optional)</label>
            <input type="text" class="form-control" name="transaction_id" id="transaction_id" placeholder="e.g., MPSA123456">
          </div>

          <div class="mb-3">
            <label for="receipt" class="form-label">Upload Receipt (Optional)</label>
            <input type="file" class="form-control" name="receipt" id="receipt" accept=".jpg,.jpeg,.png,.pdf">
            <div class="form-text">Max file size: 2MB. Supported formats: JPG, PNG, PDF</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const uploadReceiptModal = document.getElementById('uploadReceiptModal');
  const paymentInput = document.getElementById('payment_amount');
  const formattedInput = document.getElementById('formatted_amount');
  const remainingInput = document.getElementById('remaining_balance');

  let currentFeeAmount = 0;

  uploadReceiptModal.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const feeId = button.getAttribute('data-fee-id');
    const feeType = button.getAttribute('data-fee-type');
    const amount = parseFloat(button.getAttribute('data-amount')) || 0;

    currentFeeAmount = amount;

    document.getElementById('modal_fee_id').value = feeId;
    paymentInput.value = amount;
    formattedInput.value = amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    remainingInput.value = '0.00';
    document.getElementById('fee_info_display').textContent = `Remaining ${feeType} - Tsh ${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  });

  paymentInput.addEventListener('input', function() {
    const val = parseFloat(this.value) || 0;
    formattedInput.value = val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let remaining = currentFeeAmount - val;
    remaining = remaining < 0 ? 0 : remaining;
    remainingInput.value = remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  });

  document.querySelector('#uploadReceiptModal form').addEventListener('submit', function(e) {
    const amount = parseFloat(paymentInput.value) || 0;
    const method = document.getElementById('payment_method').value;

    if (!amount || amount <= 0) {
      e.preventDefault();
      alert('Please enter a valid amount');
      return;
    }

    if (!method) {
      e.preventDefault();
      alert('Please select a payment method');
      return;
    }
  });

  uploadReceiptModal.addEventListener('hidden.bs.modal', function () {
    paymentInput.value = '';
    formattedInput.value = '';
    remainingInput.value = '';
    document.getElementById('fee_info_display').textContent = 'Select a fee to pay';
  });
});

function downloadReceipt(paymentId, event) {
  const button = event.target.closest('button');
  const originalHTML = button.innerHTML;
  button.innerHTML = '<i class="bx bx-loader bx-spin"></i>';
  button.disabled = true;

  const iframe = document.createElement('iframe');
  iframe.style.display = 'none';
  iframe.src = `/accountant/payments/${paymentId}/receipt/download`;
  document.body.appendChild(iframe);

  setTimeout(() => {
      button.innerHTML = originalHTML;
      button.disabled = false;
      document.body.removeChild(iframe);
  }, 2000);
}
</script>
@endsection
