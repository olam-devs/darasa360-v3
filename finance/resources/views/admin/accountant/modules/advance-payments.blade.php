@extends('layouts.accountant')

@section('title', 'Advance payments — Darasa Finance')
@section('page_title', 'Advance payments')

@section('content')
<div class="w-full p-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
        <div class="flex-1">
            <label class="block text-sm font-bold mb-2">Search student (name or reg no)</label>
            <input id="searchQ" type="text" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2" placeholder="e.g., S1001, Asha">
        </div>
        <div class="flex gap-3">
            <button onclick="loadAdvancePayments()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-bold transition">
                 Search
            </button>
            <a id="pdfLink" href="/api/advance-payments/pdf" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-bold transition">
                 Download PDF
            </a>
            <a id="csvLink" href="/api/advance-payments/csv" class="bg-slate-600 hover:bg-slate-700 text-white px-6 py-3 rounded-lg font-bold transition">
                 Download CSV
            </a>
        </div>
    </div>

    <div id="summary" class="bg-emerald-50 border-2 border-emerald-200 rounded-lg p-4 mb-4"></div>
    <div id="list" class="bg-white border-2 border-gray-200 rounded-lg overflow-hidden"></div>
</div>

{{-- Assign Advance to Fee Modal --}}
<div id="assignModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
        <div class="bg-emerald-700 text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
            <h3 class="text-lg font-bold">Assign Advance to Fee</h3>
            <button onclick="closeAssignModal()" class="text-white hover:text-gray-200 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-6">
            <div id="assignStudentInfo" class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 mb-4">
                <div class="text-sm text-gray-500">Student</div>
                <div id="assignStudentName" class="font-bold text-lg"></div>
                <div class="text-sm text-gray-500 mt-1">Available advance balance</div>
                <div id="assignAdvanceBalance" class="font-bold text-emerald-700 text-lg"></div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">Select Fee / Particular <span class="text-red-500">*</span></label>
                <select id="assignParticularSelect" onchange="onParticularChange()"
                    class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">-- loading particulars... --</option>
                </select>
                <div id="assignParticularBalance" class="text-xs text-gray-500 mt-1 hidden">
                    Outstanding: <span id="assignParticularBalanceAmt" class="font-bold text-red-600"></span>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">Amount to Apply (TSh) <span class="text-red-500">*</span></label>
                <input id="assignAmount" type="number" min="1" step="1"
                    class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-sm"
                    placeholder="Enter amount">
                <div class="text-xs text-gray-400 mt-1">Must not exceed advance balance or fee outstanding</div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold mb-1">Notes (optional)</label>
                <input id="assignNotes" type="text"
                    class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-sm"
                    placeholder="e.g. Q3 fees applied from advance">
            </div>

            <div id="assignError" class="hidden bg-red-50 border border-red-300 text-red-700 rounded p-3 mb-4 text-sm"></div>

            <div class="flex gap-3 justify-end">
                <button onclick="closeAssignModal()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-bold text-sm transition">Cancel</button>
                <button onclick="submitAssign()" id="assignSubmitBtn"
                    class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-sm transition">
                    Apply Advance
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const API_BASE = '/api';
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.withCredentials = true;

let assignStudentId   = null;
let assignMaxAdvance  = 0;
let assignParticulars = [];

