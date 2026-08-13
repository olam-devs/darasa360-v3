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
        <button onclick="switchExpenseTab('mysubmissions')" id="etab-mysubmissions"
            class="expense-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-rose-50">
            My Submissions
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
                        <div class="flex-1">
                            <input type="text" id="composeCategoryInput" list="categoriesDatalist"
                                class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm"
                                placeholder="Search or type category name..."
                                oninput="onComposeCategoryInput()" autocomplete="off">
                            <input type="hidden" id="composeCategory">
                            <p id="composeCategoryWarning" class="hidden text-xs text-amber-600 mt-1"></p>
                        </div>
                        <button type="button" onclick="proposeNewCategory()" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded whitespace-nowrap self-start">+ New</button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                    <select id="composeAcademicYear" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm"></select>
                </div>
                @if($isMainAccountant)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Book</label>
                    <select id="composeBook" onchange="loadFeeCategoriesForSelect(document.getElementById('composeBook'), document.getElementById('composeFeeCategory'))" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm"></select>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" id="composeDate" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                @if($isMainAccountant)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Fee (optional)</label>
                    <select id="composeFeeCategory" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm"><option value="">-- No fee --</option></select>
                    <p class="text-xs text-gray-500 mt-1">Auto-cuts the book's transaction fee for this expense.</p>
                </div>
                @endif
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
                            <th class="p-2 text-right w-36">Price per unit</th>
                            <th class="p-2 text-right w-28">Quantity</th>
                            <th class="p-2 text-right w-32">Total Price</th>
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

            <div id="composeEditingBanner" class="hidden mb-3 bg-amber-50 border border-amber-200 rounded px-3 py-2 text-sm text-amber-700">
                Editing mode — modify the items and click Update to save changes to the pending submission.
                <button type="button" onclick="cancelEdit()" class="ml-2 underline text-amber-800">Cancel edit</button>
            </div>

            <button type="button" onclick="submitExpense()" id="composeSubmitBtn"
                class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-2.5 rounded font-semibold">
                {{ $isMainAccountant ? 'Approve & Record' : 'Submit for Approval' }}
            </button>
        </div>
    </div>

    <!-- My Submissions Tab -->
    <div id="epanel-mysubmissions" class="hidden">
        <div class="bg-white rounded-lg shadow p-6 max-w-3xl">
            <h3 class="font-semibold mb-4">My Submissions</h3>
            <div id="mySubsBox"><p class="text-gray-400 text-center py-6">Loading…</p></div>
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
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-semibold">Categories</h3>
                        <button type="button" onclick="openAddCategoryModal()" class="text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 px-2 py-1 rounded">+ Add</button>
                    </div>
                    <input type="text" id="categorySearch" oninput="filterCategoryList()" placeholder="Search categories…"
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm mb-2">
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
                        @if($isMainAccountant)
                        <div><p class="text-xs text-gray-500">Budget</p><p class="text-xl font-bold text-blue-600" id="chartExpected">TSh 0</p></div>
                        @endif
                        <div><p class="text-xs text-gray-500">Actual</p><p class="text-xl font-bold text-rose-600" id="chartActual">TSh 0</p></div>
                    </div>
                    <div id="chartCanvasWrap"><canvas id="expenseChart" height="220"></canvas></div>
                    <p id="chartHiddenMsg" class="hidden text-sm text-gray-500 italic mt-4">Budget data is only visible to the main accountant.</p>
                </div>

                @if($isMainAccountant)
                <div class="bg-white rounded-lg shadow p-4" id="budgetPlanPanel">
                    <h3 class="font-semibold mb-1">Monthly budget plan</h3>
                    <p class="text-xs text-gray-500 mb-3">Select a specific category and academic year above, then enter a budget for each month.</p>
                    <div id="budgetPlanBox">
                        <p class="text-gray-400 text-sm">Select a category above to set monthly budgets.</p>
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
            <h3 class="font-semibold mb-3">Decision log (school-wide) <span class="text-xs font-normal text-gray-500">— click any row to see item details</span></h3>
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
    <datalist id="categoriesDatalist"></datalist>
</div>

<!-- Category Modal (add / rename) -->
<div id="categoryModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center hidden" onclick="if(event.target===this)closeCategoryModal()">
    <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
        <h3 class="font-semibold text-gray-800 mb-4" id="categoryModalTitle">New Category</h3>
        <input type="hidden" id="categoryModalId">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
            <input type="text" id="categoryModalName" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm"
                placeholder="e.g. Transport &amp; Fuel" onkeydown="if(event.key==='Enter')submitCategoryModal()">
        </div>
        <div class="flex gap-2 justify-end">
            <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 text-sm rounded bg-gray-100 hover:bg-gray-200">Cancel</button>
            <button type="button" onclick="submitCategoryModal()" class="px-4 py-2 text-sm rounded bg-rose-600 text-white hover:bg-rose-700">Save</button>
        </div>
    </div>
</div>

<!-- Add Catalog Item Modal -->
<div id="catalogItemModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center hidden" onclick="if(event.target===this)closeCatalogItemModal()">
    <div class="bg-white rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
        <h3 class="font-semibold text-gray-800 mb-4">Add Catalog Item</h3>
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
            <input type="text" id="catalogItemName" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. Exercise Books (80 pages)">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Type</label>
            <input type="text" id="catalogItemUnit" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. pieces, litres, kg"
                onkeydown="if(event.key==='Enter')submitCatalogItemModal()">
        </div>
        <div class="flex gap-2 justify-end">
            <button type="button" onclick="closeCatalogItemModal()" class="px-4 py-2 text-sm rounded bg-gray-100 hover:bg-gray-200">Cancel</button>
            <button type="button" onclick="submitCatalogItemModal()" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Add Item</button>
        </div>
    </div>
</div>

