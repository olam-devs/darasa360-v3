@extends('layouts.accountant')

@section('title', 'Expenses — Darasa Finance')
@section('page_title', 'Expenses')

@php
    $isMainAccountant = (bool) (auth()->user()->is_main_accountant ?? false);
@endphp

@section('content')
<div class="w-full p-6">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-rose-600">Expenses</h2>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-300 mb-6 gap-1 flex-wrap">
        <button onclick="switchExpenseTab('compose')" id="etab-compose"
            class="expense-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-rose-600 text-white border-rose-600">
            Compose
        </button>
        @if($isMainAccountant)
        <button onclick="switchExpenseTab('review')" id="etab-review"
            class="expense-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-rose-50">
            Review Queue
        </button>
        @endif
        <button onclick="switchExpenseTab('budget')" id="etab-budget"
            class="expense-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-rose-50">
            Categories &amp; Budget
        </button>
        <button onclick="switchExpenseTab('catalog')" id="etab-catalog"
            class="expense-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-rose-50">
            Item Catalog
        </button>
        <button onclick="switchExpenseTab('reports')" id="etab-reports"
            class="expense-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-rose-50">
            Reports &amp; Log
        </button>
    </div>

    <!-- Compose Tab -->
    <div id="epanel-compose">
        <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <div class="flex gap-2">
                        <select id="composeCategory" class="flex-1 border-2 border-gray-300 rounded px-3 py-2 text-sm"></select>
                        <button type="button" onclick="proposeNewCategory()" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded whitespace-nowrap">+ New</button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                    <select id="composeAcademicYear" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm"></select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Book</label>
                    <select id="composeBook" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm"></select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" id="composeDate" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
                <input type="text" id="composeTitle" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. Monthly stationery purchase">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                <textarea id="composeDescription" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm" rows="2"></textarea>
            </div>

            <h3 class="font-semibold text-gray-800 mb-2">Line items</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm mb-2">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 text-left">Item</th>
                            <th class="p-2 text-left w-24">Unit</th>
                            <th class="p-2 text-right w-24">Qty</th>
                            <th class="p-2 text-right w-32">Unit Price</th>
                            <th class="p-2 text-right w-32">Subtotal</th>
                            <th class="p-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="composeLineItems"></tbody>
                </table>
            </div>
            <button type="button" onclick="addComposeLineRow()" class="text-sm bg-blue-50 text-blue-700 px-3 py-1.5 rounded hover:bg-blue-100 mb-4">+ Add item</button>

            <div class="flex justify-end mb-4">
                <div class="text-right">
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="text-2xl font-bold text-gray-900" id="composeGrandTotal">TSh 0</p>
                </div>
            </div>

            <button type="button" onclick="submitExpense()" id="composeSubmitBtn"
                class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-2.5 rounded font-semibold">
                {{ $isMainAccountant ? 'Approve & Record' : 'Submit for Approval' }}
            </button>
        </div>
    </div>

    <!-- Review Queue Tab -->
    @if($isMainAccountant)
    <div id="epanel-review" class="hidden">
        <div id="pendingCategoriesBox" class="mb-4"></div>
        <div id="reviewQueueBox"><p class="text-gray-400 text-center py-6">Loading…</p></div>
    </div>
    @endif

    <!-- Categories & Budget Tab -->
    <div id="epanel-budget" class="hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold mb-3">Categories</h3>
                    <div id="categoryListBox"><p class="text-gray-400 text-sm">Loading…</p></div>
                </div>
            </div>
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex flex-wrap gap-2 items-end mb-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Category</label>
                            <select id="chartCategory" onchange="loadChart()" class="border rounded px-2 py-1.5 text-sm"><option value="">All categories</option></select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Academic Year</label>
                            <select id="chartAcademicYear" onchange="loadChart()" class="border rounded px-2 py-1.5 text-sm"></select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From</label>
                            <input type="date" id="chartFrom" onchange="loadChart()" class="border rounded px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">To</label>
                            <input type="date" id="chartTo" onchange="loadChart()" class="border rounded px-2 py-1.5 text-sm">
                        </div>
                    </div>
                    <div class="flex gap-6 mb-4">
                        <div><p class="text-xs text-gray-500">Expected</p><p class="text-xl font-bold text-blue-600" id="chartExpected">TSh 0</p></div>
                        <div><p class="text-xs text-gray-500">Actual</p><p class="text-xl font-bold text-rose-600" id="chartActual">TSh 0</p></div>
                    </div>
                    <div id="chartCanvasWrap"><canvas id="expenseChart" height="220"></canvas></div>
                    <p id="chartHiddenMsg" class="hidden text-sm text-gray-500 italic mt-4">The main accountant hasn't made this category's budget visible to other accountants.</p>
                </div>

                @if($isMainAccountant)
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold mb-3">Edit plan for selected category</h3>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div><label class="block text-xs text-gray-500 mb-1">Expected amount (TSh)</label><input type="number" step="0.01" id="planAmount" class="w-full border rounded px-2 py-1.5 text-sm"></div>
                        <div><label class="block text-xs text-gray-500 mb-1">Academic year</label><select id="planAcademicYear" class="w-full border rounded px-2 py-1.5 text-sm"></select></div>
                        <div><label class="block text-xs text-gray-500 mb-1">From</label><input type="date" id="planFrom" class="w-full border rounded px-2 py-1.5 text-sm"></div>
                        <div><label class="block text-xs text-gray-500 mb-1">To</label><input type="date" id="planTo" class="w-full border rounded px-2 py-1.5 text-sm"></div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="savePlan()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded text-sm">Save plan</button>
                        <button onclick="toggleBudgetVisibility()" class="bg-gray-200 hover:bg-gray-300 px-4 py-1.5 rounded text-sm">Toggle visibility to others</button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Item Catalog Tab -->
    <div id="epanel-catalog" class="hidden">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex justify-between items-center mb-3 flex-wrap gap-2">
                <h3 class="font-semibold">Item catalog</h3>
                <div class="flex gap-2">
                    <input type="text" id="catalogSearch" oninput="loadCatalog()" placeholder="Search items..." class="border rounded px-3 py-1.5 text-sm">
                    <button onclick="showAddCatalogItem()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm">+ Add item</button>
                </div>
            </div>
            <div id="catalogTableBox"><p class="text-gray-400 text-sm">Loading…</p></div>
        </div>
    </div>

    <!-- Reports & Log Tab -->
    <div id="epanel-reports" class="hidden">
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="font-semibold mb-3">Decision log (school-wide)</h3>
            <div id="logBox"><p class="text-gray-400 text-sm">Loading…</p></div>
        </div>

        @if($isMainAccountant)
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-3">Export report</h3>
            <div class="flex flex-wrap gap-2 items-end">
                <div><label class="block text-xs text-gray-500 mb-1">Academic Year</label><select id="reportAcademicYear" class="border rounded px-2 py-1.5 text-sm"></select></div>
                <div><label class="block text-xs text-gray-500 mb-1">Category</label><select id="reportCategory" class="border rounded px-2 py-1.5 text-sm"><option value="">All</option></select></div>
                <div><label class="block text-xs text-gray-500 mb-1">From</label><input type="date" id="reportFrom" class="border rounded px-2 py-1.5 text-sm"></div>
                <div><label class="block text-xs text-gray-500 mb-1">To</label><input type="date" id="reportTo" class="border rounded px-2 py-1.5 text-sm"></div>
                <button onclick="downloadReport('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded text-sm">PDF</button>
                <button onclick="downloadReport('csv')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded text-sm">CSV</button>
            </div>
        </div>
        @endif
    </div>

    <datalist id="itemsDatalist"></datalist>
