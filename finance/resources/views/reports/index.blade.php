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
    </div>
</div>
@endsection
