@extends('layouts.accountant')

@section('title', 'Books — Darasa Finance')
@section('page_title', 'Books')

@section('content')
    <div class="w-full space-y-6">
        <div>
            <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <h2 class="text-xl font-semibold text-slate-900 md:text-2xl">Books</h2>
                <button type="button" onclick="showCreateBookForm()" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Create book
                </button>
            </div>
            <div id="booksList" class="mt-4"></div>
            <div id="bookFormContainer"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
const API_BASE = '/api';
        let allBooks = [];

        function escapeHtml(s) {
            if (s === null || s === undefined) return '';
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function tierTableRowsHtml(tiers) {
            const rows = (tiers && tiers.length) ? tiers : [{ amount_from: '', amount_to: '', fee_amount: '' }];
            return rows.map(t => `
                <tr data-tier-row="1">
                    <td class="p-2"><input type="number" step="0.01" min="0" class="w-full border rounded px-2 py-1 tier-from" value="${t.amount_from ?? ''}" placeholder="From"></td>
                    <td class="p-2"><input type="number" step="0.01" min="0" class="w-full border rounded px-2 py-1 tier-to" value="${t.amount_to ?? ''}" placeholder="To (empty = no max)"></td>
                    <td class="p-2"><input type="number" step="0.01" min="0" class="w-full border rounded px-2 py-1 tier-fee" value="${t.fee_amount ?? ''}" placeholder="Fee"></td>
                    <td class="p-2"><button type="button" class="text-red-600 text-sm font-bold" onclick="this.closest('tr').remove()">✕</button></td>
                </tr>`).join('');
        }

        function collectBankFeeTiers(tbodyId) {
            const tbody = document.getElementById(tbodyId);
            if (!tbody) return [];
            const out = [];
            tbody.querySelectorAll('tr[data-tier-row]').forEach(tr => {
                const from = tr.querySelector('.tier-from')?.value;
                const to = tr.querySelector('.tier-to')?.value;
                const fee = tr.querySelector('.tier-fee')?.value;
                if (from === '' || from == null) return;
                out.push({
                    amount_from: parseFloat(from),
                    amount_to: (to === '' || to == null) ? null : parseFloat(to),
                    fee_amount: parseFloat(fee || 0),
                });
            });
            return out;
        }

        function addTierRow(tbodyId) {
            const tbody = document.getElementById(tbodyId);
            if (!tbody) return;
            const tr = document.createElement('tr');
            tr.setAttribute('data-tier-row', '1');
            tr.innerHTML = `
                <td class="p-2"><input type="number" step="0.01" min="0" class="w-full border rounded px-2 py-1 tier-from" placeholder="From"></td>
                <td class="p-2"><input type="number" step="0.01" min="0" class="w-full border rounded px-2 py-1 tier-to" placeholder="To (empty = no max)"></td>
                <td class="p-2"><input type="number" step="0.01" min="0" class="w-full border rounded px-2 py-1 tier-fee" placeholder="Fee"></td>
                <td class="p-2"><button type="button" class="text-red-600 text-sm font-bold" onclick="this.closest('tr').remove()">✕</button></td>
            `;
            tbody.appendChild(tr);
        }

        // Configure axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.withCredentials = true;

        // Load books on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadBooks();
        });

        async function loadBooks() {
            try {
                const response = await axios.get(`${API_BASE}/books`);
                allBooks = response.data;

                let html = '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
                allBooks.forEach(book => {
                    html += `
                        <div class="border-2 rounded-lg p-4 ${book.is_cash_book ? 'border-slate-200 bg-slate-50' : 'border-slate-200 bg-slate-50'}">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-bold text-lg">${book.name}</h3>
                                    <p class="text-sm text-gray-600">${book.bank_account_number || 'No Account Number'}</p>
                                    <p class="text-xs font-semibold mt-2 ${book.is_cash_book ? 'text-slate-700' : 'text-slate-800'}">
                                        ${book.is_cash_book ? '💵 Cash Book' : '🏦 Bank Account'}
                                    </p>
                                    
                                </div>
                                ${!book.is_cash_book ? `
                                    <div class="flex gap-2">
                                        <button onclick='showBookFeesAndCuts(${JSON.stringify(book)})' class="bg-blue-700 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition">
                                            Fees/Cuts
                                        </button>
                                        <button onclick='showEditBookForm(${JSON.stringify(book)})' class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition">
                                            Edit
                                        </button>
                                        <button onclick="deleteBook(${book.id})" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition">
                                            Delete
                                        </button>
                                    </div>
                                ` : '<span class="text-xs text-slate-700 font-bold">Protected</span>'}
                            </div>

                            <!-- Deposit/Withdrawal Buttons -->
                            <div class="border-t pt-3 mt-3 flex gap-2 flex-wrap">
                                <button onclick="showDepositForm(${book.id}, '${book.name}')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-bold transition flex-1">
                                    ➕ Deposit
                                </button>
                                <button onclick="showWithdrawForm(${book.id}, '${book.name}')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm font-bold transition flex-1">
                                    ➖ Withdraw
                                </button>
                                <button onclick="showTransactionHistory(${book.id}, '${book.name}')" class="bg-blue-700 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm font-bold transition flex-1">
                                    📜 History
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('booksList').innerHTML = html;
            } catch (error) {
                alert('Error loading books: ' + error.message);
            }
        }

        async function showCreateBookForm() {
            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div class="bg-white rounded-lg p-8 max-w-2xl w-full shadow-2xl my-8">
                        <h3 class="text-2xl font-bold mb-6 text-slate-800">Create New Book</h3>
                        <form onsubmit="createBook(event)" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold mb-2">Book Name *</label>
                                <input type="text" id="bookName" required
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-slate-500 focus:outline-none"
                                    placeholder="e.g., NMB 002, CRDB 800">
                                <p class="text-xs text-gray-500 mt-1">Include last 3 digits of account number</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Bank Account Number *</label>
                                <input type="text" id="bookAccount" required
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-slate-500 focus:outline-none"
                                    placeholder="e.g., 1234567002">
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition">
                                    💾 Save Book
                                </button>
                                <button type="button" onclick="closeBookForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
                                    ✖️ Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('bookFormContainer').innerHTML = formHtml;
        }

        function closeBookForm() {
            document.getElementById('bookFormContainer').innerHTML = '';
        }

        async function createBook(event) {
            event.preventDefault();
            const name = document.getElementById('bookName').value;
            const accountNumber = document.getElementById('bookAccount').value;

            try {
                await axios.post(`${API_BASE}/books`, {
                    name: name,
                    bank_account_number: accountNumber,
                });
                alert('✅ Book created successfully!');
                closeBookForm();
                loadBooks();
            } catch (error) {
                console.error('Create book failed', error);
                const msg = (error.response?.data?.message)
                    || (error.response?.data?.errors ? JSON.stringify(error.response.data.errors) : null)
                    || error.message;
                alert('❌ Error: ' + msg);
            }
        }

        // Ensure onclick handlers resolve in all browser contexts
        window.showCreateBookForm = showCreateBookForm;
        window.createBook = createBook;
        window.closeBookForm = closeBookForm;

        // ===== Fee categories + monthly cuts management =====
        async function showBookFeesAndCuts(book) {
            const bookId = book.id;
            const [catsRes, cutsRes] = await Promise.all([
                axios.get(`${API_BASE}/books/${bookId}/fee-categories`).catch(() => ({ data: [] })),
                axios.get(`${API_BASE}/books/${bookId}/monthly-cuts`).catch(() => ({ data: [] })),
            ]);
            const categories = Array.isArray(catsRes.data) ? catsRes.data : [];
            const cuts = Array.isArray(cutsRes.data) ? cutsRes.data : [];

            const catRows = categories.map(c => `
                <div class="p-3 border rounded bg-white flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="font-bold">${escapeHtml(c.name)} ${c.code ? `<span class="text-xs text-gray-500">(${escapeHtml(c.code)})</span>` : ''}</div>
                        <div class="text-xs text-gray-600 mt-1">Active: ${c.is_active ? 'Yes' : 'No'}</div>
                        <div class="text-xs text-gray-600 mt-1">Tiers: ${(c.tiers || []).length}</div>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded border border-slate-300 bg-white px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-50" onclick='showEditFeeCategory(${bookId}, ${JSON.stringify(c)})'>Edit</button>
                        <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm" onclick="deleteFeeCategory(${bookId}, ${c.id})">Delete</button>
                    </div>
                </div>
            `).join('');

            const cutRows = cuts.map(c => `
                <div class="p-3 border rounded bg-white flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="font-bold">${escapeHtml(c.name)}</div>
                        <div class="text-xs text-gray-600 mt-1">Active: ${c.is_active ? 'Yes' : 'No'} • Day: ${c.day_of_month} • Amount: ${formatTSh(c.amount)}</div>
                        <div class="text-xs text-gray-600 mt-1">${escapeHtml(c.notes || '')}</div>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded border border-slate-300 bg-white px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-50" onclick='showEditMonthlyCut(${bookId}, ${JSON.stringify(c)})'>Edit</button>
                        <button class="bg-blue-700 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm" onclick="applyMonthlyCut(${bookId}, ${c.id})">Apply now</button>
                        <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm" onclick="deleteMonthlyCut(${bookId}, ${c.id})">Delete</button>
                    </div>
                </div>
            `).join('');

            const modal = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div class="bg-white rounded-lg p-6 max-w-4xl w-full shadow-2xl my-8">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900">Fees & Monthly Cuts</h3>
                                <p class="text-sm text-gray-600">Book: <strong>${escapeHtml(book.name)}</strong></p>
                            </div>
                            <button onclick="closeBookForm()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="border rounded-lg p-4 bg-slate-50">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="font-bold text-slate-800">Transaction fee categories</h4>
                                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm" onclick="showCreateFeeCategory(${bookId})">+ Add</button>
                                </div>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    ${catRows || '<p class="text-sm text-gray-600">No categories yet. Add one (e.g., bank→bank, bank→mobile money).</p>'}
                                </div>
                            </div>

                            <div class="border rounded-lg p-4 bg-amber-50">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="font-bold text-amber-800">Monthly cuts</h4>
                                    <button class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1 rounded text-sm" onclick="showCreateMonthlyCut(${bookId})">+ Add</button>
                                </div>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    ${cutRows || '<p class="text-sm text-gray-600">No monthly cuts yet. Add one (e.g., monthly service charge).</p>'}
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 text-right">
                            <button onclick="closeBookForm()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-5 py-2 rounded font-bold">Close</button>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('bookFormContainer').innerHTML = modal;
        }

        function feeCategoryTierInputsHtml(tiers) {
            const rows = (tiers && tiers.length) ? tiers : [{ amount_from: '', amount_to: '', fee_amount: '' }];
            return rows.map(t => `
                <tr data-tier-row="1">
                    <td class="p-2"><input type="number" step="0.01" min="0" class="w-full border rounded px-2 py-1 tier-from" value="${t.amount_from ?? ''}" placeholder="From"></td>
                    <td class="p-2"><input type="number" step="0.01" min="0" class="w-full border rounded px-2 py-1 tier-to" value="${t.amount_to ?? ''}" placeholder="To (blank=open)"></td>
                    <td class="p-2"><input type="number" step="0.01" min="0" class="w-full border rounded px-2 py-1 tier-fee" value="${t.fee_amount ?? ''}" placeholder="Fee"></td>
                    <td class="p-2"><button type="button" class="text-red-600 text-sm font-bold" onclick="this.closest('tr').remove()">✕</button></td>
                </tr>`).join('');
        }

        function showCreateFeeCategory(bookId) {
            const html = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div class="bg-white rounded-lg p-6 max-w-2xl w-full shadow-2xl my-8">
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Add fee category</h3>
                        <form onsubmit="createFeeCategory(event, ${bookId})" class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-bold mb-1">Name *</label>
                                    <input id="fc_name" required class="w-full border-2 border-gray-300 rounded px-3 py-2" placeholder="e.g., Bank→Mobile Money">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1">Code</label>
                                    <input id="fc_code" class="w-full border-2 border-gray-300 rounded px-3 py-2" placeholder="e.g., B2M">
                                </div>
                            </div>
                            <div class="border rounded p-3">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-bold">Fee tiers (amount → fee)</span>
                                    <button type="button" class="text-sm bg-slate-100 text-slate-800 px-2 py-1 rounded" onclick="addTierRow('fc_tbody')">+ Add range</button>
                                </div>
                                <table class="w-full text-sm border border-gray-200 rounded overflow-hidden">
                                    <thead class="bg-gray-100"><tr>
                                        <th class="p-2 text-left">From (≥)</th>
                                        <th class="p-2 text-left">To (≤)</th>
                                        <th class="p-2 text-left">Fee</th>
                                        <th class="p-2"></th>
                                    </tr></thead>
                                    <tbody id="fc_tbody">${feeCategoryTierInputsHtml([])}</tbody>
                                </table>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-bold">Save</button>
                                <button type="button" onclick="closeBookForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded font-bold">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('bookFormContainer').innerHTML = html;
        }

        async function createFeeCategory(event, bookId) {
            event.preventDefault();
            const tiers = collectBankFeeTiers('fc_tbody');
            await axios.post(`${API_BASE}/books/${bookId}/fee-categories`, {
                name: document.getElementById('fc_name').value,
                code: document.getElementById('fc_code').value || null,
                is_active: true,
                tiers,
            });
            alert('✅ Fee category saved');
            closeBookForm();
            loadBooks();
        }

        function showEditFeeCategory(bookId, cat) {
            const html = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div class="bg-white rounded-lg p-6 max-w-2xl w-full shadow-2xl my-8">
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Edit fee category</h3>
                        <form onsubmit="updateFeeCategory(event, ${bookId}, ${cat.id})" class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-bold mb-1">Name *</label>
                                    <input id="fc_name" required class="w-full border-2 border-gray-300 rounded px-3 py-2" value="${escapeHtml(cat.name)}">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1">Code</label>
                                    <input id="fc_code" class="w-full border-2 border-gray-300 rounded px-3 py-2" value="${escapeHtml(cat.code || '')}">
                                </div>
                            </div>
                            <div class="pt-2">
                                <label class="flex items-center gap-2 font-bold text-gray-800">
                                    <input type="checkbox" id="fc_active" class="rounded border-gray-400" ${cat.is_active ? 'checked' : ''}>
                                    Active
                                </label>
                            </div>
                            <div class="border rounded p-3">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-bold">Fee tiers (amount → fee)</span>
                                    <button type="button" class="text-sm bg-slate-100 text-slate-800 px-2 py-1 rounded" onclick="addTierRow('fc_tbody')">+ Add range</button>
                                </div>
                                <table class="w-full text-sm border border-gray-200 rounded overflow-hidden">
                                    <thead class="bg-gray-100"><tr>
                                        <th class="p-2 text-left">From (≥)</th>
                                        <th class="p-2 text-left">To (≤)</th>
                                        <th class="p-2 text-left">Fee</th>
                                        <th class="p-2"></th>
                                    </tr></thead>
                                    <tbody id="fc_tbody">${feeCategoryTierInputsHtml(cat.tiers || [])}</tbody>
                                </table>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-bold">Update</button>
                                <button type="button" onclick="closeBookForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded font-bold">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('bookFormContainer').innerHTML = html;
        }

        async function updateFeeCategory(event, bookId, categoryId) {
            event.preventDefault();
            const tiers = collectBankFeeTiers('fc_tbody');
            await axios.put(`${API_BASE}/books/${bookId}/fee-categories/${categoryId}`, {
                name: document.getElementById('fc_name').value,
                code: document.getElementById('fc_code').value || null,
                is_active: document.getElementById('fc_active').checked,
                tiers,
            });
            alert('✅ Fee category updated');
            closeBookForm();
            loadBooks();
        }

        async function deleteFeeCategory(bookId, categoryId) {
            if (!confirm('Delete this fee category?')) return;
            await axios.delete(`${API_BASE}/books/${bookId}/fee-categories/${categoryId}`);
            alert('✅ Deleted');
            closeBookForm();
            loadBooks();
        }

        function showCreateMonthlyCut(bookId) {
            const html = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div class="bg-white rounded-lg p-6 max-w-xl w-full shadow-2xl my-8">
                        <h3 class="text-xl font-bold text-amber-800 mb-4">Add monthly cut</h3>
                        <form onsubmit="createMonthlyCut(event, ${bookId})" class="space-y-3">
                            <div>
                                <label class="block text-sm font-bold mb-1">Name *</label>
                                <input id="mc_name" required class="w-full border-2 border-gray-300 rounded px-3 py-2" placeholder="e.g., Monthly service charge">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-bold mb-1">Day of month (1-28) *</label>
                                    <input id="mc_day" type="number" min="1" max="28" required class="w-full border-2 border-gray-300 rounded px-3 py-2" value="1">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1">Amount *</label>
                                    <input id="mc_amount" type="number" step="0.01" min="0.01" required class="w-full border-2 border-gray-300 rounded px-3 py-2" placeholder="0.00">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-1">Notes</label>
                                <input id="mc_notes" class="w-full border-2 border-gray-300 rounded px-3 py-2" placeholder="Optional">
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded font-bold">Save</button>
                                <button type="button" onclick="closeBookForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded font-bold">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('bookFormContainer').innerHTML = html;
        }

        async function createMonthlyCut(event, bookId) {
            event.preventDefault();
            await axios.post(`${API_BASE}/books/${bookId}/monthly-cuts`, {
                name: document.getElementById('mc_name').value,
                is_active: true,
                day_of_month: parseInt(document.getElementById('mc_day').value),
                amount: parseFloat(document.getElementById('mc_amount').value),
                notes: document.getElementById('mc_notes').value || null,
            });
            alert('✅ Monthly cut saved');
            closeBookForm();
            loadBooks();
        }

        function showEditMonthlyCut(bookId, cut) {
            const html = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div class="bg-white rounded-lg p-6 max-w-xl w-full shadow-2xl my-8">
                        <h3 class="text-xl font-bold text-amber-800 mb-4">Edit monthly cut</h3>
                        <form onsubmit="updateMonthlyCut(event, ${bookId}, ${cut.id})" class="space-y-3">
                            <div>
                                <label class="block text-sm font-bold mb-1">Name *</label>
                                <input id="mc_name" required class="w-full border-2 border-gray-300 rounded px-3 py-2" value="${escapeHtml(cut.name)}">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-bold mb-1">Day of month (1-28) *</label>
                                    <input id="mc_day" type="number" min="1" max="28" required class="w-full border-2 border-gray-300 rounded px-3 py-2" value="${cut.day_of_month}">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1">Amount *</label>
                                    <input id="mc_amount" type="number" step="0.01" min="0.01" required class="w-full border-2 border-gray-300 rounded px-3 py-2" value="${cut.amount}">
                                </div>
                            </div>
                            <div class="pt-2">
                                <label class="flex items-center gap-2 font-bold text-gray-800">
                                    <input type="checkbox" id="mc_active" class="rounded border-gray-400" ${cut.is_active ? 'checked' : ''}>
                                    Active
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-1">Notes</label>
                                <input id="mc_notes" class="w-full border-2 border-gray-300 rounded px-3 py-2" value="${escapeHtml(cut.notes || '')}">
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded font-bold">Update</button>
                                <button type="button" onclick="closeBookForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded font-bold">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('bookFormContainer').innerHTML = html;
        }

        async function updateMonthlyCut(event, bookId, cutId) {
            event.preventDefault();
            await axios.put(`${API_BASE}/books/${bookId}/monthly-cuts/${cutId}`, {
                name: document.getElementById('mc_name').value,
                is_active: document.getElementById('mc_active').checked,
                day_of_month: parseInt(document.getElementById('mc_day').value),
                amount: parseFloat(document.getElementById('mc_amount').value),
                notes: document.getElementById('mc_notes').value || null,
            });
            alert('✅ Monthly cut updated');
            closeBookForm();
            loadBooks();
        }

        async function deleteMonthlyCut(bookId, cutId) {
            if (!confirm('Delete this monthly cut?')) return;
            await axios.delete(`${API_BASE}/books/${bookId}/monthly-cuts/${cutId}`);
            alert('✅ Deleted');
            closeBookForm();
            loadBooks();
        }

        async function applyMonthlyCut(bookId, cutId) {
            const date = prompt('Apply cut for date (YYYY-MM-DD). Leave empty for today:', '');
            await axios.post(`${API_BASE}/books/${bookId}/monthly-cuts/${cutId}/apply`, {
                date: date || null
            });
            alert('✅ Cut applied (check book ledger)');
            closeBookForm();
            loadBooks();
        }

        window.showBookFeesAndCuts = showBookFeesAndCuts;
        window.showCreateFeeCategory = showCreateFeeCategory;
        window.createFeeCategory = createFeeCategory;
        window.showEditFeeCategory = showEditFeeCategory;
        window.updateFeeCategory = updateFeeCategory;
        window.deleteFeeCategory = deleteFeeCategory;
        window.showCreateMonthlyCut = showCreateMonthlyCut;
        window.createMonthlyCut = createMonthlyCut;
        window.showEditMonthlyCut = showEditMonthlyCut;
        window.updateMonthlyCut = updateMonthlyCut;
        window.deleteMonthlyCut = deleteMonthlyCut;
        window.applyMonthlyCut = applyMonthlyCut;

        async function showEditBookForm(book) {
            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div class="bg-white rounded-lg p-8 max-w-2xl w-full shadow-2xl my-8">
                        <h3 class="text-2xl font-bold mb-6 text-slate-800">Edit Book</h3>
                        <form onsubmit="updateBook(event, ${book.id})" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold mb-2">Book Name *</label>
                                <input type="text" id="editBookName" required
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-slate-500 focus:outline-none"
                                    placeholder="e.g., NMB 002, CRDB 800"
                                    value="${String(book.name ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')}">
                                <p class="text-xs text-gray-500 mt-1">Include last 3 digits of account number</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Bank Account Number *</label>
                                <input type="text" id="editBookAccount" required
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-slate-500 focus:outline-none"
                                    placeholder="e.g., 1234567002"
                                    value="${String(book.bank_account_number ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')}">
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition">
                                    💾 Update Book
                                </button>
                                <button type="button" onclick="closeBookForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
                                    ✖️ Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('bookFormContainer').innerHTML = formHtml;
        }

        async function updateBook(event, id) {
            event.preventDefault();
            const name = document.getElementById('editBookName').value;
            const accountNumber = document.getElementById('editBookAccount').value;

            try {
                await axios.put(`${API_BASE}/books/${id}`, {
                    name: name,
                    bank_account_number: accountNumber,
                });
                alert('✅ Book updated successfully!');
                closeBookForm();
                loadBooks();
            } catch (error) {
                const msg = error.response?.data?.message || (error.response?.data?.errors
                    ? JSON.stringify(error.response.data.errors)
                    : error.message);
                alert('❌ Error: ' + msg);
            }
        }

        async function deleteBook(id) {
            if (confirm('⚠️ Are you sure you want to delete this book?')) {
                try {
                    await axios.delete(`${API_BASE}/books/${id}`);
                    alert('✅ Book deleted successfully!');
                    loadBooks();
                } catch (error) {
                    alert('❌ Error: ' + (error.response?.data?.message || error.message));
                }
            }
        }

        // Format amount in Tanzania Shillings
        function formatTSh(amount) {
            return 'TSh ' + parseFloat(amount || 0).toLocaleString('en-TZ', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
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

        function showDepositForm(bookId, bookName) {
            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-8 max-w-lg w-full shadow-2xl">
                        <h3 class="text-2xl font-bold mb-2 text-slate-700">➕ Bank Deposit</h3>
                        <p class="text-gray-600 mb-4">Book: <strong>${bookName}</strong></p>
                        <form onsubmit="submitDeposit(event, ${bookId})" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold mb-2">Amount (TSh) *</label>
                                    <input type="text" id="depositAmount" required
                                        class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-slate-500 focus:outline-none"
                                        placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-2">Date *</label>
                                    <input type="date" id="depositDate" required
                                        class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-slate-500 focus:outline-none"
                                        value="${new Date().toISOString().split('T')[0]}">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Reference Number</label>
                                <input type="text" id="depositRef"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-slate-500 focus:outline-none"
                                    placeholder="e.g., Receipt #, Check #">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Short Notes (shown in ledger)</label>
                                <input type="text" id="depositShortNotes"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-slate-500 focus:outline-none"
                                    placeholder="e.g., School fundraiser deposit" maxlength="255">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Full Details (optional)</label>
                                <textarea id="depositFullDetails" rows="3"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-slate-500 focus:outline-none"
                                    placeholder="Detailed description of the deposit source..."></textarea>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition">
                                    💾 Record Deposit
                                </button>
                                <button type="button" onclick="closeBookForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
                                    ✖️ Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('bookFormContainer').innerHTML = formHtml;
            attachMoneyFormatting('depositAmount');
        }

        async function submitDeposit(event, bookId) {
            event.preventDefault();
            try {
                await axios.post(`${API_BASE}/book-transactions/deposit`, {
                    book_id: bookId,
                    amount: parseMoneyInput(document.getElementById('depositAmount').value),
                    transaction_date: document.getElementById('depositDate').value,
                    reference_number: document.getElementById('depositRef').value || null,
                    short_notes: document.getElementById('depositShortNotes').value || null,
                    full_details: document.getElementById('depositFullDetails').value || null
                });
                alert('✅ Deposit recorded successfully!');
                closeBookForm();
            } catch (error) {
                alert('❌ Error: ' + (error.response?.data?.error || error.message));
            }
        }

        function showWithdrawForm(bookId, bookName) {
            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-8 max-w-lg w-full shadow-2xl">
                        <h3 class="text-2xl font-bold mb-2 text-red-600">➖ Bank Withdrawal</h3>
                        <p class="text-gray-600 mb-4">Book: <strong>${bookName}</strong></p>
                        <form onsubmit="submitWithdrawal(event, ${bookId})" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold mb-2">Amount (TSh) *</label>
                                    <input type="text" id="withdrawAmount" required
                                        class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-red-500 focus:outline-none"
                                        placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-2">Date *</label>
                                    <input type="date" id="withdrawDate" required
                                        class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-red-500 focus:outline-none"
                                        value="${new Date().toISOString().split('T')[0]}">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Transaction Fee Category (Optional)</label>
                                <select id="withdrawFeeCategory" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                                    <option value="">-- No fee category --</option>
                                </select>
                                <p class="text-xs text-gray-600 mt-1">Select category to apply the correct fee (bank→bank, bank→mobile, etc.).</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Reference Number</label>
                                <input type="text" id="withdrawRef"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-red-500 focus:outline-none"
                                    placeholder="e.g., Cheque #, Transfer ID">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Short Notes (shown in ledger)</label>
                                <input type="text" id="withdrawShortNotes"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-red-500 focus:outline-none"
                                    placeholder="e.g., Cash withdrawal for expenses" maxlength="255">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Full Details (optional)</label>
                                <textarea id="withdrawFullDetails" rows="3"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-red-500 focus:outline-none"
                                    placeholder="Detailed description of the withdrawal purpose..."></textarea>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="submit" class="flex-1 bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-bold transition">
                                    💾 Record Withdrawal
                                </button>
                                <button type="button" onclick="closeBookForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
                                    ✖️ Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('bookFormContainer').innerHTML = formHtml;
            attachMoneyFormatting('withdrawAmount');

            // Load fee categories for this book (if any)
            axios.get(`${API_BASE}/books/${bookId}/fee-categories`)
                .then(res => {
                    const list = Array.isArray(res.data) ? res.data : [];
                    const sel = document.getElementById('withdrawFeeCategory');
                    if (!sel) return;
                    list.filter(c => c.is_active).forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.code ? `${c.name} (${c.code})` : c.name;
                        sel.appendChild(opt);
                    });
                })
                .catch(e => console.error('Failed to load fee categories', e));
        }

        async function submitWithdrawal(event, bookId) {
            event.preventDefault();
            try {
                await axios.post(`${API_BASE}/book-transactions/withdrawal`, {
                    book_id: bookId,
                    amount: parseMoneyInput(document.getElementById('withdrawAmount').value),
                    transaction_date: document.getElementById('withdrawDate').value,
                    fee_category_id: document.getElementById('withdrawFeeCategory')?.value || null,
                    reference_number: document.getElementById('withdrawRef').value || null,
                    short_notes: document.getElementById('withdrawShortNotes').value || null,
                    full_details: document.getElementById('withdrawFullDetails').value || null
                });
                alert('✅ Withdrawal recorded successfully!');
                closeBookForm();
            } catch (error) {
                alert('❌ Error: ' + (error.response?.data?.error || error.message));
            }
        }

        async function showTransactionHistory(bookId, bookName) {
            try {
                const response = await axios.get(`${API_BASE}/book-transactions/${bookId}`);
                const data = response.data;

                let html = `
                    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-auto">
                        <div class="bg-white rounded-lg p-8 max-w-4xl w-full shadow-2xl my-8 max-h-[90vh] overflow-y-auto">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="text-2xl font-bold text-slate-800">📜 Transaction History</h3>
                                    <p class="text-gray-600">Book: <strong>${bookName}</strong></p>
                                </div>
                                <button onclick="closeBookForm()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                            </div>

                            <!-- Summary Cards -->
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center">
                                    <p class="text-sm text-gray-600">Total Deposits</p>
                                    <p class="text-xl font-bold text-slate-900">${formatTSh(data.summary.total_deposits)}</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white p-4 text-center">
                                    <p class="text-sm text-gray-600">Total Withdrawals</p>
                                    <p class="text-xl font-bold text-slate-900">${formatTSh(data.summary.total_withdrawals)}</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white p-4 text-center">
                                    <p class="text-sm text-gray-600">Net Amount</p>
                                    <p class="text-xl font-bold ${data.summary.net_amount >= 0 ? 'text-slate-900' : 'text-red-600'}">${formatTSh(data.summary.net_amount)}</p>
                                </div>
                            </div>

                            <!-- Transaction Table -->
                            <div class="overflow-x-auto">
                                <table class="w-full border-2 border-gray-300 bg-white">
                                    <thead class="bg-slate-100">
                                        <tr>
                                            <th class="p-3 text-left">Date</th>
                                            <th class="p-3 text-left">Type</th>
                                            <th class="p-3 text-right">Amount</th>
                                            <th class="p-3 text-left">Reference</th>
                                            <th class="p-3 text-left">Notes</th>
                                            <th class="p-3 text-left">Details</th>
                                            <th class="p-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;

                if (data.transactions.data.length === 0) {
                    html += `<tr><td colspan="7" class="p-8 text-center text-gray-500">No transactions found</td></tr>`;
                } else {
                    data.transactions.data.forEach(txn => {
                        const isDeposit = txn.transaction_type === 'deposit';
                        const isCancelled = !!txn.cancelled_at;
                        html += `
                            <tr class="border-t hover:bg-slate-50 ${isCancelled ? 'opacity-60' : ''}">
                                <td class="p-3">${txn.transaction_date}</td>
                                <td class="p-3">
                                    <span class="rounded px-2 py-1 text-xs font-bold ${isDeposit ? 'bg-slate-200 text-slate-800' : 'bg-red-50 text-red-800 ring-1 ring-red-200'}">
                                        ${isDeposit ? 'Deposit' : 'Withdrawal'}
                                    </span>
                                    ${isCancelled ? '<span class="ml-2 px-2 py-1 rounded text-xs font-bold bg-gray-200 text-gray-700">CANCELLED</span>' : ''}
                                </td>
                                <td class="p-3 text-right font-bold ${isDeposit ? 'text-slate-700' : 'text-red-600'}">
                                    ${formatTSh(txn.amount)}
                                </td>
                                <td class="p-3 font-mono text-sm">${txn.reference_number || '-'}</td>
                                <td class="p-3 text-sm">${txn.short_notes || '-'}</td>
                                <td class="p-3 text-xs text-gray-600 max-w-xs truncate">${txn.full_details || '-'}</td>
                                <td class="p-3 text-center">
                                    ${isCancelled ? '-' : `
                                        <div class="flex flex-col gap-2 items-center">
                                            <button onclick="cancelTransaction(${txn.id}, ${bookId}, '${bookName}')" class="rounded border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                Cancel
                                            </button>
                                            <button onclick="deleteTransaction(${txn.id}, ${bookId}, '${bookName}')" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">
                                                Delete
                                            </button>
                                        </div>
                                    `}
                                </td>
                            </tr>
                        `;
                    });
                }

                html += `
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 text-center">
                                <button onclick="closeBookForm()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-lg font-bold transition">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                document.getElementById('bookFormContainer').innerHTML = html;
            } catch (error) {
                alert('❌ Error loading history: ' + (error.response?.data?.error || error.message));
            }
        }

        async function deleteTransaction(txnId, bookId, bookName) {
            if (confirm('⚠️ Are you sure you want to delete this transaction? This will also remove the associated ledger entry.')) {
                try {
                    await axios.delete(`${API_BASE}/book-transactions/${txnId}`);
                    alert('✅ Transaction deleted successfully!');
                    showTransactionHistory(bookId, bookName); // Refresh history
                } catch (error) {
                    alert('❌ Error: ' + (error.response?.data?.error || error.message));
                }
            }
        }

        async function cancelTransaction(txnId, bookId, bookName) {
            const reason = prompt('Reason for canceling this transaction (required):', '');
            if (!reason) return;
            try {
                await axios.post(`${API_BASE}/book-transactions/${txnId}/cancel`, { reason });
                alert('✅ Transaction cancelled (ledger voucher(s) removed). Now record the correct transaction.');
                showTransactionHistory(bookId, bookName);
            } catch (error) {
                alert('❌ Error: ' + (error.response?.data?.error || error.message));
            }
        }
    </script>
@endpush
