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

        function showAllStudentsInvoices() {
            const url = `${INVOICE_PDF_BASE}/all-students/pdf`;
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

            let url;
            if (selectedClasses.length === allClasses.length) {
                url = `${INVOICE_PDF_BASE}/all-students/pdf`;
            } else if (selectedClasses.length === 1) {
                url = `${INVOICE_PDF_BASE}/all-students/pdf?class=${encodeURIComponent(selectedClasses[0])}`;
            } else {
                const classesParam = selectedClasses.map(c => `classes[]=${encodeURIComponent(c)}`).join('&');
                url = `${INVOICE_PDF_BASE}/all-students/pdf?${classesParam}`;
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
            const url = `${INVOICE_PDF_BASE}/student/${selectedStudentId}/pdf`;
            const newWindow = window.open(url, '_blank');
            if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                alert('Pop-up blocked. Allow pop-ups for this site to download invoices.');
            }
        }

        loadInitialData();
    </script>
@endpush