<!-- Submission Detail Modal (My Submissions + Log) -->
<div id="submissionDetailModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center hidden" onclick="if(event.target===this)closeSubmissionDetailModal()">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="font-semibold text-gray-800 text-lg" id="detailModalTitle">Expense Detail</h3>
                <p class="text-sm text-gray-500 mt-0.5" id="detailModalSubtitle"></p>
            </div>
            <button type="button" onclick="closeSubmissionDetailModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none ml-4">&times;</button>
        </div>
        <div id="detailModalBody"><p class="text-gray-400">Loading…</p></div>
        <div id="detailModalEditSection" class="hidden mt-4 pt-4 border-t">
            <p class="text-sm text-amber-600 mb-3">This submission is pending. You can edit and resubmit.</p>
            <button type="button" onclick="loadSubmissionForEdit()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">Edit this submission</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

const EBASE = '{{ url('/api') }}';
const IS_MAIN_ACCOUNTANT = @json($isMainAccountant);
const DEFAULT_SUBMIT_BTN_TEXT = IS_MAIN_ACCOUNTANT ? 'Approve & Record' : 'Submit for Approval';

function fmt(n) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(n || 0);
}

// ─── Tabs ────────────────────────────────────────────────────────────────
const EXPENSE_TABS = IS_MAIN_ACCOUNTANT
    ? ['compose', 'mysubmissions', 'review', 'budget', 'catalog', 'reports']
    : ['compose', 'mysubmissions', 'budget', 'catalog', 'reports'];

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

    if (name === 'mysubmissions') loadMySubmissions();
    if (name === 'review') loadReviewQueue();
    if (name === 'budget') { loadCategoryListForBudget(); loadChart(); }
    if (name === 'catalog') loadCatalog();
    if (name === 'reports') loadLog();
}

// ─── Shared caches ───────────────────────────────────────────────────────
let itemsCache = [];
let categoriesCache = [];
let _budgetPlanState = []; // [{type:'month',key,label,amount}|{type:'group',fromKey,toKey,fromLabel,toLabel,amount}]

async function loadItemsCache() {
    const res = await axios.get(`${EBASE}/expense-items`);
    itemsCache = res.data.items || [];
    const list = document.getElementById('itemsDatalist');
    list.innerHTML = itemsCache.map(i => `<option value="${i.name}">`).join('');
}

async function loadCategoriesForSelects() {
    const res = await axios.get(`${EBASE}/expense-categories?approved_only=1`);
    categoriesCache = res.data.categories || [];

    // Compose: datalist (searchable text input)
    const dl = document.getElementById('categoriesDatalist');
    if (dl) dl.innerHTML = categoriesCache.map(c => `<option value="${c.name}"></option>`).join('');

    // Chart + report: regular selects
    const opts = categoriesCache.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    ['chartCategory', 'reportCategory'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = (el.querySelector('option')?.outerHTML || '') + opts;
    });
}

let academicYearsCache = [];

async function loadAcademicYearsForSelects() {
    const res = await axios.get('/api/academic-years');
    academicYearsCache = res.data || [];
    const opts = academicYearsCache.map(y => `<option value="${y.id}" ${y.is_current ? 'selected' : ''}>${y.name}</option>`).join('');
    ['composeAcademicYear', 'chartAcademicYear', 'reportAcademicYear'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = opts;
    });
}

let booksCache = [];

async function loadBooksForSelect() {
    if (!IS_MAIN_ACCOUNTANT) return;
    const res = await axios.get('/api/books');
    booksCache = res.data || [];
    const el = document.getElementById('composeBook');
    if (el) el.innerHTML = '<option value="">Select a book</option>' + booksOptionsHtml();
}

function booksOptionsHtml(selectedId) {
    return booksCache.map(b => `<option value="${b.id}" ${selectedId && String(b.id) === String(selectedId) ? 'selected' : ''}>${b.name}</option>`).join('');
}

async function loadFeeCategoriesForSelect(bookSelectEl, feeSelectEl, selectedFeeCategoryId) {
    if (!bookSelectEl || !feeSelectEl) return;
    const bookId = bookSelectEl.value;
    feeSelectEl.innerHTML = '<option value="">-- No fee --</option>';
    if (!bookId) return;
    try {
        const res = await axios.get(`${EBASE}/books/${bookId}/fee-categories`);
        const list = (Array.isArray(res.data) ? res.data : []).filter(c => c.is_active);
        list.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.code ? `${c.name} (${c.code})` : c.name;
            if (selectedFeeCategoryId && String(c.id) === String(selectedFeeCategoryId)) opt.selected = true;
            feeSelectEl.appendChild(opt);
        });
    } catch (e) {
        console.error('Failed to load fee categories', e);
    }
}

// ─── Compose ─────────────────────────────────────────────────────────────
let composeRowSeq = 0;
let editingSubmissionId = null;

function onComposeCategoryInput() {
    const val = document.getElementById('composeCategoryInput').value.trim().toLowerCase();
    const match = categoriesCache.find(c => c.name.toLowerCase() === val);
    const hiddenInput = document.getElementById('composeCategory');
    const warning = document.getElementById('composeCategoryWarning');

    if (match) {
        hiddenInput.value = match.id;
        // Show warning if category has a plan but it's expired
        if (!IS_MAIN_ACCOUNTANT && match.latest_plan_to_date && match.has_active_plan == 0) {
            warning.textContent = `⚠ This category's budget plan expired on ${match.latest_plan_to_date}. The main accountant must update it.`;
            warning.classList.remove('hidden');
        } else {
            warning.classList.add('hidden');
        }
    } else {
        hiddenInput.value = '';
        warning.classList.add('hidden');
    }
}

