@extends($portalLayout ?? 'layouts.accountant')

@section('title', 'Student profile — Darasa Finance')
@section('page_title', 'Student profile')

@section('content')
    <div class="pb-6">
        <p class="text-slate-600 mb-6">Search by name or registration number, or pick a class and choose a student. All academic years, assignments, payments, scholarships, and related records load together.</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <div class="lg:col-span-2 bg-white rounded-2xl shadow border border-slate-200 p-5">
                <label class="block text-sm font-bold text-slate-700 mb-2">Search student</label>
                <div class="relative">
                    <input type="text" id="globalSearch" autocomplete="off" placeholder="Type name or reg no (min 2 characters)…"
                           class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 focus:border-indigo-500 focus:outline-none">
                    <div id="searchDropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-64 overflow-y-auto"></div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow border border-slate-200 p-5">
                <label class="block text-sm font-bold text-slate-700 mb-2">Or browse by class</label>
                <select id="classSelect" class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 focus:border-indigo-500 focus:outline-none">
                    <option value="">Select class…</option>
                </select>
                <select id="studentSelect" class="mt-3 w-full border-2 border-slate-200 rounded-xl px-4 py-3 focus:border-indigo-500 focus:outline-none" disabled>
                    <option value="">Select student…</option>
                </select>
            </div>
        </div>

        <div id="emptyState" class="bg-white rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">
            Select a student to load the full record.
        </div>

        <div id="profilePanel" class="hidden space-y-6">
            <!-- Header card -->
            <div class="bg-white rounded-2xl shadow border border-slate-200 p-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <h2 id="pName" class="text-2xl font-extrabold text-slate-900"></h2>
                        <p id="pMeta" class="text-sm text-slate-600 mt-1"></p>
                        <p id="pContact" class="text-sm text-slate-500 mt-2"></p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a id="linkLedgerPdf" href="#" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Ledger PDF</a>
                        <a id="linkLedgerCsv" href="#" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-700 text-white text-sm font-semibold hover:bg-slate-800">Ledger CSV</a>
                        <a id="linkInvoicePdf" href="#" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">Invoice PDF</a>
                        <a id="linkStatement" href="#" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl border-2 border-slate-300 text-slate-800 text-sm font-semibold hover:bg-slate-50">Statement</a>
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">Expected (gross)</p>
                    <p id="tGross" class="text-lg font-bold text-slate-900 mt-1">—</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">Scholarships</p>
                    <p id="tSch" class="text-lg font-bold text-amber-700 mt-1">—</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">Expected (net)</p>
                    <p id="tNet" class="text-lg font-bold text-slate-900 mt-1">—</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">Collected (pivot)</p>
                    <p id="tCol" class="text-lg font-bold text-emerald-700 mt-1">—</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">Outstanding</p>
                    <p id="tOut" class="text-lg font-bold text-rose-700 mt-1">—</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">Advance balance</p>
                    <p id="tAdv" class="text-lg font-bold text-sky-700 mt-1">—</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <section class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                        <h3 class="font-bold text-slate-800">Fee assignments (all years)</h3>
                        <p class="text-xs text-slate-500">Per particular & academic year — sales, scholarship, paid, outstanding</p>
                    </div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-100 text-left text-xs font-bold text-slate-600">
                                <tr>
                                    <th class="px-3 py-2">Year</th>
                                    <th class="px-3 py-2">Particular</th>
                                    <th class="px-3 py-2 text-right">Sales</th>
                                    <th class="px-3 py-2 text-right">Scholar</th>
                                    <th class="px-3 py-2 text-right">Paid</th>
                                    <th class="px-3 py-2 text-right">Out</th>
                                    <th class="px-3 py-2">Deadline</th>
                                </tr>
                            </thead>
                            <tbody id="assignBody"></tbody>
                        </table>
                    </div>
                </section>

                <section class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                        <h3 class="font-bold text-slate-800">Scholarships</h3>
                    </div>
                    <div class="overflow-x-auto max-h-96">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-100 text-left text-xs font-bold text-slate-600">
                                <tr>
                                    <th class="px-3 py-2">Year</th>
                                    <th class="px-3 py-2">Particular</th>
                                    <th class="px-3 py-2 text-right">Forgiven</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2">Applied</th>
                                </tr>
                            </thead>
                            <tbody id="scholarBody"></tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex flex-wrap justify-between items-center gap-2">
                    <div>
                        <h3 class="font-bold text-slate-800">Vouchers & payments</h3>
                        <p class="text-xs text-slate-500">Up to 1,000 most recent rows</p>
                    </div>
                </div>
                <div class="overflow-x-auto max-h-[28rem]">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-100 text-left text-xs font-bold text-slate-600 sticky top-0">
                            <tr>
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Particular</th>
                                <th class="px-3 py-2">Book</th>
                                <th class="px-3 py-2 text-right">Debit</th>
                                <th class="px-3 py-2 text-right">Credit</th>
                                <th class="px-3 py-2">Note / Pay to</th>
                            </tr>
                        </thead>
                        <tbody id="voucherBody"></tbody>
                    </table>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                        <h3 class="font-bold text-slate-800">Suspense (linked to student)</h3>
                    </div>
                    <div class="overflow-x-auto max-h-64">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-100 text-left text-xs font-bold text-slate-600">
                                <tr>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2 text-right">Amount</th>
                                    <th class="px-3 py-2">Resolved</th>
                                    <th class="px-3 py-2">Description</th>
                                </tr>
                            </thead>
                            <tbody id="suspenseBody"></tbody>
                        </table>
                    </div>
                </section>
                <section class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                        <h3 class="font-bold text-slate-800">Recent SMS</h3>
                    </div>
                    <div class="overflow-x-auto max-h-64">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-100 text-left text-xs font-bold text-slate-600">
                                <tr>
                                    <th class="px-3 py-2">When</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2">Message</th>
                                </tr>
                            </thead>
                            <tbody id="smsBody"></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
