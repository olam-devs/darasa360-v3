@extends('layouts.accountant')

@section('title', 'Reconciliation — Darasa Finance')
@section('page_title', 'Reconciliation')

@section('content')
<div class="w-full p-6 space-y-6">
        <p class="text-gray-600 max-w-4xl">
            Select a book, review the <strong>cash (accountant)</strong> ledger, then record missing or corrected entries by <strong>category</strong> (like Fee entry or Expenses).
            Use <strong>Adjust</strong> on an existing line to fix amounts. New entries are dated — balances from that date onward reflect them.
        </p>
        @if(empty($canEditHistory))
        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900">
            You can view the ledger here. To post adjustments or use <strong>Adjust</strong> on lines, enable <strong>Edit history</strong> under Settings → Accountant access (or ask your administrator).
        </p>
        @else
        <p class="mt-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm text-blue-900">
            Click a ledger row to select it, then use <strong>Adjust</strong> to correct amounts. Advance payments applied to fees appear on the student fee ledger (not as book cash).
        </p>
        @endif

        <div class="bg-white border-2 border-blue-200 rounded-xl p-6 shadow-sm grid grid-cols-1 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2">
                <label class="block text-sm font-bold mb-2">Book *</label>
                <select id="bookSelect" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    <option value="">— Choose book —</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">From date</label>
                <input type="date" id="fromDate" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">To date</label>
                <input type="date" id="toDate" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="lg:col-span-4 flex flex-wrap gap-3">
                <button type="button" onclick="loadAll()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition">
                    Load ledger &amp; activity
                </button>
                <a id="ledgerPdf" href="#" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-bold transition inline-block">
                    Ledger PDF
                </a>
            </div>
        </div>

        <div id="summaryBox" class="hidden bg-blue-50 border-2 border-blue-200 rounded-xl p-4"></div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white border-2 border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-blue-100 px-4 py-3 font-bold text-blue-900">Record entry (choose category)</div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-sm font-bold mb-1">Category of entry *</label>
                        <select id="adjEntryCategory" onchange="loadAdjustmentForm()" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-sm" {{ empty($canEditHistory) ? 'disabled' : '' }}>
                            <option value="">— What are you recording? —</option>
                            <option value="student_fee">Student fee (charge / receipt / payment)</option>
                            <option value="book_correction">Book correction (no student — general in/out)</option>
                            <option value="bank_deposit">Bank deposit (money into book)</option>
                            <option value="bank_withdrawal">Bank withdrawal (money out of book)</option>
                            <option value="bank_fee">Bank transaction fee</option>
                            <option value="monthly_cut">Monthly bank cut</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">The form below matches Fee entry, deposits, and bank lines.</p>
                    </div>
                    <div id="adjFormHost" class="text-sm text-gray-500 border-t border-dashed border-gray-200 pt-3">
                        Choose a book above, then pick a category.
                    </div>
                </div>
            </div>

            <div class="bg-white border-2 border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-amber-100 px-4 py-3 font-bold text-amber-900">Bank deposits &amp; withdrawals</div>
                <div id="txnList" class="p-4 text-sm text-gray-600 max-h-96 overflow-y-auto">
                    Select a book and load.
                </div>
                <div class="border-t border-indigo-200 bg-indigo-50 px-4 py-2 font-bold text-indigo-900 text-sm">Recent advance applications</div>
                <div id="advanceList" class="p-4 text-sm text-gray-600 max-h-40 overflow-y-auto">
                    Load ledger to list fee payments made from student advance.
                </div>
            </div>
        </div>

        <div class="bg-white border-2 border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <div class="bg-slate-100 px-4 py-3 font-bold text-slate-800 flex flex-wrap items-center justify-between gap-2">
                <span>Ledger (cash view)</span>
                <span id="selectedRowHint" class="text-xs font-normal text-slate-600">Click a row to select · purple = paid from advance</span>
            </div>
            <div id="ledgerTable" class="overflow-x-auto p-2"></div>
        </div>
    </div>

    <div id="editModalHost"></div>
