@extends('layouts.accountant')

@section('title', 'Suspense — Darasa Finance')
@section('page_title', 'Suspense')

@section('content')
<!-- Module Content -->
    <div class="w-full p-6">
        <div>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-amber-600">⏳ Suspense Accounts</h2>
                <button onclick="showCreateSuspenseForm()" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded transition">
                    ➕ Add Suspense Entry
                </button>
            </div>

            <p class="text-gray-600 mb-6">Suspense accounts temporarily hold unallocated payments until they can be properly assigned.</p>

            <!-- Create Suspense Account Modal -->
            <div id="createSuspenseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <h3 class="text-2xl font-bold text-amber-600 mb-4">➕ Create Suspense Entry</h3>

                    <form id="createSuspenseForm" onsubmit="submitSuspenseForm(event)">
                        <div class="mb-4">
                            <label class="block font-bold mb-2">Date <span class="text-red-500">*</span></label>
                            <input type="date" id="suspense_date" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block font-bold mb-2">Book <span class="text-red-500">*</span></label>
                            <select id="suspense_book" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                                <option value="">Select Book...</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block font-bold mb-2">Amount (TSh) <span class="text-red-500">*</span></label>
                            <input type="number" id="suspense_amount" required step="0.01" min="0" placeholder="0.00" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block font-bold mb-2">Description <span class="text-red-500">*</span></label>
                            <textarea id="suspense_description" required rows="3" placeholder="Describe the unallocated payment..." class="w-full border-2 border-gray-300 rounded-lg px-4 py-2"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block font-bold mb-2">Reference Number</label>
                            <input type="text" id="suspense_reference" placeholder="e.g., REF-12345, TXN-98765" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition">
                                ✅ Create Entry
                            </button>
                            <button type="button" onclick="closeCreateSuspenseModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold transition">
                                ❌ Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <h3 class="text-lg font-bold text-gray-700 mb-3">📅 Filter Suspense Accounts</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block font-bold mb-2 text-sm">From Date</label>
                        <input type="date" id="suspense_filter_from" onchange="loadSuspenseAccounts()"
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block font-bold mb-2 text-sm">To Date</label>
                        <input type="date" id="suspense_filter_to" onchange="loadSuspenseAccounts()"
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block font-bold mb-2 text-sm">Status</label>
                        <select id="suspense_filter_resolved" onchange="loadSuspenseAccounts()"
                                class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">All</option>
                            <option value="false">Unresolved</option>
                            <option value="true">Resolved</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold mb-2 text-sm">Actions</label>
                        <button onclick="clearFilters()" class="w-full bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-lg text-sm transition">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <div id="suspenseContent"></div>
        </div>
    </div>

    <!-- Resolve Suspense Modal (Full Entry Form) -->
    <div id="resolveSuspenseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold text-green-600 mb-4">✅ Resolve Suspense Account - Record Fee Entry</h3>

            <!-- Suspense Info Display -->
            <div class="mb-4 bg-amber-50 border-2 border-amber-300 rounded-lg p-4">
                <div class="grid grid-cols-3 gap-3 mb-3">
                    <div>
                        <p class="text-xs text-amber-600 font-bold">Total Amount</p>
                        <p id="suspense_amount_display" class="text-lg font-bold text-gray-700">TSh 0.00</p>
                    </div>
                    <div>
                        <p class="text-xs text-green-600 font-bold">Already Resolved</p>
                        <p id="suspense_resolved_display" class="text-lg font-bold text-green-600">TSh 0.00</p>
                    </div>
                    <div>
                        <p class="text-xs text-red-600 font-bold">Remaining Balance</p>
                        <p id="suspense_remaining_display" class="text-lg font-bold text-red-600">TSh 0.00</p>
                    </div>
                </div>
                <p class="text-sm text-amber-800 mt-2"><strong>Description:</strong> <span id="suspense_description_display">N/A</span></p>
                <p class="text-sm text-amber-800 mt-1"><strong>Reference:</strong> <span id="suspense_reference_display">N/A</span></p>
            </div>

            <form id="resolveSuspenseForm" onsubmit="submitResolveSuspenseForm(event)" class="space-y-4">
                <input type="hidden" id="resolve_suspense_id">
                <input type="hidden" id="resolve_student_id" required>

                <!-- Step 1: Search Student (Direct Search OR Filter by Class) -->
                <div class="border-2 border-blue-200 rounded-lg p-4 bg-blue-50">
                    <h4 class="font-bold mb-3">1️⃣ Search & Select Student</h4>

                    <!-- Optional Class Filter -->
                    <div class="mb-3">
                        <label class="block text-sm font-bold mb-2">Filter by Class (Optional)</label>
                        <select id="resolve_class" onchange="loadStudentsByClassForResolve()" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                            <option value="">All Classes</option>
                        </select>
                    </div>

                    <!-- Student Selection Dropdown -->
                    <div id="studentSelectSection" class="mb-3" style="display: none;">
                        <label class="block text-sm font-bold mb-2">Select Student from List</label>
                        <select id="resolve_student_select" onchange="selectStudentFromDropdown()" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                            <option value="">-- Select Student --</option>
                        </select>
                    </div>

                    <!-- OR Divider -->
                    <div id="orDivider" class="my-3 text-center text-gray-500 font-bold" style="display: none;">- OR -</div>

                    <!-- Search Bar -->
                    <div class="relative">
                        <label class="block text-sm font-bold mb-2">Search Student</label>
                        <input type="text" id="resolve_student_search"
                               placeholder="Type student name or registration number..."
                               oninput="showAutocomplete()"
                               onfocus="showAutocomplete()"
                               class="w-full border-2 border-blue-300 rounded-lg px-4 py-2 focus:border-blue-500 focus:outline-none">
                        <div id="autocompleteResults" class="hidden absolute z-50 w-full mt-1 bg-white border-2 border-blue-300 rounded-lg shadow-lg max-h-64 overflow-y-auto"></div>
                    </div>

                    <div id="selectedStudentDisplay" class="mt-3 p-3 bg-white rounded border-2 border-green-500 hidden">
                        <p class="text-xs font-bold text-green-600">✓ Selected Student:</p>
                        <p id="resolve_student_name_display" class="font-bold text-lg"></p>
                    </div>
                </div>

                <!-- Step 2: Entry Form (shown after student selection) -->
                <div id="entryFormSection" style="display: none;" class="border-2 border-purple-200 rounded-lg p-4 bg-purple-50">
                    <h4 class="font-bold mb-3">2️⃣ Complete Entry Details</h4>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Date -->
                        <div>
                            <label class="block font-bold mb-2">Date <span class="text-red-500">*</span></label>
                            <input type="date" id="resolve_date" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        </div>

                        <!-- Particular/Fee Type -->
                        <div class="col-span-2">
                            <label class="block font-bold mb-2">Fee Particular <span class="text-red-500">*</span></label>
                            <select id="resolve_particular_id" required onchange="loadParticularDetails()" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                                <option value="">Select Fee Type...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Particular Details (shown when particular is selected) -->
                    <div id="particularDetailsSection" style="display: none;" class="mt-4 p-4 bg-blue-50 border-2 border-blue-200 rounded-lg">
                        <h5 class="font-bold text-blue-800 mb-3">📊 Selected Fee Particular Details for This Student</h5>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-white p-3 rounded border border-blue-300">
                                <p class="text-xs text-gray-600 mb-1">Amount Required</p>
                                <p id="particular_amount_required" class="font-bold text-lg text-blue-600">TSh 0.00</p>
                            </div>
                            <div class="bg-white p-3 rounded border border-green-300">
                                <p class="text-xs text-gray-600 mb-1">Amount Paid</p>
                                <p id="particular_amount_paid" class="font-bold text-lg text-green-600">TSh 0.00</p>
                            </div>
                            <div class="bg-white p-3 rounded border border-red-300">
                                <p class="text-xs text-gray-600 mb-1">Outstanding Balance</p>
                                <p id="particular_outstanding" class="font-bold text-lg text-red-600">TSh 0.00</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <!-- Amount to Resolve -->
                        <div>
                            <label class="block font-bold mb-2">Amount to Resolve (TSh) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" id="resolve_amount" required min="0"
                                   placeholder="0.00"
                                   class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 font-bold text-lg">
                            <p class="text-xs text-gray-600 mt-1">Max suspense amount: <span id="max_suspense_amount" class="font-bold">TSh 0.00</span></p>
                        </div>

                        <!-- Book/Account -->
                        <div>
                            <label class="block font-bold mb-2">Book/Account <span class="text-red-500">*</span></label>
                            <select id="resolve_book_id" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                                <option value="">Select Book...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mt-4">
                        <label class="block font-bold mb-2">Notes</label>
                        <textarea id="resolve_notes" rows="3"
                                  class="w-full border-2 border-gray-300 rounded-lg px-4 py-2"
                                  placeholder="Add any notes about this payment..."></textarea>
                    </div>
                </div>

                <div class="flex gap-3 pt-4 border-t-2">
                    <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition">
                        ✅ Resolve & Record Entry
                    </button>
                    <button type="button" onclick="closeResolveSuspenseModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold transition">
                        ❌ Cancel
                    </button>
                </div>
            </form>
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
            return 'TSh ' + parseFloat(amount).toLocaleString('en-TZ', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        let books = [];
        let students = [];
        let particulars = [];
        let currentSuspenseAccount = null;

        // Load suspense accounts on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSuspenseAccounts();
            loadBooks();
            loadStudents();
            loadParticulars();
        });

        async function loadBooks() {
            try {
                const response = await axios.get(`${API_BASE}/books`);
                books = response.data.books || response.data;
            } catch (error) {
                console.error('Error loading books:', error);
            }
        }

        async function loadStudents() {
            try {
                const response = await axios.get(`${API_BASE}/students`);
                students = response.data.students || response.data;
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }

        async function loadParticulars() {
            try {
                const response = await axios.get(`${API_BASE}/particulars`);
                particulars = response.data.particulars || response.data;
            } catch (error) {
                console.error('Error loading particulars:', error);
            }
        }

        async function loadSuspenseAccounts() {
            try {
                // Build query string with filters
                const fromDate = document.getElementById('suspense_filter_from')?.value || '';
                const toDate = document.getElementById('suspense_filter_to')?.value || '';
                const resolved = document.getElementById('suspense_filter_resolved')?.value || '';

                let url = `${API_BASE}/suspense-accounts?`;
                if (fromDate) url += `from_date=${fromDate}&`;
                if (toDate) url += `to_date=${toDate}&`;
                if (resolved !== '') url += `resolved=${resolved}&`;

                const response = await axios.get(url);
                const data = response.data;
                const accounts = data.suspense_accounts.data || data.suspense_accounts || [];

                let html = `
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-amber-50 border-2 border-amber-300 rounded-lg p-4">
                            <h4 class="text-sm font-bold text-amber-700 mb-2">Total Unresolved Amount</h4>
                            <p class="text-3xl font-bold text-amber-600">${formatTSh(data.summary?.total_unresolved || 0)}</p>
                        </div>
                        <div class="bg-orange-50 border-2 border-orange-300 rounded-lg p-4">
                            <h4 class="text-sm font-bold text-orange-700 mb-2">Total Resolved</h4>
                            <p class="text-3xl font-bold text-green-600">${formatTSh(data.summary?.total_resolved || 0)}</p>
                        </div>
                    </div>

                    <!-- Suspense Entries Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full border-2 border-gray-300 rounded-lg">
                            <thead class="bg-amber-100">
                                <tr>
                                    <th class="p-3 text-left">Date</th>
                                    <th class="p-3 text-left">Book</th>
                                    <th class="p-3 text-left">Reference</th>
                                    <th class="p-3 text-left">Description</th>
                                    <th class="p-3 text-right">Total Amount</th>
                                    <th class="p-3 text-right">Resolved</th>
                                    <th class="p-3 text-right">Remaining</th>
                                    <th class="p-3 text-left">Status</th>
                                    <th class="p-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                if (accounts.length === 0) {
                    html += `
                        <tr>
                            <td colspan="9" class="p-6 text-center text-gray-500">No suspense accounts found. Click "Add Suspense Entry" to create one.</td>
                        </tr>
                    `;
                } else {
                    accounts.forEach(account => {
                        const resolvedAmount = parseFloat(account.resolved_amount || 0);
                        const totalAmount = parseFloat(account.amount);
                        const remainingAmount = totalAmount - resolvedAmount;

                        let statusBadge = '';
                        if (account.resolved || remainingAmount <= 0) {
                            statusBadge = '<span class="px-2 py-1 bg-green-200 text-green-800 rounded text-xs font-bold">Fully Resolved</span>';
                        } else if (resolvedAmount > 0) {
                            statusBadge = '<span class="px-2 py-1 bg-orange-200 text-orange-800 rounded text-xs font-bold">Partially Resolved</span>';
                        } else {
                            statusBadge = '<span class="px-2 py-1 bg-yellow-200 text-yellow-800 rounded text-xs font-bold">Unresolved</span>';
                        }

                        const bookName = account.book ? account.book.name : 'N/A';
                        const canResolve = remainingAmount > 0.01; // Allow small rounding errors

                        html += `
                            <tr class="border-t hover:bg-amber-50">
                                <td class="p-3">${new Date(account.date).toLocaleDateString()}</td>
                                <td class="p-3 font-semibold text-blue-600">${bookName}</td>
                                <td class="p-3 font-mono text-sm">${account.reference_number || 'N/A'}</td>
                                <td class="p-3">${account.description || 'N/A'}</td>
                                <td class="p-3 text-right font-bold text-gray-700">${formatTSh(totalAmount)}</td>
                                <td class="p-3 text-right font-bold text-green-600">${formatTSh(resolvedAmount)}</td>
                                <td class="p-3 text-right font-bold text-amber-700">${formatTSh(remainingAmount)}</td>
                                <td class="p-3">${statusBadge}</td>
                                <td class="p-3">
                                    ${canResolve ? `
                                        <button onclick='showResolveSuspenseModal(${JSON.stringify(account).replace(/'/g, "&apos;")})' class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs transition">
                                            Resolve
                                        </button>
                                    ` : '<span class="text-gray-400 text-xs">Completed</span>'}
                                </td>
                            </tr>
                        `;
                    });
                }

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                document.getElementById('suspenseContent').innerHTML = html;
            } catch (error) {
                alert('Error loading suspense accounts: ' + error.message);
            }
        }

        function showCreateSuspenseForm() {
            document.getElementById('createSuspenseModal').classList.remove('hidden');
            // Set today's date as default
            document.getElementById('suspense_date').valueAsDate = new Date();

            // Populate books dropdown
            const bookSelect = document.getElementById('suspense_book');
            bookSelect.innerHTML = '<option value="">Select Book...</option>';
            books.forEach(book => {
                bookSelect.innerHTML += `<option value="${book.id}">${book.name}</option>`;
            });
        }

        function closeCreateSuspenseModal() {
            document.getElementById('createSuspenseModal').classList.add('hidden');
            document.getElementById('createSuspenseForm').reset();
        }

        function clearFilters() {
            document.getElementById('suspense_filter_from').value = '';
            document.getElementById('suspense_filter_to').value = '';
            document.getElementById('suspense_filter_resolved').value = '';
            loadSuspenseAccounts();
        }

        async function submitSuspenseForm(event) {
            event.preventDefault();

            const bookId = document.getElementById('suspense_book').value;
            if (!bookId) {
                alert('⚠️ Please select a book');
                return;
            }

            const formData = {
                date: document.getElementById('suspense_date').value,
                book_id: parseInt(bookId),
                reference_number: document.getElementById('suspense_reference').value,
                amount: parseFloat(document.getElementById('suspense_amount').value),
                description: document.getElementById('suspense_description').value
            };

            try {
                await axios.post(`${API_BASE}/suspense-accounts`, formData);
                alert('✅ Suspense entry created successfully');
                closeCreateSuspenseModal();
                loadSuspenseAccounts(); // Reload the list
            } catch (error) {
                alert('❌ Error creating suspense entry: ' + (error.response?.data?.message || error.message));
            }
        }

        async function showResolveSuspenseModal(account) {
            currentSuspenseAccount = account;

            // Calculate remaining balance
            const resolvedAmount = parseFloat(account.resolved_amount || 0);
            const totalAmount = parseFloat(account.amount);
            const remainingBalance = totalAmount - resolvedAmount;

            // Check if there's anything left to resolve
            if (remainingBalance <= 0.01) {
                alert('⚠️ This suspense account is fully resolved. No remaining balance to allocate.');
                return;
            }

            // Set hidden field and suspense data
            document.getElementById('resolve_suspense_id').value = account.id;
            document.getElementById('suspense_amount_display').textContent = formatTSh(totalAmount);
            document.getElementById('suspense_resolved_display').textContent = formatTSh(resolvedAmount);
            document.getElementById('suspense_remaining_display').textContent = formatTSh(remainingBalance);
            document.getElementById('suspense_description_display').textContent = account.description || 'N/A';
            document.getElementById('suspense_reference_display').textContent = account.reference_number || 'N/A';

            // Set max suspense amount to remaining balance
            document.getElementById('max_suspense_amount').textContent = formatTSh(remainingBalance);
            document.getElementById('resolve_amount').setAttribute('max', remainingBalance);
            document.getElementById('resolve_amount').value = remainingBalance; // Default to remaining amount

            // Set today's date
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('resolve_date').value = today;

            // Populate book dropdown
            const bookSelect = document.getElementById('resolve_book_id');
            bookSelect.innerHTML = '<option value="">Select Book...</option>';
            books.forEach(book => {
                if (book.is_active) {
                    const selected = book.id === account.book_id ? 'selected' : '';
                    bookSelect.innerHTML += `<option value="${book.id}" ${selected}>${book.name}</option>`;
                }
            });

            // Load classes for dropdown
            await loadClassesForResolve();

            // Load ALL students initially so search works from the start
            await loadAllStudentsForResolve();

            // Reset form visibility
            document.getElementById('entryFormSection').style.display = 'none';
            document.getElementById('particularDetailsSection').style.display = 'none';
            document.getElementById('studentSelectSection').style.display = 'none';
            document.getElementById('orDivider').style.display = 'none';

            // Show modal
            document.getElementById('resolveSuspenseModal').classList.remove('hidden');
        }

        async function loadClassesForResolve() {
            try {
                const response = await axios.get(`${API_BASE}/classes`);
                const classSelect = document.getElementById('resolve_class');
                classSelect.innerHTML = '<option value="">Select Class...</option>';
                const classes = response.data.classes || response.data;
                classes.forEach(classItem => {
                    // Handle both string array and object array
                    const className = typeof classItem === 'string' ? classItem : (classItem.name || classItem);
                    classSelect.innerHTML += `<option value="${className}">${className}</option>`;
                });
            } catch (error) {
                console.error('Failed to load classes:', error);
            }
        }

        let studentsForResolve = [];
        let allStudents = [];
        let selectedParticularName = '';

        async function loadAllStudentsForResolve() {
            try {
                const response = await axios.get(`${API_BASE}/students`);
                allStudents = response.data.students || response.data;
                studentsForResolve = allStudents; // Initially show all students
            } catch (error) {
                console.error('Failed to load all students:', error);
            }
        }

        async function loadStudentsByClassForResolve() {
            const className = document.getElementById('resolve_class').value;

            if (!className || className === '') {
                // Show all students if "All Classes" is selected
                studentsForResolve = allStudents;
                // Hide the student select dropdown
                document.getElementById('studentSelectSection').style.display = 'none';
                document.getElementById('orDivider').style.display = 'none';
            } else {
                // Filter students by selected class
                studentsForResolve = allStudents.filter(student => student.class === className);

                // Populate the student dropdown
                const studentSelect = document.getElementById('resolve_student_select');
                studentSelect.innerHTML = '<option value="">-- Select Student --</option>';

                studentsForResolve.forEach(student => {
                    studentSelect.innerHTML += `<option value="${student.id}" data-name="${student.name}" data-regno="${student.student_reg_no}">${student.name} (${student.student_reg_no})</option>`;
                });

                // Show the student select dropdown and OR divider
                document.getElementById('studentSelectSection').style.display = 'block';
                document.getElementById('orDivider').style.display = 'block';
            }

            // Clear search and selections when class changes
            document.getElementById('resolve_student_search').value = '';
            document.getElementById('resolve_student_id').value = '';
            document.getElementById('resolve_student_select').value = '';
            document.getElementById('autocompleteResults').classList.add('hidden');
            document.getElementById('selectedStudentDisplay').classList.add('hidden');
            document.getElementById('entryFormSection').style.display = 'none';
            document.getElementById('particularDetailsSection').style.display = 'none';
        }

        function selectStudentFromDropdown() {
            const select = document.getElementById('resolve_student_select');
            const selectedOption = select.options[select.selectedIndex];

            if (!select.value) {
                return;
            }

            const studentId = select.value;
            const studentName = selectedOption.getAttribute('data-name');
            const regNo = selectedOption.getAttribute('data-regno');

            // Clear search bar
            document.getElementById('resolve_student_search').value = '';
            document.getElementById('autocompleteResults').classList.add('hidden');

            // Call the same function as autocomplete selection
            selectStudent(studentId, studentName, regNo);
        }

        function showAutocomplete() {
            const searchValue = document.getElementById('resolve_student_search').value.toLowerCase();
            const resultsDiv = document.getElementById('autocompleteResults');

            // Clear dropdown selection when user starts typing in search
            if (searchValue.length > 0) {
                document.getElementById('resolve_student_select').value = '';
            }

            if (searchValue.length === 0) {
                resultsDiv.classList.add('hidden');
                return;
            }

            // Filter students
            const matchedStudents = studentsForResolve.filter(student => {
                return student.name.toLowerCase().includes(searchValue) ||
                       student.student_reg_no.toLowerCase().includes(searchValue);
            });

            if (matchedStudents.length === 0) {
                resultsDiv.innerHTML = '<div class="p-3 text-gray-500 text-center">No students found</div>';
                resultsDiv.classList.remove('hidden');
                return;
            }

            // Build autocomplete results
            let html = '';
            matchedStudents.forEach(student => {
                html += `
                    <div onclick="selectStudent(${student.id}, '${student.name.replace(/'/g, "\\'")}', '${student.student_reg_no}')"
                         class="p-3 hover:bg-blue-100 cursor-pointer border-b border-gray-200 last:border-0">
                        <div class="font-semibold text-gray-800">${student.name}</div>
                        <div class="text-sm text-gray-600">${student.student_reg_no}</div>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('hidden');
        }

        function selectStudent(studentId, studentName, regNo) {
            document.getElementById('resolve_student_id').value = studentId;
            document.getElementById('resolve_student_search').value = `${studentName} (${regNo})`;
            document.getElementById('autocompleteResults').classList.add('hidden');

            // Show selected student display
            document.getElementById('selectedStudentDisplay').classList.remove('hidden');
            document.getElementById('resolve_student_name_display').textContent = `${studentName} (${regNo})`;

            // Show entry form section
            document.getElementById('entryFormSection').style.display = 'block';

            // Load particulars assigned to this student
            loadParticularsForStudent(studentId);
        }

        // Close autocomplete when clicking outside
        document.addEventListener('click', function(event) {
            const searchInput = document.getElementById('resolve_student_search');
            const resultsDiv = document.getElementById('autocompleteResults');
            if (searchInput && resultsDiv && !searchInput.contains(event.target) && !resultsDiv.contains(event.target)) {
                resultsDiv.classList.add('hidden');
            }
        });

        async function loadParticularsForStudent(studentId) {
            try {
                const response = await axios.get(`${API_BASE}/students/${studentId}/particulars`);
                const particularSelect = document.getElementById('resolve_particular_id');
                particularSelect.innerHTML = '<option value="">Select Fee Type...</option>';

                const particularsData = response.data.particulars || response.data;
                if (particularsData.length === 0) {
                    particularSelect.innerHTML += '<option value="" disabled>No particulars assigned to this student</option>';
                } else {
                    particularsData.forEach(particular => {
                        particularSelect.innerHTML += `<option value="${particular.id}">${particular.name}</option>`;
                    });
                }
            } catch (error) {
                console.error('Failed to load particulars for student:', error);
                // Fallback to loading all active particulars
                await loadParticularsForResolve();
            }
        }

        async function loadParticularsForResolve() {
            try {
                const response = await axios.get(`${API_BASE}/particulars`);
                const particularSelect = document.getElementById('resolve_particular_id');
                particularSelect.innerHTML = '<option value="">Select Fee Type...</option>';

                const particularsData = response.data.particulars || response.data;
                particularsData.forEach(particular => {
                    if (particular.is_active) {
                        particularSelect.innerHTML += `<option value="${particular.id}">${particular.name}</option>`;
                    }
                });
            } catch (error) {
                console.error('Failed to load particulars:', error);
            }
        }

        async function loadParticularDetails() {
            const studentId = document.getElementById('resolve_student_id').value;
            const particularId = document.getElementById('resolve_particular_id').value;
            const particularSelect = document.getElementById('resolve_particular_id');

            // Get the selected particular name
            const selectedOption = particularSelect.options[particularSelect.selectedIndex];
            selectedParticularName = selectedOption ? selectedOption.text : '';

            console.log('Loading particular details:', { studentId, particularId, particularName: selectedParticularName });

            if (!studentId || !particularId) {
                console.log('Missing student or particular ID');
                document.getElementById('particularDetailsSection').style.display = 'none';
                return;
            }

            try {
                const url = `${API_BASE}/students/${studentId}/particulars/${particularId}/details`;
                console.log('Fetching from:', url);
                const response = await axios.get(url);
                const data = response.data;
                console.log('Particular details received:', data);

                // Display particular details
                document.getElementById('particular_amount_required').textContent = formatTSh(data.debit || 0);
                document.getElementById('particular_amount_paid').textContent = formatTSh(data.credit || 0);
                document.getElementById('particular_outstanding').textContent = formatTSh(data.balance || 0);

                // Auto-populate notes field
                const notesField = document.getElementById('resolve_notes');
                if (notesField && selectedParticularName) {
                    notesField.value = `Payment for ${selectedParticularName}`;
                }

                // Show the details section
                document.getElementById('particularDetailsSection').style.display = 'block';
            } catch (error) {
                console.error('Failed to load particular details:', error);
                console.error('Error response:', error.response?.data);
                // Hide details section if error
                document.getElementById('particularDetailsSection').style.display = 'none';
            }
        }

        function closeResolveSuspenseModal() {
            document.getElementById('resolveSuspenseModal').classList.add('hidden');
            document.getElementById('resolveSuspenseForm').reset();
            document.getElementById('entryFormSection').style.display = 'none';
            document.getElementById('selectedStudentDisplay').classList.add('hidden');
            document.getElementById('autocompleteResults').classList.add('hidden');
            document.getElementById('particularDetailsSection').style.display = 'none';
            document.getElementById('studentSelectSection').style.display = 'none';
            document.getElementById('orDivider').style.display = 'none';
            currentSuspenseAccount = null;
            studentsForResolve = [];
            allStudents = [];
        }

        async function submitResolveSuspenseForm(event) {
            event.preventDefault();

            const suspenseId = document.getElementById('resolve_suspense_id').value;
            const studentId = document.getElementById('resolve_student_id').value;
            const particularId = document.getElementById('resolve_particular_id').value;
            const amount = parseFloat(document.getElementById('resolve_amount').value);
            const bookId = document.getElementById('resolve_book_id').value;
            const date = document.getElementById('resolve_date').value;
            const notes = document.getElementById('resolve_notes').value;

            console.log('Submitting resolve form:', {
                suspenseId, studentId, particularId, amount, bookId, date, notes
            });

            if (!studentId || !particularId) {
                alert('⚠️ Please select both student and fee particular');
                return;
            }

            if (!amount || amount <= 0) {
                alert('⚠️ Please enter a valid amount');
                return;
            }

            // Validate amount doesn't exceed suspense amount
            const maxAmount = parseFloat(document.getElementById('resolve_amount').getAttribute('max'));
            if (amount > maxAmount) {
                alert(`⚠️ Amount cannot exceed suspense amount of ${formatTSh(maxAmount)}`);
                return;
            }

            const payload = {
                student_id: parseInt(studentId),
                particular_id: parseInt(particularId),
                amount_to_resolve: amount,
                book_id: parseInt(bookId),
                date: date,
                notes: notes
            };

            console.log('Payload being sent:', payload);

            try {
                const response = await axios.post(`${API_BASE}/suspense-accounts/${suspenseId}/resolve`, payload);
                console.log('Resolve response:', response.data);

                // Show success message with details
                const data = response.data;
                let message = data.message || '✅ Suspense account resolved successfully';

                if (data.remaining_amount && data.remaining_amount > 0) {
                    message += `\n\n💰 Remaining to resolve: ${formatTSh(data.remaining_amount)}`;
                }

                alert(message);
                closeResolveSuspenseModal();
                loadSuspenseAccounts(); // Reload the list
            } catch (error) {
                console.error('Resolve error:', error);
                console.error('Error response:', error.response?.data);
                console.error('Error details:', error.response?.data?.error);

                let errorMessage = 'Unknown error';
                if (error.response?.data) {
                    const data = error.response.data;
                    if (data.errors) {
                        // Validation errors
                        errorMessage = Object.values(data.errors).flat().join(', ');
                    } else if (data.error) {
                        errorMessage = data.error;
                        if (data.file && data.line) {
                            errorMessage += ` (${data.file}:${data.line})`;
                        }
                    } else if (data.message) {
                        errorMessage = data.message;
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }

                alert('❌ Error resolving suspense account: ' + errorMessage);
            }
        }
    </script>
@endpush
