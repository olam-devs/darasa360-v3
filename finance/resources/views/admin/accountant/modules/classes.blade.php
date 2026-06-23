@extends('layouts.accountant')

@section('title', 'Classes — Darasa Finance')
@section('page_title', 'Classes')

@section('content')
<!-- Module Content -->
    <div class="w-full p-6">
        <div>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-blue-600">🎓 School Classes</h2>
                <button onclick="showAddClassModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition">
                    ➕ Add Class
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="text" id="classSearch" placeholder="Search classes..." onkeyup="loadClasses()" class="border-2 border-gray-300 rounded-lg px-4 py-2">
                    <select id="levelFilter" onchange="loadClasses()" class="border-2 border-gray-300 rounded-lg px-4 py-2">
                        <option value="">All Levels</option>
                        <option value="pre">Pre</option>
                        <option value="primary">Primary</option>
                        <option value="secondary">Secondary</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    <select id="statusFilter" onchange="loadClasses()" class="border-2 border-gray-300 rounded-lg px-4 py-2">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Classes Table -->
            <div id="classesTable" class="bg-white rounded-lg shadow-md overflow-x-auto"></div>
        </div>
    </div>

    <!-- Add/Edit Class Modal -->
    <div id="classModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <h3 id="modalTitle" class="text-2xl font-bold text-blue-600 mb-4">➕ Add Class</h3>
            <form id="classForm" onsubmit="submitClassForm(event)">
                <input type="hidden" id="classId" value="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block font-bold mb-2">Class Name <span class="text-red-500">*</span></label>
                        <input type="text" id="className" required placeholder="e.g., Form 1, Grade 6" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        <p class="text-xs text-gray-500 mt-1">This will be the display name for the class</p>
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Level</label>
                        <select id="classLevel" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                            <option value="">Not specified</option>
                            <option value="pre">Pre</option>
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Capacity</label>
                        <input type="number" id="classCapacity" min="1" placeholder="Max students (optional)" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-bold mb-2">Description</label>
                        <textarea id="classDescription" rows="2" placeholder="Optional description" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2"></textarea>
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Display Order</label>
                        <input type="number" id="classDisplayOrder" min="0" placeholder="Sort order" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div class="flex items-center">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="classIsActive" checked class="w-5 h-5 mr-2">
                            <span class="font-bold">Active</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition">
                        ✅ Save Class
                    </button>
                    <button type="button" onclick="closeClassModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold transition">
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

        // Load classes on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadClasses();
        });

        async function loadClasses() {
            try {
                const search = document.getElementById('classSearch').value;
                const level = document.getElementById('levelFilter').value;
                const status = document.getElementById('statusFilter').value;

                const params = new URLSearchParams();
                if (search) params.append('search', search);
                if (level) params.append('level', level);
                if (status) params.append('status', status);

                const response = await axios.get(`${API_BASE}/school-classes?${params.toString()}`);
                const data = response.data;
                const classes = Array.isArray(data) ? data : (data.classes?.data || data.classes || []);

                renderClassesTable(classes);
            } catch (error) {
                alert('Error loading classes: ' + error.message);
            }
        }

        function renderClassesTable(classes) {
            let html = `
                <table class="w-full">
                    <thead class="bg-blue-100">
                        <tr>
                            <th class="p-3 text-left">Name</th>
                            <th class="p-3 text-left">Code</th>
                            <th class="p-3 text-left">Level</th>
                            <th class="p-3 text-right">Students</th>
                            <th class="p-3 text-right">Capacity</th>
                            <th class="p-3 text-center">Status</th>
                            <th class="p-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            if (classes.length === 0) {
                html += `
                    <tr>
                        <td colspan="7" class="p-6 text-center text-gray-500">No classes found. Click "Add Class" to create one.</td>
                    </tr>
                `;
            } else {
                classes.forEach(classItem => {
                    const statusBadge = classItem.is_active
                        ? '<span class="px-2 py-1 bg-green-200 text-green-800 rounded text-xs font-bold">Active</span>'
                        : '<span class="px-2 py-1 bg-gray-200 text-gray-800 rounded text-xs font-bold">Inactive</span>';

                    const studentCount = classItem.students_count || classItem.active_students_count || 0;
                    const capacity = classItem.capacity || 'N/A';
                    const capacityDisplay = classItem.capacity ? `${studentCount} / ${capacity}` : studentCount;

                    html += `
                        <tr class="border-t hover:bg-blue-50">
                            <td class="p-3 font-semibold">${classItem.name}</td>
                            <td class="p-3 font-mono text-sm text-gray-600">${classItem.code}</td>
                            <td class="p-3">${classItem.level || 'N/A'}</td>
                            <td class="p-3 text-right font-bold text-blue-700">${capacityDisplay}</td>
                            <td class="p-3 text-right">${capacity}</td>
                            <td class="p-3 text-center">${statusBadge}</td>
                            <td class="p-3 text-center">
                                <button onclick="editClass(${classItem.id})" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm mr-2">
                                    ✏️ Edit
                                </button>
                                <button onclick="deleteClass(${classItem.id}, '${classItem.name}', ${studentCount})" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }

            html += `
                    </tbody>
                </table>
            `;

            document.getElementById('classesTable').innerHTML = html;
        }

        function showAddClassModal() {
            document.getElementById('modalTitle').textContent = '➕ Add Class';
            document.getElementById('classId').value = '';
            document.getElementById('classForm').reset();
            document.getElementById('classIsActive').checked = true;
            document.getElementById('classModal').classList.remove('hidden');
        }

        async function editClass(classId) {
            try {
                const response = await axios.get(`${API_BASE}/school-classes/${classId}`);
                const classData = response.data.class;

                document.getElementById('modalTitle').textContent = '✏️ Edit Class';
                document.getElementById('classId').value = classData.id;
                document.getElementById('className').value = classData.name;
                document.getElementById('classLevel').value = classData.level || '';
                document.getElementById('classCapacity').value = classData.capacity || '';
                document.getElementById('classDescription').value = classData.description || '';
                document.getElementById('classDisplayOrder').value = classData.display_order || 0;
                document.getElementById('classIsActive').checked = classData.is_active;

                document.getElementById('classModal').classList.remove('hidden');
            } catch (error) {
                alert('Error loading class details: ' + error.message);
            }
        }

        function closeClassModal() {
            document.getElementById('classModal').classList.add('hidden');
            document.getElementById('classForm').reset();
        }

        async function submitClassForm(event) {
            event.preventDefault();

            const classId = document.getElementById('classId').value;
            const formData = {
                name: document.getElementById('className').value,
                level: document.getElementById('classLevel').value || null,
                capacity: document.getElementById('classCapacity').value || null,
                description: document.getElementById('classDescription').value || null,
                display_order: parseInt(document.getElementById('classDisplayOrder').value) || 0,
                is_active: document.getElementById('classIsActive').checked,
            };

            try {
                if (classId) {
                    // Update existing class
                    await axios.put(`${API_BASE}/school-classes/${classId}`, formData);
                    alert('✅ Class updated successfully');
                } else {
                    // Create new class
                    await axios.post(`${API_BASE}/school-classes`, formData);
                    alert('✅ Class created successfully');
                }

                closeClassModal();
                loadClasses();
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

        async function deleteClass(classId, className, studentCount) {
            if (studentCount > 0) {
                alert(`⚠️ Cannot delete class "${className}" because it has ${studentCount} student(s) assigned.\n\nPlease reassign or remove students first.`);
                return;
            }

            if (!confirm(`Are you sure you want to delete class "${className}"?`)) {
                return;
            }

            try {
                await axios.delete(`${API_BASE}/school-classes/${classId}`);
                alert('✅ Class deleted successfully');
                loadClasses();
            } catch (error) {
                alert('❌ Error deleting class: ' + (error.response?.data?.message || error.message));
            }
        }
    </script>
@endpush
