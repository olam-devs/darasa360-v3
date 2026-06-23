@extends('layouts.modernLayout', [
    'pageTitle' => 'Bulk Invoice Generation'
])
@section('title', 'Bulk Invoice Generation')

@section('content')
<div class="row">
  <div class="col-12">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Bulk Invoice Generation</h4>
      <div>
        <a href="{{ route('accountant.fees') }}" class="btn btn-outline-secondary me-2">
          <i class="bx bx-arrow-back"></i> Back to Fees
        </a>
      </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    {{-- Generation Form --}}
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Invoice Generation Settings</h5>
      </div>
      <div class="card-body">
        <form id="bulkInvoiceForm" method="POST" action="{{ route('invoices.generate.bulk') }}">
          @csrf
          
          <div class="row">
            {{-- Fee Selection --}}
            <div class="col-md-6 mb-3">
              <label for="fee_id" class="form-label">Select Fee *</label>
              <select class="form-select" id="fee_id" name="fee_id" required onchange="updateStudentCount()">
                <option value="">-- Select Fee --</option>
                @foreach($fees as $fee)
                  <option value="{{ $fee->id }}" data-class="{{ $fee->class_id }}">
                    {{ $fee->name }} - Tsh {{ number_format($fee->amount, 2) }}
                    @if($fee->class_id)
                      ({{ $classes->firstWhere('id', $fee->class_id)->name }})
                    @else
                      (All Classes)
                    @endif
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Class Selection --}}
           <div class="col-md-6 mb-3">
            <label for="class_id" class="form-label">Filter by Class (Optional)</label>
            <select class="form-select" id="class_id" name="class_id" onchange="updateStudentCount()">
              @if($totalClasses === $assignedClassCount)
                <option value="">All Classes</option>
              @endif
              @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
              @endforeach
            </select>
          </div>

          </div>

          {{-- Student Count Preview --}}
          <div class="row mb-4">
            <div class="col-12">
              <div class="alert alert-info" id="studentCountAlert">
                <i class="bx bx-info-circle"></i>
                <span id="studentCountText">Select a fee to see affected students</span>
              </div>
            </div>
          </div>

          {{-- Optional Services --}}
          {{-- <div class="row">
            <div class="col-12 mb-4">
              <label class="form-label fw-semibold">Include Optional Services</label>
              <div class="border p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                @if(isset($optionalServices) && count($optionalServices) > 0)
                  <div class="row">
                    @foreach($optionalServices as $service)
                      <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100">
                          <div class="card-body">
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" name="services[]" 
                                     value="{{ $service->id }}" id="service_{{ $service->id }}"
                                     onchange="calculateTotalAmount()">
                              <label class="form-check-label w-100" for="service_{{ $service->id }}">
                                <div class="fw-medium">{{ $service->name }}</div>
                                <div class="text-success fw-bold">Tsh {{ number_format($service->amount, 2) }}</div>
                                @if($service->description)
                                  <small class="text-muted">{{ $service->description }}</small>
                                @endif
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @else
                  <p class="text-muted mb-0 text-center py-3">
                    <i class="bx bx-package fs-1"></i><br>
                    No optional services available.
                  </p>
                @endif
              </div>
            </div>
          </div> --}}

          {{-- Output Settings --}}
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="format" class="form-label">Output Format *</label>
              <select class="form-select" id="format" name="format" required>
                <option value="pdf">PDF Documents</option>
                <option value="excel">Excel Spreadsheet</option>
                {{-- <option value="zip">ZIP File (Multiple PDFs)</option> --}}
              </select>
              <div class="form-text" id="formatDescription">
                Single PDF file with all invoices
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <label for="send_method" class="form-label">Delivery Method</label>
              <select class="form-select" id="send_method" name="send_method">
                <option value="">Download Only</option>
                {{-- <option value="in_app">Send via In-App Notification</option>
                <option value="sms">Send via SMS</option>
                <option value="both">Both In-App & SMS</option> --}}
              </select>
              <div class="form-text">Choose how to deliver invoices to parents</div>
            </div>
          </div>

          {{-- Invoice Customization --}}
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="due_date" class="form-label">Due Date</label>
              <input type="date" class="form-control" id="due_date" name="due_date" 
                     value="{{ \Carbon\Carbon::now()->addDays(30)->format('Y-m-d') }}">
              <div class="form-text">Set invoice due date</div>
            </div>

            <div class="col-md-6 mb-3">
              <label for="invoice_notes" class="form-label">Invoice Notes (Optional)</label>
              <textarea class="form-control" id="invoice_notes" name="invoice_notes" 
                        rows="2" placeholder="Add custom notes to all invoices..."></textarea>
            </div>
          </div>

          {{-- Summary Card --}}
          <div class="row">
            <div class="col-12">
              <div class="card bg-light">
                <div class="card-header">
                  <h6 class="mb-0">Generation Summary</h6>
                </div>
                <div class="card-body">
                  <div class="row text-center">
                    <div class="col-md-3">
                      <div class="border-end pe-3">
                        <div class="fs-4 fw-bold text-primary" id="summaryStudents">0</div>
                        <small class="text-muted">Students</small>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="border-end pe-3">
                        <div class="fs-4 fw-bold text-success" id="summaryFeeAmount">Tsh 0.00</div>
                        <small class="text-muted">Base Fee</small>
                      </div>
                    </div>
                    {{-- <div class="col-md-3">
                      <div>
                        <div class="fs-4 fw-bold text-dark" id="summaryTotalAmount">Tsh 0.00</div>
                        <small class="text-muted">Total per Student</small>
                      </div>
                    </div> --}}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Action Buttons --}}
          <div class="row mt-4">
            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  {{-- <button type="button" class="btn btn-outline-secondary" onclick="previewInvoices()">
                    <i class="bx bx-show"></i> Preview Sample
                  </button> --}}
                </div>
                <div>
                  <button type="submit" class="btn btn-primary" id="generateBtn">
                    <i class="bx bx-receipt"></i> Generate Invoices
                  </button>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    {{-- Recent Invoices --}}
    {{-- <div class="card mt-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Recent Bulk Generations</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Fee</th>
                <th>Class</th>
                <th>Students</th>
                <th>Format</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="invoiceHistory">
              <tr>
                <td colspan="7" class="text-center py-4">
                  <div class="spinner-border spinner-border-sm me-2"></div>
                  Loading generation history...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div> --}}
  </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Invoice Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center py-4">
          <i class="bx bx-receipt fs-1 text-muted mb-3"></i>
          <p class="text-muted">Preview will be displayed here</p>
          <p class="small text-muted">Note: Actual invoices will include individual student details</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Global variables
