@extends($portalLayout ?? 'layouts.accountant')

@section('title', 'Overdue — Darasa Finance')
@section('page_title', 'Overdue')

@section('content')
<!-- Module Content -->
    <div class="w-full p-6">
        <div>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-red-600">Overdue Payments</h2>
                <div class="flex gap-3">
                    @if(empty($readOnly))
                    <button onclick="openSmsModal()" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded font-bold transition">
                        Send SMS Reminders
                    </button>
                    @endif
                    <button onclick="loadOverdueData()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition">
                        Refresh
                    </button>
                </div>
            </div>

            @if(empty($readOnly))
            <!-- Enhanced SMS Reminder Modal -->
            <div id="smsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-2xl font-bold text-purple-600 mb-4">Send Overdue Payment Reminders</h3>

                    <!-- Class Filter -->
                    <div class="mb-4">
                        <label class="block font-bold mb-2">Filter by Class:</label>
                        <select id="classFilter" onchange="filterStudentsByClass()" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                            <option value="">All Classes</option>
                        </select>
                    </div>

                    <!-- Select All Checkbox -->
                    <div class="mb-4 flex items-center gap-2">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="w-4 h-4">
                        <label for="selectAll" class="font-bold cursor-pointer">Select All Students (<span id="selectedCount">0</span> selected)</label>
                    </div>

                    <!-- Student Checkboxes -->
                    <div id="studentCheckboxes" class="mb-4 max-h-60 overflow-y-auto border-2 border-gray-200 rounded-lg p-4">
                        <div class="text-gray-500 text-center">Loading students...</div>
                    </div>

                    <div class="mb-4">
                        <label class="block font-bold mb-2">Select Language:</label>
                        <div class="flex gap-4">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="language" value="english" checked class="mr-2" onchange="updateMessagePreview()">
                                <span>English</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="language" value="swahili" class="mr-2" onchange="updateMessagePreview()">
                                <span>Swahili</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block font-bold mb-2">Message Template (You can edit):</label>
                        <textarea id="messagePreview" class="w-full bg-gray-50 p-4 rounded border border-gray-300 text-sm" rows="12"></textarea>
                        <p class="text-xs text-gray-600 mt-1">
                            <strong>Variables:</strong> {student_name}, {particulars}, {total}
                        </p>
                    </div>

                    <div class="mb-4 bg-blue-50 border-2 border-blue-300 rounded p-4">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> SMS will only be sent to students whose parents have phone numbers saved in the system.
                            You will receive a report showing which students were skipped due to missing numbers.
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button onclick="confirmSendReminders()" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition">
                            Confirm & Send
                        </button>
                        <button onclick="closeSmsModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold transition">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Result Modal -->
            <div id="resultModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
                    <h3 class="text-2xl font-bold text-green-600 mb-4">SMS Sending Report</h3>
                    <div id="resultContent"></div>
                    <button onclick="closeResultModal()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded font-bold transition mt-4">
                        Close
                    </button>
                </div>
            </div>
            @endif

            <div id="overdueContent"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
