@extends('layouts.accountant')

@section('title', 'SMS — Darasa Finance')
@section('page_title', 'SMS')

@section('content')
<div class="w-full space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <nav class="mb-3 text-sm text-slate-500">
                <a href="{{ route('accountant.dashboard') }}" class="font-medium text-slate-700 hover:text-slate-900">Dashboard</a>
                <span class="mx-1.5">/</span>
                <span class="text-slate-600">SMS</span>
            </nav>
            <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Send SMS</h2>
                    <p class="text-sm text-slate-500">Credits and delivery</p>
                </div>
                <div id="balance-display" class="sms-credits-panel flex flex-wrap items-stretch gap-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-800">
                    <div class="flex items-center gap-3 border-r border-slate-200 px-4 py-2">
                        <div>
                            <div class="text-xs text-slate-500">School</div>
                            <div class="font-semibold" id="sms-school">-</div>
                        </div>
                    </div>
                    <div class="px-4 py-2 text-center">
                        <div class="text-xs text-slate-500">Assigned</div>
                        <div class="font-semibold tabular-nums" id="sms-assigned">-</div>
                    </div>
                    <div class="border-l border-slate-200 px-4 py-2 text-center">
                        <div class="text-xs text-slate-500">Used</div>
                        <div class="font-semibold tabular-nums" id="sms-used">-</div>
                    </div>
                    <div class="border-l border-slate-200 px-4 py-2 text-center">
                        <div class="text-xs text-slate-500">Remaining</div>
                        <div class="text-lg font-semibold tabular-nums text-slate-900" id="sms-remaining">-</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('accountant.sms-logs') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">SMS logs</a>
                    <a href="{{ route('accountant.phone-numbers') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">Phone numbers</a>
                </div>
            </div>
        </div>

            <!-- Success/Error Messages -->
            <div id="message-container"></div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Panel - Student Selection -->
                <div class="lg:col-span-1">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-slate-900">Select recipients</h2>

                        <!-- Selection Method Tabs -->
                        <div class="mb-4 flex gap-2">
                            <button type="button" onclick="switchTab('class')" id="tab-class" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white">
                                By Class
                            </button>
                            <button type="button" onclick="switchTab('search')" id="tab-search" class="flex-1 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                Search
                            </button>
                        </div>

                        <!-- By Class Tab -->
                        <div id="class-tab" class="tab-content">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Class</label>
                                <select id="class-select" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-slate-400 focus:ring-slate-400 p-2 border">
                                    <option value="">Choose a class...</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" onclick="loadStudentsByClass()" class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                                Load Students
                            </button>
                        </div>

                        <!-- Search Tab -->
                        <div id="search-tab" class="tab-content hidden">
                            <div class="mb-4 relative">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search Student</label>
                                <input type="text" id="search-input" placeholder="Name or Registration Number..."
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-slate-400 focus:ring-slate-400 p-2 border"
                                    oninput="showSearchAutocomplete()"
                                    onfocus="showSearchAutocomplete()">
                                <div id="search-autocomplete" class="hidden absolute z-50 w-full mt-1 bg-white border-2 border-slate-300 rounded-lg shadow-lg max-h-64 overflow-y-auto"></div>
                            </div>
                            <button type="button" onclick="searchStudents()" class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                                Search
                            </button>
                        </div>

                        <!-- Student List -->
                        <div class="mt-6">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-semibold text-gray-700">Students (<span id="student-count">0</span>)</h3>
                                <button onclick="selectAllStudents()" class="text-sm text-slate-700 hover:text-slate-900">
                                    Select All
                                </button>
                            </div>
                            <div id="students-list" class="space-y-2 max-h-96 overflow-y-auto">
                                <p class="text-gray-500 text-sm text-center py-4">No students loaded</p>
                            </div>
                        </div>

                        <!-- Selected Count -->
                        <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <p class="text-sm font-medium text-slate-800">
                                Selected: <span id="selected-count" class="font-bold">0</span> students
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Message Composition -->
                <div class="lg:col-span-2">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-slate-900">Compose message</h2>

                        <!-- Phone Number Selection -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Send to which phone?</label>
                            <div class="flex gap-3">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="phone_number" value="phone_1" checked class="mr-2">
                                    <span>Phone 1 Only</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="phone_number" value="phone_2" class="mr-2">
                                    <span>Phone 2 Only</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="phone_number" value="both" class="mr-2">
                                    <span>Both Numbers</span>
                                </label>
                            </div>
                        </div>

                        <!-- Language Toggle -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Template Language</label>
                            <div class="flex gap-3">
                                <button type="button" onclick="switchLanguage('en')" id="lang-en" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition">
                                    English
                                </button>
                                <button type="button" onclick="switchLanguage('sw')" id="lang-sw" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    Kiswahili
                                </button>
                            </div>
                        </div>

                        <!-- Message Templates -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quick Templates</label>
                            <select id="template-select" onchange="useTemplate()" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-slate-400 focus:ring-slate-400 p-2 border">
                                <option value="">Choose a template...</option>
                                <optgroup label="System Templates">
                                    <option value="fee_reminder">Fee Reminder (Short)</option>
                                    <option value="fee_statement">Fee Statement (Detailed)</option>
                                    <option value="overdue_reminder">Overdue Payment Reminder</option>
                                    <option value="payment_received">Payment Confirmation</option>
                                </optgroup>
                                @if($templates && count($templates) > 0)
                                <optgroup label="Custom Templates">
                                    @foreach($templates as $template)
                                        <option value="custom_{{ $template->id }}" data-message-en="{{ $template->message_en }}" data-message-sw="{{ $template->message_sw }}">
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                        </div>

                        <!-- Message Input -->
                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-gray-700">Payment Reminder Message (for students with balance)</label>
                                <button type="button" onclick="openSaveTemplateModal()" class="text-sm font-medium text-slate-700 hover:text-slate-900">
                                    Save as template
                                </button>
                            </div>
                            <textarea id="message-text" rows="6" maxlength="1000" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-slate-400 focus:ring-slate-400 p-3 border" placeholder="Type your payment reminder message here..."></textarea>
                            <div class="flex justify-between items-center mt-2 text-sm">
                                <div class="flex gap-4 text-gray-600">
                                    <span>
                                        <span id="char-count">0</span>/<span id="max-chars">160</span> chars
                                    </span>
                                    <span class="font-semibold text-slate-800">
                                        = <span id="sms-count">0</span> SMS
                                    </span>
                                    <span id="unicode-indicator" class="hidden text-amber-700">
                                        (Unicode detected - 70 chars/SMS)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Thank You Message Input -->
                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-slate-700">Thank you message (fully paid students) — optional</label>
                                <span class="text-xs text-gray-500">Leave empty to skip fully paid students</span>
                            </div>
                            <textarea id="thank-you-text" rows="4" maxlength="1000" class="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 shadow-sm focus:border-slate-400 focus:ring-slate-400" placeholder="Dear parent of {student_name}, Thank you for completing all fee payments! We appreciate your prompt payment..."></textarea>
                            <div class="flex justify-between items-center mt-2 text-sm">
                                <span class="text-gray-600">
                                    <span id="thank-you-char-count">0</span>/1000 characters
                                    (<span id="thank-you-sms-count">0</span> SMS)
                                </span>
                            </div>
                        </div>

                        <!-- Clickable Placeholders - Left Bottom -->
                        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <h3 class="mb-2 text-sm font-semibold text-slate-700">Insert placeholder</h3>

                            <!-- Student Info -->
                            <div class="mb-3">
                                <h4 class="text-xs font-semibold text-gray-600 mb-1">Student Info:</h4>
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" onclick="insertPlaceholder('{student_name}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {student_name}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{student_reg}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {student_reg}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{class}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {class}
                                    </button>
                                </div>
                            </div>

                            <!-- Financial Info -->
                            <div class="mb-3">
                                <h4 class="text-xs font-semibold text-gray-600 mb-1">Financial:</h4>
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" onclick="insertPlaceholder('{total_sales}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {total_sales}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{total_paid}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {total_paid}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{balance}')" class="rounded bg-red-50 px-3 py-2 text-left text-xs text-red-800 transition hover:bg-red-100">
                                        {balance}
                                    </button>
                                </div>
                            </div>

                            <!-- Overdue & Deadline Info -->
                            <div class="mb-3">
                                <h4 class="text-xs font-semibold text-gray-600 mb-1">Overdue & Deadline:</h4>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" onclick="insertPlaceholder('{total_overdue}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {total_overdue}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{overdue_count}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {overdue_count}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{particular_name}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {particular_name}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{deadline}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {deadline}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{days_overdue}')" class="rounded bg-red-50 px-3 py-2 text-left text-xs text-red-800 transition hover:bg-red-100">
                                        {days_overdue}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{overdue_amount}')" class="rounded bg-red-50 px-3 py-2 text-left text-xs text-red-800 transition hover:bg-red-100">
                                        {overdue_amount}
                                    </button>
                                </div>
                            </div>

                            <!-- Detailed Breakdowns -->
                            <div>
                                <h4 class="text-xs font-semibold text-gray-600 mb-1">Detailed Breakdowns:</h4>
                                <div class="grid grid-cols-1 gap-2">
                                    <button type="button" onclick="insertPlaceholder('{particulars_breakdown}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {particulars_breakdown}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{academic_year_breakdown}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {academic_year_breakdown}
                                    </button>
                                    <button type="button" onclick="insertPlaceholder('{overdue_details}')" class="rounded bg-slate-100 px-3 py-2 text-left text-xs text-slate-800 transition hover:bg-slate-200">
                                        {overdue_details}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <h3 class="mb-2 font-semibold text-slate-700">Message preview</h3>
                            <p id="message-preview" class="text-sm text-gray-600 whitespace-pre-wrap">Your message will appear here...</p>
                        </div>

                        <!-- Send Button -->
                        <div class="flex gap-3">
                            <button onclick="sendSMS()" id="send-btn" class="flex-1 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                Send SMS
                            </button>
                            <button onclick="clearForm()" class="rounded-lg border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Save Template Modal -->
    <div id="save-template-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="mx-4 w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-lg">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Save as template</h3>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Template Name</label>
                <input type="text" id="template-name" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-slate-400 focus:ring-slate-400 p-2 border" placeholder="e.g., Monthly Fee Reminder">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">English Message</label>
                <textarea id="template-message-en" rows="4" readonly class="w-full border-gray-300 rounded-lg shadow-sm bg-gray-50 p-2 border"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Swahili Message (Optional)</label>
                <textarea id="template-message-sw" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-slate-400 focus:ring-slate-400 p-2 border" placeholder="Translate your message to Swahili..."></textarea>
            </div>

            <div class="flex gap-3">
                <button onclick="saveTemplate()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                    Save Template
                </button>
                <button onclick="closeSaveTemplateModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
let selectedStudents = [];
        let students = [];
        let currentLanguage = 'en';
        let allTemplates = @json($templates ?? []);

        // Configure axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Load SMS balance on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadSmsBalance();
            updateCharCount();
        });

        // Switch language
        function switchLanguage(lang) {
            currentLanguage = lang;
            const en = document.getElementById('lang-en');
            const sw = document.getElementById('lang-sw');
            const active = ['bg-blue-600', 'text-white'];
            const inactive = ['border', 'border-slate-200', 'bg-white', 'text-slate-700', 'hover:bg-slate-50'];

            if (lang === 'en') {
                en.classList.add(...active);
                en.classList.remove(...inactive);
                sw.classList.remove(...active);
                sw.classList.add(...inactive);
            } else {
                sw.classList.add(...active);
                sw.classList.remove(...inactive);
                en.classList.remove(...active);
                en.classList.add(...inactive);
            }
        }

        // Insert placeholder at cursor position
        function insertPlaceholder(placeholder) {
            const textarea = document.getElementById('message-text');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const before = text.substring(0, start);
            const after = text.substring(end, text.length);

            textarea.value = before + placeholder + after;
            textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
            textarea.focus();

            updateCharCount();
            updatePreview();
        }

        // Switch tabs
        function switchTab(tab) {
            document.getElementById('class-tab').classList.add('hidden');
            document.getElementById('search-tab').classList.add('hidden');
            const tabClass = document.getElementById('tab-class');
            const tabSearch = document.getElementById('tab-search');
            const active = ['bg-blue-600', 'text-white'];
            const inactive = ['border', 'border-slate-200', 'bg-white', 'text-slate-700', 'hover:bg-slate-50'];

            tabClass.classList.remove(...active);
            tabClass.classList.add(...inactive);
            tabSearch.classList.remove(...active);
            tabSearch.classList.add(...inactive);

            if (tab === 'class') {
                document.getElementById('class-tab').classList.remove('hidden');
                tabClass.classList.add(...active);
                tabClass.classList.remove(...inactive);
            } else {
                document.getElementById('search-tab').classList.remove('hidden');
                tabSearch.classList.add(...active);
                tabSearch.classList.remove(...inactive);
            }
        }

        // Load students by class
        async function loadStudentsByClass() {
            const classId = document.getElementById('class-select').value;
            if (!classId) {
                showMessage('Please select a class', 'error');
                return;
            }

            try {
                const response = await axios.get(`/api/sms/students/class/${classId}`);
                const data = response.data;
                students = Array.isArray(data) ? data : (data.data || []);
                displayStudents(students);
            } catch (error) {
                showMessage('Error loading students: ' + (error.response?.data?.message || error.message), 'error');
            }
        }

        // Search students
        async function searchStudents() {
            const search = document.getElementById('search-input').value;
            if (!search || search.length < 2) {
                showMessage('Please enter at least 2 characters', 'error');
                return;
            }

            try {
                const response = await axios.get('/api/sms/students/search', { params: { search } });
                students = response.data;
                displayStudents(students);
                document.getElementById('search-autocomplete').classList.add('hidden');
            } catch (error) {
                showMessage('Error searching students: ' + (error.response?.data?.message || error.message), 'error');
            }
        }

        // Show search autocomplete
        let searchTimeout;
        async function showSearchAutocomplete() {
            const searchValue = document.getElementById('search-input').value.trim();
            const resultsDiv = document.getElementById('search-autocomplete');

            // Clear previous timeout
            clearTimeout(searchTimeout);

            if (searchValue.length < 2) {
                resultsDiv.classList.add('hidden');
                return;
            }

            // Debounce the API call
            searchTimeout = setTimeout(async () => {
                try {
                    const response = await axios.get('/api/sms/students/search', { params: { search: searchValue } });
                    const matchedStudents = response.data;

                    if (matchedStudents.length === 0) {
                        resultsDiv.innerHTML = '<div class="p-3 text-gray-500 text-center text-sm">No students found</div>';
                        resultsDiv.classList.remove('hidden');
                        return;
                    }

                    // Build autocomplete results
                    let html = '';
                    matchedStudents.slice(0, 10).forEach(student => {
                        html += `
                            <div onclick="selectStudentFromAutocomplete(${student.id})"
                                 class="cursor-pointer border-b border-gray-200 p-3 last:border-0 hover:bg-slate-100">
                                <div class="font-semibold text-gray-800 text-sm">${student.name}</div>
                                <div class="text-xs text-gray-600">${student.student_reg_no} | ${student.school_class?.name || 'N/A'}</div>
                                <div class="text-xs text-gray-500">Tel: ${student.parent_phone_1 || 'No phone'}${student.parent_phone_2 ? ' | ' + student.parent_phone_2 : ''}</div>
                            </div>
                        `;
                    });

                    resultsDiv.innerHTML = html;
                    resultsDiv.classList.remove('hidden');
                } catch (error) {
                    console.error('Error fetching autocomplete:', error);
                    resultsDiv.classList.add('hidden');
                }
            }, 300);
        }

        // Select student from autocomplete
        function selectStudentFromAutocomplete(studentId) {
            document.getElementById('search-autocomplete').classList.add('hidden');

            // Find the student in the matched results
            axios.get('/api/sms/students/search', { params: { search: document.getElementById('search-input').value } })
                .then(response => {
                    students = response.data;
                    displayStudents(students);

                    // Auto-select the clicked student
                    const checkbox = document.querySelector(`#students-list input[value="${studentId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        toggleStudent(studentId);
                    }
                })
                .catch(error => {
                    showMessage('Error loading student: ' + (error.response?.data?.message || error.message), 'error');
                });
        }

        // Close autocomplete when clicking outside
        document.addEventListener('click', function(event) {
            const searchInput = document.getElementById('search-input');
            const resultsDiv = document.getElementById('search-autocomplete');

            if (searchInput && resultsDiv && !searchInput.contains(event.target) && !resultsDiv.contains(event.target)) {
                resultsDiv.classList.add('hidden');
            }
        });

        // Display students
        function displayStudents(studentList) {
            const container = document.getElementById('students-list');
            document.getElementById('student-count').textContent = studentList.length;

            if (studentList.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">No students with registered phone numbers found</p>';
                return;
            }

            container.innerHTML = studentList.map(student => `
                <label class="flex cursor-pointer items-center rounded-lg border border-slate-200 bg-slate-50 p-3 transition hover:bg-slate-100">
                    <input type="checkbox" value="${student.id}" onchange="toggleStudent(${student.id})" class="mr-3 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                    <div class="flex-1">
                        <div class="font-semibold text-gray-800">${student.name}</div>
                        <div class="text-xs text-gray-600">${student.student_reg_no} | ${student.school_class?.name || 'N/A'}</div>
                        <div class="text-xs text-gray-500">
                            Tel: ${student.parent_phone_1}${student.parent_phone_2 ? ' | ' + student.parent_phone_2 : ''}
                        </div>
                    </div>
                </label>
            `).join('');
        }

        // Toggle student selection
        function toggleStudent(studentId) {
            const index = selectedStudents.indexOf(studentId);
            if (index > -1) {
                selectedStudents.splice(index, 1);
            } else {
                selectedStudents.push(studentId);
            }
            updateSelectedCount();
        }

        // Select all students
        function selectAllStudents() {
            selectedStudents = students.map(s => s.id);
            document.querySelectorAll('#students-list input[type="checkbox"]').forEach(cb => cb.checked = true);
            updateSelectedCount();
        }

        // Update selected count
        function updateSelectedCount() {
            document.getElementById('selected-count').textContent = selectedStudents.length;
        }

        // Update character count
        document.getElementById('message-text')?.addEventListener('input', function() {
            updateCharCount();
            updatePreview();
        });

        document.getElementById('thank-you-text')?.addEventListener('input', function() {
            updateThankYouCharCount();
        });

        // Check if text contains Unicode characters (non-ASCII)
        function containsUnicode(text) {
            return /[^\x00-\x7F]/.test(text);
        }

        // Calculate SMS count based on content (standard vs Unicode)
        function calculateSmsInfo(text) {
            const isUnicode = containsUnicode(text);
            const length = text.length;
            const maxCharsPerSms = isUnicode ? 70 : 160;
            const concatChars = isUnicode ? 67 : 153;

            let smsCount = 0;
            if (length === 0) {
                smsCount = 0;
            } else if (length <= maxCharsPerSms) {
                smsCount = 1;
            } else {
                smsCount = Math.ceil(length / concatChars);
            }

            return {
                length: length,
                smsCount: smsCount,
                isUnicode: isUnicode,
                maxCharsPerSms: maxCharsPerSms
            };
        }

        function updateCharCount() {
            const text = document.getElementById('message-text').value;
            const info = calculateSmsInfo(text);

            document.getElementById('char-count').textContent = info.length;
            document.getElementById('max-chars').textContent = info.maxCharsPerSms;
            document.getElementById('sms-count').textContent = info.smsCount;

            // Show/hide Unicode indicator
            const unicodeIndicator = document.getElementById('unicode-indicator');
            if (info.isUnicode) {
                unicodeIndicator.classList.remove('hidden');
            } else {
                unicodeIndicator.classList.add('hidden');
            }
        }

        function updateThankYouCharCount() {
            const text = document.getElementById('thank-you-text').value;
            const info = calculateSmsInfo(text);

            document.getElementById('thank-you-char-count').textContent = info.length;
            document.getElementById('thank-you-sms-count').textContent = info.smsCount;
        }

        // Legacy function for compatibility
        function calculateSmsCount(length) {
            if (length === 0) return 0;
            if (length <= 160) return 1;
            if (length <= 306) return 2;
            if (length <= 459) return 3;
            return Math.ceil(length / 153);
        }

        // Update preview
        function updatePreview() {
            const text = document.getElementById('message-text').value;
            document.getElementById('message-preview').textContent = text || 'Your message will appear here...';
        }

        // Use template
        function useTemplate() {
            const template = document.getElementById('template-select').value;

            // System templates
            const systemTemplatesEn = {
                'fee_reminder': `Dear Parent of {student_name},\n\nFee reminder for {student_name} ({class}).\n\nTotal Fees: TSh {total_sales}\nPaid: TSh {total_paid}\nBalance Due: TSh {balance}\n\nPlease pay soon.\n\nDarasa Finance`,
                'fee_statement': `DARASA FEE STATEMENT\n\nStudent: {student_name}\nReg: {student_reg}\nClass: {class}\n\n--- Fee Breakdown ---\n{particulars_breakdown}\n\nTotal Fees: TSh {total_sales}\nPaid: TSh {total_paid}\nBalance: TSh {balance}\n\nThank you!`,
                'overdue_reminder': `URGENT: OVERDUE FEE PAYMENT\n\nDear Parent of {student_name},\n\nYou have {overdue_count} overdue fee payment(s):\n\n{overdue_details}\n\nTotal Overdue: TSh {total_overdue}\n\nPlease settle this immediately to avoid penalties.\n\nDarasa Finance`,
                'payment_received': `Dear Parent,\n\nPayment received for {student_name}.\n\nAmount Paid: TSh {total_paid}\nRemaining Balance: TSh {balance}\n\nThank you!\nDarasa Finance`
            };

            const systemTemplatesSw = {
                'fee_reminder': `Mzazi Mpendwa wa {student_name},\n\nKumbusho la ada kwa {student_name} ({class}).\n\nJumla ya Ada: TSh {total_sales}\nAmelipa: TSh {total_paid}\nSalio: TSh {balance}\n\nTafadhali lipa mapema.\n\nDarasa Finance`,
                'fee_statement': `TAARIFA YA ADA - DARASA\n\nMwanafunzi: {student_name}\nNamba: {student_reg}\nDarasa: {class}\n\n--- Maelezo ya Ada ---\n{particulars_breakdown}\n\nJumla ya Ada: TSh {total_sales}\nAmelipa: TSh {total_paid}\nSalio: TSh {balance}\n\nAsante!`,
                'overdue_reminder': `MUHIMU: MALIPO YA ADA YALIYOPITA\n\nMzazi Mpendwa wa {student_name},\n\nUna malipo {overdue_count} yaliyopita muda:\n\n{overdue_details}\n\nJumla ya Kiasi: TSh {total_overdue}\n\nTafadhali lipa mara moja ili kuepuka adhabu.\n\nDarasa Finance`,
                'payment_received': `Mzazi Mpendwa,\n\nMalipo kwa {student_name} yamepokelewa.\n\nKiasi Kilicholipwa: TSh {total_paid}\nSalio Lililobaki: TSh {balance}\n\nAsante!\nDarasa Finance`
            };

            if (systemTemplatesEn[template]) {
                const message = currentLanguage === 'en' ? systemTemplatesEn[template] : systemTemplatesSw[template];
                document.getElementById('message-text').value = message;
                updateCharCount();
                updatePreview();
            } else if (template.startsWith('custom_')) {
                // Custom template
                const templateId = template.replace('custom_', '');
                const customTemplate = allTemplates.find(t => t.id == templateId);
                if (customTemplate) {
                    const message = currentLanguage === 'en' ? customTemplate.message_en : (customTemplate.message_sw || customTemplate.message_en);
                    document.getElementById('message-text').value = message;
                    updateCharCount();
                    updatePreview();
                }
            }
        }

        // Send SMS
        async function sendSMS() {
            if (selectedStudents.length === 0) {
                showMessage('Please select at least one student', 'error');
                return;
            }

            const message = document.getElementById('message-text').value.trim();
            if (!message) {
                showMessage('Please enter a message', 'error');
                return;
            }

            // Calculate estimated SMS count
            const messageInfo = calculateSmsInfo(message);
            const estimatedSmsCount = selectedStudents.length * messageInfo.smsCount;

            // Get current remaining credits
            const currentRemaining = parseInt(document.getElementById('sms-remaining').textContent.replace(/,/g, '')) || 0;

            if (currentRemaining < estimatedSmsCount) {
                showMessage(`Insufficient SMS credits. Estimated need: ${estimatedSmsCount}, Available: ${currentRemaining}. Please contact administrator.`, 'error');
                return;
            }

            const thankYouMessage = document.getElementById('thank-you-text').value.trim();
            const phoneNumber = document.querySelector('input[name="phone_number"]:checked').value;

            const btn = document.getElementById('send-btn');
            btn.disabled = true;
            btn.innerHTML = 'Sending...';

            try {
                const payload = {
                    student_ids: selectedStudents,
                    message: message,
                    phone_number: phoneNumber
                };

                // Add thank you message if provided
                if (thankYouMessage) {
                    payload.thank_you_message = thankYouMessage;
                }

                const response = await axios.post('/sms/send', payload);

                let successMsg = response.data.message || 'Sent.';
                if (response.data.sms_credits) {
                    successMsg += ` (${response.data.sms_credits.used} SMS used, ${response.data.sms_credits.remaining} remaining)`;
                }
                showMessage(successMsg, 'success');
                clearForm();
                loadSmsBalance();
            } catch (error) {
                const errorMsg = error.response?.data?.message || error.message;
                if (error.response?.status === 403) {
                    showMessage(errorMsg + ' Please contact your administrator to add more SMS credits.', 'error');
                } else {
                    showMessage(errorMsg, 'error');
                }
                loadSmsBalance(); // Refresh credits display
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Send SMS';
            }
        }

        // Load SMS credits for school
        async function loadSmsBalance() {
            try {
                const response = await axios.get('/api/sms/credits');
                const data = response.data;

                document.getElementById('sms-assigned').textContent = formatNumber(data.assigned || 0);
                document.getElementById('sms-used').textContent = formatNumber(data.used || 0);
                document.getElementById('sms-remaining').textContent = formatNumber(data.remaining || 0);
                document.getElementById('sms-school').textContent = data.school_name || (data.message ? 'Not linked' : 'Unknown');

                // Log debug info to console for troubleshooting
                if (data.debug) {
                    console.log('SMS Credits Debug:', data.debug);
                }

                // Update display color based on remaining credits
                const balanceDisplay = document.getElementById('balance-display');
                if (!balanceDisplay) return;
                balanceDisplay.classList.remove('bg-slate-50', 'bg-red-50', 'bg-amber-50', 'bg-slate-200', 'border-red-200', 'border-amber-200', 'border-slate-200');
                if (data.error || data.message) {
                    balanceDisplay.classList.add('bg-slate-200', 'border-slate-300');
                    if (data.message) {
                        document.getElementById('sms-remaining').textContent = '0';
                    }
                } else if (data.remaining <= 0) {
                    balanceDisplay.classList.add('bg-red-50', 'border-red-200');
                } else if (data.remaining < 50) {
                    balanceDisplay.classList.add('bg-amber-50', 'border-amber-200');
                } else {
                    balanceDisplay.classList.add('bg-slate-50', 'border-slate-200');
                }
            } catch (error) {
                console.error('Error loading SMS credits:', error);
                document.getElementById('sms-assigned').textContent = '0';
                document.getElementById('sms-used').textContent = '0';
                document.getElementById('sms-remaining').textContent = '0';
                document.getElementById('sms-school').textContent = 'Error';
            }
        }

        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Save template modal
        function openSaveTemplateModal() {
            const message = document.getElementById('message-text').value.trim();
            if (!message) {
                showMessage('Please write a message first', 'error');
                return;
            }

            document.getElementById('template-message-en').value = message;
            document.getElementById('save-template-modal').classList.remove('hidden');
        }

        function closeSaveTemplateModal() {
            document.getElementById('save-template-modal').classList.add('hidden');
            document.getElementById('template-name').value = '';
            document.getElementById('template-message-en').value = '';
            document.getElementById('template-message-sw').value = '';
        }

        async function saveTemplate() {
            const name = document.getElementById('template-name').value.trim();
            const messageEn = document.getElementById('template-message-en').value.trim();
            const messageSw = document.getElementById('template-message-sw').value.trim();

            if (!name) {
                showMessage('Please enter a template name', 'error');
                return;
            }

            try {
                const response = await axios.post('/sms/templates', {
                    name: name,
                    message_en: messageEn,
                    message_sw: messageSw
                });

                showMessage('Template saved successfully.', 'success');
                closeSaveTemplateModal();

                // Reload templates
                location.reload();
            } catch (error) {
                showMessage('Failed to save template: ' + (error.response?.data?.message || error.message), 'error');
            }
        }

        // Clear form
        function clearForm() {
            document.getElementById('message-text').value = '';
            document.getElementById('thank-you-text').value = '';
            document.getElementById('template-select').value = '';
            selectedStudents = [];
            document.querySelectorAll('#students-list input[type="checkbox"]').forEach(cb => cb.checked = false);
            updateSelectedCount();
            updateCharCount();
            updateThankYouCharCount();
            updatePreview();
        }

        // Show message
        function showMessage(message, type) {
            const container = document.getElementById('message-container');
            const boxClass = type === 'success'
                ? 'mb-4 rounded-lg border border-slate-200 border-l-4 border-l-slate-700 bg-slate-50 p-4 text-slate-800 shadow-sm'
                : 'mb-4 rounded-lg border border-red-200 border-l-4 border-l-red-600 bg-red-50 p-4 text-red-800 shadow-sm';

            container.innerHTML = `
                <div class="${boxClass}" role="alert">
                    <p class="font-bold">${type === 'success' ? 'Success' : 'Error'}</p>
                    <p>${message}</p>
                </div>
            `;

            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
    </script>
@endpush
