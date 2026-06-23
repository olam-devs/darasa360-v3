@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Fees'
])
@section('title', 'Manage Fees')

@section('content')
<div class="row">
  <div class="col-12">
    {{-- Header --}}
<div class="row mb-4">
  <div class="col-12 col-md-6">
    <h4 class="fw-bold mb-3 mb-md-0">Manage Fees</h4>
  </div>
  <div class="col-12 col-md-6">
    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-md-end">
      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reportsModal">
        <i class="bx bx-chart"></i> Reports
      </button>

      @if($isAccountant)
        <button type="button" class="btn btn-outline-warning position-relative" data-bs-toggle="modal" data-bs-target="#verifyPaymentsModal">
          <i class="bx bx-check-shield"></i> Verify Payments
          @if($pendingCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              {{ $pendingCount }}
            </span>
          @endif
        </button>
      @endif

       @if($isAccountant)
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal">
        <i class="bx bx-plus"></i> Add New Fee
      </button>
       @endif
    </div>
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

    {{-- Quick Stats --}}
   <div class="row mb-4 g-2 g-sm-3">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100 position-relative">
      <div class="position-absolute top-0 start-0 w-100 h-2 bg-primary"></div>
      <div class="card-body text-center p-2 p-sm-3 d-flex flex-column justify-content-end pt-4">
        <h6 class="card-title mb-1 small text-muted fw-normal">Total Fees</h6>
        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalFees, 2) }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100 position-relative">
      <div class="position-absolute top-0 start-0 w-100 h-2 bg-success"></div>
      <div class="card-body text-center p-2 p-sm-3 d-flex flex-column justify-content-end pt-4">
        <h6 class="card-title mb-1 small text-muted fw-normal">Total Collected</h6>
        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalCollected, 2) }}</h4>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm h-100 position-relative">
      <div class="position-absolute top-0 start-0 w-100 h-2 bg-danger"></div>
      <div class="card-body text-center p-2 p-sm-3 d-flex flex-column justify-content-end pt-4">
        <h6 class="card-title mb-1 small text-muted fw-normal">Overdue</h6>
        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalOverdue, 2) }}</h4>
      </div>
    </div>
  </div>
</div>

    {{-- Fees Table --}}
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Fee Structure</h5>
        {{-- <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportFees('pdf')">
            <i class="bx bx-file"></i> PDF
          </button>
          <button type="button" class="btn btn-sm btn-outline-success" onclick="exportFees('excel')">
            <i class="bx bx-spreadsheet"></i> Excel
          </button>
        </div> --}}
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-bordered table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Fee Name</th>
              <th>Class</th>
              <th>Amount</th>
              <th>Due Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
  @forelse($fees as $fee)
    @php
      $dueStatus = $fee->due_date && \Carbon\Carbon::parse($fee->due_date)->isPast() ? 'overdue' : 'active';
    @endphp
    <tr>
      <td>{{ $loop->iteration + ($fees->currentPage() - 1) * $fees->perPage() }}</td>
      <td>{{ $fee->name }}</td>

      {{-- Show multiple classes --}}
      <td>
        @if($fee->classes->isNotEmpty())
          {{ $fee->classes->pluck('name')->join(', ') }}
        @else
          <span class="text-muted">All Classes</span>
        @endif
      </td>

      <td>{{ number_format($fee->amount, 2) }}</td>
      <td>
        {{ $fee->due_date ?? 'N/A' }}
        @if($dueStatus === 'overdue')
          <span class="badge bg-danger ms-1">Overdue</span>
        @endif
      </td>
      
      <td>
        <div class="d-flex gap-1">
          <button type="button" class="btn btn-sm btn-outline-info"
                  data-bs-toggle="modal"
                  data-bs-target="#viewFeeModal"
                  onclick="openViewFee({{ $fee->id }}, '{{ $fee->name }}', '{{ $fee->amount }}')">
            <i class="bx bx-show"></i>
          </button>

          @if($isAccountant)
          <button type="button" class="btn btn-sm btn-outline-warning"
                  data-bs-toggle="modal"
                  data-bs-target="#editFeeModal"
                 onclick="openEditFee({{ $fee->id }}, '{{ $fee->name }}', '{{ $fee->amount }}', '{{ $fee->due_date }}', '{{ $fee->is_active }}')">
            <i class="bx bx-edit"></i>
          </button>
          @endif
          <button type="button" class="btn btn-sm btn-outline-secondary"
                  onclick="generateInvoices({{ $fee->id }})">
            <i class="bx bx-receipt"></i>
          </button>

          @if($isAccountant)
          <form action="{{ route('fees.destroy', $fee->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash"></i></button>
          </form>
          @endif
        </div>
      </td>
    </tr>
  @empty
    <tr>
      <td colspan="7" class="text-center">No fees found.</td>
    </tr>
  @endforelse
