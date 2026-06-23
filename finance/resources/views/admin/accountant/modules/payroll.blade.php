@extends('layouts.accountant')

@section('title', 'Payroll — Darasa Finance')
@section('page_title', 'Payroll')

@section('content')
<div class="w-full p-6">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-violet-600">👥 Payroll Management</h2>
        <div class="flex gap-3">
            <a href="/api/staff/csv/template" download class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition text-sm">
                📥 CSV Template
            </a>
            <button onclick="showUploadCsvModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition text-sm">
                📤 Upload CSV
            </button>
            <button onclick="showAddStaffModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition text-sm">
                ➕ Add Staff
            </button>
            <button onclick="showDeductionTypesModal()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded transition text-sm">
                ✂️ Deduction Types
            </button>
            <button onclick="showProcessPayrollModal()" class="bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded transition text-sm">
                💰 Process Payroll
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-300 mb-6 gap-1">
        <button onclick="switchTab('staff')" id="tab-staff"
            class="tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-violet-600 text-white border-violet-600">
            👥 Staff
        </button>
        <button onclick="switchTab('payroll')" id="tab-payroll"
            class="tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-violet-50">
            💰 Payroll Entries
        </button>
        <button onclick="switchTab('deductions')" id="tab-deductions"
            class="tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-violet-50">
            ✂️ Deductions Ledger
        </button>
    </div>

    <!-- Staff Tab -->
    <div id="panel-staff">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-bold text-gray-700">Staff Members</h3>
            <input type="text" id="staffSearch" placeholder="Search staff..." onkeyup="filterStaff()"
                class="border-2 border-gray-300 rounded-lg px-4 py-2 w-64 text-sm">
        </div>
        <div id="staffGrid" class="overflow-x-auto">
            <p class="text-gray-400 text-center py-6">Loading…</p>
        </div>
    </div>

    <!-- Payroll Entries Tab -->
    <div id="panel-payroll" class="hidden">
        <!-- Summary cards -->
        <div class="grid grid-cols-3 gap-4 mb-5">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                <div class="text-xs text-blue-500 font-semibold uppercase">Total Gross</div>
                <div class="text-2xl font-bold text-blue-700 mt-1" id="summary-gross">—</div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                <div class="text-xs text-red-500 font-semibold uppercase">Total Deductions</div>
                <div class="text-2xl font-bold text-red-700 mt-1" id="summary-deductions">—</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                <div class="text-xs text-green-500 font-semibold uppercase">Total Net Pay</div>
                <div class="text-2xl font-bold text-green-700 mt-1" id="summary-net">—</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex gap-3 mb-4">
            <select id="filter-month" class="border border-gray-300 rounded px-3 py-2 text-sm" onchange="loadPayrollEntries()">
                <option value="">All Months</option>
                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                    <option value="{{ $i + 1 }}" {{ now()->month == ($i+1) ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
            <input type="number" id="filter-year" value="{{ now()->year }}" min="2020" max="2099"
                class="border border-gray-300 rounded px-3 py-2 text-sm w-24" onchange="loadPayrollEntries()">
            <button onclick="loadPayrollEntries()" class="bg-violet-500 text-white px-4 py-2 rounded text-sm">Filter</button>
        </div>

        <div id="payrollTable" class="overflow-x-auto">
            <p class="text-gray-400 text-center py-6">Loading…</p>
        </div>
    </div>

    <!-- Deductions Ledger Tab -->
    <div id="panel-deductions" class="hidden">
        <div class="flex gap-3 mb-4">
            <select id="dl-month" class="border border-gray-300 rounded px-3 py-2 text-sm" onchange="loadDeductionsLedger()">
                <option value="">All Months</option>
                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                    <option value="{{ $i + 1 }}" {{ now()->month == ($i+1) ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
            <input type="number" id="dl-year" value="{{ now()->year }}" min="2020" max="2099"
                class="border border-gray-300 rounded px-3 py-2 text-sm w-24" onchange="loadDeductionsLedger()">
            <button onclick="loadDeductionsLedger()" class="bg-orange-500 text-white px-4 py-2 rounded text-sm">Filter</button>
        </div>

        <!-- Summary by type -->
        <div id="deductions-summary" class="mb-5"></div>

        <!-- Per-entry deductions -->
        <div id="deductions-table" class="overflow-x-auto">
            <p class="text-gray-400 text-center py-6">Loading…</p>
        </div>
    </div>
</div>

{{-- ──────────────────────────────────────────
     ADD STAFF MODAL
────────────────────────────────────────── --}}
<div id="addStaffModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold text-blue-600 mb-4">➕ Add Staff Member</h3>
        <form id="addStaffForm" onsubmit="submitAddStaffForm(event)">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-1 text-sm">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="staff_name" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Staff ID <span class="text-red-500">*</span></label>
                    <input type="text" id="staff_id" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Position <span class="text-red-500">*</span></label>
                    <input type="text" id="staff_position" required placeholder="e.g., Teacher, Accountant" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Department</label>
                    <input type="text" id="staff_department" placeholder="e.g., Finance, Academic" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Monthly Salary (TSh) <span class="text-red-500">*</span></label>
                    <input type="number" id="staff_salary" required step="0.01" min="0" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Date Joined</label>
                    <input type="date" id="staff_date_joined" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Phone</label>
                    <input type="tel" id="staff_phone" placeholder="+255…" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Email</label>
                    <input type="email" id="staff_email" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Bank Name</label>
                    <input type="text" id="staff_bank_name" placeholder="e.g., CRDB, NMB" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Bank Account Number</label>
                    <input type="text" id="staff_bank_account" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>
            <div class="mt-4">
                <label class="block font-bold mb-1 text-sm">Notes</label>
                <textarea id="staff_notes" rows="2" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2"></textarea>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition">✅ Add Staff</button>
                <button type="button" onclick="closeAddStaffModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold transition">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- ──────────────────────────────────────────
     PROCESS PAYROLL MODAL (with deductions)
────────────────────────────────────────── --}}
<div id="processPayrollModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold text-violet-600 mb-4">💰 Process Payroll</h3>
        <form id="processPayrollForm" onsubmit="submitProcessPayrollForm(event)">

            <!-- Staff search -->
            <div class="mb-3">
                <label class="block font-bold mb-1 text-sm">Search Staff</label>
                <input type="text" id="payroll_staff_search" placeholder="Search by name or ID…"
                    onkeyup="filterPayrollStaffList()" class="w-full border-2 border-blue-300 rounded-lg px-4 py-2 focus:border-blue-500 focus:outline-none text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold mb-1 text-sm">Staff Member <span class="text-red-500">*</span></label>
                    <select id="payroll_staff_id" required onchange="onStaffSelected()" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        <option value="">Select Staff…</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Gross Salary (TSh) <span class="text-red-500">*</span></label>
                    <input type="number" id="payroll_gross" required step="0.01" min="0"
                        oninput="recalcNet()" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Month <span class="text-red-500">*</span></label>
                    <select id="payroll_month" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        <option value="">Select Month…</option>
                        @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                            <option value="{{ $i + 1 }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Year <span class="text-red-500">*</span></label>
                    <input type="number" id="payroll_year" required min="2020" max="2099" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Payment Date <span class="text-red-500">*</span></label>
                    <input type="date" id="payroll_payment_date" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Book <span class="text-red-500">*</span></label>
                    <select id="payroll_book_id" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        <option value="">Select Book…</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Payment Method <span class="text-red-500">*</span></label>
                    <select id="payroll_payment_method" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-1 text-sm">Reference Number</label>
                    <input type="text" id="payroll_reference" placeholder="Transaction ref" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                </div>
            </div>

            <!-- Deductions Section -->
            <div class="mt-5 border-2 border-orange-200 rounded-lg p-4 bg-orange-50">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-bold text-orange-700">✂️ Deductions (optional)</h4>
                    <div class="flex gap-2">
                        <select id="quick-deduction-type" class="border border-orange-300 rounded px-2 py-1 text-xs">
                            <option value="">— Add from template —</option>
                        </select>
                        <button type="button" onclick="addDeductionFromTemplate()" class="bg-orange-400 hover:bg-orange-500 text-white px-3 py-1 rounded text-xs">+ Add</button>
                        <button type="button" onclick="addDeductionRow()" class="bg-gray-400 hover:bg-gray-500 text-white px-3 py-1 rounded text-xs">+ Custom</button>
                    </div>
                </div>
                <div id="deductionRows" class="space-y-2"></div>
                <div class="mt-3 text-right text-sm font-bold text-orange-700" id="deductionTotals"></div>
            </div>

            <!-- Net pay preview -->
            <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4 flex justify-between items-center">
                <span class="font-bold text-green-700">Net Take-Home Pay:</span>
                <span class="text-2xl font-bold text-green-700" id="net-preview">TSh 0</span>
            </div>

            <div class="mt-4">
                <label class="block font-bold mb-1 text-sm">Notes</label>
                <textarea id="payroll_notes" rows="2" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2"></textarea>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition">✅ Process Payroll</button>
                <button type="button" onclick="closeProcessPayrollModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold transition">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- ──────────────────────────────────────────
     DEDUCTION TYPES MODAL
────────────────────────────────────────── --}}
<div id="deductionTypesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-2xl font-bold text-orange-600">✂️ Deduction Types</h3>
            <button onclick="closeDeductionTypesModal()" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">×</button>
        </div>

        <!-- Add new type form -->
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
            <h4 class="font-bold text-orange-700 mb-3">Add New Deduction Type</h4>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" id="dt_name" placeholder="e.g., NSSF, Income Tax, Penalty" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Type <span class="text-red-500">*</span></label>
                    <select id="dt_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" onchange="onDtTypeChange()">
                        <option value="fixed">Fixed Amount</option>
                        <option value="percentage">Percentage (%)</option>
                        <option value="insurance">Insurance</option>
                        <option value="penalty">Penalty</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Default Value <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" id="dt_default" step="0.01" min="0" placeholder="0"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <span id="dt_unit" class="absolute right-3 top-2 text-gray-400 text-sm">TSh</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Notes</label>
                    <input type="text" id="dt_notes" placeholder="Optional description" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
            <button onclick="submitDeductionType()" class="mt-3 bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded font-bold transition text-sm">
                ➕ Add Deduction Type
            </button>
        </div>

        <!-- Existing types -->
        <div id="deductionTypesList">
            <p class="text-gray-400 text-center py-4">Loading…</p>
        </div>
    </div>
