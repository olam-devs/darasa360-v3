@extends('layouts.superadmin')

@section('title', 'Command Centre — Darasa Finance')
@section('nav_title', 'Command Centre')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('schoolStats', {});
    });
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    [x-cloak] { display: none !important; }
    .sidebar-link {
        display: flex; align-items: center; gap: 8px;
        padding: 6px 12px; border-radius: 8px; font-size: 13px;
        color: #475569; cursor: pointer; transition: all 0.15s;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        border: none; background: transparent; width: 100%; text-align: left;
        text-decoration: none;
    }
    .sidebar-link:hover { background: #f1f5f9; color: #1e293b; }
    .sidebar-link.active { background: #ede9fe; color: #6d28d9; font-weight: 600; }
    .sidebar-link.ext { color: #0ea5e9; }
    .sidebar-link.ext:hover { background: #e0f2fe; }
</style>
@endpush

@section('content')
<div x-data="ownerApp()" x-init="init()" x-cloak
     class="flex -mx-4 sm:-mx-6 lg:-mx-8 -my-8"
     style="min-height: calc(100vh - 4rem)">

    {{-- ═══════════════════════════════════════ SIDEBAR ══════════════════════════════════════ --}}
    <aside class="w-56 shrink-0 border-r border-slate-100 bg-white overflow-y-auto"
           style="position: sticky; top: 4rem; height: calc(100vh - 4rem);">
        <div class="px-3 py-4 space-y-0.5">

            {{-- Overview --}}
            <button @click="setView('overview')"
                :class="view === 'overview' ? 'active' : ''"
                class="sidebar-link font-semibold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                Overview
            </button>

            <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">Schools</div>

            @foreach($schools as $si => $school)
            @php $sid = $school->id; $cs = ['blue','purple','teal'][$si % 3]; @endphp
            <div>
                <button @click="toggleSidebar({{ $sid }})"
                    :class="openSchool == {{ $sid }} ? 'bg-slate-100 font-semibold text-slate-800' : ''"
                    class="sidebar-link">
                    <svg class="w-4 h-4 shrink-0 text-{{ $cs }}-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21V8a2 2 0 012-2h12a2 2 0 012 2v13M1 21h22M9 21v-5a1 1 0 011-1h4a1 1 0 011 1v5"/>
                    </svg>
                    <span class="truncate text-xs">{{ $school->name }}</span>
                    <svg class="w-3 h-3 ml-auto shrink-0 transition-transform"
                         :class="openSchool == {{ $sid }} ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="openSchool == {{ $sid }}" x-transition
                     class="ml-3 mt-0.5 space-y-0.5 border-l border-slate-100 pl-3">

                    <button @click="setView('students', {{ $sid }})"
                        :class="view === 'students' && activeSchoolId === {{ $sid }} ? 'active' : ''"
                        class="sidebar-link text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-5-3.87M9 20H4v-1a4 4 0 015-3.87m4-3a4 4 0 10-8 0 4 4 0 008 0zm6-3a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Students & Balances
                    </button>

                    <button @click="setView('fee-entry', {{ $sid }})"
                        :class="view === 'fee-entry' && activeSchoolId === {{ $sid }} ? 'active' : ''"
                        class="sidebar-link text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Fee Entry
                    </button>

                    <a href="{{ route('superadmin.owner.enter', $school) }}?to=ledgers" target="_blank" class="sidebar-link ext text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6M4 21h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v15a1 1 0 001 1z"/>
                        </svg>
                        Ledgers ↗
                    </a>

                    <a href="{{ route('superadmin.owner.enter', $school) }}?to=invoices" target="_blank" class="sidebar-link ext text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        </svg>
                        Invoices ↗
                    </a>

                    <a href="{{ route('superadmin.owner.enter', $school) }}?to=sms" target="_blank" class="sidebar-link ext text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        SMS ↗
                    </a>

                    <a href="{{ route('superadmin.owner.enter', $school) }}?to=expenses" target="_blank" class="sidebar-link ext text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Expenses ↗
                    </a>

                </div>
            </div>
            @endforeach

        </div>
    </aside>

    {{-- ═══════════════════════════════════════ MAIN CONTENT ══════════════════════════════════ --}}
    <div class="flex-1 min-w-0 overflow-y-auto px-6 py-6">

        {{-- ──────────────────────── OVERVIEW ──────────────────────────────── --}}
        <div x-show="view === 'overview'">

            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Analytics Overview</h1>
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

            {{-- Cross-school search --}}
            <div class="relative mb-6">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" x-model="searchQuery" @input.debounce.350ms="doSearch()" @keydown.escape="searchResults = []"
                    placeholder="Search any student across all schools…"
                    class="w-full rounded-xl border-2 border-slate-200 bg-white pl-12 pr-4 py-3 text-sm shadow-sm focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100 placeholder-slate-400">
                <div x-show="searchLoading" class="absolute right-4 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                </div>
                <div x-show="searchResults.length > 0" x-transition
                     class="absolute z-40 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                    <template x-for="r in searchResults" :key="r.school_id + '-' + r.id">
                        <button @click="openStudent(r.school_id, r.id)"
                            class="flex w-full items-center justify-between px-4 py-2.5 text-left hover:bg-purple-50 border-b border-slate-50 last:border-0">
                            <div>
                                <span class="font-semibold text-slate-800 text-sm" x-text="r.name"></span>
                                <span class="ml-2 text-xs text-slate-400" x-text="r.class_name ? '· ' + r.class_name : ''"></span>
                                <span class="ml-2 inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                                      :class="schoolBadge(r.school_id)" x-text="schoolShort(r.school_id)"></span>
                            </div>
                            <span class="text-xs font-semibold ml-4 shrink-0"
                                  :class="r.outstanding > 0 ? 'text-red-600' : 'text-slate-400'"
                                  x-text="r.outstanding > 0 ? 'Owed: ' + tsh(r.outstanding) : 'Clear'"></span>
                        </button>
                    </template>
                </div>
                <div x-show="searchQuery.length >= 2 && !searchLoading && searchResults.length === 0" x-transition
                     class="absolute z-40 mt-1 w-full rounded-xl border border-slate-100 bg-white shadow-xl px-4 py-3 text-sm text-slate-400">
                    No students found.
                </div>
            </div>

            {{-- KPI tiles --}}
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

            {{-- 7-day chart --}}
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 mb-6">
                <h2 class="text-sm font-bold text-slate-700 mb-4">7-Day Collection Trend</h2>
                <div x-show="statsLoading" class="h-44 flex items-center justify-center text-slate-300 animate-pulse text-sm">Loading chart…</div>
                <div x-show="!statsLoading" style="height:176px; position:relative;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            {{-- School cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @foreach($schools as $si => $school)
                @php $c = ['blue','purple','teal'][$si % 3]; @endphp
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden"
                     x-data="{ s: null }"
                     x-init="$watch('schools', v => { s = v.find(x => x.id == {{ $school->id }}); })">
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                        @if($school->logo)
                            <img src="{{ asset('storage/'.$school->logo) }}" class="h-7 w-7 rounded object-contain">
                        @else
                            <div class="h-7 w-7 rounded bg-{{ $c }}-100 flex items-center justify-center text-{{ $c }}-700 font-bold text-sm">{{ substr($school->name,0,1) }}</div>
                        @endif
                        <p class="text-xs font-bold text-slate-700 truncate flex-1">{{ $school->name }}</p>
                        <button @click="setView('students', {{ $school->id }})"
                            class="rounded-lg bg-{{ $c }}-600 px-2.5 py-1 text-xs font-bold text-white hover:opacity-90 shrink-0">
                            View
                        </button>
                    </div>
                    <template x-if="!statsLoading && s && !s.error">
                        <div class="grid grid-cols-2 gap-x-3 gap-y-2 p-4 text-xs">
                            <div><span class="text-slate-400">Students</span><br><strong x-text="(s.students||0).toLocaleString()"></strong></div>
                            <div><span class="text-slate-400">Today</span><br><strong class="text-green-700" x-text="tsh(s.today_collection)"></strong></div>
                            <div><span class="text-slate-400">Month</span><br><strong class="text-blue-700" x-text="tsh(s.month_collection)"></strong></div>
                            <div><span class="text-slate-400">Outstanding</span><br><strong class="text-red-600" x-text="tsh(s.outstanding)"></strong></div>
                        </div>
                    </template>
                    <template x-if="statsLoading || !s">
                        <div class="p-4 space-y-2 animate-pulse">
                            <div class="h-3 bg-slate-100 rounded w-2/3"></div>
                            <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                        </div>
                    </template>
                </div>
                @endforeach
            </div>

            {{-- Recent receipts --}}
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <h2 class="text-sm font-bold text-slate-700 px-5 py-3 border-b border-slate-100">Recent Receipts — All Schools</h2>
                <template x-if="statsLoading">
                    <div class="p-4 space-y-2 animate-pulse">
                        <template x-for="i in 6" :key="i"><div class="h-7 rounded bg-slate-50"></div></template>
                    </div>
                </template>
                <template x-if="!statsLoading">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-slate-100">
                                <tr class="text-[10px] uppercase tracking-wider text-slate-400">
                                    <th class="px-5 py-2 text-left font-semibold">School</th>
                                    <th class="px-5 py-2 text-left font-semibold">Student</th>
                                    <th class="px-5 py-2 text-left font-semibold">Date</th>
                                    <th class="px-5 py-2 text-right font-semibold">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in allReceipts()" :key="row.school_id + '-' + row.id">
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/60 cursor-pointer"
                                        @click="openStudent(row.school_id, row.student_id)">
                                        <td class="px-5 py-2">
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-bold"
                                                  :class="schoolBadge(row.school_id)" x-text="schoolShort(row.school_id)"></span>
                                        </td>
                                        <td class="px-5 py-2 text-slate-700 font-medium" x-text="row.student_name"></td>
                                        <td class="px-5 py-2 text-slate-400" x-text="row.date"></td>
                                        <td class="px-5 py-2 text-right font-semibold text-green-700" x-text="tsh(row.amount)"></td>
                                    </tr>
                                </template>
                                <template x-if="allReceipts().length === 0">
                                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400 italic">No receipts yet</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

        </div>{{-- /overview --}}

        {{-- ──────────────────────── STUDENTS & BALANCES ───────────────────── --}}
        <div x-show="view === 'students'">

            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900" x-text="activeSchoolName()"></h1>
                    <p class="text-xs text-slate-400 mt-0.5">Students & Outstanding Balances</p>
                </div>
                <button @click="setView('fee-entry', activeSchoolId)"
                    class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-green-700">
                    + Record Receipt
                </button>
            </div>

            <div class="relative mb-4 max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" x-model="sList.q" @input.debounce.300ms="loadStudents()"
                    placeholder="Search by name…"
                    class="w-full border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:border-blue-400">
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <template x-if="sList.loading">
                    <div class="p-6 space-y-2 animate-pulse">
                        <template x-for="i in 8" :key="i"><div class="h-8 rounded bg-slate-50"></div></template>
                    </div>
                </template>
                <template x-if="!sList.loading">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr class="text-[10px] uppercase tracking-wider text-slate-400">
                                    <th class="px-4 py-2.5 text-left font-semibold">Name</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Class</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">Outstanding</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">Advance</th>
                                    <th class="px-4 py-2.5 text-right font-semibold"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="s in sList.data" :key="s.id">
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/60">
                                        <td class="px-4 py-2.5 font-medium text-slate-800" x-text="s.name"></td>
                                        <td class="px-4 py-2.5 text-slate-500" x-text="s.class_name || '—'"></td>
                                        <td class="px-4 py-2.5 text-right font-semibold"
                                            :class="s.outstanding > 0 ? 'text-red-600' : 'text-slate-400'"
                                            x-text="s.outstanding > 0 ? tsh(s.outstanding) : 'Clear'"></td>
                                        <td class="px-4 py-2.5 text-right text-amber-600 font-medium"
                                            x-text="s.advance_balance > 0 ? tsh(s.advance_balance) : '—'"></td>
                                        <td class="px-4 py-2.5 text-right">
                                            <button @click="openStudent(activeSchoolId, s.id)"
                                                class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-blue-100 hover:text-blue-700">
                                                Details
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="sList.data.length === 0">
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">No students found</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
                <div x-show="sList.lastPage > 1"
                     class="flex items-center justify-between px-4 py-3 border-t border-slate-100 text-xs text-slate-500">
                    <span x-text="`${sList.total} students total`"></span>
                    <div class="flex gap-1">
                        <button @click="loadStudents(sList.page - 1)" :disabled="sList.page <= 1"
                            class="px-2.5 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-40">←</button>
                        <span class="px-2.5 py-1" x-text="`${sList.page} / ${sList.lastPage}`"></span>
                        <button @click="loadStudents(sList.page + 1)" :disabled="sList.page >= sList.lastPage"
                            class="px-2.5 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-40">→</button>
                    </div>
                </div>
            </div>

        </div>{{-- /students --}}

        {{-- ──────────────────────── FEE ENTRY ─────────────────────────────── --}}
        <div x-show="view === 'fee-entry'">

            <div class="mb-5">
                <h1 class="text-xl font-bold text-slate-900" x-text="activeSchoolName()"></h1>
                <p class="text-xs text-slate-400 mt-0.5">Fee Entry — search a student to begin</p>
            </div>

            <div class="relative mb-6 max-w-lg">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" x-model="feSearch.q" @input.debounce.300ms="feeSearch()"
                    @keydown.escape="feSearch.results = []"
                    placeholder="Search student by name…"
                    class="w-full border-2 border-slate-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-green-400">
                <div x-show="feSearch.results.length > 0" x-transition
                     class="absolute z-40 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                    <template x-for="s in feSearch.results" :key="s.id">
                        <button @click="openStudent(activeSchoolId, s.id); feSearch.results = []; feSearch.q = s.name"
                            class="flex w-full items-center justify-between px-4 py-2.5 text-left hover:bg-green-50 border-b border-slate-50 last:border-0">
                            <div>
                                <span class="font-semibold text-sm" x-text="s.name"></span>
                                <span class="ml-2 text-xs text-slate-400" x-text="s.class_name ? '· ' + s.class_name : ''"></span>
                            </div>
                            <span class="text-xs font-semibold ml-4 shrink-0"
                                  :class="s.outstanding > 0 ? 'text-red-600' : 'text-slate-400'"
                                  x-text="s.outstanding > 0 ? 'Owed: ' + tsh(s.outstanding) : 'Clear'"></span>
                        </button>
                    </template>
                </div>
            </div>

            <p class="text-sm text-slate-400 italic" x-show="!student.open">
                Search for a student above to view their fee details and record a receipt.
            </p>

        </div>{{-- /fee-entry --}}

    </div>{{-- /main --}}

    {{-- ═══════════════════════════════════ STUDENT DETAIL MODAL ═══════════════════════════════ --}}
    <div x-show="student.open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 overflow-y-auto"
         @keydown.escape.window="student.open = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-6" @click.stop>

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div x-show="!student.loading">
                    <h3 class="font-bold text-slate-800 text-lg" x-text="student.data?.student?.name"></h3>
                    <p class="text-xs text-slate-400"
                       x-text="[student.data?.school_name, student.data?.student?.class_name].filter(Boolean).join(' · ')"></p>
                </div>
                <div x-show="student.loading" class="h-8 w-48 bg-slate-100 rounded animate-pulse"></div>
                <button @click="student.open = false" class="text-slate-400 hover:text-slate-600 ml-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
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
                        <p class="mt-2 text-xs text-slate-500 px-1">
                            Advance held: <strong class="text-amber-600" x-text="tsh(student.data.student.advance_balance)"></strong>
                        </p>
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

                    {{-- Actions --}}
                    <div class="flex flex-wrap gap-2 mb-5">
                        <a :href="`/superadmin/owner/invoice/${student.schoolId}/${student.studentId}`"
                           target="_blank"
                           class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            Download Invoice
                        </a>
                        <button @click="student.showReceipt = !student.showReceipt"
                            :class="student.showReceipt ? 'bg-slate-500 hover:bg-slate-600' : 'bg-green-600 hover:bg-green-700'"
                            class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span x-text="student.showReceipt ? 'Cancel Receipt' : 'Record Receipt'"></span>
                        </button>
                    </div>

                    {{-- Inline receipt form --}}
                    <div x-show="student.showReceipt" x-transition
                         class="rounded-xl border-2 border-green-200 bg-green-50/50 p-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-green-700 mb-3">Record Fee Receipt</h4>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Date *</label>
                                <input type="date" x-model="receipt.date"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-green-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Book / Account *</label>
                                <select x-model="receipt.bookId"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-green-400">
                                    <option value="">-- Select --</option>
                                    <template x-for="b in student.data.books" :key="b.id">
                                        <option :value="b.id" x-text="b.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Particular(s) *</label>
                            <template x-for="(item, idx) in receipt.items" :key="idx">
                                <div class="flex gap-2 mb-1.5 items-center">
                                    <select x-model="item.particularId"
                                        class="flex-1 border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-green-400">
                                        <option value="">-- Particular --</option>
                                        <template x-for="p in student.data.particulars" :key="p.id">
                                            <option :value="p.id"
                                                x-text="p.outstanding > 0 ? p.name + ' (owed: ' + tsh(p.outstanding) + ')' : p.name"></option>
                                        </template>
                                    </select>
                                    <input type="number" x-model="item.amount" placeholder="Amount" step="0.01" min="0"
                                        class="w-32 border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-green-400">
                                    <button @click="receipt.items.splice(idx,1)" x-show="receipt.items.length > 1"
                                        class="text-red-400 hover:text-red-600 text-xl leading-none">×</button>
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
                                class="px-4 rounded-lg bg-slate-100 text-slate-600 text-sm font-medium hover:bg-slate-200">Cancel</button>
                        </div>
                        <p x-show="receipt.msg" class="mt-2 text-xs font-semibold"
                           :class="receipt.ok ? 'text-green-700' : 'text-red-600'" x-text="receipt.msg"></p>
                    </div>

                </div>
            </template>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const SCHOOL_IDS   = @json($schools->pluck('id'));
const SCHOOL_NAMES = @json($schools->pluck('name', 'id'));

function ownerApp() {
    return {
        view:          'overview',
        activeSchoolId: null,
        openSchool:    null,

        schools:       [],
        statsLoading:  true,
        lastUpdated:   'Loading…',
        trendChart:    null,

        searchQuery:   '',
        searchResults: [],
        searchLoading: false,

        sList: { loading: false, data: [], q: '', page: 1, lastPage: 1, total: 0 },

        feSearch: { q: '', results: [] },

        student: { open: false, loading: false, data: null, schoolId: null, studentId: null, showReceipt: false },
        receipt: { date: new Date().toISOString().slice(0,10), bookId:'', items:[{particularId:'',amount:''}], notes:'', saving:false, msg:'', ok:false },

        // ── Init ──────────────────────────────────────────────────────────────
        init() {
            this.loadStats();
        },

        // ── Navigation ────────────────────────────────────────────────────────
        setView(v, schoolId) {
            this.view = v;
            if (schoolId) {
                this.activeSchoolId = schoolId;
                this.openSchool     = schoolId;
                if (v === 'students') {
                    this.sList = { loading: false, data: [], q: '', page: 1, lastPage: 1, total: 0 };
                    this.loadStudents();
                }
                if (v === 'fee-entry') {
                    this.feSearch = { q: '', results: [] };
                }
            }
            this.student.open = false;
        },

        toggleSidebar(id) {
            this.openSchool = this.openSchool === id ? null : id;
        },

        activeSchoolName() {
            return SCHOOL_NAMES[this.activeSchoolId] || '';
        },

        // ── Stats + chart ─────────────────────────────────────────────────────
        async loadStats() {
            this.statsLoading = true;
            try {
                const res    = await fetch('{{ route('superadmin.owner.live-stats') }}');
                this.schools = await res.json();
                this.lastUpdated = 'Updated ' + new Date().toLocaleTimeString();
                await this.$nextTick();
                this.renderChart();
            } finally {
                this.statsLoading = false;
            }
        },

        renderChart() {
            const canvas = document.getElementById('trendChart');
            if (!canvas) return;
            if (this.trendChart) this.trendChart.destroy();

            const ok = this.schools.filter(s => !s.error && s.trend);
            if (!ok.length) return;

            const days   = Object.keys(ok[0].trend);
            const labels = days.map(d => new Date(d + 'T00:00:00').toLocaleDateString('en', {month:'short',day:'numeric'}));
            const colors = ['#7c3aed','#0ea5e9','#0d9488'];

            this.trendChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: ok.map((s, i) => ({
                        label:           SCHOOL_NAMES[s.id] || s.name,
                        data:            Object.values(s.trend),
                        borderColor:     colors[i % colors.length],
                        backgroundColor: colors[i % colors.length] + '18',
                        borderWidth: 2, tension: 0.35, fill: true, pointRadius: 3,
                    }))
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { font:{size:11}, boxWidth:12, padding:16 } },
                        tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: TSh ${Number(ctx.raw).toLocaleString()}` } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true, grid: { color: '#f1f5f9' },
                            ticks: { font:{size:10}, callback: v => v >= 1000 ? 'TSh '+(v/1000).toFixed(0)+'k' : 'TSh '+v }
                        },
                        x: { grid: { display: false }, ticks: { font:{size:10} } }
                    }
                }
            });
        },

        // ── Helpers ───────────────────────────────────────────────────────────
        tsh(v) {
            if (!v && v !== 0) return '—';
            return 'TSh ' + Number(v).toLocaleString('en-US', {minimumFractionDigits:0, maximumFractionDigits:0});
        },

        summaryTiles() {
            const ok  = this.schools.filter(s => !s.error);
            const sum = k => ok.reduce((a,s) => a + (Number(s[k])||0), 0);
            return [
                { label:'Students',    value: ok.reduce((a,s)=>a+(s.students||0),0).toLocaleString(), color:'text-slate-800'  },
                { label:'Today',       value: this.tsh(sum('today_collection')),  color:'text-green-700'  },
                { label:'This Month',  value: this.tsh(sum('month_collection')),  color:'text-blue-700'   },
                { label:'This Year',   value: this.tsh(sum('year_collection')),   color:'text-indigo-700' },
                { label:'Outstanding', value: this.tsh(sum('outstanding')),       color:'text-red-600'    },
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
            const name = SCHOOL_NAMES[id] || '?';
            return name.split(' ').filter(w=>w.length>2).slice(0,2).map(w=>w[0]).join('').toUpperCase() || name.slice(0,3).toUpperCase();
        },

        schoolBadge(id) {
            const idx = SCHOOL_IDS.indexOf(Number(id));
            return ['bg-purple-100 text-purple-700','bg-blue-100 text-blue-700','bg-teal-100 text-teal-700'][Math.max(0,idx) % 3];
        },

        // ── Cross-school search ───────────────────────────────────────────────
        async doSearch() {
            const q = this.searchQuery.trim();
            if (q.length < 2) { this.searchResults = []; return; }
            this.searchLoading = true;
            try {
                const res = await fetch(`{{ route('superadmin.owner.search') }}?q=${encodeURIComponent(q)}`);
                this.searchResults = await res.json();
            } finally { this.searchLoading = false; }
        },

        // ── Students list (per school) ────────────────────────────────────────
        async loadStudents(page) {
            if (!this.activeSchoolId) return;
            page = page || 1;
            this.sList.loading = true;
            this.sList.page    = page;
            try {
                const res = await fetch(`/superadmin/owner/students/${this.activeSchoolId}?q=${encodeURIComponent(this.sList.q)}&page=${page}`);
                const j   = await res.json();
                this.sList.data     = j.data;
                this.sList.total    = j.total;
                this.sList.lastPage = j.last_page;
            } finally { this.sList.loading = false; }
        },

        // ── Fee entry: school-scoped search ──────────────────────────────────
        async feeSearch() {
            const q = this.feSearch.q.trim();
            if (q.length < 2) { this.feSearch.results = []; return; }
            const res = await fetch(`/superadmin/owner/students/${this.activeSchoolId}?q=${encodeURIComponent(q)}&page=1`);
            const j   = await res.json();
            this.feSearch.results = (j.data || []).slice(0, 10);
        },

        // ── Student detail ────────────────────────────────────────────────────
        async openStudent(schoolId, studentId) {
            this.searchResults = [];
            this.searchQuery   = '';
            this.student = { open: true, loading: true, data: null, schoolId, studentId, showReceipt: false };
            this.receipt = { date: new Date().toISOString().slice(0,10), bookId:'', items:[{particularId:'',amount:''}], notes:'', saving:false, msg:'', ok:false };

            const res = await fetch(`/superadmin/owner/student/${schoolId}/${studentId}`);
            this.student.data    = await res.json();
            this.student.loading = false;
        },

        // ── Submit receipt ────────────────────────────────────────────────────
        async submitReceipt() {
            this.receipt.saving = true;
            this.receipt.msg    = '';
            const items = this.receipt.items.filter(i => i.particularId && i.amount > 0);
            if (!items.length || !this.receipt.bookId || !this.receipt.date) {
                this.receipt.msg = 'Fill in date, book and at least one particular with amount.';
                this.receipt.ok  = false; this.receipt.saving = false; return;
            }
            try {
                const res = await fetch(`/superadmin/owner/receipt/${this.student.schoolId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        date: this.receipt.date, student_id: this.student.studentId,
                        book_id: parseInt(this.receipt.bookId), notes: this.receipt.notes,
                        items: items.map(i => ({ particular_id: parseInt(i.particularId), amount: parseFloat(i.amount) })),
                        advance_amount: 0,
                    }),
                });
                const json = await res.json();
                if (res.ok) {
                    const msg = `Saved! Receipt #${json.voucher_id} — ${this.tsh(json.total)}`;
                    this.receipt.items = [{particularId:'',amount:''}];
                    const sid = this.student.studentId, schid = this.student.schoolId;
                    await this.openStudent(schid, sid);
                    this.student.showReceipt = true;
                    this.receipt.msg = msg;
                    this.receipt.ok  = true;
                } else {
                    this.receipt.msg = json.error || 'Failed to save.';
                    this.receipt.ok  = false;
                }
            } catch { this.receipt.msg = 'Network error.'; this.receipt.ok = false; }
            finally  { this.receipt.saving = false; }
        },
    };
}
</script>
@endpush