</tbody>

        </table>
      </div>
      <div class="mt-3 mx-3">
        {{ $fees->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

{{-- ================= Modals ================= --}}

{{-- Verify Payments Modal --}}
<div class="modal fade" id="verifyPaymentsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-warning bg-opacity-10">
        <h5 class="modal-title"><i class="bx bx-check-shield me-1"></i> Verify Pending Payments</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="verify_fee_filter" class="form-label">Filter by Fee</label>
            <select class="form-select" id="verify_fee_filter" onchange="filterPendingPayments()">
              <option value="">All Fees</option>
              @foreach($fees as $fee)
                <option value="{{ $fee->id }}">{{ $fee->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label for="verify_class_filter" class="form-label">Filter by Class</label>
            <select class="form-select" id="verify_class_filter" onchange="filterPendingPayments()">
              <option value="">All Classes</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-outline-secondary w-100" onclick="filterPendingPayments()">
              <i class="bx bx-filter-alt"></i> Apply Filters
            </button>
          </div>
        </div>

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Students with Pending Payments</h6>
            <span id="verify_total_pending" class="badge bg-danger">{{ $pendingCount }} Pending</span>
          </div>

          <div class="table-responsive text-nowrap">
            <table class="table table-striped">
    <thead>
        <tr>
            <th>Student</th>
            <th>Class</th>
            <th>Fee</th>
            <th>Amount Due</th>
            <th>Due Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody id="verifyPaymentsBody">
        @include('content.dashboard.accountant.modals.invoices.verify-payments-body', ['pendingPayments' => $pendingPayments])
    </tbody>
</table>


{{ $pendingPayments->links() }}

          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



{{-- View Fee Modal --}}
<div class="modal fade" id="viewFeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Fee: <span id="viewFeeName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="viewClassSelect" class="form-label">Select Class</label>
            <select class="form-select" id="viewClassSelect" onchange="loadStudentsForFee()">
              <option value="">-- Select Class --</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label for="studentSearchInput" class="form-label">Search Student</label>
            <input type="text" id="studentSearchInput" class="form-control" placeholder="Type student name..." onkeyup="loadStudentsForFee()">
          </div>
        </div>

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Student Payments</h5>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table table-bordered table-hover">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Student</th>
                  <th>Paid</th>
                  <th>Required</th>
                  <th>Due</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="studentsTableBody">
                <tr>
                  <td colspan="7" class="text-center">Select a class or type a student name.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


{{-- Payment History Modal --}}
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Payment History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="paymentHistoryBody">
        Loading...
      </div>
    </div>
  </div>
</div>

{{-- Add Payment Modal --}}
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addPaymentForm" method="POST" action="" enctype="multipart/form-data">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Record Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="student_id" id="payment_student_id">
          <input type="hidden" name="fee_id" id="payment_fee_id">
          <input type="hidden" id="fee_total_amount"> <!-- Store total fee -->

          <div class="mb-3">
            <label for="payment_amount" class="form-label">Amount *</label>
            <input type="number" class="form-control" name="amount" id="payment_amount" step="0.01" required>
          </div>

          <!-- Read-only formatted fields -->
          <div class="mb-3">
            <label class="form-label">Amount (Formatted)</label>
            <input type="text" class="form-control" id="formatted_payment_amount" readonly>
          </div>

          {{-- <div class="mb-3">
            <label class="form-label">Remaining Balance</label>
            <input type="text" class="form-control" id="remaining_payment_balance" readonly>
          </div> --}}

         <div class="mb-3">
            <label for="payment_method" class="form-label">Payment Method *</label>
            <select class="form-select" name="payment_method" id="payment_method" required>
              <option value="">Loading...</option>
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
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Payment</button>
        </div>
      </div>
    </form>
  </div>
</div>



{{-- Add Fee Modal --}}
{{-- Add Fee Modal --}}
<div class="modal fade" id="addFeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('fees.store') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Fee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="fee_name" class="form-label">Fee Name *</label>
            <input type="text" class="form-control" id="fee_name" name="name" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Assign to Classes & Students *</label>

            <div id="class-student-container">
              {{-- First block --}}
              <div class="class-student-block border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="flex-grow-1 me-2">
                    <label class="form-label">Select Class</label>
                    <select class="form-select class-select" name="assignments[0][class_id]" onchange="loadStudents(this)">
                      <option value="">-- Choose Class --</option>
                      @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeClassBlock(this)">
                    <i class="bx bx-x"></i>
                  </button>
                </div>

                <div class="student-select-container">
                  <label class="form-label">Select Students</label>
                  <input type="text"
                         class="form-control form-control-sm mb-2 student-search"
                         placeholder="Search student..."
                         onkeyup="filterStudents(this)">
                  <div class="students-list text-muted small">Select a class first...</div>
                </div>
              </div>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addClassBtn">
              <i class="bx bx-plus"></i> Add Another Class
            </button>
          </div>

          <div class="mb-3">
            <label for="amount" class="form-label">Amount *</label>
            <input type="number" class="form-control" name="amount" id="fee_amount" step="0.01" min="0" required>
            <div class="form-text text-muted" id="formattedAmount">Tsh 0.00</div>
          </div>

          <script>
            const amountInput = document.getElementById('fee_amount');
            const formattedAmountDiv = document.getElementById('formattedAmount');

            amountInput.addEventListener('input', () => {
                const value = parseFloat(amountInput.value) || 0;
                formattedAmountDiv.textContent = 'Formatted: Tsh ' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            });
          </script>


          <div class="mb-3">
            <label for="due_date" class="form-label">Due Date</label>
            <input type="date" class="form-control" name="due_date">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Fee</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- ✅ Scripts --}}
<script>
  let classIndex = 1;

  // 🔹 Add another class block
  document.getElementById('addClassBtn').addEventListener('click', () => {
    const container = document.getElementById('class-student-container');
    const originalBlock = container.querySelector('.class-student-block');
    const newBlock = originalBlock.cloneNode(true);

    // ✅ Reset class dropdown
    const classSelect = newBlock.querySelector('.class-select');
    classSelect.name = `assignments[${classIndex}][class_id]`;
    classSelect.value = '';

    // ✅ Reset student list and search
    const studentSearch = newBlock.querySelector('.student-search');
    studentSearch.value = '';
    const studentsList = newBlock.querySelector('.students-list');
    studentsList.innerHTML = 'Select a class first...';

    // ✅ Remove any previously selected checkboxes
    const oldCheckboxes = newBlock.querySelectorAll('.student-checkbox');
    oldCheckboxes.forEach(cb => cb.remove());

    // ✅ Update any name attributes inside block
    newBlock.querySelectorAll('[name]').forEach(el => {
      el.name = el.name.replace(/\[\d+\]/, `[${classIndex}]`);
    });

    container.appendChild(newBlock);
    classIndex++;
  });

  // 🔹 Remove class block
  function removeClassBlock(button) {
    const container = document.getElementById('class-student-container');
    if (container.querySelectorAll('.class-student-block').length > 1) {
      button.closest('.class-student-block').remove();
    }
  }

  // 🔹 Load students dynamically by class
  function loadStudents(select) {
    const classId = select.value;
    const block = select.closest('.class-student-block');
    const listContainer = block.querySelector('.students-list');
    const searchInput = block.querySelector('.student-search');

    if (!classId) {
      listContainer.innerHTML = 'Select a class first...';
      searchInput.value = '';
      return;
    }

    listContainer.innerHTML = '<i class="bx bx-loader bx-spin"></i> Loading...';
    searchInput.value = '';

    fetch(`/accountant/classes/${classId}/students`)
      .then(res => res.json())
      .then(students => {
        if (!students.length) {
          listContainer.innerHTML = '<span class="text-muted">No students found.</span>';
          return;
        }

        const index = select.name.match(/\d+/)[0];
        const html = `
          <div class="form-check mb-1">
            <input class="form-check-input select-all" type="checkbox" onchange="toggleSelectAll(this)">
            <label class="form-check-label fw-bold">Select All</label>
          </div>
          ${students.map(s => `
            <div class="form-check ms-3">
              <input class="form-check-input student-checkbox"
                     type="checkbox"
                     name="assignments[${index}][student_ids][]"
                     value="${s.id}">
              <label class="form-check-label">${s.full_name}</label>
            </div>
          `).join('')}
        `;
        listContainer.innerHTML = html;
      })
      .catch(() => listContainer.innerHTML = '<span class="text-danger">Failed to load students.</span>');
  }

  // 🔹 “Select All” checkbox toggle
  function toggleSelectAll(checkbox) {
    const container = checkbox.closest('.students-list');
    const checkboxes = container.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
  }

  // 🔹 Filter/search students
  function filterStudents(input) {
    const query = input.value.toLowerCase();
    const block = input.closest('.class-student-block');
    const students = block.querySelectorAll('.student-checkbox + label');

    students.forEach(label => {
      const text = label.textContent.toLowerCase();
      const parent = label.closest('.form-check');
      if (parent.querySelector('.select-all')) return;
      parent.style.display = text.includes(query) ? '' : 'none';
    });
  }
</script>



{{-- Edit Fee Modal --}}
{{-- Edit Fee Modal --}}
<div class="modal fade" id="editFeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" id="editFeeForm">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Fee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_fee_name" class="form-label">Fee Name *</label>
            <input type="text" class="form-control" id="edit_fee_name" name="name" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Assign to Classes & Students *</label>

            {{-- ✅ Changed ID to be unique for edit modal --}}
            <div id="edit-class-student-container">
              <div class="class-student-block border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="flex-grow-1 me-2">
                    <label class="form-label">Select Class</label>
                    <select class="form-select class-select" name="assignments[0][class_id]" onchange="loadEditStudents(this)">
                      <option value="">-- Choose Class --</option>
                      @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeEditClassBlock(this)">
                    <i class="bx bx-x"></i>
                  </button>
                </div>

                <div class="student-select-container">
                  <label class="form-label">Select Students</label>
                  <input type="text"
                         class="form-control form-control-sm mb-2 student-search"
                         placeholder="Search student..."
                         onkeyup="filterEditStudents(this)">
                  <div class="students-list text-muted small">Select a class first...</div>
                </div>
              </div>
            </div>

            {{-- ✅ Changed ID to be unique --}}
            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addEditClassBtn">
              <i class="bx bx-plus"></i> Add Another Class
            </button>
          </div>

          <div class="mb-3">
            <label for="edit_amount" class="form-label">Amount *</label>
            <input type="number" class="form-control" id="edit_amount" name="amount" step="0.01" min="0" required>
          </div>

          <div class="mb-3">
            <label for="edit_due_date" class="form-label">Due Date</label>
            <input type="date" class="form-control" id="edit_due_date" name="due_date">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Fee</button>
        </div>
      </div>
    </form>
  </div>
</div>




{{-- Reports Modal --}}
<div class="modal fade" id="reportsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Generate Reports</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="reportForm" method="POST" action="{{ route('fees.reports.generate') }}">
          @csrf
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="report_type" class="form-label">Report Type *</label>
              <select class="form-select" id="report_type" name="report_type" required>
                <option value="">Select Report Type</option>
                <option value="class">Class Report</option>
                <option value="payment_method">Payment Method Report</option>
                {{-- <option value="term">Term Report</option>
                <option value="year">Annual Report</option> --}}
                <option value="overdue">Overdue Payments</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="format" class="form-label">Export Format *</label>
              <select class="form-select" id="format" name="format" required>
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
                {{-- <option value="both">Both PDF & Excel</option> --}}
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="start_date" class="form-label">Start Date</label>
              <input type="date" class="form-control" id="start_date" name="start_date">
            </div>
            <div class="col-md-6 mb-3">
              <label for="end_date" class="form-label">End Date</label>
              <input type="date" class="form-control" id="end_date" name="end_date">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="class_id_report" class="form-label">Class</label>
              <select class="form-select" id="class_id_report" name="class_id">
                {{-- <option value="">All Classes</option> --}}
                @foreach($classes as $class)
                  <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
              </select>
            </div>
            {{-- <div class="col-md-6 mb-3">
              <label for="send_to" class="form-label">Send To (Optional)</label>
              <select class="form-select" id="send_to" name="send_to">
                <option value="">Don't Send</option>
                <option value="headmaster">Headmaster</option>
                <option value="owner">School Owner</option>
                <option value="both">Both</option>
              </select>
            </div> --}}
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="generateReport()">Generate Report</button>
      </div>
    </div>
  </div>
</div>

{{-- Send Reminders Modal --}}
<div class="modal fade" id="sendRemindersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Send Payment Reminders</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="reminderForm" method="POST" action="{{ route('fees.reminders.send') }}">
          @csrf
          <div class="mb-3">
            <label for="reminder_type" class="form-label">Reminder Type *</label>
            <select class="form-select" id="reminder_type" name="reminder_type" required>
              <option value="overdue">Overdue Payments</option>
              <option value="upcoming">Upcoming Due Dates</option>
              <option value="partial">Partial Payments</option>
              <option value="all">All Pending</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="reminder_channel" class="form-label">Send Via *</label>
            <div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="in_app" name="channels[]" value="in_app" checked>
                <label class="form-check-label" for="in_app">In-App Notification</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="sms" name="channels[]" value="sms">
                <label class="form-check-label" for="sms">SMS</label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label for="control_number" class="form-label">Include Control Number</label>
            <select class="form-select" id="control_number" name="control_number">
              <option value="none">No Control Number</option>
              <option value="student">Student-specific Number</option>
              <option value="school">General School Account</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="custom_message" class="form-label">Custom Message (Optional)</label>
            <textarea class="form-control" id="custom_message" name="custom_message" rows="3" placeholder="Add a custom message..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="sendReminders()">Send Reminders</button>
      </div>
    </div>
  </div>
</div>

{{-- Generate Invoices Modal --}}
<div class="modal fade" id="generateInvoicesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Generate Invoices</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="invoiceForm" method="POST" action="{{ route('invoices.generate.bulk') }}">
          @csrf
          <input type="hidden" name="fee_id" id="invoice_fee_id">
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="class_id_invoice" class="form-label">Select Class</label>
              <select class="form-select" id="class_id_invoice" name="class_id">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                  <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="invoice_format" class="form-label">Output Format *</label>
              <select class="form-select" name="format" id="invoice_format" required>
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
                <option value="both">Both PDF & Excel</option>
              </select>
            </div>
          </div>

          {{-- <div class="mb-3">
            <label class="form-label">Include Optional Services</label>
            <div class="border p-3 rounded" style="max-height: 200px; overflow-y: auto;">
              @foreach($optionalServices ?? [] as $service)
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="services[]" value="{{ $service->id }}" id="service_{{ $service->id }}">
                  <label class="form-check-label" for="service_{{ $service->id }}">
                    <strong>{{ $service->name }}</strong> - Tsh {{ number_format($service->amount, 2) }}
                    @if($service->description)
                      <br><small class="text-muted">{{ $service->description }}</small>
                    @endif
                  </label>
                </div>
              @endforeach
              @if(!isset($optionalServices) || count($optionalServices ?? []) === 0)
                <p class="text-muted mb-0">No optional services available.</p>
              @endif
            </div>
          </div> --}}
          
          <div class="mb-3">
            <label for="send_invoice" class="form-label">Delivery Method</label>
            <select class="form-select" name="send_method" id="send_invoice">
              <option value="">Download Only</option>
              <option value="in_app">Send via In-App</option>
              <option value="sms">Send via SMS</option>
              <option value="both">Both In-App & SMS</option>
            </select>
            <div class="form-text">Select how to deliver invoices to parents/students</div>
          </div>

          <div class="alert alert-info">
            <i class="bx bx-info-circle"></i>
            This will generate invoices for all students in the selected class(es) with the chosen fee and optional services.
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="processInvoices()">Generate Invoices</button>
      </div>
    </div>
  </div>
</div>

<script>
let currentFeeId = null;
let currentFeeAmount = 0;

// Event delegation for download receipt buttons
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-download-receipt]')) {
        e.preventDefault();
        const button = e.target.closest('[data-download-receipt]');
        const paymentId = button.getAttribute('data-download-receipt');
        downloadReceipt(paymentId, button);
    }
});