(function () {
            const m = document.querySelector('meta[name="csrf-token"]');
            if (m?.content) axios.defaults.headers.common['X-CSRF-TOKEN'] = m.content;
        })();
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.withCredentials = true;

        const fmt = (n) => Number(n || 0).toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const esc = (s) => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');

        let searchTimer = null;
        let selectedStudentId = null;

        function resetSidebarChrome() {
            document.getElementById('sidebar')?.classList.add('-translate-x-full');
            const ov = document.getElementById('sidebar-overlay');
            if (ov) {
                ov.classList.add('hidden', 'pointer-events-none');
                ov.classList.remove('pointer-events-auto');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            resetSidebarChrome();

            const classSelect = document.getElementById('classSelect');
            const studentSelect = document.getElementById('studentSelect');
            const globalSearch = document.getElementById('globalSearch');
            const searchDropdown = document.getElementById('searchDropdown');

            classSelect?.addEventListener('change', async function () {
                const classId = this.value;
                if (!studentSelect) return;
                studentSelect.innerHTML = '<option value="">Select student…</option>';
                studentSelect.disabled = true;
                if (!classId) return;
                try {
                    const { data } = await axios.get('/api/students', { params: { class_id: classId, status: 'active' } });
                    const list = data.students || data;
                    if (!Array.isArray(list)) {
                        console.error('Unexpected students response', data);
                        alert('Could not load students');
                        return;
                    }
                    list.forEach(s => {
                        const o = document.createElement('option');
                        o.value = s.id;
                        o.textContent = `${s.name} (${s.student_reg_no})`;
                        studentSelect.appendChild(o);
                    });
                    studentSelect.disabled = false;
                } catch (e) {
                    alert('Could not load students');
                }
            });

            studentSelect?.addEventListener('change', function () {
                const id = parseInt(this.value, 10);
                if (!Number.isNaN(id) && id > 0) loadProfile(id);
            });

            globalSearch?.addEventListener('input', function () {
                clearTimeout(searchTimer);
                const q = this.value.trim();
                if (!searchDropdown) return;
                if (q.length < 2) {
                    searchDropdown.classList.add('hidden');
                    searchDropdown.innerHTML = '';
                    return;
                }
                searchTimer = setTimeout(async () => {
                    try {
                        const { data } = await axios.get('/api/students/search', { params: { q } });
                        const rows = Array.isArray(data) ? data : (data.students || []);
                        if (!rows.length) {
                            searchDropdown.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No matches</div>';
                            searchDropdown.classList.remove('hidden');
                            return;
                        }
                        searchDropdown.innerHTML = rows.map(s => `
                        <button type="button" class="w-full text-left px-4 py-3 hover:bg-indigo-50 border-b border-slate-100 last:border-0"
                            data-id="${s.id}">
                            <span class="font-semibold text-slate-800">${esc(s.name)}</span>
                            <span class="text-xs text-slate-500 block">${esc(s.student_reg_no)} · ${esc(s.class)}</span>
                        </button>
                    `).join('');
                        searchDropdown.querySelectorAll('button[data-id]').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const id = parseInt(btn.dataset.id, 10);
                                const line = btn.querySelector('.font-semibold')?.textContent?.trim() || '';
                                const sub = btn.querySelector('.text-xs')?.textContent?.trim() || '';
                                if (globalSearch) globalSearch.value = sub ? (line + ' — ' + sub) : line;
                                searchDropdown.classList.add('hidden');
                                loadProfile(id);
                            });
                        });
                        searchDropdown.classList.remove('hidden');
                    } catch (e) {
                        console.error(e);
                    }
                }, 280);
            });

            document.addEventListener('click', (e) => {
                if (!searchDropdown || !globalSearch) return;
                const t = e.target;
                if (searchDropdown.contains(t) || t === globalSearch) return;
                searchDropdown.classList.add('hidden');
            });

            void loadClasses();

            const params = new URLSearchParams(window.location.search);
            const sid = params.get('student_id');
            if (sid) {
                const id = parseInt(sid, 10);
                if (!Number.isNaN(id) && id > 0) loadProfile(id);
            }
        });

        async function loadClasses() {
            try {
                const { data } = await axios.get('/api/classes');
                const sel = document.getElementById('classSelect');
                if (!sel) return;
                const list = Array.isArray(data) ? data : (data.classes || []);
                if (!Array.isArray(list)) {
                    console.error('Unexpected classes response', data);
                    return;
                }
                list.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.id;
                    o.textContent = c.name;
                    sel.appendChild(o);
                });
            } catch (e) {
                console.error(e);
            }
        }

        async function loadProfile(studentId) {
            if (!studentId) return;
            selectedStudentId = studentId;
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('profilePanel').classList.remove('hidden');

            try {
                const { data } = await axios.get(`/api/students/${studentId}/full-profile`);
                renderProfile(data);
                const url = new URL(window.location.href);
                url.searchParams.set('student_id', studentId);
                window.history.replaceState({}, '', url);
            } catch (e) {
                alert('Failed to load profile: ' + (e.response?.data?.message || e.message));
            }
        }

        function renderProfile(data) {
            const s = data.student;
            document.getElementById('pName').textContent = s.name;
            document.getElementById('pMeta').textContent =
                `Reg: ${s.student_reg_no} · Class: ${s.school_class?.name || s.class || '—'} · Status: ${s.status || '—'}`;
            const contact = [];
            if (s.parent_phone_1) contact.push('Phone 1: ' + s.parent_phone_1);
            if (s.parent_phone_2) contact.push('Phone 2: ' + s.parent_phone_2);
            if (s.email) contact.push('Email: ' + s.email);
            document.getElementById('pContact').textContent = contact.join(' · ') || 'No contact on file';

            const L = data.links || {};
            document.getElementById('linkLedgerPdf').href = L.ledger_pdf || '#';
            document.getElementById('linkLedgerCsv').href = L.ledger_csv || '#';
            document.getElementById('linkInvoicePdf').href = L.invoice_pdf || '#';
            document.getElementById('linkStatement').href = L.student_statement || '#';

            const t = data.totals || {};
            document.getElementById('tGross').textContent = fmt(t.expected_gross);
            document.getElementById('tSch').textContent = fmt(t.scholarships);
            document.getElementById('tNet').textContent = fmt(t.expected_net);
            document.getElementById('tCol').textContent = fmt(t.collected);
            document.getElementById('tOut').textContent = fmt(t.outstanding);
            document.getElementById('tAdv').textContent = fmt(t.advance_balance);

            const ab = document.getElementById('assignBody');
            ab.innerHTML = (data.assignments || []).map(a => `
                <tr class="border-t border-slate-100 hover:bg-slate-50">
                    <td class="px-3 py-2">${esc(a.academic_year_name || '—')}</td>
                    <td class="px-3 py-2 font-medium">${esc(a.particular_name)}</td>
                    <td class="px-3 py-2 text-right">${fmt(a.sales)}</td>
                    <td class="px-3 py-2 text-right text-amber-700">${fmt(a.scholarship_forgiven)}</td>
                    <td class="px-3 py-2 text-right text-emerald-700">${fmt(a.credit)}</td>
                    <td class="px-3 py-2 text-right text-rose-700">${fmt(a.outstanding)}</td>
                    <td class="px-3 py-2 text-xs">${a.deadline ? esc(String(a.deadline).slice(0, 10)) : '—'}</td>
                </tr>
            `).join('') || '<tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No assignments</td></tr>';

            const sb = document.getElementById('scholarBody');
            sb.innerHTML = (data.scholarships || []).map(x => `
                <tr class="border-t border-slate-100">
                    <td class="px-3 py-2">${esc(x.academic_year?.name || '—')}</td>
                    <td class="px-3 py-2">${esc(x.particular?.name || '—')}</td>
                    <td class="px-3 py-2 text-right">${fmt(x.forgiven_amount)}</td>
                    <td class="px-3 py-2">${x.is_active ? 'Active' : 'Inactive'}</td>
                    <td class="px-3 py-2 text-xs">${x.applied_date ? esc(String(x.applied_date).slice(0, 10)) : '—'}</td>
                </tr>
            `).join('') || '<tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No scholarships</td></tr>';

            const vb = document.getElementById('voucherBody');
            vb.innerHTML = (data.vouchers || []).map(v => `
                <tr class="border-t border-slate-100 hover:bg-slate-50">
                    <td class="px-3 py-2 whitespace-nowrap">${v.date ? esc(String(v.date).slice(0, 10)) : '—'}</td>
                    <td class="px-3 py-2">${esc(v.voucher_type)}</td>
                    <td class="px-3 py-2">${esc(v.particular?.name || '—')}</td>
                    <td class="px-3 py-2">${esc(v.book?.name || '—')}</td>
                    <td class="px-3 py-2 text-right">${fmt(v.debit)}</td>
                    <td class="px-3 py-2 text-right">${fmt(v.credit)}</td>
                    <td class="px-3 py-2 text-xs max-w-xs truncate" title="${esc(v.payment_by_receipt_to || v.notes || '')}">${esc(v.payment_by_receipt_to || v.notes || '—')}</td>
                </tr>
            `).join('') || '<tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No vouchers</td></tr>';

            const xb = document.getElementById('suspenseBody');
            xb.innerHTML = (data.suspense_accounts || []).map(x => `
                <tr class="border-t border-slate-100">
                    <td class="px-3 py-2">${x.date ? esc(String(x.date).slice(0, 10)) : '—'}</td>
                    <td class="px-3 py-2 text-right">${fmt(x.amount)}</td>
                    <td class="px-3 py-2">${x.resolved ? 'Yes' : 'No'}</td>
                    <td class="px-3 py-2 text-xs">${esc(x.description)}</td>
                </tr>
            `).join('') || '<tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">None</td></tr>';

            const mb = document.getElementById('smsBody');
            mb.innerHTML = (data.sms_logs || []).map(m => {
                const when = m.sent_at || m.created_at;
                return `
                <tr class="border-t border-slate-100">
                    <td class="px-3 py-2 text-xs whitespace-nowrap">${when ? esc(String(when).replace('T', ' ').slice(0, 19)) : '—'}</td>
                    <td class="px-3 py-2">${esc(m.status || '—')}</td>
                    <td class="px-3 py-2 text-xs max-w-md truncate" title="${esc(m.message || '')}">${esc(m.message || '—')}</td>
                </tr>
            `}).join('') || '<tr><td colspan="3" class="px-3 py-6 text-center text-slate-500">No SMS log</td></tr>';
        }
    </script>
@endpush
