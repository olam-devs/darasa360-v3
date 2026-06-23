@extends($portalLayout ?? 'layouts.accountant')

@section('title', 'Student Management — Darasa Finance')
@section('page_title', 'Students')

@section('content')
    <div>
        <div>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-slate-900">Students</h2>
                <div class="flex gap-3">
                    <a href="/api/students/csv/template" download class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Download template
                    </a>
                    <button onclick="showUploadCsvModal()" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Bulk upload CSV
                    </button>
                    <button onclick="showAddStudentModal()" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Add student
                    </button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="text" id="studentSearch" placeholder="Search by name or reg no..." onkeyup="loadStudents()" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <select id="classFilter" onchange="loadStudents()" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Classes</option>
                        <!-- Populated dynamically -->
                    </select>
                    <select id="genderFilter" onchange="loadStudents()" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Genders</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <select id="sortFilter" onchange="loadStudents()" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="recent">Recently Added</option>
                        <option value="oldest">Oldest First</option>
                        <option value="modified">Recently Modified</option>
                    </select>
                </div>
            </div>

            <!-- Students Table -->
            <div id="studentsTable" class="bg-white rounded-lg shadow-md overflow-x-auto"></div>
        </div>
    </div>

    <!-- Add/Edit Student Modal -->
    <div id="studentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <h3 id="studentModalTitle" class="text-2xl font-bold text-blue-600 mb-4">➕ Add Student</h3>
            <form id="studentForm" onsubmit="submitStudentForm(event)">
                <input type="hidden" id="studentId" value="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-2">Registration Number <span class="text-red-500">*</span></label>
                        <input type="text" id="studentRegNo" required placeholder="e.g., STU001" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" id="studentName" required placeholder="e.g., John Doe" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Gender <span class="text-red-500">*</span></label>
                        <select id="studentGender" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Class <span class="text-red-500">*</span></label>
                        <select id="studentClass" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                            <option value="">Select Class</option>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Parent Phone 1</label>
                        <input type="text" id="studentParentPhone1" placeholder="+255..." class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Parent Phone 2</label>
                        <input type="text" id="studentParentPhone2" placeholder="+255..." class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Admission Date</label>
                        <input type="date" id="studentAdmissionDate" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Status</label>
                        <select id="studentStatus" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="graduated">Graduated</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition">
                        ✅ Save Student
                    </button>
                    <button type="button" onclick="closeStudentModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold transition">
                        ❌ Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload CSV Modal -->
    <div id="uploadCsvModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
            <h3 class="text-2xl font-bold text-blue-600 mb-4">📤 Upload Student CSV</h3>
            <form id="uploadCsvForm" onsubmit="submitUploadCsvForm(event)">
                <div class="mb-4">
                    <label class="block font-bold mb-2">Select CSV File <span class="text-red-500">*</span></label>
                    <input type="file" id="csv_file" accept=".csv" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                        <p class="text-sm font-semibold mb-2">Required CSV Format:</p>
                        <p class="text-xs text-gray-700 font-mono">student_reg_no, name, gender, class, parent_phone_1, parent_phone_2, admission_date, status</p>
                        <p class="text-xs text-gray-600 mt-2"><strong>Required fields:</strong> student_reg_no, name, gender, class</p>
                        <p class="text-xs text-gray-600"><strong>Gender values:</strong> Male, Female</p>
                        <p class="text-xs text-gray-600"><strong>Class field:</strong> Must match existing class name</p>
                        <p class="text-xs text-gray-600"><strong>Duplicate handling:</strong> Existing student_reg_no will be skipped</p>
                    </div>
                </div>
                <div id="uploadProgress" class="hidden mb-4">
                    <div class="bg-blue-100 border border-blue-500 rounded-lg p-4">
                        <p class="font-semibold text-blue-800">Processing CSV...</p>
                    </div>
                </div>
                <div id="uploadResults" class="hidden mb-4"></div>
                <div class="flex gap-3">
                    <button type="submit" id="uploadBtn" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition">
                        ✅ Upload CSV
                    </button>
                    <button type="button" onclick="closeUploadCsvModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold transition">
                        ❌ Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Module Scripts -->
