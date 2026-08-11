@extends('layouts.accountant')

@section('title', 'Team Permissions — Darasa Finance')
@section('page_title', 'Team Permissions')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <a href="{{ route('accountant.dashboard') }}" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to dashboard
        </a>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-slate-900">Team permissions</h1>
            <p class="mt-2 text-sm text-slate-600">As this school's main accountant, you can grant or revoke Edit history (reconciliation) and View activity logs for your school's other accountants.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div id="teamPermissionsTable" class="text-sm text-slate-500">Loading accountants…</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        const accountantPermissionsIndexUrl = '{{ route('accountant.api.accountant-permissions.index') }}';
        const accountantPermissionsUpdateUrlBase = '{{ url('/accountant/api/accountant-permissions') }}';

        async function loadTeamPermissions() {
            const box = document.getElementById('teamPermissionsTable');
            try {
                const res = await axios.get(accountantPermissionsIndexUrl);
                const accountants = res.data.accountants || [];
                if (!accountants.length) {
                    box.innerHTML = '<p class="text-slate-500">No other accountants at this school yet.</p>';
                    return;
                }
                let html = '<table class="w-full border border-slate-200 rounded-lg overflow-hidden"><thead class="bg-slate-100"><tr>' +
                    '<th class="p-2 text-left">Name</th><th class="p-2 text-left">Email</th>' +
                    '<th class="p-2 text-center">Edit history</th><th class="p-2 text-center">View logs</th><th class="p-2"></th>' +
                    '</tr></thead><tbody>';
                accountants.forEach(a => {
                    const mainBadge = a.is_main_accountant ? ' <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Main</span>' : '';
                    html += `<tr class="border-t bg-white"><td class="p-2 font-medium">${a.name}${mainBadge}</td><td class="p-2 text-slate-600">${a.email}</td>
                        <td class="p-2 text-center"><input type="checkbox" id="edit_${a.id}" ${a.can_edit_history ? 'checked' : ''}></td>
                        <td class="p-2 text-center"><input type="checkbox" id="logs_${a.id}" ${a.can_view_logs ? 'checked' : ''}></td>
                        <td class="p-2 text-right"><button type="button" onclick="saveTeamPermissions(${a.id})" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">Save</button></td></tr>`;
                });
                html += '</tbody></table>';
                box.innerHTML = html;
            } catch (e) {
                box.innerHTML = '<p class="text-red-600">' + (e.response?.data?.error || 'Could not load accountants.') + '</p>';
            }
        }

        async function saveTeamPermissions(accountantId) {
            try {
                await axios.put(`${accountantPermissionsUpdateUrlBase}/${accountantId}`, {
                    can_edit_history: document.getElementById('edit_' + accountantId).checked,
                    can_view_logs: document.getElementById('logs_' + accountantId).checked,
                });
                showDarasaToast({ type: 'success', message: 'Permissions saved.' });
            } catch (e) {
                showDarasaToast({ type: 'error', message: e.response?.data?.error || 'Failed to save permissions.' });
            }
        }

        document.addEventListener('DOMContentLoaded', loadTeamPermissions);
    </script>
@endpush
