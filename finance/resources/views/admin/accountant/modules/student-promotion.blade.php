@extends('layouts.accountant')

@section('title', 'Student promotion — Darasa Finance')
@section('page_title', 'Student promotion')

@section('content')
<div class="w-full p-6">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-3xl font-bold text-blue-600 mb-6">📚 Promote Students to Next Class</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Step 1: Select Source Class -->
                <div class="border-2 border-blue-300 rounded-lg p-4">
                    <h3 class="text-xl font-bold text-blue-600 mb-3">1️⃣ Select Source Class</h3>
                    <select id="sourceClass"
                            onchange="loadStudentsForPromotion()"
                            class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        <option value="">Select Class...</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }} ({{ $class->active_students_count }} students)</option>
                        @endforeach
                    </select>
                    <p class="text-sm text-gray-600 mt-2">Choose the class you want to promote students from</p>
                </div>

                <!-- Step 2: Select Students -->
                <div class="border-2 border-green-300 rounded-lg p-4">
                    <h3 class="text-xl font-bold text-green-600 mb-3">2️⃣ Select Students</h3>
                    <div class="mb-2">
                        <input type="text" id="studentSearch"
                               placeholder="Search students..."
                               onkeyup="filterStudentList()"
                               class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 mb-2">
                    </div>
                    <div class="mb-2 flex gap-2">
                        <button onclick="selectAllStudents()"
                                class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                            ✅ Select All
                        </button>
                        <button onclick="deselectAllStudents()"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                            ❌ Deselect All
                        </button>
                    </div>
                    <div id="studentList" class="max-h-96 overflow-y-auto border-2 border-gray-200 rounded-lg p-2">
                        <p class="text-gray-500 text-center py-4">Select a source class to load students</p>
                    </div>
                </div>

                <!-- Step 3: Select Destination Class -->
                <div class="border-2 border-purple-300 rounded-lg p-4">
                    <h3 class="text-xl font-bold text-purple-600 mb-3">3️⃣ Destination Class</h3>
                    <select id="destinationClassSelect"
                            onchange="document.getElementById('destinationClassId').value = this.value; updatePromoteButton()"
                            class="w-full border-2 border-gray-300 rounded-lg px-4 py-2 mb-4">
                        <option value="">Select Destination Class...</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" id="destinationClassId">

                    <div id="promotionSummary" class="bg-purple-50 p-3 rounded mb-4">
                        <p class="font-bold text-purple-800">Selected: <span id="selectedCount">0</span> student(s)</p>
                    </div>

                    <button onclick="promoteStudents()"
                            id="promoteBtn"
                            disabled
                            class="w-full bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-bold transition disabled:bg-gray-400 disabled:cursor-not-allowed">
                        🎓 Promote Students
                    </button>
                </div>
            </div>

            <!-- Results Section -->
            <div id="resultsSection" class="mt-6 hidden">
                <div class="bg-green-100 border-2 border-green-500 rounded-lg p-4">
                    <h4 class="text-xl font-bold text-green-800 mb-2">✅ Promotion Results</h4>
                    <p id="resultsMessage" class="text-green-700"></p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

        let studentsData = [];
        let selectedStudents = new Set();

        async function loadStudentsForPromotion() {
            const sourceClassId = document.getElementById('sourceClass').value;
            if (!sourceClassId) {
                document.getElementById('studentList').innerHTML = '<p class="text-gray-500 text-center py-4">Select a source class</p>';
                return;
            }

            try {
                const response = await axios.get(`/api/students/for-promotion?source_class_id=${sourceClassId}`);
                studentsData = response.data.students;
                selectedStudents.clear();
                renderStudentList();
                updateSelectedCount();
            } catch (error) {
                console.error('Failed to load students:', error);
                alert('Failed to load students');
            }
        }

        function renderStudentList() {
            const listContainer = document.getElementById('studentList');
            if (studentsData.length === 0) {
                listContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No students found in this class</p>';
                return;
            }

            let html = '<div class="space-y-2">';
            studentsData.forEach(student => {
                const isSelected = selectedStudents.has(student.id);
                html += `
                    <label class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer student-item"
                           data-name="${student.name.toLowerCase()}"
                           data-reg="${student.student_reg_no.toLowerCase()}">
                        <input type="checkbox"
                               value="${student.id}"
                               ${isSelected ? 'checked' : ''}
                               onchange="toggleStudent(${student.id})"
                               class="mr-2 w-4 h-4">
                        <div>
                            <p class="font-semibold">${student.name}</p>
                            <p class="text-sm text-gray-600">${student.student_reg_no}</p>
                        </div>
                    </label>
                `;
            });
            html += '</div>';
            listContainer.innerHTML = html;
        }

        function filterStudentList() {
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const items = document.querySelectorAll('.student-item');

            items.forEach(item => {
                const name = item.getAttribute('data-name');
                const reg = item.getAttribute('data-reg');

                if (name.includes(searchTerm) || reg.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function toggleStudent(studentId) {
            if (selectedStudents.has(studentId)) {
                selectedStudents.delete(studentId);
            } else {
                selectedStudents.add(studentId);
            }
            updateSelectedCount();
        }

        function selectAllStudents() {
            const visibleCheckboxes = document.querySelectorAll('.student-item:not([style*="display: none"]) input[type="checkbox"]');
            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
                selectedStudents.add(parseInt(checkbox.value));
            });
            updateSelectedCount();
        }

        function deselectAllStudents() {
            const checkboxes = document.querySelectorAll('.student-item input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectedStudents.clear();
            updateSelectedCount();
        }

        function updateSelectedCount() {
            document.getElementById('selectedCount').textContent = selectedStudents.size;
            updatePromoteButton();
        }

        function updatePromoteButton() {
            const promoteBtn = document.getElementById('promoteBtn');
            const destinationClassId = document.getElementById('destinationClassId').value;

            promoteBtn.disabled = selectedStudents.size === 0 || !destinationClassId;
        }

        async function promoteStudents() {
            const sourceClassId = document.getElementById('sourceClass').value;
            const destinationClassId = document.getElementById('destinationClassId').value;
            const destinationClassSelect = document.getElementById('destinationClassSelect');
            const destinationClassName = destinationClassSelect.options[destinationClassSelect.selectedIndex]?.text || 'selected class';

            if (selectedStudents.size === 0) {
                alert('Please select at least one student');
                return;
            }

            if (!sourceClassId) {
                alert('Please select a source class');
                return;
            }

            if (!destinationClassId) {
                alert('Please select a destination class');
                return;
            }

            if (sourceClassId === destinationClassId) {
                alert('Cannot promote students to the same class. Please select a different destination class.');
                return;
            }

            if (!confirm(`Are you sure you want to promote ${selectedStudents.size} student(s) to ${destinationClassName}?`)) {
                return;
            }

            try {
                const response = await axios.post('/api/students/promote', {
                    student_ids: Array.from(selectedStudents),
                    source_class_id: parseInt(sourceClassId),
                    destination_class_id: parseInt(destinationClassId)
                });

                document.getElementById('resultsMessage').textContent = response.data.message;
                document.getElementById('resultsSection').classList.remove('hidden');

                // Clear selections and reload
                selectedStudents.clear();
                document.getElementById('sourceClass').value = '';
                document.getElementById('destinationClassSelect').value = '';
                document.getElementById('destinationClassId').value = '';
                document.getElementById('studentList').innerHTML = '<p class="text-gray-500 text-center py-4">Select a source class to load students</p>';
                updateSelectedCount();

                setTimeout(() => {
                    document.getElementById('resultsSection').classList.add('hidden');
                }, 5000);

            } catch (error) {
                console.error('Failed to promote students:', error);
                alert('Failed to promote students: ' + (error.response?.data?.message || error.message));
            }
        }
    </script>
@endpush