</div>

{{-- ──────────────────────────────────────────
     UPLOAD CSV MODAL
────────────────────────────────────────── --}}
<div id="uploadCsvModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-2xl font-bold text-blue-600 mb-4">📤 Upload Staff CSV</h3>
        <form id="uploadCsvForm" onsubmit="submitUploadCsvForm(event)">
            <div class="mb-4">
                <label class="block font-bold mb-2 text-sm">Select CSV File <span class="text-red-500">*</span></label>
                <input type="file" id="csv_file" accept=".csv" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-500 mt-2">Columns: staff_id, name, position, department, monthly_salary, phone, email, bank_name, bank_account, date_joined</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition">✅ Upload</button>
                <button type="button" onclick="closeUploadCsvModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold transition">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const API_BASE = '/api';

axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.withCredentials = true;

// ─── Utilities ───────────────────────────────────────────────────────────────

function fmt(amount) {
    return 'TSh ' + parseFloat(amount || 0).toLocaleString('en-TZ', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

const MONTHS = ['','January','February','March','April','May','June',
                'July','August','September','October','November','December'];

// ─── Tabs ────────────────────────────────────────────────────────────────────

function switchTab(name) {
    ['staff','payroll','deductions'].forEach(t => {
        document.getElementById('panel-' + t).classList.add('hidden');
        const btn = document.getElementById('tab-' + t);
        btn.classList.remove('bg-violet-600','text-white','border-violet-600');
        btn.classList.add('bg-white','text-gray-600','border-gray-300');
    });
    document.getElementById('panel-' + name).classList.remove('hidden');
    const active = document.getElementById('tab-' + name);
    active.classList.add('bg-violet-600','text-white','border-violet-600');
    active.classList.remove('bg-white','text-gray-600','border-gray-300');

    if (name === 'payroll') loadPayrollEntries();
    if (name === 'deductions') loadDeductionsLedger();
}

// ─── Page init ───────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    loadStaff();
    // Set default month/year for payroll modal
    const now = new Date();
    document.getElementById('payroll_month').value = now.getMonth() + 1;
    document.getElementById('payroll_year').value  = now.getFullYear();
    document.getElementById('payroll_payment_date').valueAsDate = now;
});

