@extends($portalLayout ?? 'layouts.accountant')

@section('title', 'Invoices — Darasa Finance')
@section('page_title', 'Invoices')

@section('content')
    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Download student invoices</h2>
                    <p class="mt-1 text-sm text-slate-600">Generate and download fee statements for parents.</p>
                </div>
                @if(empty($readOnly))
                <a href="{{ route('accountant.dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Back to dashboard
                </a>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Select students</h3>

            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-1">Invoice heading <span class="text-slate-400 font-normal">(editable — appears on every page)</span></label>
                <input type="text" id="invoiceHeadingInput" value="FEE STATEMENT" maxlength="80"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold uppercase tracking-wide focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
            </div>

            <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
                <button type="button" onclick="showAllStudentsInvoices()" class="rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                    Download all students
                </button>
                <button type="button" onclick="showClassSelection()" class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                    By class
                </button>
                <button type="button" onclick="showStudentSearch()" class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                    Search student
                </button>
            </div>

            <div id="classSelectionArea" class="hidden">
                <h3 class="mb-3 text-base font-semibold text-slate-900">Select classes</h3>
                <div class="mb-4 flex flex-wrap gap-2">
                    <button type="button" onclick="selectAllClasses()" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Select all</button>
                    <button type="button" onclick="deselectAllClasses()" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Deselect all</button>
                    <button type="button" onclick="downloadSelectedClassInvoices()" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Download</button>
                </div>
                <div id="classList" class="grid grid-cols-2 gap-3 md:grid-cols-4"></div>
            </div>

            <div id="studentSearchArea" class="hidden">
                <h3 class="mb-3 text-base font-semibold text-slate-900">Search student</h3>
                <div class="mb-4 flex flex-col gap-2 sm:flex-row">
                    <input type="text" id="studentSearchInput" placeholder="Name or registration number…"
                        class="min-w-0 flex-1 rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        onkeyup="searchStudents()">
                    <button type="button" onclick="downloadSelectedStudent()" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Download PDF
                    </button>
                </div>
                <div id="studentSearchResults" class="max-h-96 overflow-y-auto rounded-lg border border-slate-100"></div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">
            <h3 class="font-semibold text-slate-900">Invoice contents</h3>
            <p class="mt-2">Each PDF keeps one student’s full statement together on its own page (header, fees, balance, bank details). Class filters now apply to bulk downloads.</p>
        </div>

        <!-- Quarter label editor -->
        <div class="rounded-xl border border-indigo-200 bg-white p-6 shadow-sm">
            <h3 class="mb-1 text-base font-semibold text-slate-900">Quarter column headings</h3>
            <p class="mb-4 text-sm text-slate-500">These labels appear as column headers in every student’s fee statement.</p>

            <!-- Show months toggle -->
            <label class="inline-flex items-center gap-2 cursor-pointer mb-4 select-none">
                <input type="checkbox" id="showMonthSpan" class="h-4 w-4 rounded border-slate-300 accent-indigo-600" checked>
                <span class="text-sm font-medium text-slate-700">Show month span in headers</span>
                <span class="text-xs text-slate-400">(e.g. "QUARTER 1 (Jan–Mar)" vs just "QUARTER 1")</span>
            </label>

            <div id="quarterLabelsForm" class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="text-center text-slate-400 text-sm col-span-4 py-2">Loading…</div>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="button" onclick="saveQuarterLabels()"
                    class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-sm font-semibold">
                    Save labels
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const API_BASE = '/api';
        const INVOICE_PDF_BASE = @json($invoicePdfBase ?? '/accountant/invoices');
        let allStudents = [];
        let allClasses = [];
        let selectedStudentId = null;

        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.withCredentials = true;

        async function loadInitialData() {
            try {
                const [studentsResponse, classesResponse] = await Promise.all([
                    axios.get(`${API_BASE}/students`),
                    axios.get(`${API_BASE}/classes`)
                ]);
                allStudents = studentsResponse.data.students || studentsResponse.data;
                allClasses = classesResponse.data;
            } catch (error) {
                console.error('Error loading data:', error);
                alert('Error loading student data. Please refresh the page.');
            }
        }

        function getInvoiceHeading() {
            return encodeURIComponent(document.getElementById('invoiceHeadingInput').value.trim() || 'FEE STATEMENT');
        }

        function showAllStudentsInvoices() {
            const url = `${INVOICE_PDF_BASE}/all-students/pdf?heading=${getInvoiceHeading()}`;
            const newWindow = window.open(url, '_blank');
            if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                alert('Pop-up blocked. Allow pop-ups for this site to download invoices.');
            }
        }

        function showClassSelection() {
            document.getElementById('classSelectionArea').classList.remove('hidden');
            document.getElementById('studentSearchArea').classList.add('hidden');

            let html = '';
            allClasses.forEach(cls => {
                const studentCount = allStudents.filter(s => s.class_id == cls.id).length;
                html += `
                    <div class="rounded-lg border border-slate-200 bg-white p-3 hover:border-slate-300">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" class="class-checkbox h-4 w-4 rounded border-slate-300" value="${cls.id}" data-class-name="${cls.name}">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">${cls.name}</p>
                                <p class="text-xs text-slate-500">${studentCount} students</p>
                            </div>
                        </label>
                    </div>
                `;
            });
            document.getElementById('classList').innerHTML = html;
        }

        function showStudentSearch() {
            document.getElementById('classSelectionArea').classList.add('hidden');
            document.getElementById('studentSearchArea').classList.remove('hidden');
        }

        function selectAllClasses() {
            document.querySelectorAll('.class-checkbox').forEach(cb => cb.checked = true);
        }

        function deselectAllClasses() {
            document.querySelectorAll('.class-checkbox').forEach(cb => cb.checked = false);
        }

        function downloadSelectedClassInvoices() {
            const selectedClasses = [];
            document.querySelectorAll('.class-checkbox:checked').forEach(cb => {
                selectedClasses.push(cb.value);
            });

            if (selectedClasses.length === 0) {
                alert('Please select at least one class.');
                return;
            }

            const heading = getInvoiceHeading();
            let url;
            if (selectedClasses.length === allClasses.length) {
                url = `${INVOICE_PDF_BASE}/all-students/pdf?heading=${heading}`;
            } else if (selectedClasses.length === 1) {
                url = `${INVOICE_PDF_BASE}/all-students/pdf?class=${encodeURIComponent(selectedClasses[0])}&heading=${heading}`;
            } else {
                const classesParam = selectedClasses.map(c => `classes[]=${encodeURIComponent(c)}`).join('&');
                url = `${INVOICE_PDF_BASE}/all-students/pdf?${classesParam}&heading=${heading}`;
            }

            const newWindow = window.open(url, '_blank');
            if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                alert('Pop-up blocked. Allow pop-ups for this site to download invoices.');
            }
        }

        function searchStudents() {
            const searchTerm = document.getElementById('studentSearchInput').value.toLowerCase();
            const filtered = allStudents.filter(s =>
                s.name.toLowerCase().includes(searchTerm) ||
                s.student_reg_no.toLowerCase().includes(searchTerm)
            );

            let html = '';
            filtered.slice(0, 20).forEach(student => {
                const safeName = String(student.name).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                html += `
                    <div onclick="selectStudentForInvoice(${student.id}, '${safeName}')"
                        class="cursor-pointer border-b border-slate-100 p-3 hover:bg-slate-50">
                        <p class="text-sm font-semibold text-slate-900">${student.name}</p>
                        <p class="text-xs text-slate-500">${student.student_reg_no} · ${student.class}</p>
                    </div>
                `;
            });

            if (filtered.length === 0) {
                html = '<p class="p-4 text-center text-sm text-slate-500">No students found</p>';
            }

            document.getElementById('studentSearchResults').innerHTML = html;
        }

        function selectStudentForInvoice(studentId, studentName) {
            selectedStudentId = studentId;
            document.getElementById('studentSearchInput').value = studentName;
            document.getElementById('studentSearchResults').innerHTML =
                `<div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-800">Selected: <strong>${studentName}</strong></div>`;
        }

        function downloadSelectedStudent() {
            if (!selectedStudentId) {
                alert('Please search and select a student first.');
                return;
            }
            const url = `${INVOICE_PDF_BASE}/student/${selectedStudentId}/pdf?heading=${getInvoiceHeading()}`;
            const newWindow = window.open(url, '_blank');
            if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                alert('Pop-up blocked. Allow pop-ups for this site to download invoices.');
            }
        }

        loadInitialData();

        // ── Quarter label editor ────────────────────────────────────────────
        const DEFAULT_MONTH_SPANS = { 1: 'Jan–Mar', 2: 'Apr–Jun', 3: 'Jul–Sep', 4: 'Oct–Dec' };
        const QUARTER_LABELS_BASE = '/accountant/api/quarter-labels';

        async function loadQuarterLabels() {
            try {
                const res = await axios.get(QUARTER_LABELS_BASE);
                const labels = res.data; // {1: 'QUARTER 1 (Jan–Mar)', ...}

                // Detect if any label has a month span in parentheses
                let showMonths = false;
                const monthSpans = { ...DEFAULT_MONTH_SPANS };
                Object.entries(labels).forEach(([q, label]) => {
                    const match = label.match(/\(([^)]+)\)/);
                    if (match) { showMonths = true; monthSpans[q] = match[1]; }
                });

                const toggle = document.getElementById('showMonthSpan');
                if (toggle) toggle.checked = showMonths;

                const form = document.getElementById('quarterLabelsForm');
                form.innerHTML = [1, 2, 3, 4].map(q => `
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Quarter ${q}</label>
                        <input type="text" id="qlabel_${q}" value="${monthSpans[q] || DEFAULT_MONTH_SPANS[q]}"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-indigo-400 focus:outline-none"
                            placeholder="e.g. Jan–Mar" maxlength="20">
                        <span class="text-[11px] text-slate-400">month span only</span>
                    </div>
                `).join('');
            } catch (e) {
                document.getElementById('quarterLabelsForm').innerHTML =
                    '<p class="text-red-500 text-sm col-span-4">Failed to load labels — please refresh.</p>';
            }
        }

        async function saveQuarterLabels() {
            const showMonths = document.getElementById('showMonthSpan')?.checked ?? true;
            const updates = [];
            for (let q = 1; q <= 4; q++) {
                const el = document.getElementById(`qlabel_${q}`);
                const span = el ? el.value.trim() : DEFAULT_MONTH_SPANS[q];
                const label = (showMonths && span)
                    ? `QUARTER ${q} (${span})`
                    : `QUARTER ${q}`;
                updates.push({ q, label });
            }
            try {
                await Promise.all(updates.map(u =>
                    axios.put(`${QUARTER_LABELS_BASE}/${u.q}`, { label: u.label })
                ));
                showDarasaToast({ type: 'success', title: 'Quarter labels', message: 'Labels saved. They will appear on all new invoices.' });
            } catch (e) {
                showDarasaToast({ type: 'error', title: 'Quarter labels', message: 'Failed to save — try again.' });
            }
        }

        loadQuarterLabels();
    </script>
@endpush