</div>
@endsection

@push('scripts')
<script>
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

const EBASE = '{{ url('/accountant/api') }}';
const IS_MAIN_ACCOUNTANT = @json($isMainAccountant);

function fmt(n) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(n || 0);
}

// ─── Tabs ────────────────────────────────────────────────────────────────
const EXPENSE_TABS = IS_MAIN_ACCOUNTANT ? ['compose', 'review', 'budget', 'catalog', 'reports'] : ['compose', 'budget', 'catalog', 'reports'];

function switchExpenseTab(name) {
    EXPENSE_TABS.forEach(t => {
        document.getElementById('epanel-' + t)?.classList.add('hidden');
        const btn = document.getElementById('etab-' + t);
        btn?.classList.remove('bg-rose-600', 'text-white', 'border-rose-600');
        btn?.classList.add('bg-white', 'text-gray-600', 'border-gray-300');
    });
    document.getElementById('epanel-' + name).classList.remove('hidden');
    const active = document.getElementById('etab-' + name);
    active.classList.add('bg-rose-600', 'text-white', 'border-rose-600');
    active.classList.remove('bg-white', 'text-gray-600', 'border-gray-300');

    if (name === 'review') loadReviewQueue();
    if (name === 'budget') { loadCategoryListForBudget(); loadChart(); }
    if (name === 'catalog') loadCatalog();
    if (name === 'reports') loadLog();
}