// ─── Staff ───────────────────────────────────────────────────────────────────

let allStaff = [];

async function loadStaff() {
    try {
        const r = await axios.get(`${API_BASE}/staff`);
        allStaff = r.data.staff?.data || r.data.staff || [];
        renderStaffTable(allStaff);
    } catch (e) {
        document.getElementById('staffGrid').innerHTML = '<p class="text-red-500 text-center py-4">Error loading staff.</p>';
    }
}

function renderStaffTable(staff) {
    if (staff.length === 0) {
        document.getElementById('staffGrid').innerHTML = '<p class="text-gray-400 text-center py-6">No staff members. Click "Add Staff" to add one.</p>';
        return;
    }
    let html = `
        <table class="w-full border border-gray-200 rounded-lg text-sm" id="staffTable">
            <thead class="bg-violet-50 text-violet-700">
                <tr>
                    <th class="p-3 text-left">Staff ID</th>
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3 text-left">Position</th>
                    <th class="p-3 text-left">Department</th>
                    <th class="p-3 text-right">Monthly Salary</th>
                    <th class="p-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
    `;
    staff.forEach(m => {
        const badge = m.status === 'active'
            ? '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-bold">Active</span>'
            : '<span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs font-bold">Inactive</span>';
        html += `
            <tr class="border-t hover:bg-violet-50">
                <td class="p-3 font-mono text-xs">${m.staff_id}</td>
                <td class="p-3 font-semibold">${m.name}</td>
                <td class="p-3">${m.position}</td>
                <td class="p-3 text-gray-500">${m.department || '—'}</td>
                <td class="p-3 text-right font-bold text-violet-700">${fmt(m.monthly_salary)}</td>
                <td class="p-3 text-center">${badge}</td>
            </tr>`;
    });
    html += '</tbody></table>';
    document.getElementById('staffGrid').innerHTML = html;
}

