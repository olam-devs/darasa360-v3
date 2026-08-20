@extends('layouts.accountant')

@section('title', 'Reports — Darasa Finance')
@section('page_title', 'Reports')

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Financial reports</h2>
        <p class="mt-1 text-sm text-slate-600">API-backed reports for accountants. Use Ledgers, Overdue, and Analytics for day-to-day views.</p>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('reports.income-statement') }}" target="_blank" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
            <h3 class="font-semibold text-blue-800">Income statement</h3>
            <p class="mt-1 text-xs text-slate-500">JSON summary by date range</p>
        </a>
        <a href="{{ route('reports.balance-sheet') }}" target="_blank" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
            <h3 class="font-semibold text-blue-800">Balance sheet</h3>
            <p class="mt-1 text-xs text-slate-500">JSON assets & liabilities</p>
        </a>
        <a href="{{ route('reports.trial-balance') }}" target="_blank" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
            <h3 class="font-semibold text-blue-800">Trial balance</h3>
            <p class="mt-1 text-xs text-slate-500">JSON trial balance</p>
        </a>
        <a href="{{ route('reports.fee-collection') }}" target="_blank" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
            <h3 class="font-semibold text-blue-800">Fee collection</h3>
            <p class="mt-1 text-xs text-slate-500">JSON collection summary</p>
        </a>
        <a href="{{ route('reports.outstanding') }}" target="_blank" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
            <h3 class="font-semibold text-blue-800">Outstanding balances</h3>
            <p class="mt-1 text-xs text-slate-500">JSON overdue summary</p>
        </a>
        <a href="{{ route('accountant.ledgers') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
            <h3 class="font-semibold text-blue-800">Ledgers (UI)</h3>
            <p class="mt-1 text-xs text-slate-500">Student, class & book ledgers</p>
        </a>
        <button onclick="openExcelModal()" class="rounded-xl border-2 border-emerald-300 bg-emerald-50 p-5 shadow-sm hover:border-emerald-500 hover:bg-emerald-100 text-left transition">
            <h3 class="font-semibold text-emerald-800">&#x2b07; Download Excel report</h3>
            <p class="mt-1 text-xs text-slate-500">Fee collection + budget/expenses (.xlsx)</p>
        </button>
    </div>
</div>

{{-- Excel year-picker modal --}}
<div id="excelModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm">
        <div class="bg-emerald-700 text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
            <h3 class="text-lg font-bold">Download Excel Report</h3>
            <button onclick="closeExcelModal()" class="text-white hover:text-gray-200 text-2xl leading-none">&times;</button>
        </div>
        <div class="p-6">
            <label class="block text-sm font-bold mb-2">Select year</label>
            <select id="excelYear" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-sm mb-5">
                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <div class="text-xs text-gray-500 mb-5">
                <strong>Sheet 1</strong> — Fee collection: all students grouped by class, expected &amp; collected per fee item, balances/advances.<br>
                <strong>Sheet 2</strong> — Budget &amp; expenses: budgeted vs actual per category, plus weekly expense breakdown.
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="closeExcelModal()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-bold text-sm transition">Cancel</button>
                <button onclick="downloadExcel()" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-sm transition">
                    Download
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openExcelModal()  { document.getElementById('excelModal').classList.remove('hidden'); }
function closeExcelModal() { document.getElementById('excelModal').classList.add('hidden'); }
function downloadExcel() {
    const year = document.getElementById('excelYear').value;
    window.location.href = `{{ route('api.reports.excel') }}?year=${year}`;
    closeExcelModal();
}
</script>
@endpush
@endsection
