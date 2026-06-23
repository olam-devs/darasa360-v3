@extends('layouts.headmaster')

@section('title', 'Headmaster Dashboard — Darasa Finance')

@section('content')
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 p-8 text-white shadow-lg flex items-start justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-2xl font-bold">Welcome, {{ session('headmaster_name') }}</h2>
            <p class="mt-1 text-blue-100">School financial overview (read-only).</p>
        </div>

        {{-- Cross-system jump button — only visible when school has Academics + cross_jump_enabled --}}
        @if(isset($school) && $school->canCrossJump())
            <form method="POST" action="{{ route('headmaster.jump-to-academics') }}">
                @csrf
                <button type="submit"
                    class="flex items-center gap-2 bg-white text-blue-700 font-semibold px-4 py-2 rounded-xl shadow hover:bg-blue-50 transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Open Academics
                </button>
            </form>
        @endif
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total students</p>
            <p class="text-3xl font-bold text-slate-900">{{ number_format($totalStudents) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Expected fees</p>
            <p class="text-2xl font-bold text-slate-900">TSh {{ number_format($totalFeesExpected, 0) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Collected fees</p>
            <p class="text-2xl font-bold text-emerald-700">TSh {{ number_format($totalFeesCollected, 0) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Collection rate</p>
            <p class="text-3xl font-bold text-blue-800">{{ number_format($collectionRate, 1) }}%</p>
        </div>
    </div>

    <div class="mb-8">
        <h3 class="mb-4 text-lg font-semibold text-slate-900">Financial reports</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('headmaster.ledgers') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
                <h4 class="font-semibold text-blue-800">Student ledgers</h4>
                <p class="mt-1 text-xs text-slate-500">Individual payment records</p>
            </a>
            <a href="{{ route('headmaster.particular-ledger') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
                <h4 class="font-semibold text-green-800">Particular ledger</h4>
                <p class="mt-1 text-xs text-slate-500">Fee type collections</p>
            </a>
            <a href="{{ route('headmaster.overdue') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
                <h4 class="font-semibold text-red-800">Overdue payments</h4>
                <p class="mt-1 text-xs text-slate-500">Outstanding balances</p>
            </a>
            <a href="{{ route('headmaster.invoices') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-300">
                <h4 class="font-semibold text-purple-800">Student invoices</h4>
                <p class="mt-1 text-xs text-slate-500">Download fee statements</p>
            </a>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-slate-900">Recent transactions</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Student</th>
                        <th class="px-3 py-2 text-left">Particular</th>
                        <th class="px-3 py-2 text-left">Book</th>
                        <th class="px-3 py-2 text-right">Amount</th>
                        <th class="px-3 py-2 text-center">Type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentTransactions as $transaction)
                        @php
                            $amount = max((float) $transaction->debit, (float) $transaction->credit);
                        @endphp
                        <tr>
                            <td class="px-3 py-2">{{ $transaction->date }}</td>
                            <td class="px-3 py-2">{{ $transaction->student->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $transaction->particular->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $transaction->book->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-right font-semibold">TSh {{ number_format($amount, 0) }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $transaction->voucher_type === 'Receipt' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $transaction->voucher_type }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No recent transactions</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
