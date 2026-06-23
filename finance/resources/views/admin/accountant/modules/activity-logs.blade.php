@extends('layouts.accountant')

@section('title', 'Activity logs — Darasa Finance')
@section('page_title', 'Activity logs')

@section('content')
<div class="mx-auto w-full max-w-6xl space-y-4">
    <p class="text-sm text-slate-600">Who did what in the school system — fee voids, reconciliation edits, permission changes, and more.</p>

    <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
            <input type="date" id="filterFrom" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" id="filterTo" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <input type="text" id="filterAction" placeholder="Action filter" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
            <button type="button" onclick="loadLogs(1)" class="bg-blue-600 text-white rounded-lg px-4 py-2 text-sm font-semibold hover:bg-blue-700">Apply</button>
            <button type="button" onclick="clearFilters()" class="border border-slate-300 rounded-lg px-4 py-2 text-sm hover:bg-slate-50">Clear</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-2 text-left">When</th>
                        <th class="p-2 text-left">User</th>
                        <th class="p-2 text-left">Action</th>
                        <th class="p-2 text-left">Description</th>
                    </tr>
                </thead>
                <tbody id="logsBody">
                    <tr><td colspan="4" class="p-6 text-center text-slate-500">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div id="pagination" class="mt-4"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

async function loadLogs(page = 1) {
    const params = new URLSearchParams({ page });
    const from = document.getElementById('filterFrom').value;
    const to = document.getElementById('filterTo').value;
    const action = document.getElementById('filterAction').value.trim();
    if (from) params.set('from_date', from);
    if (to) params.set('to_date', to);
    if (action) params.set('action', action);

    try {
        const res = await axios.get('/api/activity-logs?' + params.toString());
        const data = res.data;
        const rows = data.data || [];
        const tbody = document.getElementById('logsBody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-6 text-center text-slate-500">No log entries found.</td></tr>';
        } else {
            tbody.innerHTML = rows.map(r => `
                <tr class="border-t hover:bg-slate-50">
                    <td class="p-2 whitespace-nowrap text-xs">${new Date(r.created_at).toLocaleString()}</td>
                    <td class="p-2">${r.user_name || '—'}</td>
                    <td class="p-2"><span class="font-mono text-xs bg-slate-100 px-2 py-0.5 rounded">${r.action}</span></td>
                    <td class="p-2 text-slate-700">${r.description || '—'}</td>
                </tr>
            `).join('');
        }
        renderPagination(data);
    } catch (e) {
        document.getElementById('logsBody').innerHTML = '<tr><td colspan="4" class="p-6 text-center text-red-600">Failed to load logs.</td></tr>';
    }
}

function renderPagination(data) {
    const el = document.getElementById('pagination');
    if (!data.last_page || data.last_page <= 1) {
        el.innerHTML = '';
        return;
    }
    let html = '<div class="flex justify-center gap-2 flex-wrap">';
    for (let i = 1; i <= data.last_page; i++) {
        const active = i === data.current_page ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700';
        html += `<button type="button" onclick="loadLogs(${i})" class="${active} px-3 py-1 rounded text-sm">${i}</button>`;
    }
    html += '</div>';
    el.innerHTML = html;
}

function clearFilters() {
    document.getElementById('filterFrom').value = '';
    document.getElementById('filterTo').value = '';
    document.getElementById('filterAction').value = '';
    loadLogs(1);
}

document.addEventListener('DOMContentLoaded', () => loadLogs(1));
</script>
@endpush