function filterStaff() {
    const q = document.getElementById('staffSearch').value.toLowerCase();
    const filtered = allStaff.filter(m =>
        m.name.toLowerCase().includes(q) ||
        m.staff_id.toLowerCase().includes(q) ||
        (m.position || '').toLowerCase().includes(q)
    );
    renderStaffTable(filtered);
}

function showAddStaffModal() {
    document.getElementById('addStaffModal').classList.remove('hidden');
}
function closeAddStaffModal() {
    document.getElementById('addStaffModal').classList.add('hidden');
    document.getElementById('addStaffForm').reset();
}

async function submitAddStaffForm(event) {
    event.preventDefault();
    const btn = event.submitter;
    btn.disabled = true; btn.textContent = '⏳ Saving…';
    try {
        await axios.post(`${API_BASE}/staff`, {
            name:           document.getElementById('staff_name').value,
            staff_id:       document.getElementById('staff_id').value,
            position:       document.getElementById('staff_position').value,
            department:     document.getElementById('staff_department').value,
            monthly_salary: parseFloat(document.getElementById('staff_salary').value),
            date_joined:    document.getElementById('staff_date_joined').value || null,
            phone:          document.getElementById('staff_phone').value || null,
            email:          document.getElementById('staff_email').value || null,
            bank_name:      document.getElementById('staff_bank_name').value || null,
            bank_account:   document.getElementById('staff_bank_account').value || null,
            notes:          document.getElementById('staff_notes').value || null,
        });
        closeAddStaffModal();
        loadStaff();
    } catch (e) {
        const msg = e.response?.data?.errors
            ? Object.values(e.response.data.errors).flat().join('\n')
            : (e.response?.data?.message || e.message);
        alert('❌ ' + msg);
    } finally {
        btn.disabled = false; btn.textContent = '✅ Add Staff';
    }
}

// ─── Payroll Entries ──────────────────────────────────────────────────────────

