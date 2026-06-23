<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Phone Numbers - Darasa Finance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header with Breadcrumb -->
        <nav class="bg-gradient-to-r from-green-600 to-blue-600 text-white p-4 shadow-lg">
            <div class="container mx-auto">
                <!-- Breadcrumb Navigation -->
                <div class="mb-2 text-sm">
                    <a href="{{ url('/accountant-dashboard') }}" class="hover:text-green-200 transition">🏠 Home</a>
                    <span class="mx-2">›</span>
                    <a href="{{ route('sms.index') }}" class="hover:text-green-200 transition">SMS Notification</a>
                    <span class="mx-2">›</span>
                    <span class="text-green-200">Manage Phone Numbers</span>
                </div>

                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <h1 class="text-2xl font-bold">📞 Manage Parent Phone Numbers</h1>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('sms.download-template') }}" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded transition">
                            📥 Download CSV Template
                        </a>
                        <button onclick="document.getElementById('csv-upload').click()" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 rounded transition">
                            📤 Upload CSV
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container mx-auto p-6">
            <!-- Upload Form (Hidden) -->
            <form id="upload-form" enctype="multipart/form-data" class="hidden">
                <input type="file" id="csv-upload" accept=".csv,.txt" onchange="uploadCSV()">
            </form>

            <!-- Message Container -->
            <div id="message-container"></div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @php
                    $totalStudents = $students->count();
                    $withPhone1 = $students->filter(function($s) {
                        return !empty($s->parent_phone_1);
                    })->count();
                    $withPhone2 = $students->filter(function($s) {
                        return !empty($s->parent_phone_2);
                    })->count();
                    $withBoth = $students->filter(function($s) {
                        return !empty($s->parent_phone_1) && !empty($s->parent_phone_2);
                    })->count();
                @endphp

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                    <div class="text-sm text-gray-600">Total Students</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $totalStudents }}</div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                    <div class="text-sm text-gray-600">With Phone 1</div>
                    <div class="text-2xl font-bold text-green-600">{{ $withPhone1 }}</div>
                    <div class="text-xs text-gray-500">{{ $totalStudents > 0 ? round(($withPhone1/$totalStudents)*100) : 0 }}%</div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
                    <div class="text-sm text-gray-600">With Phone 2</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ $withPhone2 }}</div>
                    <div class="text-xs text-gray-500">{{ $totalStudents > 0 ? round(($withPhone2/$totalStudents)*100) : 0 }}%</div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
                    <div class="text-sm text-gray-600">With Both Numbers</div>
                    <div class="text-2xl font-bold text-purple-600">{{ $withBoth }}</div>
                    <div class="text-xs text-gray-500">{{ $totalStudents > 0 ? round(($withBoth/$totalStudents)*100) : 0 }}%</div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
                <h3 class="font-bold text-blue-700 mb-2">💡 Instructions</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Phone numbers must be in international format: <strong>255XXXXXXXXX</strong></li>
                    <li>• Phone 1 is required for SMS sending, Phone 2 is optional</li>
                    <li>• You can edit individual numbers below or upload bulk via CSV</li>
                    <li>• Download the CSV template to see all students and fill in phone numbers</li>
                </ul>
            </div>

            <!-- Filter & Search -->
            <div class="bg-white rounded-lg shadow-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Class</label>
                        <select id="filter-class" onchange="filterTable()" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 p-2 border">
                            <option value="">All Classes</option>
                            @foreach($students->pluck('class')->unique()->sort() as $class)
                                <option value="{{ $class }}">{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                        <select id="filter-status" onchange="filterTable()" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 p-2 border">
                            <option value="">All Students</option>
                            <option value="complete">Complete (Both Numbers)</option>
                            <option value="partial">Partial (Phone 1 Only)</option>
                            <option value="missing">Missing Phone Numbers</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" id="search-input" onkeyup="filterTable()" placeholder="Name or Reg Number..." class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 p-2 border">
                    </div>
                </div>
            </div>

            <!-- Students Table -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S/N</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent Phone 1</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent Phone 2</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="students-table" class="bg-white divide-y divide-gray-200">
                            @foreach($students as $index => $student)
                            <tr class="hover:bg-gray-50 student-row"
                                data-class="{{ $student->class }}"
                                data-name="{{ strtolower($student->name) }}"
                                data-reg="{{ strtolower($student->student_reg_no) }}"
                                data-status="{{ $student->parent_phone_1 && $student->parent_phone_2 ? 'complete' : ($student->parent_phone_1 ? 'partial' : 'missing') }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-gray-900">{{ $student->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $student->student_reg_no }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $student->class }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="text"
                                           id="phone1-{{ $student->id }}"
                                           value="{{ $student->parent_phone_1 }}"
                                           placeholder="255XXXXXXXXX"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 p-2 border text-sm">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="text"
                                           id="phone2-{{ $student->id }}"
                                           value="{{ $student->parent_phone_2 }}"
                                           placeholder="255XXXXXXXXX (Optional)"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 p-2 border text-sm">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button onclick="updatePhone({{ $student->id }})"
                                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded transition text-xs">
                                        💾 Save
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Update phone number
        async function updatePhone(studentId) {
            const phone1 = document.getElementById(`phone1-${studentId}`).value.trim();
            const phone2 = document.getElementById(`phone2-${studentId}`).value.trim();

            try {
                const response = await axios.put(`/sms/student/${studentId}/phone`, {
                    parent_phone_1: phone1,
                    parent_phone_2: phone2
                });

                showMessage('✅ Phone numbers updated successfully!', 'success');
            } catch (error) {
                showMessage('❌ ' + (error.response?.data?.message || error.message), 'error');
            }
        }

        // Upload CSV
        async function uploadCSV() {
            const fileInput = document.getElementById('csv-upload');
            const file = fileInput.files[0];

            if (!file) return;

            const formData = new FormData();
            formData.append('csv_file', file);

            try {
                const response = await axios.post('/sms/upload-phones', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                showMessage(`✅ ${response.data.message}`, 'success');

                if (response.data.errors && response.data.errors.length > 0) {
                    console.log('Errors:', response.data.errors);
                }

                setTimeout(() => location.reload(), 2000);
            } catch (error) {
                showMessage('❌ ' + (error.response?.data?.message || error.message), 'error');
            }

            fileInput.value = '';
        }

        // Filter table
        function filterTable() {
            const classFilter = document.getElementById('filter-class').value.toLowerCase();
            const statusFilter = document.getElementById('filter-status').value;
            const searchFilter = document.getElementById('search-input').value.toLowerCase();

            const rows = document.querySelectorAll('.student-row');

            rows.forEach(row => {
                const rowClass = row.dataset.class.toLowerCase();
                const rowName = row.dataset.name;
                const rowReg = row.dataset.reg;
                const rowStatus = row.dataset.status;

                let showRow = true;

                if (classFilter && rowClass !== classFilter) {
                    showRow = false;
                }

                if (statusFilter && rowStatus !== statusFilter) {
                    showRow = false;
                }

                if (searchFilter && !rowName.includes(searchFilter) && !rowReg.includes(searchFilter)) {
                    showRow = false;
                }

                row.style.display = showRow ? '' : 'none';
            });
        }

        // Show message
        function showMessage(message, type) {
            const container = document.getElementById('message-container');
            const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';

            container.innerHTML = `
                <div class="${bgColor} border-l-4 p-4 mb-4 rounded" role="alert">
                    <p class="font-bold">${type === 'success' ? 'Success' : 'Error'}</p>
                    <p>${message}</p>
                </div>
            `;

            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
