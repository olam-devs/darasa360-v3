@extends($portalLayout ?? 'layouts.accountant')

@section('title', 'Particular ledger — Darasa Finance')
@section('page_title', 'Particular ledger')

@section('content')
<!-- Module Content -->
    <div class="w-full p-6">
        <div>
            <h2 class="text-3xl font-bold text-blue-600 mb-6">📋 Particular Ledger</h2>
            <p class="text-gray-600 mb-6">Select a particular/fee type to view all related transactions across all students.</p>

            <div id="particularsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6"></div>
            <div id="particularDetailsSection"></div>
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

        // Load particulars on page load
        document.addEventListener('DOMContentLoaded', async function() {
            await loadParticulars();
        });

        async function loadParticulars() {
            try {
                const response = await axios.get(`${API_BASE}/particulars`);
                const particulars = response.data;

                let html = '';
                particulars.forEach(particular => {
                    html += `
                        <div onclick="loadParticularDetails(${particular.id})" class="bg-blue-50 border-2 border-blue-300 rounded-lg p-4 hover:bg-blue-100 hover:border-blue-500 transition cursor-pointer">
                            <h4 class="font-bold text-lg text-blue-800">${particular.name}</h4>
                            <p class="text-xs text-gray-600 mt-2">Click to view transactions</p>
                        </div>
                    `;
                });

                document.getElementById('particularsGrid').innerHTML = html;
            } catch (error) {
                alert('Error loading particulars: ' + error.message);
            }
        }

        let currentParticularId = null;
        let currentFromDate = '';
        let currentToDate = '';

        async function loadParticularDetails(particularId, fromDate = '', toDate = '', page = 1) {
            try {
                currentParticularId = particularId;
                currentFromDate = fromDate;
                currentToDate = toDate;

                let url = `${API_BASE}/ledgers/particular/${particularId}?page=${page}&per_page=15`;
                if (fromDate && toDate) {
                    url += `&from_date=${fromDate}&to_date=${toDate}`;
                }

                const response = await axios.get(url);
                const data = response.data;

                let html = `
                    <div class="bg-white border-2 border-blue-300 rounded-lg p-6">
                        <div class="mb-6 border-b-2 border-blue-300 pb-4">
                            <h3 class="text-2xl font-bold text-blue-700">${data.particular.name}</h3>
                            <p class="text-sm text-gray-600 mt-2">${data.date_range}</p>

                            <!-- Date Range Filter -->
                            <div class="mt-4 bg-blue-50 border-2 border-blue-300 rounded-lg p-3">
                                <h4 class="text-sm font-bold text-blue-700 mb-2">Filter by Date Range</h4>
                                <div class="grid grid-cols-4 gap-3">
                                    <div>
                                        <label class="text-xs font-bold text-gray-700">From:</label>
                                        <input type="date" id="particularFromDate" value="${fromDate}" class="w-full border rounded px-2 py-1">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-gray-700">To:</label>
                                        <input type="date" id="particularToDate" value="${toDate}" class="w-full border rounded px-2 py-1">
                                    </div>
                                    <div class="flex items-end">
                                        <button onclick="applyParticularDateFilter(${particularId})" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-1 rounded w-full text-sm">
                                            Apply
                                        </button>
                                    </div>
                                    <div class="flex items-end">
                                        <button onclick="loadParticularDetails(${particularId})" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-1 rounded w-full text-sm">
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                `;

                // Show opening balance if it exists
                if (data.summary.opening_balance !== undefined && data.summary.opening_balance !== 0) {
                    html += `
                        <div class="grid grid-cols-4 gap-4 mb-6">
                            <div class="bg-purple-50 border-2 border-purple-300 rounded-lg p-4 text-center">
                                <p class="text-xs text-gray-600">Opening Balance</p>
                                <p class="text-2xl font-bold text-purple-700">${formatTSh(data.summary.opening_balance)}</p>
                            </div>
                            <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-4 text-center">
                                <p class="text-xs text-gray-600">Total Debit</p>
                                <p class="text-2xl font-bold text-blue-700">${formatTSh(data.summary.total_debit)}</p>
                            </div>
                            <div class="bg-green-50 border-2 border-green-300 rounded-lg p-4 text-center">
                                <p class="text-xs text-gray-600">Total Credit</p>
                                <p class="text-2xl font-bold text-green-700">${formatTSh(data.summary.total_credit)}</p>
                            </div>
                            <div class="bg-red-50 border-2 border-red-300 rounded-lg p-4 text-center">
                                <p class="text-xs text-gray-600">Closing Balance</p>
                                <p class="text-2xl font-bold text-red-700">${formatTSh(data.summary.balance)}</p>
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-4 text-center">
                                <p class="text-xs text-gray-600">Total Debit</p>
                                <p class="text-2xl font-bold text-blue-700">${formatTSh(data.summary.total_debit)}</p>
                            </div>
                            <div class="bg-green-50 border-2 border-green-300 rounded-lg p-4 text-center">
                                <p class="text-xs text-gray-600">Total Credit</p>
                                <p class="text-2xl font-bold text-green-700">${formatTSh(data.summary.total_credit)}</p>
                            </div>
                            <div class="bg-red-50 border-2 border-red-300 rounded-lg p-4 text-center">
                                <p class="text-xs text-gray-600">Balance</p>
                                <p class="text-2xl font-bold text-red-700">${formatTSh(data.summary.balance)}</p>
                            </div>
                        </div>
                    `;
                }

                html += `

                        <!-- Transactions Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-2 border-gray-300 bg-white">
                                <thead class="bg-blue-100">
                                    <tr>
                                        <th class="p-3 text-left">Date</th>
                                        <th class="p-3 text-left">Student</th>
                                        <th class="p-3 text-left">Class</th>
                                        <th class="p-3 text-left">Book</th>
                                        <th class="p-3 text-left">Type</th>
                                        <th class="p-3 text-right">Debit</th>
                                        <th class="p-3 text-right">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                let entries = data.entries;
                if (!Array.isArray(entries)) {
                     // Fallback if entries is paginated object in some cases or wrapped
                     entries = entries?.data || [];
                }
                if (entries.length === 0) {
                    html += '<tr><td colspan="9" class="p-8 text-center text-gray-500">No transactions found</td></tr>';
                }
                entries.forEach(entry => {
                    // Check if this is a page opening balance
                    if (entry.is_page_opening) {
                        html += `
                            <tr class="bg-purple-100 border-2 border-purple-500">
                                <td colspan="5" class="p-3 font-bold text-purple-900">
                                    Page Opening Balance
                                </td>
                                <td colspan="2" class="p-3 text-right font-bold text-xl text-purple-900">
                                    ${formatTSh(entry.opening_balance)}
                                </td>
                            </tr>
                        `;
                    }
                    // Check if this is a page closing balance
                    else if (entry.is_page_closing) {
                        html += `
                            <tr class="bg-purple-100 border-2 border-purple-500">
                                <td colspan="5" class="p-3 font-bold text-purple-900 text-right">
                                    Page Closing Balance:
                                </td>
                                <td colspan="2" class="p-3 text-right font-bold text-xl text-purple-900">
                                    ${formatTSh(entry.closing_balance)}
                                </td>
                            </tr>
                        `;
                    }
                    // Check if this is a month-end highlight
                    else if (entry.is_month_end) {
                        html += `
                            <tr class="bg-amber-100 border-2 border-amber-500">
                                <td colspan="5" class="p-3 font-bold text-amber-900">
                                    Month End - ${entry.month}
                                </td>
                                <td class="p-3 text-right font-bold text-amber-900">
                                    ${formatTSh(entry.monthly_debit)}
                                </td>
                                <td class="p-3 text-right font-bold text-amber-900">
                                    ${formatTSh(entry.monthly_credit)}
                                </td>
                            </tr>
                            <tr class="bg-amber-200 border-2 border-amber-600">
                                <td colspan="5" class="p-3 font-bold text-amber-950 text-right">
                                    CLOSING BALANCE:
                                </td>
                                <td colspan="2" class="p-3 text-right font-bold text-xl text-amber-950">
                                    ${formatTSh(entry.closing_balance)}
                                </td>
                            </tr>
                        `;
                    }
                    // Check if this is a month-start highlight
                    else if (entry.is_month_start) {
                        html += `
                            <tr class="bg-blue-100 border-2 border-blue-500">
                                <td colspan="5" class="p-3 font-bold text-blue-900">
                                    Month Start - ${entry.month}
                                </td>
                                <td colspan="2" class="p-3 text-right font-bold text-blue-900">
                                    Opening Balance: ${formatTSh(entry.opening_balance)}
                                </td>
                            </tr>
                        `;
                    }
                    // Regular transaction entry
                    else {
                        html += `
                            <tr class="border-t hover:bg-blue-50">
                                <td class="p-3">${entry.date}</td>
                                <td class="p-3 font-semibold">${entry.student}</td>
                                <td class="p-3">${entry.class}</td>
                                <td class="p-3">${entry.book}</td>
                                <td class="p-3"><span class="px-2 py-1 rounded text-xs font-bold ${
                                    entry.voucher_type === 'Sales' ? 'bg-red-200 text-red-800' :
                                    entry.voucher_type === 'Receipt' ? 'bg-green-200 text-green-800' :
                                    'bg-blue-200 text-blue-800'
                                }">${entry.voucher_type}</span></td>
                                <td class="p-3 text-right font-bold text-red-600">${formatTSh(entry.debit)}</td>
                                <td class="p-3 text-right font-bold text-green-600">${formatTSh(entry.credit)}</td>
                            </tr>
                        `;
                    }
                });

                html += `
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                `;

                if (data.pagination && data.pagination.last_page > 1) {
                    html += `
                        <div class="mt-6 flex justify-center items-center gap-2">
                            <button
                                onclick="loadParticularDetails(${particularId}, '${fromDate}', '${toDate}', ${data.pagination.current_page - 1})"
                                ${data.pagination.current_page === 1 ? 'disabled' : ''}
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed"
                            >
                                Previous
                            </button>
                            <span class="px-4 py-2 bg-gray-100 border rounded">
                                Page ${data.pagination.current_page} of ${data.pagination.last_page}
                            </span>
                            <button
                                onclick="loadParticularDetails(${particularId}, '${fromDate}', '${toDate}', ${data.pagination.current_page + 1})"
                                ${data.pagination.current_page === data.pagination.last_page ? 'disabled' : ''}
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed"
                            >
                                Next
                            </button>
                        </div>
                        <div class="mt-2 text-center text-sm text-gray-600">
                            Showing ${data.pagination.from || 0} to ${data.pagination.to || 0} of ${data.pagination.total} entries
                        </div>
                    `;
                }


                // Add Scholarship Section if there are scholarships
                if (data.scholarships && data.scholarships.entries && data.scholarships.entries.length > 0) {
                    html += `
                        <div class="mt-6 bg-amber-50 border-2 border-amber-400 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-lg font-bold text-amber-800">🎓 Scholarships for this Particular</h4>
                                <div class="flex gap-4">
                                    <span class="bg-amber-200 text-amber-900 px-3 py-1 rounded-full text-sm font-bold">
                                        ${data.scholarships.student_count} Students
                                    </span>
                                    <span class="bg-green-200 text-green-900 px-3 py-1 rounded-full text-sm font-bold">
                                        Total Forgiven: ${formatTSh(data.scholarships.total_forgiven)}
                                    </span>
                                </div>
                            </div>
                            <table class="w-full border text-sm">
                                <thead class="bg-amber-100">
                                    <tr>
                                        <th class="p-2 text-left border">Date</th>
                                        <th class="p-2 text-left border">Student</th>
                                        <th class="p-2 text-left border">Class</th>
                                        <th class="p-2 text-left border">Academic Year</th>
                                        <th class="p-2 text-center border">Type</th>
                                        <th class="p-2 text-right border">Original</th>
                                        <th class="p-2 text-right border">Forgiven</th>
                                        <th class="p-2 text-right border">Pays</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    data.scholarships.entries.forEach(s => {
                        const typeClass = s.scholarship_type === 'full' ? 'bg-purple-200 text-purple-800' : 'bg-blue-200 text-blue-800';
                        html += `
                            <tr class="border-t hover:bg-amber-100">
                                <td class="p-2 border">${s.date}</td>
                                <td class="p-2 border font-medium">${s.student}</td>
                                <td class="p-2 border">${s.class}</td>
                                <td class="p-2 border">${s.academic_year}</td>
                                <td class="p-2 border text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold ${typeClass}">
                                        ${s.scholarship_type === 'full' ? 'Full' : 'Partial'}
                                    </span>
                                </td>
                                <td class="p-2 border text-right text-gray-600 line-through">${formatTSh(s.original_amount)}</td>
                                <td class="p-2 border text-right text-red-600 font-bold">${formatTSh(s.forgiven_amount)}</td>
                                <td class="p-2 border text-right text-green-600 font-bold">${formatTSh(s.remaining_amount)}</td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                            <p class="text-xs text-gray-600 mt-2">
                                <strong>Note:</strong> Scholarship amounts are recorded separately and do not appear in cash/bank ledger entries.
                                If the organization later pays the scholarship amount, it will be added as a normal deposit.
                            </p>
                        </div>
                    `;
                }

                html += `
                        <div class="mt-4 text-center">
                `;

                // Add date params to PDF URL if filter is applied
                let pdfUrl = `${API_BASE}/ledgers/particular/${particularId}/pdf`;
                let pdfButtonText = '📄 Download PDF (All Data)';

                if (fromDate && toDate) {
                    pdfUrl += `?from_date=${fromDate}&to_date=${toDate}`;
                    pdfButtonText = '📄 Download Filtered PDF';
                }

                html += `
                            <a href="${pdfUrl}" target="_blank" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded inline-flex items-center transition">
                                ${pdfButtonText}
                            </a>
                `;

                if (fromDate && toDate) {
                    html += `
                            <p class="text-xs text-gray-600 mt-2">
                                PDF will include data from ${fromDate} to ${toDate}
                            </p>
                    `;
                }

                html += `
                        </div>
                    </div>
                `;

                document.getElementById('particularDetailsSection').innerHTML = html;
            } catch (error) {
                alert('Error loading particular details: ' + error.message);
            }
        }

        function applyParticularDateFilter(particularId) {
            const fromDate = document.getElementById('particularFromDate').value;
            const toDate = document.getElementById('particularToDate').value;

            if (fromDate && toDate) {
                loadParticularDetails(particularId, fromDate, toDate);
            } else {
                alert('Please select both From and To dates');
            }
        }
    </script>
@endpush