async function loadPayrollEntries() {
    document.getElementById('payrollTable').innerHTML = '<p class="text-gray-400 text-center py-6">Loading…</p>';
    const month = document.getElementById('filter-month').value;
    const year  = document.getElementById('filter-year').value;
    try {
        const r = await axios.get(`${API_BASE}/payroll`, { params: { month, year } });
        const { payrolls, total_gross, total_deductions, total_net } = r.data;

        document.getElementById('summary-gross').textContent      = fmt(total_gross);
        document.getElementById('summary-deductions').textContent = fmt(total_deductions);
        document.getElementById('summary-net').textContent        = fmt(total_net);

        if (!payrolls || payrolls.length === 0) {
            document.getElementById('payrollTable').innerHTML = '<p class="text-gray-400 text-center py-6">No payroll entries for this period.</p>';
            return;
        }

        let html = `
            <table class="w-full border border-gray-200 rounded-lg text-sm">
                <thead class="bg-violet-50 text-violet-700">
                    <tr>
                        <th class="p-3 text-left">Period</th>
                        <th class="p-3 text-left">Staff</th>
                        <th class="p-3 text-right">Gross</th>
                        <th class="p-3 text-right">Deductions</th>
                        <th class="p-3 text-right">Net Pay</th>
                        <th class="p-3 text-left">Method</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
        `;

        payrolls.forEach(e => {
            const badge = e.status === 'paid'
                ? '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-bold">Paid</span>'
                : '<span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs font-bold">Pending</span>';
            const dedsCount = e.deductions?.length || 0;
            html += `
                <tr class="border-t hover:bg-violet-50">
                    <td class="p-3">${MONTHS[e.month] || e.month} ${e.year}</td>
                    <td class="p-3 font-semibold">${e.staff?.name || '—'}</td>
                    <td class="p-3 text-right text-blue-700 font-bold">${fmt(e.gross_salary)}</td>
                    <td class="p-3 text-right text-red-600">${fmt(e.total_deductions)}</td>
                    <td class="p-3 text-right text-green-700 font-bold">${fmt(e.net_salary)}</td>
                    <td class="p-3 text-gray-500 capitalize">${(e.payment_method || '').replace('_',' ')}</td>
                    <td class="p-3 text-center">${badge}</td>
                    <td class="p-3 text-center">
                        <button onclick="showPayrollDetail(${e.id})"
                            class="text-violet-600 hover:underline text-xs">
                            ${dedsCount > 0 ? dedsCount + ' cut(s)' : 'View'}
                        </button>
                    </td>
                </tr>`;
        });

        html += '</tbody></table>';
        document.getElementById('payrollTable').innerHTML = html;
    } catch (e) {
        document.getElementById('payrollTable').innerHTML = '<p class="text-red-500 text-center py-4">Error loading payroll.</p>';
    }
}

async function showPayrollDetail(id) {
    try {
        const r = await axios.get(`${API_BASE}/payroll/${id}`);
        const e = r.data;
        let msg = `Staff: ${e.staff?.name}\nPeriod: ${MONTHS[e.month]} ${e.year}\nGross: ${fmt(e.gross_salary)}\n`;
        if (e.deductions && e.deductions.length > 0) {
            msg += '\nDeductions:\n';
            e.deductions.forEach(d => {
                msg += `  • ${d.name} (${d.type}): ${fmt(d.amount)}`;
                if (d.note) msg += ` — ${d.note}`;
                msg += '\n';
            });
            msg += `\nTotal Deductions: ${fmt(e.total_deductions)}`;
        }
        msg += `\nNet Pay: ${fmt(e.net_salary)}`;
        alert(msg);
    } catch (err) {
        alert('Could not load details.');
    }
}

// ─── Process Payroll Modal ────────────────────────────────────────────────────

let allBooks = [];
let allDeductionTypes = [];
let deductionRowCount = 0;

async function showProcessPayrollModal() {
    try {
        const [staffR, booksR, typesR] = await Promise.all([
            axios.get(`${API_BASE}/staff`),
            axios.get(`${API_BASE}/books`),
            axios.get(`${API_BASE}/payroll/deduction-types`),
        ]);
        allStaff          = staffR.data.staff?.data || staffR.data.staff || [];
        allBooks          = booksR.data || [];
        allDeductionTypes = typesR.data || [];
    } catch (e) {
        console.error('Error loading modal data:', e);
    }

    // Populate staff
    const staffSel = document.getElementById('payroll_staff_id');
    staffSel.innerHTML = '<option value="">Select Staff…</option>';
    allStaff.forEach(s => {
        staffSel.innerHTML += `<option value="${s.id}" data-salary="${s.monthly_salary}">${s.name} (${s.staff_id})</option>`;
    });

    // Populate books
    const bookSel = document.getElementById('payroll_book_id');
    bookSel.innerHTML = '<option value="">Select Book…</option>';
    allBooks.forEach(b => {
        bookSel.innerHTML += `<option value="${b.id}">${b.name}</option>`;
    });

    // Populate quick-deduction template
    const qSel = document.getElementById('quick-deduction-type');
    qSel.innerHTML = '<option value="">— Add from template —</option>';
    allDeductionTypes.forEach(t => {
        qSel.innerHTML += `<option value="${t.id}" data-name="${t.name}" data-type="${t.type}" data-value="${t.default_value}" data-pct="${t.is_percentage ? 1 : 0}">${t.name} (${t.is_percentage ? t.default_value + '%' : fmt(t.default_value)})</option>`;
    });

    // Defaults
    const now = new Date();
    document.getElementById('payroll_month').value = now.getMonth() + 1;
    document.getElementById('payroll_year').value  = now.getFullYear();
    document.getElementById('payroll_payment_date').valueAsDate = now;

    // Reset deductions
    document.getElementById('deductionRows').innerHTML = '';
    deductionRowCount = 0;
    recalcNet();

    document.getElementById('processPayrollModal').classList.remove('hidden');
}

