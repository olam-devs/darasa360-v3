@extends('layouts.accountant')

@section('title', 'School Settings — Darasa Finance')
@section('page_title', 'School settings')

@section('content')
    @php
        $pdfShowLogo = old('show_logo_on_pdfs') !== null
            ? old('show_logo_on_pdfs') === '1'
            : (bool) ($settings->show_logo_on_pdfs ?? true);
    @endphp
    <div class="mx-auto w-full max-w-6xl space-y-4">
            @if(session('success'))
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-slate-800">
                {{ session('success') }}
            </div>
            @endif

            @if($errors->any())
            <div class="bg-red-100 border-2 border-red-500 text-red-800 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="mb-6 text-xl font-semibold text-slate-900">School profile</h2>

                <form action="{{ route('accountant.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold mb-2">School Name *</label>
                            <input id="school_name" type="text" name="school_name" value="{{ old('school_name', $settings->school_name) }}" required
                                class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2">P.O. Box</label>
                            <input id="po_box" type="text" name="po_box" value="{{ old('po_box', $settings->po_box) }}"
                                class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2">Region</label>
                            <input id="region" type="text" name="region" value="{{ old('region', $settings->region) }}"
                                class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2">Phone</label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone', $settings->phone) }}"
                                class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $settings->email) }}"
                                class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2">Office WhatsApp number</label>
                            <input id="office_whatsapp_number" type="text" name="office_whatsapp_number" value="{{ old('office_whatsapp_number', $settings->office_whatsapp_number ?? '') }}"
                                placeholder="+255..."
                                class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                            <p class="mt-1 text-xs text-gray-500">Shown to parents; connect this number to <code class="text-xs">POST /api/messenger/parent</code> via your SMS/WhatsApp provider.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2">Parent messenger PIN</label>
                            <input id="parent_messenger_pin" type="text" name="parent_messenger_pin" value="{{ old('parent_messenger_pin', $settings->parent_messenger_pin ?? '') }}"
                                placeholder="School-issued code for parents"
                                class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                            <p class="mt-1 text-xs text-gray-500">Parents include this PIN when messaging the school number to receive fee balances by phone.</p>
                        </div>

                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold mb-2">School Logo</label>
                        <p class="text-xs text-gray-600 mb-3">Upload a logo (JPEG, PNG, GIF - Max 2MB)</p>

                        @if($settings->logo_path)
                        <div class="mb-4 p-4 bg-gray-50 border-2 border-gray-300 rounded">
                            <p class="text-sm font-semibold mb-2">Current Logo:</p>
                            <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="School Logo" class="max-w-xs max-h-40 border-2 border-gray-300 rounded">
                        </div>
                        @endif
                        <input id="logo_input" type="file" name="logo" accept="image/*"
                            class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">

                        <!-- Live preview -->
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gradient-to-br from-slate-50 to-white border border-slate-200 rounded-xl p-4">
                                <p class="text-sm font-bold text-slate-700 mb-2">Live Logo Preview</p>
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center overflow-hidden">
                                        <img id="logo_preview_img"
                                             src="{{ $settings->logo_path ? asset('storage/' . $settings->logo_path) : '' }}"
                                             alt="Logo Preview"
                                             class="w-full h-full object-contain {{ $settings->logo_path ? '' : 'hidden' }}">
                                        <div id="logo_preview_placeholder" class="{{ $settings->logo_path ? 'hidden' : '' }} text-slate-400 text-xs text-center px-2">
                                            No logo selected
                                        </div>
                                    </div>
                                    <div class="text-xs text-slate-600 leading-relaxed">
                                        <p class="font-semibold text-slate-700">Tip</p>
                                        <p>Square logos look best. Transparent PNG recommended.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4">
                                <p class="mb-2 text-sm font-bold text-slate-800">PDF header preview</p>
                                <div class="mb-3 flex items-start gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <input type="hidden" name="show_logo_on_pdfs" value="0">
                                    <input type="checkbox" name="show_logo_on_pdfs" value="1" id="show_logo_on_pdfs"
                                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        {{ $pdfShowLogo ? 'checked' : '' }}>
                                    <label for="show_logo_on_pdfs" class="text-xs leading-snug text-slate-700">
                                        <span class="font-semibold text-slate-900">Show logo on PDFs</span>
                                        <span class="block text-slate-500">When off, downloaded statements and ledgers omit the logo (name and contacts still print).</span>
                                    </label>
                                </div>
                                <p class="mb-3 text-xs text-slate-600">This preview updates as you type. It shows how your header will appear on downloaded PDFs.</p>

                                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                                    <div class="flex items-start gap-3">
                                        <div id="pdf_header_logo_wrap" class="shrink-0 {{ $pdfShowLogo ? '' : 'hidden' }}">
                                        <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden">
                                            <img id="pdf_logo_preview"
                                                 src="{{ $settings->logo_path ? asset('storage/' . $settings->logo_path) : '' }}"
                                                 alt="PDF Logo Preview"
                                                 class="w-full h-full object-contain {{ $settings->logo_path ? '' : 'hidden' }}">
                                            <div id="pdf_logo_placeholder" class="{{ $settings->logo_path ? 'hidden' : '' }} text-slate-400 text-[10px] text-center px-1">
                                                Logo
                                            </div>
                                        </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <h3 id="pdf_school_name" class="text-base font-extrabold text-slate-900 leading-tight">
                                                        {{ $settings->school_name ?? 'School Name' }}
                                                    </h3>
                                                    <p id="pdf_contacts" class="text-[11px] text-slate-600 mt-1 leading-snug">
                                                        <!-- Filled by JS -->
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-[10px] text-slate-500 font-semibold">DOCUMENT</p>
                                                    <p class="text-sm font-extrabold text-slate-900">Fee Ledger</p>
                                                    <p class="text-[11px] text-slate-600">Date: {{ \Carbon\Carbon::now()->format('d M Y') }}</p>
                                                </div>
                                            </div>
                                            <div class="mt-3 border-t border-dashed border-slate-200 pt-3">
                                                <div class="grid grid-cols-3 gap-2 text-[11px]">
                                                    <div class="bg-slate-50 border border-slate-200 rounded px-2 py-1">
                                                        <span class="text-slate-500">Student</span><br>
                                                        <span class="font-semibold text-slate-800">S1001 • Student 1</span>
                                                    </div>
                                                    <div class="bg-slate-50 border border-slate-200 rounded px-2 py-1">
                                                        <span class="text-slate-500">Class</span><br>
                                                        <span class="font-semibold text-slate-800">Form 1</span>
                                                    </div>
                                                    <div class="bg-slate-50 border border-slate-200 rounded px-2 py-1">
                                                        <span class="text-slate-500">Totals</span><br>
                                                        <span class="font-semibold text-slate-800">Expected 100,000 • Paid 40,000</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700">
                            Save settings
                        </button>
                        <a href="{{ route('accountant.dashboard') }}" class="rounded-lg border border-slate-200 bg-white px-6 py-3 font-semibold text-slate-700 transition hover:bg-slate-50">
                            Back to dashboard
                        </a>
                    </div>
                </form>

                <!-- Accountant permissions (local users) -->
                <div class="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Accountant access</h3>
                    <p class="text-sm text-slate-600 mb-4">Control who can edit historical ledger data (reconciliation) and who can view activity logs.</p>
                    <div id="accountantPermissionsTable" class="text-sm text-slate-500">Loading accountants…</div>
                </div>

                <!-- Bank Accounts Section -->
                <div class="mt-8 bg-gray-50 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Bank accounts</h3>
                        <button type="button" onclick="showAddBankModal()" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                            + Add Bank Account
                        </button>
                    </div>

                    <div id="bankAccountsTable"></div>
                </div>

                <!-- Academic Years Section -->
                <div class="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Academic years</h3>
                        <button type="button" onclick="showAddAcademicYearModal()" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                            + Add Academic Year
                        </button>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Manage academic years for fee assignments. The current year will be pre-selected when assigning fees.</p>

                    <div id="academicYearsTable"></div>
                </div>
            </div>
    </div>

    <!-- Add/Edit Bank Account Modal -->
    <div id="bankModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 id="bankModalTitle" class="text-xl font-bold mb-4">Add Bank Account</h3>
            <form id="bankForm" onsubmit="saveBankAccount(event)">
                <input type="hidden" id="bank_id">
                <div class="mb-4">
                    <label class="block font-bold mb-2">Bank Name <span class="text-red-500">*</span></label>
                    <input type="text" id="bank_name" required
                           placeholder="e.g., CRDB Bank, NMB Bank"
                           class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                </div>
                <div class="mb-4">
                    <label class="block font-bold mb-2">Account Number <span class="text-red-500">*</span></label>
                    <input type="text" id="account_number" required
                           placeholder="e.g., 0150123456789"
                           class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-700">
                        Save
                    </button>
                    <button type="button" onclick="closeBankModal()" class="flex-1 rounded-lg border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Academic Year Modal -->
    <div id="academicYearModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 id="academicYearModalTitle" class="text-xl font-bold mb-4">Add Academic Year</h3>
            <form id="academicYearForm" onsubmit="saveAcademicYear(event)">
                <input type="hidden" id="academic_year_id">
                <div class="mb-4">
                    <label class="block font-bold mb-2">Year Name <span class="text-red-500">*</span></label>
                    <input type="text" id="year_name" required
                           placeholder="e.g., 2024/2025"
                           class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                </div>
                <div class="mb-4">
                    <label class="block font-bold mb-2">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" id="start_date" required
                           class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                </div>
                <div class="mb-4">
                    <label class="block font-bold mb-2">End Date <span class="text-red-500">*</span></label>
                    <input type="date" id="end_date" required
                           class="w-full border-2 border-gray-300 rounded px-4 py-2 focus:border-slate-500 focus:outline-none">
                </div>
                <div class="mb-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="is_current" class="h-5 w-5 rounded border-gray-300 text-slate-900 focus:ring-slate-400">
                        <span class="font-bold">Set as Current Academic Year</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">This will be the default year when assigning fees</p>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-700">
                        Save
                    </button>
                    <button type="button" onclick="closeAcademicYearModal()" class="flex-1 rounded-lg border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

        async function loadAccountantPermissions() {
            const box = document.getElementById('accountantPermissionsTable');
            if (!box) return;
            try {
                const res = await axios.get('/api/accountant-users');
                const users = res.data.users || [];
                if (!users.length) {
                    box.innerHTML = '<p class="text-slate-500">No accountant users found.</p>';
                    return;
                }
                let html = '<table class="w-full border border-slate-200 rounded-lg overflow-hidden"><thead class="bg-slate-100"><tr><th class="p-2 text-left">Name</th><th class="p-2 text-left">Email</th><th class="p-2 text-center">Edit history</th><th class="p-2 text-center">View logs</th><th class="p-2"></th></tr></thead><tbody>';
                users.forEach(u => {
                    html += `<tr class="border-t bg-white"><td class="p-2 font-medium">${u.name}</td><td class="p-2 text-slate-600">${u.email}</td>
                        <td class="p-2 text-center"><input type="checkbox" id="edit_${u.id}" ${u.can_edit_history ? 'checked' : ''}></td>
                        <td class="p-2 text-center"><input type="checkbox" id="logs_${u.id}" ${u.can_view_logs ? 'checked' : ''}></td>
                        <td class="p-2 text-right"><button type="button" onclick="saveUserPermissions(${u.id})" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">Save</button></td></tr>`;
                });
                html += '</tbody></table>';
                box.innerHTML = html;
            } catch (e) {
                box.innerHTML = '<p class="text-red-600">Could not load accountant users.</p>';
            }
        }

        async function saveUserPermissions(userId) {
            try {
                await axios.put(`/api/accountant-users/${userId}/permissions`, {
                    can_edit_history: document.getElementById('edit_' + userId).checked,
                    can_view_logs: document.getElementById('logs_' + userId).checked,
                });
                alert('Permissions saved.');
            } catch (e) {
                alert(e.response?.data?.error || 'Failed to save permissions.');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadBankAccounts();
            loadAcademicYears();
            initSettingsPreviews();
            loadAccountantPermissions();
        });

        function initSettingsPreviews() {
            const logoInput = document.getElementById('logo_input');
            const logoImg = document.getElementById('logo_preview_img');
            const logoPlaceholder = document.getElementById('logo_preview_placeholder');
            const pdfLogoImg = document.getElementById('pdf_logo_preview');
            const pdfLogoPlaceholder = document.getElementById('pdf_logo_placeholder');

            const schoolNameEl = document.getElementById('school_name');
            const poBoxEl = document.getElementById('po_box');
            const regionEl = document.getElementById('region');
            const phoneEl = document.getElementById('phone');
            const emailEl = document.getElementById('email');

            const pdfSchoolName = document.getElementById('pdf_school_name');
            const pdfContacts = document.getElementById('pdf_contacts');
            const showLogoPdf = document.getElementById('show_logo_on_pdfs');
            const pdfHeaderLogoWrap = document.getElementById('pdf_header_logo_wrap');

            function syncPdfLogoVisibility() {
                if (!pdfHeaderLogoWrap || !showLogoPdf) return;
                pdfHeaderLogoWrap.classList.toggle('hidden', !showLogoPdf.checked);
            }

            function updatePdfTextPreview() {
                const schoolName = (schoolNameEl?.value || '').trim() || 'School Name';
                const po = (poBoxEl?.value || '').trim();
                const region = (regionEl?.value || '').trim();
                const phone = (phoneEl?.value || '').trim();
                const email = (emailEl?.value || '').trim();

                pdfSchoolName.textContent = schoolName;

                const parts = [];
                if (po) parts.push(`P.O. Box ${po}`);
                if (region) parts.push(region);
                if (phone) parts.push(`Tel: ${phone}`);
                if (email) parts.push(email);

                pdfContacts.textContent = parts.length ? parts.join(' • ') : 'Add contacts (P.O. Box, region, phone, email) to appear here.';
            }

            function setLogoPreview(src) {
                if (src) {
                    if (logoImg) {
                        logoImg.src = src;
                        logoImg.classList.remove('hidden');
                    }
                    if (logoPlaceholder) logoPlaceholder.classList.add('hidden');
                    if (pdfLogoImg) {
                        pdfLogoImg.src = src;
                        pdfLogoImg.classList.remove('hidden');
                    }
                    if (pdfLogoPlaceholder) pdfLogoPlaceholder.classList.add('hidden');
                } else {
                    if (logoImg) logoImg.classList.add('hidden');
                    if (logoPlaceholder) logoPlaceholder.classList.remove('hidden');
                    if (pdfLogoImg) pdfLogoImg.classList.add('hidden');
                    if (pdfLogoPlaceholder) pdfLogoPlaceholder.classList.remove('hidden');
                }
            }

            // Initial fill (for existing settings)
            updatePdfTextPreview();
            syncPdfLogoVisibility();
            if (showLogoPdf) {
                showLogoPdf.addEventListener('change', syncPdfLogoVisibility);
            }
            // Keep existing logo if present, otherwise placeholder.
            if (!logoImg?.getAttribute('src')) setLogoPreview('');

            [schoolNameEl, poBoxEl, regionEl, phoneEl, emailEl].forEach(el => {
                if (!el) return;
                el.addEventListener('input', updatePdfTextPreview);
            });

            if (logoInput) {
                logoInput.addEventListener('change', function () {
                    const file = logoInput.files && logoInput.files[0];
                    if (!file) {
                        setLogoPreview('');
                        return;
                    }
                    if (!file.type || !file.type.startsWith('image/')) {
                        alert('Please select a valid image file.');
                        logoInput.value = '';
                        setLogoPreview('');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const src = e.target?.result ? String(e.target.result) : '';
                        setLogoPreview(src);
                    };
                    reader.readAsDataURL(file);
                });
            }
        }

        async function loadBankAccounts() {
            try {
                const response = await axios.get('/api/bank-accounts');
                const accounts = response.data.bank_accounts;

                let html = '';
                if (accounts.length === 0) {
                    html = '<p class="text-gray-500 text-center py-4">No bank accounts added yet. Click "Add Bank Account" to add one.</p>';
                } else {
                    html = `
                        <table class="w-full border-2 border-gray-300 rounded-lg">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="p-3 text-left">#</th>
                                    <th class="p-3 text-left">Bank Name</th>
                                    <th class="p-3 text-left">Account Number</th>
                                    <th class="p-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    accounts.forEach((account, index) => {
                        html += `
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-3 font-bold">${index + 1}</td>
                                <td class="p-3">${account.bank_name}</td>
                                <td class="p-3 font-mono">${account.account_number}</td>
                                <td class="p-3 text-center">
                                    <button onclick='editBankAccount(${JSON.stringify(account)})'
                                            class="mr-2 rounded border border-slate-300 bg-white px-3 py-1 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                        Edit
                                    </button>
                                    <button onclick="deleteBankAccount(${account.id})"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    html += `
                            </tbody>
                        </table>
                    `;
                }

                document.getElementById('bankAccountsTable').innerHTML = html;
            } catch (error) {
                console.error('Error loading bank accounts:', error);
            }
        }

        function showAddBankModal() {
            document.getElementById('bankModalTitle').textContent = 'Add Bank Account';
            document.getElementById('bankForm').reset();
            document.getElementById('bank_id').value = '';
            document.getElementById('bankModal').classList.remove('hidden');
        }

        function editBankAccount(account) {
            document.getElementById('bankModalTitle').textContent = 'Edit Bank Account';
            document.getElementById('bank_id').value = account.id;
            document.getElementById('bank_name').value = account.bank_name;
            document.getElementById('account_number').value = account.account_number;
            document.getElementById('bankModal').classList.remove('hidden');
        }

        function closeBankModal() {
            document.getElementById('bankModal').classList.add('hidden');
            document.getElementById('bankForm').reset();
        }

        async function saveBankAccount(event) {
            event.preventDefault();

            const bankId = document.getElementById('bank_id').value;
            const data = {
                bank_name: document.getElementById('bank_name').value,
                account_number: document.getElementById('account_number').value
            };

            try {
                if (bankId) {
                    // Update existing
                    await axios.put(`/api/bank-accounts/${bankId}`, data);
                } else {
                    // Create new
                    await axios.post('/api/bank-accounts', data);
                }

                closeBankModal();
                loadBankAccounts();
                alert('Bank account saved successfully.');
            } catch (error) {
                alert('Error saving bank account: ' + (error.response?.data?.message || error.message));
            }
        }

        async function deleteBankAccount(id) {
            if (!confirm('Are you sure you want to delete this bank account?')) {
                return;
            }

            try {
                await axios.delete(`/api/bank-accounts/${id}`);
                loadBankAccounts();
                alert('Bank account deleted successfully.');
            } catch (error) {
                alert('Error deleting bank account: ' + (error.response?.data?.message || error.message));
            }
        }

        // Academic Year Functions
        async function loadAcademicYears() {
            try {
                const response = await axios.get('/api/academic-years');
                const years = response.data;

                let html = '';
                if (years.length === 0) {
                    html = '<p class="text-gray-500 text-center py-4">No academic years added yet. Click "Add Academic Year" to add one.</p>';
                } else {
                    html = `
                        <table class="w-full rounded-lg border-2 border-slate-200">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="p-3 text-left">#</th>
                                    <th class="p-3 text-left">Year Name</th>
                                    <th class="p-3 text-left">Period</th>
                                    <th class="p-3 text-center">Status</th>
                                    <th class="p-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    years.forEach((year, index) => {
                        const startDate = new Date(year.start_date).toLocaleDateString();
                        const endDate = new Date(year.end_date).toLocaleDateString();
                        html += `
                            <tr class="border-t hover:bg-slate-50">
                                <td class="p-3 font-bold">${index + 1}</td>
                                <td class="p-3 font-semibold">${year.name}</td>
                                <td class="p-3 text-sm">${startDate} - ${endDate}</td>
                                <td class="p-3 text-center">
                                    ${year.is_current ?
                                        '<span class="rounded-full bg-blue-600 px-3 py-1 text-xs font-bold text-white">Current</span>' :
                                        '<span class="bg-gray-300 text-gray-700 px-3 py-1 rounded-full text-xs">Inactive</span>'
                                    }
                                </td>
                                <td class="p-3 text-center">
                                    ${!year.is_current ? `
                                        <button onclick="setCurrentAcademicYear(${year.id})"
                                                class="mr-1 rounded border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50">
                                            Set Current
                                        </button>
                                    ` : ''}
                                    <button onclick='editAcademicYear(${JSON.stringify(year)})'
                                            class="mr-1 rounded border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50">
                                        Edit
                                    </button>
                                    <button onclick="deleteAcademicYear(${year.id})"
                                            class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs transition">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    html += `
                            </tbody>
                        </table>
                    `;
                }

                document.getElementById('academicYearsTable').innerHTML = html;
            } catch (error) {
                console.error('Error loading academic years:', error);
                document.getElementById('academicYearsTable').innerHTML = '<p class="text-red-500 text-center py-4">Error loading academic years</p>';
            }
        }

        function showAddAcademicYearModal() {
            document.getElementById('academicYearModalTitle').textContent = 'Add Academic Year';
            document.getElementById('academicYearForm').reset();
            document.getElementById('academic_year_id').value = '';
            document.getElementById('academicYearModal').classList.remove('hidden');
        }

        function editAcademicYear(year) {
            document.getElementById('academicYearModalTitle').textContent = 'Edit Academic Year';
            document.getElementById('academic_year_id').value = year.id;
            document.getElementById('year_name').value = year.name;
            document.getElementById('start_date').value = year.start_date.split('T')[0];
            document.getElementById('end_date').value = year.end_date.split('T')[0];
            document.getElementById('is_current').checked = year.is_current;
            document.getElementById('academicYearModal').classList.remove('hidden');
        }

        function closeAcademicYearModal() {
            document.getElementById('academicYearModal').classList.add('hidden');
            document.getElementById('academicYearForm').reset();
        }

        async function saveAcademicYear(event) {
            event.preventDefault();

            const yearId = document.getElementById('academic_year_id').value;
            const data = {
                name: document.getElementById('year_name').value,
                start_date: document.getElementById('start_date').value,
                end_date: document.getElementById('end_date').value,
                is_current: document.getElementById('is_current').checked
            };

            try {
                if (yearId) {
                    await axios.put(`/api/academic-years/${yearId}`, data);
                } else {
                    await axios.post('/api/academic-years', data);
                }

                closeAcademicYearModal();
                loadAcademicYears();
                alert('Academic year saved successfully.');
            } catch (error) {
                alert('Error saving academic year: ' + (error.response?.data?.message || error.message));
            }
        }

        async function setCurrentAcademicYear(id) {
            if (!confirm('Are you sure you want to set this as the current academic year?')) {
                return;
            }

            try {
                await axios.post(`/api/academic-years/${id}/set-current`);
                loadAcademicYears();
                alert('Academic year set as current.');
            } catch (error) {
                alert('Error: ' + (error.response?.data?.message || error.message));
            }
        }

        async function deleteAcademicYear(id) {
            if (!confirm('Are you sure you want to delete this academic year? This cannot be done if there are fee assignments for this year.')) {
                return;
            }

            try {
                await axios.delete(`/api/academic-years/${id}`);
                loadAcademicYears();
                alert('Academic year deleted successfully.');
            } catch (error) {
                alert('Error deleting academic year: ' + (error.response?.data?.error || error.response?.data?.message || error.message));
            }
        }
    </script>
@endpush