@endsection

@push('scripts')
    <script>
const API_BASE = '/api';

        // Configure axios
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        axios.defaults.headers.common['Accept'] = 'application/json';

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadClasses();
            loadStudents();
        });

        async function loadClasses() {
            try {
                const response = await axios.get(`${API_BASE}/school-classes-dropdown`);
                const classes = response.data;

                // Populate class filter
                const classFilter = document.getElementById('classFilter');
                classFilter.innerHTML = '<option value="">All Classes</option>';
                classes.forEach(c => {
                    classFilter.innerHTML += `<option value="${c.id}">${c.name}</option>`;
                });

                // Populate student form class dropdown
                const studentClass = document.getElementById('studentClass');
                studentClass.innerHTML = '<option value="">Select Class</option>';
                classes.forEach(c => {
                    studentClass.innerHTML += `<option value="${c.id}">${c.name}</option>`;
                });
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }

        async function loadStudents() {
            try {
                const search = document.getElementById('studentSearch').value;
                const classId = document.getElementById('classFilter').value;
                const gender = document.getElementById('genderFilter').value;
                const sort = document.getElementById('sortFilter').value;

                const params = new URLSearchParams();
                if (search) params.append('search', search);
                if (classId) params.append('class_id', classId);
                if (gender) params.append('gender', gender);
                if (sort) params.append('sort', sort);

                const response = await axios.get(`${API_BASE}/students?${params.toString()}`);
                renderStudentsTable(response.data.students);
            } catch (error) {
                console.error('Error loading students:', error);
                document.getElementById('studentsTable').innerHTML = `
                    <div class="p-6 text-center text-red-500">
                        <p class="mb-2">❌ Failed to load students</p>
                        <p class="text-sm">${error.message}</p>
                    </div>
                `;
            }
        }

        function renderStudentsTable(students) {
            const tableContainer = document.getElementById('studentsTable');

            if (students.length === 0) {
                tableContainer.innerHTML = `
                    <div class="p-6 text-center text-gray-500">
                        <p class="mb-2">📭 No students found</p>
                        <p class="text-sm">Try adjusting your filters or add new students using the buttons above.</p>
                    </div>
                `;
                return;
            }

            let tableHtml = `
                <div class="p-4">
                    <p class="text-sm text-gray-600 mb-3">Showing ${students.length} student(s)</p>
                    <table class="w-full border-collapse">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="p-3 text-left">Reg No</th>
                                <th class="p-3 text-left">Name</th>
                                <th class="p-3 text-left">Gender</th>
                                <th class="p-3 text-left">Class</th>
                                <th class="p-3 text-left">Parent Phone 1</th>
                                <th class="p-3 text-left">Parent Phone 2</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
            `;

            students.forEach((student, index) => {
                const bgClass = index % 2 === 0 ? 'bg-gray-50' : 'bg-white';
                const statusColor = student.status === 'active' ? 'text-green-600' : student.status === 'inactive' ? 'text-red-600' : 'text-blue-600';

                tableHtml += `
                    <tr class="${bgClass} hover:bg-blue-50 border-b">
                        <td class="p-3 font-mono text-sm">${student.student_reg_no}</td>
                        <td class="p-3 font-semibold">${student.name}</td>
                        <td class="p-3">${student.gender || 'N/A'}</td>
                        <td class="p-3">${student.school_class?.name || 'N/A'}</td>
                        <td class="p-3 text-sm">${student.parent_phone_1 || '-'}</td>
                        <td class="p-3 text-sm">${student.parent_phone_2 || '-'}</td>
                        <td class="p-3 ${statusColor} font-semibold capitalize">${student.status}</td>
                        <td class="p-3 text-center">
                            <button onclick="editStudent(${student.id})" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm mr-2">
                                ✏️ Edit
                            </button>
                            <button onclick="deleteStudent(${student.id}, '${student.name}')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                🗑️ Delete
                            </button>
                        </td>
                    </tr>
                `;
            });

            tableHtml += `
                        </tbody>
                    </table>
                </div>
            `;

            tableContainer.innerHTML = tableHtml;
        }

        function showAddStudentModal() {
            document.getElementById('studentModalTitle').textContent = '➕ Add Student';
            document.getElementById('studentId').value = '';
            document.getElementById('studentForm').reset();
            document.getElementById('studentModal').classList.remove('hidden');
        }

        async function editStudent(studentId) {
            try {
                // Fetch student details (we can get from existing data but better to fetch fresh)
                const response = await axios.get(`/students/${studentId}`);
                // Note: This might return HTML. Let's use the data from the table instead for now
                // Or we need to create an API endpoint for single student

                // For now, let's make a simpler approach - get all students and find the one
                const studentsResponse = await axios.get(`${API_BASE}/students`);
                const student = studentsResponse.data.students.find(s => s.id === studentId);

                if (!student) {
                    alert('Student not found');
                    return;
                }

                // Populate form
                document.getElementById('studentModalTitle').textContent = '✏️ Edit Student';
                document.getElementById('studentId').value = student.id;
                document.getElementById('studentRegNo').value = student.student_reg_no;
                document.getElementById('studentName').value = student.name;
                document.getElementById('studentGender').value = student.gender;
                document.getElementById('studentClass').value = student.class_id;
                document.getElementById('studentParentPhone1').value = student.parent_phone_1 || '';
                document.getElementById('studentParentPhone2').value = student.parent_phone_2 || '';
                document.getElementById('studentAdmissionDate').value = student.admission_date || '';
                document.getElementById('studentStatus').value = student.status;

                document.getElementById('studentModal').classList.remove('hidden');
            } catch (error) {
                console.error('Error loading student:', error);
                alert('❌ Failed to load student details');
            }
        }

        async function deleteStudent(studentId, studentName) {
            if (!confirm(`⚠️ Are you sure you want to delete ${studentName}?\n\nThis will remove their phone numbers but preserve all transaction records.`)) {
                return;
            }

            try {
                await axios.delete(`/students/${studentId}`);
                alert('✅ Student deleted successfully');
                loadStudents();
            } catch (error) {
                const errorMsg = error.response?.data?.message || error.message;
                alert('❌ Failed to delete student: ' + errorMsg);
            }
        }

        function closeStudentModal() {
            document.getElementById('studentModal').classList.add('hidden');
            document.getElementById('studentForm').reset();
        }

        async function submitStudentForm(event) {
            event.preventDefault();

            const studentId = document.getElementById('studentId').value;
            const formData = {
                student_reg_no: document.getElementById('studentRegNo').value,
                name: document.getElementById('studentName').value,
                gender: document.getElementById('studentGender').value,
                class_id: document.getElementById('studentClass').value,
                parent_phone_1: document.getElementById('studentParentPhone1').value,
                parent_phone_2: document.getElementById('studentParentPhone2').value,
                admission_date: document.getElementById('studentAdmissionDate').value || new Date().toISOString().split('T')[0],
                status: document.getElementById('studentStatus').value,
            };

            try {
                if (studentId) {
                    // Update existing student
                    await axios.put(`/students/${studentId}`, formData);
                    alert('✅ Student updated successfully');
                } else {
                    // Create new student
                    await axios.post('/students', formData);
                    alert('✅ Student created successfully');
                }

                closeStudentModal();
                loadStudents();
            } catch (error) {
                const errorMsg = error.response?.data?.message || error.message;
                const errors = error.response?.data?.errors;

                let message = '❌ Error: ' + errorMsg;
                if (errors) {
                    message += '\n\nDetails:\n';
                    Object.entries(errors).forEach(([field, messages]) => {
                        message += `- ${field}: ${messages.join(', ')}\n`;
                    });
                }

                alert(message);
            }
        }

        function showUploadCsvModal() {
            document.getElementById('uploadCsvModal').classList.remove('hidden');
            document.getElementById('uploadResults').classList.add('hidden');
        }

        function closeUploadCsvModal() {
            document.getElementById('uploadCsvModal').classList.add('hidden');
            document.getElementById('uploadCsvForm').reset();
            document.getElementById('uploadResults').classList.add('hidden');
        }

        async function submitUploadCsvForm(event) {
            event.preventDefault();

            const fileInput = document.getElementById('csv_file');
            const file = fileInput.files[0];

            if (!file) {
                alert('⚠️ Please select a CSV file');
                return;
            }

            const formData = new FormData();
            formData.append('csv_file', file);

            // Show progress
            document.getElementById('uploadProgress').classList.remove('hidden');
            document.getElementById('uploadBtn').disabled = true;
            document.getElementById('uploadBtn').textContent = 'Processing...';

            try {
                const response = await axios.post(`${API_BASE}/students/csv/upload`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                const data = response.data;

                // Show results
                let resultsHtml = `
                    <div class="bg-green-100 border-2 border-green-500 rounded-lg p-4">
                        <h4 class="font-bold text-green-800 text-lg mb-2">✅ ${data.message}</h4>
                        <p class="text-green-700">Successfully imported: ${data.success_count} student(s)</p>
                `;

                if (data.skipped && data.skipped.length > 0) {
                    resultsHtml += `
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-400 rounded">
                            <p class="font-semibold text-yellow-800">⚠️ Skipped (${data.skipped.length}):</p>
                            <ul class="text-xs text-yellow-700 mt-2 max-h-32 overflow-y-auto">
                                ${data.skipped.map(msg => `<li>• ${msg}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                if (data.errors && data.errors.length > 0) {
                    resultsHtml += `
                        <div class="mt-3 p-3 bg-red-50 border border-red-400 rounded">
                            <p class="font-semibold text-red-800">❌ Errors (${data.errors.length}):</p>
                            <ul class="text-xs text-red-700 mt-2 max-h-32 overflow-y-auto">
                                ${data.errors.map(msg => `<li>• ${msg}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                resultsHtml += '</div>';

                document.getElementById('uploadResults').innerHTML = resultsHtml;
                document.getElementById('uploadResults').classList.remove('hidden');
                document.getElementById('uploadProgress').classList.add('hidden');

                // Reload student list if any successful imports
                if (data.success_count > 0) {
                    loadStudents();
                }

            } catch (error) {
                document.getElementById('uploadProgress').classList.add('hidden');

                let errorMsg = error.response?.data?.message || error.message;
                let errorDetails = '';

                if (error.response?.data?.errors) {
                    errorDetails = '<ul class="text-xs mt-2">';
                    Object.entries(error.response.data.errors).forEach(([field, messages]) => {
                        errorDetails += `<li>• ${field}: ${messages.join(', ')}</li>`;
                    });
                    errorDetails += '</ul>';
                }

                document.getElementById('uploadResults').innerHTML = `
                    <div class="bg-red-100 border-2 border-red-500 rounded-lg p-4">
                        <h4 class="font-bold text-red-800 text-lg mb-2">❌ Upload Failed</h4>
                        <p class="text-red-700">${errorMsg}</p>
                        ${errorDetails}
                    </div>
                `;
                document.getElementById('uploadResults').classList.remove('hidden');
            } finally {
                document.getElementById('uploadBtn').disabled = false;
                document.getElementById('uploadBtn').textContent = '✅ Upload CSV';
            }
        }
    </script>
@endpush
