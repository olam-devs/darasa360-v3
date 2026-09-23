@extends('layouts.superadmin')

@section('title', 'Owner Dashboard — Darasa Finance')
@section('nav_title', 'Owner Dashboard')

@push('head')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('schoolStats', {});
    });
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush

@section('content')
<div x-data="ownerApp()" x-init="init()">

    {{-- ── Header ── --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Owner Dashboard</h1>
            <p class="text-xs text-slate-400 mt-0.5" x-text="lastUpdated"></p>
        </div>
        <button @click="loadStats()" :disabled="statsLoading"
            class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40">
            <svg :class="statsLoading ? 'animate-spin' : ''" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
        </button>
    </div>

    {{-- ── Global search ── --}}
    <div class="relative mb-8">
        <div class="relative">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" x-model="searchQuery" @input.debounce.350ms="doSearch()"
                @keydown.escape="searchResults = []"
                placeholder="Search student by name across all schools…"
                class="w-full rounded-xl border-2 border-slate-200 bg-white pl-12 pr-4 py-3.5 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 placeholder-slate-400">
            <div x-show="searchLoading" class="absolute right-4 top-1/2 -translate-y-1/2">
                <svg class="animate-spin w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </div>
        </div>

        {{-- Search results dropdown --}}
        <div x-show="searchResults.length > 0" x-transition
             class="absolute z-40 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden">
            <template x-for="r in searchResults" :key="r.school_id + '-' + r.id">
                <button @click="openStudent(r.school_id, r.id)"
                    class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-blue-50 border-b border-slate-50 last:border-0">
                    <div>
                        <span class="font-semibold text-slate-800 text-sm" x-text="r.name"></span>
                        <span class="ml-2 text-xs text-slate-400" x-text="r.class_name ? '· ' + r.class_name : ''"></span>
                        <span class="ml-2 inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                              :class="schoolBadge(r.school_id)" x-text="schoolShort(r.school_id)"></span>
                    </div>
                    <div class="text-right ml-4 shrink-0">
                        <span class="block text-xs text-red-600 font-semibold" x-text="r.outstanding > 0 ? 'Owed: ' + tsh(r.outstanding) : 'Clear'"></span>
                    </div>
                </button>
            </template>
        </div>
        <div x-show="searchQuery.length >= 2 && !searchLoading && searchResults.length === 0" x-transition
             class="absolute z-40 mt-1 w-full rounded-xl border border-slate-100 bg-white shadow-xl px-4 py-3 text-sm text-slate-400">
            No students found.
        </div>
    </div>

    {{-- ── Summary bar ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        <template x-if="statsLoading">
            <template x-for="i in 5" :key="i">
                <div class="rounded-xl bg-white border border-slate-100 shadow-sm px-4 py-3 animate-pulse">
                    <div class="h-2 w-16 bg-slate-200 rounded mb-2"></div>
                    <div class="h-6 w-24 bg-slate-100 rounded"></div>
                </div>
            </template>
        </template>
        <template x-if="!statsLoading">
            <template x-for="tile in summaryTiles()" :key="tile.label">
                <div class="rounded-xl bg-white border border-slate-100 shadow-sm px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5" x-text="tile.label"></p>
                    <p class="font-bold text-base" :class="tile.color" x-text="tile.value"></p>
                </div>
            </template>
        </template>
    </div>

    {{-- ── School cards ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @foreach($schools as $school)
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden"
             x-data="{ s: null }"
             x-init="$watch('schools', v => { s = v.find(x => x.id == {{ $school->id }}); })">

            <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                @if($school->logo)
                    <img src="{{ asset('storage/'.$school->logo) }}" class="h-7 w-7 rounded object-contain">
                @else
                    <div class="h-7 w-7 rounded bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm">{{ substr($school->name,0,1) }}</div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-700 truncate">{{ $school->name }}</p>
                </div>
                {{-- Enter school (impersonation) --}}
                <form method="POST" action="{{ route('superadmin.impersonate', $school) }}" class="inline">
                    @csrf
                    <input type="hidden" name="master_password" x-model="masterPwd">
                    <button type="button" @click="enterSchool({{ $school->id }}, '{{ addslashes($school->name) }}')"
                        class="rounded-lg bg-blue-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-blue-700">
                        Enter
                    </button>
                </form>
            </div>

            <template x-if="!s">
                <div class="p-4 space-y-2 animate-pulse">
                    <div class="h-3 bg-slate-100 rounded w-2/3"></div>
                    <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                </div>
            </template>
            <template x-if="s && !s.error">
                <div class="grid grid-cols-2 gap-x-3 gap-y-2 p-4 text-xs">
                    <div><span class="text-slate-400">Students</span><br><strong x-text="(s.students||0).toLocaleString()"></strong></div>
                    <div><span class="text-slate-400">Today</span><br><strong class="text-green-700" x-text="tsh(s.today_collection)"></strong></div>
                    <div><span class="text-slate-400">Month</span><br><strong class="text-blue-700" x-text="tsh(s.month_collection)"></strong></div>
                    <div><span class="text-slate-400">Outstanding</span><br><strong class="text-red-600" x-text="tsh(s.outstanding)"></strong></div>
                </div>
            </template>
        </div>
        @endforeach
    </div>

    {{-- ── Recent receipts (cross-school) ── --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-700">Recent Receipts — All Schools</h2>
        </div>
        <template x-if="statsLoading">
            <div class="p-5 space-y-2 animate-pulse">
                <template x-for="i in 6" :key="i"><div class="h-7 rounded bg-slate-50"></div></template>
            </div>
        </template>
        <template x-if="!statsLoading">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-[10px] uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-2 text-left font-semibold">School</th>
                            <th class="px-5 py-2 text-left font-semibold">Student</th>
                            <th class="px-5 py-2 text-left font-semibold">Date</th>
                            <th class="px-5 py-2 text-right font-semibold">Amount</th>
                            <th class="px-5 py-2 text-right font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in allReceipts()" :key="row.school_id + '-' + row.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/60 cursor-pointer" @click="openStudent(row.school_id, row.student_id)">
                                <td class="px-5 py-2">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-bold" :class="schoolBadge(row.school_id)" x-text="schoolShort(row.school_id)"></span>
                                </td>
                                <td class="px-5 py-2 text-slate-700 font-medium" x-text="row.student_name"></td>
                                <td class="px-5 py-2 text-slate-400" x-text="row.date"></td>
                                <td class="px-5 py-2 text-right font-semibold text-green-700" x-text="tsh(row.amount)"></td>
                                <td class="px-5 py-2 text-right">
                                    <span class="text-blue-500 text-xs">View →</span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="allReceipts().length === 0">
                            <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400 italic">No receipts yet</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    {{-- ══ Student detail modal ══ --}}
    <div x-show="student.open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 overflow-y-auto"
         @keydown.escape.window="student.open = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-6" @click.stop>

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div x-show="!student.loading">
                    <h3 class="font-bold text-slate-800 text-lg" x-text="student.data?.student?.name"></h3>
                    <p class="text-xs text-slate-400"
                       x-text="[student.data?.school_name, student.data?.student?.class_name].filter(Boolean).join(' · ')"></p>
                </div>
                <div x-show="student.loading" class="h-8 w-48 bg-slate-100 rounded animate-pulse"></div>
                <button @click="student.open = false" class="text-slate-400 hover:text-slate-600 ml-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div x-show="student.loading" class="p-6 space-y-3 animate-pulse">
                <div class="h-4 bg-slate-100 rounded w-3/4"></div>
                <div class="h-4 bg-slate-100 rounded w-1/2"></div>
                <div class="h-4 bg-slate-100 rounded w-2/3"></div>
            </div>

            <template x-if="!student.loading && student.data">
                <div class="p-6">

                    {{-- Fee items --}}
                    <div class="mb-5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Fee Items</h4>
                        <div class="rounded-xl border border-slate-100 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50">
                                    <tr class="text-xs text-slate-400 uppercase tracking-wide">
                                        <th class="px-4 py-2 text-left font-semibold">Particular</th>
                                        <th class="px-4 py-2 text-right font-semibold">Charged</th>
                                        <th class="px-4 py-2 text-right font-semibold">Paid</th>
                                        <th class="px-4 py-2 text-right font-semibold">Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="p in student.data.particulars" :key="p.id">
                                        <tr class="border-t border-slate-50">
                                            <td class="px-4 py-2 text-slate-700" x-text="p.name"></td>
                                            <td class="px-4 py-2 text-right text-slate-500" x-text="tsh(p.sales)"></td>
                                            <td class="px-4 py-2 text-right text-green-600" x-text="tsh(p.credit)"></td>
                                            <td class="px-4 py-2 text-right font-semibold"
                                                :class="p.outstanding > 0 ? 'text-red-600' : 'text-slate-400'"
                                                x-text="p.outstanding > 0 ? tsh(p.outstanding) : 'Paid'"></td>
                                        </tr>
                                    </template>
                                    <template x-if="student.data.particulars.length === 0">
                                        <tr><td colspan="4" class="px-4 py-3 text-slate-400 italic text-xs">No fee items assigned</td></tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 flex gap-4 text-xs text-slate-500 px-1">
                            <span>Advance held: <strong class="text-amber-600" x-text="tsh(student.data.student.advance_balance)"></strong></span>
                        </div>
                    </div>

                    {{-- Recent receipts --}}
                    <div class="mb-5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Recent Receipts</h4>
                        <template x-if="student.data.receipts.length > 0">
                            <div class="space-y-1">
                                <template x-for="r in student.data.receipts" :key="r.id">
                                    <div class="flex items-center justify-between text-sm px-2 py-1.5 rounded-lg hover:bg-slate-50">
                                        <span class="text-slate-500" x-text="r.date"></span>
                                        <span class="text-slate-400 text-xs" x-text="r.book_name"></span>
                                        <span class="font-semibold text-green-700" x-text="tsh(r.amount)"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="student.data.receipts.length === 0">
                            <p class="text-xs text-slate-400 italic px-2">No receipts recorded yet</p>
                        </template>
                    </div>

                    {{-- ── Action buttons ── --}}
                    <div class="flex flex-wrap gap-2 mb-5">
                        <a :href="`/superadmin/owner/invoice/${student.schoolId}/${student.studentId}`"
                           target="_blank"
                           class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                            Download Invoice
                        </a>
                        <button @click="student.showReceipt = !student.showReceipt"
                            class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-xs font-bold text-white hover:bg-green-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Record Receipt
                        </button>
                    </div>

                    {{-- ── Inline receipt form ── --}}
                    <div x-show="student.showReceipt" x-transition
                         class="rounded-xl border-2 border-green-200 bg-green-50/50 p-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-green-700 mb-3">Record Fee Receipt</h4>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Date *</label>
                                <input type="date" x-model="receipt.date" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-green-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Book / Account *</label>
                                <select x-model="receipt.bookId" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-green-400">
                                    <option value="">-- Select --</option>
                                    <template x-for="b in student.data.books" :key="b.id">
                                        <option :value="b.id" x-text="b.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        {{-- Particular rows --}}
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Particular(s) *</label>
                            <template x-for="(item, idx) in receipt.items" :key="idx">
                                <div class="flex gap-2 mb-1.5 items-center">
                                    <select x-model="item.particularId" class="flex-1 border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-green-400">
                                        <option value="">-- Particular --</option>
                                        <template x-for="p in student.data.particulars" :key="p.id">
                                            <option :value="p.id" x-text="p.outstanding > 0 ? p.name + ' (owed: ' + tsh(p.outstanding) + ')' : p.name"></option>
                                        </template>
                                    </select>
                                    <input type="number" x-model="item.amount" placeholder="Amount" step="0.01" min="0"
                                        class="w-32 border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-green-400">
                                    <button @click="receipt.items.splice(idx,1)" x-show="receipt.items.length > 1"
                                        class="text-red-400 hover:text-red-600 text-lg leading-none">×</button>
                                </div>
                            </template>
                            <button @click="receipt.items.push({particularId:'',amount:''})"
                                class="text-xs text-green-700 font-semibold hover:underline mt-1">+ Add particular</button>
                        </div>

                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Notes</label>
                            <input type="text" x-model="receipt.notes" placeholder="Optional note"
                                class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-green-400">
                        </div>

                        <div class="flex gap-2">
                            <button @click="submitReceipt()" :disabled="receipt.saving"
                                class="flex-1 rounded-lg bg-green-600 py-2 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-50">
                                <span x-show="!receipt.saving">Save Receipt</span>
                                <span x-show="receipt.saving">Saving…</span>
                            </button>
                            <button @click="student.showReceipt = false"
                                class="px-4 rounded-lg bg-slate-100 text-slate-600 text-sm font-medium hover:bg-slate-200">
                                Cancel
                            </button>
                        </div>

                        <p x-show="receipt.msg" class="mt-2 text-xs font-semibold" :class="receipt.ok ? 'text-green-700' : 'text-red-600'" x-text="receipt.msg"></p>
                    </div>

                </div>
            </template>
        </div>
    </div>

    {{-- ══ Enter school modal ══ --}}
    <div x-show="enterModal.open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="enterModal.open = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6" @click.stop>
            <h3 class="font-bold text-lg mb-1">Enter <span x-text="enterModal.schoolName" class="text-blue-700"></span></h3>
            <p class="text-sm text-slate-500 mb-4">Master password required for full portal access.</p>
            <form :action="enterModal.action" method="POST">
                @csrf
                <input type="hidden" name="redirect_to" :value="enterModal.redirectTo">
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Master Password</label>
                    <input type="password" name="master_password" required
                        class="w-full border-2 border-slate-200 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                        x-init="$el.focus()">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white font-bold py-2.5 rounded-lg hover:bg-blue-700 text-sm">Enter</button>
                    <button type="button" @click="enterModal.open = false"
                        class="px-4 bg-slate-100 text-slate-600 font-medium py-2.5 rounded-lg hover:bg-slate-200 text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ownerApp() {
    return {
        schools:      [],
        statsLoading: true,
        lastUpdated:  'Loading…',
        searchQuery:  '',
        searchResults:[],
        searchLoading:false,

        student: { open: false, loading: false, data: null, schoolId: null, studentId: null, showReceipt: false },
        receipt: { date: new Date().toISOString().slice(0,10), bookId: '', items: [{particularId:'', amount:''}], notes: '', saving: false, msg: '', ok: false },
        enterModal: { open: false, schoolName: '', action: '', redirectTo: '' },

        init() {
            this.loadStats();
        },

        async loadStats() {
            this.statsLoading = true;
            try {
                const res  = await fetch('{{ route('superadmin.owner.live-stats') }}');
                this.schools = await res.json();
                this.lastUpdated = 'Updated ' + new Date().toLocaleTimeString();
            } finally {
                this.statsLoading = false;
            }
        },

        tsh(v) {
            if (!v && v !== 0) return '—';
            return 'TSh ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },

        summaryTiles() {
            const ok  = this.schools.filter(s => !s.error);
            const sum = k => ok.reduce((a, s) => a + (Number(s[k]) || 0), 0);
            return [
                { label: 'Students',    value: ok.reduce((a,s)=>a+(s.students||0),0).toLocaleString(), color: 'text-slate-800' },
                { label: 'Today',       value: this.tsh(sum('today_collection')),  color: 'text-green-700' },
                { label: 'This Month',  value: this.tsh(sum('month_collection')),  color: 'text-blue-700'  },
                { label: 'This Year',   value: this.tsh(sum('year_collection')),   color: 'text-indigo-700'},
                { label: 'Outstanding', value: this.tsh(sum('outstanding')),       color: 'text-red-600'   },
            ];
        },

        allReceipts() {
            const rows = [];
            this.schools.filter(s => !s.error && s.recent_receipts).forEach(s => {
                (s.recent_receipts || []).forEach(r => rows.push({ ...r, school_id: s.id }));
            });
            rows.sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
            return rows.slice(0, 20);
        },

        schoolShort(id) {
            const s = this.schools.find(x => x.id == id);
            if (!s) return '?';
            return s.name.split(' ').filter(w=>w.length>2).slice(0,2).map(w=>w[0]).join('').toUpperCase() || s.name.slice(0,3).toUpperCase();
        },

        schoolBadge(id) {
            const palettes = ['bg-blue-100 text-blue-700','bg-purple-100 text-purple-700','bg-teal-100 text-teal-700','bg-orange-100 text-orange-700'];
            const idx = this.schools.findIndex(x => x.id == id);
            return palettes[Math.max(0, idx) % palettes.length];
        },

        async doSearch() {
            const q = this.searchQuery.trim();
            if (q.length < 2) { this.searchResults = []; return; }
            this.searchLoading = true;
            try {
                const res   = await fetch(`{{ route('superadmin.owner.search') }}?q=${encodeURIComponent(q)}`);
                this.searchResults = await res.json();
            } finally {
                this.searchLoading = false;
            }
        },

        async openStudent(schoolId, studentId) {
            this.searchResults  = [];
            this.searchQuery    = '';
            this.student        = { open: true, loading: true, data: null, schoolId, studentId, showReceipt: false };
            this.receipt        = { date: new Date().toISOString().slice(0,10), bookId: '', items: [{particularId:'', amount:''}], notes: '', saving: false, msg: '', ok: false };

            const res  = await fetch(`/superadmin/owner/student/${schoolId}/${studentId}`);
            this.student.data    = await res.json();
            this.student.loading = false;
        },

        async submitReceipt() {
            this.receipt.saving = true;
            this.receipt.msg    = '';
            const items = this.receipt.items.filter(i => i.particularId && i.amount > 0);
            if (!items.length || !this.receipt.bookId || !this.receipt.date) {
                this.receipt.msg = 'Fill in date, book and at least one particular with amount.';
                this.receipt.ok  = false;
                this.receipt.saving = false;
                return;
            }
            try {
                const res  = await fetch(`/superadmin/owner/receipt/${this.student.schoolId}`, {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        date:           this.receipt.date,
                        student_id:     this.student.studentId,
                        book_id:        parseInt(this.receipt.bookId),
                        notes:          this.receipt.notes,
                        items:          items.map(i => ({ particular_id: parseInt(i.particularId), amount: parseFloat(i.amount) })),
                        advance_amount: 0,
                    }),
                });
                const json = await res.json();
                if (res.ok) {
                    this.receipt.msg = `Saved! Receipt #${json.voucher_id} — ${this.tsh(json.total)}`;
                    this.receipt.ok  = true;
                    this.receipt.items = [{particularId:'', amount:''}];
                    // Refresh student detail
                    await this.openStudent(this.student.schoolId, this.student.studentId);
                    this.student.showReceipt = true;
                    this.receipt.msg = `Saved! Receipt #${json.voucher_id} — ${this.tsh(json.total)}`;
                    this.receipt.ok  = true;
                } else {
                    this.receipt.msg = json.error || 'Failed to save.';
                    this.receipt.ok  = false;
                }
            } catch (e) {
                this.receipt.msg = 'Network error.';
                this.receipt.ok  = false;
            } finally {
                this.receipt.saving = false;
            }
        },

        enterSchool(schoolId, schoolName, destination) {
            this.enterModal = {
                open: true, schoolName,
                action: `/superadmin/impersonate/${schoolId}`,
                redirectTo: destination || '',
            };
        },
    };
}
</script>
@endpush
