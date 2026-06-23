@extends('layouts.accountant')

@section('title', 'Bank integration — Darasa Finance')
@section('page_title', 'Bank integration')

@section('content')
<!-- Main Content -->
    <div class="w-full p-6">
        <!-- Tab Navigation -->
        <div class="flex gap-4 mb-6">
            <button onclick="showTab('simulation')" id="tab-simulation" class="px-6 py-3 font-bold rounded-lg transition bg-gradient-to-r from-green-500 to-blue-500 text-white">
                🎮 Payment Simulation
            </button>
            <button onclick="showTab('transactions')" id="tab-transactions" class="px-6 py-3 font-bold rounded-lg transition bg-gray-300 text-gray-700 hover:bg-gray-400">
                📋 Transactions
            </button>
            <button onclick="showTab('settings')" id="tab-settings" class="px-6 py-3 font-bold rounded-lg transition bg-gray-300 text-gray-700 hover:bg-gray-400">
                ⚙️ API Settings
            </button>
        </div>

        <!-- Simulation Tab -->
        <div id="content-simulation">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <h2 class="text-3xl font-bold text-green-600 mb-4">🎮 Bank Payment Simulator</h2>
                <p class="text-gray-600 mb-6">Test the bank payment integration without a real bank API. Simulate payments and see instant results!</p>

                <!-- Simulation Form -->
                <form id="simulationForm" onsubmit="simulatePayment(event)" class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block font-bold mb-2">Transaction ID <span class="text-red-500">*</span></label>
                        <input type="text" id="sim_transaction_id" required placeholder="TXN-" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Control Number</label>
                        <input type="text" id="sim_control_number" placeholder="Optional" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Payer Name <span class="text-red-500">*</span></label>
                        <input type="text" id="sim_payer_name" required placeholder="Enter student name" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                        <p class="text-xs text-gray-500 mt-1">💡 Tip: Use exact student name from database for automatic matching</p>
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Payer Phone</label>
                        <input type="text" id="sim_payer_phone" placeholder="255XXXXXXXXX" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Amount (TSh) <span class="text-red-500">*</span></label>
                        <input type="number" id="sim_amount" required step="0.01" min="0" placeholder="50000.00" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Transaction Date <span class="text-red-500">*</span></label>
                        <input type="date" id="sim_transaction_date" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Bank Name</label>
                        <select id="sim_bank_name" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                            <option value="CRDB Bank">CRDB Bank</option>
                            <option value="NMB Bank">NMB Bank</option>
                            <option value="NBC Bank">NBC Bank</option>
                            <option value="Stanbic Bank">Stanbic Bank</option>
                            <option value="Exim Bank">Exim Bank</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Reference</label>
                        <input type="text" id="sim_reference" placeholder="FEE PAYMENT" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div class="col-span-2">
                        <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-blue-500 hover:from-green-600 hover:to-blue-600 text-white px-8 py-4 rounded-lg font-bold text-lg shadow-lg transition transform hover:scale-105">
                            🚀 Simulate Bank Payment
                        </button>
                    </div>
                </form>

                <!-- Result Display -->
                <div id="simulationResult" class="hidden mt-8 p-6 rounded-lg"></div>
            </div>
        </div>

        <!-- Transactions Tab -->
        <div id="content-transactions" class="hidden">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-green-600">📋 Bank Transactions</h2>
                    <div class="flex gap-3">
                        <select id="filter_status" onchange="loadTransactions()" class="border-2 border-gray-300 rounded-lg px-4 py-2">
                            <option value="">All Status</option>
                            <option value="matched">✅ Matched</option>
                            <option value="suspense">⚠️ Suspense</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="failed">❌ Failed</option>
                        </select>
                        <input type="text" id="filter_search" onkeyup="loadTransactions()" placeholder="Search..." class="border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>

                <!-- Summary Cards -->
                <div id="transactionSummary" class="grid grid-cols-4 gap-4 mb-6"></div>

                <!-- Transactions Table -->
                <div id="transactionsTable" class="overflow-x-auto"></div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="content-settings" class="hidden">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <h2 class="text-3xl font-bold text-green-600 mb-4">⚙️ Bank API Settings</h2>
                <p class="text-gray-600 mb-6">Configure your bank API integration and webhook settings.</p>

                <form id="settingsForm" onsubmit="saveSettings(event)" class="max-w-2xl">
                    <div class="mb-4">
                        <label class="block font-bold mb-2">Bank Name <span class="text-red-500">*</span></label>
                        <input type="text" id="settings_bank_name" required class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block font-bold mb-2">API URL</label>
                        <input type="url" id="settings_api_url" placeholder="https://bank-api.example.com" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block font-bold mb-2">API Key</label>
                        <input type="text" id="settings_api_key" placeholder="Your API Key" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block font-bold mb-2">API Secret</label>
                        <input type="password" id="settings_api_secret" placeholder="Your API Secret" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block font-bold mb-2">Webhook Secret</label>
                        <input type="text" id="settings_webhook_secret" placeholder="Shared secret for webhook verification" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
                    </div>

                    <div class="mb-4 p-4 bg-blue-50 border-2 border-blue-300 rounded-lg">
                        <p class="font-bold text-blue-800 mb-2">📌 Webhook URL (provide this to your bank):</p>
                        <code class="block bg-white p-3 rounded border border-blue-200 text-sm">
                            {{ url('/api/bank-webhook') }}
                        </code>
                    </div>

                    <div class="mb-4 flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="settings_is_active" class="w-5 h-5">
                            <span class="font-bold">Enable Bank API Integration</span>
                        </label>
                    </div>

                    <div class="mb-6 flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="settings_use_simulation" class="w-5 h-5">
                            <span class="font-bold">Use Simulation Mode (for testing)</span>
                        </label>
                    </div>

                    <button type="submit" class="bg-gradient-to-r from-green-500 to-blue-500 hover:from-green-600 hover:to-blue-600 text-white px-8 py-3 rounded-lg font-bold shadow-lg transition">
                        💾 Save Settings
                    </button>
                </form>
            </div>
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

        // Format amount
        function formatTSh(amount) {
            return 'TSh ' + parseFloat(amount).toLocaleString('en-TZ', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Tab Switching
        function showTab(tabName) {
            // Hide all tabs
            document.getElementById('content-simulation').classList.add('hidden');
            document.getElementById('content-transactions').classList.add('hidden');
            document.getElementById('content-settings').classList.add('hidden');

            // Reset all tab buttons
            document.getElementById('tab-simulation').className = 'px-6 py-3 font-bold rounded-lg transition bg-gray-300 text-gray-700 hover:bg-gray-400';
            document.getElementById('tab-transactions').className = 'px-6 py-3 font-bold rounded-lg transition bg-gray-300 text-gray-700 hover:bg-gray-400';
            document.getElementById('tab-settings').className = 'px-6 py-3 font-bold rounded-lg transition bg-gray-300 text-gray-700 hover:bg-gray-400';

            // Show selected tab
            document.getElementById('content-' + tabName).classList.remove('hidden');
            document.getElementById('tab-' + tabName).className = 'px-6 py-3 font-bold rounded-lg transition bg-gradient-to-r from-green-500 to-blue-500 text-white';

            // Load data if needed
            if (tabName === 'transactions') {
                loadTransactions();
            } else if (tabName === 'settings') {
                loadSettings();
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set default transaction ID and date
            document.getElementById('sim_transaction_id').value = 'TXN-' + Date.now();
            document.getElementById('sim_transaction_date').valueAsDate = new Date();
        });

        // Simulate Payment
        async function simulatePayment(event) {
            event.preventDefault();

            const formData = {
                transaction_id: document.getElementById('sim_transaction_id').value,
                control_number: document.getElementById('sim_control_number').value,
                payer_name: document.getElementById('sim_payer_name').value,
                payer_phone: document.getElementById('sim_payer_phone').value,
                amount: parseFloat(document.getElementById('sim_amount').value),
                transaction_date: document.getElementById('sim_transaction_date').value,
                bank_name: document.getElementById('sim_bank_name').value,
                reference: document.getElementById('sim_reference').value
            };

            try {
                const response = await axios.post(`${API_BASE}/bank-webhook/simulate`, formData);
                const result = response.data;

                let resultHtml = '';

                if (result.data.status === 'matched') {
                    resultHtml = `
                        <div class="bg-green-50 border-2 border-green-400 rounded-lg p-6">
                            <h3 class="text-2xl font-bold text-green-700 mb-4">✅ Payment Successfully Matched!</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="font-bold text-gray-700">Student</p>
                                    <p class="text-lg">${result.data.student.name}</p>
                                    <p class="text-sm text-gray-600">${result.data.student.student_reg_no}</p>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-700">Amount</p>
                                    <p class="text-lg text-green-700 font-bold">${formatTSh(formData.amount)}</p>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-700">Receipt Number</p>
                                    <p class="text-lg font-mono">${result.data.voucher.voucher_number}</p>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-700">Book Entry</p>
                                    <p class="text-sm text-gray-600">Voucher created in ${result.data.voucher.book_id ? 'book' : 'default book'}</p>
                                </div>
                            </div>
                            <div class="mt-4 p-3 bg-white rounded border border-green-200">
                                <p class="font-bold text-sm text-green-800">📱 SMS Confirmation sent to parent!</p>
                            </div>
                        </div>
                    `;
                } else if (result.data.status === 'suspense') {
                    resultHtml = `
                        <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-6">
                            <h3 class="text-2xl font-bold text-yellow-700 mb-4">⚠️ Payment Moved to Suspense</h3>
                            <p class="mb-4">Student name "${formData.payer_name}" not found in database.</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="font-bold text-gray-700">Amount</p>
                                    <p class="text-lg text-yellow-700 font-bold">${formatTSh(formData.amount)}</p>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-700">Suspense Entry</p>
                                    <p class="text-lg font-mono">${result.data.voucher.voucher_number}</p>
                                </div>
                            </div>
                            <div class="mt-4 p-3 bg-white rounded border border-yellow-200">
                                <p class="font-bold text-sm text-yellow-800">💡 You can resolve this from Suspense Accounts module</p>
                            </div>
                        </div>
                    `;
                }

                document.getElementById('simulationResult').innerHTML = resultHtml;
                document.getElementById('simulationResult').classList.remove('hidden');

                // Reset form
                document.getElementById('simulationForm').reset();
                document.getElementById('sim_transaction_id').value = 'TXN-' + Date.now();
                document.getElementById('sim_transaction_date').valueAsDate = new Date();

            } catch (error) {
                let errorMsg = error.response?.data?.message || error.message;
                if (error.response?.data?.errors) {
                    errorMsg += '\n' + Object.values(error.response.data.errors).flat().join('\n');
                }

                document.getElementById('simulationResult').innerHTML = `
                    <div class="bg-red-50 border-2 border-red-400 rounded-lg p-6">
                        <h3 class="text-2xl font-bold text-red-700 mb-4">❌ Error Processing Payment</h3>
                        <p class="text-red-600">${errorMsg}</p>
                    </div>
                `;
                document.getElementById('simulationResult').classList.remove('hidden');
            }
        }

        // Load Transactions
        async function loadTransactions() {
            try {
                const status = document.getElementById('filter_status').value;
                const search = document.getElementById('filter_search').value;

                let url = `${API_BASE}/bank-transactions?`;
                if (status) url += `status=${status}&`;
                if (search) url += `search=${search}&`;

                const response = await axios.get(url);
                const data = response.data;
                const transactions = data.transactions.data || data.transactions || [];

                // Display Summary
                let summaryHtml = `
                    <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-4 text-center">
                        <p class="text-sm font-semibold text-blue-700">Total Transactions</p>
                        <p class="text-2xl font-bold text-blue-600">${data.summary.total_transactions}</p>
                    </div>
                    <div class="bg-green-50 border-2 border-green-300 rounded-lg p-4 text-center">
                        <p class="text-sm font-semibold text-green-700">✅ Matched</p>
                        <p class="text-2xl font-bold text-green-600">${data.summary.matched_count}</p>
                    </div>
                    <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-4 text-center">
                        <p class="text-sm font-semibold text-yellow-700">⚠️ Suspense</p>
                        <p class="text-2xl font-bold text-yellow-600">${data.summary.suspense_count}</p>
                    </div>
                    <div class="bg-purple-50 border-2 border-purple-300 rounded-lg p-4 text-center">
                        <p class="text-sm font-semibold text-purple-700">Total Amount</p>
                        <p class="text-xl font-bold text-purple-600">${formatTSh(data.summary.total_amount || 0)}</p>
                    </div>
                `;
                document.getElementById('transactionSummary').innerHTML = summaryHtml;

                // Display Transactions Table
                let tableHtml = `
                    <table class="w-full border-2 border-gray-300 rounded-lg">
                        <thead class="bg-green-100">
                            <tr>
                                <th class="p-3 text-left">Date</th>
                                <th class="p-3 text-left">Transaction ID</th>
                                <th class="p-3 text-left">Payer Name</th>
                                <th class="p-3 text-right">Amount</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Student/Notes</th>
                                <th class="p-3 text-left">SMS</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                if (transactions.length === 0) {
                    tableHtml += `
                        <tr>
                            <td colspan="7" class="p-6 text-center text-gray-500">No transactions found</td>
                        </tr>
                    `;
                } else {
                    transactions.forEach(txn => {
                        let statusBadge = '';
                        if (txn.processing_status === 'matched') {
                            statusBadge = '<span class="px-2 py-1 bg-green-200 text-green-800 rounded text-xs font-bold">✅ Matched</span>';
                        } else if (txn.processing_status === 'suspense') {
                            statusBadge = '<span class="px-2 py-1 bg-yellow-200 text-yellow-800 rounded text-xs font-bold">⚠️ Suspense</span>';
                        } else if (txn.processing_status === 'pending') {
                            statusBadge = '<span class="px-2 py-1 bg-blue-200 text-blue-800 rounded text-xs font-bold">⏳ Pending</span>';
                        } else if (txn.processing_status === 'failed') {
                            statusBadge = '<span class="px-2 py-1 bg-red-200 text-red-800 rounded text-xs font-bold">❌ Failed</span>';
                        }

                        const smsBadge = txn.sms_sent
                            ? '<span class="text-green-600">📱 ✅</span>'
                            : '<span class="text-gray-400">📱 ❌</span>';

                        const studentInfo = txn.student
                            ? `${txn.student.name}<br><span class="text-xs text-gray-500">${txn.student.student_reg_no}</span>`
                            : `<span class="text-xs text-gray-500">${txn.processing_notes || 'N/A'}</span>`;

                        tableHtml += `
                            <tr class="border-t hover:bg-green-50">
                                <td class="p-3">${new Date(txn.transaction_date).toLocaleDateString()}</td>
                                <td class="p-3 font-mono text-sm">${txn.transaction_id}</td>
                                <td class="p-3">${txn.payer_name}</td>
                                <td class="p-3 text-right font-bold text-green-700">${formatTSh(txn.amount)}</td>
                                <td class="p-3">${statusBadge}</td>
                                <td class="p-3">${studentInfo}</td>
                                <td class="p-3 text-center">${smsBadge}</td>
                            </tr>
                        `;
                    });
                }

                tableHtml += `
                        </tbody>
                    </table>
                `;

                document.getElementById('transactionsTable').innerHTML = tableHtml;

            } catch (error) {
                console.error('Error loading transactions:', error);
                alert('Failed to load transactions: ' + error.message);
            }
        }

        // Load Settings
        async function loadSettings() {
            try {
                const response = await axios.get(`${API_BASE}/bank-api-settings`);
                const settings = response.data;

                document.getElementById('settings_bank_name').value = settings.bank_name || '';
                document.getElementById('settings_api_url').value = settings.api_url || '';
                document.getElementById('settings_is_active').checked = settings.is_active || false;
                document.getElementById('settings_use_simulation').checked = settings.use_simulation || false;

            } catch (error) {
                console.error('Error loading settings:', error);
            }
        }

        // Save Settings
        async function saveSettings(event) {
            event.preventDefault();

            const formData = {
                bank_name: document.getElementById('settings_bank_name').value,
                api_url: document.getElementById('settings_api_url').value,
                api_key: document.getElementById('settings_api_key').value || undefined,
                api_secret: document.getElementById('settings_api_secret').value || undefined,
                webhook_secret: document.getElementById('settings_webhook_secret').value || undefined,
                is_active: document.getElementById('settings_is_active').checked,
                use_simulation: document.getElementById('settings_use_simulation').checked
            };

            try {
                await axios.put(`${API_BASE}/bank-api-settings`, formData);
                alert('✅ Settings saved successfully!');
            } catch (error) {
                alert('❌ Failed to save settings: ' + (error.response?.data?.message || error.message));
            }
        }
    </script>
@endpush
