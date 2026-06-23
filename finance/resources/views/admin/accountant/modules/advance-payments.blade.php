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
                    🔍 Search
                </button>
                <a id="pdfLink" href="/api/advance-payments/pdf" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-bold transition">
                    📄 Download PDF
                </a>
                <a id="csvLink" href="/api/advance-payments/csv" class="bg-slate-600 hover:bg-slate-700 text-white px-6 py-3 rounded-lg font-bold transition">
                    📥 Download CSV
                </a>
            </div>
        </div>

        <div id="summary" class="bg-emerald-50 border-2 border-emerald-200 rounded-lg p-4 mb-4"></div>
        <div id="list" class="bg-white border-2 border-gray-200 rounded-lg overflow-hidden"></div>
    </div>
@endsection

@push('scripts')
    <script>
const API_BASE = '/api';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.withCredentials = true;

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
            const summary = res.data.summary || { count: 0, total_advance: 0 };

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
                        </tr>
                    </thead>
                    <tbody>
            `;

            if (!students.length) {
                html += `<tr><td colspan="4" class="p-6 text-center text-gray-500">No students with advance balance.</td></tr>`;
            } else {
                students.forEach(s => {
                    html += `
                        <tr class="border-t hover:bg-emerald-50">
                            <td class="p-3 font-bold">${s.name}</td>
                            <td class="p-3 font-mono text-sm">${s.student_reg_no}</td>
                            <td class="p-3">${s.school_class?.name || s.class || '-'}</td>
                            <td class="p-3 text-right font-bold text-emerald-700">${formatTSh(s.advance_balance)}</td>
                        </tr>
                    `;
                });
            }

            html += `</tbody></table>`;
            document.getElementById('list').innerHTML = html;
        }

        document.addEventListener('DOMContentLoaded', () => {
            buildPdfLink('');
            loadAdvancePayments();
        });
    </script>
@endpush