function closeProcessPayrollModal() {
    document.getElementById('processPayrollModal').classList.add('hidden');
    document.getElementById('processPayrollForm').reset();
    document.getElementById('deductionRows').innerHTML = '';
    deductionRowCount = 0;
    recalcNet();
}

function filterPayrollStaffList() {
    const q   = document.getElementById('payroll_staff_search').value.toLowerCase();
    const sel = document.getElementById('payroll_staff_id');
    Array.from(sel.options).forEach(opt => {
        opt.style.display = (opt.value === '' || opt.text.toLowerCase().includes(q)) ? '' : 'none';
    });
}

function onStaffSelected() {
    const sel  = document.getElementById('payroll_staff_id');
    const opt  = sel.options[sel.selectedIndex];
    const sal  = opt?.getAttribute('data-salary');
    if (sal) {
        document.getElementById('payroll_gross').value = parseFloat(sal);
        recalcNet();
    }
}

function addDeductionFromTemplate() {
    const sel = document.getElementById('quick-deduction-type');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const isPct = opt.getAttribute('data-pct') === '1';
    addDeductionRow({
        deduction_type_id: opt.value,
        name:  opt.getAttribute('data-name'),
        type:  opt.getAttribute('data-type'),
        amount: opt.getAttribute('data-value'),
        is_pct: isPct,
    });
    sel.value = '';
}

function addDeductionRow(defaults = {}) {
    const idx = deductionRowCount++;
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-end bg-white border border-orange-200 rounded p-2';
    row.id = `ded-row-${idx}`;

    const typeOptions = ['fixed','percentage','insurance','penalty','other']
        .map(t => `<option value="${t}" ${(defaults.type||'fixed')===t?'selected':''}>${t}</option>`)
        .join('');

    row.innerHTML = `
        <input type="hidden" name="deductions[${idx}][deduction_type_id]" value="${defaults.deduction_type_id || ''}">
        <div class="flex-1">
            <label class="block text-xs text-gray-500 mb-0.5">Name</label>
            <input type="text" name="deductions[${idx}][name]" value="${defaults.name || ''}" required
                placeholder="e.g., NSSF" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-0.5">Type</label>
            <select name="deductions[${idx}][type]" class="border border-gray-300 rounded px-2 py-1 text-sm" onchange="recalcNet()">
                ${typeOptions}
            </select>
        </div>
        <div class="w-32">
            <label class="block text-xs text-gray-500 mb-0.5">${defaults.is_pct ? '% of gross' : 'Amount (TSh)'}</label>
            <input type="number" name="deductions[${idx}][amount]" value="${defaults.amount || ''}" required
                step="0.01" min="0" oninput="recalcNet()" placeholder="0"
                class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <div class="flex-1">
            <label class="block text-xs text-gray-500 mb-0.5">Note</label>
            <input type="text" name="deductions[${idx}][note]" value=""
                placeholder="optional" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
        </div>
        <button type="button" onclick="removeDeductionRow('ded-row-${idx}')"
            class="text-red-400 hover:text-red-600 text-xl font-bold leading-none pb-1">×</button>
    `;

    document.getElementById('deductionRows').appendChild(row);
    recalcNet();
}

function removeDeductionRow(rowId) {
    document.getElementById(rowId)?.remove();
    recalcNet();
}

function recalcNet() {
    const gross = parseFloat(document.getElementById('payroll_gross').value) || 0;
    let totalDed = 0;

    document.querySelectorAll('#deductionRows > div').forEach(row => {
        const type   = row.querySelector('[name$="[type]"]')?.value || 'fixed';
        const amount = parseFloat(row.querySelector('[name$="[amount]"]')?.value) || 0;
        if (type === 'percentage') {
            totalDed += gross * (amount / 100);
        } else {
            totalDed += amount;
        }
    });

    const net = Math.max(0, gross - totalDed);
    document.getElementById('net-preview').textContent = fmt(net);
    document.getElementById('deductionTotals').textContent =
        totalDed > 0 ? `Total deductions: ${fmt(totalDed)}` : '';
}

