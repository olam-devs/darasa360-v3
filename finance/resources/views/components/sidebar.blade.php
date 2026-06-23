<!-- Sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 z-50 h-full w-72 -translate-x-full border-r border-blue-100/80 bg-white shadow-xl transition-transform duration-300 lg:translate-x-0">
    <!-- Header -->
    <div class="flex items-center justify-between gap-3 border-b border-blue-100/80 bg-gradient-to-r from-blue-50/90 to-sky-50/50 px-5 py-4">
        <div class="min-w-0">
            <p class="text-xs font-medium text-blue-700">Darasa Finance</p>
            <p class="truncate text-sm font-semibold text-slate-900">Accountant</p>
        </div>
        <button type="button" onclick="toggleSidebar()"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200/80 bg-white text-blue-800 hover:bg-blue-50 lg:hidden">
            <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Sidebar Content -->
    <div class="p-4 overflow-y-auto h-[calc(100%-64px)]">
        <!-- Sidebar Categories -->
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="{{ route('accountant.dashboard') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                      {{ request()->routeIs('accountant.dashboard') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9v9a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4H9v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-9z" />
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- Finance Management -->
            <div class="sidebar-category">
                <button type="button" onclick="toggleCategory('finance')"
                        class="w-full flex items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-900 hover:bg-blue-50/90 transition">
                    <span>Finance</span>
                    <svg class="w-4 h-4 transform transition-transform {{ request()->routeIs('accountant.fee-entry', 'accountant.ledgers', 'accountant.particular-ledger', 'accountant.overdue', 'accountant.suspense', 'accountant.advance-payments', 'accountant.reconciliation') ? 'rotate-180' : '' }}" id="finance-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="finance-submenu" class="ml-3 mt-1 space-y-1 {{ request()->routeIs('accountant.fee-entry', 'accountant.ledgers', 'accountant.particular-ledger', 'accountant.overdue', 'accountant.suspense', 'accountant.advance-payments', 'accountant.reconciliation') ? '' : 'hidden' }}">
                    <a href="{{ route('accountant.fee-entry') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.fee-entry') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">Fee Entry</a>
                    <a href="{{ route('accountant.ledgers') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.ledgers') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">Ledgers</a>
                    <a href="{{ route('accountant.reconciliation') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.reconciliation') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">Reconciliation</a>
                    <a href="{{ route('accountant.advance-payments') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.advance-payments') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">Advance Payments</a>
                    <a href="{{ route('accountant.particular-ledger') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.particular-ledger') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">Particular Ledger</a>
                    <a href="{{ route('accountant.overdue') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.overdue') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">Overdue Payments</a>
                    <a href="{{ route('accountant.suspense') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.suspense') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">Suspense Accounts</a>
                </div>
            </div>

            <!-- Books & Accounting -->
            <div class="sidebar-category">
                <button type="button" onclick="toggleCategory('books')"
                        class="w-full flex items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-900 hover:bg-blue-50/90 transition">
                    <span>Books & Accounting</span>
                    <svg class="w-4 h-4 transform transition-transform {{ request()->routeIs('accountant.books', 'accountant.particulars') ? 'rotate-180' : '' }}" id="books-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="books-submenu" class="ml-3 mt-1 space-y-1 {{ request()->routeIs('accountant.books', 'accountant.particulars') ? '' : 'hidden' }}">
                    <a href="{{ route('accountant.books') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.books') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Books Management</a>
                    <a href="{{ route('accountant.particulars') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.particulars') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Particulars</a>
                </div>
            </div>

            <!-- Expenses & Payroll -->
            <div class="sidebar-category">
                <button type="button" onclick="toggleCategory('expenses')"
                        class="w-full flex items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-900 hover:bg-blue-50/90 transition">
                    <span>Expenses & Payroll</span>
                    <svg class="w-4 h-4 transform transition-transform {{ request()->routeIs('accountant.payroll', 'accountant.expenses') ? 'rotate-180' : '' }}" id="expenses-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="expenses-submenu" class="ml-3 mt-1 space-y-1 {{ request()->routeIs('accountant.payroll', 'accountant.expenses') ? '' : 'hidden' }}">
                    <a href="{{ route('accountant.payroll') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.payroll') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Payroll</a>
                    <a href="{{ route('accountant.expenses') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.expenses') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Expenses</a>
                </div>
            </div>

            <!-- Students -->
            <div class="sidebar-category">
                <button type="button" onclick="toggleCategory('students')"
                        class="w-full flex items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-900 hover:bg-blue-50/90 transition">
                    <span>Students</span>
                    <svg class="w-4 h-4 transform transition-transform {{ request()->routeIs('accountant.students', 'accountant.classes', 'students.promotion-page', 'accountant.invoices-page', 'accountant.student-profile') ? 'rotate-180' : '' }}" id="students-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="students-submenu" class="ml-3 mt-1 space-y-1 {{ request()->routeIs('accountant.students', 'accountant.classes', 'students.promotion-page', 'accountant.invoices-page', 'accountant.student-profile') ? '' : 'hidden' }}">
                    <a href="{{ route('accountant.students') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.students') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Student Management</a>
                    <a href="{{ route('accountant.student-profile') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.student-profile') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Student Profile</a>
                    <a href="{{ route('accountant.classes') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.classes') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Class Management</a>
                    <a href="{{ route('students.promotion-page') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('students.promotion-page') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Student Promotion</a>
                    <a href="{{ route('accountant.invoices-page') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.invoices-page') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Student Invoices</a>
                </div>
            </div>

            <!-- Communication -->
            <div class="sidebar-category">
                <button type="button" onclick="toggleCategory('communication')"
                        class="w-full flex items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-900 hover:bg-blue-50/90 transition">
                    <span>Communication</span>
                    <svg class="w-4 h-4 transform transition-transform {{ request()->routeIs('accountant.sms', 'accountant.phone-numbers', 'accountant.sms-logs') ? 'rotate-180' : '' }}" id="communication-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="communication-submenu" class="ml-3 mt-1 space-y-1 {{ request()->routeIs('accountant.sms', 'accountant.phone-numbers', 'accountant.sms-logs') ? '' : 'hidden' }}">
                    <a href="{{ route('accountant.sms') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.sms') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Send SMS</a>
                    <a href="{{ route('accountant.phone-numbers') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.phone-numbers') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">Phone Numbers</a>
                    <a href="{{ route('accountant.sms-logs') }}" class="block rounded-md px-3 py-2 text-sm transition {{ request()->routeIs('accountant.sms-logs') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15 font-semibold' : 'text-slate-700 hover:bg-blue-50/90' }}">SMS Logs</a>
                </div>
            </div>

            <a href="{{ route('accountant.bank-api') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                      {{ request()->routeIs('accountant.bank-api') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3.866 0-7 1.343-7 3v7h14v-7c0-1.657-3.134-3-7-3z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11V9a7 7 0 0114 0v2" />
                </svg>
                <span>Bank Integration</span>
            </a>

            @if(auth()->user() && auth()->user()->can_view_logs)
            <a href="{{ route('accountant.activity-logs') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                      {{ request()->routeIs('accountant.activity-logs') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>Activity logs</span>
            </a>
            @endif

            <a href="{{ route('accountant.settings') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                      {{ request()->routeIs('accountant.settings') ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/15' : 'text-slate-700 hover:bg-blue-50/90' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06A1.65 1.65 0 0015 19.4a1.65 1.65 0 00-1 .6 1.65 1.65 0 00-.33 1.82A2 2 0 1110 21.4a1.65 1.65 0 00-1-.6 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-.6-1 1.65 1.65 0 00-1.82-.33A2 2 0 112.6 10a1.65 1.65 0 00.6-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-.6 1.65 1.65 0 00.33-1.82A2 2 0 1114 2.6a1.65 1.65 0 001 .6 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9c.24.31.42.65.52 1.02.1.37.1.76 0 1.13-.1.37-.28.71-.52 1.02z" />
                </svg>
                <span>Settings</span>
            </a>
        </nav>
    </div>
</aside>

<!-- Sidebar Overlay: pointer-events-none while hidden so a stuck overlay cannot block the page -->
<div id="sidebar-overlay" class="pointer-events-none fixed inset-0 z-40 hidden bg-black/50 lg:hidden" onclick="toggleSidebar()"></div>

<!-- Sidebar JavaScript -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden', 'pointer-events-none');
            overlay.classList.add('pointer-events-auto');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden', 'pointer-events-none');
            overlay.classList.remove('pointer-events-auto');
        }
    }

    function toggleCategory(category) {
        const submenu = document.getElementById(category + '-submenu');
        const arrow = document.getElementById(category + '-arrow');

        if (submenu.classList.contains('hidden')) {
            submenu.classList.remove('hidden');
            arrow.classList.add('rotate-180');
        } else {
            submenu.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }
    }
</script>
