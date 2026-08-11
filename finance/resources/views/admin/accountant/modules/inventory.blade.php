@extends('layouts.accountant')

@section('title', 'Inventory — Darasa Finance')
@section('page_title', 'Inventory')

@section('content')
<div class="w-full p-6">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-emerald-600">Inventory</h2>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-300 mb-6 gap-1 flex-wrap">
        <button onclick="switchInvTab('levels')" id="itab-levels"
            class="inv-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-emerald-600 text-white border-emerald-600">
            Stock Levels
        </button>
        <button onclick="switchInvTab('record')" id="itab-record"
            class="inv-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-emerald-50">
            Record Movement
        </button>
        <button onclick="switchInvTab('log')" id="itab-log"
            class="inv-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-emerald-50">
            Movement Log
        </button>
        <button onclick="switchInvTab('catalog')" id="itab-catalog"
            class="inv-tab-btn px-5 py-2 text-sm font-semibold rounded-t border border-b-0 bg-white text-gray-600 border-gray-300 hover:bg-emerald-50">
            Item Catalog
        </button>
    </div>

    <!-- Stock Levels Tab -->
    <div id="ipanel-levels">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-wrap items-end gap-3 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" id="levelsSearch" oninput="loadStockLevels()" placeholder="Item name..."
                        class="border-2 border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="levelsCategory" onchange="loadStockLevels()" class="border-2 border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">All categories</option>
                    </select>
                </div>
                <a href="{{ url('/api/inventory-movements-report/csv') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm">CSV</a>
            </div>
            <div id="stockLevelsTable" class="overflow-x-auto">
                <p class="text-gray-400 text-center py-6">Loading…</p>
            </div>
        </div>
    </div>

    <!-- Record Movement Tab -->
    <div id="ipanel-record" class="hidden">
        <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
            <div class="flex gap-3 mb-5">
                <button type="button" onclick="setMovementType('in')" id="movTypeInBtn"
                    class="flex-1 py-2 rounded font-semibold border-2 border-emerald-600 bg-emerald-600 text-white">Stock In (Received)</button>
                <button type="button" onclick="setMovementType('out')" id="movTypeOutBtn"
                    class="flex-1 py-2 rounded font-semibold border-2 border-gray-300 text-gray-600">Stock Out (Issued)</button>
            </div>
            <form id="movementForm" onsubmit="submitMovement(event)">
                <input type="hidden" id="movementType" value="in">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item</label>
                    <input type="text" id="movementItemSearch" placeholder="Search or type new item..." autocomplete="off"
                        oninput="filterMovementItems()" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
                    <input type="hidden" id="movementItemId">
                    <div id="movementItemResults" class="border border-gray-200 rounded mt-1 max-h-40 overflow-y-auto hidden bg-white shadow"></div>
                    <p id="movementItemUnit" class="text-xs text-gray-500 mt-1"></p>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                        <input type="number" id="movementQuantity" step="0.001" min="0.001" required class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" id="movementDate" required class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
                    </div>
                </div>
                <div id="movementUnitCostRow" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (TSh, optional)</label>
                    <input type="number" id="movementUnitCost" step="0.01" min="0" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div id="movementIssuedToRow" class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Issued To</label>
                    <input type="text" id="movementIssuedTo" placeholder="e.g. Kitchen, Grade 3 classroom" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                    <textarea id="movementNote" rows="2" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm"></textarea>
                </div>
                <button type="submit" id="movementSubmitBtn" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded font-semibold">Record Stock In</button>
            </form>
        </div>
    </div>

    <!-- Movement Log Tab -->
    <div id="ipanel-log" class="hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-wrap items-end gap-3 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select id="logType" onchange="loadMovementLog()" class="border-2 border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="in">Stock In</option>
                        <option value="out">Stock Out</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                    <input type="date" id="logFromDate" onchange="loadMovementLog()" class="border-2 border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="date" id="logToDate" onchange="loadMovementLog()" class="border-2 border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
            <div id="movementLogTable" class="overflow-x-auto">
                <p class="text-gray-400 text-center py-6">Loading…</p>
            </div>
        </div>
    </div>

    <!-- Item Catalog Tab -->
    <div id="ipanel-catalog" class="hidden">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-700">Item catalog</h3>
                <button onclick="openItemModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded text-sm font-semibold">+ Add item</button>
            </div>
            <div id="catalogTable" class="overflow-x-auto">
                <p class="text-gray-400 text-center py-6">Loading…</p>
            </div>
        </div>
    </div>
