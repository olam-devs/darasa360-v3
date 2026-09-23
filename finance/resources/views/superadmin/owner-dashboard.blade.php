@extends('layouts.superadmin')

@section('title', 'Owner Dashboard — Darasa Finance')
@section('nav_title', 'Owner Dashboard')

@section('content')
<div x-data="ownerDashboard()" x-init="load()">

    {{-- Page header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Owner Dashboard</h1>
            <p class="text-sm text-slate-500 mt-0.5">Live view across all schools — <span x-text="lastUpdated" class="text-blue-600"></span></p>
        </div>
        <button @click="load()" :disabled="loading"
            class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
            <svg :class="loading ? 'animate-spin' : ''" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
        </button>
    </div>

    {{-- Combined KPI bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <template x-if="!loading && schools.length">
            <template x-for="(tile, i) in summaryTiles()" :key="i">
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1" x-text="tile.label"></p>
                    <p class="text-2xl font-bold" :class="tile.color" x-text="tile.value"></p>
                </div>
            </template>
        </template>
        <template x-if="loading">
            <template x-for="i in 5" :key="i">
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm px-5 py-4 animate-pulse">
                    <div class="h-3 w-20 bg-slate-200 rounded mb-3"></div>
                    <div class="h-7 w-32 bg-slate-100 rounded"></div>
                </div>
            </template>
        </template>
    </div>

    {{-- School cards --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        @foreach($schools as $school)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden"
             x-data="{ stats: null, loading: true }"
             x-init="$watch('$store.schoolStats', v => { stats = v['{{ $school->id }}']; loading = false })">

            {{-- Card header --}}
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                @if($school->logo)
                    <img src="{{ asset('storage/' . $school->logo) }}" class="h-9 w-9 rounded-lg object-contain border border-slate-100" onerror="this.style.display='none'">
                @else
                    <div class="h-9 w-9 rounded-lg bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-lg">
                        {{ substr($school->name, 0, 1) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="font-bold text-slate-800 text-sm leading-tight truncate">{{ $school->name }}</p>
                    <p class="text-xs text-slate-400">{{ $school->slug }}</p>
                </div>
            </div>

            {{-- Stats grid --}}
            <div class="p-5">
                <template x-if="loading">
                    <div class="space-y-3 animate-pulse">
                        <div class="h-4 bg-slate-100 rounded w-3/4"></div>
                        <div class="h-4 bg-slate-100 rounded w-1/2"></div>
                        <div class="h-4 bg-slate-100 rounded w-2/3"></div>
                    </div>
                </template>
                <template x-if="!loading && stats && !stats.error">
                    <div class="space-y-0">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm mb-4">
                            <div>
                                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Students</p>
                                <p class="font-bold text-slate-800 text-lg" x-text="stats.students.toLocaleString()"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Today</p>
                                <p class="font-bold text-green-700 text-lg" x-text="tsh(stats.today_collection)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">This Month</p>
                                <p class="font-bold text-blue-700 text-base" x-text="tsh(stats.month_collection)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">This Year</p>
                                <p class="font-bold text-indigo-700 text-base" x-text="tsh(stats.year_collection)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Outstanding</p>
                                <p class="font-bold text-red-600 text-base" x-text="tsh(stats.outstanding)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Advance Held</p>
                                <p class="font-bold text-amber-600 text-base" x-text="tsh(stats.advance_total)"></p>
                            </div>
                        </div>

                        {{-- Recent receipts --}}
                        <div class="border-t border-slate-100 pt-3 mb-4">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Recent receipts</p>
                            <template x-if="stats.recent_receipts && stats.recent_receipts.length">
                                <ul class="space-y-1.5">
                                    <template x-for="r in stats.recent_receipts.slice(0,4)" :key="r.id">
                                        <li class="flex items-center justify-between text-xs">
                                            <span class="truncate text-slate-600 max-w-[55%]" x-text="r.student_name"></span>
                                            <span class="font-semibold text-green-700 whitespace-nowrap ml-2" x-text="tsh(r.amount)"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                            <template x-if="!stats.recent_receipts || !stats.recent_receipts.length">
                                <p class="text-xs text-slate-400 italic">No receipts yet</p>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="!loading && stats && stats.error">
                    <p class="text-xs text-red-500 italic" x-text="'Error: ' + stats.error"></p>
                </template>
            </div>

            {{-- Quick actions --}}
            <div class="px-5 pb-5 flex flex-wrap gap-2">
                <button @click="openEnterModal({{ $school->id }}, '{{ addslashes($school->name) }}')"
                    class="flex-1 flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    Enter school
                </button>
                <button @click="openEnterModal({{ $school->id }}, '{{ addslashes($school->name) }}', 'fee-entry')"
                    class="flex items-center justify-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition" title="Go to fee entry">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Fee Entry
                </button>
                <button @click="openEnterModal({{ $school->id }}, '{{ addslashes($school->name) }}', 'ledgers')"
                    class="flex items-center justify-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition" title="View ledgers">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Ledgers
                </button>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Cross-school recent receipts feed --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Recent Receipts — All Schools
        </h2>
        <template x-if="loading">
            <div class="space-y-3 animate-pulse">
                <template x-for="i in 8" :key="i">
                    <div class="h-8 bg-slate-50 rounded"></div>
                </template>
            </div>
        </template>
        <template x-if="!loading">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs text-slate-400 font-semibold uppercase tracking-wide">
                            <th class="pb-2 text-left">School</th>
                            <th class="pb-2 text-left">Student</th>
                            <th class="pb-2 text-left">Date</th>
                            <th class="pb-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in allReceipts()" :key="row.school + row.id">
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50">
                                <td class="py-2 pr-4">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                                          :class="schoolBadge(row.school_id)">
                                        <span x-text="schoolShortName(row.school_id)"></span>
                                    </span>
                                </td>
                                <td class="py-2 pr-4 text-slate-700" x-text="row.student_name"></td>
                                <td class="py-2 pr-4 text-slate-500" x-text="row.date"></td>
                                <td class="py-2 text-right font-semibold text-green-700" x-text="tsh(row.amount)"></td>
                            </tr>
                        </template>
                        <template x-if="allReceipts().length === 0">
                            <tr><td colspan="4" class="py-6 text-center text-slate-400 italic text-sm">No receipts yet</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    {{-- Enter school modal --}}
    <div x-show="modal.open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="modal.open = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6" @click.stop>
            <h3 class="font-bold text-lg mb-1">Enter <span x-text="modal.schoolName" class="text-blue-700"></span></h3>
            <p class="text-sm text-slate-500 mb-4">Enter your master password to access this school's portal.</p>
            <form :action="modal.action" method="POST">
                @csrf
                <input type="hidden" name="redirect_to" :value="modal.redirectTo">
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Master Password</label>
                    <input type="password" name="master_password" required autofocus
                        class="w-full border-2 border-slate-200 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 text-white font-bold py-2.5 rounded-lg hover:bg-blue-700 transition text-sm">
                        Enter School
                    </button>
                    <button type="button" @click="modal.open = false"
                        class="px-4 bg-slate-100 text-slate-600 font-medium py-2.5 rounded-lg hover:bg-slate-200 transition text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('head')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('schoolStats', {});
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush

@push('scripts')
<script>
function ownerDashboard() {
    return {
        loading: true,
        schools: [],
        lastUpdated: '',
        modal: { open: false, schoolName: '', action: '', redirectTo: '' },

        async load() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('superadmin.api.schools-live-stats') }}');
                const data = await res.json();
                this.schools = data;

                // Store in Alpine store for school cards to pick up
                const map = {};
                data.forEach(s => { map[s.id] = s; });
                Alpine.store('schoolStats', map);

                this.lastUpdated = 'Updated ' + new Date().toLocaleTimeString();
            } finally {
                this.loading = false;
            }
        },

        tsh(v) {
            if (v == null) return '—';
            return 'TSh ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },

        summaryTiles() {
            const ok = this.schools.filter(s => !s.error);
            const sum = (key) => ok.reduce((a, s) => a + (s[key] || 0), 0);
            const students = ok.reduce((a, s) => a + (s.students || 0), 0);
            return [
                { label: 'Total Students', value: students.toLocaleString(), color: 'text-slate-800' },
                { label: 'Today (All)', value: this.tsh(sum('today_collection')), color: 'text-green-700' },
                { label: 'This Month', value: this.tsh(sum('month_collection')), color: 'text-blue-700' },
                { label: 'This Year', value: this.tsh(sum('year_collection')), color: 'text-indigo-700' },
                { label: 'Outstanding', value: this.tsh(sum('outstanding')), color: 'text-red-600' },
            ];
        },

        allReceipts() {
            const rows = [];
            this.schools.filter(s => !s.error && s.recent_receipts).forEach(s => {
                s.recent_receipts.forEach(r => rows.push({ ...r, school_id: s.id, school_name: s.name }));
            });
            rows.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            return rows.slice(0, 20);
        },

        schoolShortName(schoolId) {
            const s = this.schools.find(x => x.id == schoolId);
            if (!s) return '?';
            const words = s.name.split(' ').filter(w => w.length > 2);
            return words.slice(0, 2).map(w => w[0]).join('').toUpperCase() || s.name.substring(0, 3).toUpperCase();
        },

        schoolBadge(schoolId) {
            const palettes = [
                'bg-blue-100 text-blue-700',
                'bg-purple-100 text-purple-700',
                'bg-teal-100 text-teal-700',
                'bg-orange-100 text-orange-700',
            ];
            const idx = this.schools.findIndex(x => x.id == schoolId);
            return palettes[idx % palettes.length];
        },

        openEnterModal(schoolId, schoolName, destination) {
            this.modal = {
                open: true,
                schoolName,
                action: `/superadmin/impersonate/${schoolId}`,
                redirectTo: destination || '',
            };
            this.$nextTick(() => {
                const pw = document.querySelector('[name=master_password]');
                if (pw) pw.focus();
            });
        },
    };
}
</script>
@endpush