// ─── Shared caches ───────────────────────────────────────────────────────
let itemsCache = []; // {id, name, unit_type}
let categoriesCache = []; // approved only, for selects

async function loadItemsCache() {
    const res = await axios.get(`${EBASE}/expense-items`);
    itemsCache = res.data.items || [];
    const list = document.getElementById('itemsDatalist');
    list.innerHTML = itemsCache.map(i => `<option value="${i.name}">`).join('');
}

async function loadCategoriesForSelects() {
    const res = await axios.get(`${EBASE}/expense-categories?approved_only=1`);
    categoriesCache = res.data.categories || [];
    const opts = categoriesCache.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    ['composeCategory', 'chartCategory', 'reportCategory'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const keepFirst = id === 'chartCategory' || id === 'reportCategory';
        el.innerHTML = (keepFirst ? el.querySelector('option')?.outerHTML || '' : '') + opts;
    });
}

async function loadAcademicYearsForSelects() {
    const res = await axios.get('/api/academic-years');
    const years = res.data || [];
    const opts = years.map(y => `<option value="${y.id}" ${y.is_current ? 'selected' : ''}>${y.name}</option>`).join('');
    ['composeAcademicYear', 'chartAcademicYear', 'planAcademicYear', 'reportAcademicYear'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = opts;
    });
}

async function loadBooksForSelect() {
    const res = await axios.get('/api/books');
    const books = res.data || [];
    const el = document.getElementById('composeBook');
    if (el) el.innerHTML = '<option value="">Select a book</option>' + books.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
}

// ─── Compose ─────────────────────────────────────────────────────────────
let composeRowSeq = 0;

function addComposeLineRow() {
    const rowId = ++composeRowSeq;
    const tbody = document.getElementById('composeLineItems');
    const tr = document.createElement('tr');
    tr.dataset.rowId = rowId;
    tr.innerHTML = `
        <td class="p-2">
            <input type="text" list="itemsDatalist" class="w-full border rounded px-2 py-1 line-item-name" oninput="onLineItemNameChange(${rowId})" placeholder="Search or type new item">
            <input type="hidden" class="line-item-existing-id">
        </td>
        <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 line-item-unit" placeholder="kg, pcs..."></td>
        <td class="p-2"><input type="number" step="0.001" min="0.001" value="1" class="w-full border rounded px-2 py-1 text-right line-item-qty" oninput="recomputeComposeLine(${rowId})"></td>
        <td class="p-2"><input type="number" step="0.01" min="0" value="0" class="w-full border rounded px-2 py-1 text-right line-item-price" oninput="recomputeComposeLine(${rowId})"></td>
        <td class="p-2 text-right line-item-subtotal">TSh 0</td>
        <td class="p-2 text-center"><button type="button" onclick="removeComposeLineRow(${rowId})" class="text-red-500 hover:text-red-700">&times;</button></td>
    `;
    tbody.appendChild(tr);
}

function removeComposeLineRow(rowId) {
    document.querySelector(`#composeLineItems tr[data-row-id="${rowId}"]`)?.remove();
    recomputeComposeGrandTotal();
}

function onLineItemNameChange(rowId) {
    const tr = document.querySelector(`#composeLineItems tr[data-row-id="${rowId}"]`);
    const name = tr.querySelector('.line-item-name').value.trim().toLowerCase();
    const match = itemsCache.find(i => i.name.toLowerCase() === name);
    const idField = tr.querySelector('.line-item-existing-id');
    const unitField = tr.querySelector('.line-item-unit');
    if (match) {
        idField.value = match.id;
        unitField.value = match.unit_type;
    } else {
        idField.value = '';
    }
}

function recomputeComposeLine(rowId) {
    const tr = document.querySelector(`#composeLineItems tr[data-row-id="${rowId}"]`);
    const qty = parseFloat(tr.querySelector('.line-item-qty').value) || 0;
    const price = parseFloat(tr.querySelector('.line-item-price').value) || 0;
    tr.querySelector('.line-item-subtotal').textContent = 'TSh ' + fmt(qty * price);
    recomputeComposeGrandTotal();
}