</div>

{{-- Add/Edit item modal --}}
<div id="itemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-emerald-600 mb-4" id="itemModalTitle">Add Item</h3>
        <form id="itemForm" onsubmit="submitItemForm(event)">
            <input type="hidden" id="itemFormId">
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="itemFormName" required class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit type</label>
                <input type="text" id="itemFormUnit" placeholder="e.g. kg, pieces, litres" required class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category (optional)</label>
                <input type="text" id="itemFormCategory" placeholder="e.g. Kitchen, Stationery, Cleaning" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reorder level (optional)</label>
                <input type="number" id="itemFormReorder" step="0.001" min="0" placeholder="Flag as low stock at or below this quantity" class="w-full border-2 border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded font-semibold">Save</button>
                <button type="button" onclick="closeItemModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2.5 rounded font-semibold">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="messageContainer" class="fixed top-4 right-4 z-[60] w-96"></div>
@endsection

@push('scripts')
<script>
const EBASE = '{{ url('/api') }}';
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

function fmt(n) {
    return parseFloat(n || 0).toLocaleString('en-TZ', { maximumFractionDigits: 3 });
}
function fmtTsh(n) {
    return 'TSh ' + parseFloat(n || 0).toLocaleString('en-TZ', { maximumFractionDigits: 2 });
}

function showToast(message, type = 'success') {
    const box = document.createElement('div');
    const styles = {
        success: 'bg-slate-50 border-slate-700 text-slate-800',
        warning: 'bg-amber-50 border-amber-500 text-amber-800',
        error: 'bg-red-50 border-red-600 text-red-800',
    };
    box.className = `mb-3 rounded-lg border border-l-4 ${styles[type] || styles.success} p-4 shadow-sm`;
    box.innerHTML = `<p class="font-bold">${type === 'success' ? 'Success' : type === 'warning' ? 'Notice' : 'Error'}</p><p class="text-sm">${message}</p>`;
    document.getElementById('messageContainer').appendChild(box);
    setTimeout(() => box.remove(), 6000);
}

// ─── Tabs ───────────────────────────────────────────────────────────────────
function switchInvTab(name) {
    ['levels', 'record', 'log', 'catalog'].forEach(t => {
        document.getElementById('ipanel-' + t).classList.toggle('hidden', t !== name);
        const btn = document.getElementById('itab-' + t);
        if (t === name) {
            btn.classList.add('bg-emerald-600', 'text-white', 'border-emerald-600');
            btn.classList.remove('bg-white', 'text-gray-600', 'border-gray-300');
        } else {
            btn.classList.remove('bg-emerald-600', 'text-white', 'border-emerald-600');
            btn.classList.add('bg-white', 'text-gray-600', 'border-gray-300');
        }
    });
    if (name === 'levels') loadStockLevels();
    if (name === 'log') loadMovementLog();
    if (name === 'catalog') loadCatalog();
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('movementDate').valueAsDate = new Date();
    setMovementType('in');
    loadStockLevels();
    loadCategoriesForFilter();
    loadItemsCache();
});

