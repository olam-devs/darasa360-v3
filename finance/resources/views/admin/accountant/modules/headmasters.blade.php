@extends('layouts.accountant')

@section('title', 'Headmasters — Darasa Finance')
@section('page_title', 'Headmasters')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <a href="{{ route('accountant.dashboard') }}" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to dashboard
        </a>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-slate-900">Headmaster / owner access</h1>
            <p class="mt-2 text-sm text-slate-600">Read-only portal logins for school leadership.</p>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Add headmaster</h2>
            <form method="POST" action="{{ route('accountant.headmasters.store') }}">
                @csrf
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Name *</label>
                        <input type="text" name="name" required class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Registration number *</label>
                        <input type="text" name="registration_number" required placeholder="e.g. HM001" class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        <p class="mt-1 text-xs text-slate-500">Used to sign in at /headmaster/login</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Email (optional)</label>
                        <input type="email" name="email" class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Phone (optional)</label>
                        <input type="text" name="phone" class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="inline-flex rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Add headmaster
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Headmasters</h2>
            </div>
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Registration</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Phone</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($headmasters as $headmaster)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $headmaster->name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <code class="rounded bg-slate-100 px-2 py-1 text-xs">{{ $headmaster->registration_number }}</code>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $headmaster->email ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $headmaster->phone ?? '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $headmaster->is_active ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200' }}">
                                    {{ $headmaster->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm">
                                <form method="POST" action="{{ route('accountant.headmasters.toggle', $headmaster) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="font-medium text-slate-700 hover:text-slate-900">
                                        {{ $headmaster->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <span class="mx-2 text-slate-300">|</span>
                                <form method="POST" action="{{ route('accountant.headmasters.destroy', $headmaster) }}" class="inline" onsubmit="return confirm('Delete this headmaster?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">No headmasters yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">
            <h3 class="font-semibold text-slate-900">About headmaster access</h3>
            <p class="mt-2">
                Headmasters sign in with their registration number at <strong class="font-mono text-slate-800">/headmaster/login</strong>.
                Access is read-only for summaries, ledgers, overdue amounts, and invoices.
            </p>
        </div>
    </div>
@endsection