function recomputeComposeGrandTotal() {
    const rows = [...document.querySelectorAll('#composeLineItems tr')];
    const total = rows.reduce((sum, tr) => {
        const qty = parseFloat(tr.querySelector('.line-item-qty').value) || 0;
        const price = parseFloat(tr.querySelector('.line-item-price').value) || 0;
        return sum + (qty * price);
    }, 0);
    document.getElementById('composeGrandTotal').textContent = 'TSh ' + fmt(total);
}

async function proposeNewCategory() {
    const name = prompt('New category name:');
    if (!name || !name.trim()) return;
    try {
        const res = await axios.post(`${EBASE}/expense-categories`, { name: name.trim() });
        showDarasaToast({ type: 'success', message: res.data.category.status === 'approved' ? 'Category added.' : 'Category proposed - pending main accountant approval.' });
        await loadCategoriesForSelects();
        if (res.data.category.status === 'approved') {
            document.getElementById('composeCategory').value = res.data.category.id;
        }
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to propose category.' });
    }
}

async function submitExpense() {
    const rows = [...document.querySelectorAll('#composeLineItems tr')];
    if (!rows.length) {
        showDarasaToast({ type: 'error', message: 'Add at least one line item.' });
        return;
    }

    const lineItems = rows.map(tr => {
        const existingId = tr.querySelector('.line-item-existing-id').value;
        const name = tr.querySelector('.line-item-name').value.trim();
        const unit = tr.querySelector('.line-item-unit').value.trim();
        const qty = parseFloat(tr.querySelector('.line-item-qty').value) || 0;
        const price = parseFloat(tr.querySelector('.line-item-price').value) || 0;
        const data = { quantity: qty, unit_price: price };
        if (existingId) {
            data.expense_item_id = parseInt(existingId, 10);
        } else {
            data.new_item_name = name;
            data.new_item_unit_type = unit;
        }
        return data;
    });

    const payload = {
        expense_category_id: document.getElementById('composeCategory').value,
        book_id: document.getElementById('composeBook').value || null,
        academic_year_id: document.getElementById('composeAcademicYear').value,
        transaction_date: document.getElementById('composeDate').value,
        title: document.getElementById('composeTitle').value || null,
        description: document.getElementById('composeDescription').value || null,
        line_items: lineItems,
    };

    if (!payload.expense_category_id || !payload.academic_year_id || !payload.transaction_date) {
        showDarasaToast({ type: 'error', message: 'Category, academic year, and date are required.' });
        return;
    }

    try {
        await axios.post(`${EBASE}/expense-submissions`, payload);
        showDarasaToast({ type: 'success', message: IS_MAIN_ACCOUNTANT ? 'Expense recorded.' : 'Submitted for approval.' });
        resetComposeForm();
        loadItemsCache();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to save expense.' });
    }
}

function resetComposeForm() {
    document.getElementById('composeLineItems').innerHTML = '';
    document.getElementById('composeTitle').value = '';
    document.getElementById('composeDescription').value = '';
    document.getElementById('composeGrandTotal').textContent = 'TSh 0';
    addComposeLineRow();
}

// ─── Review Queue ────────────────────────────────────────────────────────
async function loadReviewQueue() {
    const box = document.getElementById('reviewQueueBox');
    const catBox = document.getElementById('pendingCategoriesBox');
    try {
        const res = await axios.get(`${EBASE}/expense-submissions-queue`);
        const submissions = res.data.submissions || [];
        const priceHistory = res.data.price_history || {};
        const pendingCategories = res.data.pending_categories || [];

        catBox.innerHTML = pendingCategories.length
            ? '<div class="bg-white rounded-lg shadow p-4"><h3 class="font-semibold mb-2">Pending categories</h3>' +
                pendingCategories.map(c => `
                    <div class="flex justify-between items-center border-t py-2">
                        <span>${c.name}</span>
                        <div class="flex gap-2">
                            <button onclick="decideCategory(${c.id}, 'approve')" class="text-xs bg-emerald-600 text-white px-2 py-1 rounded">Approve</button>
                            <button onclick="decideCategory(${c.id}, 'deny')" class="text-xs bg-red-500 text-white px-2 py-1 rounded">Deny</button>
                        </div>
                    </div>
                `).join('') + '</div>'
            : '';

        box.innerHTML = submissions.length
            ? submissions.map(sub => renderQueueCard(sub, priceHistory)).join('')
            : '<p class="text-gray-400 text-center py-6">Nothing pending review.</p>';
    } catch (e) {
        box.innerHTML = '<p class="text-red-600">Could not load the review queue.</p>';
    }
}

