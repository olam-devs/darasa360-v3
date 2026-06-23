@extends('layouts.modernLayout', [
    'pageTitle' => 'Receipts'
])

@section('title', 'Receipt Verification')

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="fw-bold mb-4">Receipt Verification</h4>

    <!-- Summary Cards -->
    <div class="row mb-4 g-3">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 position-relative">
          <div class="position-absolute top-0 start-0 w-100 h-2 bg-primary"></div>
          <div class="card-body text-center p-3 d-flex flex-column justify-content-end pt-4">
            <h6 class="card-title mb-2 small text-white fw-normal bg-primary rounded-pill py-1 px-3 d-inline-block mx-auto" style="min-width: fit-content; width: auto;">
              Pending
            </h6>
            <h4 class="mb-0 fw-bold text-dark">{{ $pendingCount }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 position-relative">
          <div class="position-absolute top-0 start-0 w-100 h-2 bg-success"></div>
          <div class="card-body text-center p-3 d-flex flex-column justify-content-end pt-4">
            <h6 class="card-title mb-2 small text-white fw-normal bg-success rounded-pill py-1 px-3 d-inline-block mx-auto" style="min-width: fit-content; width: auto;">
              Verified
            </h6>
            <h4 class="mb-0 fw-bold text-dark">{{ $verifiedCount }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 position-relative">
          <div class="position-absolute top-0 start-0 w-100 h-2 bg-warning"></div>
          <div class="card-body text-center p-3 d-flex flex-column justify-content-end pt-4">
            <h6 class="card-title mb-2 small text-white fw-normal bg-warning rounded-pill py-1 px-3 d-inline-block mx-auto" style="min-width: fit-content; width: auto;">
              Rejected
            </h6>
            <h4 class="mb-0 fw-bold text-dark">{{ $rejectedCount }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 position-relative">
          <div class="position-absolute top-0 start-0 w-100 h-2 bg-info"></div>
          <div class="card-body text-center p-3 d-flex flex-column justify-content-end pt-4">
            <h6 class="card-title mb-2 small text-white fw-normal bg-info rounded-pill py-1 px-3 d-inline-block mx-auto" style="min-width: fit-content; width: auto;">
              Total
            </h6>
            <h4 class="mb-0 fw-bold text-dark">{{ $totalReceipts }}</h4>
          </div>
        </div>
      </div>
    </div>

    <!-- Receipts Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Pending Receipt Verifications</h5>
        <div class="d-flex gap-2">
          <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="verified">Verified</option>
            <option value="rejected">Rejected</option>
          </select>
          <input type="date" class="form-control form-control-sm" id="dateFilter" style="width: auto;" placeholder="Filter by date">
        </div>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover" id="receiptsTable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Parent</th>
              <th>Fee Type</th>
              <th>Amount</th>
              <th>Payment Date</th>
              <th>Receipt</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @if($receipts->isEmpty())
              <tr>
                <td colspan="9" class="text-center py-4">
                  <div class="text-muted">No receipts pending verification.</div>
                </td>
              </tr>
            @else
              @foreach($receipts as $receipt)
                <tr>
                  <td>{{ ($receipts->currentPage() - 1) * $receipts->perPage() + $loop->iteration }}</td>
                  <td>
                    <strong>{{ $receipt->payment->student->name }}</strong>
                    <br><small class="text-muted">{{ $receipt->payment->student->grade }}</small>
                  </td>
                  <td>{{ $receipt->parent->name }}</td>
                  <td>{{ $receipt->payment->fee_type }}</td>
                  <td>₹{{ number_format($receipt->payment->amount, 2) }}</td>
                  <td>{{ $receipt->payment_date->format('M d, Y') }}</td>
                  <td>
                    <a href="#" class="btn btn-outline-primary btn-sm view-receipt" 
                       data-bs-toggle="modal" data-bs-target="#viewReceiptModal"
                       data-receipt-url="{{ Storage::url($receipt->receipt_path) }}"
                       data-receipt-name="{{ basename($receipt->receipt_path) }}">
                      <i class="bx bx-show me-1"></i>View
                    </a>
                  </td>
                  <td>
                    <span class="badge bg-{{ $receipt->status === 'verified' ? 'success' : ($receipt->status === 'rejected' ? 'warning' : 'secondary') }}">
                      {{ ucfirst($receipt->status) }}
                    </span>
                  </td>
                  <td>
                    @if($receipt->status === 'pending')
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-success verify-receipt" 
                                data-receipt-id="{{ $receipt->id }}"
                                data-payment-id="{{ $receipt->payment_id }}">
                          <i class="bx bx-check"></i> Verify
                        </button>
                        <button type="button" class="btn btn-warning reject-receipt" 
                                data-bs-toggle="modal" data-bs-target="#rejectModal"
                                data-receipt-id="{{ $receipt->id }}">
                          <i class="bx bx-x"></i> Reject
                        </button>
                      </div>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            @endif
          </tbody>
        </table>
      </div>

      @if($receipts->hasPages())
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
          <div>
            Showing {{ $receipts->firstItem() }} to {{ $receipts->lastItem() }} of {{ $receipts->total() }} receipts
          </div>
          <div>
            {{ $receipts->links('pagination::bootstrap-5') }}
          </div>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- View Receipt Modal -->
<div class="modal fade" id="viewReceiptModal" tabindex="-1" aria-labelledby="viewReceiptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Receipt Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <div id="receiptPreview">
          <!-- Receipt will be loaded here -->
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn btn-primary" id="downloadReceiptBtn" download>
          <i class="bx bx-download me-1"></i> Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Reject Receipt</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="rejectForm" action="{{ route('accountant.receipts.reject') }}" method="POST">
        @csrf
        <input type="hidden" name="receipt_id" id="reject_receipt_id">
        <div class="modal-body">
          <div class="mb-3">
            <label for="rejection_reason" class="form-label">Reason for Rejection *</label>
            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" 
                      placeholder="Please specify the reason for rejecting this receipt..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Reject Receipt</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // View Receipt
    document.querySelectorAll('.view-receipt').forEach(button => {
      button.addEventListener('click', function () {
        const receiptUrl = this.getAttribute('data-receipt-url');
        const receiptName = this.getAttribute('data-receipt-name');
        const fileExtension = receiptName.split('.').pop().toLowerCase();
        
        const preview = document.getElementById('receiptPreview');
        const downloadBtn = document.getElementById('downloadReceiptBtn');
        
        downloadBtn.href = receiptUrl;
        downloadBtn.download = receiptName;
        
        if (fileExtension === 'pdf') {
          preview.innerHTML = `<iframe src="${receiptUrl}" width="100%" height="600px" style="border: none;"></iframe>`;
        } else {
          preview.innerHTML = `<img src="${receiptUrl}" alt="Receipt" class="img-fluid" style="max-height: 600px;">`;
        }
      });
    });

    // Verify Receipt
    document.querySelectorAll('.verify-receipt').forEach(button => {
      button.addEventListener('click', function () {
        const receiptId = this.getAttribute('data-receipt-id');
        const paymentId = this.getAttribute('data-payment-id');
        
        if (confirm('Are you sure you want to verify this receipt?')) {
          fetch('{{ route("accountant.receipts.verify") }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              receipt_id: receiptId,
              payment_id: paymentId
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              location.reload();
            } else {
              alert('Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while verifying the receipt.');
          });
        }
      });
    });

    // Reject Receipt Modal
    const rejectModal = document.getElementById('rejectModal');
    rejectModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const receiptId = button.getAttribute('data-receipt-id');
      document.getElementById('reject_receipt_id').value = receiptId;
    });

    // Filter functionality
    document.getElementById('statusFilter').addEventListener('change', function() {
      filterTable();
    });

    document.getElementById('dateFilter').addEventListener('change', function() {
      filterTable();
    });

    function filterTable() {
      const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
      const dateFilter = document.getElementById('dateFilter').value;
      const rows = document.querySelectorAll('#receiptsTable tbody tr');
      
      rows.forEach(row => {
        const status = row.cells[7].textContent.toLowerCase();
        const date = row.cells[5].textContent;
        const rowDate = new Date(date).toISOString().split('T')[0];
        
        const statusMatch = !statusFilter || status.includes(statusFilter);
        const dateMatch = !dateFilter || rowDate === dateFilter;
        
        row.style.display = statusMatch && dateMatch ? '' : 'none';
      });
    }
  });
</script>
@endsection