function openViewFee(feeId, feeName, feeAmount) {
    currentFeeId = feeId;
    currentFeeAmount = feeAmount;
    const feeNameElem = document.getElementById('viewFeeName');
    const classSelect = document.getElementById('viewClassSelect');
    const studentsBody = document.getElementById('studentsTableBody');
    if(feeNameElem) feeNameElem.innerText = feeName;
    if(classSelect) classSelect.value = '';
    if(studentsBody) studentsBody.innerHTML = '<tr><td colspan="8" class="text-center">Select a class to view students.</td></tr>';
}

function openEditFeeModal(fee) {
  const container = document.getElementById('edit_class_student_container');
  container.innerHTML = ''; // clear previous blocks

  // Set form action and fields
  document.getElementById('editFeeForm').action = '/fees/' + fee.id;
  document.getElementById('edit_fee_name').value = fee.name;
  document.getElementById('edit_amount').value = fee.amount;
  document.getElementById('edit_due_date').value = fee.due_date ?? '';

  // Render class blocks
  fee.classes.forEach((cls, index) => {
    const block = document.createElement('div');
    block.classList.add('class-student-block', 'border', 'rounded', 'p-3', 'mb-3');

    block.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="flex-grow-1 me-2">
          <label class="form-label">Select Class</label>
          <select class="form-select class-select" name="assignments[${index}][class_id]" onchange="loadStudents(this, true)">
            <option value="">-- Choose Class --</option>
            ${fee.all_classes.map(c => `
              <option value="${c.id}" ${c.id == cls.id ? 'selected' : ''}>${c.name}</option>
            `).join('')}
          </select>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeClassBlock(this)">
          <i class="bx bx-x"></i>
        </button>
      </div>

      <div class="student-select-container">
        <label class="form-label">Select Students</label>
        <input type="text" class="form-control form-control-sm mb-2 student-search" placeholder="Search student..." onkeyup="filterStudents(this)">
        <div class="students-list text-muted small"><i class="bx bx-loader bx-spin"></i> Loading...</div>
      </div>
    `;

    container.appendChild(block);

    // Load students for this class and mark pre-selected
    setTimeout(() => {
      fetch(`/accountant/classes/${cls.id}/students`)
        .then(res => res.json())
        .then(students => {
          const listContainer = block.querySelector('.students-list');
          listContainer.innerHTML = `
            <div class="form-check mb-1">
              <input class="form-check-input select-all" type="checkbox" onchange="toggleSelectAll(this)">
              <label class="form-check-label fw-bold">Select All</label>
            </div>
            ${students.map(s => `
              <div class="form-check ms-3">
                <input class="form-check-input student-checkbox"
                       type="checkbox"
                       name="assignments[${index}][student_ids][]"
                       value="${s.id}"
                       ${cls.assigned_students.includes(s.id) ? 'checked' : ''}>
                <label class="form-check-label">${s.full_name}</label>
              </div>
            `).join('')}
          `;
        });
    }, 100);
  });

  // Show modal
  new bootstrap.Modal(document.getElementById('editFeeModal')).show();
}



function loadStudentsForFee() {
    if (!currentFeeId) return;

    const classId = document.getElementById('viewClassSelect').value;
    const search = document.getElementById('studentSearchInput').value.trim();

    if (!classId && search.length < 2) {
        document.getElementById('studentsTableBody').innerHTML = 
            '<tr><td colspan="7" class="text-center">Select a class or type at least 2 chars for student name.</td></tr>';
        return;
    }

    const tbody = document.getElementById('studentsTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><i class="bx bx-loader bx-spin"></i> Loading...</td></tr>';

    fetch(`/accountant/fees/${currentFeeId}/students?class_id=${classId}&search=${encodeURIComponent(search)}`)
        .then(res => res.json())
        .then(data => {
            const students = data.students || [];
            const paymentMethods = data.payment_methods || [];

            // ✅ Populate payment method dropdown
            const paymentSelect = document.getElementById('payment_method');
            if (paymentSelect) {
                if (paymentMethods.length) {
                    paymentSelect.innerHTML = '<option value="">-- Select Method --</option>' +
                        paymentMethods.map(pm => `<option value="${pm.name}">${pm.name}</option>`).join('');
                } else {
                    paymentSelect.innerHTML = '<option value="">No payment methods available</option>';
                }
            }

            // ✅ Populate students table
            if (students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No students found.</td></tr>';
                return;
            }

            const userRole = "{{ session('role') }}";

            tbody.innerHTML = students.map((s, idx) => {
                const required = parseFloat(s.required) || 0;
                const paid = parseFloat(s.paid) || 0;
                const balance = parseFloat(s.balance) || 0;

                return `
<tr>
  <td>${idx + 1}</td>
  <td>${s.name}</td>
  <td>Tsh ${paid.toFixed(2)}</td>
  <td>Tsh ${required.toFixed(2)}</td>
  <td class="${balance > 0 ? 'text-danger fw-bold' : 'text-success'}">Tsh ${balance.toFixed(2)}</td>
  <td>${balance > 0 ? 'Pending' : 'Paid'}</td>
  <td>
    <div class="d-flex gap-1">
      <button class="btn btn-sm btn-outline-info" onclick="viewHistory(${s.id})" title="View History">
        <i class="bx bx-history"></i>
      </button>

      ${userRole === 'Accountant' ? `
        <button class="btn btn-sm btn-outline-primary"
                onclick="recordPayment(${s.id}, ${required})"
                title="Record Payment">
            <i class="bx bx-credit-card"></i>
        </button>
      ` : ''}

      <button class="btn btn-sm btn-outline-secondary" onclick="generateStudentInvoice(${s.id})" title="Generate Invoice">
        <i class="bx bx-receipt"></i>
      </button>
    </div>
  </td>
</tr>`;
            }).join('');
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load students.</td></tr>';
        });
}




// Helper function to format numbers with commas
function formatNumberWithCommas(number) {
    // Split the number into integer and decimal parts
    const parts = number.toString().split('.');
    const integerPart = parts[0];
    const decimalPart = parts[1] || '';
    
    // Add commas to the integer part
    const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    // Combine with decimal part
    return decimalPart ? `${formattedInteger}.${decimalPart}` : formattedInteger;
}

function viewHistory(studentId) {
    // Show loading state
    const paymentHistoryBody = document.getElementById('paymentHistoryBody');
    if(paymentHistoryBody) {
        paymentHistoryBody.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading payment history...</p>
            </div>
        `;
    }
    
    // Show modal immediately with loading state
    const historyModal = new bootstrap.Modal(document.getElementById('paymentHistoryModal'));
    historyModal.show();

    fetch(`/accountant/fees/${currentFeeId}/student/${studentId}/history`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            if(paymentHistoryBody) {
                paymentHistoryBody.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Error loading payment history:', error);
            if(paymentHistoryBody) {
                paymentHistoryBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bx bx-error"></i>
                        Failed to load payment history. Please try again.
                    </div>
                `;
            }
        });
}

function recordPayment(studentId, feeAmount = 0) {
    const studentIdInput = document.getElementById('payment_student_id');
    const feeIdInput = document.getElementById('payment_fee_id');
    const paymentForm = document.getElementById('addPaymentForm');
    const amountInput = document.getElementById('payment_amount');
    const formattedInput = document.getElementById('formatted_payment_amount');
    const remainingInput = document.getElementById('remaining_payment_balance');
    const feeTotalInput = document.getElementById('fee_total_amount');

    if(studentIdInput) studentIdInput.value = studentId;
    if(feeIdInput) feeIdInput.value = currentFeeId;

    // Store total fee amount
    feeTotalInput.value = feeAmount;

    if(paymentForm) {
        paymentForm.action = `/accountant/fees/${currentFeeId}/student/${studentId}/pay`;

        // Reset form fields
        if(amountInput) amountInput.value = '';
        if(formattedInput) formattedInput.value = '';
        if(remainingInput) remainingInput.value = feeAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const methodSelect = document.getElementById('payment_method');
        const dateInput = document.getElementById('payment_date');
        const transactionInput = document.getElementById('transaction_id');
        const receiptInput = document.getElementById('receipt');
        if(methodSelect) methodSelect.selectedIndex = 0;
        if(dateInput) dateInput.value = '{{ date('Y-m-d') }}';
        if(transactionInput) transactionInput.value = '';
        if(receiptInput) receiptInput.value = '';
    }

    // Update formatted and remaining dynamically
    amountInput.addEventListener('input', function() {
        const val = parseFloat(this.value) || 0;
        formattedInput.value = val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        let remaining = feeAmount - val;
        remaining = remaining < 0 ? 0 : remaining;
        remainingInput.value = remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });

    const paymentModal = new bootstrap.Modal(document.getElementById('addPaymentModal'));
    paymentModal.show();
}


function generateStudentInvoice(studentId) {
    // Generate invoice for specific student with current fee
    const url = `/accountant/students/${studentId}/invoice?fee_id=${currentFeeId}&format=pdf`;
    window.open(url, '_blank');
}

function generateInvoices(feeId) {
    // Open bulk invoice generation modal
    window.location.href = `/accountant/invoices/bulk-generate/${feeId}`;
    // document.getElementById('invoice_fee_id').value = feeId;
    // const invoiceModal = new bootstrap.Modal(document.getElementById('generateInvoicesModal'));
    // invoiceModal.show();
}

function populateEditFee(id, name, classId, amount, dueDate, isActive) {
    const form = document.getElementById('editFeeForm');
    if(form) {
        form.action = `/accountant/fees/${id}`;
        
        const nameInput = document.getElementById('edit_fee_name');
        const classSelect = document.getElementById('edit_class_id');
        const amountInput = document.getElementById('edit_amount');
        const dateInput = document.getElementById('edit_due_date');
        const activeInput = document.getElementById('edit_is_active');
        
        if(nameInput) nameInput.value = name;
        if(classSelect) classSelect.value = classId;
        if(amountInput) amountInput.value = amount;
        if(dateInput) dateInput.value = dueDate;
        if(activeInput) activeInput.checked = isActive == 1;
    }
}

function exportFees(format) {
    window.location.href = `/accountant/fees/export/${format}`;
}

function generateReport() {
    const form = document.getElementById('reportForm');
    const reportType = document.getElementById('report_type').value;
    const format = document.getElementById('format').value;

    if (!reportType) {
        alert('Please select a report type');
        return;
    }

    if (!format) {
        alert('Please select an export format');
        return;
    }

    // Show loading state
    const button = event.target;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bx bx-loader bx-spin"></i> Generating...';
    button.disabled = true;

    // Submit form
    form.submit();

    // Reset button after 5 seconds
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.disabled = false;
    }, 5000);
}


function sendReminders() {
    document.getElementById('reminderForm').submit();
}

function processInvoices() {
    document.getElementById('invoiceForm').submit();
}

function downloadReceipt(paymentId, button) {
    // Show loading state
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bx bx-loader bx-spin"></i>';
    button.disabled = true;

    // Create download link
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

function downloadPendingPayment(paymentId) {
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

function filterPendingPayments() {
  const feeId = document.getElementById('verify_fee_filter').value;
  const classId = document.getElementById('verify_class_filter').value;

  fetch(`/accountant/fees/verify/filter?fee_id=${feeId}&class_id=${classId}`)
    .then(res => res.text())
    .then(html => {
      document.getElementById('verifyPaymentsBody').innerHTML = html;
    });
}

function markAsVerified(id) {
  console.log('the id:',id);
  
  if (confirm('Mark this student as verified?')) {
    fetch(`/accountant/fees/verify/${id}`, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
    }).then(() => location.reload());
  }
}


// 🔹 Edit Fee Modal JavaScript
let editClassIndex = 1;

// 🔹 Add another class block in EDIT modal
document.getElementById('addEditClassBtn').addEventListener('click', () => {
  const container = document.getElementById('edit-class-student-container');
  const originalBlock = container.querySelector('.class-student-block');
  const newBlock = originalBlock.cloneNode(true);

  // ✅ Reset class dropdown
  const classSelect = newBlock.querySelector('.class-select');
  classSelect.name = `assignments[${editClassIndex}][class_id]`;
  classSelect.value = '';
  classSelect.onchange = function() { loadEditStudents(this); };

  // ✅ Reset student list and search
  const studentSearch = newBlock.querySelector('.student-search');
  studentSearch.value = '';
  studentSearch.onkeyup = function() { filterEditStudents(this); };
  
  const studentsList = newBlock.querySelector('.students-list');
  studentsList.innerHTML = 'Select a class first...';

  // ✅ Remove any previously selected checkboxes
  const oldCheckboxes = newBlock.querySelectorAll('.student-checkbox');
  oldCheckboxes.forEach(cb => cb.remove());

  container.appendChild(newBlock);
  editClassIndex++;
});

// 🔹 Remove class block in EDIT modal
function removeEditClassBlock(button) {
  const container = document.getElementById('edit-class-student-container');
  if (container.querySelectorAll('.class-student-block').length > 1) {
    button.closest('.class-student-block').remove();
    // Re-index the remaining blocks
    editClassIndex = 0;
    container.querySelectorAll('.class-student-block').forEach((block, index) => {
      const classSelect = block.querySelector('.class-select');
      classSelect.name = `assignments[${index}][class_id]`;
      editClassIndex = index + 1;
    });
  }
}

// 🔹 Load students for EDIT modal
function loadEditStudents(select) {
  const classId = select.value;
  const block = select.closest('.class-student-block');
  const listContainer = block.querySelector('.students-list');
  const searchInput = block.querySelector('.student-search');

  if (!classId) {
    listContainer.innerHTML = 'Select a class first...';
    searchInput.value = '';
    return;
  }

  listContainer.innerHTML = '<i class="bx bx-loader bx-spin"></i> Loading...';
  searchInput.value = '';

  fetch(`/accountant/classes/${classId}/students`)
    .then(res => res.json())
    .then(students => {
      if (!students.length) {
        listContainer.innerHTML = '<span class="text-muted">No students found.</span>';
        return;
      }

      const index = select.name.match(/\d+/)[0];
      const html = `
        <div class="form-check mb-1">
          <input class="form-check-input select-all" type="checkbox" onchange="toggleEditSelectAll(this)">
          <label class="form-check-label fw-bold">Select All</label>
        </div>
        ${students.map(s => `
          <div class="form-check ms-3">
            <input class="form-check-input student-checkbox"
                   type="checkbox"
                   name="assignments[${index}][student_ids][]"
                   value="${s.id}">
            <label class="form-check-label">${s.full_name}</label>
          </div>
        `).join('')}
      `;
      listContainer.innerHTML = html;
    })
    .catch(() => listContainer.innerHTML = '<span class="text-danger">Failed to load students.</span>');
}

// 🔹 "Select All" for EDIT modal
function toggleEditSelectAll(checkbox) {
  const container = checkbox.closest('.students-list');
  const checkboxes = container.querySelectorAll('.student-checkbox');
  checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

// 🔹 Filter students for EDIT modal
function filterEditStudents(input) {
  const query = input.value.toLowerCase();
  const block = input.closest('.class-student-block');
  const students = block.querySelectorAll('.student-checkbox + label');

  students.forEach(label => {
    const text = label.textContent.toLowerCase();
    const parent = label.closest('.form-check');
    if (parent.querySelector('.select-all')) return;
    parent.style.display = text.includes(query) ? '' : 'none';
  });
}

// 🔹 Populate edit form with existing data
function populateEditFee(id, name, amount, dueDate, isActive) {
  const form = document.getElementById('editFeeForm');
  if(form) {
    form.action = `fees/${id}`;
    
    const nameInput = document.getElementById('edit_fee_name');
    const amountInput = document.getElementById('edit_amount');
    const dateInput = document.getElementById('edit_due_date');
    
    if(nameInput) nameInput.value = name;
    if(amountInput) amountInput.value = amount;
    if(dateInput) dateInput.value = dueDate;
    
    // Reset the class container
    const container = document.getElementById('edit-class-student-container');
    container.innerHTML = container.querySelector('.class-student-block').outerHTML;
    editClassIndex = 1;
  }
}

function openEditFee(id, name, amount, dueDate, isActive) {
  populateEditFee(id, name, amount, dueDate, isActive);
  // You might want to load existing class assignments here via AJAX
}
</script>
<style>
  .student-row:hover {
    background-color: #f8f9fa !important;
}

[data-theme="dark"] .student-row:hover {
    background-color: var(--neutral-200) !important;
}

.student-avatar {
    transition: all 0.2s ease;
}

.student-row:hover .student-avatar {
    background-color: #e3f2fd !important;
    transform: scale(1.05);
}

[data-theme="dark"] .student-row:hover .student-avatar {
    background-color: rgba(124, 58, 237, 0.2) !important;
}

.btn-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    transform: translateY(-1px);
}

.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
}
  </style>
@endsection