async function submitProcessPayrollForm(event) {
    event.preventDefault();
    const btn = event.submitter;
    btn.disabled = true; btn.textContent = '⏳ Processing…';

    const deductions = [];
    document.querySelectorAll('#deductionRows > div').forEach(row => {
        deductions.push({
            deduction_type_id: row.querySelector('[name$="[deduction_type_id]"]')?.value || null,
            name:   row.querySelector('[name$="[name]"]')?.value,
            type:   row.querySelector('[name$="[type]"]')?.value,
            amount: parseFloat(row.querySelector('[name$="[amount]"]')?.value) || 0,
            note:   row.querySelector('[name$="[note]"]')?.value || null,
        });
    });

    const payload = {
        staff_id:         parseInt(document.getElementById('payroll_staff_id').value),
        book_id:          parseInt(document.getElementById('payroll_book_id').value),
        month:            parseInt(document.getElementById('payroll_month').value),
        year:             parseInt(document.getElementById('payroll_year').value),
        gross_salary:     parseFloat(document.getElementById('payroll_gross').value),
        payment_date:     document.getElementById('payroll_payment_date').value,
        payment_method:   document.getElementById('payroll_payment_method').value,
        reference_number: document.getElementById('payroll_reference').value || null,
        notes:            document.getElementById('payroll_notes').value || null,
        deductions,
    };

    try {
        const r = await axios.post(`${API_BASE}/payroll`, payload);
        alert('✅ ' + r.data.message);
        closeProcessPayrollModal();
        if (document.getElementById('panel-payroll').classList.contains('hidden')) {
            switchTab('payroll');
        } else {
            loadPayrollEntries();
        }
    } catch (e) {
        const msg = e.response?.data?.errors
            ? Object.values(e.response.data.errors).flat().join('\n')
            : (e.response?.data?.error || e.message);
        alert('❌ ' + msg);
    } finally {
        btn.disabled = false; btn.textContent = '✅ Process Payroll';
    }
}

// ─── Deduction Types Management ───────────────────────────────────────────────

async function showDeductionTypesModal() {
    document.getElementById('deductionTypesModal').classList.remove('hidden');
    loadDeductionTypesList();
}
function closeDeductionTypesModal() {
    document.getElementById('deductionTypesModal').classList.add('hidden');
}

function onDtTypeChange() {
    const t    = document.getElementById('dt_type').value;
    const unit = document.getElementById('dt_unit');
    unit.textContent = (t === 'percentage') ? '%' : 'TSh';
}