async function decideCategory(id, action) {
    try {
        await axios.post(`${EBASE}/expense-categories/${id}/${action}`, {});
        showDarasaToast({ type: 'success', message: `Category ${action === 'approve' ? 'approved' : 'denied'}.` });
        loadReviewQueue();
        loadCategoriesForSelects();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to decide category.' });
    }
}

function renderQueueCard(sub, priceHistoryMap) {
    const total = (sub.line_items || []).reduce((s, l) => s + parseFloat(l.line_total), 0);
    const lineRows = (sub.line_items || []).map(li => {
        const hist = priceHistoryMap[li.expense_item_id] || [];
        const histHtml = hist.length
            ? '<div class="text-xs text-gray-400 mt-1">Last: ' + hist.slice(0, 3).map(h => `${h.date} TSh ${fmt(h.unit_price)}${h.category ? ' (' + h.category + ')' : ''}`).join(' · ') + '</div>'
            : '<div class="text-xs text-gray-300 mt-1">No prior price history.</div>';
        return `
            <tr data-line-id="${li.id}">
                <td class="p-2 align-top">${li.item_name_snapshot}${histHtml}</td>
                <td class="p-2 align-top"><input type="text" class="w-20 border rounded px-1 py-0.5 text-xs review-unit" value="${li.unit_type_snapshot}"></td>
                <td class="p-2 align-top"><input type="number" step="0.001" class="w-20 border rounded px-1 py-0.5 text-xs text-right review-qty" value="${li.quantity}"></td>
                <td class="p-2 align-top"><input type="number" step="0.01" class="w-24 border rounded px-1 py-0.5 text-xs text-right review-price" value="${li.unit_price}"></td>
                <td class="p-2 align-top text-center">
                    <select class="review-status text-xs border rounded px-1 py-0.5">
                        <option value="approved" selected>Approve</option>
                        <option value="denied">Deny</option>
                    </select>
                </td>
            </tr>
        `;
    }).join('');

    return `
    <div class="bg-white rounded-lg shadow p-4 mb-4" data-submission-id="${sub.id}">
        <div class="flex justify-between items-start mb-2 flex-wrap gap-2">
            <div>
                <p class="font-semibold">${sub.submission_number} — ${sub.category?.name || ''}</p>
                <p class="text-xs text-gray-500">${sub.transaction_date} · ${sub.title || 'No title'} · Book: ${sub.book?.name || 'Not set'}</p>
            </div>
            <p class="font-bold text-gray-900">TSh ${fmt(total)}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm mb-3">
                <thead class="bg-gray-50"><tr><th class="p-2 text-left">Item</th><th class="p-2 text-left">Unit</th><th class="p-2 text-right">Qty</th><th class="p-2 text-right">Price</th><th class="p-2 text-center">Decision</th></tr></thead>
                <tbody>${lineRows}</tbody>
            </table>
        </div>
        <textarea class="w-full border rounded px-2 py-1 text-sm mb-2 review-note" placeholder="Decision note (required, visible to all accountants at this school)" rows="2"></textarea>
        <div class="flex gap-2">
            <button onclick="submitReview(${sub.id}, 'approve')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded text-sm">Save Decision</button>
            <button onclick="submitReview(${sub.id}, 'deny')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-1.5 rounded text-sm">Deny All</button>
        </div>
    </div>`;
}