let studentsData = [];
let selectedFee = null;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
  updateFormatDescription();
  loadInvoiceHistory();
  
  // Add event listeners
  document.getElementById('format').addEventListener('change', updateFormatDescription);
  document.getElementById('fee_id').addEventListener('change', loadFeeDetails);
});

// Update format description
function updateFormatDescription() {
  const format = document.getElementById('format').value;
  const description = document.getElementById('formatDescription');
  
  switch(format) {
    case 'pdf':
      description.textContent = 'Single PDF file with all invoices';
      break;
    case 'excel':
      description.textContent = 'Excel spreadsheet with invoice data';
      break;
    case 'zip':
      description.textContent = 'ZIP file containing individual PDF invoices';
      break;
  }
}

// Load fee details and update student count
async function loadFeeDetails() {
  const feeId = document.getElementById('fee_id').value;
  const classId = document.getElementById('class_id').value;
  
  if (!feeId) {
    resetSummary();
    return;
  }

  // Show loading
  document.getElementById('studentCountText').innerHTML = 
    '<div class="spinner-border spinner-border-sm me-2"></div>Loading students...';
  
  try {
    const response = await fetch(`/accountant/fees/${feeId}/students-count?class_id=${classId}`);
    const data = await response.json();
    
    studentsData = data.students || [];
    selectedFee = data.fee;
    
    updateStudentCountDisplay(data.count, data.fee);
    calculateTotalAmount();
    
  } catch (error) {
    console.error('Error loading fee details:', error);
    document.getElementById('studentCountText').textContent = 
      'Error loading student data. Please try again.';
  }
}

// Update student count based on filters
async function updateStudentCount() {
  await loadFeeDetails();
}

// Update student count display
function updateStudentCountDisplay(count, fee) {
  const countText = document.getElementById('studentCountText');
  const classFilter = document.getElementById('class_id').value;
  const className = classFilter ? 
    document.getElementById('class_id').options[document.getElementById('class_id').selectedIndex].text : 
    'All Classes';
  
  if (count > 0) {
    countText.innerHTML = 
      // `<strong>${count} students</strong> in <strong>${className}</strong> will receive invoices for <strong>${fee.name}</strong>`;
      `Total Students: <strong>${count}</strong>`;
    document.getElementById('studentCountAlert').className = 'alert alert-success';
  } else {
    countText.textContent = `No students found in ${className} for the selected fee.`;
    document.getElementById('studentCountAlert').className = 'alert alert-warning';
  }
  
  // Update summary
  document.getElementById('summaryStudents').textContent = count;
  document.getElementById('summaryFeeAmount').textContent = `Tsh ${formatCurrency(fee.amount)}`;

}

// Calculate total amount including optional services
function calculateTotalAmount() {
  if (!selectedFee) return;

  const studentCount = parseInt(document.getElementById('summaryStudents').textContent) || 0;

  let servicesAmount = 0;
  const serviceCheckboxes = document.querySelectorAll('input[name="services[]"]:checked');

  serviceCheckboxes.forEach(checkbox => {
    const serviceId = checkbox.value;
    const service = {!! $optionalServices ?? '[]' !!}.find(s => s.id == serviceId);
    if (service) {
      servicesAmount += parseFloat(service.amount);
    }
  });

  const perStudentAmount = parseFloat(selectedFee.amount) + servicesAmount;
  const totalAmount = perStudentAmount * studentCount;

  document.getElementById('summaryServicesAmount').textContent = `Tsh ${formatCurrency(servicesAmount)}`;
  document.getElementById('summaryTotalAmount').textContent = `Tsh ${formatCurrency(totalAmount)}`;
}


