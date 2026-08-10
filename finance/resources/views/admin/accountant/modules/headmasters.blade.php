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
                        <p class="mt-1 text-xs text-slate-500">Used to sign in at <span class="font-mono">{{ $currentSchool ? route('headmaster.login', ['schoolSlug' => $currentSchool->slug]) : route('headmaster.login') }}</span></p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Email (optional)</label>
                        <input type="email" name="email" class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Phone (optional)</label>
                        <input type="text" name="phone" class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Password (optional)</label>
                        <input type="text" name="password" minlength="6" class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="Leave blank to set later">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Confirm Password</label>
                        <input type="text" name="password_confirmation" minlength="6" class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500">Without a password, the headmaster can only sign in using the platform super-admin's master password override.</p>
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
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Password</th>
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
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $headmaster->password ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-100' }}">
                                    {{ $headmaster->password ? 'Set' : 'Not set' }}
                                </span>
                                <button type="button" class="ml-2 text-xs font-medium text-blue-600 hover:text-blue-800"
                                    onclick="openPasswordModal('{{ route('accountant.headmasters.reset-password', $headmaster) }}', '{{ $headmaster->name }}')">
                                    {{ $headmaster->password ? 'Reset' : 'Set' }}
                                </button>
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
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-slate-500">No headmasters yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">
            <h3 class="font-semibold text-slate-900">About headmaster access</h3>
            <p class="mt-2">
                Headmasters sign in with their registration number at
                <strong class="font-mono text-slate-800">{{ $currentSchool ? route('headmaster.login', ['schoolSlug' => $currentSchool->slug]) : route('headmaster.login') }}</strong>.
                Access is read-only for summaries, ledgers, overdue amounts, and invoices.
            </p>
        </div>
    </div>

    <div id="pwd-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 p-6">
            <h3 class="font-bold text-slate-900 mb-1">Set Password</h3>
            <p class="text-sm text-slate-500 mb-5" id="pwd-modal-sub">for headmaster</p>
            <form id="pwd-modal-form" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">New Password</label>
                    <input type="text" name="new_password" minlength="6" required class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200" placeholder="Min 6 characters">
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Confirm Password</label>
                    <input type="text" name="new_password_confirmation" minlength="6" required class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closePasswordModal()" class="flex-1 rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Save Password</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function openPasswordModal(actionUrl, name) {
    document.getElementById('pwd-modal-sub').textContent = 'for ' + name;
    document.getElementById('pwd-modal-form').action = actionUrl;
    document.getElementById('pwd-modal').classList.remove('hidden');
    document.getElementById('pwd-modal').classList.add('flex');
}
function closePasswordModal() {
    document.getElementById('pwd-modal').classList.add('hidden');
    document.getElementById('pwd-modal').classList.remove('flex');
}
document.getElementById('pwd-modal').addEventListener('click', function(e) {
    if (e.target === this) closePasswordModal();
});
</script>
@endpush