function addComposeLineRow(prefill) {
    const rowId = ++composeRowSeq;
    const tbody = document.getElementById('composeLineItems');
    const tr = document.createElement('tr');
    tr.dataset.rowId = rowId;
    tr.innerHTML = `
        <td class="p-2">
            <input type="text" list="itemsDatalist" class="w-full border rounded px-2 py-1 line-item-name text-sm" oninput="onLineItemNameChange(${rowId})" placeholder="Search or type new item" autocomplete="off">
            <input type="hidden" class="line-item-existing-id">
        </td>
        <td class="p-2"><input type="text" class="w-full border rounded px-2 py-1 line-item-unit text-sm" placeholder="unit"></td>
        <td class="p-2 text-right"><input type="number" step="0.01" min="0" value="0" class="w-full border rounded px-2 py-1 text-right line-item-price text-sm" oninput="recomputeComposeLine(${rowId})"></td>
        <td class="p-2 text-right"><input type="number" step="0.001" min="0.001" value="1" class="w-full border rounded px-2 py-1 text-right line-item-qty text-sm" oninput="recomputeComposeLine(${rowId})"></td>
        <td class="p-2 text-right line-item-subtotal text-sm">TSh 0</td>
        <td class="p-2 text-center"><button type="button" onclick="removeComposeLineRow(${rowId})" class="text-red-500 hover:text-red-700">&times;</button></td>
    `;
    tbody.appendChild(tr);

    if (prefill) {
        tr.querySelector('.line-item-name').value = prefill.item_name_snapshot;
        tr.querySelector('.line-item-existing-id').value = prefill.expense_item_id || '';
        tr.querySelector('.line-item-unit').value = prefill.unit_type_snapshot;
        tr.querySelector('.line-item-price').value = prefill.unit_price;
        tr.querySelector('.line-item-qty').value = prefill.quantity;
        recomputeComposeLine(rowId);
    }
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
    const priceField = tr.querySelector('.line-item-price');
    if (match) {
        idField.value = match.id;
        unitField.value = match.unit_type;
        if (IS_MAIN_ACCOUNTANT && match.last_price != null) {
            priceField.value = match.last_price;
            recomputeComposeLine(rowId);
        }
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

function proposeNewCategory() {
    openAddCategoryModal();
}

function cancelEdit() {
    editingSubmissionId = null;
    document.getElementById('composeEditingBanner').classList.add('hidden');
    document.getElementById('composeSubmitBtn').textContent = DEFAULT_SUBMIT_BTN_TEXT;
    resetComposeForm();
}

async function submitExpense() {
    const rows = [...document.querySelectorAll('#composeLineItems tr')];
    if (!rows.length) {
        showDarasaToast({ type: 'error', message: 'Add at least one line item.' });
        return;
    }

    const categoryId = document.getElementById('composeCategory').value;
    if (!categoryId) {
        showDarasaToast({ type: 'error', message: 'Select a category.' });
        return;
    }

    // Block if category plan is expired
    const selectedCat = categoriesCache.find(c => String(c.id) === String(categoryId));
    if (!IS_MAIN_ACCOUNTANT && selectedCat?.latest_plan_to_date && selectedCat?.has_active_plan == 0) {
        showDarasaToast({ type: 'error', message: "This category's plan has expired. The main accountant must extend it." });
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
        expense_category_id: categoryId,
        academic_year_id: document.getElementById('composeAcademicYear').value,
        transaction_date: document.getElementById('composeDate').value,
        title: document.getElementById('composeTitle').value || null,
        description: document.getElementById('composeDescription').value || null,
        line_items: lineItems,
    };

    if (IS_MAIN_ACCOUNTANT) {
        payload.book_id = document.getElementById('composeBook')?.value || null;
        payload.book_fee_category_id = document.getElementById('composeFeeCategory')?.value || null;
    }

    if (!payload.academic_year_id || !payload.transaction_date) {
        showDarasaToast({ type: 'error', message: 'Academic year and date are required.' });
        return;
    }

    try {
        if (editingSubmissionId) {
            await axios.put(`${EBASE}/expense-submissions/${editingSubmissionId}`, payload);
            showDarasaToast({ type: 'success', message: 'Submission updated.' });
            editingSubmissionId = null;
            document.getElementById('composeEditingBanner').classList.add('hidden');
            document.getElementById('composeSubmitBtn').textContent = DEFAULT_SUBMIT_BTN_TEXT;
        } else {
            await axios.post(`${EBASE}/expense-submissions`, payload);
            showDarasaToast({ type: 'success', message: IS_MAIN_ACCOUNTANT ? 'Expense recorded.' : 'Submitted for approval.' });
        }
        resetComposeForm();
        loadItemsCache();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to save expense.' });
    }
}

function resetComposeForm() {
    document.getElementById('composeLineItems').innerHTML = '';
    document.getElementById('composeCategoryInput').value = '';
    document.getElementById('composeCategory').value = '';
    document.getElementById('composeCategoryWarning').classList.add('hidden');
    document.getElementById('composeTitle').value = '';
    document.getElementById('composeDescription').value = '';
    document.getElementById('composeGrandTotal').textContent = 'TSh 0';
    const feeEl = document.getElementById('composeFeeCategory');
    if (feeEl) feeEl.innerHTML = '<option value="">-- No fee --</option>';
    addComposeLineRow();
}

// ─── My Submissions ──────────────────────────────────────────────────────
async function loadMySubmissions() {
    const box = document.getElementById('mySubsBox');
    try {
        const res = await axios.get(`${EBASE}/expense-submissions?mine=1&per_page=50`);
        const submissions = res.data.data || [];
        if (!submissions.length) {
            box.innerHTML = '<p class="text-gray-400 text-center py-6">No submissions yet.</p>';
            return;
        }
        box.innerHTML = submissions.map(s => {
            const badge = s.status === 'approved'
                ? '<span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Approved</span>'
                : s.status === 'denied'
                    ? '<span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">Denied</span>'
                    : '<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Pending</span>';
            return `
                <button type="button" onclick="openSubmissionDetail(${s.id}, ${s.status === 'pending' ? 'true' : 'false'})"
                    class="w-full text-left border-t py-3 hover:bg-gray-50 px-1">
                    <div class="flex justify-between flex-wrap gap-1 items-start">
                        <div>
                            <p class="font-medium text-sm">${s.submission_number} — ${s.category?.name || ''}</p>
                            <p class="text-xs text-gray-500">${s.transaction_date}${s.title ? ' · ' + s.title : ''}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <p class="font-bold text-sm text-gray-900">TSh ${fmt(s.total_amount)}</p>
                            ${badge}
                        </div>
                    </div>
                    ${s.decision_note ? `<p class="text-sm mt-1 text-gray-600 italic text-left">"${s.decision_note}"</p>` : ''}
                    ${s.decided_at ? `<p class="text-xs text-gray-400 mt-0.5">Decided ${s.decided_at}</p>` : ''}
                </button>
            `;
        }).join('');
    } catch (e) {
        box.innerHTML = '<p class="text-red-600">Could not load submissions.</p>';
    }
}

// ─── Submission Detail Modal ─────────────────────────────────────────────
let currentDetailSubmissionId = null;
let currentDetailAllowEdit = false;

async function openSubmissionDetail(id, allowEdit = false) {
    currentDetailSubmissionId = id;
    currentDetailAllowEdit = allowEdit;

    const modal = document.getElementById('submissionDetailModal');
    const body = document.getElementById('detailModalBody');
    const editSection = document.getElementById('detailModalEditSection');

    modal.classList.remove('hidden');
    body.innerHTML = '<p class="text-gray-400 py-4">Loading…</p>';
    editSection.classList.add('hidden');

    try {
        const res = await axios.get(`${EBASE}/expense-submissions/${id}`);
        const sub = res.data.submission;

        document.getElementById('detailModalTitle').textContent = sub.submission_number;
        document.getElementById('detailModalSubtitle').textContent =
            `${sub.category?.name || ''} · ${sub.transaction_date}${sub.title ? ' · ' + sub.title : ''}`;

        const badge = sub.status === 'approved'
            ? '<span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Approved</span>'
            : sub.status === 'denied'
                ? '<span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">Denied</span>'
                : '<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Pending</span>';

        const items = sub.line_items || [];
        const itemRows = items.map(li => `
            <tr class="border-t">
                <td class="p-2 text-sm">${li.item_name_snapshot}</td>
                <td class="p-2 text-sm">${li.unit_type_snapshot}</td>
                <td class="p-2 text-right text-sm">TSh ${fmt(li.unit_price)}</td>
                <td class="p-2 text-right text-sm">${li.quantity}</td>
                <td class="p-2 text-right text-sm font-medium">TSh ${fmt(li.line_total)}</td>
            </tr>
        `).join('');

        body.innerHTML = `
            <div class="flex items-center gap-3 mb-3">${badge}${sub.submitted_by_name ? `<span class="text-xs text-gray-500">by ${sub.submitted_by_name}</span>` : ''}</div>
            ${sub.description ? `<p class="text-sm text-gray-600 mb-3">${sub.description}</p>` : ''}
            ${sub.decision_note ? `<div class="bg-gray-50 rounded p-3 mb-3 text-sm"><strong>Decision note:</strong> "${sub.decision_note}"</div>` : ''}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 text-xs"><tr>
                        <th class="p-2 text-left">Item</th>
                        <th class="p-2 text-left">Unit</th>
                        <th class="p-2 text-right">Price/unit</th>
                        <th class="p-2 text-right">Qty</th>
                        <th class="p-2 text-right">Total</th>
                    </tr></thead>
                    <tbody>${itemRows}</tbody>
                    <tfoot><tr class="border-t-2 font-bold bg-gray-50">
                        <td colspan="4" class="p-2 text-right text-sm">Grand Total</td>
                        <td class="p-2 text-right text-sm">TSh ${fmt(sub.total_amount)}</td>
                    </tr></tfoot>
                </table>
            </div>
        `;

        if (allowEdit && sub.status === 'pending') {
            editSection.classList.remove('hidden');
        }
    } catch (e) {
        body.innerHTML = '<p class="text-red-600">Could not load submission details.</p>';
    }
}

function closeSubmissionDetailModal() {
    document.getElementById('submissionDetailModal').classList.add('hidden');
}

async function loadSubmissionForEdit() {
    if (!currentDetailSubmissionId) return;
    try {
        const res = await axios.get(`${EBASE}/expense-submissions/${currentDetailSubmissionId}`);
        const sub = res.data.submission;

        closeSubmissionDetailModal();
        switchExpenseTab('compose');

        // Pre-fill compose form
        editingSubmissionId = sub.id;

        const catMatch = categoriesCache.find(c => c.id === sub.expense_category_id);
        document.getElementById('composeCategoryInput').value = catMatch?.name || '';
        document.getElementById('composeCategory').value = sub.expense_category_id;
        document.getElementById('composeAcademicYear').value = sub.academic_year_id;
        document.getElementById('composeDate').value = sub.transaction_date;
        document.getElementById('composeTitle').value = sub.title || '';
        document.getElementById('composeDescription').value = sub.description || '';

        document.getElementById('composeLineItems').innerHTML = '';
        (sub.line_items || []).forEach(li => addComposeLineRow(li));

        document.getElementById('composeEditingBanner').classList.remove('hidden');
        document.getElementById('composeSubmitBtn').textContent = 'Update Submission';
    } catch (e) {
        showDarasaToast({ type: 'error', message: 'Could not load submission for editing.' });
    }
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

        box.querySelectorAll('.review-book').forEach(sel => {
            if (sel.value) {
                loadFeeCategoriesForSelect(sel, sel.closest('[data-submission-id]').querySelector('.review-fee-category'));
            }
        });
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

function recomputeReviewLine(input) {
    const tr = input.closest('tr[data-line-id]');
    if (!tr) return;
    const price = parseFloat(tr.querySelector('.review-price').value) || 0;
    const qty = parseFloat(tr.querySelector('.review-qty').value) || 0;
    const totalEl = tr.querySelector('.review-line-total');
    if (totalEl) totalEl.textContent = 'TSh ' + fmt(price * qty);
}

function renderQueueCard(sub, priceHistoryMap) {
    const total = (sub.line_items || []).reduce((s, l) => s + parseFloat(l.line_total), 0);
    const lineRows = (sub.line_items || []).map(li => {
        const hist = priceHistoryMap[li.expense_item_id] || [];
        const histHtml = hist.length
            ? '<div class="text-xs text-gray-400 mt-1">Last: ' + hist.slice(0, 3).map(h => `${h.date} TSh ${fmt(h.unit_price)}${h.category ? ' (' + h.category + ')' : ''}`).join(' · ') + '</div>'
            : '<div class="text-xs text-gray-300 mt-1">No prior price history.</div>';
        const lineTotal = (parseFloat(li.unit_price) * parseFloat(li.quantity)).toFixed(2);
        return `
            <tr data-line-id="${li.id}">
                <td class="p-2 align-top text-sm">${li.item_name_snapshot}${histHtml}</td>
                <td class="p-2 align-top"><input type="text" class="w-20 border rounded px-1 py-0.5 text-xs review-unit" value="${li.unit_type_snapshot}"></td>
                <td class="p-2 align-top text-right"><input type="number" step="0.01" class="w-24 border rounded px-1 py-0.5 text-xs text-right review-price" value="${li.unit_price}" oninput="recomputeReviewLine(this)"></td>
                <td class="p-2 align-top text-right"><input type="number" step="0.001" class="w-20 border rounded px-1 py-0.5 text-xs text-right review-qty" value="${li.quantity}" oninput="recomputeReviewLine(this)"></td>
                <td class="p-2 align-top text-right review-line-total text-sm">TSh ${fmt(lineTotal)}</td>
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
                <p class="text-xs text-gray-500">${sub.transaction_date} · ${sub.title || 'No title'}${sub.submitted_by_name ? ' · by <strong>' + sub.submitted_by_name + '</strong>' : ''}</p>
            </div>
            <p class="font-bold text-gray-900">TSh ${fmt(total)}</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3 bg-gray-50 rounded p-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Book (money comes from) *</label>
                <select class="w-full border rounded px-2 py-1 text-sm review-book" onchange="loadFeeCategoriesForSelect(this, this.closest('[data-submission-id]').querySelector('.review-fee-category'))">
                    <option value="">Select a book</option>
                    ${booksOptionsHtml(sub.book_id)}
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Transaction fee (optional)</label>
                <select class="w-full border rounded px-2 py-1 text-sm review-fee-category"><option value="">-- No fee --</option></select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm mb-3">
                <thead class="bg-gray-50"><tr>
                    <th class="p-2 text-left">Item</th>
                    <th class="p-2 text-left">Unit</th>
                    <th class="p-2 text-right">Price per unit</th>
                    <th class="p-2 text-right">Quantity</th>
                    <th class="p-2 text-right">Total</th>
                    <th class="p-2 text-center">Decision</th>
                </tr></thead>
                <tbody>${lineRows}</tbody>
            </table>
        </div>
        <textarea class="w-full border rounded px-2 py-1 text-sm mb-2 review-note" placeholder="Decision note (required, visible to all accountants)" rows="2"></textarea>
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
    const bookId = card.querySelector('.review-book').value || null;
    if (overallDecision === 'approve' && !bookId) {
        showDarasaToast({ type: 'error', message: 'Select which book the money comes from before approving.' });
        return;
    }
    const feeCategoryId = card.querySelector('.review-fee-category').value || null;
    const lineItems = [...card.querySelectorAll('tr[data-line-id]')].map(tr => ({
        id: parseInt(tr.dataset.lineId, 10),
        status: overallDecision === 'deny' ? 'denied' : tr.querySelector('.review-status').value,
        unit_price: parseFloat(tr.querySelector('.review-price').value) || 0,
        quantity: parseFloat(tr.querySelector('.review-qty').value) || 0,
    }));

    try {
        await axios.post(`${EBASE}/expense-submissions/${submissionId}/review`, {
            decision_note: note, overall_decision: overallDecision,
            book_id: bookId, book_fee_category_id: feeCategoryId, line_items: lineItems,
        });
        showDarasaToast({ type: 'success', message: 'Decision saved.' });
        loadReviewQueue();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to save decision.' });
    }
}

// ─── Category Modal ──────────────────────────────────────────────────────
let categoryModalMode = 'add';

function openAddCategoryModal() {
    categoryModalMode = 'add';
    document.getElementById('categoryModalTitle').textContent = 'New Category';
    document.getElementById('categoryModalId').value = '';
    document.getElementById('categoryModalName').value = '';
    document.getElementById('categoryModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('categoryModalName').focus(), 50);
}

function openRenameCategoryModal(id, currentName) {
    categoryModalMode = 'rename';
    document.getElementById('categoryModalTitle').textContent = 'Rename Category';
    document.getElementById('categoryModalId').value = id;
    document.getElementById('categoryModalName').value = currentName;
    document.getElementById('categoryModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('categoryModalName').focus(), 50);
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

async function submitCategoryModal() {
    const name = document.getElementById('categoryModalName').value.trim();
    if (!name) return;
    const id = document.getElementById('categoryModalId').value;
    try {
        if (categoryModalMode === 'add') {
            const res = await axios.post(`${EBASE}/expense-categories`, { name });
            showDarasaToast({ type: 'success', message: res.data.category.status === 'approved' ? 'Category added.' : 'Category proposed — pending approval.' });
            await loadCategoriesForSelects();
            loadCategoryListForBudget();
            if (res.data.category.status === 'approved') {
                document.getElementById('composeCategoryInput').value = name;
                document.getElementById('composeCategory').value = res.data.category.id;
            }
        } else {
            await axios.put(`${EBASE}/expense-categories/${id}`, { name });
            showDarasaToast({ type: 'success', message: 'Category renamed.' });
            await loadCategoriesForSelects();
            loadCategoryListForBudget();
        }
        closeCategoryModal();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed.' });
    }
}

async function deleteCategory(id, name) {
    if (!confirm(`Delete category "${name}"? This cannot be undone.`)) return;
    try {
        await axios.delete(`${EBASE}/expense-categories/${id}`);
        showDarasaToast({ type: 'success', message: 'Category deleted.' });
        await loadCategoriesForSelects();
        loadCategoryListForBudget();
        if (document.getElementById('chartCategory').value === String(id)) {
            document.getElementById('chartCategory').value = '';
            loadChart();
        }
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to delete.' });
    }
}

// ─── Categories & Budget ─────────────────────────────────────────────────
let allCategoriesForBudget = [];

async function loadCategoryListForBudget() {
    const box = document.getElementById('categoryListBox');
    try {
        const res = await axios.get(`${EBASE}/expense-categories`);
        allCategoriesForBudget = res.data.categories || [];
        renderCategoryList();
    } catch (e) {
        box.innerHTML = '<p class="text-red-600 text-sm">Could not load categories.</p>';
    }
}

function filterCategoryList() {
    renderCategoryList();
}

function renderCategoryList() {
    const box = document.getElementById('categoryListBox');
    const q = (document.getElementById('categorySearch')?.value || '').toLowerCase();
    const categories = q
        ? allCategoriesForBudget.filter(c => c.name.toLowerCase().includes(q))
        : allCategoriesForBudget;

    if (!categories.length) {
        box.innerHTML = '<p class="text-gray-400 text-sm">No categories found.</p>';
        return;
    }

    box.innerHTML = categories.map(c => {
        const nameEsc = c.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const isExpired = c.latest_plan_to_date && !c.has_active_plan;
        const planTag = isExpired
            ? `<span class="text-xs text-red-500 flex-shrink-0 ml-1" title="Plan expired ${c.latest_plan_to_date}">expired</span>`
            : (c.status !== 'approved' ? `<span class="text-xs text-amber-600 flex-shrink-0 ml-1">${c.status}</span>` : '');
        const renameBtn = IS_MAIN_ACCOUNTANT
            ? `<button type="button" onclick="openRenameCategoryModal(${c.id}, '${nameEsc}')" class="text-gray-400 hover:text-blue-600 px-1 py-1 text-sm leading-none flex-shrink-0" title="Rename">✎</button>`
            : '';
        const deleteBtn = IS_MAIN_ACCOUNTANT
            ? `<button type="button" onclick="deleteCategory(${c.id}, '${nameEsc}')" class="text-gray-300 hover:text-red-500 px-1 py-1 text-sm leading-none flex-shrink-0" title="Delete">✕</button>`
            : '';

        return `
            <div class="flex items-center gap-0.5 ${c.status !== 'approved' ? 'opacity-60' : ''}">
                <button type="button" onclick="selectBudgetCategory(${c.id}, '${nameEsc}')"
                    class="flex-1 text-left px-2 py-1.5 rounded text-sm hover:bg-rose-50 flex items-center gap-1 min-w-0">
                    <span class="truncate">${c.name}</span>${planTag}
                </button>
                ${renameBtn}${deleteBtn}
            </div>
        `;
    }).join('');
}

function selectBudgetCategory(id, name) {
    document.getElementById('chartCategory').value = id;
    loadChart();
}

async function loadChart() {
    const categoryId = document.getElementById('chartCategory').value;
    const academicYearId = document.getElementById('chartAcademicYear').value;
    if (!academicYearId) return;

    const params = new URLSearchParams({ academic_year_id: academicYearId });
    if (categoryId) params.set('category_id', categoryId);
    // Only send from/to if the user has explicitly set them (override only)
    const from = document.getElementById('chartFrom').value;
    const to = document.getElementById('chartTo').value;
    if (from) params.set('from_date', from);
    if (to) params.set('to_date', to);

    try {
        const res = await axios.get(`${EBASE}/expense-plans/chart?${params.toString()}`);
        document.getElementById('chartHiddenMsg').classList.add('hidden');
        document.getElementById('chartCanvasWrap').classList.remove('hidden');

        if (IS_MAIN_ACCOUNTANT) {
            document.getElementById('chartExpected').textContent = 'TSh ' + fmt(res.data.expected_amount);
        }
        document.getElementById('chartActual').textContent = 'TSh ' + fmt(res.data.actual_amount);

        renderExpenseChart(res.data.timeline || [], res.data.planned_per_bucket || []);

        if (IS_MAIN_ACCOUNTANT) {
            loadBudgetPlanPanel(res.data.monthly_plans || []);
        }
    } catch (e) {
        if (e.response?.status === 403) {
            document.getElementById('chartHiddenMsg').classList.remove('hidden');
            document.getElementById('chartCanvasWrap').classList.add('hidden');
            if (IS_MAIN_ACCOUNTANT) document.getElementById('chartExpected').textContent = 'TSh 0';
            document.getElementById('chartActual').textContent = 'TSh 0';
        } else {
            showDarasaToast({ type: 'error', message: 'Could not load chart data.' });
        }
    }
}

let expenseChartInstance = null;
let chartsScriptLoading = false;

function renderExpenseChart(timeline, plannedPerBucket) {
    if (typeof Chart === 'undefined') {
        if (!chartsScriptLoading) {
            chartsScriptLoading = true;
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => renderExpenseChart(timeline, plannedPerBucket);
            document.head.appendChild(script);
        }
        return;
    }

    const ctx = document.getElementById('expenseChart').getContext('2d');
    if (expenseChartInstance) expenseChartInstance.destroy();

    const datasets = [];
    const budgetArr = Array.isArray(plannedPerBucket) ? plannedPerBucket : [];

    if (IS_MAIN_ACCOUNTANT && budgetArr.some(v => v > 0)) {
        datasets.push({
            label: 'Budget',
            data: budgetArr,
            backgroundColor: '#93c5fd',
            borderRadius: 4,
            order: 2,
        });
    }

    datasets.push({
        label: 'Actual spend',
        data: timeline.map(t => t.amount),
        backgroundColor: '#e11d48',
        borderRadius: 4,
        order: 1,
    });

    expenseChartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels: timeline.map(t => t.label), datasets },
        options: {
            responsive: true,
            plugins: { legend: { display: datasets.length > 1 } },
            scales: { y: { beginAtZero: true } },
        },
    });
}

// ─── Monthly Budget Planning Panel ──────────────────────────────────────
function getMonthsForYear(academicYear) {
    const months = [];
    if (!academicYear?.start_date || !academicYear?.end_date) return months;

    const [sy, sm] = academicYear.start_date.slice(0, 7).split('-').map(Number);
    const [ey, em] = academicYear.end_date.slice(0, 7).split('-').map(Number);

    let cy = sy, cm = sm;
    while (cy < ey || (cy === ey && cm <= em)) {
        const key = `${cy}-${String(cm).padStart(2, '0')}`;
        const label = new Date(cy, cm - 1, 1).toLocaleString('default', { month: 'long', year: 'numeric' });
        months.push({ key, label });
        cm++;
        if (cm > 12) { cm = 1; cy++; }
    }
    return months;
}

function buildPlanState(allMonths, rawPlans) {
    const monthAmounts = {};
    const groups = [];

    for (const p of rawPlans) {
        if (p.type === 'month') {
            monthAmounts[p.month_key] = p.expected_amount;
        } else if (p.type === 'group') {
            groups.push({ fromKey: p.from_month, toKey: p.to_month, amount: p.expected_amount });
        }
    }

    // Mark all months that belong to a group
    const groupedMonths = new Set();
    for (const g of groups) {
        let [gy, gm] = g.fromKey.split('-').map(Number);
        const [ty, tm] = g.toKey.split('-').map(Number);
        while (gy < ty || (gy === ty && gm <= tm)) {
            groupedMonths.add(`${gy}-${String(gm).padStart(2, '0')}`);
            gm++;
            if (gm > 12) { gm = 1; gy++; }
        }
    }

    const state = [];
    for (const m of allMonths) {
        if (groupedMonths.has(m.key)) {
            const group = groups.find(g => g.fromKey === m.key);
            if (group) {
                const toMonth = allMonths.find(x => x.key === group.toKey);
                state.push({
                    type: 'group',
                    fromKey: group.fromKey, toKey: group.toKey,
                    fromLabel: m.label, toLabel: toMonth?.label || group.toKey,
                    amount: group.amount,
                });
            }
            // else: interior month of a group — skip (already added as part of the group entry)
        } else {
            state.push({ type: 'month', key: m.key, label: m.label, amount: monthAmounts[m.key] ?? '' });
        }
    }
    return state;
}

function renderBudgetPlanTable() {
    const box = document.getElementById('budgetPlanBox');

    const rows = _budgetPlanState.map((item, idx) => {
        if (item.type === 'group') {
            const fromShort = item.fromLabel.split(' ')[0];
            const label = `${fromShort} – ${item.toLabel}`;
            return `
                <tr data-plan-idx="${idx}" class="border-t bg-indigo-50">
                    <td class="py-2 pr-3 text-sm font-semibold text-indigo-800 whitespace-nowrap">
                        <span class="mr-1 text-indigo-400 text-xs">▸▸</span>${label}
                    </td>
                    <td class="py-2">
                        <input type="number" step="1" min="0" value="${item.amount || ''}"
                            class="w-full border border-indigo-300 rounded px-2 py-1 text-sm text-right budget-month-input"
                            placeholder="— no budget —" oninput="updateBudgetTotal()">
                    </td>
                    <td class="py-2 pl-2 whitespace-nowrap">
                        <button onclick="ungroupPlan('${item.fromKey}')"
                            class="text-xs text-red-500 hover:text-red-700 font-medium">Ungroup</button>
                    </td>
                </tr>`;
        }
        return `
            <tr data-plan-idx="${idx}" class="border-t">
                <td class="py-2 pr-3 text-sm text-gray-700 whitespace-nowrap">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" class="plan-month-cb" data-idx="${idx}" onchange="onPlanCheckChange()">
                        ${item.label}
                    </label>
                </td>
                <td class="py-2">
                    <input type="number" step="1" min="0" value="${item.amount !== '' ? item.amount : ''}"
                        class="w-full border rounded px-2 py-1 text-sm text-right budget-month-input"
                        placeholder="— no budget —" oninput="updateBudgetTotal()">
                </td>
                <td class="py-2 pl-2"></td>
            </tr>`;
    }).join('');

    box.innerHTML = `
        <div class="flex items-center gap-3 mb-3 min-h-[28px]">
            <span id="groupBtnWrap" class="hidden">
                <button onclick="groupSelectedMonths()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-xs font-semibold">
                    ▸▸ Group selected months
                </button>
            </span>
            <span id="groupHint" class="hidden text-xs text-gray-400 italic">Select 2 or more consecutive months to group them</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full mb-3">
                <thead class="bg-gray-50 text-xs">
                    <tr>
                        <th class="py-2 pr-3 text-left font-medium text-gray-600">Month</th>
                        <th class="py-2 text-right font-medium text-gray-600">Budget (TSh)</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody id="budgetMonthRows">${rows}</tbody>
                <tfoot>
                    <tr class="border-t-2">
                        <td class="py-2 text-sm font-semibold">Total</td>
                        <td class="py-2 text-right text-sm font-bold text-blue-600" id="budgetPlanTotal">TSh 0</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button onclick="saveMonthlyPlans()"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
            Save budget plans
        </button>`;

    updateBudgetTotal();
}

function loadBudgetPlanPanel(rawPlans) {
    if (!IS_MAIN_ACCOUNTANT) return;

    const box = document.getElementById('budgetPlanBox');
    const categoryId = document.getElementById('chartCategory').value;
    const academicYearId = document.getElementById('chartAcademicYear').value;

    if (!categoryId || !academicYearId) {
        _budgetPlanState = [];
        box.innerHTML = '<p class="text-gray-400 text-sm">Select a specific category and academic year above to set monthly budgets.</p>';
        return;
    }

    const academicYear = academicYearsCache.find(y => String(y.id) === String(academicYearId));
    const allMonths = academicYear ? getMonthsForYear(academicYear) : [];

    if (!allMonths.length) {
        _budgetPlanState = [];
        box.innerHTML = '<p class="text-gray-400 text-sm">Could not determine the months for this academic year.</p>';
        return;
    }

    _budgetPlanState = buildPlanState(allMonths, rawPlans || []);
    renderBudgetPlanTable();
}

function updateBudgetTotal() {
    const inputs = [...document.querySelectorAll('.budget-month-input')];
    const total = inputs.reduce((sum, inp) => sum + (parseFloat(inp.value) || 0), 0);
    const el = document.getElementById('budgetPlanTotal');
    if (el) el.textContent = 'TSh ' + fmt(total);
}

function onPlanCheckChange() {
    const checked = [...document.querySelectorAll('.plan-month-cb:checked')];
    const wrap = document.getElementById('groupBtnWrap');
    const hint = document.getElementById('groupHint');
    if (!wrap) return;

    if (checked.length < 2) {
        wrap.classList.add('hidden');
        hint?.classList.toggle('hidden', checked.length === 0);
        return;
    }

    const indices = checked.map(cb => parseInt(cb.dataset.idx)).sort((a, b) => a - b);
    const minIdx = indices[0], maxIdx = indices[indices.length - 1];
    // All state entries between min and max must be type 'month', and all must be checked
    const sliceItems = _budgetPlanState.slice(minIdx, maxIdx + 1);
    const allMonths  = sliceItems.every(e => e.type === 'month');
    const allChecked = allMonths && sliceItems.length === checked.length;

    wrap.classList.toggle('hidden', !allChecked);
    hint?.classList.toggle('hidden', allChecked || checked.length < 2);
}

function groupSelectedMonths() {
    const checked = [...document.querySelectorAll('.plan-month-cb:checked')];
    const indices = checked.map(cb => parseInt(cb.dataset.idx)).sort((a, b) => a - b);
    const minIdx = indices[0], maxIdx = indices[indices.length - 1];

    // Flush any typed amounts from DOM back to state before merging
    document.querySelectorAll('#budgetMonthRows tr[data-plan-idx]').forEach(tr => {
        const i = parseInt(tr.dataset.planIdx);
        const inp = tr.querySelector('.budget-month-input');
        if (!isNaN(i) && inp) _budgetPlanState[i].amount = parseFloat(inp.value) || '';
    });

    const monthsInGroup = _budgetPlanState.slice(minIdx, maxIdx + 1);
    const groupEntry = {
        type: 'group',
        fromKey: monthsInGroup[0].key,
        toKey: monthsInGroup[monthsInGroup.length - 1].key,
        fromLabel: monthsInGroup[0].label,
        toLabel: monthsInGroup[monthsInGroup.length - 1].label,
        amount: '',
    };
    _budgetPlanState.splice(minIdx, maxIdx - minIdx + 1, groupEntry);
    renderBudgetPlanTable();
}

function ungroupPlan(fromKey) {
    const idx = _budgetPlanState.findIndex(e => e.type === 'group' && e.fromKey === fromKey);
    if (idx === -1) return;

    const group = _budgetPlanState[idx];
    const academicYearId = document.getElementById('chartAcademicYear').value;
    const academicYear = academicYearsCache.find(y => String(y.id) === String(academicYearId));
    const allMonths = academicYear ? getMonthsForYear(academicYear) : [];

    const monthEntries = allMonths
        .filter(m => m.key >= group.fromKey && m.key <= group.toKey)
        .map(m => ({ type: 'month', key: m.key, label: m.label, amount: '' }));

    _budgetPlanState.splice(idx, 1, ...monthEntries);
    renderBudgetPlanTable();
}

async function saveMonthlyPlans() {
    const categoryId = document.getElementById('chartCategory').value;
    const academicYearId = document.getElementById('chartAcademicYear').value;
    if (!categoryId || !academicYearId) return;

    // Sync DOM amounts to state
    document.querySelectorAll('#budgetMonthRows tr[data-plan-idx]').forEach(tr => {
        const i = parseInt(tr.dataset.planIdx);
        const inp = tr.querySelector('.budget-month-input');
        if (!isNaN(i) && inp) _budgetPlanState[i].amount = parseFloat(inp.value) || 0;
    });

    const months = _budgetPlanState
        .filter(e => e.type === 'month' && e.amount > 0)
        .map(e => ({ month_key: e.key, expected_amount: e.amount }));

    const groups = _budgetPlanState
        .filter(e => e.type === 'group' && e.amount > 0)
        .map(e => ({ from_month: e.fromKey, to_month: e.toKey, expected_amount: e.amount }));

    try {
        await axios.post(`${EBASE}/expense-categories/${categoryId}/monthly-plans`, {
            academic_year_id: academicYearId,
            months,
            groups,
        });
        showDarasaToast({ type: 'success', message: 'Budget plans saved.' });
        loadChart();
        loadCategoryListForBudget();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to save budget plans.' });
    }
}

// ─── Catalog Item Modal ──────────────────────────────────────────────────
function showAddCatalogItem() {
    document.getElementById('catalogItemName').value = '';
    document.getElementById('catalogItemUnit').value = '';
    document.getElementById('catalogItemModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('catalogItemName').focus(), 50);
}

function closeCatalogItemModal() {
    document.getElementById('catalogItemModal').classList.add('hidden');
}

async function submitCatalogItemModal() {
    const name = document.getElementById('catalogItemName').value.trim();
    const unitType = document.getElementById('catalogItemUnit').value.trim();
    if (!name || !unitType) {
        showDarasaToast({ type: 'error', message: 'Both name and unit type are required.' });
        return;
    }
    try {
        await axios.post(`${EBASE}/expense-items`, { name, unit_type: unitType });
        showDarasaToast({ type: 'success', message: 'Item added.' });
        closeCatalogItemModal();
        loadCatalog();
        loadItemsCache();
    } catch (e) {
        showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to add item.' });
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

// ─── Reports & Log ───────────────────────────────────────────────────────
async function loadLog() {
    const box = document.getElementById('logBox');
    try {
        const res = await axios.get(`${EBASE}/expense-submissions-log`);
        const submissions = res.data.data || [];
        box.innerHTML = submissions.length
            ? submissions.map(s => `
                <button type="button" onclick="openSubmissionDetail(${s.id})"
                    class="w-full text-left border-t py-3 hover:bg-gray-50 px-1">
                    <div class="flex justify-between flex-wrap gap-1">
                        <p class="font-medium text-sm">${s.submission_number} — ${s.category?.name || ''} — TSh ${fmt(s.total_amount)}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full ${s.status === 'denied' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}">${s.status.replace('_', ' ')}</span>
                    </div>
                    <p class="text-xs text-gray-500">${s.transaction_date} · decided ${s.decided_at || ''}${s.submitted_by_name ? ' · by ' + s.submitted_by_name : ''}</p>
                    ${s.decision_note ? `<p class="text-sm text-gray-700 mt-1 italic text-left">"${s.decision_note}"</p>` : ''}
                </button>
            `).join('')
            : '<p class="text-gray-400 text-center py-6">No decisions yet.</p>';
    } catch (e) {
        box.innerHTML = '<p class="text-red-600">Could not load the log.</p>';
    }
}

function downloadReport(type) {
    const params = new URLSearchParams({ academic_year_id: document.getElementById('reportAcademicYear').value });
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