// Reset summary
function resetSummary() {
  document.getElementById('studentCountText').textContent = 'Select a fee to see affected students';
  document.getElementById('studentCountAlert').className = 'alert alert-info';
  document.getElementById('summaryStudents').textContent = '0';
  document.getElementById('summaryFeeAmount').textContent = 'Tsh 0.00';
  document.getElementById('summaryServicesAmount').textContent = 'Tsh 0.00';
  document.getElementById('summaryTotalAmount').textContent = 'Tsh 0.00';
}

// Preview invoices
function previewInvoices() {
  const feeId = document.getElementById('fee_id').value;
  if (!feeId) {
    alert('Please select a fee first');
    return;
  }
  
  const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
  previewModal.show();
  
  // In a real implementation, you might fetch and display an actual preview
  // For now, we'll just show the modal
}

// Load invoice generation history
// Load invoice generation history
async function loadInvoiceHistory() {
  try {
    const response = await fetch('/accountant/invoices/history');
    const data = await response.json();

    const historyBody = document.getElementById('invoiceHistory');

    if (!data.length) {
      historyBody.innerHTML = 
        `<tr><td colspan="7" class="text-center py-4 text-muted">
          No generation history found
        </td></tr>`;
      return;
    }

    historyBody.innerHTML = data.map(invoice => {
      // Format display (if format info is saved, otherwise default PDF)
      const format = invoice.format ? invoice.format.toUpperCase() : 'PDF';
      
      // Status badge based on invoice status
      let statusBadge = '';
      switch (invoice.status) {
        case 'draft':
          statusBadge = '<span class="badge bg-secondary">Draft</span>';
          break;
        case 'sent':
          statusBadge = '<span class="badge bg-info">Sent</span>';
          break;
        case 'paid':
          statusBadge = '<span class="badge bg-success">Paid</span>';
          break;
        case 'overdue':
          statusBadge = '<span class="badge bg-danger">Overdue</span>';
          break;
        case 'cancelled':
          statusBadge = '<span class="badge bg-dark">Cancelled</span>';
          break;
        default:
          statusBadge = `<span class="badge bg-primary">${invoice.status}</span>`;
      }

      return `
        <tr>
          <td>${new Date(invoice.issue_date).toLocaleDateString()}</td>
          <td>${invoice.fee_name}</td>
          <td>${invoice.class_name || 'All Classes'}</td>
          <td>${invoice.student_count}</td>
          <td><span class="badge bg-info">${format}</span></td>
          <td>
            <button class="btn btn-sm btn-outline-primary" onclick="downloadGeneratedInvoice(${invoice.id})">
              <i class="bx bx-download"></i>
            </button>
          </td>
        </tr>
      `;
    }).join('');

  } catch (error) {
    console.error('Error loading invoice history:', error);
    document.getElementById('invoiceHistory').innerHTML = 
      `<tr><td colspan="7" class="text-center py-4 text-danger">
        Error loading history
      </td></tr>`;
  }
}

// Refresh history
function refreshHistory() {
  loadInvoiceHistory();
}

// Example function to download invoice (implement endpoint)
function downloadGeneratedInvoice(invoiceId) {
  window.open(`/accountant/invoices/${invoiceId}/download`, '_blank');
}


// Download generated invoice
function downloadGeneratedInvoice(invoiceId) {
  window.open(`/accountant/invoices/${invoiceId}/download`, '_blank');
}

// Form submission handler
document.getElementById('bulkInvoiceForm').addEventListener('submit', function(e) {
  const feeId = document.getElementById('fee_id').value;
  const studentCount = parseInt(document.getElementById('summaryStudents').textContent);
  
  if (!feeId) {
    e.preventDefault();
    alert('Please select a fee');
    return;
  }
  
  if (studentCount === 0) {
    e.preventDefault();
    alert('No students found for the selected criteria');
    return;
  }
  
  // Show loading state
  const generateBtn = document.getElementById('generateBtn');
  const originalText = generateBtn.innerHTML;
  generateBtn.innerHTML = '<i class="bx bx-loader bx-spin"></i> Generating...';
  generateBtn.disabled = true;
  
  // Re-enable button after 5 seconds (in case of error)
  setTimeout(() => {
    generateBtn.innerHTML = originalText;
    generateBtn.disabled = false;
  }, 5000);
});

// Format number with commas and 2 decimals
function formatCurrency(amount) {
  return parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

</script>

<style>
.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.form-check-input:checked {
  background-color: #198754;
  border-color: #198754;
}
.border-end:last-child {
  border-right: none !important;
}
</style>
@endsection