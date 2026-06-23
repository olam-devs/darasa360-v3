@extends('layouts.accountant')
@section('title', 'Parent Portal Passwords')

@push('head')
<style>
    .pwd-badge-set   { background:#dcfce7; color:#166534; font-size:11px; padding:2px 8px; border-radius:12px; font-weight:600; }
    .pwd-badge-unset { background:#fef3c7; color:#92400e; font-size:11px; padding:2px 8px; border-radius:12px; font-weight:600; }
    .student-row { display:grid; grid-template-columns:2fr 1fr 1fr 1fr auto; gap:12px; align-items:center; padding:12px 16px; border-bottom:1px solid #f1f5f9; font-size:13px; }
    .student-row:hover { background:#f8fafc; }
    .student-row:last-child { border-bottom:none; }
    .btn-sm { padding:5px 12px; border-radius:7px; font-size:12px; font-weight:600; border:none; cursor:pointer; transition:all .15s; }
    .btn-blue  { background:#2563eb; color:#fff; }
    .btn-blue:hover  { background:#1d4ed8; }
    .btn-green { background:#16a34a; color:#fff; }
    .btn-green:hover { background:#15803d; }
    .inp { width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; }
    .inp:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
    .card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; }
    .card-header { padding:16px 20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; }
    .toast { position:fixed; bottom:80px; right:20px; padding:12px 20px; border-radius:10px; font-size:13px; font-weight:600; z-index:9999; transform:translateY(20px); opacity:0; transition:all .3s; }
    .toast.show { transform:translateY(0); opacity:1; }
    .toast-success { background:#16a34a; color:#fff; }
    .toast-error   { background:#dc2626; color:#fff; }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Page header -->
    <div>
        <h1 class="text-xl font-bold text-slate-900">Parent Portal Passwords</h1>
        <p class="text-sm text-slate-500 mt-1">Search students by name or registration number and set their portal access password. You can also apply a bulk password to an entire class.</p>
    </div>

    <!-- Bulk password section -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="font-semibold text-slate-800">Bulk Password — By Class</div>
                <div class="text-xs text-slate-400 mt-0.5">Sets the same password for all students in a selected class</div>
            </div>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Select Class</label>
                    <select id="bulk-class" class="inp">
                        <option value="">-- Choose a class --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Password to Set</label>
                    <input type="text" id="bulk-password" class="inp" placeholder="e.g. School@2024">
                </div>
                <div>
                    <button onclick="doBulkPassword()" class="btn-sm btn-blue w-full py-2.5 text-sm">
                        <i class="fas fa-users mr-1.5"></i> Apply to Entire Class
                    </button>
                </div>
            </div>
            <div id="bulk-result" class="mt-3 text-sm hidden"></div>
        </div>
    </div>

    <!-- Search & individual reset -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="font-semibold text-slate-800">Search Students</div>
                <div class="text-xs text-slate-400 mt-0.5">Search by name or registration number, then set individual passwords</div>
            </div>
            <div class="text-xs text-slate-400" id="result-count"></div>
        </div>

        <!-- Search bar -->
        <div class="p-4 border-b border-slate-100 flex gap-3">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" id="search-input" class="inp" style="padding-left:34px;" placeholder="Type student name or reg number...">
            </div>
            <select id="filter-class" class="inp" style="max-width:200px;">
                <option value="">All classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Column headers -->
        <div class="student-row text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-50" style="padding:8px 16px;">
            <span>Student</span>
            <span>Reg No.</span>
            <span>Class</span>
            <span>Status</span>
            <span>Action</span>
        </div>

        <!-- Results container -->
        <div id="student-list">
            <div class="py-12 text-center text-sm text-slate-400">
                <i class="fas fa-search text-2xl mb-3 block opacity-30"></i>
                Search for a student above to get started
            </div>
        </div>
    </div>
</div>

<!-- Set Password Modal -->
<div id="pwd-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 p-6">
        <h3 class="font-bold text-slate-900 mb-1" id="modal-title">Set Password</h3>
        <p class="text-sm text-slate-500 mb-5" id="modal-sub">for student</p>
        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">New Password</label>
            <input type="text" id="modal-password" class="inp" placeholder="Enter a password (min 4 characters)">
        </div>
        <div class="flex gap-3">
            <button onclick="closeModal()" class="flex-1 btn-sm py-2.5" style="background:#f1f5f9;color:#475569;">Cancel</button>
            <button onclick="savePassword()" id="modal-save" class="flex-1 btn-sm btn-blue py-2.5">Set Password</button>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>
@endsection

@push('scripts')
<script>
let selectedStudentId = null;
let searchTimer = null;

document.getElementById('search-input').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(doSearch, 350);
});
document.getElementById('filter-class').addEventListener('change', doSearch);

function doSearch() {
    const q = document.getElementById('search-input').value.trim();
    const classId = document.getElementById('filter-class').value;
    if (!q && !classId) {
        document.getElementById('student-list').innerHTML = '<div class="py-12 text-center text-sm text-slate-400"><i class="fas fa-search text-2xl mb-3 block opacity-30"></i>Search for a student above to get started</div>';
        document.getElementById('result-count').textContent = '';
        return;
    }
    document.getElementById('student-list').innerHTML = '<div class="py-8 text-center text-sm text-slate-400"><i class="fas fa-spinner fa-spin mr-2"></i>Searching...</div>';
    const params = new URLSearchParams({ q, class_id: classId });
    axios.get('{{ route("accountant.api.students.portal-password.search") }}?' + params)
        .then(r => renderStudents(r.data))
        .catch(() => showToast('Search failed', 'error'));
}

function renderStudents(students) {
    document.getElementById('result-count').textContent = students.length + ' student(s) found';
    if (!students.length) {
        document.getElementById('student-list').innerHTML = '<div class="py-10 text-center text-sm text-slate-400">No students found matching your search</div>';
        return;
    }
    document.getElementById('student-list').innerHTML = students.map(s => `
        <div class="student-row" id="row-${s.id}">
            <div>
                <div class="font-medium text-slate-800">${s.name}</div>
            </div>
            <div class="text-slate-500">${s.reg_no}</div>
            <div class="text-slate-500">${s.class}</div>
            <div>
                ${s.has_password
                    ? `<span class="pwd-badge-set">Password Set</span><div class="text-xs text-slate-400 mt-0.5">${s.set_by ?? ''} ${s.set_at ? '· ' + s.set_at : ''}</div>`
                    : '<span class="pwd-badge-unset">No Password</span>'}
            </div>
            <div>
                <button class="btn-sm ${s.has_password ? 'btn-green' : 'btn-blue'}" onclick="openModal(${s.id}, '${s.name.replace(/'/g,"\\'")}')">
                    <i class="fas fa-key mr-1"></i>${s.has_password ? 'Reset' : 'Set'} Password
                </button>
            </div>
        </div>
    `).join('');
}

function openModal(studentId, name) {
    selectedStudentId = studentId;
    document.getElementById('modal-title').textContent = 'Set Portal Password';
    document.getElementById('modal-sub').textContent   = 'Student: ' + name;
    document.getElementById('modal-password').value    = '';
    document.getElementById('pwd-modal').classList.remove('hidden');
    document.getElementById('pwd-modal').classList.add('flex');
    setTimeout(() => document.getElementById('modal-password').focus(), 100);
}

function closeModal() {
    document.getElementById('pwd-modal').classList.add('hidden');
    document.getElementById('pwd-modal').classList.remove('flex');
    selectedStudentId = null;
}

function savePassword() {
    const pwd = document.getElementById('modal-password').value.trim();
    if (pwd.length < 4) { showToast('Password must be at least 4 characters', 'error'); return; }
    const btn = document.getElementById('modal-save');
    btn.disabled = true; btn.textContent = 'Saving...';
    axios.post(`{{ url('/accountant/api/students') }}/${selectedStudentId}/portal-password`, { password: pwd }, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(r => {
        showToast(r.data.message, 'success');
        closeModal();
        doSearch();
    }).catch(e => {
        showToast(e.response?.data?.message || 'Failed to set password', 'error');
    }).finally(() => { btn.disabled = false; btn.textContent = 'Set Password'; });
}

function doBulkPassword() {
    const classId = document.getElementById('bulk-class').value;
    const pwd     = document.getElementById('bulk-password').value.trim();
    if (!classId)       { showToast('Please select a class', 'error'); return; }
    if (pwd.length < 4) { showToast('Password must be at least 4 characters', 'error'); return; }
    if (!confirm('This will set the same password for ALL students in the selected class. Continue?')) return;
    axios.post('{{ route("accountant.api.students.portal-password.bulk") }}', { class_id: classId, password: pwd }, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(r => {
        const el = document.getElementById('bulk-result');
        el.textContent = r.data.message;
        el.className = 'mt-3 text-sm text-green-700 font-semibold';
        el.classList.remove('hidden');
        showToast(r.data.message, 'success');
        doSearch();
    }).catch(e => showToast(e.response?.data?.message || 'Bulk update failed', 'error'));
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast toast-' + type + ' show';
    setTimeout(() => { t.className = 'toast'; }, 3500);
}

document.getElementById('pwd-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('modal-password').addEventListener('keydown', e => { if (e.key === 'Enter') savePassword(); });
</script>
@endpush