async function submitReview(submissionId, overallDecision) {
    const card = document.querySelector(`[data-submission-id="${submissionId}"]`);
    const note = card.querySelector('.review-note').value.trim();
    if (!note) {
        showDarasaToast({ type: 'error', message: 'A decision note is required.' });
        return;
    }

    const lineItems = [...card.querySelectorAll('tr[data-line-id]')].map(tr => ({
        id: parseInt(tr.dataset.lineId, 10),
        status: overallDecision === 'deny' ? 'denied' : tr.querySelector('.review-status').value,
        unit_price: parseFloat(tr.querySelector('.review-price').value) || 0,
        quantity: parseFloat(tr.querySelector('.review-qty').value) || 0,
    }));

    try {
        await axios.post(`${EBASE}/expense-submissions/${submissionId}/review`, {
            decision_note: note,
            overall_decision: overallDecision,
            line_items: lineItems,
        });
        showDarasaToast({ type: 'success', message: 'Decision saved.' });
        loadReviewQueue();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to save decision.' });
    }
}

// ─── Categories & Budget ─────────────────────────────────────────────────
let selectedBudgetCategoryId = null;

async function loadCategoryListForBudget() {
    const box = document.getElementById('categoryListBox');
    try {
        const res = await axios.get(`${EBASE}/expense-categories`);
        const categories = res.data.categories || [];
        box.innerHTML = categories.map(c => `
            <button type="button" onclick="selectBudgetCategory(${c.id}, '${c.name.replace(/'/g, "\\'")}')"
                class="w-full text-left px-3 py-2 rounded text-sm hover:bg-rose-50 flex justify-between items-center ${c.status !== 'approved' ? 'opacity-50' : ''}">
                <span>${c.name}</span>
                ${c.status !== 'approved' ? `<span class="text-xs text-amber-600">${c.status}</span>` : ''}
            </button>
        `).join('') || '<p class="text-gray-400 text-sm">No categories yet.</p>';
    } catch (e) {
        box.innerHTML = '<p class="text-red-600 text-sm">Could not load categories.</p>';
    }
}

function selectBudgetCategory(id, name) {
    selectedBudgetCategoryId = id;
    document.getElementById('chartCategory').value = id;
    loadChart();
}

async function loadChart() {
    const categoryId = document.getElementById('chartCategory').value;
    const academicYearId = document.getElementById('chartAcademicYear').value;
    if (!academicYearId) return;

    const params = new URLSearchParams({ academic_year_id: academicYearId });
    if (categoryId) params.set('category_id', categoryId);
    const from = document.getElementById('chartFrom').value;
    const to = document.getElementById('chartTo').value;
    if (from) params.set('from_date', from);
    if (to) params.set('to_date', to);

    try {
        const res = await axios.get(`${EBASE}/expense-plans/chart?${params.toString()}`);
        document.getElementById('chartHiddenMsg').classList.add('hidden');
        document.getElementById('chartCanvasWrap').classList.remove('hidden');
        document.getElementById('chartExpected').textContent = 'TSh ' + fmt(res.data.expected_amount);
        document.getElementById('chartActual').textContent = 'TSh ' + fmt(res.data.actual_amount);
        renderExpenseChart(res.data.timeline || []);

        if (categoryId) {
            document.getElementById('planAmount').value = '';
            document.getElementById('planFrom').value = '';
            document.getElementById('planTo').value = '';
        }
    } catch (e) {
        if (e.response?.status === 403) {
            document.getElementById('chartHiddenMsg').classList.remove('hidden');
            document.getElementById('chartCanvasWrap').classList.add('hidden');
            document.getElementById('chartExpected').textContent = 'TSh 0';
            document.getElementById('chartActual').textContent = 'TSh 0';
        } else {
            showDarasaToast({ type: 'error', message: 'Could not load chart data.' });
        }
    }
}

let expenseChartInstance = null;
let chartsScriptLoading = false;

function renderExpenseChart(timeline) {
    if (typeof Chart === 'undefined') {
        if (!chartsScriptLoading) {
            chartsScriptLoading = true;
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => renderExpenseChart(timeline);
            document.head.appendChild(script);
        }
        return;
    }

    const ctx = document.getElementById('expenseChart').getContext('2d');
    if (expenseChartInstance) expenseChartInstance.destroy();
    expenseChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: timeline.map(t => t.label),
            datasets: [{
                label: 'Actual spend',
                data: timeline.map(t => t.amount),
                backgroundColor: '#e11d48',
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
        },
    });
}

async function savePlan() {
    const categoryId = document.getElementById('chartCategory').value;
    if (!categoryId) {
        showDarasaToast({ type: 'error', message: 'Select a specific category first (not "All categories").' });
        return;
    }
    const payload = {
        academic_year_id: document.getElementById('planAcademicYear').value,
        expected_amount: document.getElementById('planAmount').value,
        from_date: document.getElementById('planFrom').value,
        to_date: document.getElementById('planTo').value,
    };
    try {
        await axios.post(`${EBASE}/expense-categories/${categoryId}/plan`, payload);
        showDarasaToast({ type: 'success', message: 'Plan saved.' });
        loadChart();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to save plan.' });
    }
}