async function loadDeductionTypesList() {
    document.getElementById('deductionTypesList').innerHTML = '<p class="text-gray-400 text-center py-4">Loading…</p>';
    try {
        const r = await axios.get(`${API_BASE}/payroll/deduction-types`);
        const types = r.data;
        if (!types.length) {
            document.getElementById('deductionTypesList').innerHTML = '<p class="text-gray-400 text-center py-4">No deduction types yet.</p>';
            return;
        }
        let html = '<table class="w-full text-sm border border-gray-200 rounded"><thead class="bg-orange-50"><tr><th class="p-2 text-left">Name</th><th class="p-2 text-left">Type</th><th class="p-2 text-right">Default</th><th class="p-2 text-right">Total Deducted</th><th class="p-2 text-center">Action</th></tr></thead><tbody>';
        types.forEach(t => {
            const defaultDisplay = t.is_percentage ? t.default_value + '%' : fmt(t.default_value);
            html += `<tr class="border-t hover:bg-orange-50">
                <td class="p-2 font-semibold">${t.name}</td>
                <td class="p-2 capitalize text-gray-500">${t.type}</td>
                <td class="p-2 text-right">${defaultDisplay}</td>
                <td class="p-2 text-right text-red-600 font-bold">${fmt(t.total_deducted || 0)}</td>
                <td class="p-2 text-center">
                    <button onclick="deleteDeductionType(${t.id})" class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('deductionTypesList').innerHTML = html;
    } catch (e) {
        document.getElementById('deductionTypesList').innerHTML = '<p class="text-red-500 text-center py-4">Error loading types.</p>';
    }
}

async function submitDeductionType() {
    const name    = document.getElementById('dt_name').value.trim();
    const type    = document.getElementById('dt_type').value;
    const defVal  = parseFloat(document.getElementById('dt_default').value);
    const notes   = document.getElementById('dt_notes').value.trim();

    if (!name || isNaN(defVal)) { alert('Name and default value are required.'); return; }

    try {
        await axios.post(`${API_BASE}/payroll/deduction-types`, {
            name, type, default_value: defVal,
            is_percentage: type === 'percentage',
            notes: notes || null,
        });
        document.getElementById('dt_name').value    = '';
        document.getElementById('dt_default').value = '';
        document.getElementById('dt_notes').value   = '';
        loadDeductionTypesList();
    } catch (e) {
        alert('❌ ' + (e.response?.data?.message || e.message));
    }
}

async function deleteDeductionType(id) {
    if (!confirm('Remove this deduction type?')) return;
    try {
        const r = await axios.delete(`${API_BASE}/payroll/deduction-types/${id}`);
        alert(r.data.message);
        loadDeductionTypesList();
    } catch (e) {
        alert('❌ ' + (e.response?.data?.message || e.message));
    }
}

// ─── Deductions Ledger ────────────────────────────────────────────────────────

async function loadDeductionsLedger() {
    document.getElementById('deductions-table').innerHTML = '<p class="text-gray-400 text-center py-6">Loading…</p>';
    const month = document.getElementById('dl-month').value;
    const year  = document.getElementById('dl-year').value;

    try {
        const r = await axios.get(`${API_BASE}/payroll/deductions-ledger`, { params: { month, year } });
        const { deductions, summary, total_deducted } = r.data;

        // Summary cards
        let summHtml = '<div class="flex flex-wrap gap-3 mb-4">';
        (summary || []).forEach(s => {
            summHtml += `
                <div class="bg-orange-50 border border-orange-200 rounded p-3 min-w-[140px]">
                    <div class="text-xs text-orange-500 font-bold uppercase">${s.name}</div>
                    <div class="text-lg font-bold text-orange-700 mt-1">${fmt(s.total)}</div>
                    <div class="text-xs text-gray-400">${s.count} entries</div>
                </div>`;
        });
        summHtml += `<div class="bg-red-50 border border-red-200 rounded p-3 min-w-[140px]">
            <div class="text-xs text-red-500 font-bold uppercase">TOTAL</div>
            <div class="text-lg font-bold text-red-700 mt-1">${fmt(total_deducted)}</div>
        </div>`;
        summHtml += '</div>';
        document.getElementById('deductions-summary').innerHTML = summHtml;

        if (!deductions || deductions.length === 0) {
            document.getElementById('deductions-table').innerHTML = '<p class="text-gray-400 text-center py-6">No deductions for this period.</p>';
            return;
        }

        let html = `
            <table class="w-full text-sm border border-gray-200 rounded-lg">
                <thead class="bg-red-50 text-red-700">
                    <tr>
                        <th class="p-3 text-left">Staff</th>
                        <th class="p-3 text-left">Period</th>
                        <th class="p-3 text-left">Deduction</th>
                        <th class="p-3 text-left">Type</th>
                        <th class="p-3 text-right">Amount</th>
                        <th class="p-3 text-left">Note</th>
                    </tr>
                </thead>
                <tbody>`;

        deductions.forEach(d => {
            const entry = d.payroll_entry || {};
            const staff = entry.staff || {};
            html += `
                <tr class="border-t hover:bg-red-50">
                    <td class="p-3 font-semibold">${staff.name || '—'}</td>
                    <td class="p-3">${MONTHS[entry.month] || ''} ${entry.year || ''}</td>
                    <td class="p-3">${d.name}</td>
                    <td class="p-3 capitalize text-gray-500">${d.type}</td>
                    <td class="p-3 text-right font-bold text-red-600">${fmt(d.amount)}</td>
                    <td class="p-3 text-gray-400 text-xs">${d.note || '—'}</td>
                </tr>`;
        });

        html += '</tbody></table>';
        document.getElementById('deductions-table').innerHTML = html;
    } catch (e) {
        document.getElementById('deductions-table').innerHTML = '<p class="text-red-500 text-center py-4">Error loading deductions ledger.</p>';
    }
}

// ─── Upload CSV ───────────────────────────────────────────────────────────────

function showUploadCsvModal() {
    document.getElementById('uploadCsvModal').classList.remove('hidden');
}
function closeUploadCsvModal() {
    document.getElementById('uploadCsvModal').classList.add('hidden');
    document.getElementById('uploadCsvForm').reset();
}

async function submitUploadCsvForm(event) {
    event.preventDefault();
    const file = document.getElementById('csv_file').files[0];
    if (!file) { alert('Please select a CSV file.'); return; }

    const formData = new FormData();
    formData.append('file', file);

    try {
        const r = await axios.post(`${API_BASE}/staff/csv/upload`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        alert('✅ ' + r.data.message + (r.data.errors?.length ? '\nErrors:\n' + r.data.errors.join('\n') : ''));
        closeUploadCsvModal();
        loadStaff();
    } catch (e) {
        alert('❌ ' + (e.response?.data?.message || e.message));
    }
}
</script>
@endpush