const API_BASE = '/api';

        // Configure axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.withCredentials = true;

        // Format amount in Tanzania Shillings
        function formatTSh(amount) {
            return 'TSh ' + parseFloat(amount || 0).toLocaleString('en-TZ', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        // Load overdue data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadOverdueData();
            updateMessagePreview();
        });

        let overdueStudentsData = [];
        let allClasses = [];
        let selectedStudents = new Set();
        let filteredStudents = [];
        let currentPage = 1;
        let lastPage = 1;
        let totalItems = 0;

        function updateMessagePreview() {
            const language = document.querySelector('input[name="language"]:checked')?.value || 'english';

            const templates = {
                english: `Dear Parent of {student_name},

This is a reminder that your child has overdue fee payments.

Overdue Fees:
{particulars}

Total Amount Due: TSH {total}

Please make payment as soon as possible to avoid inconvenience.

Thank you.
Darasa Finance`,
                swahili: `Mzazi Mpendwa wa {student_name},

Hii ni ukumbusho kwamba mtoto wako ana malipo ya ada yaliyopita muda.

Ada Zilizopita Muda:
{particulars}

Jumla ya Kiasi: TSH {total}

Tafadhali fanya malipo haraka iwezekanavyo ili kuepuka usumbufu.

Asante.
Darasa Finance`
            };

            const previewEl = document.getElementById('messagePreview');
            if (previewEl) {
                previewEl.value = templates[language];
            }
        }

        function openSmsModal() {
            if (overdueStudentsData.length === 0) {
                alert('No overdue students found. Please refresh the data first.');
                return;
            }

            populateClassFilter();
            populateStudentCheckboxes(overdueStudentsData);
            document.getElementById('smsModal').classList.remove('hidden');
        }

        function populateClassFilter() {
            const filter = document.getElementById('classFilter');
            allClasses = [...new Set(overdueStudentsData.map(s => s.student?.class || 'Unknown'))].sort();

            filter.innerHTML = '<option value="">All Classes</option>';
            allClasses.forEach(className => {
                filter.innerHTML += `<option value="${className}">${className}</option>`;
            });
        }

        function filterStudentsByClass() {
            const selectedClass = document.getElementById('classFilter').value;
            filteredStudents = selectedClass === ''
                ? overdueStudentsData
                : overdueStudentsData.filter(s => s.student?.class === selectedClass);
            populateStudentCheckboxes(filteredStudents);
            updateSelectedCount();
        }

        function populateStudentCheckboxes(students) {
            const container = document.getElementById('studentCheckboxes');
            filteredStudents = students;

            if (!students || students.length === 0) {
                container.innerHTML = '<div class="text-gray-500 text-center">No students found</div>';
                return;
            }

            container.innerHTML = students.map(student => {
                const isChecked = selectedStudents.has(student.student?.id);
                return `
                    <div class="flex items-start gap-2 p-2 hover:bg-gray-50 rounded">
                        <input type="checkbox"
                               class="student-checkbox mt-1"
                               value="${student.student?.id}"
                               ${isChecked ? 'checked' : ''}
                               onchange="updateSelectedStudents()">
                        <div class="flex-1">
                            <p class="font-semibold">${student.student?.name || 'Unknown'}</p>
                            <p class="text-xs text-gray-500">Reg: ${student.student?.reg_no || 'N/A'} | Class: ${student.student?.class || 'N/A'}</p>
                            <p class="text-xs text-red-600 font-semibold">Overdue: ${formatTSh(student.total_overdue)}</p>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll').checked;
            document.querySelectorAll('.student-checkbox').forEach(checkbox => {
                checkbox.checked = selectAll;
                const studentId = parseInt(checkbox.value);
                selectAll ? selectedStudents.add(studentId) : selectedStudents.delete(studentId);
            });
            updateSelectedCount();
        }

        function updateSelectedStudents() {
            selectedStudents.clear();
            document.querySelectorAll('.student-checkbox:checked').forEach(checkbox => {
                selectedStudents.add(parseInt(checkbox.value));
            });
            const allCheckboxes = document.querySelectorAll('.student-checkbox');
            document.getElementById('selectAll').checked =
                allCheckboxes.length > 0 && Array.from(allCheckboxes).every(cb => cb.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            document.getElementById('selectedCount').textContent = selectedStudents.size;
        }

        function closeSmsModal() {
            document.getElementById('smsModal').classList.add('hidden');
            selectedStudents.clear();
            document.getElementById('selectAll').checked = false;
            updateSelectedCount();
        }

        function closeResultModal() {
            document.getElementById('resultModal').classList.add('hidden');
        }

        async function confirmSendReminders() {
            if (selectedStudents.size === 0) {
                alert('Please select at least one student to send reminders to.');
                return;
            }

            const language = document.querySelector('input[name="language"]:checked').value;
            const customTemplate = document.getElementById('messagePreview').value;

            closeSmsModal();
            document.getElementById('resultContent').innerHTML = '<p class="text-center py-4">Sending SMS reminders... Please wait.</p>';
            document.getElementById('resultModal').classList.remove('hidden');

            const selectedStudentData = overdueStudentsData.filter(s => selectedStudents.has(s.student?.id));

            try {
                const response = await axios.post('/accountant/sms/send-overdue-reminders', {
                    language: language,
                    custom_template: customTemplate,
                    students: selectedStudentData
                });

                const result = response.data;
                let html = `
                    <div class="mb-4 bg-green-50 border-2 border-green-300 rounded p-4">
                        <h4 class="font-bold text-green-800 mb-2">Successfully Sent</h4>
                        <p class="text-green-700">${result.success_count || 0} SMS messages sent successfully</p>
                    </div>
                `;

                if (result.skipped_count > 0) {
                    html += `
                        <div class="mb-4 bg-yellow-50 border-2 border-yellow-300 rounded p-4">
                            <h4 class="font-bold text-yellow-800 mb-2">Skipped (No Phone Number)</h4>
                            <p class="text-yellow-700">${result.skipped_count} students skipped</p>
                        </div>
                    `;
                }

                document.getElementById('resultContent').innerHTML = html;
            } catch (error) {
                document.getElementById('resultContent').innerHTML = `
                    <div class="bg-red-50 border-2 border-red-300 rounded p-4">
                        <h4 class="font-bold text-red-800 mb-2">Error</h4>
                        <p class="text-red-700">${error.response?.data?.message || error.message}</p>
                    </div>
                `;
            }
        }

        async function loadOverdueData(page = 1) {
            const content = document.getElementById('overdueContent');
            content.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">Loading overdue data...</p></div>';

            try {
                const response = await axios.get(`${API_BASE}/overdue-amounts?page=${page}`);
                const data = response.data;

                overdueStudentsData = data.overdue_by_student || [];
                currentPage = data.pagination?.current_page || 1;
                lastPage = data.pagination?.last_page || 1;
                totalItems = data.pagination?.total || 0;

                let html = `
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-red-50 border-2 border-red-300 rounded-lg p-4">
                            <h4 class="text-sm font-bold text-red-700 mb-2">Total Overdue Amount</h4>
                            <p class="text-3xl font-bold text-red-600">${formatTSh(data.summary?.total_overdue_amount || 0)}</p>
                        </div>
                        <div class="bg-orange-50 border-2 border-orange-300 rounded-lg p-4">
                            <h4 class="text-sm font-bold text-orange-700 mb-2">Students with Overdue</h4>
                            <p class="text-3xl font-bold text-orange-600">${data.summary?.total_students_with_overdue || 0}</p>
                        </div>
                        <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-4">
                            <h4 class="text-sm font-bold text-yellow-700 mb-2">Overdue Particulars</h4>
                            <p class="text-3xl font-bold text-yellow-600">${data.summary?.total_overdue_particulars || 0}</p>
                        </div>
                    </div>
                `;

                // By Particular Summary
                if (data.overdue_by_particular && data.overdue_by_particular.length > 0) {
                    html += `
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-gray-700 mb-3">Overdue by Particular</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    `;
                    data.overdue_by_particular.forEach(item => {
                        html += `
                            <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-4">
                                <h4 class="font-bold text-lg text-yellow-800">${item.particular?.name || 'Unknown'}</h4>
                                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <p class="text-gray-600">Students Overdue:</p>
                                        <p class="font-bold text-lg">${item.total_students_overdue || 0}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Amount Overdue:</p>
                                        <p class="font-bold text-lg text-red-600">${formatTSh(item.total_amount_overdue)}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += `</div></div>`;
                }

                // Students Table
                html += `
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-700 mb-3">Students with Overdue Payments</h3>
                        <input type="text" id="studentSearch" placeholder="Search student..."
                               class="border-2 border-gray-300 rounded-lg px-4 py-2 w-full md:w-96 mb-4"
                               onkeyup="filterStudents()">
                    </div>
                    <div class="overflow-x-auto mb-6">
                        <table class="w-full border-2 border-gray-300 rounded-lg">
                            <thead class="bg-red-100">
                                <tr>
                                    <th class="p-3 text-left">Student</th>
                                    <th class="p-3 text-left">Class</th>
                                    <th class="p-3 text-left">Overdue Particulars</th>
                                    <th class="p-3 text-right">Total Overdue</th>
                                </tr>
                            </thead>
                            <tbody id="overdueTableBody">
                `;

                if (overdueStudentsData.length === 0) {
                    html += `<tr><td colspan="4" class="p-8 text-center text-gray-500">No overdue students found</td></tr>`;
                } else {
                    overdueStudentsData.forEach(student => {
                        const particularsDetails = (student.overdue_particulars || []).map(p => {
                            let detail = `${p.particular_name}: ${formatTSh(p.amount_due)} (${Math.abs(Math.round(p.days_overdue))} days)`;
                            if (p.has_scholarship) {
                                detail += ` <span class="bg-amber-200 text-amber-800 text-xs px-1 rounded">🎓 Scholarship</span>`;
                            }
                            return detail;
                        }).join('<br>');

                        const hasScholarship = student.student?.has_scholarship;
                        const scholarshipBadge = hasScholarship
                            ? `<span class="ml-2 bg-amber-200 text-amber-800 text-xs px-2 py-0.5 rounded">🎓 Scholarship: ${formatTSh(student.student?.total_scholarship_amount || 0)}</span>`
                            : '';

                        html += `
                            <tr class="border-t hover:bg-red-50 ${hasScholarship ? 'bg-amber-50' : ''}">
                                <td class="p-3">
                                    <p class="font-bold">${student.student?.name || 'Unknown'}${scholarshipBadge}</p>
                                    <p class="text-xs text-gray-500">${student.student?.reg_no || 'N/A'}</p>
                                </td>
                                <td class="p-3">${student.student?.class || 'N/A'}</td>
                                <td class="p-3 text-sm">${particularsDetails || 'No details'}</td>
                                <td class="p-3 text-right font-bold text-red-600 text-lg">${formatTSh(student.total_overdue)}</td>
                            </tr>
                        `;
                    });
                }

                html += `
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Page ${currentPage} of ${lastPage} (${totalItems} total)
                        </div>
                        <div class="flex gap-2">
                            <button onclick="previousPage()" id="prevBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded transition ${currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${currentPage <= 1 ? 'disabled' : ''}>
                                Previous
                            </button>
                            <button onclick="nextPage()" id="nextBtn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded transition ${currentPage >= lastPage ? 'opacity-50 cursor-not-allowed' : ''}" ${currentPage >= lastPage ? 'disabled' : ''}>
                                Next
                            </button>
                        </div>
                    </div>
                `;

                content.innerHTML = html;
            } catch (error) {
                console.error('Error loading overdue data:', error);
                content.innerHTML = `
                    <div class="bg-red-50 border-2 border-red-300 rounded-lg p-6 text-center">
                        <p class="text-red-600 font-bold mb-2">Error loading overdue data</p>
                        <p class="text-red-500">${error.response?.data?.error || error.message}</p>
                        <button onclick="loadOverdueData()" class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            Retry
                        </button>
                    </div>
                `;
            }
        }

        function previousPage() {
            if (currentPage > 1) {
                loadOverdueData(currentPage - 1);
            }
        }

        function nextPage() {
            if (currentPage < lastPage) {
                loadOverdueData(currentPage + 1);
            }
        }

        function filterStudents() {
            const searchValue = (document.getElementById('studentSearch')?.value || '').toLowerCase();
            const rows = document.querySelectorAll('#overdueTableBody tr');
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchValue) ? '' : 'none';
            });
        }
    </script>
@endpush