async function toggleBudgetVisibility() {
    const categoryId = document.getElementById('chartCategory').value;
    if (!categoryId) {
        showDarasaToast({ type: 'error', message: 'Select a specific category first.' });
        return;
    }
    try {
        await axios.post(`${EBASE}/expense-categories/${categoryId}/toggle-budget-visibility`, {});
        showDarasaToast({ type: 'success', message: 'Visibility updated.' });
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to update visibility.' });
    }
}

// ─── Item Catalog ────────────────────────────────────────────────────────
async function loadCatalog() {
    const box = document.getElementById('catalogTableBox');
    const search = document.getElementById('catalogSearch').value;
    try {
        const res = await axios.get(`${EBASE}/expense-items?search=${encodeURIComponent(search)}`);
        const items = res.data.items || [];
        box.innerHTML = items.length
            ? `<table class="w-full text-sm"><thead class="bg-gray-100"><tr><th class="p-2 text-left">Name</th><th class="p-2 text-left">Unit type</th></tr></thead><tbody>${
                items.map(i => `<tr class="border-t"><td class="p-2">${i.name}</td><td class="p-2">${i.unit_type}</td></tr>`).join('')
            }</tbody></table>`
            : '<p class="text-gray-400 text-sm text-center py-4">No items found.</p>';
    } catch (e) {
        box.innerHTML = '<p class="text-red-600 text-sm">Could not load items.</p>';
    }
}

async function showAddCatalogItem() {
    const name = prompt('Item name:');
    if (!name || !name.trim()) return;
    const unitType = prompt('Unit type (e.g. kg, pieces, litres):');
    if (!unitType || !unitType.trim()) return;
    try {
        await axios.post(`${EBASE}/expense-items`, { name: name.trim(), unit_type: unitType.trim() });
        showDarasaToast({ type: 'success', message: 'Item added.' });
        loadCatalog();
        loadItemsCache();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to add item.' });
    }
}

// ─── Reports & Log ───────────────────────────────────────────────────────
async function loadLog() {
    const box = document.getElementById('logBox');
    try {
        const res = await axios.get(`${EBASE}/expense-submissions-log`);
        const submissions = res.data.data || [];
        box.innerHTML = submissions.length
            ? submissions.map(s => `
                <div class="border-t py-3">
                    <div class="flex justify-between flex-wrap gap-1">
                        <p class="font-medium">${s.submission_number} — ${s.category?.name || ''} — TSh ${fmt(s.total_amount)}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full ${s.status === 'denied' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}">${s.status.replace('_', ' ')}</span>
                    </div>
                    <p class="text-xs text-gray-500">${s.transaction_date} · decided ${s.decided_at || ''}</p>
                    ${s.decision_note ? `<p class="text-sm text-gray-700 mt-1 italic">"${s.decision_note}"</p>` : ''}
                </div>
            `).join('')
            : '<p class="text-gray-400 text-center py-6">No decisions yet.</p>';
    } catch (e) {
        box.innerHTML = '<p class="text-red-600">Could not load the log.</p>';
    }
}

function downloadReport(type) {
    const params = new URLSearchParams({
        academic_year_id: document.getElementById('reportAcademicYear').value,
    });
    const category = document.getElementById('reportCategory').value;
    const from = document.getElementById('reportFrom').value;
    const to = document.getElementById('reportTo').value;
    if (category) params.set('category_id', category);
    if (from) params.set('from_date', from);
    if (to) params.set('to_date', to);

    if (!params.get('academic_year_id')) {
        showDarasaToast({ type: 'error', message: 'Select an academic year first.' });
        return;
    }

    window.location = `${EBASE}/expense-submissions-report/${type}?${params.toString()}`;
}

// ─── Page init ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    document.getElementById('composeDate').value = new Date().toISOString().slice(0, 10);
    await Promise.all([loadItemsCache(), loadCategoriesForSelects(), loadAcademicYearsForSelects(), loadBooksForSelect()]);
    addComposeLineRow();
});
</script>
@endpush
