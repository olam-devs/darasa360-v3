@extends('layouts.accountant')

@section('title', 'Dashboard — Darasa Finance')
@section('page_title', 'Dashboard')

@section('topbar_actions')
    <span class="hidden text-xs text-slate-500 sm:inline">{{ \Carbon\Carbon::now()->format('l, M j, Y') }}</span>
@endsection

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        /* Skeleton Loading Animation */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        .skeleton {
            animation: shimmer 2s infinite linear;
            background: linear-gradient(to right, #f0f0f0 4%, #e0e0e0 25%, #f0f0f0 36%);
            background-size: 1000px 100%;
        }

        .fade-in {
            animation: fadeIn 0.35s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .hover-lift {
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .hover-lift:hover {
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.06);
        }

        /* Hide scrollbar but keep functionality */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
@endpush

@section('content')
    <div class="space-y-8">
        <!-- Welcome -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm fade-in">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 md:text-2xl">Overview</h2>
                    <p class="mt-1 text-sm text-slate-600">School finance snapshot and quick entry points.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="window.location.reload()"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </button>
                    <a href="{{ route('accountant.fee-entry') }}"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        New fee entry
                    </a>
                </div>
            </div>
        </div>

        <!-- Advanced Analytics Section -->
        <div class="fade-in mb-6 md:mb-10">
            <div class="mb-4 flex items-center gap-3 md:mb-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-slate-900 md:text-xl">Analytics and reports</h2>
            </div>

            <!-- Time Period Selector -->
            <div class="mb-4 flex flex-wrap gap-2 md:mb-6 md:gap-3">
                <button type="button" onclick="loadAnalytics('today')" id="btn-today" class="analytics-btn rounded-lg border border-blue-700 bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 md:px-6 md:py-3 md:text-base">
                    Today
                </button>
                <button type="button" onclick="loadAnalytics('weekly')" id="btn-weekly" class="analytics-btn rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 md:px-6 md:py-3 md:text-base">
                    This Week
                </button>
                <button type="button" onclick="loadAnalytics('monthly')" id="btn-monthly" class="analytics-btn rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 md:px-6 md:py-3 md:text-base">
                    This Month
                </button>
                <button type="button" onclick="loadAnalytics('yearly')" id="btn-yearly" class="analytics-btn rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 md:px-6 md:py-3 md:text-base">
                    This Year
                </button>
                <button type="button" onclick="showCustomDatePicker()" id="btn-custom" class="analytics-btn rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 md:px-6 md:py-3 md:text-base">
                    Custom Date
                </button>
            </div>

            <!-- Custom Date Range Picker (Initially Hidden) -->
            <div id="custom-date-picker" class="mb-4 hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:mb-6">
                <h3 class="mb-3 text-base font-semibold text-slate-900">Custom date range</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-600">From date</label>
                        <input type="text" id="custom-from-date" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Start date">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-600">To date</label>
                        <input type="text" id="custom-to-date" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="End date">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="button" onclick="applyCustomDateRange()" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Apply
                        </button>
                        <button type="button" onclick="hideCustomDatePicker()" class="flex-1 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Analytics Summary Cards -->
            <div id="analytics-cards" class="mb-6 grid grid-cols-1 gap-3 min-[500px]:grid-cols-2 xl:grid-cols-5 md:gap-4">
                <!-- Skeleton loaders will be replaced -->
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
            </div>

            <!-- Charts Row: Line Graph and Books Pie Chart -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-900 md:text-lg">Fee collection trend</h3>
                    <div id="chart-container-1" class="relative" style="height: 250px;">
                        <canvas id="collectionChart"></canvas>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
                    <h3 class="mb-4 text-base font-semibold text-slate-900 md:text-lg">Books distribution (collections)</h3>
                    <div id="chart-container-2" class="relative" style="height: 250px;">
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                    <div id="books-legend" class="mt-4 grid grid-cols-2 gap-2 text-xs"></div>
                </div>
            </div>

            <!-- Particulars Bar Graph: Expected vs Collected -->
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900 md:text-xl">Fees by particular (expected vs collected)</h3>
                    <div class="flex gap-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="select-all-particulars" onchange="toggleAllParticulars()" checked>
                            <span class="text-sm">Select All</span>
                        </label>
                    </div>
                </div>
                <div id="particular-checkboxes" class="flex flex-wrap gap-3 mb-4">
                    <!-- Particular checkboxes will be populated here -->
                </div>
                <div id="chart-container-particulars" class="relative" style="height: 350px;">
                    <canvas id="particularsChart"></canvas>
                </div>
            </div>

            <!-- Student Completion Summary -->
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-xl font-semibold text-slate-900">Student payment completion</h3>
                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm text-slate-600">Total students</p>
                        <p class="text-2xl font-semibold tabular-nums text-slate-900" id="total-students">0</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm text-slate-600">Completed</p>
                        <p class="text-2xl font-semibold tabular-nums text-slate-900" id="completed-students">0</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm text-slate-600">Incomplete</p>
                        <p class="text-2xl font-semibold tabular-nums text-slate-900" id="incomplete-students">0</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm text-slate-600">Completion rate</p>
                        <p class="text-2xl font-semibold tabular-nums text-slate-900" id="completion-rate">0%</p>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Class</label>
                        <select id="class-filter" class="px-4 py-2 border border-gray-300 rounded-lg w-full" onchange="filterClassStats()">
                            <option value="">All Classes</option>
                        </select>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Search Student</label>
                        <input type="text" id="student-search" placeholder="Search by name or registration number..." class="px-4 py-2 border border-gray-300 rounded-lg w-full" oninput="showAutocomplete()" autocomplete="off">
                        <div id="autocomplete-dropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Custom Date Range (Collected)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="status-from-date" placeholder="From (YYYY-MM-DD)" class="px-4 py-2 border border-gray-300 rounded-lg w-full">
                            <input type="text" id="status-to-date" placeholder="To (YYYY-MM-DD)" class="px-4 py-2 border border-gray-300 rounded-lg w-full">
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button type="button" class="px-4 py-2 rounded-lg bg-blue-600 text-sm font-semibold text-white transition hover:bg-blue-700" onclick="applyStatusDateFilter()">Apply</button>
                            <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition" onclick="clearStatusDateFilter()">Clear</button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Expected stays from assignments; Collected is filtered by receipts within the date range.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Class</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Completed/Total Students</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Expected Amount</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Collected Amount</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Collection Rate</th>
                            </tr>
                        </thead>
                        <tbody id="class-stats-table">
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Loading class statistics...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="fade-in">
            <div class="mb-4 flex items-center gap-3 md:mb-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-slate-900 md:text-xl">Quick actions</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4 lg:grid-cols-5">
                <a href="{{ route('accountant.fee-entry') }}" class="rounded-xl border border-slate-200 bg-white p-4 hover-lift md:p-5">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3.866 0-7 1.343-7 3v2h14v-2c0-1.657-3.134-3-7-3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10V8a7 7 0 0114 0v2"/></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900 md:text-base">Record fee</h3>
                    <p class="mt-0.5 hidden text-xs text-slate-500 md:block">New receipt</p>
                </a>
                <a href="{{ route('accountant.ledgers') }}" class="rounded-xl border border-slate-200 bg-white p-4 hover-lift md:p-5">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900 md:text-base">Ledgers</h3>
                    <p class="mt-0.5 hidden text-xs text-slate-500 md:block">Reports</p>
                </a>
                <a href="{{ route('accountant.sms') }}" class="rounded-xl border border-slate-200 bg-white p-4 hover-lift md:p-5">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900 md:text-base">SMS</h3>
                    <p class="mt-0.5 hidden text-xs text-slate-500 md:block">Messaging</p>
                </a>
                <a href="{{ route('accountant.overdue') }}" class="rounded-xl border border-slate-200 bg-white p-4 hover-lift md:p-5">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900 md:text-base">Overdues</h3>
                    <p class="mt-0.5 hidden text-xs text-slate-500 md:block">Follow-up</p>
                </a>
                <a href="{{ route('accountant.invoices-page') }}" class="rounded-xl border border-slate-200 bg-white p-4 hover-lift md:p-5">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-900 md:text-base">Invoices</h3>
                    <p class="mt-0.5 hidden text-xs text-slate-500 md:block">PDFs</p>
                </a>
            </div>
        </div>

        <!-- System modules -->
        <div class="fade-in">
            <div class="mb-4 flex items-center gap-3 md:mb-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-slate-900 md:text-xl">All modules</h2>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 md:gap-4">
                <a href="{{ route('accountant.books') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-blue-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Books</h3>
                            <p class="text-xs text-slate-500">Account books</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.particulars') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-emerald-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Particulars</h3>
                            <p class="text-xs text-slate-500">Fee types</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.fee-entry') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-violet-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Fee entry</h3>
                            <p class="text-xs text-slate-500">Receipts</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.ledgers') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-orange-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Ledgers</h3>
                            <p class="text-xs text-slate-500">Class and student</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.particular-ledger') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-blue-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Particular ledger</h3>
                            <p class="text-xs text-slate-500">By fee type</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.overdue') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-red-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Overdue</h3>
                            <p class="text-xs text-slate-500">Late fees</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.suspense') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-amber-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Suspense</h3>
                            <p class="text-xs text-slate-500">Unallocated</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.payroll') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-yellow-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-9a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Payroll</h3>
                            <p class="text-xs text-slate-500">Staff pay</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.expenses') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-rose-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Expenses</h3>
                            <p class="text-xs text-slate-500">School spend</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.bank-api') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-emerald-600 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3.866 0-7 1.343-7 3v7h14v-7c0-1.657-3.134-3-7-3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11V9a7 7 0 0114 0v2"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Bank integration</h3>
                            <p class="text-xs text-slate-500">API & automation</p>
                            <span class="mt-2 inline-block rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">New</span>
                        </div>
                    </div>
                </a>
                <div class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-indigo-500 bg-white p-4 md:p-5">
                    <div class="mb-3 flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">SMS</h3>
                            <p class="text-xs text-slate-500">Messaging</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <a href="{{ route('accountant.sms') }}" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 md:text-sm">Send SMS</a>
                        <a href="{{ route('accountant.phone-numbers') }}" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 md:text-sm">Phone numbers</a>
                        <a href="{{ route('accountant.sms-logs') }}" class="block rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-800 hover:bg-slate-100 md:text-sm">SMS logs</a>
                    </div>
                </div>
                <a href="{{ route('accountant.invoices-page') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-purple-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Invoices</h3>
                            <p class="text-xs text-slate-500">PDFs</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.classes') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-sky-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Classes</h3>
                            <p class="text-xs text-slate-500">Structure</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.students') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-blue-600 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Students</h3>
                            <p class="text-xs text-slate-500">Roster & import</p>
                            <span class="mt-2 inline-block rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">Bulk import</span>
                        </div>
                    </div>
                </a>
                <a href="{{ route('students.promotion-page') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-fuchsia-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Promotion</h3>
                            <p class="text-xs text-slate-500">Next class</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('accountant.settings') }}" class="hover-lift rounded-xl border border-slate-200 border-l-4 border-l-slate-500 bg-white p-4 md:p-5">
                    <div class="flex gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 .6 1.65 1.65 0 00-.33 1.82A2 2 0 1110 21.4a1.65 1.65 0 00-1-.6 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-.6-1 1.65 1.65 0 00-1.82-.33A2 2 0 112.6 10a1.65 1.65 0 00.6-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-.6 1.65 1.65 0 00.33-1.82A2 2 0 1114 2.6a1.65 1.65 0 001 .6 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9c.24.31.42.65.52 1.02.1.37.1.76 0 1.13-.1.37-.28.71-.52 1.02z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 md:text-base">Settings</h3>
                            <p class="text-xs text-slate-500">School profile</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        let collectionChart, paymentMethodsChart, particularsChart;
        let currentPeriod = 'today';
        let chartsInitialized = false;
        let allClassStats = [];
        let allParticularStats = [];
        let selectedParticulars = new Set();

        // Initialize on page load with delay to prevent blackout
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts first
            initCharts();

            // Initialize Flatpickr for custom date range
            flatpickr("#custom-from-date", {
                dateFormat: "Y-m-d",
                maxDate: "today"
            });

            flatpickr("#custom-to-date", {
                dateFormat: "Y-m-d",
                maxDate: "today"
            });

            // Student Payment Completion Status date range filter
            flatpickr("#status-from-date", {
                dateFormat: "Y-m-d",
                maxDate: "today"
            });
            flatpickr("#status-to-date", {
                dateFormat: "Y-m-d",
                maxDate: "today"
            });

            // Analytics will be loaded automatically after charts are initialized
        });

        function collectionProgressBar(rate, heightClass) {
            const pct = Math.min(100, Math.max(0, parseFloat(rate) || 0));
            const h = heightClass || 'h-2.5';
            return `
                <div class="flex-1 overflow-hidden rounded-full bg-green-100 ${h === 'h-2' ? '' : ''}">
                    <div class="${h} rounded-full darasa-collection-fill transition-all duration-300" style="width: ${pct}%; min-width: ${pct > 0 ? '4px' : '0'}; background-color: #22c55e;"></div>
                </div>
            `;
        }

        function formatPeriodLabel(period, data) {
            if (period === 'custom' && data?.date_from && data?.date_to) {
                return `${data.date_from} to ${data.date_to}`;
            }
            const labels = {
                today: 'Today',
                weekly: 'This week',
                monthly: 'This month',
                yearly: 'This year',
                custom: 'Custom range',
            };
            return labels[period] || period;
        }

        function loadAnalytics(period) {
            currentPeriod = period;
            console.log('Loading analytics for period:', period);

            // Hide custom date picker when switching to preset periods
            const customPicker = document.getElementById('custom-date-picker');
            if (customPicker) {
                customPicker.classList.add('hidden');
            }
            // Update active button
            document.querySelectorAll('.analytics-btn').forEach(btn => {
                btn.classList.remove('border-blue-700', 'bg-blue-600', 'text-white', 'shadow-sm');
                btn.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
            });
            const activeBtn = document.getElementById('btn-' + period);
            if (activeBtn) {
                activeBtn.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
                activeBtn.classList.add('border-blue-700', 'bg-blue-600', 'text-white', 'shadow-sm');
            }

            // Show skeleton while loading
            showSkeletonCards();

            // Fetch analytics data
            axios.get('/api/analytics/' + period)
                .then(response => {
                    console.log('Analytics data received for', period, ':', response.data);
                    setTimeout(() => updateAnalyticsUI(response.data), 300);
                })
                .catch(error => {
                    console.error('Error loading analytics for', period, ':', error);
                    setTimeout(() => showAnalyticsLoadError(period, error), 300);
                });
        }

        function showSkeletonCards() {
            document.getElementById('analytics-cards').innerHTML = `
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
                <div class="skeleton rounded-xl h-32 md:h-36"></div>
            `;
        }

        function showAnalyticsLoadError(period, error) {
            const message = error?.response?.data?.error || error?.message || 'Failed to load analytics.';
            renderAnalyticsCards({
                collected: 0,
                expected: 0,
                overdue: 0,
                students: 0,
                rate: 0,
                books_balance: 0,
                scholarships: 0,
            }, period);
            allClassStats = [];
            displayClassStats([]);
            document.getElementById('total-students').textContent = '0';
            document.getElementById('completed-students').textContent = '0';
            document.getElementById('incomplete-students').textContent = '0';
            document.getElementById('completion-rate').textContent = '0%';
            const tbody = document.getElementById('class-stats-table');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-red-600">
                            <p class="font-semibold mb-1">Could not load class statistics</p>
                            <p class="text-sm text-gray-600">${message}</p>
                        </td>
                    </tr>
                `;
            }
        }

        function getStatusDateRangeParams() {
            const from = (document.getElementById('status-from-date')?.value || '').trim();
            const to = (document.getElementById('status-to-date')?.value || '').trim();
            if (!from && !to) return null;
            return { from_date: from || null, to_date: to || null };
        }

        function applyStatusDateFilter() {
            // Reset class/student filters but keep values in UI
            const params = getStatusDateRangeParams();
            if (!params || (!params.from_date && !params.to_date)) {
                filterClassStats();
                return;
            }
            if (!params.from_date || !params.to_date) {
                alert('Please select both From and To dates.');
                return;
            }
            loadStatusClassStats(params.from_date, params.to_date);
        }

        function clearStatusDateFilter() {
            const fromEl = document.getElementById('status-from-date');
            const toEl = document.getElementById('status-to-date');
            if (fromEl) fromEl.value = '';
            if (toEl) toEl.value = '';
            // Restore stats from the current period analytics payload if available
            loadAnalytics(currentPeriod || 'today');
        }

        function loadStatusClassStats(fromDate, toDate) {
            const tbody = document.getElementById('class-stats-table');
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Loading class statistics for selected date range...</td>
                </tr>
            `;

            axios.get('/api/analytics/class-stats', { params: { from_date: fromDate, to_date: toDate } })
                .then(response => {
                    const stats = response.data?.class_stats || [];
                    allClassStats = stats;
                    displayClassStats(allClassStats);
                    populateClassFilter(allClassStats);

                    const totalStudents = allClassStats.reduce((sum, stat) => sum + stat.total_students, 0);
                    const completedStudents = allClassStats.reduce((sum, stat) => sum + stat.completed_students, 0);
                    const incompleteStudents = totalStudents - completedStudents;
                    const overallRate = totalStudents > 0 ? ((completedStudents / totalStudents) * 100).toFixed(1) : 0;

                    document.getElementById('total-students').textContent = totalStudents;
                    document.getElementById('completed-students').textContent = completedStudents;
                    document.getElementById('incomplete-students').textContent = incompleteStudents;
                    document.getElementById('completion-rate').textContent = overallRate + '%';
                    filterClassStats();
                })
                .catch(error => {
                    console.error('Error loading class stats for date range:', error);
                    const message = error?.response?.data?.message || error?.response?.data?.error || error?.message || 'Failed to load.';
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-red-600">
                                <p class="font-semibold mb-1">Could not load class statistics for selected date range</p>
                                <p class="text-sm text-gray-600">${message}</p>
                            </td>
                        </tr>
                    `;
                });
        }

        function updateAnalyticsUI(data) {
            // Calculate collection rate
            const totalExpected = data.summary.total_expected || 0;
            const totalCollections = data.summary.total_collections || 0;
            const booksTotalBalance = data.summary.books_total_balance || 0;
            const scholarshipsTotal = data.summary.total_scholarships || 0;
            const collectionRate = totalExpected > 0
                ? ((totalCollections / totalExpected) * 100).toFixed(1)
                : 0;

            renderAnalyticsCards({
                collected: totalCollections,
                expected: totalExpected,
                overdue: data.summary.outstanding_balance || 0,
                students: data.summary.active_students || 0,
                rate: collectionRate,
                books_balance: booksTotalBalance,
                scholarships: scholarshipsTotal,
            }, formatPeriodLabel(data.period || currentPeriod, data));

            // Keep class "Collected" filter aligned with the active analytics period
            if (data.date_from && data.date_to) {
                const fromEl = document.getElementById('status-from-date');
                const toEl = document.getElementById('status-to-date');
                if (fromEl) fromEl.value = data.date_from;
                if (toEl) toEl.value = data.date_to;
            }

            // Update class statistics
            if (data.class_stats && data.class_stats.length > 0) {
                allClassStats = data.class_stats;
                displayClassStats(allClassStats);
                populateClassFilter(allClassStats);

                // Calculate totals for summary cards
                const totalStudents = allClassStats.reduce((sum, stat) => sum + stat.total_students, 0);
                const completedStudents = allClassStats.reduce((sum, stat) => sum + stat.completed_students, 0);
                const incompleteStudents = totalStudents - completedStudents;
                const overallRate = totalStudents > 0 ? ((completedStudents / totalStudents) * 100).toFixed(1) : 0;

                document.getElementById('total-students').textContent = totalStudents;
                document.getElementById('completed-students').textContent = completedStudents;
                document.getElementById('incomplete-students').textContent = incompleteStudents;
                document.getElementById('completion-rate').textContent = overallRate + '%';
            } else {
                // No class stats available
                allClassStats = [];
                displayClassStats([]);
                document.getElementById('total-students').textContent = '0';
                document.getElementById('completed-students').textContent = '0';
                document.getElementById('incomplete-students').textContent = '0';
                document.getElementById('completion-rate').textContent = '0%';
            }

            // Update particular statistics
            if (data.particulars_data && data.particulars_data.length > 0) {
                allParticularStats = data.particulars_data;
                populateParticularCheckboxes(allParticularStats);
            } else {
                allParticularStats = [];
                populateParticularCheckboxes([]);
            }

            // Update charts with real data
            if (chartsInitialized) {
                updateChartsData(data);
            }
        }

        function displayClassStats(stats) {
            const tbody = document.getElementById('class-stats-table');

            if (stats.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center">
                            <div class="text-gray-500">
                                <p class="font-semibold mb-2">No class data available</p>
                                <p class="text-sm">Students need to be assigned to classes (Grade 1-6, Form 1-4) to view class-wise statistics.</p>
                                <a href="{{ route('accountant.students') }}" class="mt-3 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm text-white transition hover:bg-blue-700">
                                    Manage Students
                                </a>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = stats.map(stat => {
                const incompleteStudents = stat.total_students - stat.completed_students;
                return `
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3 font-semibold">${stat.class_name}</td>
                    <td class="px-4 py-3">
                        <span class="font-bold text-slate-900">${stat.completed_students}</span> /
                        <span class="text-gray-600">${stat.total_students}</span>
                        <span class="text-xs text-gray-500">(${incompleteStudents} incomplete)</span>
                    </td>
                    <td class="px-4 py-3 font-semibold">TSH ${Math.round(stat.expected_amount).toLocaleString()}</td>
                    <td class="px-4 py-3 font-semibold text-slate-900">TSH ${Math.round(stat.collected_amount).toLocaleString()}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            ${collectionProgressBar(stat.collection_rate, 'h-2.5')}
                            <span class="font-bold text-sm text-green-700">${stat.collection_rate}%</span>
                        </div>
                    </td>
                </tr>
            `}).join('');
        }

        function populateClassFilter(stats) {
            const filter = document.getElementById('class-filter');
            const currentValue = filter.value;

            filter.innerHTML = '<option value="">All Classes</option>';
            stats.forEach(stat => {
                filter.innerHTML += `<option value="${stat.class_name}">${stat.class_name}</option>`;
            });

            if (currentValue) {
                filter.value = currentValue;
            }
        }

        function filterClassStats() {
            const selectedClass = document.getElementById('class-filter').value;
            const searchTerm = document.getElementById('student-search').value.toLowerCase().trim();

            let filteredStats = allClassStats;

            // Filter by class if selected
            if (selectedClass) {
                filteredStats = filteredStats.filter(stat => stat.class_name === selectedClass);
            }

            // Apply search filter if there's a search term
            if (searchTerm) {
                // This will filter the stats display, but for student search we'll need more data
                // For now, just show stats that match
                displayClassStats(filteredStats);
            } else {
                displayClassStats(filteredStats);
            }
        }

        let autocompleteTimeout;

        function showAutocomplete() {
            const searchTerm = document.getElementById('student-search').value.trim();
            const dropdown = document.getElementById('autocomplete-dropdown');

            // Clear previous timeout
            clearTimeout(autocompleteTimeout);

            if (!searchTerm || searchTerm.length < 2) {
                dropdown.classList.add('hidden');
                dropdown.innerHTML = '';
                if (!searchTerm) {
                    filterClassStats();
                }
                return;
            }

            // Debounce the API call
            autocompleteTimeout = setTimeout(() => {
                axios.get('/api/students/search?q=' + encodeURIComponent(searchTerm))
                    .then(response => {
                        if (response.data && response.data.length > 0) {
                            displayAutocomplete(response.data);
                        } else {
                            dropdown.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">No students found</div>';
                            dropdown.classList.remove('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching autocomplete:', error);
                        dropdown.classList.add('hidden');
                    });
            }, 300);
        }

        function displayAutocomplete(students) {
            const dropdown = document.getElementById('autocomplete-dropdown');

            dropdown.innerHTML = students.map(student => `
                <div class="cursor-pointer border-b px-4 py-3 last:border-b-0 hover:bg-slate-50" onclick="selectStudent(${student.id}, '${student.name.replace(/'/g, "\\'")}', '${student.student_reg_no}')">
                    <div class="font-semibold text-sm">${student.name}</div>
                    <div class="text-xs text-gray-500">Reg: ${student.student_reg_no} | Class: ${student.class}</div>
                </div>
            `).join('');

            dropdown.classList.remove('hidden');
        }

        function selectStudent(studentId, studentName, studentRegNo) {
            const searchInput = document.getElementById('student-search');
            const dropdown = document.getElementById('autocomplete-dropdown');

            // Set the search input value
            searchInput.value = studentName + ' (' + studentRegNo + ')';

            // Hide dropdown
            dropdown.classList.add('hidden');

            // Fetch and display student's payment details
            searchStudentById(studentId);
        }

        function searchStudentById(studentId) {
            const tbody = document.getElementById('class-stats-table');

            // Show loading state
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                        <p class="font-semibold mb-2">Loading student payment details...</p>
                    </td>
                </tr>
            `;

            // Fetch student payment summary
            const params = getStatusDateRangeParams();
            axios.get('/api/students/' + studentId + '/payment-summary', { params: params || {} })
                .then(response => {
                    const student = response.data;

                    tbody.innerHTML = `
                        <tr class="bg-slate-50">
                            <td colspan="5" class="px-6 py-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Student Name</p>
                                        <p class="text-lg font-bold text-gray-800">${student.name}</p>
                                        <p class="text-sm text-gray-600">Reg: ${student.student_reg_no}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Class</p>
                                        <p class="text-lg font-bold text-slate-900">${student.class}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Assignments</p>
                                        <p class="text-lg font-bold">
                                            <span class="text-slate-900">${student.completed_assignments}</span>
                                            <span class="text-gray-400">/</span>
                                            <span class="text-gray-600">${student.total_assignments}</span>
                                        </p>
                                        <p class="text-xs text-gray-500">Completed / Total</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Expected Amount</p>
                                        <p class="text-lg font-bold text-gray-700">TSH ${student.total_expected.toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Collected Amount</p>
                                        <p class="text-lg font-bold text-slate-900">TSH ${student.total_collected.toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Collection Rate</p>
                                        <div class="flex items-center gap-3">
                                            ${collectionProgressBar(student.collection_rate, 'h-3')}
                                            <span class="text-lg font-bold text-green-700">${student.collection_rate}%</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                })
                .catch(error => {
                    console.error('Error fetching student payment summary:', error);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-red-500">
                                <p class="font-semibold mb-2">Error loading student details</p>
                                <p class="text-sm">${error.response?.data?.error || error.message}</p>
                            </td>
                        </tr>
                    `;
                });
        }

        // Hide autocomplete when clicking outside
        document.addEventListener('click', function(event) {
            const searchInput = document.getElementById('student-search');
            const dropdown = document.getElementById('autocomplete-dropdown');

            if (searchInput && dropdown && !searchInput.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        function displayStudentSearchResults(students) {
            const tbody = document.getElementById('class-stats-table');

            tbody.innerHTML = students.map(student => `
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3 font-semibold">${student.name}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs text-gray-500">Reg: ${student.student_reg_no || 'N/A'}</span><br>
                        <span class="text-xs text-gray-500">Class: ${student.class || 'Not Assigned'}</span>
                    </td>
                    <td class="px-4 py-3 font-semibold">TSH ${(student.total_expected || 0).toLocaleString()}</td>
                    <td class="px-4 py-3 font-semibold text-slate-900">TSH ${(student.total_collected || 0).toLocaleString()}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            ${collectionProgressBar(student.collection_rate || 0, 'h-2.5')}
                            <span class="font-bold text-sm text-green-700">${student.collection_rate || 0}%</span>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function populateParticularCheckboxes(stats) {
            const container = document.getElementById('particular-checkboxes');
            selectedParticulars.clear();

            if (stats.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">No particulars data available</p>';
                return;
            }

            container.innerHTML = stats.map(stat => {
                selectedParticulars.add(stat.particular_name);
                return `
                    <label class="flex items-center gap-2 bg-gray-100 px-3 py-2 rounded">
                        <input type="checkbox" class="particular-checkbox" value="${stat.particular_name}" onchange="updateParticularsChart()" checked>
                        <span class="text-sm font-semibold">${stat.particular_name}</span>
                    </label>
                `;
            }).join('');

            if (chartsInitialized) {
                updateParticularsChart();
            }
        }

        function toggleAllParticulars() {
            const selectAll = document.getElementById('select-all-particulars').checked;
            const checkboxes = document.querySelectorAll('.particular-checkbox');

            selectedParticulars.clear();
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll;
                if (selectAll) {
                    selectedParticulars.add(checkbox.value);
                }
            });

            updateParticularsChart();
        }

        function updateParticularsChart() {
            const checkboxes = document.querySelectorAll('.particular-checkbox:checked');
            selectedParticulars.clear();
            checkboxes.forEach(checkbox => selectedParticulars.add(checkbox.value));

            // Update select all checkbox state
            const allCheckboxes = document.querySelectorAll('.particular-checkbox');
            const selectAllCheckbox = document.getElementById('select-all-particulars');
            selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;

            if (particularsChart && allParticularStats.length > 0) {
                const filteredStats = allParticularStats.filter(stat => selectedParticulars.has(stat.particular_name));

                particularsChart.data.labels = filteredStats.map(s => s.particular_name);
                particularsChart.data.datasets[0].data = filteredStats.map(s => s.expected);
                particularsChart.data.datasets[1].data = filteredStats.map(s => s.collected);
                particularsChart.update();
            }
        }

        function renderAnalyticsCards(data, period) {
            const fmt = (n) => {
                const v = Math.round(Number(n) || 0);
                return 'TSH ' + v.toLocaleString('en-TZ');
            };
            const scholarshipsNote = data.scholarships
                ? ` · Scholarships: ${fmt(data.scholarships)}`
                : '';
            const cardsHTML = `
                <div class="rounded-xl border border-slate-200 bg-white p-4 fade-in hover-lift md:p-5">
                    <p class="text-xs font-medium text-slate-500 md:text-sm">Total fee collected</p>
                    <p class="mt-2 break-words text-base font-semibold tabular-nums leading-snug text-slate-900 sm:text-lg">${fmt(data.collected)}</p>
                    <p class="mt-1 text-xs text-slate-500">${period}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 fade-in hover-lift md:p-5">
                    <p class="text-xs font-medium text-slate-500 md:text-sm">Expected fees</p>
                    <p class="mt-2 break-words text-base font-semibold tabular-nums leading-snug text-slate-900 sm:text-lg">${fmt(data.expected)}</p>
                    <p class="mt-1 break-words text-xs text-slate-500">${period}${scholarshipsNote}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 fade-in hover-lift md:p-5">
                    <p class="text-xs font-medium text-slate-500 md:text-sm">Total overdue</p>
                    <p class="mt-2 break-words text-base font-semibold tabular-nums leading-snug text-slate-900 sm:text-lg">${fmt(data.overdue)}</p>
                    <p class="mt-1 text-xs text-slate-500">${data.students} students</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 fade-in hover-lift md:p-5">
                    <p class="text-xs font-medium text-slate-500 md:text-sm">Collection rate</p>
                    <p class="mt-2 text-base font-semibold tabular-nums text-slate-900 sm:text-lg">${data.rate}%</p>
                    <p class="mt-1 text-xs text-slate-500">${period}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 fade-in hover-lift md:p-5">
                    <p class="text-xs font-medium text-slate-500 md:text-sm">Total balance in books</p>
                    <p class="mt-2 break-words text-base font-semibold tabular-nums leading-snug text-slate-900 sm:text-lg">${fmt(data.books_balance || 0)}</p>
                    <p class="mt-1 text-xs text-slate-500">Cash and bank balances</p>
                </div>
            `;

            document.getElementById('analytics-cards').innerHTML = cardsHTML;
        }

        function initCharts() {
            if (chartsInitialized) return;

            // Dynamically load Chart.js
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = function() {
                createCharts();
                chartsInitialized = true;
                // Load analytics after charts are ready
                loadAnalytics('today');
            };
            document.head.appendChild(script);
        }

        function createCharts() {
            // Collection Trend Chart
            const ctxCollection = document.getElementById('collectionChart').getContext('2d');
            collectionChart = new Chart(ctxCollection, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Collected',
                        data: [],
                        borderColor: 'rgb(37, 99, 235)',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgb(37, 99, 235)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) return 'TSH ' + (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return 'TSH ' + (value / 1000).toFixed(0) + 'K';
                                    return 'TSH ' + value;
                                }
                            }
                        }
                    }
                }
            });

            // Books Distribution Chart
            const ctxPayment = document.getElementById('paymentMethodsChart').getContext('2d');
            paymentMethodsChart = new Chart(ctxPayment, {
                type: 'doughnut',
                data: {
                    labels: ['Loading...'],
                    datasets: [{
                        data: [1],
                        backgroundColor: [
                            'rgb(37, 99, 235)',
                            'rgb(16, 185, 129)',
                            'rgb(217, 119, 6)',
                            'rgb(124, 58, 237)',
                            'rgb(8, 145, 178)',
                            'rgb(234, 88, 12)',
                            'rgb(99, 102, 241)',
                            'rgb(236, 72, 153)'
                        ],
                        borderWidth: 3,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += 'TSH ' + context.parsed.toLocaleString();
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            // Particulars Bar Chart (Expected vs Collected by Particular)
            const ctxParticulars = document.getElementById('particularsChart').getContext('2d');
            particularsChart = new Chart(ctxParticulars, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Expected Amount',
                            data: [],
                            backgroundColor: 'rgba(99, 102, 241, 0.65)',
                            borderColor: 'rgb(99, 102, 241)',
                            borderWidth: 2
                        },
                        {
                            label: 'Collected Amount',
                            data: [],
                            backgroundColor: 'rgb(34, 197, 94)',
                            borderColor: 'rgb(22, 163, 74)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += 'TSH ' + context.parsed.y.toLocaleString();
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'TSH ' + (value / 1000000).toFixed(1) + 'M';
                                    } else if (value >= 1000) {
                                        return 'TSH ' + (value / 1000).toFixed(0) + 'K';
                                    }
                                    return 'TSH ' + value;
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        }

        function updateChartsData(data) {
            console.log('Updating charts with data:', data);

            // Update collection trend chart - destroy and recreate for reliable redraw
            if (collectionChart && data.collection_trend) {
                const labels = data.collection_trend.map(d => d.label);
                const amounts = data.collection_trend.map(d => parseFloat(d.amount) || 0);
                const maxAmount = Math.max(...amounts, 100); // Minimum 100 for scale

                console.log('Line graph update:', { points: labels.length, max: maxAmount, amounts: amounts });

                // Destroy and recreate for clean redraw
                const canvas = document.getElementById('collectionChart');
                collectionChart.destroy();

                collectionChart = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Collections',
                            data: amounts,
                            borderColor: 'rgb(37, 99, 235)',
                            backgroundColor: 'rgba(37, 99, 235, 0.15)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: 'rgb(37, 99, 235)',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 600 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                padding: 12,
                                titleFont: { size: 14 },
                                bodyFont: { size: 13 },
                                callbacks: {
                                    label: ctx => 'TSH ' + ctx.parsed.y.toLocaleString()
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                min: 0,
                                suggestedMax: maxAmount * 1.2,
                                ticks: {
                                    callback: v => v >= 1000000 ? 'TSH ' + (v/1000000).toFixed(1) + 'M' : (v >= 1000 ? 'TSH ' + (v/1000).toFixed(0) + 'K' : 'TSH ' + v)
                                },
                                grid: { color: 'rgba(0,0,0,0.05)' }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
                console.log('Line chart recreated successfully');
            }

            // Update books distribution chart and legend
            if (paymentMethodsChart && data.books_distribution) {
                const colors = [
                    'rgb(37, 99, 235)',
                    'rgb(16, 185, 129)',
                    'rgb(217, 119, 6)',
                    'rgb(124, 58, 237)',
                    'rgb(8, 145, 178)',
                    'rgb(234, 88, 12)',
                    'rgb(99, 102, 241)',
                    'rgb(236, 72, 153)'
                ];

                if (data.books_distribution && data.books_distribution.length > 0) {
                    console.log('Updating pie chart with', data.books_distribution.length, 'books');
                    // Update chart data
                    paymentMethodsChart.data.labels = data.books_distribution.map(d => d.name);
                    paymentMethodsChart.data.datasets[0].data = data.books_distribution.map(d => d.amount);
                    paymentMethodsChart.data.datasets[0].backgroundColor = data.books_distribution.map((d, i) => colors[i % colors.length]);

                    // Calculate total for percentage
                    const total = data.books_distribution.reduce((sum, book) => sum + parseFloat(book.amount), 0);
                    console.log('Total for pie chart:', total);

                    // Update legend with percentages
                    const legendHTML = data.books_distribution.map((book, index) => {
                        const color = colors[index % colors.length];
                        const percentage = total > 0 ? ((parseFloat(book.amount) / total) * 100).toFixed(1) : 0;
                        return `
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: ${color}"></div>
                                <span class="text-gray-700 font-medium">${book.name}: ${percentage}%</span>
                            </div>
                        `;
                    }).join('');
                    document.getElementById('books-legend').innerHTML = legendHTML;
                } else {
                    // Show message when no book data is available
                    paymentMethodsChart.data.labels = ['No book transactions yet'];
                    paymentMethodsChart.data.datasets[0].data = [1];
                    paymentMethodsChart.data.datasets[0].backgroundColor = ['rgb(209, 213, 219)'];
                    document.getElementById('books-legend').innerHTML = '<p class="text-gray-500 text-sm col-span-2">No book data available</p>';
                }
                paymentMethodsChart.update();
            }

            // Update particulars chart
            if (particularsChart && data.particulars_data) {
                if (data.particulars_data.length > 0) {
                    const filteredStats = data.particulars_data.filter(stat =>
                        selectedParticulars.size === 0 || selectedParticulars.has(stat.particular_name)
                    );
                    particularsChart.data.labels = filteredStats.map(s => s.particular_name);
                    particularsChart.data.datasets[0].data = filteredStats.map(s => s.expected);
                    particularsChart.data.datasets[1].data = filteredStats.map(s => s.collected);
                } else {
                    particularsChart.data.labels = [];
                    particularsChart.data.datasets[0].data = [];
                    particularsChart.data.datasets[1].data = [];
                }
                particularsChart.update();
            }
        }

        // Custom date picker functions
        function showCustomDatePicker() {
            document.getElementById('custom-date-picker').classList.remove('hidden');

            // Update button states
            document.querySelectorAll('.analytics-btn').forEach(btn => {
                btn.classList.remove('border-blue-700', 'bg-blue-600', 'text-white', 'shadow-sm');
                btn.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
            });
            const customBtn = document.getElementById('btn-custom');
            customBtn.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
            customBtn.classList.add('border-blue-700', 'bg-blue-600', 'text-white', 'shadow-sm');
        }

        function hideCustomDatePicker() {
            document.getElementById('custom-date-picker').classList.add('hidden');
            document.getElementById('custom-from-date').value = '';
            document.getElementById('custom-to-date').value = '';

            // Reset to today view
            loadAnalytics('today');
        }

        function applyCustomDateRange() {
            const fromDate = document.getElementById('custom-from-date').value;
            const toDate = document.getElementById('custom-to-date').value;

            if (!fromDate || !toDate) {
                alert('Please select both start and end dates');
                return;
            }

            if (new Date(fromDate) > new Date(toDate)) {
                alert('Start date must be before end date');
                return;
            }

            currentPeriod = 'custom';

            // Update active button
            document.querySelectorAll('.analytics-btn').forEach(btn => {
                btn.classList.remove('border-blue-700', 'bg-blue-600', 'text-white', 'shadow-sm');
                btn.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
            });
            const customBtn = document.getElementById('btn-custom');
            if (customBtn) {
                customBtn.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
                customBtn.classList.add('border-blue-700', 'bg-blue-600', 'text-white', 'shadow-sm');
            }

            // Show skeleton while loading
            showSkeletonCards();

            // Fetch analytics data with custom date range
            axios.get('/api/analytics/custom', {
                params: {
                    from_date: fromDate,
                    to_date: toDate
                }
            })
            .then(response => {
                setTimeout(() => updateAnalyticsUI(response.data), 300);
            })
            .catch(error => {
                console.error('Error loading custom analytics:', error);
                setTimeout(() => showAnalyticsLoadError('custom', error), 300);
            });
        }
    </script>
@endpush