function formatTSh(amount) {
    return 'TSh ' + parseFloat(amount || 0).toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function buildPdfLink(q) {
    const url = new URL('/api/advance-payments/pdf', window.location.origin);
    if (q) url.searchParams.set('q', q);
    document.getElementById('pdfLink').href = url.toString();
    const csvUrl = new URL('/api/advance-payments/csv', window.location.origin);
    if (q) csvUrl.searchParams.set('q', q);
    document.getElementById('csvLink').href = csvUrl.toString();
}

async function loadAdvancePayments() {
    const q = document.getElementById('searchQ').value.trim();
    buildPdfLink(q);

    const url = new URL(`${API_BASE}/advance-payments`, window.location.origin);
    if (q) url.searchParams.set('q', q);

    const res = await axios.get(url.toString());
    const students = res.data.students || [];
    const summary  = res.data.summary  || { count: 0, total_advance: 0 };

    document.getElementById('summary').innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
            <div class="bg-white border rounded p-3">
                <div class="text-xs text-gray-500">Students with advance</div>
                <div class="text-2xl font-bold text-emerald-700">${summary.count || 0}</div>
            </div>
            <div class="bg-white border rounded p-3 md:col-span-2">
                <div class="text-xs text-gray-500">Total advance balance</div>
                <div class="text-2xl font-bold text-blue-700">${formatTSh(summary.total_advance || 0)}</div>
            </div>
        </div>
    `;

    let html = `
        <table class="w-full">
            <thead class="bg-emerald-100">
                <tr>
                    <th class="p-3 text-left">Student</th>
                    <th class="p-3 text-left">Reg No</th>
                    <th class="p-3 text-left">Class</th>
                    <th class="p-3 text-right">Advance Balance</th>
                    <th class="p-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
    `;

    if (!students.length) {
        html += `<tr><td colspan="5" class="p-6 text-center text-gray-500">No students with advance balance.</td></tr>`;
    } else {
        students.forEach(s => {
            const safeName = s.name.replace(/'/g, "\\'");
            html += `
                <tr class="border-t hover:bg-emerald-50">
                    <td class="p-3 font-bold">${s.name}</td>
                    <td class="p-3 font-mono text-sm">${s.student_reg_no}</td>
                    <td class="p-3">${s.school_class?.name || s.class || '-'}</td>
                    <td class="p-3 text-right font-bold text-emerald-700">${formatTSh(s.advance_balance)}</td>
                    <td class="p-3 text-center">
                        <button onclick="openAssignModal(${s.id}, '${safeName}', ${s.advance_balance})"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-bold transition">
                            Assign to Fee
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    html += `</tbody></table>`;
    document.getElementById('list').innerHTML = html;
}

async function openAssignModal(studentId, studentName, advanceBalance) {
    assignStudentId  = studentId;
    assignMaxAdvance = parseFloat(advanceBalance);
    assignParticulars = [];

    document.getElementById('assignStudentName').textContent    = studentName;
    document.getElementById('assignAdvanceBalance').textContent = formatTSh(advanceBalance);
    document.getElementById('assignParticularSelect').innerHTML = '<option value="">-- loading... --</option>';
    document.getElementById('assignParticularBalance').classList.add('hidden');
    document.getElementById('assignAmount').value  = '';
    document.getElementById('assignNotes').value   = '';
    document.getElementById('assignError').classList.add('hidden');
    document.getElementById('assignModal').classList.remove('hidden');

    try {
        const res = await axios.get(`${API_BASE}/students/${studentId}/particulars`);
        assignParticulars = (res.data || []).filter(p => p.balance > 0);

        if (!assignParticulars.length) {
            document.getElementById('assignParticularSelect').innerHTML =
                '<option value="">-- No outstanding fees for this student --</option>';
        } else {
            document.getElementById('assignParticularSelect').innerHTML =
                '<option value="">-- Select a fee --</option>' +
                assignParticulars.map(p =>
                    `<option value="${p.id}" data-balance="${p.balance}">${p.name} (outstanding: ${formatTSh(p.balance)})</option>`
                ).join('');
        }
    } catch (e) {
        document.getElementById('assignParticularSelect').innerHTML =
            '<option value="">-- Error loading fees --</option>';
    }
}

function onParticularChange() {
    const sel = document.getElementById('assignParticularSelect');
    const opt = sel.options[sel.selectedIndex];
    const balance = opt?.dataset?.balance ? parseFloat(opt.dataset.balance) : 0;
    if (sel.value && balance > 0) {
        const maxApply = Math.min(assignMaxAdvance, balance);
        document.getElementById('assignParticularBalanceAmt').textContent = formatTSh(balance);
        document.getElementById('assignParticularBalance').classList.remove('hidden');
        document.getElementById('assignAmount').value = maxApply;
        document.getElementById('assignAmount').max   = maxApply;
    } else {
        document.getElementById('assignParticularBalance').classList.add('hidden');
        document.getElementById('assignAmount').value = '';
    }
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
    assignStudentId = null;
}

async function submitAssign() {
    const particularId = document.getElementById('assignParticularSelect').value;
    const amount       = parseFloat(document.getElementById('assignAmount').value);
    const notes        = document.getElementById('assignNotes').value.trim();
    const errEl        = document.getElementById('assignError');

    errEl.classList.add('hidden');

    if (!particularId) { errEl.textContent = 'Please select a fee particular.'; errEl.classList.remove('hidden'); return; }
    if (!amount || amount <= 0) { errEl.textContent = 'Please enter a valid amount.'; errEl.classList.remove('hidden'); return; }
    if (amount > assignMaxAdvance) { errEl.textContent = `Amount exceeds advance balance (${formatTSh(assignMaxAdvance)}).`; errEl.classList.remove('hidden'); return; }

    const btn = document.getElementById('assignSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Applying...';

    try {
        await axios.post(`${API_BASE}/vouchers/apply-advance`, {
            student_id:    assignStudentId,
            particular_id: particularId,
            amount,
            notes: notes || null,
        });
        closeAssignModal();
        loadAdvancePayments();
    } catch (e) {
        const msg = e.response?.data?.error || 'Failed to apply advance. Please try again.';
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Apply Advance';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    buildPdfLink('');
    loadAdvancePayments();
});
</script>
@endpush
