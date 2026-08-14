@extends('layouts.accountant')
@section('title', 'Parent Portal Passwords')

@push('head')
<style>
    .pwd-badge-set   { background:#dcfce7; color:#166534; font-size:11px; padding:2px 8px; border-radius:12px; font-weight:600; }
    .pwd-badge-unset { background:#fef3c7; color:#92400e; font-size:11px; padding:2px 8px; border-radius:12px; font-weight:600; }
    .student-row { display:grid; grid-template-columns:1.6fr 1.6fr 0.8fr 1fr 1fr auto; gap:12px; align-items:center; padding:12px 16px; border-bottom:1px solid #f1f5f9; font-size:13px; }
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

    <!-- Bulk Generate Portal Credentials -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="font-semibold text-slate-800">Generate Portal Emails &amp; Passwords — By Class</div>
                <div class="text-xs text-slate-400 mt-0.5">Generates a portal email (if missing) and a new random password for every student in the class. Download PDF to distribute, or send SMS privately per parent.</div>
            </div>
        </div>
        <div class="p-5">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-44">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Select Class</label>
                    <select id="prov-class" class="inp">
                        <option value="">-- Choose a class --</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" data-name="{{ $class->name }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button onclick="doBulkProvision()" id="prov-btn" class="btn-sm btn-blue py-2.5 px-5">
                        Generate &amp; Set Passwords
                    </button>
                </div>
            </div>
            <div id="prov-warning" class="hidden mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">
                This will reset passwords for ALL students in this class. Existing logins will stop working until parents use the new password.
            </div>
            <!-- Results -->
            <div id="prov-result" class="hidden mt-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="font-semibold text-slate-700 text-sm" id="prov-result-label"></div>
                    <button onclick="downloadCredentialsPdf()" class="btn-sm btn-green py-2 px-4">
                        <i class="fas fa-file-pdf mr-1.5"></i> Download PDF
                    </button>
                </div>
                <div style="overflow-x:auto;">
                    <table id="prov-table" style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead>
                            <tr style="background:#1e40af;color:#fff;">
                                <th style="padding:7px 10px;text-align:left;">#</th>
                                <th style="padding:7px 10px;text-align:left;">Student</th>
                                <th style="padding:7px 10px;text-align:left;">Portal Email</th>
                                <th style="padding:7px 10px;text-align:left;">Temp Password</th>
                                <th style="padding:7px 10px;text-align:center;">SMS</th>
                            </tr>
                        </thead>
                        <tbody id="prov-tbody"></tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-slate-400">Passwords shown above are shown once. Download the PDF or send SMS to parents now before navigating away.</p>
            </div>
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
            <span>Portal Login Email</span>
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

<!-- Send Portal SMS Modal -->
<div id="sms-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="font-bold text-slate-900 mb-1">Send Portal Login via SMS</h3>
        <p class="text-sm text-slate-500 mb-4" id="sms-modal-sub">to parent of <span id="sms-student-name"></span></p>
        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Language</label>
            <div class="flex gap-2">
                <button onclick="setSmsLang('en')" id="lang-en-btn"
                    class="flex-1 btn-sm btn-blue py-2">English</button>
                <button onclick="setSmsLang('sw')" id="lang-sw-btn"
                    class="flex-1 btn-sm py-2" style="background:#f1f5f9;color:#475569;">Kiswahili</button>
            </div>
        </div>
        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Phone Number</label>
            <input type="text" id="sms-phone" class="inp" placeholder="+255...">
        </div>
        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Message</label>
            <textarea id="sms-message" rows="5" class="inp" style="resize:vertical;font-size:12px;"></textarea>
            <div class="text-right text-xs text-slate-400 mt-1" id="sms-char-count"></div>
        </div>
        <div class="flex gap-3">
            <button onclick="closeSmsModal()" class="flex-1 btn-sm py-2.5" style="background:#f1f5f9;color:#475569;">Cancel</button>
            <button onclick="sendPortalSms()" id="sms-send-btn" class="flex-1 btn-sm btn-blue py-2.5">Send SMS</button>
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
            <div>
                ${s.portal_email
                    ? `<span class="font-mono text-slate-700">${s.portal_email}</span>`
                    : `<button class="btn-sm btn-blue" onclick="generatePortalEmail(${s.id})" id="gen-btn-${s.id}"><i class="fas fa-magic mr-1"></i>Generate</button>`}
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

function generatePortalEmail(studentId) {
    const btn = document.getElementById(`gen-btn-${studentId}`);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Generating...'; }
    axios.post(`{{ url('/accountant/api/students') }}/${studentId}/portal-email/generate`, {}, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(r => {
        showToast('Portal email generated', 'success');
        doSearch();
    }).catch(e => {
        showToast(e.response?.data?.message || 'Could not generate portal email', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-magic mr-1"></i>Generate'; }
    });
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
document.getElementById('sms-modal').addEventListener('click', function(e) { if (e.target === this) closeSmsModal(); });
document.getElementById('prov-class').addEventListener('change', function() {
    const w = document.getElementById('prov-warning');
    w.classList.toggle('hidden', !this.value);
});

// ─── Bulk Provision ────────────────────────────────────────────────────────────

let provisionedCredentials = [];
let provisionedClassName = '';

async function doBulkProvision() {
    const classId = document.getElementById('prov-class').value;
    const classOpt = document.querySelector(`#prov-class option[value="${classId}"]`);
    if (!classId) { showToast('Please select a class', 'error'); return; }
    if (!confirm(`Generate/reset portal emails and passwords for ALL students in ${classOpt?.dataset.name ?? 'this class'}? Existing passwords will change.`)) return;

    const btn = document.getElementById('prov-btn');
    btn.disabled = true; btn.textContent = 'Generating…';
    document.getElementById('prov-result').classList.add('hidden');

    try {
        const r = await axios.post(`{{ url('/accountant/api/students/class') }}/${classId}/bulk-provision-portal`, {}, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        provisionedCredentials = r.data.credentials ?? [];
        provisionedClassName = r.data.class_name ?? '';
        renderProvisionedTable(provisionedCredentials, provisionedClassName);
        showToast(`Generated credentials for ${r.data.count} students`, 'success');
    } catch (e) {
        showToast(e.response?.data?.message || 'Generation failed', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Generate & Set Passwords';
    }
}

function renderProvisionedTable(creds, className) {
    const tbody = document.getElementById('prov-tbody');
    tbody.innerHTML = creds.map((c, i) => `
        <tr style="background:${i%2?'#f8fafc':'#fff'};border-bottom:1px solid #e5e7eb;">
            <td style="padding:7px 10px;color:#888;">${i+1}</td>
            <td style="padding:7px 10px;font-weight:600;">${esc(c.name)}</td>
            <td style="padding:7px 10px;font-family:monospace;font-size:11px;color:#1d4ed8;">${esc(c.portal_email ?? '—')}</td>
            <td style="padding:7px 10px;font-family:monospace;font-size:11px;background:#fef2f2;color:#991b1b;border-radius:4px;">${esc(c.temp_password)}</td>
            <td style="padding:7px 10px;text-align:center;">
                <button class="btn-sm btn-blue py-1" onclick='openSmsModal(${JSON.stringify(c)})' style="padding:4px 10px;font-size:11px;">
                    <i class="fas fa-sms mr-1"></i>SMS
                </button>
            </td>
        </tr>
    `).join('');
    document.getElementById('prov-result-label').textContent = `${creds.length} students in ${className} — share privately`;
    document.getElementById('prov-result').classList.remove('hidden');
}

function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

async function downloadCredentialsPdf() {
    if (!provisionedCredentials.length) { showToast('No credentials to download', 'error'); return; }
    try {
        const response = await axios.post('{{ route("accountant.api.students.portal-credentials-pdf") }}', {
            class_name: provisionedClassName,
            credentials: provisionedCredentials,
        }, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            responseType: 'blob',
        });
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        const a = document.createElement('a');
        a.href = url;
        a.download = `portal-credentials-${provisionedClassName}.pdf`;
        document.body.appendChild(a);
        a.click();
        setTimeout(() => { window.URL.revokeObjectURL(url); a.remove(); }, 1000);
    } catch (e) {
        showToast('PDF download failed', 'error');
    }
}

// ─── Portal SMS ────────────────────────────────────────────────────────────────

let smsStudent = null;
let smsLang = 'en';

const SMS_TEMPLATES = {
    en: (s, loginLink) =>
        `Dear parent of ${s.name},\n\nYour child's parent portal login:\nEmail: ${s.portal_email ?? '(not set)'}\nPassword: ${s.temp_password}\nLogin: ${loginLink}\n\nPlease change the password after first login.\n— Darasa Finance`,
    sw: (s, loginLink) =>
        `Mpendwa mzazi wa ${s.name},\n\nMtoto wako anaweza kuingia portal:\nBarua pepe: ${s.portal_email ?? '(haijawekwa)'}\nNenosiri: ${s.temp_password}\nIngia: ${loginLink}\n\nBadilisha nenosiri baada ya kuingia mara ya kwanza.\n— Darasa Finance`,
};

const PARENT_LOGIN_LINK = '{{ config("app.url") }}/parent/login{{ $currentSchool?->slug ? "/" . $currentSchool->slug : "" }}';

function openSmsModal(student) {
    smsStudent = student;
    smsLang = 'en';
    document.getElementById('sms-student-name').textContent = student.name;
    document.getElementById('sms-phone').value = student.parent_phone_1 ?? '';
    setSmsLang('en');
    document.getElementById('sms-modal').classList.remove('hidden');
    document.getElementById('sms-modal').classList.add('flex');
}

function closeSmsModal() {
    document.getElementById('sms-modal').classList.add('hidden');
    document.getElementById('sms-modal').classList.remove('flex');
    smsStudent = null;
}

function setSmsLang(lang) {
    smsLang = lang;
    const enBtn = document.getElementById('lang-en-btn');
    const swBtn = document.getElementById('lang-sw-btn');
    if (lang === 'en') {
        enBtn.className = 'flex-1 btn-sm btn-blue py-2';
        swBtn.className = 'flex-1 btn-sm py-2'; swBtn.style.cssText = 'background:#f1f5f9;color:#475569;';
    } else {
        swBtn.className = 'flex-1 btn-sm btn-blue py-2'; swBtn.style.cssText = '';
        enBtn.className = 'flex-1 btn-sm py-2'; enBtn.style.cssText = 'background:#f1f5f9;color:#475569;';
    }
    const msg = SMS_TEMPLATES[lang](smsStudent, PARENT_LOGIN_LINK);
    document.getElementById('sms-message').value = msg;
    document.getElementById('sms-char-count').textContent = msg.length + ' characters';
}

document.getElementById('sms-message')?.addEventListener('input', function() {
    document.getElementById('sms-char-count').textContent = this.value.length + ' characters';
});

async function sendPortalSms() {
    const phone = document.getElementById('sms-phone').value.trim();
    const message = document.getElementById('sms-message').value.trim();
    if (!phone) { showToast('Phone number is required', 'error'); return; }
    if (!message) { showToast('Message cannot be empty', 'error'); return; }

    const btn = document.getElementById('sms-send-btn');
    btn.disabled = true; btn.textContent = 'Sending…';

    try {
        const r = await axios.post('{{ route("sms.send") }}', {
            recipient_phone: phone,
            message: message,
            student_id: smsStudent?.student_id ?? null,
        }, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        if (r.data.success) {
            showToast('SMS sent', 'success');
            closeSmsModal();
        } else {
            showToast(r.data.message || 'Send failed', 'error');
        }
    } catch (e) {
        showToast(e.response?.data?.message || 'SMS send failed', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Send SMS';
    }
}
</script>
@endpush
