<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Send SMS - Darasa Finance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header with Breadcrumb -->
        <nav class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-4 shadow-lg">
            <div class="container mx-auto">
                <!-- Breadcrumb Navigation -->
                <div class="mb-2 text-sm">
                    <a href="{{ url('/accountant-dashboard') }}" class="hover:text-purple-200 transition">🏠 Home</a>
                    <span class="mx-2">›</span>
                    <span class="text-purple-200">SMS Notification</span>
                </div>

                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <h1 class="text-2xl font-bold">📱 SMS Notification System</h1>
                    </div>
                    <div class="flex gap-3 items-center">
                        <span id="balance-display" class="bg-purple-700 px-4 py-2 rounded-lg shadow">
                            <span class="text-sm">SMS Balance: </span>
                            <span class="font-bold" id="sms-balance">Loading...</span>
                        </span>
                        <a href="{{ route('sms.logs') }}" class="bg-indigo-500 hover:bg-indigo-600 px-4 py-2 rounded transition">
                            📋 SMS Logs
                        </a>
                        <a href="{{ route('sms.manage-phones') }}" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded transition">
                            📞 Manage Phones
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container mx-auto p-6">
            <!-- Success/Error Messages -->
            <div id="message-container"></div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Panel - Student Selection -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold mb-4 text-purple-600">👥 Select Recipients</h2>

                        <!-- Selection Method Tabs -->
                        <div class="flex gap-2 mb-4">
                            <button onclick="switchTab('class')" id="tab-class" class="flex-1 px-4 py-2 rounded bg-purple-500 text-white">
                                By Class
                            </button>
                            <button onclick="switchTab('search')" id="tab-search" class="flex-1 px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                                Search
                            </button>
                        </div>

                        <!-- By Class Tab -->
                        <div id="class-tab" class="tab-content">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Class</label>
                                <select id="class-select" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border">
                                    <option value="">Choose a class...</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button onclick="loadStudentsByClass()" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded transition">
                                Load Students
                            </button>
                        </div>

                        <!-- Search Tab -->
                        <div id="search-tab" class="tab-content hidden">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search Student</label>
                                <input type="text" id="search-input" placeholder="Name or Registration Number..." class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border">
                            </div>
                            <button onclick="searchStudents()" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded transition">
                                🔍 Search
                            </button>
                        </div>

                        <!-- Student List -->
                        <div class="mt-6">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-semibold text-gray-700">Students (<span id="student-count">0</span>)</h3>
                                <button onclick="selectAllStudents()" class="text-sm text-purple-600 hover:text-purple-700">
                                    Select All
                                </button>
                            </div>
                            <div id="students-list" class="space-y-2 max-h-96 overflow-y-auto">
                                <p class="text-gray-500 text-sm text-center py-4">No students loaded</p>
                            </div>
                        </div>

                        <!-- Selected Count -->
                        <div class="mt-4 p-3 bg-purple-50 rounded-lg border border-purple-200">
                            <p class="text-sm font-medium text-purple-700">
                                Selected: <span id="selected-count" class="font-bold">0</span> students
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Message Composition -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold mb-4 text-purple-600">✉️ Compose Message</h2>

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
                                <button onclick="switchLanguage('en')" id="lang-en" class="px-4 py-2 rounded bg-purple-500 text-white transition">
                                    English
                                </button>
                                <button onclick="switchLanguage('sw')" id="lang-sw" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 transition">
                                    Kiswahili
                                </button>
                            </div>
                        </div>

                        <!-- Message Templates -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quick Templates</label>
                            <select id="template-select" onchange="useTemplate()" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border">
                                <option value="">Choose a template...</option>
                                <optgroup label="System Templates">
                                    <option value="fee_reminder">Fee Reminder (Short)</option>
                                    <option value="fee_statement">Fee Statement (Detailed)</option>
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
                                <label class="block text-sm font-medium text-gray-700">Message</label>
                                <button onclick="openSaveTemplateModal()" class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                    💾 Save as Template
                                </button>
                            </div>
                            <textarea id="message-text" rows="8" maxlength="1000" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-3 border" placeholder="Type your message here..."></textarea>
                            <div class="flex justify-between items-center mt-2 text-sm">
                                <span class="text-gray-600">
                                    <span id="char-count">0</span>/1000 characters
                                    (<span id="sms-count">0</span> SMS)
                                </span>
                            </div>
                        </div>

                        <!-- Clickable Placeholders - Left Bottom -->
                        <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h3 class="font-semibold text-gray-700 mb-2 text-sm">Click to Insert Placeholder:</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                <button onclick="insertPlaceholder('{student_name}')" class="bg-purple-100 hover:bg-purple-200 text-purple-700 px-3 py-2 rounded text-xs transition text-left">
                                    📝 {student_name}
                                </button>
                                <button onclick="insertPlaceholder('{student_reg}')" class="bg-purple-100 hover:bg-purple-200 text-purple-700 px-3 py-2 rounded text-xs transition text-left">
                                    🔢 {student_reg}
                                </button>
                                <button onclick="insertPlaceholder('{class}')" class="bg-purple-100 hover:bg-purple-200 text-purple-700 px-3 py-2 rounded text-xs transition text-left">
                                    🏫 {class}
                                </button>
                                <button onclick="insertPlaceholder('{total_sales}')" class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded text-xs transition text-left">
                                    💰 {total_sales}
                                </button>
                                <button onclick="insertPlaceholder('{total_paid}')" class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded text-xs transition text-left">
                                    ✅ {total_paid}
                                </button>
                                <button onclick="insertPlaceholder('{balance}')" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-2 rounded text-xs transition text-left">
                                    ⚠️ {balance}
                                </button>
                                <button onclick="insertPlaceholder('{particulars_breakdown}')" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded text-xs transition text-left col-span-2">
                                    📋 {particulars_breakdown}
                                </button>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h3 class="font-semibold text-gray-700 mb-2">📱 Message Preview</h3>
                            <p id="message-preview" class="text-sm text-gray-600 whitespace-pre-wrap">Your message will appear here...</p>
                        </div>

                        <!-- Send Button -->
                        <div class="flex gap-3">
                            <button onclick="sendSMS()" id="send-btn" class="flex-1 bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-6 py-3 rounded-lg font-semibold transition transform hover:scale-105 shadow-lg">
                                📤 Send SMS
                            </button>
                            <button onclick="clearForm()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold transition">
                                🔄 Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Template Modal -->
    <div id="save-template-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold mb-4 text-purple-600">💾 Save as Template</h3>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Template Name</label>
                <input type="text" id="template-name" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border" placeholder="e.g., Monthly Fee Reminder">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">English Message</label>
                <textarea id="template-message-en" rows="4" readonly class="w-full border-gray-300 rounded-lg shadow-sm bg-gray-50 p-2 border"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Swahili Message (Optional)</label>
                <textarea id="template-message-sw" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2 border" placeholder="Translate your message to Swahili..."></textarea>
            </div>

            <div class="flex gap-3">
                <button onclick="saveTemplate()" class="flex-1 bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded transition">
                    Save Template
                </button>
                <button onclick="closeSaveTemplateModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>

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
            document.getElementById('lang-en').classList.toggle('bg-purple-500', lang === 'en');
            document.getElementById('lang-en').classList.toggle('text-white', lang === 'en');
            document.getElementById('lang-en').classList.toggle('bg-gray-200', lang !== 'en');
            document.getElementById('lang-sw').classList.toggle('bg-purple-500', lang === 'sw');
            document.getElementById('lang-sw').classList.toggle('text-white', lang === 'sw');
            document.getElementById('lang-sw').classList.toggle('bg-gray-200', lang !== 'sw');
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
            document.getElementById('tab-class').classList.remove('bg-purple-500', 'text-white');
            document.getElementById('tab-class').classList.add('bg-gray-200');
            document.getElementById('tab-search').classList.remove('bg-purple-500', 'text-white');
            document.getElementById('tab-search').classList.add('bg-gray-200');

            if (tab === 'class') {
                document.getElementById('class-tab').classList.remove('hidden');
                document.getElementById('tab-class').classList.add('bg-purple-500', 'text-white');
                document.getElementById('tab-class').classList.remove('bg-gray-200');
            } else {
                document.getElementById('search-tab').classList.remove('hidden');
                document.getElementById('tab-search').classList.add('bg-purple-500', 'text-white');
                document.getElementById('tab-search').classList.remove('bg-gray-200');
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
                students = response.data;
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
            } catch (error) {
                showMessage('Error searching students: ' + (error.response?.data?.message || error.message), 'error');
            }
        }

        // Display students
        function displayStudents(studentList) {
            const container = document.getElementById('students-list');
            document.getElementById('student-count').textContent = studentList.length;

            if (studentList.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">No students with registered phone numbers found</p>';
                return;
            }

            container.innerHTML = studentList.map(student => `
                <label class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-purple-50 cursor-pointer transition border border-gray-200">
                    <input type="checkbox" value="${student.id}" onchange="toggleStudent(${student.id})" class="mr-3 h-4 w-4 text-purple-600">
                    <div class="flex-1">
                        <div class="font-semibold text-gray-800">${student.name}</div>
                        <div class="text-xs text-gray-600">${student.student_reg_no} | ${student.school_class?.name || 'N/A'}</div>
                        <div class="text-xs text-gray-500">
                            📞 ${student.parent_phone_1}${student.parent_phone_2 ? ' | ' + student.parent_phone_2 : ''}
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

        function updateCharCount() {
            const text = document.getElementById('message-text').value;
            const charCount = text.length;
            const smsCount = calculateSmsCount(charCount);

            document.getElementById('char-count').textContent = charCount;
            document.getElementById('sms-count').textContent = smsCount;
        }

        function calculateSmsCount(length) {
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
                'fee_reminder': `Dear Parent,\n\nFee reminder for {student_name} ({class}).\n\nBalance Due: TSh {balance}\n\nPlease pay soon.\n\n- Darasa Finance`,
                'fee_statement': `DARASA FEE STATEMENT\n\nStudent: {student_name}\nReg: {student_reg}\nClass: {class}\n\n{particulars_breakdown}\n\nTotal Fees: {total_sales}\nPaid: {total_paid}\nBalance: {balance}\n\nThank you!`,
                'payment_received': `Dear Parent,\n\nPayment of TSh {total_paid} received for {student_name}.\n\nRemaining balance: TSh {balance}\n\nThank you!\n- Darasa Finance`
            };

            const systemTemplatesSw = {
                'fee_reminder': `Mzazi Mpendwa,\n\nKumbusho la ada kwa {student_name} ({class}).\n\nSalio: TSh {balance}\n\nTafadhali lipa mapema.\n\n- Darasa Finance`,
                'fee_statement': `TAARIFA YA ADA - DARASA\n\nMwanafunzi: {student_name}\nNamba: {student_reg}\nDarasa: {class}\n\n{particulars_breakdown}\n\nJumla ya Ada: {total_sales}\nAmelipa: {total_paid}\nSalio: {balance}\n\nAsante!`,
                'payment_received': `Mzazi Mpendwa,\n\nMalipo ya TSh {total_paid} kwa {student_name} yamepokelewa.\n\nSalio Lililobaki: TSh {balance}\n\nAsante!\n- Darasa Finance`
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

            const phoneNumber = document.querySelector('input[name="phone_number"]:checked').value;

            const btn = document.getElementById('send-btn');
            btn.disabled = true;
            btn.innerHTML = '⏳ Sending...';

            try {
                const response = await axios.post('/sms/send', {
                    student_ids: selectedStudents,
                    message: message,
                    phone_number: phoneNumber
                });

                showMessage(`✅ ${response.data.message}`, 'success');
                clearForm();
                loadSmsBalance();
            } catch (error) {
                showMessage('❌ ' + (error.response?.data?.message || error.message), 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '📤 Send SMS';
            }
        }

        // Load SMS balance
        async function loadSmsBalance() {
            try {
                const response = await axios.get('/sms/balance');
                const balance = response.data.display || response.data.sms_balance || 'N/A';
                document.getElementById('sms-balance').textContent = balance;
            } catch (error) {
                document.getElementById('sms-balance').textContent = 'Error';
            }
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

                showMessage('✅ Template saved successfully!', 'success');
                closeSaveTemplateModal();

                // Reload templates
                location.reload();
            } catch (error) {
                showMessage('❌ Failed to save template: ' + (error.response?.data?.message || error.message), 'error');
            }
        }

        // Clear form
        function clearForm() {
            document.getElementById('message-text').value = '';
            document.getElementById('template-select').value = '';
            selectedStudents = [];
            document.querySelectorAll('#students-list input[type="checkbox"]').forEach(cb => cb.checked = false);
            updateSelectedCount();
            updateCharCount();
            updatePreview();
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
