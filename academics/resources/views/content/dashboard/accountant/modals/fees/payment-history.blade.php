<div class="payment-history-content">
    {{-- Student and Fee Info --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-semibold">Student Information</h6>
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


                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <div>
                            <span class="text-muted small">Name:</span>
                            <div class="fw-medium">{{ $student->first_name }} {{ $student->last_name }}</div>
                        </div>
                        <div>
                            <span class="text-muted small">Class:</span>
                            <div class="fw-medium">
                                @if($student->class_id && isset($classes[$student->class_id]))
                                    {{ $classes[$student->class_id]->name }}
                                @else
                                    <span class="text-muted">Not assigned</span>
                                @endif
                            </div>
                        </div>
                        @if(isset($student->admission_number))
                        <div>
                            <span class="text-muted small">Admission No:</span>
                            <div class="fw-medium">{{ $student->admission_number }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-semibold">Fee Information</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <div>
                            <span class="text-muted small">Fee Name:</span>
                            <div class="fw-medium">{{ $fee->name }}</div>
                        </div>
                        <div>
                            <span class="text-muted small">Total Amount:</span>
                            <div class="fw-medium">{{ number_format($fee->amount, 2) }}</div>
                        </div>
                        <div>
                            <span class="text-muted small">Total Paid:</span>
                            <div class="fw-medium text-success">{{ number_format($totalPaid, 2) }}</div>
                        </div>
                        <div>
                            <span class="text-muted small">Due Amount:</span>
                            <div class="fw-medium {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($balance, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment History Table --}}
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0 fw-semibold">Payment History</h5>
        </div>
        <div class="card-body p-0">
            @if($payments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Payment Date</th>
                                <th>Amount Paid</th>
                                <th>Payment Method</th>
                                @if(session('role') === 'Accountant')
                                <th>Status</th>
                                @endif
                                <th class="d-none d-md-table-cell">Transaction Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $index => $payment)
                                <tr>
                                    <td class="ps-3">{{ $index + 1 }}</td>
                                    <td>
                                        @if(isset($payment->payment_date))
                                            <span class="d-inline d-md-none">{{ \Carbon\Carbon::parse($payment->payment_date)->format('m/d/Y') }}</span>
                                            <span class="d-none d-md-inline">{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}</span>
                                        @else
                                            <span class="d-inline d-md-none">{{ \Carbon\Carbon::parse($payment->created_at)->format('m/d/Y') }}</span>
                                            <span class="d-none d-md-inline">{{ \Carbon\Carbon::parse($payment->created_at)->format('M d, Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-success fw-bold">
                                        {{ number_format($payment->amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-capitalize">
                                            {{ $payment->payment_method ?? 'cash' }}
                                        </span>
                                    </td>

                                    @if(session('role') === 'Accountant')
                                   <td>
                                        <div class="dropdown">
                                            @php
                                                $statusColors = [
                                                    'paid' => 'success',
                                                    'pending' => 'warning',
                                                    'partial' => 'info'
                                                ];
                                                $badgeColor = $statusColors[$payment->status] ?? 'secondary';
                                            @endphp

                                            <!-- Badge that triggers dropdown -->
                                            <button class="btn btn-{{ $badgeColor }} btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ strtoupper($payment->status) }}
                                            </button>

                                            <!-- Dropdown form -->
                                            <ul class="dropdown-menu p-3">
                                                <form action="{{ url('accountant/payments/'.$payment->id.'/status') }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <select name="status" class="form-select mb-2">
                                                        <option value="paid" {{ $payment->status === 'paid' ? 'selected' : '' }}>PAID</option>
                                                        <option value="pending" {{ $payment->status === 'pending' ? 'selected' : '' }}>PENDING</option>
                                                         <option value="rejected" {{ $payment->status === 'rejected' ? 'selected' : '' }}>REJECTED</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary btn-sm w-100">Update</button>
                                                </form>
                                            </ul>
                                        </div>
                                    </td>
                                    @endif

                                    <td class="d-none d-md-table-cell">
                                        {{ \Carbon\Carbon::parse($payment->created_at)->format('M d, Y h:i A') }}
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            @if($payment->receipt_path)
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="downloadPendingPayment({{ $payment->id }})"
                                                        title="Download Receipt">
                                                    <i class="bx bx-download"></i>
                                                </button>
                                            @else
                                                <span class="text-muted small">No receipt</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <td colspan="2" class="text-end fw-semibold ps-3">Total Paid:</td>
                                <td colspan="5" class="text-success fw-bold">
                                    {{ number_format($totalPaid, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bx bx-receipt fs-1 text-muted mb-3"></i>
                    <p class="text-muted mb-0">No payment records found.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Payment Summary --}}
    @if($payments->count() > 0)
        <div class="row mt-4 g-2 g-sm-3 align-items-end">
            <div class="col-12 col-sm-4">
                <div class="card border-0 bg-light text-dark h-100">
                    <div class="card-body text-center p-2 p-sm-3 d-flex flex-column justify-content-end">
                        <h6 class="card-title mb-1 small text-muted fw-normal">Total Fee</h6>
                        <h5 class="mb-0 fw-bold text-dark">{{ number_format($fee->amount, 2) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="card border-0 bg-light text-dark h-100">
                    <div class="card-body text-center p-2 p-sm-3 d-flex flex-column justify-content-end">
                        <h6 class="card-title mb-1 small text-muted fw-normal">Total Paid</h6>
                        <h5 class="mb-0 fw-bold text-success">{{ number_format($totalPaid, 2) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="card border-0 bg-light text-dark h-100">
                    <div class="card-body text-center p-2 p-sm-3 d-flex flex-column justify-content-end">
                        <h6 class="card-title mb-1 small text-muted fw-normal">Due Amount</h6>
                        <h5 class="mb-0 fw-bold {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($balance, 2) }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function downloadReceipt(paymentId) {
    const link = document.createElement('a');
    link.href = `/accountant/payments/${paymentId}/receipt/download`;
    link.style.display = 'none';
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Reset button after a short delay
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.disabled = false;
    }, 1500);

}

// Alternative method using fetch (if you prefer)
function downloadReceiptFetch(paymentId) {
    // Show loading state
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bx bx-loader bx-spin"></i>';
    button.disabled = true;

    fetch(`/accountant/payments/${paymentId}/receipt/download`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to download receipt');
            }
            return response.blob();
        })
        .then(blob => {
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `receipt-${paymentId}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Error downloading receipt:', error);
            alert('Failed to download receipt');
        })
        .finally(() => {
            // Reset button
            button.innerHTML = originalHTML;
            button.disabled = false;
        });
}
</script>

<style>
.payment-history-content .card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.payment-history-content .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
}
.payment-history-content .card-body {
    padding: 1rem;
}
.table th {
    border-top: none;
    font-weight: 600;
    white-space: nowrap;
}
.payment-history-content .bg-light {
    background-color: #f8f9fa !important;
}
@media (max-width: 768px) {
    .payment-history-content .card-body {
        padding: 0.75rem;
    }
    .payment-history-content .table-responsive {
        font-size: 0.875rem;
    }
    .payment-history-content .card-header h5 {
        font-size: 1rem;
    }
    .payment-history-content .d-flex.gap-1 {
        flex-direction: column;
        gap: 0.25rem !important;
    }
}
@media (max-width: 576px) {
    .payment-history-content .row {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
    }
    .payment-history-content .col-md-6,
    .payment-history-content .col-12 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .payment-history-content .card-body {
        padding: 0.75rem;
    }
    .payment-history-content .table {
        font-size: 0.8rem;
    }
    .payment-history-content .badge {
        font-size: 0.7rem;
    }
    /* Hide transaction date on very small screens */
    .payment-history-content .d-none.d-md-table-cell {
        display: none !important;
    }
    /* Vertical alignment for summary cards on mobile */
    .payment-history-content .align-items-end .col-12 {
        margin-bottom: 0.5rem;
    }
    .payment-history-content .align-items-end .col-12:last-child {
        margin-bottom: 0;
    }
}
</style>