@extends($portalLayout ?? 'layouts.accountant')

@section('title', 'Particulars — Darasa Finance')
@section('page_title', 'Particulars')

@section('content')
    <div class="mx-auto max-w-7xl px-0 sm:px-1">
        <div>
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-bold text-blue-700 md:text-3xl">Particulars management</h2>
                <button type="button" onclick="showCreateParticularForm()" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-blue-500 to-sky-600 px-6 py-3 text-sm font-semibold text-white shadow-md shadow-blue-500/25 transition hover:from-blue-600 hover:to-sky-700">
                    Create new particular
                </button>
            </div>
            <div id="particularsList" class="mt-4"></div>
            <div id="particularFormContainer"></div>
        </div>
    </div>

    <!-- Module Scripts -->
@endsection

@push('scripts')
    <script>
const API_BASE = '/api';
        let allBooks = [];
        let allParticulars = [];
        let allStudents = [];
        let allClasses = [];
        let allAcademicYears = [];
        let selectedAcademicYearId = null;

        // Configure axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.withCredentials = true;

        // Money input formatting (commas) while keeping numeric payloads
        function parseMoneyInput(value) {
            if (value === null || value === undefined) return 0;
            const cleaned = String(value).replace(/,/g, '').trim();
            const n = parseFloat(cleaned);
            return Number.isFinite(n) ? n : 0;
        }

        function formatMoneyForInput(value) {
            const n = parseMoneyInput(value);
            return n.toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function attachMoneyFormattingToEl(el) {
            if (!el) return;
            el.setAttribute('inputmode', 'decimal');
            el.addEventListener('focus', () => { el.value = String(el.value || '').replace(/,/g, ''); });
            el.addEventListener('blur', () => { if (el.value !== '') el.value = formatMoneyForInput(el.value); });
        }

        function applyMoneyFormattingInContainer(container) {
            const root = container || document;
            root.querySelectorAll('input.money-input').forEach(attachMoneyFormattingToEl);
        }

        // Load initial data on page load
        document.addEventListener('DOMContentLoaded', async function() {
            await loadInitialData();
            await loadParticulars();
        });

        async function loadInitialData() {
            try {
                const [booksResponse, studentsResponse, classesResponse, academicYearsResponse] = await Promise.all([
                    axios.get(`${API_BASE}/books`),
                    axios.get(`${API_BASE}/students`),
                    axios.get(`${API_BASE}/classes`),
                    axios.get(`${API_BASE}/academic-years`)
                ]);

                allBooks = booksResponse.data;
                allStudents = studentsResponse.data.students || studentsResponse.data;
                allClasses = classesResponse.data;
                allAcademicYears = academicYearsResponse.data;

                // Set default selected academic year to current
                const currentYear = allAcademicYears.find(y => y.is_current);
                if (currentYear) {
                    selectedAcademicYearId = currentYear.id;
                } else if (allAcademicYears.length > 0) {
                    selectedAcademicYearId = allAcademicYears[0].id;
                }
            } catch (error) {
                console.error('Error loading initial data:', error);
            }
        }

        async function loadParticulars() {
            try {
                const response = await axios.get(`${API_BASE}/particulars`);
                allParticulars = response.data;

                let html = '<div class="space-y-4">';
                allParticulars.forEach(particular => {
                    html += `
                        <div class="border-2 border-green-300 rounded-lg p-6 bg-green-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-bold text-xl text-green-700">${particular.name}</h3>
                                    <p class="text-sm text-gray-600 mt-2">📚 Books: ${particular.book_ids?.length || 0} assigned</p>
                                    <p class="text-sm text-gray-600">👥 Students: ${particular.students?.length || 0} assigned</p>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick='showEditParticularForm(${JSON.stringify(particular)})'
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition">
                                        ✏️ Edit
                                    </button>
                                    <button onclick="showAssignStudentsForm(${particular.id}, '${particular.name}')"
                                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                                        👥 Manage Assignments
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('particularsList').innerHTML = html;
            } catch (error) {
                alert('Error loading particulars: ' + error.message);
            }
        }

        function showCreateParticularForm() {
            const booksCheckboxes = allBooks.map(book =>
                `<label class="flex items-center gap-2 p-3 bg-gray-50 hover:bg-blue-50 rounded border-2 border-gray-300 hover:border-blue-400 cursor-pointer transition">
                    <input type="checkbox" class="book-checkbox w-5 h-5 rounded border-gray-300" value="${book.id}">
                    <span class="font-semibold">${book.name}</span>
                    ${book.is_cash_book ? '<span class="text-xs text-green-600 font-bold ml-auto">💵 Cash Book</span>' : '<span class="text-xs text-blue-600 font-bold ml-auto">🏦 Bank</span>'}
                </label>`
            ).join('');

            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
                    <div class="bg-white rounded-lg p-8 max-w-2xl w-full shadow-2xl m-4">
                        <h3 class="text-2xl font-bold mb-6 text-green-600">Create New Particular</h3>
                        <form onsubmit="createParticular(event)" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold mb-2">Particular Name *</label>
                                <input type="text" id="particularName" required
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-green-500 focus:outline-none"
                                    placeholder="e.g., Tuition Fees, Food Fees, Transport">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Assign Books * (Check to select)</label>
                                <div class="border-2 border-gray-300 rounded-lg p-4 max-h-64 overflow-y-auto space-y-2">
                                    ${booksCheckboxes}
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Select one or more books where this fee can be paid</p>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-bold transition">
                                    💾 Save Particular
                                </button>
                                <button type="button" onclick="closeParticularForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
                                    ✖️ Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('particularFormContainer').innerHTML = formHtml;
        }

        function closeParticularForm() {
            document.getElementById('particularFormContainer').innerHTML = '';
        }

        async function createParticular(event) {
            event.preventDefault();
            const name = document.getElementById('particularName').value;
            const bookCheckboxes = document.querySelectorAll('.book-checkbox:checked');
            const bookIds = Array.from(bookCheckboxes).map(cb => parseInt(cb.value));

            if (bookIds.length === 0) {
                alert('⚠️ Please select at least one book');
                return;
            }

            try {
                await axios.post(`${API_BASE}/particulars`, {
                    name: name,
                    book_ids: bookIds,
                    class_names: []
                });
                alert('✅ Particular created successfully!');
                closeParticularForm();
                loadParticulars();
            } catch (error) {
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        function showEditParticularForm(particular) {
            const booksCheckboxes = allBooks.map(book => {
                const isChecked = particular.book_ids?.includes(book.id) ? 'checked' : '';
                return `<label class="flex items-center gap-2 p-3 bg-gray-50 hover:bg-blue-50 rounded border-2 border-gray-300 hover:border-blue-400 cursor-pointer transition">
                    <input type="checkbox" class="book-checkbox w-5 h-5 rounded border-gray-300" value="${book.id}" ${isChecked}>
                    <span class="font-semibold">${book.name}</span>
                    ${book.is_cash_book ? '<span class="text-xs text-green-600 font-bold ml-auto">💵 Cash Book</span>' : '<span class="text-xs text-blue-600 font-bold ml-auto">🏦 Bank</span>'}
                </label>`;
            }).join('');

            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
                    <div class="bg-white rounded-lg p-8 max-w-2xl w-full shadow-2xl m-4">
                        <h3 class="text-2xl font-bold mb-6 text-yellow-600">✏️ Edit Particular</h3>
                        <form onsubmit="updateParticular(event, ${particular.id})" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold mb-2">Particular Name *</label>
                                <input type="text" id="editParticularName" required
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 focus:border-yellow-500 focus:outline-none"
                                    placeholder="e.g., Tuition Fees, Food Fees, Transport"
                                    value="${particular.name}">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Assign Books * (Check to select)</label>
                                <div class="border-2 border-gray-300 rounded-lg p-4 max-h-64 overflow-y-auto space-y-2">
                                    ${booksCheckboxes}
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Select one or more books where this fee can be paid</p>
                                <p class="text-xs text-orange-600 font-semibold mt-2">⚠️ Note: Unchecking a book will not delete existing entries but will prevent future entries to that book.</p>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="submit" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-bold transition">
                                    💾 Update Particular
                                </button>
                                <button type="button" onclick="closeParticularForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
                                    ✖️ Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.getElementById('particularFormContainer').innerHTML = formHtml;
        }

        async function updateParticular(event, particularId) {
            event.preventDefault();
            const name = document.getElementById('editParticularName').value;
            const bookCheckboxes = document.querySelectorAll('.book-checkbox:checked');
            const bookIds = Array.from(bookCheckboxes).map(cb => parseInt(cb.value));

            if (bookIds.length === 0) {
                alert('⚠️ Please select at least one book');
                return;
            }

            try {
                await axios.put(`${API_BASE}/particulars/${particularId}`, {
                    name: name,
                    book_ids: bookIds
                });
                alert('✅ Particular updated successfully!');
                closeParticularForm();
                loadParticulars();
            } catch (error) {
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        // ===== ASSIGN STUDENTS TO PARTICULAR =====
        async function showAssignStudentsForm(particularId, particularName) {
            // Store for later use
            currentParticularForExisting = { id: particularId, name: particularName };

            // Always reload classes and students fresh
            if (allClasses.length === 0) {
                try {
                    const classesResponse = await axios.get(`${API_BASE}/classes`);
                    allClasses = classesResponse.data;
                } catch (error) {
                    console.error('Error loading classes:', error);
                    allClasses = [];
                }
            }

            // Reload academic years if not loaded
            if (allAcademicYears.length === 0) {
                try {
                    const academicYearsResponse = await axios.get(`${API_BASE}/academic-years`);
                    allAcademicYears = academicYearsResponse.data;
                    const currentYear = allAcademicYears.find(y => y.is_current);
                    if (currentYear) {
                        selectedAcademicYearId = currentYear.id;
                    } else if (allAcademicYears.length > 0) {
                        selectedAcademicYearId = allAcademicYears[0].id;
                    }
                } catch (error) {
                    console.error('Error loading academic years:', error);
                }
            }

            const academicYearOptions = allAcademicYears.map(year =>
                `<option value="${year.id}" ${year.id === selectedAcademicYearId ? 'selected' : ''}>${year.name} ${year.is_current ? '(Current)' : ''}</option>`
            ).join('');

            const classButtons = allClasses.map(cls =>
                `<button type="button" onclick="loadStudentsForClass(${particularId}, ${cls.id}, '${cls.name}')"
                    class="class-btn bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-bold transition transform hover:scale-105">
                    ${cls.name}
                </button>`
            ).join('');

            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
                    <div class="bg-white rounded-lg p-8 max-w-6xl w-full shadow-2xl m-4 max-h-[90vh] overflow-y-auto">
                        <h3 class="text-2xl font-bold mb-4 text-green-600">📋 Manage Assignments: ${particularName}</h3>

                        <!-- Academic Year Selection (Required) -->
                        <div class="mb-6 p-6 bg-yellow-50 rounded-lg border-2 border-yellow-400">
                            <label class="block text-lg font-bold mb-3 text-center text-yellow-700">📅 Step 1: Select Academic Year (Required)</label>
                            <div class="flex justify-center">
                                <select id="academicYearSelect" class="w-64 border-2 border-yellow-400 rounded-lg px-4 py-3 text-lg font-semibold bg-white focus:border-yellow-600 focus:outline-none" onchange="onAcademicYearChange(this.value)">
                                    ${academicYearOptions.length > 0 ? academicYearOptions : '<option value="">No Academic Years Available</option>'}
                                </select>
                            </div>
                            <p class="text-sm text-yellow-600 text-center mt-2">All fee assignments will be linked to this academic year</p>
                        </div>

                        <!-- Advance Payment Option -->
                        <div class="mb-6 p-5 bg-emerald-50 rounded-lg border-2 border-emerald-300">
                            <label class="flex items-center justify-center gap-3 text-emerald-800 font-bold">
                                <input type="checkbox" id="useAdvanceToggle" class="w-5 h-5 rounded border-gray-300">
                                Use students' advance payments to auto-pay new assignments (if available)
                            </label>
                            <p class="text-xs text-emerald-700 text-center mt-2">If a student has advance balance, it will be applied up to the assigned amount and recorded in the ledger.</p>
                        </div>

                        <div class="mb-6 p-6 bg-blue-50 rounded-lg border-2 border-blue-300">
                            <label class="block text-lg font-bold mb-4 text-center">📚 Step 2: Select Class to View Students:</label>
                            <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                ${classButtons}
                            </div>
                        </div>

                        <div id="studentsListContainer" class="mb-6"></div>

                        <div class="flex gap-3 pt-4 border-t-2">
                            <button type="button" onclick="closeAssignForm()"
                                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
                                ✖️ Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('particularFormContainer').innerHTML = formHtml;
        }

        function onAcademicYearChange(yearId) {
            selectedAcademicYearId = parseInt(yearId);
            // Clear the students list when academic year changes
            document.getElementById('studentsListContainer').innerHTML = '<p class="text-center text-gray-500 p-4">Select a class to view students for the selected academic year.</p>';
        }

        function toggleSelectAllVisibleStudents(checked) {
            document.querySelectorAll('.student-select-checkbox').forEach(cb => {
                cb.checked = checked;
            });
        }

        function applyBulkValueToSelected(selector, value) {
            document.querySelectorAll('.student-select-checkbox:checked').forEach(cb => {
                const studentId = cb.getAttribute('data-student-id');
                const input = document.querySelector(`${selector}[data-student-id="${studentId}"]`);
                if (input) input.value = value;
            });
        }

        function applyBulkAmount() {
            const bulkAmount = document.getElementById('bulkAmountInput')?.value;
            const amount = parseMoneyInput(bulkAmount);
            if (!amount || amount <= 0) {
                alert('⚠️ Enter a valid bulk amount');
                return;
            }
            applyBulkValueToSelected('.student-amount', amount);
        }

        /** Bulk deadline when assigning by class + academic year (uses #bulkDeadlineInput, .student-deadline) */
        function applyBulkDeadlineForClassStudents() {
            const bulkDeadline = document.getElementById('bulkDeadlineInput')?.value;
            if (!bulkDeadline) {
                alert('⚠️ Select a bulk deadline date');
                return;
            }
            applyBulkValueToSelected('.student-deadline', bulkDeadline);
        }

        async function loadStudentsForClass(particularId, classId, className) {
            if (!classId) {
                document.getElementById('studentsListContainer').innerHTML = '';
                return;
            }

            // Check if academic year is selected
            if (!selectedAcademicYearId) {
                alert('⚠️ Please select an Academic Year first');
                return;
            }

            // Show loading
            document.getElementById('studentsListContainer').innerHTML = '<p class="text-center text-blue-600 p-4">⏳ Loading students...</p>';

            try {
                // Load all students with assignment status for this particular and academic year
                const response = await axios.get(`${API_BASE}/particulars/${particularId}/students-for-new-assignment?academic_year_id=${selectedAcademicYearId}`);
                const allStudents = response.data;

                // Filter students by class
                const classStudents = allStudents.filter(s => s.class_name === className);

                if (classStudents.length === 0) {
                    document.getElementById('studentsListContainer').innerHTML =
                        '<p class="text-center text-gray-500 p-4">No students found in this class.</p>';
                    return;
                }

                let html = `
                    <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                            <h4 class="font-bold text-lg">Students in ${className}</h4>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="w-5 h-5 rounded border-gray-300" onchange="toggleSelectAllVisibleStudents(this.checked)">
                                    <span class="text-sm font-bold">Select all</span>
                                </label>
                                <div class="hidden md:block h-6 w-px bg-gray-300"></div>
                                <div class="flex items-center gap-2">
                                    <input id="bulkAmountInput" type="text" class="money-input w-32 border-2 border-gray-300 rounded px-2 py-1 text-sm" placeholder="Bulk amount">
                                    <button onclick="applyBulkAmount()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm font-bold">Apply</button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input id="bulkDeadlineInput" type="date" class="w-40 border-2 border-gray-300 rounded px-2 py-1 text-sm">
                                    <button onclick="applyBulkDeadlineForClassStudents()" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-sm font-bold">Apply</button>
                                </div>
                                <div class="hidden md:block h-6 w-px bg-gray-300"></div>
                                <button onclick="bulkAssignSelected(${particularId})" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-bold">Bulk assign</button>
                                <button onclick="bulkUpdateSelected(${particularId})" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm font-bold">Bulk update</button>
                            </div>
                        </div>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                `;

                classStudents.forEach(student => {
                    const bgClass = student.has_assignment ? 'bg-green-50 border-green-400' : 'bg-white';
                    const checkmark = student.has_assignment ? '✅ ' : '';

                    html += `
                        <div class="flex items-center gap-2 p-3 ${bgClass} rounded border-2 hover:border-blue-500">
                            <input type="checkbox"
                                class="student-select-checkbox w-5 h-5 rounded border-gray-300"
                                data-student-id="${student.student_id}">
                            <div class="flex-1">
                                <p class="font-bold">${checkmark}${student.student_name}</p>
                                <p class="text-xs text-gray-500">${student.student_reg_no}</p>
                            </div>
                            <div class="flex gap-2 items-center">
                                <span class="text-xs font-bold">Amount (TSH):</span>
                                <input type="text" value="${student.sales || ''}"
                                    class="money-input student-amount w-32 border-2 border-gray-300 rounded px-2 py-1 text-sm"
                                    data-student-id="${student.student_id}"
                                    data-original="${student.sales || 0}"
                                    placeholder="0.00">
                            </div>
                            <div class="flex gap-2 items-center">
                                <span class="text-xs font-bold">Deadline:</span>
                                <input type="date" value="${student.deadline || ''}"
                                    class="student-deadline w-36 border-2 border-gray-300 rounded px-2 py-1 text-sm"
                                    data-student-id="${student.student_id}">
                            </div>
                            ${student.has_assignment ? `
                            <button onclick="saveStudentEdit(${particularId}, ${student.student_id})"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm font-bold">
                                ✏️ Update
                            </button>
                            ` : `
                            <button onclick="assignSingleStudent(${particularId}, ${student.student_id})"
                                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm font-bold">
                                ➕ Assign
                            </button>
                            `}
                        </div>
                    `;
                });

                html += '</div></div>';
                document.getElementById('studentsListContainer').innerHTML = html;
                applyMoneyFormattingInContainer(document.getElementById('studentsListContainer'));
                applyMoneyFormattingInContainer(document.getElementById('assignActionsContainer'));

            } catch (error) {
                console.error('Error loading students:', error);
                document.getElementById('studentsListContainer').innerHTML =
                    '<p class="text-center text-red-500 p-4">❌ Error loading students</p>';
            }
        }

        function getSelectedRowsForBulk() {
            const selected = [];
            document.querySelectorAll('.student-select-checkbox:checked').forEach(cb => {
                const studentId = parseInt(cb.getAttribute('data-student-id'));
                const amountInput = document.querySelector(`.student-amount[data-student-id="${studentId}"]`);
                const deadlineInput = document.querySelector(`.student-deadline[data-student-id="${studentId}"]`);
                const amount = parseMoneyInput(amountInput?.value || '0');
                const original = parseMoneyInput(amountInput?.getAttribute('data-original') || '0');

                selected.push({
                    student_id: studentId,
                    sales: amount,
                    deadline: deadlineInput?.value || null,
                    original_sales: original,
                });
            });
            return selected;
        }

        async function bulkAssignSelected(particularId) {
            if (!selectedAcademicYearId) {
                alert('⚠️ Please select an Academic Year first');
                return;
            }

            const selected = getSelectedRowsForBulk();
            if (selected.length === 0) {
                alert('⚠️ Select at least one student');
                return;
            }

            const assignments = selected
                .filter(r => r.sales && r.sales > 0)
                .map(r => ({
                    student_id: r.student_id,
                    sales: r.sales,
                    deadline: r.deadline,
                }));

            if (assignments.length === 0) {
                alert('⚠️ Enter amount(s) for selected students');
                return;
            }

            try {
                const useAdvance = document.getElementById('useAdvanceToggle')?.checked || false;
                await axios.post(`${API_BASE}/particulars/${particularId}/bulk-opening-balance`, {
                    assignments,
                    academic_year_id: selectedAcademicYearId,
                    use_advance: useAdvance,
                });
                alert(`✅ Bulk assign complete for ${assignments.length} student(s)`);
                showAssignStudentsForm(particularId, currentParticularForExisting.name);
            } catch (error) {
                console.error('Error bulk assigning:', error);
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        async function bulkUpdateSelected(particularId) {
            if (!selectedAcademicYearId) {
                alert('⚠️ Please select an Academic Year first');
                return;
            }

            const selected = getSelectedRowsForBulk();
            if (selected.length === 0) {
                alert('⚠️ Select at least one student');
                return;
            }

            const updates = selected
                .map(r => {
                    const sales = (r.sales && r.sales > 0)
                        ? r.sales
                        : parseMoneyInput(String(r.original_sales ?? 0));
                    return { student_id: r.student_id, sales, deadline: r.deadline };
                })
                .filter(r => r.sales > 0);

            if (updates.length === 0) {
                alert('⚠️ Selected students need a fee amount (from the row or saved assignment). Enter amounts or choose students who are already assigned.');
                return;
            }

            try {
                await axios.put(`${API_BASE}/particulars/${particularId}/bulk-update-assignments`, {
                    updates,
                    academic_year_id: selectedAcademicYearId,
                });
                alert(`✅ Bulk update complete for ${updates.length} student(s)`);
                showAssignStudentsForm(particularId, currentParticularForExisting.name);
            } catch (error) {
                console.error('Error bulk updating:', error);
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        async function assignSingleStudent(particularId, studentId) {
            // Check if academic year is selected
            if (!selectedAcademicYearId) {
                alert('⚠️ Please select an Academic Year first');
                return;
            }

            const amountInput = document.querySelector(`.student-amount[data-student-id="${studentId}"]`);
            const deadlineInput = document.querySelector(`.student-deadline[data-student-id="${studentId}"]`);

            const amount = parseMoneyInput(amountInput.value);
            const deadline = deadlineInput.value;

            if (!amount || amount <= 0) {
                alert('⚠️ Please enter a valid amount');
                return;
            }

            try {
                await axios.post(`${API_BASE}/particulars/${particularId}/assignments`, {
                    student_id: studentId,
                    sales: amount,
                    deadline: deadline || null,
                    academic_year_id: selectedAcademicYearId
                });

                alert('✅ Student assigned successfully!');
                // Reload the class view
                const className = amountInput.closest('[class*="border-2"]').parentElement.querySelector('h4').textContent.replace('Students in ', '');
                showAssignStudentsForm(particularId, currentParticularForExisting.name);
            } catch (error) {
                console.error('Error assigning student:', error);
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        async function saveStudentEdit(particularId, studentId) {
            const amountInput = document.querySelector(`.student-amount[data-student-id="${studentId}"]`);
            const deadlineInput = document.querySelector(`.student-deadline[data-student-id="${studentId}"]`);

            const newAmount = parseMoneyInput(amountInput.value);
            const originalAmount = parseMoneyInput(amountInput.getAttribute('data-original'));
            const deadline = deadlineInput.value;

            if (!newAmount || newAmount <= 0) {
                alert('⚠️ Please enter a valid amount');
                return;
            }

            // Check if anything actually changed
            const amountChanged = Math.abs(newAmount - originalAmount) > 0.001;

            if (!amountChanged && !deadline) {
                alert('⚠️ No changes detected');
                return;
            }

            try {
                await axios.put(`${API_BASE}/particulars/${particularId}/assignments/${studentId}`, {
                    sales: newAmount,
                    deadline: deadline || null,
                    academic_year_id: selectedAcademicYearId
                });

                if (amountChanged) {
                    alert(`✅ Updated successfully! Ledger entry created for amount change (${originalAmount} → ${newAmount})`);
                } else {
                    alert('✅ Deadline updated successfully!');
                }

                // Reload the class view
                showAssignStudentsForm(particularId, currentParticularForExisting.name);
            } catch (error) {
                console.error('Error updating assignment:', error);
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        function showNewAssignmentForm(particularId, studentId, studentName) {
            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-8 max-w-md w-full shadow-2xl m-4">
                        <h3 class="text-xl font-bold mb-4 text-green-600">➕ Assign to: ${studentName}</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block font-bold mb-2">Amount (TSH):</label>
                                <input type="text" id="newAssignAmount" class="money-input w-full border-2 border-gray-300 rounded px-3 py-2" placeholder="0.00" required>
                            </div>
                            <div>
                                <label class="block font-bold mb-2">Deadline (Optional):</label>
                                <input type="date" id="newAssignDeadline" class="w-full border-2 border-gray-300 rounded px-3 py-2">
                            </div>
                            <label class="flex items-center gap-2 text-emerald-800 font-semibold text-sm">
                                <input type="checkbox" id="newAssignUseAdvance" class="w-4 h-4 rounded border-gray-300">
                                Use student advance (if available) to auto-pay this assignment
                            </label>
                            <div class="flex gap-3 pt-4 border-t-2">
                                <button onclick="closeNewAssignmentForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-bold">Cancel</button>
                                <button onclick="saveNewAssignment(${particularId}, ${studentId})" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-bold">✅ Assign</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', formHtml);
            applyMoneyFormattingInContainer(document.body);
        }

        function closeNewAssignmentForm() {
            document.querySelector('.fixed.inset-0').remove();
        }

        async function saveNewAssignment(particularId, studentId) {
            // Check if academic year is selected
            if (!selectedAcademicYearId) {
                alert('⚠️ Please select an Academic Year first');
                return;
            }

            const amount = document.getElementById('newAssignAmount').value;
            const deadline = document.getElementById('newAssignDeadline').value;

            if (!amount || amount <= 0) {
                alert('⚠️ Please enter a valid amount');
                return;
            }

            try {
                await axios.post(`${API_BASE}/particulars/${particularId}/assignments`, {
                    student_id: studentId,
                    sales: parseMoneyInput(amount),
                    deadline: deadline || null,
                    academic_year_id: selectedAcademicYearId,
                    use_advance: document.getElementById('newAssignUseAdvance')?.checked || false,
                });

                alert('✅ Student assigned successfully!');
                closeNewAssignmentForm();
                showAssignStudentsForm(particularId, currentParticularForExisting?.name || 'Particular');
            } catch (error) {
                console.error('Error assigning student:', error);
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        function showEditAssignmentForm(particularId, studentId, studentName, currentAmount, currentDeadline) {
            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-8 max-w-md w-full shadow-2xl m-4">
                        <h3 class="text-xl font-bold mb-4 text-yellow-600">✏️ Edit Assignment: ${studentName}</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block font-bold mb-2">Current Amount: TSH ${currentAmount.toLocaleString()}</label>
                                <label class="block font-bold mb-2">New Amount (TSH):</label>
                                <input type="text" id="editAssignAmount" value="${currentAmount}" class="money-input w-full border-2 border-gray-300 rounded px-3 py-2" required>
                                <p class="text-xs text-gray-600 mt-1">Note: If amount changes, a ledger entry will be created automatically</p>
                            </div>
                            <div>
                                <label class="block font-bold mb-2">Deadline:</label>
                                <input type="date" id="editAssignDeadline" value="${currentDeadline}" class="w-full border-2 border-gray-300 rounded px-3 py-2">
                            </div>
                            <div class="flex gap-3 pt-4 border-t-2">
                                <button onclick="closeEditAssignmentForm()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-bold">Cancel</button>
                                <button onclick="saveEditAssignment(${particularId}, ${studentId})" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-bold">💾 Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', formHtml);
            applyMoneyFormattingInContainer(document.body);
        }

        function closeEditAssignmentForm() {
            document.querySelector('.fixed.inset-0:last-of-type').remove();
        }

        async function saveEditAssignment(particularId, studentId) {
            const amount = document.getElementById('editAssignAmount').value;
            const deadline = document.getElementById('editAssignDeadline').value;

            if (!amount || amount <= 0) {
                alert('⚠️ Please enter a valid amount');
                return;
            }

            try {
                await axios.put(`${API_BASE}/particulars/${particularId}/assignments/${studentId}`, {
                    sales: parseMoneyInput(amount),
                    deadline: deadline || null
                });

                alert('✅ Assignment updated successfully! Ledger entry created for amount change.');
                closeEditAssignmentForm();
                showAssignStudentsForm(particularId, currentParticularForExisting?.name || 'Particular');
            } catch (error) {
                console.error('Error updating assignment:', error);
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        async function deleteAssignment(particularId, studentId, studentName) {
            if (!confirm(`⚠️ Are you sure you want to remove this particular from ${studentName}?`)) {
                return;
            }

            try {
                await axios.delete(`${API_BASE}/particulars/${particularId}/assignments/${studentId}`);
                alert('✅ Assignment deleted successfully!');
                showAssignStudentsForm(particularId, currentParticularForExisting?.name || 'Particular');
            } catch (error) {
                console.error('Error deleting assignment:', error);
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        async function loadStudentsByClass(particularId, classId, className) {
            if (!classId) {
                document.getElementById('studentsListContainer').innerHTML = '';
                document.getElementById('assignActionsContainer').innerHTML = '';
                return;
            }

            // Show loading message
            document.getElementById('studentsListContainer').innerHTML = '<p class="text-center text-blue-600 p-4">⏳ Loading students...</p>';

            // Load existing assignments for this particular
            let existingAssignments = {};
            try {
                const particularResponse = await axios.get(`${API_BASE}/particulars/${particularId}`);
                const particularData = particularResponse.data;
                if (particularData.students) {
                    particularData.students.forEach(student => {
                        existingAssignments[student.id] = {
                            sales: student.pivot.sales || 0,
                            deadline: student.pivot.deadline || '',
                            isAssigned: true
                        };
                    });
                }
            } catch (error) {
                console.error('Error loading existing assignments:', error);
            }

            const classStudents = allStudents.filter(s => s.class_id == classId);

            if (classStudents.length === 0) {
                document.getElementById('studentsListContainer').innerHTML = '<p class="text-center text-gray-500 p-4">No students found in this class.</p>';
                return;
            }

            let html = `
                <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-bold text-lg">Students in ${className}</h4>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                                class="w-5 h-5 rounded border-gray-300">
                            <span class="font-bold text-sm">Select All</span>
                        </label>
                    </div>
                    <div class="space-y-2 max-h-96 overflow-y-auto" id="studentsList">
            `;

            classStudents.forEach(student => {
                const isAssigned = existingAssignments[student.id]?.isAssigned || false;
                const salesAmount = existingAssignments[student.id]?.sales || 0;
                const checkedAttr = isAssigned ? 'checked' : '';
                const bgClass = isAssigned ? 'bg-green-50 border-green-400' : 'bg-white';
                const deadlineValue = existingAssignments[student.id]?.deadline || '';
                const checkmark = isAssigned ? '✅ ' : '';

                html += `
                    <div class="flex items-center gap-2 p-3 ${bgClass} rounded border hover:border-green-500">
                        <input type="checkbox" class="student-checkbox w-5 h-5 rounded border-gray-300"
                            data-student-id="${student.id}" ${checkedAttr}>
                        <div class="flex-1">
                            <p class="font-bold">${checkmark}${student.name}</p>
                            <p class="text-xs text-gray-500">${student.student_reg_no}</p>
                        </div>
                        <div class="flex gap-2 items-center">
                            <span class="text-xs font-bold">Sales:</span>
                            <input type="text" value="${salesAmount}"
                                class="money-input sales-amount w-28 border-2 border-gray-300 rounded px-2 py-1 text-sm"
                                data-student-id="${student.id}" placeholder="TSh">
                        </div>
                        <div class="flex gap-2 items-center">
                            <span class="text-xs font-bold">Deadline:</span>
                            <input type="date" value="${deadlineValue}"
                                class="deadline-date w-36 border-2 border-gray-300 rounded px-2 py-1 text-sm"
                                data-student-id="${student.id}">
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;

            document.getElementById('studentsListContainer').innerHTML = html;

            // Show action buttons
            const actionsHtml = `
                <div class="bg-blue-50 p-4 rounded-lg border-2 border-blue-300 space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-2">
                            <label class="font-bold text-sm">Bulk Sales Amount:</label>
                            <input type="text" id="bulkSalesAmount"
                                class="money-input border-2 border-gray-300 rounded px-3 py-2 w-32"
                                placeholder="TSh 0.00">
                            <button onclick="applyBulkSales()"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded transition text-sm">
                                Apply
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="font-bold text-sm">Bulk Deadline:</label>
                            <input type="date" id="bulkDeadlineDate"
                                class="border-2 border-gray-300 rounded px-3 py-2 w-36">
                            <button onclick="applyBulkDeadlineForCheckboxForm()"
                                class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 rounded transition text-sm">
                                Apply
                            </button>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="assignSelectedStudents(${particularId})"
                            class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-bold transition">
                            💾 Save & Exit
                        </button>
                        <button onclick="assignAndSelectAnother(${particularId})"
                            class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-bold transition">
                            💾 Save & Select Another Class
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('assignActionsContainer').innerHTML = actionsHtml;
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll').checked;
            document.querySelectorAll('.student-checkbox').forEach(cb => {
                cb.checked = selectAll;
            });
        }

        function applyBulkSales() {
            const bulkAmount = document.getElementById('bulkSalesAmount').value;
            if (!bulkAmount) {
                alert('⚠️ Please enter a sales amount');
                return;
            }

            document.querySelectorAll('.student-checkbox:checked').forEach(cb => {
                const studentId = cb.getAttribute('data-student-id');
                const input = document.querySelector(`.sales-amount[data-student-id="${studentId}"]`);
                if (input) {
                    input.value = bulkAmount;
                }
            });
            alert('✅ Sales amount applied to selected students');
        }

        /** Bulk deadline on the older checkbox list form (uses #bulkDeadlineDate, .deadline-date) */
        function applyBulkDeadlineForCheckboxForm() {
            const bulkDeadline = document.getElementById('bulkDeadlineDate')?.value;
            if (!bulkDeadline) {
                alert('⚠️ Please select a deadline date');
                return;
            }

            document.querySelectorAll('.student-checkbox:checked').forEach(cb => {
                const studentId = cb.getAttribute('data-student-id');
                const input = document.querySelector(`.deadline-date[data-student-id="${studentId}"]`);
                if (input) {
                    input.value = bulkDeadline;
                }
            });
            alert('✅ Deadline applied to selected students');
        }

        async function assignSelectedStudents(particularId, selectAnother = false) {
            const selectedStudents = [];
            document.querySelectorAll('.student-checkbox:checked').forEach(cb => {
                const studentId = cb.getAttribute('data-student-id');
                const salesAmount = document.querySelector(`.sales-amount[data-student-id="${studentId}"]`).value || 0;
                const deadline = document.querySelector(`.deadline-date[data-student-id="${studentId}"]`).value || null;

                selectedStudents.push({
                    student_id: parseInt(studentId),
                    sales: parseMoneyInput(salesAmount),
                    debit: 0,
                    credit: 0,
                    deadline: deadline
                });
            });

            if (selectedStudents.length === 0) {
                alert('⚠️ Please select at least one student');
                return;
            }

            try {
                await axios.post(`${API_BASE}/particulars/${particularId}/assign-students`, {
                    students: selectedStudents
                });
                alert(`✅ ${selectedStudents.length} student(s) assigned successfully!`);

                if (selectAnother) {
                    // Clear selections and reset form
                    document.getElementById('studentsListContainer').innerHTML = '';
                    document.getElementById('assignActionsContainer').innerHTML = '';
                } else {
                    closeAssignForm();
                    loadParticulars();
                }
            } catch (error) {
                alert('❌ Error: ' + (error.response?.data?.message || error.message));
            }
        }

        function assignAndSelectAnother(particularId) {
            assignSelectedStudents(particularId, true);
        }

        function closeAssignForm() {
            document.getElementById('particularFormContainer').innerHTML = '';
            loadParticulars();
        }

        // ===== EXISTING ASSIGNMENTS MANAGEMENT =====
        let currentParticularForExisting = null;
        let currentClassForExisting = null;

        async function showExistingAssignmentsForm(particularId, particularName) {
            currentParticularForExisting = { id: particularId, name: particularName };
            currentClassForExisting = null;

            // Always reload classes fresh
            if (allClasses.length === 0) {
                try {
                    const classesResponse = await axios.get(`${API_BASE}/classes`);
                    allClasses = classesResponse.data;
                } catch (error) {
                    console.error('Error loading classes:', error);
                }
            }

            const classButtons = allClasses.map(cls =>
                `<button type="button" onclick="loadExistingAssignmentsByClass(${particularId}, ${cls.id}, '${cls.name}')"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-bold transition transform hover:scale-105">
                    ${cls.name}
                </button>`
            ).join('');

            const formHtml = `
                <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
                    <div class="bg-white rounded-lg p-8 max-w-6xl w-full shadow-2xl m-4 max-h-[90vh] overflow-y-auto">
                        <h3 class="text-2xl font-bold mb-4 text-blue-600">📋 Existing Assignments for: ${particularName}</h3>

                        <div class="mb-6 p-6 bg-blue-50 rounded-lg border-2 border-blue-300">
                            <label class="block text-lg font-bold mb-4 text-center">📚 Select Class to View Assignments:</label>
                            <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                ${classButtons}
                            </div>
                        </div>

                        <div id="existingAssignmentsContainer" class="mb-6"></div>

                        <div class="flex gap-3 pt-4 border-t-2">
                            <button type="button" onclick="closeExistingAssignmentsForm()"
                                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
                                ✖️ Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('particularFormContainer').innerHTML = formHtml;
        }

        async function loadExistingAssignmentsByClass(particularId, classId, className) {
            if (!classId) {
                document.getElementById('existingAssignmentsContainer').innerHTML = '';
                return;
            }

            // Store current class for reload purposes
            currentClassForExisting = { id: classId, name: className };

            // Show loading message
            document.getElementById('existingAssignmentsContainer').innerHTML = '<p class="text-center text-blue-600 p-4">⏳ Loading assignments...</p>';

            try {
                const response = await axios.get(`${API_BASE}/particulars/${particularId}/existing-assignments?class_id=${classId}`);
                const data = response.data;

                if (!data.assignments || data.assignments.length === 0) {
                    document.getElementById('existingAssignmentsContainer').innerHTML = '<p class="text-center text-gray-500 p-4">No assignments found for this class.</p>';
                    return;
                }

                let html = `
                    <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
                        <h4 class="font-bold text-lg mb-4">Students in ${className} with Assignments</h4>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                `;

                data.assignments.forEach(assignment => {
                    const deadlineDisplay = assignment.deadline ? new Date(assignment.deadline).toLocaleDateString() : 'No deadline';

                    html += `
                        <div class="flex items-center gap-3 p-4 bg-white rounded border-2 border-gray-300 hover:border-blue-500">
                            <div class="flex-1">
                                <p class="font-bold">${assignment.student_name}</p>
                                <p class="text-xs text-gray-500">${assignment.student_reg_no}</p>
                            </div>
                            <div class="flex gap-2 items-center">
                                <div>
                                    <p class="text-xs font-bold text-gray-600">Amount:</p>
                                    <p class="font-bold text-lg text-green-600">TSh ${parseFloat(assignment.sales).toLocaleString()}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 items-center">
                                <div>
                                    <p class="text-xs font-bold text-gray-600">Deadline:</p>
                                    <p class="font-bold text-sm">${deadlineDisplay}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="showEditAssignmentModal(${particularId}, ${assignment.student_id}, '${assignment.student_name}', ${assignment.sales}, '${assignment.deadline || ''}')"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded transition text-sm">
                                    ✏️ Edit
                                </button>
                                <button onclick="deleteAssignment(${particularId}, ${assignment.student_id}, '${assignment.student_name}')"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded transition text-sm">
                                    🗑️ Delete
                                </button>
                            </div>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;

                document.getElementById('existingAssignmentsContainer').innerHTML = html;
            } catch (error) {
                alert('Error loading assignments: ' + error.message);
                document.getElementById('existingAssignmentsContainer').innerHTML = '<p class="text-center text-red-500 p-4">Failed to load assignments.</p>';
            }
        }

        function showEditAssignmentModal(particularId, studentId, studentName, currentSales, currentDeadline) {
            const editModalHtml = `
                <div id="editAssignmentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-2xl m-4">
                        <h3 class="text-xl font-bold mb-4 text-yellow-600">✏️ Edit Assignment</h3>
                        <p class="mb-4"><strong>Student:</strong> ${studentName}</p>

                        <form onsubmit="updateAssignment(event, ${particularId}, ${studentId})" class="space-y-4">
                            <div>
                                <label class="block font-bold mb-2">Amount (TSh) <span class="text-red-500">*</span></label>
                                <input type="text" id="edit_sales" value="${currentSales}" required
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2"
                                    placeholder="TSh 0.00">
                            </div>
                            <div>
                                <label class="block font-bold mb-2">Deadline Date</label>
                                <input type="date" id="edit_deadline" value="${currentDeadline}"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                            </div>
                            <div class="flex gap-3">
                                <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-bold transition">
                                    💾 Save Changes
                                </button>
                                <button type="button" onclick="closeEditAssignmentModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-bold transition">
                                    ✖️ Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            // Append to body
            const modalDiv = document.createElement('div');
            modalDiv.innerHTML = editModalHtml;
            document.body.appendChild(modalDiv);
            applyMoneyFormattingInContainer(modalDiv);
        }

        function closeEditAssignmentModal() {
            const modal = document.getElementById('editAssignmentModal');
            if (modal) {
                modal.parentElement.remove();
            }
        }

        async function updateAssignment(event, particularId, studentId) {
            event.preventDefault();

            const sales = parseMoneyInput(document.getElementById('edit_sales').value);
            const deadline = document.getElementById('edit_deadline').value || null;

            if (!sales || sales <= 0) {
                alert('⚠️ Please enter a valid amount');
                return;
            }

            try {
                await axios.put(`${API_BASE}/particulars/${particularId}/assignments/${studentId}`, {
                    sales: sales,
                    deadline: deadline
                });

                alert('✅ Assignment updated successfully!');
                closeEditAssignmentModal();

                // Reload the existing assignments for the current class
                if (currentParticularForExisting && currentClassForExisting) {
                    await loadExistingAssignmentsByClass(particularId, currentClassForExisting.id, currentClassForExisting.name);
                }
            } catch (error) {
                alert('❌ Error updating assignment: ' + (error.response?.data?.message || error.message));
            }
        }

        async function deleteAssignment(particularId, studentId, studentName) {
            if (!confirm(`Are you sure you want to delete the assignment for ${studentName}? This will remove the assignment and associated Sales voucher.`)) {
                return;
            }

            try {
                await axios.delete(`${API_BASE}/particulars/${particularId}/assignments/${studentId}`);
                alert('✅ Assignment deleted successfully!');

                // Reload the existing assignments for the current class
                if (currentParticularForExisting && currentClassForExisting) {
                    await loadExistingAssignmentsByClass(particularId, currentClassForExisting.id, currentClassForExisting.name);
                }
            } catch (error) {
                alert('❌ Error deleting assignment: ' + (error.response?.data?.message || error.message));
            }
        }

        function closeExistingAssignmentsForm() {
            document.getElementById('particularFormContainer').innerHTML = '';
            currentParticularForExisting = null;
            loadParticulars();
        }
    </script>
@endpush