@endsection

@push('scripts')
    <script>
const CAN_EDIT_HISTORY = @json($canEditHistory ?? false);
const API_BASE = '/api';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.withCredentials = true;

        function formatTSh(n) {
            return 'TSh ' + parseFloat(n || 0).toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Money input formatting (commas) while keeping numeric payloads
        function parseMoneyInput(value) {
            if (value === null || value === undefined) return 0;
            const cleaned = String(value).replace(/,/g, '').trim();
            const n = parseFloat(cleaned);
            return Number.isFinite(n) ? n : 0;
        }

        function formatMoneyForInput(value) {
            const n = parseMoneyInput(value);
            return n.toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function attachMoneyFormatting(inputId) {
            const el = document.getElementById(inputId);
            if (!el) return;
            el.setAttribute('inputmode', 'decimal');
            el.addEventListener('focus', () => { el.value = String(el.value || '').replace(/,/g, ''); });
            el.addEventListener('blur', () => { if (el.value !== '') el.value = formatMoneyForInput(el.value); });
        }

        function selectedBookId() {
            return document.getElementById('bookSelect').value;
        }

        let _reconParticulars = [];
        let _reconStudents = [];
        let _reconFeeStudents = [];

        function todayStr() {
            return new Date().toISOString().slice(0, 10);
        }

        async function ensureReconLookups() {
            if (_reconParticulars.length) return;
            const [p, s] = await Promise.all([
                axios.get(`${API_BASE}/particulars`),
                axios.get(`${API_BASE}/students`),
            ]);
            _reconParticulars = p.data || [];
            _reconStudents = s.data.students || s.data || [];
        }

        async function loadReconFeeStudents() {
            const pid = document.getElementById('adjFeeParticular')?.value;
            const sel = document.getElementById('adjFeeStudent');
            if (!sel) return;
            sel.innerHTML = '<option value="">— Select student —</option>';
            _reconFeeStudents = [];
            if (!pid) return;
            try {
                const res = await axios.get(`${API_BASE}/particulars/${pid}`);
                _reconFeeStudents = res.data.students || [];
                _reconFeeStudents.forEach(st => {
                    const o = document.createElement('option');
                    o.value = st.id;
                    o.textContent = st.name || st.student_name || `Student #${st.id}`;
                    sel.appendChild(o);
                });
            } catch (e) {
                console.error(e);
            }
        }

        function prefillReconFeeNotes() {
            const notes = document.getElementById('adjFeeNotes');
            if (!notes || notes.dataset.touched === '1') return;
            const type = document.getElementById('adjFeeType')?.value || '';
            const student = document.getElementById('adjFeeStudent')?.selectedOptions?.[0]?.textContent?.trim() || 'student';
            const particular = document.getElementById('adjFeeParticular')?.selectedOptions?.[0]?.textContent?.trim() || 'fee';
            if (type === 'Sales') notes.value = `Fee charged: ${particular} (${student})`;
            else if (type === 'Receipt') notes.value = `Cash receipt for ${particular} (${student}) — reconciliation`;
            else if (type === 'Payment') notes.value = `Payment for ${particular} (${student})`;
        }

        async function loadAdjustmentForm() {
            const host = document.getElementById('adjFormHost');
            const cat = document.getElementById('adjEntryCategory')?.value || '';
            if (!host) return;
            if (!CAN_EDIT_HISTORY) {
                host.innerHTML = '<p class="text-amber-700">Enable <strong>Edit history</strong> to record entries.</p>';
                return;
            }
            if (!selectedBookId()) {
                host.innerHTML = '<p class="text-amber-700">Choose a book above first.</p>';
                return;
            }
            if (!cat) {
                host.innerHTML = '<p class="text-gray-500">Pick a category above.</p>';
                return;
            }

            const d = todayStr();
            if (cat === 'student_fee') {
                await ensureReconLookups();
                const pOpts = _reconParticulars.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
                host.innerHTML = `
                    <div class="space-y-3 text-sm">
                        <p class="text-xs text-blue-800 bg-blue-50 border border-blue-200 rounded p-2">Posts to the selected book. Receipts update student balance and book cash.</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="font-bold text-xs">Date *</label><input type="date" id="adjFeeDate" value="${d}" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                            <div><label class="font-bold text-xs">Type *</label>
                                <select id="adjFeeType" onchange="prefillReconFeeNotes()" class="w-full border-2 rounded-lg px-2 py-1.5">
                                    <option value="Sales">Sales (charge)</option>
                                    <option value="Receipt">Receipt (payment)</option>
                                    <option value="Payment">Payment (refund)</option>
                                </select>
                            </div>
                        </div>
                        <div><label class="font-bold text-xs">Particular *</label>
                            <select id="adjFeeParticular" onchange="loadReconFeeStudents(); prefillReconFeeNotes();" class="w-full border-2 rounded-lg px-2 py-1.5">
                                <option value="">— Particular —</option>${pOpts}
                            </select>
                        </div>
                        <div><label class="font-bold text-xs">Student *</label>
                            <select id="adjFeeStudent" onchange="prefillReconFeeNotes()" class="w-full border-2 rounded-lg px-2 py-1.5">
                                <option value="">— Select particular first —</option>
                            </select>
                        </div>
                        <div><label class="font-bold text-xs">Amount (TSh) *</label>
                            <input type="text" id="adjFeeAmount" class="w-full border-2 rounded-lg px-2 py-1.5" placeholder="0.00">
                        </div>
                        <div><label class="font-bold text-xs">Reason / description *</label>
                            <textarea id="adjFeeNotes" rows="2" class="w-full border-2 rounded-lg px-2 py-1.5" placeholder="Required"></textarea>
                        </div>
                        <button type="button" onclick="submitAdjustmentEntry()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg">Save student fee entry</button>
                    </div>`;
                attachMoneyFormatting('adjFeeAmount');
                const notesEl = document.getElementById('adjFeeNotes');
                if (notesEl) notesEl.addEventListener('input', () => { notesEl.dataset.touched = '1'; });
                prefillReconFeeNotes();
                return;
            }

            if (cat === 'book_correction') {
                host.innerHTML = `
                    <div class="space-y-3 text-sm">
                        <p class="text-xs text-gray-600">General book in/out with no student (reconciliation adjustment).</p>
                        <div><label class="font-bold text-xs">Direction *</label>
                            <select id="adjCorrDirection" class="w-full border-2 rounded-lg px-2 py-1.5">
                                <option value="increase">Increase balance (money in)</option>
                                <option value="decrease">Decrease balance (money out)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="font-bold text-xs">Amount *</label><input type="text" id="adjCorrAmount" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                            <div><label class="font-bold text-xs">Date *</label><input type="date" id="adjCorrDate" value="${d}" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                        </div>
                        <div><label class="font-bold text-xs">Reason *</label><textarea id="adjCorrReason" rows="2" class="w-full border-2 rounded-lg px-2 py-1.5"></textarea></div>
                        <button type="button" onclick="submitAdjustmentEntry()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg">Post book correction</button>
                    </div>`;
                attachMoneyFormatting('adjCorrAmount');
                return;
            }

            if (cat === 'bank_deposit') {
                host.innerHTML = `
                    <div class="space-y-3 text-sm">
                        <div><label class="font-bold text-xs">Amount *</label><input type="text" id="adjDepAmount" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                        <div><label class="font-bold text-xs">Date *</label><input type="date" id="adjDepDate" value="${d}" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                        <div><label class="font-bold text-xs">Reference</label><input type="text" id="adjDepRef" class="w-full border-2 rounded-lg px-2 py-1.5" placeholder="Transfer / slip #"></div>
                        <div><label class="font-bold text-xs">Reason / short note *</label><input type="text" id="adjDepNotes" class="w-full border-2 rounded-lg px-2 py-1.5" placeholder="Shown on ledger"></div>
                        <button type="button" onclick="submitAdjustmentEntry()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-lg">Record deposit</button>
                    </div>`;
                attachMoneyFormatting('adjDepAmount');
                return;
            }

            if (cat === 'bank_withdrawal') {
                host.innerHTML = `
                    <div class="space-y-3 text-sm">
                        <div><label class="font-bold text-xs">Amount *</label><input type="text" id="adjWdAmount" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                        <div><label class="font-bold text-xs">Date *</label><input type="date" id="adjWdDate" value="${d}" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                        <div><label class="font-bold text-xs">Reference</label><input type="text" id="adjWdRef" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                        <div><label class="font-bold text-xs">Reason / short note *</label><input type="text" id="adjWdNotes" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                        <button type="button" onclick="submitAdjustmentEntry()" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-lg">Record withdrawal</button>
                    </div>`;
                attachMoneyFormatting('adjWdAmount');
                return;
            }

            if (cat === 'bank_fee' || cat === 'monthly_cut') {
                const label = cat === 'bank_fee' ? 'Bank transaction fee' : 'Monthly bank cut';
                host.innerHTML = `
                    <div class="space-y-3 text-sm">
                        <p class="text-xs text-gray-600">${label} reduces book balance (credit voucher).</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="font-bold text-xs">Amount *</label><input type="text" id="adjBkAmount" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                            <div><label class="font-bold text-xs">Date *</label><input type="date" id="adjBkDate" value="${d}" class="w-full border-2 rounded-lg px-2 py-1.5"></div>
                        </div>
                        <div><label class="font-bold text-xs">Reason *</label><textarea id="adjBkReason" rows="2" class="w-full border-2 rounded-lg px-2 py-1.5"></textarea></div>
                        <button type="button" onclick="submitAdjustmentEntry()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg">Record ${label.toLowerCase()}</button>
                    </div>`;
                attachMoneyFormatting('adjBkAmount');
            }
        }

        async function submitAdjustmentEntry() {
            const bookId = selectedBookId();
            const cat = document.getElementById('adjEntryCategory')?.value;
            if (!bookId || !cat) {
                alert('Choose a book and category.');
                return;
            }
            try {
                if (cat === 'student_fee') {
                    const amount = parseMoneyInput(document.getElementById('adjFeeAmount')?.value);
                    const date = document.getElementById('adjFeeDate')?.value;
                    const studentId = document.getElementById('adjFeeStudent')?.value;
                    const particularId = document.getElementById('adjFeeParticular')?.value;
                    const voucherType = document.getElementById('adjFeeType')?.value;
                    const notes = document.getElementById('adjFeeNotes')?.value?.trim();
                    if (!amount || !date || !studentId || !particularId || !voucherType) {
                        alert('Fill all required student fee fields.');
                        return;
                    }
                    if (!notes) {
                        alert('Reason / description is required.');
                        return;
                    }
                    const payload = {
                        date,
                        student_id: studentId,
                        particular_id: particularId,
                        book_id: bookId,
                        voucher_type: voucherType,
                        notes,
                    };
                    if (voucherType === 'Sales' || voucherType === 'Receipt') {
                        payload.debit = amount;
                        payload.credit = 0;
                    } else {
                        payload.debit = 0;
                        payload.credit = amount;
                    }
                    await axios.post(`${API_BASE}/vouchers`, payload);
                } else if (cat === 'book_correction') {
                    const amount = parseMoneyInput(document.getElementById('adjCorrAmount')?.value);
                    const date = document.getElementById('adjCorrDate')?.value;
                    const reason = document.getElementById('adjCorrReason')?.value?.trim();
                    const direction = document.getElementById('adjCorrDirection')?.value;
                    if (!amount || !date || !reason) {
                        alert('Fill amount, date, and reason.');
                        return;
                    }
                    await axios.post(`${API_BASE}/reconciliation/adjustments`, {
                        book_id: bookId, direction, amount, date, reason,
                    });
                } else if (cat === 'bank_deposit') {
                    const amount = parseMoneyInput(document.getElementById('adjDepAmount')?.value);
                    const transaction_date = document.getElementById('adjDepDate')?.value;
                    const short_notes = document.getElementById('adjDepNotes')?.value?.trim();
                    if (!amount || !transaction_date || !short_notes) {
                        alert('Amount, date, and reason are required.');
                        return;
                    }
                    await axios.post(`${API_BASE}/book-transactions/deposit`, {
                        book_id: bookId,
                        amount,
                        transaction_date,
                        reference_number: document.getElementById('adjDepRef')?.value || null,
                        short_notes,
                    });
                } else if (cat === 'bank_withdrawal') {
                    const amount = parseMoneyInput(document.getElementById('adjWdAmount')?.value);
                    const transaction_date = document.getElementById('adjWdDate')?.value;
                    const short_notes = document.getElementById('adjWdNotes')?.value?.trim();
                    if (!amount || !transaction_date || !short_notes) {
                        alert('Amount, date, and reason are required.');
                        return;
                    }
                    await axios.post(`${API_BASE}/book-transactions/withdrawal`, {
                        book_id: bookId,
                        amount,
                        transaction_date,
                        reference_number: document.getElementById('adjWdRef')?.value || null,
                        short_notes,
                    });
                } else if (cat === 'bank_fee' || cat === 'monthly_cut') {
                    const amount = parseMoneyInput(document.getElementById('adjBkAmount')?.value);
                    const date = document.getElementById('adjBkDate')?.value;
                    const reason = document.getElementById('adjBkReason')?.value?.trim();
                    if (!amount || !date || !reason) {
                        alert('Fill amount, date, and reason.');
                        return;
                    }
                    const url = cat === 'bank_fee'
                        ? `${API_BASE}/reconciliation/bank-fee`
                        : `${API_BASE}/reconciliation/monthly-cut`;
                    await axios.post(url, { book_id: bookId, amount, date, reason });
                } else {
                    return;
                }
                alert('✅ Entry recorded.');
                document.getElementById('adjEntryCategory').value = '';
                document.getElementById('adjFormHost').innerHTML = 'Choose a book above, then pick a category.';
                await loadAll();
            } catch (e) {
                alert('❌ ' + (e.response?.data?.error || e.response?.data?.message || e.message));
            }
        }

        async function loadBooks() {
            const res = await axios.get(`${API_BASE}/books`);
            const books = res.data || [];
            const sel = document.getElementById('bookSelect');
            sel.innerHTML = '<option value="">— Choose book —</option>';
            books.forEach(b => {
                const o = document.createElement('option');
                o.value = b.id;
                o.textContent = `${b.name} (${b.book_type || 'book'})`;
                sel.appendChild(o);
            });
        }

        function ledgerQuery() {
            const id = selectedBookId();
            const p = new URLSearchParams({ view_type: 'cash', for_reconciliation: '1' });
            const f = document.getElementById('fromDate').value;
            const t = document.getElementById('toDate').value;
            if (f) p.set('from_date', f);
            if (t) p.set('to_date', t);
            return { id, q: p.toString() };
        }

        window._reconLedgerRows = {};
        let _selectedLedgerRowId = null;

        function selectLedgerRow(rowId) {
            _selectedLedgerRowId = rowId;
            document.querySelectorAll('[data-ledger-row]').forEach(tr => {
                const id = parseInt(tr.getAttribute('data-ledger-row'), 10);
                const base = tr.getAttribute('data-base-class') || 'border-t hover:bg-gray-50';
                if (id === rowId) {
                    tr.className = base + ' bg-blue-100 ring-2 ring-blue-400 ring-inset';
                } else {
                    tr.className = base;
                }
            });
            const row = window._reconLedgerRows[rowId];
            const hint = document.getElementById('selectedRowHint');
            if (hint && row) {
                hint.innerHTML = `Selected: <strong>${row.voucher_number || row.id}</strong> — ${row.particular || ''}` +
                    (CAN_EDIT_HISTORY && row.adjustable ? ' · Use <strong>Adjust</strong> to edit' : (CAN_EDIT_HISTORY ? '' : ' · Enable Edit history to adjust'));
            }
        }

        async function loadLedger() {
            const { id, q } = ledgerQuery();
            if (!id) {
                alert('Choose a book first.');
                return;
            }
            const res = await axios.get(`${API_BASE}/ledgers/book/${id}?${q}`);
            const data = res.data;
            window._reconLedgerRows = {};
            const sum = data.summary || {};

            document.getElementById('summaryBox').classList.remove('hidden');
            document.getElementById('summaryBox').innerHTML = `
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
                    <div><div class="text-xs text-gray-500">Opening</div><div class="font-bold">${formatTSh(sum.opening_balance)}</div></div>
                    <div><div class="text-xs text-gray-500">Receipts (DR)</div><div class="font-bold text-emerald-700">${formatTSh(sum.total_receipts)}</div></div>
                    <div><div class="text-xs text-gray-500">Payments (CR)</div><div class="font-bold text-rose-700">${formatTSh(sum.total_payments)}</div></div>
                    <div><div class="text-xs text-gray-500">Closing</div><div class="font-bold text-blue-800">${formatTSh(sum.closing_balance)}</div></div>
                </div>
            `;

            const pdf = new URL(`${API_BASE}/ledgers/book/${id}/pdf`, window.location.origin);
            if (q) pdf.search = q;
            document.getElementById('ledgerPdf').href = pdf.toString();

            let html = '<table class="w-full text-sm"><thead class="bg-slate-200"><tr>' +
                '<th class="p-2 text-left">Date</th><th class="p-2 text-left">Ref</th><th class="p-2 text-left">Student / payee</th><th class="p-2 text-left">Particular</th><th class="p-2 text-left">Type</th>' +
                '<th class="p-2 text-left">Reason</th><th class="p-2 text-right">Debit</th><th class="p-2 text-right">Credit</th><th class="p-2 text-right">Balance</th><th class="p-2"></th></tr></thead><tbody>';

            const advanceRows = [];

            (data.ledger || []).forEach(row => {
                if (row.is_month_start) {
                    html += `<tr class="bg-blue-50 font-semibold"><td colspan="10" class="p-2">▶ ${row.month} — opening ${formatTSh(row.opening_balance)}</td></tr>`;
                    return;
                }
                if (row.is_month_end) {
                    html += `<tr class="bg-amber-50"><td colspan="10" class="p-2">■ ${row.month} — closing ${formatTSh(row.closing_balance)} (month DR ${formatTSh(row.monthly_debit)} / CR ${formatTSh(row.monthly_credit)})</td></tr>`;
                    return;
                }
                if (row.id) window._reconLedgerRows[row.id] = row;
                if (row.is_advance_used) advanceRows.push(row);

                let adj = '';
                if (row.is_voided) {
                    adj = '<span class="text-xs text-red-600">Voided</span>';
                } else if (CAN_EDIT_HISTORY && row.adjustable) {
                    adj = `<button type="button" class="text-blue-600 font-bold hover:underline" onclick="event.stopPropagation(); openEditVoucher(${row.id})">Adjust</button>`;
                } else if (!CAN_EDIT_HISTORY && row.adjustable) {
                    adj = '<span class="text-xs text-gray-400" title="Enable Edit history in Settings">No permission</span>';
                }

                const rowClass = row.is_advance_used ? 'border-t hover:bg-indigo-50 bg-indigo-50/80' : (row.is_voided ? 'border-t opacity-60' : 'border-t hover:bg-gray-50');
                const particularCell = row.is_advance_used
                    ? `<span class="font-semibold text-indigo-800">${row.particular || ''}</span> <span class="text-[10px] uppercase bg-indigo-200 text-indigo-900 px-1 rounded">Advance</span>`
                    : (row.particular || '');

                html += `<tr data-ledger-row="${row.id}" data-base-class="${rowClass}" class="${rowClass} cursor-pointer" onclick="selectLedgerRow(${row.id})">
                    <td class="p-2 whitespace-nowrap">${row.date || ''}</td>
                    <td class="p-2 font-mono text-xs">${row.voucher_number || ''}</td>
                    <td class="p-2">${row.student || ''}</td>
                    <td class="p-2">${particularCell}</td>
                    <td class="p-2">${row.voucher_type || ''}</td>
                    <td class="p-2 text-xs text-gray-600 max-w-[12rem] truncate" title="${(row.notes || '').replace(/"/g, '&quot;')}">${row.notes || '—'}</td>
                    <td class="p-2 text-right">${formatTSh(row.debit)}</td>
                    <td class="p-2 text-right">${formatTSh(row.credit)}</td>
                    <td class="p-2 text-right font-semibold">${formatTSh(row.balance)}</td>
                    <td class="p-2" onclick="event.stopPropagation()">${adj}</td>
                </tr>`;
            });

            html += '</tbody></table>';
            document.getElementById('ledgerTable').innerHTML = html;
            renderAdvanceList(advanceRows);
        }

        function renderAdvanceList(rows) {
            const box = document.getElementById('advanceList');
            if (!box) return;
            if (!rows.length) {
                box.innerHTML = '<p class="text-gray-500">No advance applications in this date range on this book ledger. Apply advance from <strong>Fee entry</strong>; those update the student/particular balance (cash was already received earlier).</p>';
                return;
            }
            let h = '<ul class="space-y-2">';
            rows.forEach(r => {
                h += `<li class="border border-indigo-200 rounded p-2 bg-white"><span class="font-semibold text-indigo-800">${r.student || ''}</span> — ${r.particular || ''}<br><span class="text-xs text-gray-600">${r.notes || ''}</span><br><span class="text-xs font-bold">${formatTSh(r.debit)}</span> · ${r.date}</li>`;
            });
            h += '</ul>';
            box.innerHTML = h;
        }

        async function loadRecentAdvanceApplications() {
            try {
                const from = document.getElementById('fromDate').value;
                const to = document.getElementById('toDate').value;
                let url = `${API_BASE}/vouchers?per_page=20`;
                if (from) url += `&date_from=${from}`;
                if (to) url += `&date_to=${to}`;
                const res = await axios.get(url);
                const items = (res.data.data || res.data || []).filter(v => v.payment_by_receipt_to === 'Advance Used');
                renderAdvanceList(items.map(v => ({
                    student: v.display_student_name || v.student?.name,
                    particular: v.display_particular_name || v.particular?.name,
                    notes: v.notes,
                    debit: v.debit,
                    date: v.date,
                })));
            } catch (e) {
                console.error(e);
            }
        }

        async function loadTransactions() {
            const id = selectedBookId();
            if (!id) return;
            const res = await axios.get(`${API_BASE}/book-transactions/${id}?per_page=50`);
            const rows = res.data.transactions?.data || res.data.transactions || [];
            const box = document.getElementById('txnList');
            if (!rows.length) {
                box.innerHTML = '<p>No transactions found for this book.</p>';
                return;
            }
            let h = '<table class="w-full text-xs"><thead><tr class="text-left border-b"><th class="py-1">Date</th><th class="py-1">Type</th><th class="py-1">Amount</th><th class="py-1">Note</th><th class="py-1">Status</th></tr></thead><tbody>';
            rows.forEach(t => {
                const cancelled = t.cancelled_at ? '<span class="text-red-600 font-bold">Cancelled</span>' : 'Active';
                h += `<tr class="border-t border-gray-100">
                    <td class="py-1 whitespace-nowrap">${t.transaction_date || ''}</td>
                    <td class="py-1">${t.transaction_type || ''}</td>
                    <td class="py-1">${formatTSh(t.amount)}</td>
                    <td class="py-1">${(t.short_notes || '').slice(0, 40)}</td>
                    <td class="py-1">${cancelled}</td>
                </tr>`;
            });
            h += '</tbody></table><p class="mt-2 text-xs text-gray-500">To cancel or replace a deposit/withdrawal, use <strong>Books Management</strong> → transaction history (Cancel).</p>';
            box.innerHTML = h;
        }

        async function loadAll() {
            try {
                if (!selectedBookId()) {
                    alert('Choose a book first.');
                    return;
                }
                await loadLedger();
                await loadTransactions();
                await loadRecentAdvanceApplications();
            } catch (e) {
                console.error(e);
                alert('❌ ' + (e.response?.data?.error || e.response?.data?.message || e.message));
            }
        }

        function openEditVoucher(rowId) {
            const row = window._reconLedgerRows[rowId];
            if (!row) {
                alert('Row not found — reload the ledger.');
                return;
            }
            const host = document.getElementById('editModalHost');
            const feeLike = row.payment_by_receipt_to === 'Bank Transaction Fee' || row.payment_by_receipt_to === 'Monthly Bank Cut';
            const isPayment = row.voucher_type === 'Payment';
            const isReceiptOrSales = row.voucher_type === 'Receipt' || row.voucher_type === 'Sales';
            const sd = parseFloat(row.storage_debit) || 0;
            const sc = parseFloat(row.storage_credit) || 0;
            const debitReadonly = feeLike || isPayment || (sc > 0 && sd === 0 && !isReceiptOrSales);
            const creditReadonly = (isReceiptOrSales && !feeLike) || (sd > 0 && sc === 0 && !isPayment && !feeLike);

            host.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-2xl">
                    <h3 class="text-xl font-bold mb-2 text-blue-800">Adjust voucher ${row.voucher_number || ''}</h3>
                    <p class="text-xs text-gray-600 mb-4">${row.particular || row.payment_by_receipt_to || ''} — ${row.voucher_type || ''}. Stored amounts (accountant view). Receipts/sales: debit only; payments & bank fees: credit only.</p>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-bold">Debit (storage)</label>
                            <input type="text" id="evDebit" class="w-full border-2 rounded-lg px-3 py-2" value="${row.storage_debit}" ${debitReadonly ? 'readonly class="bg-gray-100"' : ''}>
                        </div>
                        <div>
                            <label class="text-sm font-bold">Credit (storage)</label>
                            <input type="text" id="evCredit" class="w-full border-2 rounded-lg px-3 py-2" value="${row.storage_credit}" ${creditReadonly ? 'readonly class="bg-gray-100"' : ''}>
                        </div>
                        <div>
                            <label class="text-sm font-bold">Note to append</label>
                            <input type="text" id="evNote" class="w-full border-2 rounded-lg px-3 py-2" placeholder="e.g., Corrected per statement 05/2026">
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="closeEdit()" class="flex-1 bg-gray-300 hover:bg-gray-400 py-2 rounded-lg font-bold">Cancel</button>
                        <button type="button" onclick="saveEditVoucher(${row.id})" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-bold">Save</button>
                    </div>
                </div>
            </div>`;
            attachMoneyFormatting('evDebit');
            attachMoneyFormatting('evCredit');
        }

        function closeEdit() {
            document.getElementById('editModalHost').innerHTML = '';
        }

        async function saveEditVoucher(voucherId) {
            const bookId = selectedBookId();
            try {
                await axios.put(`${API_BASE}/reconciliation/vouchers/${voucherId}`, {
                    book_id: bookId,
                    debit: parseMoneyInput(document.getElementById('evDebit').value),
                    credit: parseMoneyInput(document.getElementById('evCredit').value),
                    notes_append: document.getElementById('evNote').value.trim() || null,
                });
                alert('✅ Voucher updated.');
                closeEdit();
                await loadAll();
            } catch (e) {
                alert('❌ ' + (e.response?.data?.error || e.response?.data?.message || e.message));
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadBooks().catch(e => console.error(e));
            document.getElementById('bookSelect')?.addEventListener('change', () => {
                const cat = document.getElementById('adjEntryCategory')?.value;
                if (cat) loadAdjustmentForm();
            });
        });
    </script>
@endpush