// ─── Stock Levels ───────────────────────────────────────────────────────────
async function loadStockLevels() {
    const search = document.getElementById('levelsSearch').value;
    const category = document.getElementById('levelsCategory').value;
    try {
        const res = await axios.get(`${EBASE}/inventory-items`, { params: { search, category } });
        const items = res.data.items;
        if (!items.length) {
            document.getElementById('stockLevelsTable').innerHTML = '<p class="text-gray-400 text-center py-6">No items yet. Add one from the Item Catalog tab.</p>';
            return;
        }
        let html = `<table class="w-full border border-gray-200 rounded-lg text-sm">
            <thead class="bg-emerald-50 text-emerald-700">
                <tr><th class="p-3 text-left">Item</th><th class="p-3 text-left">Category</th><th class="p-3 text-right">Current Qty</th><th class="p-3 text-left">Unit</th><th class="p-3 text-center">Status</th></tr>
            </thead><tbody>`;
        items.forEach(i => {
            const badge = i.is_low_stock
                ? '<span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-bold">Low stock</span>'
                : '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-bold">OK</span>';
            html += `<tr class="border-t hover:bg-emerald-50">
                <td class="p-3 font-semibold">${i.name}</td>
                <td class="p-3 text-gray-500">${i.category || '—'}</td>
                <td class="p-3 text-right font-bold">${fmt(i.current_quantity)}</td>
                <td class="p-3 text-gray-500">${i.unit_type}</td>
                <td class="p-3 text-center">${badge}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('stockLevelsTable').innerHTML = html;
    } catch (e) {
        document.getElementById('stockLevelsTable').innerHTML = '<p class="text-red-500 text-center py-4">Error loading stock levels.</p>';
    }
}

async function loadCategoriesForFilter() {
    try {
        const res = await axios.get(`${EBASE}/inventory-categories`);
        const sel = document.getElementById('levelsCategory');
        res.data.categories.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c; opt.textContent = c;
            sel.appendChild(opt);
        });
    } catch (e) { /* non-fatal */ }
}

// ─── Record Movement ────────────────────────────────────────────────────────
let allInventoryItems = [];

async function loadItemsCache() {
    try {
        const res = await axios.get(`${EBASE}/inventory-items`);
        allInventoryItems = res.data.items;
    } catch (e) { /* non-fatal */ }
}

function setMovementType(type) {
    document.getElementById('movementType').value = type;
    const inBtn = document.getElementById('movTypeInBtn');
    const outBtn = document.getElementById('movTypeOutBtn');
    if (type === 'in') {
        inBtn.className = 'flex-1 py-2 rounded font-semibold border-2 border-emerald-600 bg-emerald-600 text-white';
        outBtn.className = 'flex-1 py-2 rounded font-semibold border-2 border-gray-300 text-gray-600';
        document.getElementById('movementUnitCostRow').classList.remove('hidden');
        document.getElementById('movementIssuedToRow').classList.add('hidden');
        document.getElementById('movementSubmitBtn').textContent = 'Record Stock In';
    } else {
        outBtn.className = 'flex-1 py-2 rounded font-semibold border-2 border-emerald-600 bg-emerald-600 text-white';
        inBtn.className = 'flex-1 py-2 rounded font-semibold border-2 border-gray-300 text-gray-600';
        document.getElementById('movementUnitCostRow').classList.add('hidden');
        document.getElementById('movementIssuedToRow').classList.remove('hidden');
        document.getElementById('movementSubmitBtn').textContent = 'Record Stock Out';
    }
}

function filterMovementItems() {
    const q = document.getElementById('movementItemSearch').value.toLowerCase();
    const box = document.getElementById('movementItemResults');
    document.getElementById('movementItemId').value = '';
    document.getElementById('movementItemUnit').textContent = '';
    if (!q) { box.classList.add('hidden'); box.innerHTML = ''; return; }
    const matches = allInventoryItems.filter(i => i.name.toLowerCase().includes(q)).slice(0, 8);
    if (!matches.length) {
        box.innerHTML = '<div class="p-2 text-sm text-gray-500">No matching item - add it from the Item Catalog tab first.</div>';
        box.classList.remove('hidden');
        return;
    }
    box.innerHTML = matches.map(i => `<div class="p-2 text-sm hover:bg-emerald-50 cursor-pointer" onclick="selectMovementItem(${i.id}, '${i.name.replace(/'/g, "\\'")}', '${i.unit_type}', ${i.current_quantity})">${i.name} <span class="text-gray-400">(${fmt(i.current_quantity)} ${i.unit_type} on hand)</span></div>`).join('');
    box.classList.remove('hidden');
}

function selectMovementItem(id, name, unit, currentQty) {
    document.getElementById('movementItemId').value = id;
    document.getElementById('movementItemSearch').value = name;
    document.getElementById('movementItemUnit').textContent = `${fmt(currentQty)} ${unit} currently on hand`;
    document.getElementById('movementItemResults').classList.add('hidden');
}

async function submitMovement(event) {
    event.preventDefault();
    const itemId = document.getElementById('movementItemId').value;
    if (!itemId) {
        showToast('Please select an item from the search results.', 'warning');
        return;
    }
    const type = document.getElementById('movementType').value;
    const payload = {
        inventory_item_id: itemId,
        type,
        quantity: document.getElementById('movementQuantity').value,
        movement_date: document.getElementById('movementDate').value,
        note: document.getElementById('movementNote').value || null,
    };
    if (type === 'in') {
        payload.unit_cost = document.getElementById('movementUnitCost').value || null;
    } else {
        payload.issued_to = document.getElementById('movementIssuedTo').value || null;
    }

    const btn = document.getElementById('movementSubmitBtn');
    btn.disabled = true;
    try {
        await axios.post(`${EBASE}/inventory-movements`, payload);
        showToast(type === 'in' ? 'Stock received and recorded.' : 'Stock issued and recorded.');
        document.getElementById('movementForm').reset();
        document.getElementById('movementDate').valueAsDate = new Date();
        document.getElementById('movementItemId').value = '';
        document.getElementById('movementItemUnit').textContent = '';
        setMovementType(type);
        loadItemsCache();
    } catch (e) {
        showToast(e.response?.data?.error || 'Could not record this movement.', 'error');
    } finally {
        btn.disabled = false;
    }
}

// ─── Movement Log ───────────────────────────────────────────────────────────
async function loadMovementLog() {
    const params = {
        type: document.getElementById('logType').value || undefined,
        from_date: document.getElementById('logFromDate').value || undefined,
        to_date: document.getElementById('logToDate').value || undefined,
    };
    try {
        const res = await axios.get(`${EBASE}/inventory-movements`, { params });
        const rows = res.data.data;
        if (!rows.length) {
            document.getElementById('movementLogTable').innerHTML = '<p class="text-gray-400 text-center py-6">No movements recorded yet.</p>';
            return;
        }
        let html = `<table class="w-full border border-gray-200 rounded-lg text-sm">
            <thead class="bg-emerald-50 text-emerald-700">
                <tr><th class="p-3 text-left">Date</th><th class="p-3 text-left">Item</th><th class="p-3 text-center">Type</th><th class="p-3 text-right">Qty</th><th class="p-3 text-left">Details</th><th class="p-3 text-center">Action</th></tr>
            </thead><tbody>`;
        rows.forEach(m => {
            const badge = m.type === 'in'
                ? '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-bold">In</span>'
                : '<span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs font-bold">Out</span>';
            const detail = m.type === 'in'
                ? (m.unit_cost ? `Cost: ${fmtTsh(m.unit_cost)}/unit` : '—')
                : (m.issued_to || '—');
            html += `<tr class="border-t hover:bg-emerald-50">
                <td class="p-3">${m.movement_date}</td>
                <td class="p-3 font-semibold">${m.item?.name || '—'}</td>
                <td class="p-3 text-center">${badge}</td>
                <td class="p-3 text-right">${fmt(m.quantity)} ${m.item?.unit_type || ''}</td>
                <td class="p-3 text-gray-500">${detail}${m.note ? ' · ' + m.note : ''}</td>
                <td class="p-3 text-center"><button onclick="deleteMovement(${m.id})" class="text-red-400 hover:text-red-600 text-xs">Delete</button></td>
            </tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('movementLogTable').innerHTML = html;
    } catch (e) {
        document.getElementById('movementLogTable').innerHTML = '<p class="text-red-500 text-center py-4">Error loading movement log.</p>';
    }
}

async function deleteMovement(id) {
    if (!confirm('Delete this movement? This corrects the stock level - use it to undo a mistake, not to hide history.')) return;
    try {
        await axios.delete(`${EBASE}/inventory-movements/${id}`);
        showToast('Movement deleted.');
        loadMovementLog();
        loadItemsCache();
    } catch (e) {
        showToast(e.response?.data?.error || 'Could not delete this movement.', 'error');
    }
}

// ─── Item Catalog ───────────────────────────────────────────────────────────
async function loadCatalog() {
    try {
        const res = await axios.get(`${EBASE}/inventory-items`);
        const items = res.data.items;
        if (!items.length) {
            document.getElementById('catalogTable').innerHTML = '<p class="text-gray-400 text-center py-6">No items yet.</p>';
            return;
        }
        let html = `<table class="w-full border border-gray-200 rounded-lg text-sm">
            <thead class="bg-emerald-50 text-emerald-700">
                <tr><th class="p-3 text-left">Name</th><th class="p-3 text-left">Unit</th><th class="p-3 text-left">Category</th><th class="p-3 text-right">Reorder Level</th><th class="p-3 text-center">Action</th></tr>
            </thead><tbody>`;
        items.forEach(i => {
            html += `<tr class="border-t hover:bg-emerald-50">
                <td class="p-3 font-semibold">${i.name}</td>
                <td class="p-3 text-gray-500">${i.unit_type}</td>
                <td class="p-3 text-gray-500">${i.category || '—'}</td>
                <td class="p-3 text-right">${i.reorder_level !== null ? fmt(i.reorder_level) : '—'}</td>
                <td class="p-3 text-center">
                    <button onclick='openItemModal(${JSON.stringify(i)})' class="text-emerald-600 hover:underline text-xs mr-2">Edit</button>
                    <button onclick="deleteItem(${i.id})" class="text-red-400 hover:text-red-600 text-xs">Delete</button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('catalogTable').innerHTML = html;
    } catch (e) {
        document.getElementById('catalogTable').innerHTML = '<p class="text-red-500 text-center py-4">Error loading catalog.</p>';
    }
}

function openItemModal(item = null) {
    document.getElementById('itemModalTitle').textContent = item ? 'Edit Item' : 'Add Item';
    document.getElementById('itemFormId').value = item?.id || '';
    document.getElementById('itemFormName').value = item?.name || '';
    document.getElementById('itemFormUnit').value = item?.unit_type || '';
    document.getElementById('itemFormCategory').value = item?.category || '';
    document.getElementById('itemFormReorder').value = item?.reorder_level ?? '';
    document.getElementById('itemModal').classList.remove('hidden');
}
function closeItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
    document.getElementById('itemForm').reset();
}

async function submitItemForm(event) {
    event.preventDefault();
    const id = document.getElementById('itemFormId').value;
    const payload = {
        name: document.getElementById('itemFormName').value,
        unit_type: document.getElementById('itemFormUnit').value,
        category: document.getElementById('itemFormCategory').value || null,
        reorder_level: document.getElementById('itemFormReorder').value || null,
    };
    try {
        if (id) {
            await axios.put(`${EBASE}/inventory-items/${id}`, payload);
            showToast('Item updated.');
        } else {
            await axios.post(`${EBASE}/inventory-items`, payload);
            showToast('Item added.');
        }
        closeItemModal();
        loadCatalog();
        loadItemsCache();
        loadCategoriesForFilter();
    } catch (e) {
        showToast(e.response?.data?.error || 'Could not save this item.', 'error');
    }
}

async function deleteItem(id) {
    if (!confirm('Delete this item? Only possible if it has no recorded stock movements.')) return;
    try {
        await axios.delete(`${EBASE}/inventory-items/${id}`);
        showToast('Item deleted.');
        loadCatalog();
        loadItemsCache();
    } catch (e) {
        showToast(e.response?.data?.error || 'Could not delete this item.', 'error');
    }
}
</script>
@endpush
