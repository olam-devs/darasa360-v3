@extends('layouts.superadmin')

@section('title', 'Command Centre — Darasa Finance')
@section('nav_title', 'Command Centre')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>document.addEventListener('alpine:init', () => Alpine.store('schoolStats', {}));</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
[x-cloak]{display:none!important}
.sbl{display:flex;align-items:center;gap:8px;padding:6px 12px;border-radius:8px;font-size:13px;color:#475569;cursor:pointer;transition:all .15s;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border:none;background:transparent;width:100%;text-align:left;text-decoration:none}
.sbl:hover{background:#f1f5f9;color:#1e293b}
.sbl.active{background:#ede9fe;color:#6d28d9;font-weight:600}
.sbl.ext{color:#0ea5e9}
.sbl.ext:hover{background:#e0f2fe}
.rate-ring{position:relative;display:inline-flex;align-items:center;justify-content:center}
</style>
@endpush

@section('content')
<div x-data="ownerApp()" x-init="init()" x-cloak
     class="flex -mx-4 sm:-mx-6 lg:-mx-8 -my-8"
     style="min-height:calc(100vh - 4rem)">

    {{-- ══════════════════════════════ SIDEBAR ══════════════════════════════ --}}
    <aside class="w-56 shrink-0 border-r border-slate-100 bg-white overflow-y-auto"
           style="position:sticky;top:4rem;height:calc(100vh - 4rem)">
        <div class="px-3 py-4 space-y-0.5">

            <button @click="setView('overview')" :class="view==='overview'?'active':''" class="sbl font-semibold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Overview
            </button>

            <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">Schools</div>

            @foreach($schools as $si => $school)
            @php $sid=$school->id; $cs=['blue','purple','teal'][$si%3]; @endphp
            <div>
                <button @click="toggleSidebar({{$sid}})"
                    :class="openSchool=={{$sid}}?'bg-slate-100 font-semibold text-slate-800':''"
                    class="sbl">
                    <svg class="w-4 h-4 shrink-0 text-{{$cs}}-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 21V8a2 2 0 012-2h12a2 2 0 012 2v13M1 21h22M9 21v-5a1 1 0 011-1h4a1 1 0 011 1v5"/></svg>
                    <span class="truncate text-xs">{{$school->name}}</span>
                    <svg class="w-3 h-3 ml-auto shrink-0 transition-transform" :class="openSchool=={{$sid}}?'rotate-180':''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="openSchool=={{$sid}}" x-transition class="ml-3 mt-0.5 space-y-0.5 border-l border-slate-100 pl-3">

                    <button @click="setView('analytics',{{$sid}})"
                        :class="view==='analytics'&&activeSchoolId==={{$sid}}?'active':''"
                        class="sbl text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Analytics
                        <span x-show="pendingBadge({{$sid}}) > 0"
                              class="ml-auto shrink-0 text-[10px] bg-amber-400 text-white font-bold rounded-full px-1.5 leading-4"
                              x-text="pendingBadge({{$sid}})"></span>
                    </button>

                    <button @click="setView('students',{{$sid}})"
                        :class="view==='students'&&activeSchoolId==={{$sid}}?'active':''"
                        class="sbl text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-5-3.87M9 20H4v-1a4 4 0 015-3.87m4-3a4 4 0 10-8 0 4 4 0 008 0zm6-3a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Students & Balances
                    </button>

                    <button @click="setView('fee-entry',{{$sid}})"
                        :class="view==='fee-entry'&&activeSchoolId==={{$sid}}?'active':''"
                        class="sbl text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Fee Entry
                    </button>

                    <a href="{{route('superadmin.owner.enter',$school)}}?to=ledgers" target="_blank" class="sbl ext text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6M4 21h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v15a1 1 0 001 1z"/></svg>
                        Ledgers ↗
                    </a>
                    <a href="{{route('superadmin.owner.enter',$school)}}?to=invoices" target="_blank" class="sbl ext text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                        Invoices ↗
                    </a>
                    <a href="{{route('superadmin.owner.enter',$school)}}?to=sms" target="_blank" class="sbl ext text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        SMS ↗
                    </a>
                    <a href="{{route('superadmin.owner.enter',$school)}}?to=expenses" target="_blank" class="sbl ext text-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Expenses ↗
                    </a>

                </div>
            </div>
            @endforeach

        </div>
    </aside>

    {{-- ════════════════════════════ MAIN CONTENT ════════════════════════════ --}}
    <div class="flex-1 min-w-0 overflow-y-auto px-6 py-6">

        {{-- ─────────────────────────── OVERVIEW ────────────────────────────── --}}
        <div x-show="view==='overview'">

            {{-- Header --}}
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Analytics Overview</h1>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="lastUpdated"></p>
                </div>
                <button @click="loadStats()" :disabled="statsLoading"
                    class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40">
                    <svg :class="statsLoading?'animate-spin':''" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>

            {{-- Cross-school search --}}
            <div class="relative mb-6">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" x-model="searchQuery" @input.debounce.350ms="doSearch()" @keydown.escape="searchResults=[]"
                    placeholder="Search any student across all 3 schools…"
                    class="w-full rounded-xl border-2 border-slate-200 bg-white pl-12 pr-4 py-3 text-sm shadow-sm focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">
                <div x-show="searchLoading" class="absolute right-4 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                </div>
                <div x-show="searchResults.length>0" x-transition class="absolute z-40 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                    <template x-for="r in searchResults" :key="r.school_id+'-'+r.id">
                        <button @click="openStudent(r.school_id,r.id)"
                            class="flex w-full items-center justify-between px-4 py-2.5 text-left hover:bg-purple-50 border-b border-slate-50 last:border-0">
                            <div>
                                <span class="font-semibold text-slate-800 text-sm" x-text="r.name"></span>
                                <span class="ml-2 text-xs text-slate-400" x-text="r.class_name?'· '+r.class_name:''"></span>
                                <span class="ml-2 inline-block rounded-full px-2 py-0.5 text-xs font-semibold" :class="schoolBadge(r.school_id)" x-text="schoolShort(r.school_id)"></span>
                            </div>
                            <span class="text-xs font-semibold ml-4 shrink-0" :class="r.outstanding>0?'text-red-600':'text-slate-400'"
                                  x-text="r.outstanding>0?'Owed: '+tsh(r.outstanding):'Clear'"></span>
                        </button>
                    </template>
                </div>
                <div x-show="searchQuery.length>=2&&!searchLoading&&searchResults.length===0" x-transition
                     class="absolute z-40 mt-1 w-full rounded-xl border border-slate-100 bg-white shadow-xl px-4 py-3 text-sm text-slate-400">No students found.</div>
            </div>

            {{-- KPI tiles — 6 tiles incl. collection rate --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                <template x-if="statsLoading">
                    <template x-for="i in 6" :key="i">
                        <div class="rounded-xl bg-white border border-slate-100 shadow-sm px-4 py-3 animate-pulse">
                            <div class="h-2 w-16 bg-slate-200 rounded mb-2"></div><div class="h-6 w-20 bg-slate-100 rounded"></div>
                        </div>
                    </template>
                </template>
                <template x-if="!statsLoading">
                    <template x-for="tile in summaryTiles()" :key="tile.label">
                        <div class="rounded-xl bg-white border border-slate-100 shadow-sm px-4 py-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5" x-text="tile.label"></p>
                            <p class="font-bold text-base" :class="tile.color" x-text="tile.value"></p>
                            <template x-if="tile.sub">
                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="tile.sub"></p>
                            </template>
                        </div>
                    </template>
                </template>
            </div>

            {{-- Two-column: 7-day trend + school comparison chart --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <div class="lg:col-span-2 bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-slate-700 mb-3">7-Day Collection Trend</h2>
                    <div x-show="statsLoading" class="h-44 flex items-center justify-center text-slate-300 animate-pulse text-sm">Loading…</div>
                    <div x-show="!statsLoading" style="height:176px;position:relative"><canvas id="trendChart"></canvas></div>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-slate-700 mb-3">Collection Rate by School</h2>
                    <div x-show="statsLoading" class="h-44 flex items-center justify-center text-slate-300 animate-pulse text-sm">Loading…</div>
                    <div x-show="!statsLoading" style="height:176px;position:relative"><canvas id="comparisonChart"></canvas></div>
                </div>
            </div>

            {{-- Outstanding aging buckets --}}
            <div class="grid grid-cols-3 gap-4 mb-6">
                <template x-if="!statsLoading">
                    <template x-for="bucket in agingBuckets()" :key="bucket.label">
                        <div class="rounded-xl border shadow-sm px-5 py-4" :class="bucket.bg">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-xs font-bold uppercase tracking-wider" :class="bucket.labelColor" x-text="bucket.label"></p>
                                <span class="text-[10px] rounded-full px-2 py-0.5 font-semibold" :class="bucket.badge" x-text="bucket.days"></span>
                            </div>
                            <p class="text-2xl font-bold" :class="bucket.numColor" x-text="bucket.count"></p>
                            <p class="text-xs mt-0.5" :class="bucket.sub" x-text="bucket.desc"></p>
                        </div>
                    </template>
                </template>
                <template x-if="statsLoading">
                    <template x-for="i in 3" :key="i">
                        <div class="rounded-xl border border-slate-100 bg-white shadow-sm px-5 py-4 animate-pulse">
                            <div class="h-3 bg-slate-100 rounded w-1/2 mb-2"></div><div class="h-7 bg-slate-50 rounded w-1/3"></div>
                        </div>
                    </template>
                </template>
            </div>

            {{-- Net cash flow this month --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6" x-show="!statsLoading">
                @foreach($schools as $si => $school)
                @php $c=['blue','purple','teal'][$si%3]; @endphp
                <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4"
                     x-data="{s:null}" x-init="$watch('schools',v=>{s=v.find(x=>x.id=={{$school->id}})})">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">{{$school->name}}</p>
                    <template x-if="s&&!s.error">
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs"><span class="text-slate-500">Month collected</span><span class="font-semibold text-green-700" x-text="tsh(s.month_collection)"></span></div>
                            <div class="flex justify-between text-xs"><span class="text-slate-500">Month expenses</span><span class="font-semibold text-red-500" x-text="tsh(s.month_expenses)"></span></div>
                            <div class="border-t border-slate-100 pt-1.5 flex justify-between text-xs">
                                <span class="font-semibold text-slate-600">Net cash</span>
                                <span class="font-bold" :class="(s.month_collection - s.month_expenses)>=0?'text-emerald-700':'text-red-700'"
                                      x-text="tsh(s.month_collection - s.month_expenses)"></span>
                            </div>
                            <div class="flex justify-between text-xs pt-0.5">
                                <span class="text-slate-400">Collection rate</span>
                                <span class="font-bold" :class="s.collection_rate>=80?'text-emerald-700':s.collection_rate>=60?'text-amber-600':'text-red-600'"
                                      x-text="s.collection_rate+'%'"></span>
                            </div>
                        </div>
                    </template>
                </div>
                @endforeach
            </div>

            {{-- Recent receipts --}}
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <h2 class="text-sm font-bold text-slate-700 px-5 py-3 border-b border-slate-100">Recent Receipts — All Schools</h2>
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
                                <template x-for="row in allReceipts()" :key="row.school_id+'-'+row.id">
                                    <tr class="border-b border-slate-50 hover:bg-slate-50/60 cursor-pointer" @click="openStudent(row.school_id,row.student_id)">
                                        <td class="px-5 py-2"><span class="rounded-full px-2 py-0.5 text-[11px] font-bold" :class="schoolBadge(row.school_id)" x-text="schoolShort(row.school_id)"></span></td>
                                        <td class="px-5 py-2 text-slate-700 font-medium" x-text="row.student_name"></td>
                                        <td class="px-5 py-2 text-slate-400" x-text="row.date"></td>
                                        <td class="px-5 py-2 text-right font-semibold text-green-700" x-text="tsh(row.amount)"></td>
                                    </tr>
                                </template>
                                <template x-if="allReceipts().length===0">
                                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400 italic">No receipts yet</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

        </div>{{-- /overview --}}

        {{-- ──────────────────────── PER-SCHOOL ANALYTICS ──────────────────── --}}
        <div x-show="view==='analytics'">

            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900" x-text="activeSchoolName()"></h1>
                    <p class="text-xs text-slate-400 mt-0.5">School Analytics</p>
                </div>
                <button @click="loadAnalytics(activeSchoolId)" :disabled="anl.loading"
                    class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40">
                    <svg :class="anl.loading?'animate-spin':''" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>

            {{-- Loading skeleton --}}
            <template x-if="anl.loading">
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4"><template x-for="i in 3" :key="i"><div class="h-24 bg-white rounded-xl border border-slate-100 animate-pulse"></div></template></div>
                    <div class="h-48 bg-white rounded-xl border border-slate-100 animate-pulse"></div>
                    <div class="grid grid-cols-2 gap-4"><div class="h-56 bg-white rounded-xl border border-slate-100 animate-pulse"></div><div class="h-56 bg-white rounded-xl border border-slate-100 animate-pulse"></div></div>
                </div>
            </template>

            <template x-if="!anl.loading && anl.data">
                <div>

                    {{-- Row 1: Collection funnel + badges --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

                        {{-- Billed --}}
                        <div class="bg-white rounded-xl border border-slate-100 shadow-sm px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Total Billed</p>
                            <p class="text-xl font-bold text-slate-800" x-text="tsh(anl.data.funnel.totalBilled)"></p>
                            <p class="text-xs text-slate-400 mt-0.5">All fee items assigned</p>
                        </div>

                        {{-- Rate ring --}}
                        <div class="bg-white rounded-xl border border-slate-100 shadow-sm px-5 py-4 flex flex-col items-center justify-center">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Collection Rate</p>
                            <div class="relative w-20 h-20 mb-1">
                                <svg viewBox="0 0 36 36" class="w-20 h-20 -rotate-90">
                                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f1f5f9" stroke-width="3.5"/>
                                    <circle cx="18" cy="18" r="15.9155" fill="none"
                                        :stroke="anl.data.funnel.collectionRate>=80?'#10b981':anl.data.funnel.collectionRate>=60?'#f59e0b':'#ef4444'"
                                        stroke-width="3.5"
                                        stroke-dasharray="100"
                                        :stroke-dashoffset="100 - anl.data.funnel.collectionRate"
                                        stroke-linecap="round"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-lg font-bold" :class="anl.data.funnel.collectionRate>=80?'text-emerald-600':anl.data.funnel.collectionRate>=60?'text-amber-500':'text-red-500'"
                                          x-text="anl.data.funnel.collectionRate+'%'"></span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400">of billed fees collected</p>
                        </div>

                        {{-- Outstanding + badges --}}
                        <div class="bg-white rounded-xl border border-slate-100 shadow-sm px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Outstanding</p>
                            <p class="text-xl font-bold text-red-600" x-text="tsh(anl.data.funnel.totalOutstanding)"></p>
                            <div class="mt-2 flex gap-2 flex-wrap">
                                <span x-show="anl.data.pending_approvals > 0"
                                      class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 text-[11px] font-bold px-2 py-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span x-text="anl.data.pending_approvals+' pending expenses'"></span>
                                </span>
                                <span :class="anl.data.sms_remaining < 50?'bg-red-100 text-red-700':'bg-slate-100 text-slate-600'"
                                      class="inline-flex items-center gap-1 rounded-full text-[11px] font-bold px-2 py-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    <span x-text="anl.data.sms_remaining+' SMS credits'"></span>
                                </span>
                            </div>
                        </div>

                    </div>

                    {{-- Row 2: 6-month bar chart --}}
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 mb-6">
                        <h3 class="text-sm font-bold text-slate-700 mb-3">6-Month Collection Trend</h3>
                        <div style="height:160px;position:relative"><canvas id="schoolTrendChart"></canvas></div>
                    </div>

                    {{-- Row 3: Particular breakdown + Outstanding by class --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

                        {{-- Particular breakdown --}}
                        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                            <h3 class="text-sm font-bold text-slate-700 mb-3">Fee Collection by Particular</h3>
                            <div class="space-y-3">
                                <template x-for="p in anl.data.particulars" :key="p.id">
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-slate-700 truncate max-w-[55%]" x-text="p.name"></span>
                                            <span class="shrink-0 ml-2" :class="p.collection_rate>=80?'text-emerald-600':p.collection_rate>=60?'text-amber-500':'text-red-500'"
                                                  x-text="p.collection_rate+'% · '+tsh(p.total_outstanding)+' owed'"></span>
                                        </div>
                                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-2 rounded-full transition-all"
                                                 :class="p.collection_rate>=80?'bg-emerald-500':p.collection_rate>=60?'bg-amber-400':'bg-red-500'"
                                                 :style="'width:'+Math.min(100,p.collection_rate)+'%'"></div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!anl.data.particulars.length">
                                    <p class="text-xs text-slate-400 italic">No fee data yet</p>
                                </template>
                            </div>
                        </div>

                        {{-- Outstanding by class --}}
                        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
                            <h3 class="text-sm font-bold text-slate-700 mb-3">Outstanding by Class</h3>
                            <div class="space-y-2">
                                <template x-for="cls in anl.data.by_class" :key="cls.class_name">
                                    <div class="flex items-center justify-between text-xs px-1">
                                        <span class="text-slate-700 font-medium" x-text="cls.class_name"></span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-slate-400" x-text="cls.student_count+' students'"></span>
                                            <span class="font-bold text-red-600" x-text="tsh(cls.outstanding)"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!anl.data.by_class.length">
                                    <p class="text-xs text-slate-400 italic">No outstanding fees</p>
                                </template>
                            </div>
                        </div>

                    </div>

                    {{-- Row 4: Top 10 debtors --}}
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden mb-6">
                        <h3 class="text-sm font-bold text-slate-700 px-5 py-3 border-b border-slate-100">Top Debtors</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-100">
                                    <tr class="text-[10px] uppercase tracking-wider text-slate-400">
                                        <th class="px-4 py-2 text-left font-semibold">#</th>
                                        <th class="px-4 py-2 text-left font-semibold">Student</th>
                                        <th class="px-4 py-2 text-left font-semibold">Class</th>
                                        <th class="px-4 py-2 text-right font-semibold">Outstanding</th>
                                        <th class="px-4 py-2 text-right font-semibold">Last Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(d,idx) in anl.data.top_debtors" :key="d.id">
                                        <tr class="border-b border-slate-50 hover:bg-slate-50/60 cursor-pointer"
                                            @click="openStudent(activeSchoolId,d.id)">
                                            <td class="px-4 py-2.5 text-slate-400 text-xs" x-text="idx+1"></td>
                                            <td class="px-4 py-2.5 font-medium text-slate-800" x-text="d.name"></td>
                                            <td class="px-4 py-2.5 text-slate-500" x-text="d.class_name||'—'"></td>
                                            <td class="px-4 py-2.5 text-right font-bold text-red-600" x-text="tsh(d.outstanding)"></td>
                                            <td class="px-4 py-2.5 text-right">
                                                <template x-if="d.last_receipt">
                                                    <span :class="d.days_since_payment>90?'text-red-600 font-semibold':d.days_since_payment>30?'text-amber-500':'text-slate-400'"
                                                          class="text-xs" x-text="d.days_since_payment+'d ago'"></span>
                                                </template>
                                                <template x-if="!d.last_receipt">
                                                    <span class="text-xs font-semibold text-red-700">Never paid</span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="!anl.data.top_debtors.length">
                                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">No outstanding fees</td></tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Row 5: Expense budget burn --}}
                    <template x-if="anl.data.expense_budget && anl.data.expense_budget.length">
                        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 mb-6">
                            <h3 class="text-sm font-bold text-slate-700 mb-3">Expense Budget Burn</h3>
                            <div class="space-y-3">
                                <template x-for="e in anl.data.expense_budget" :key="e.name">
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-slate-700 truncate max-w-[50%]" x-text="e.name"></span>
                                            <span class="shrink-0 ml-2 text-slate-500"
                                                  x-text="tsh(e.spent)+' / '+tsh(e.budgeted)+' ('+e.burn_rate+'%)'"></span>
                                        </div>
                                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-2 rounded-full transition-all"
                                                 :class="e.burn_rate>=90?'bg-red-500':e.burn_rate>=70?'bg-amber-400':'bg-blue-500'"
                                                 :style="'width:'+Math.min(100,e.burn_rate)+'%'"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                </div>
            </template>

        </div>{{-- /analytics --}}

        {{-- ─────────────────────────── STUDENTS ───────────────────────────── --}}
        <div x-show="view==='students'">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900" x-text="activeSchoolName()"></h1>
                    <p class="text-xs text-slate-400 mt-0.5">Students & Outstanding Balances</p>
                </div>
                <button @click="setView('fee-entry',activeSchoolId)"
                    class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-green-700">
                    + Record Receipt
                </button>
            </div>
            <div class="relative mb-4 max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" x-model="sList.q" @input.debounce.300ms="loadStudents()" placeholder="Search by name…"
                    class="w-full border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:border-blue-400">
            </div>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                <template x-if="sList.loading">
                    <div class="p-6 space-y-2 animate-pulse"><template x-for="i in 8" :key="i"><div class="h-8 rounded bg-slate-50"></div></template></div>
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
                                        <td class="px-4 py-2.5 text-slate-500" x-text="s.class_name||'—'"></td>
                                        <td class="px-4 py-2.5 text-right font-semibold"
                                            :class="s.outstanding>0?'text-red-600':'text-slate-400'"
                                            x-text="s.outstanding>0?tsh(s.outstanding):'Clear'"></td>
                                        <td class="px-4 py-2.5 text-right text-amber-600 font-medium"
                                            x-text="s.advance_balance>0?tsh(s.advance_balance):'—'"></td>
                                        <td class="px-4 py-2.5 text-right">
                                            <button @click="openStudent(activeSchoolId,s.id)"
                                                class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-blue-100 hover:text-blue-700">Details</button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="sList.data.length===0">
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">No students found</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
                <div x-show="sList.lastPage>1" class="flex items-center justify-between px-4 py-3 border-t border-slate-100 text-xs text-slate-500">
                    <span x-text="`${sList.total} students total`"></span>
                    <div class="flex gap-1">
                        <button @click="loadStudents(sList.page-1)" :disabled="sList.page<=1" class="px-2.5 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-40">←</button>
                        <span class="px-2.5 py-1" x-text="`${sList.page} / ${sList.lastPage}`"></span>
                        <button @click="loadStudents(sList.page+1)" :disabled="sList.page>=sList.lastPage" class="px-2.5 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-40">→</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─────────────────────────── FEE ENTRY ──────────────────────────── --}}
        <div x-show="view==='fee-entry'">
            <div class="mb-5">
                <h1 class="text-xl font-bold text-slate-900" x-text="activeSchoolName()"></h1>
                <p class="text-xs text-slate-400 mt-0.5">Fee Entry — search a student to begin</p>
            </div>
            <div class="relative mb-6 max-w-lg">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" x-model="feSearch.q" @input.debounce.300ms="feeSearch()" @keydown.escape="feSearch.results=[]"
                    placeholder="Search student by name…"
                    class="w-full border-2 border-slate-200 rounded-lg pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-green-400">
                <div x-show="feSearch.results.length>0" x-transition
                     class="absolute z-40 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                    <template x-for="s in feSearch.results" :key="s.id">
                        <button @click="openStudent(activeSchoolId,s.id);feSearch.results=[];feSearch.q=s.name"
                            class="flex w-full items-center justify-between px-4 py-2.5 text-left hover:bg-green-50 border-b border-slate-50 last:border-0">
                            <div>
                                <span class="font-semibold text-sm" x-text="s.name"></span>
                                <span class="ml-2 text-xs text-slate-400" x-text="s.class_name?'· '+s.class_name:''"></span>
                            </div>
                            <span class="text-xs font-semibold ml-4 shrink-0" :class="s.outstanding>0?'text-red-600':'text-slate-400'"
                                  x-text="s.outstanding>0?'Owed: '+tsh(s.outstanding):'Clear'"></span>
                        </button>
                    </template>
                </div>
            </div>
            <p class="text-sm text-slate-400 italic" x-show="!student.open">Search for a student above to view their fee details and record a receipt.</p>
        </div>

    </div>{{-- /main --}}

    {{-- ═══════════════════════════ STUDENT DETAIL MODAL ═══════════════════════ --}}
    <div x-show="student.open" x-transition.opacity
         class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 overflow-y-auto"
         @keydown.escape.window="student.open=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-6" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div x-show="!student.loading">
                    <h3 class="font-bold text-slate-800 text-lg" x-text="student.data?.student?.name"></h3>
                    <p class="text-xs text-slate-400" x-text="[student.data?.school_name,student.data?.student?.class_name].filter(Boolean).join(' · ')"></p>
                </div>
                <div x-show="student.loading" class="h-8 w-48 bg-slate-100 rounded animate-pulse"></div>
                <button @click="student.open=false" class="text-slate-400 hover:text-slate-600 ml-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div x-show="student.loading" class="p-6 space-y-3 animate-pulse">
                <div class="h-4 bg-slate-100 rounded w-3/4"></div><div class="h-4 bg-slate-100 rounded w-1/2"></div><div class="h-4 bg-slate-100 rounded w-2/3"></div>
            </div>
            <template x-if="!student.loading&&student.data">
                <div class="p-6">
                    <div class="mb-5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Fee Items</h4>
                        <div class="rounded-xl border border-slate-100 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50"><tr class="text-xs text-slate-400 uppercase tracking-wide">
                                    <th class="px-4 py-2 text-left font-semibold">Particular</th>
                                    <th class="px-4 py-2 text-right font-semibold">Charged</th>
                                    <th class="px-4 py-2 text-right font-semibold">Paid</th>
                                    <th class="px-4 py-2 text-right font-semibold">Outstanding</th>
                                </tr></thead>
                                <tbody>
                                    <template x-for="p in student.data.particulars" :key="p.id">
                                        <tr class="border-t border-slate-50">
                                            <td class="px-4 py-2 text-slate-700" x-text="p.name"></td>
                                            <td class="px-4 py-2 text-right text-slate-500" x-text="tsh(p.sales)"></td>
                                            <td class="px-4 py-2 text-right text-green-600" x-text="tsh(p.credit)"></td>
                                            <td class="px-4 py-2 text-right font-semibold" :class="p.outstanding>0?'text-red-600':'text-slate-400'"
                                                x-text="p.outstanding>0?tsh(p.outstanding):'Paid'"></td>
                                        </tr>
                                    </template>
                                    <template x-if="!student.data.particulars.length">
                                        <tr><td colspan="4" class="px-4 py-3 text-slate-400 italic text-xs">No fee items assigned</td></tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 px-1">Advance held: <strong class="text-amber-600" x-text="tsh(student.data.student.advance_balance)"></strong></p>
                    </div>
                    <div class="mb-5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Recent Receipts</h4>
                        <template x-if="student.data.receipts.length>0">
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
                        <template x-if="!student.data.receipts.length">
                            <p class="text-xs text-slate-400 italic px-2">No receipts yet</p>
                        </template>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-5">
                        <a :href="`/superadmin/owner/invoice/${student.schoolId}/${student.studentId}`" target="_blank"
                           class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                            Download Invoice
                        </a>
                        <button @click="student.showReceipt=!student.showReceipt"
                            :class="student.showReceipt?'bg-slate-500 hover:bg-slate-600':'bg-green-600 hover:bg-green-700'"
                            class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            <span x-text="student.showReceipt?'Cancel':'Record Receipt'"></span>
                        </button>
                    </div>
                    <div x-show="student.showReceipt" x-transition class="rounded-xl border-2 border-green-200 bg-green-50/50 p-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-green-700 mb-3">Record Fee Receipt</h4>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Date *</label>
                                <input type="date" x-model="receipt.date" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-green-400">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Book *</label>
                                <select x-model="receipt.bookId" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-green-400">
                                    <option value="">-- Select --</option>
                                    <template x-for="b in student.data.books" :key="b.id">
                                        <option :value="b.id" x-text="b.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Particulars *</label>
                            <template x-for="(item,idx) in receipt.items" :key="idx">
                                <div class="flex gap-2 mb-1.5 items-center">
                                    <select x-model="item.particularId" class="flex-1 border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-green-400">
                                        <option value="">-- Particular --</option>
                                        <template x-for="p in student.data.particulars" :key="p.id">
                                            <option :value="p.id" x-text="p.outstanding>0?p.name+' (owed: '+tsh(p.outstanding)+')':p.name"></option>
                                        </template>
                                    </select>
                                    <input type="number" x-model="item.amount" placeholder="Amount" step="0.01" min="0"
                                        class="w-32 border border-slate-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-green-400">
                                    <button @click="receipt.items.splice(idx,1)" x-show="receipt.items.length>1" class="text-red-400 hover:text-red-600 text-xl leading-none">×</button>
                                </div>
                            </template>
                            <button @click="receipt.items.push({particularId:'',amount:''})" class="text-xs text-green-700 font-semibold hover:underline mt-1">+ Add particular</button>
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Notes</label>
                            <input type="text" x-model="receipt.notes" placeholder="Optional" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-green-400">
                        </div>
                        <div class="flex gap-2">
                            <button @click="submitReceipt()" :disabled="receipt.saving"
                                class="flex-1 rounded-lg bg-green-600 py-2 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-50">
                                <span x-show="!receipt.saving">Save Receipt</span><span x-show="receipt.saving">Saving…</span>
                            </button>
                            <button @click="student.showReceipt=false" class="px-4 rounded-lg bg-slate-100 text-slate-600 text-sm font-medium hover:bg-slate-200">Cancel</button>
                        </div>
                        <p x-show="receipt.msg" class="mt-2 text-xs font-semibold" :class="receipt.ok?'text-green-700':'text-red-600'" x-text="receipt.msg"></p>
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
const SCHOOL_NAMES = @json($schools->pluck('name','id'));

function ownerApp() {
    return {
        view:'overview', activeSchoolId:null, openSchool:null,
        schools:[], statsLoading:true, lastUpdated:'Loading…',
        charts:{ trend:null, comparison:null, schoolTrend:null },
        searchQuery:'', searchResults:[], searchLoading:false,
        sList:{ loading:false, data:[], q:'', page:1, lastPage:1, total:0 },
        feSearch:{ q:'', results:[] },
        anl:{ loading:false, data:null },
        anlCache:{},   // cache per school id
        student:{ open:false, loading:false, data:null, schoolId:null, studentId:null, showReceipt:false },
        receipt:{ date:new Date().toISOString().slice(0,10), bookId:'', items:[{particularId:'',amount:''}], notes:'', saving:false, msg:'', ok:false },

        init(){ this.loadStats(); },

        // ── Navigation ────────────────────────────────────────────────────────
        setView(v, schoolId) {
            this.view = v;
            if (schoolId) {
                this.activeSchoolId = schoolId;
                this.openSchool     = schoolId;
            }
            this.student.open = false;
            if (v==='students') {
                this.sList = { loading:false, data:[], q:'', page:1, lastPage:1, total:0 };
                this.loadStudents();
            }
            if (v==='fee-entry') this.feSearch = { q:'', results:[] };
            if (v==='analytics') this.loadAnalytics(schoolId);
        },

        toggleSidebar(id) { this.openSchool = this.openSchool===id ? null : id; },
        activeSchoolName() { return SCHOOL_NAMES[this.activeSchoolId]||''; },

        // ── Pending badge (from cached analytics) ─────────────────────────────
        pendingBadge(schoolId) {
            return this.anlCache[schoolId]?.pending_approvals || 0;
        },

        // ── Overview stats ────────────────────────────────────────────────────
        async loadStats() {
            this.statsLoading = true;
            try {
                const res    = await fetch('{{ route('superadmin.owner.live-stats') }}');
                this.schools = await res.json();
                this.lastUpdated = 'Updated ' + new Date().toLocaleTimeString();
                await this.$nextTick();
                this.renderTrendChart();
                this.renderComparisonChart();
            } finally { this.statsLoading = false; }
        },

        renderTrendChart() {
            const canvas = document.getElementById('trendChart');
            if (!canvas) return;
            if (this.charts.trend) this.charts.trend.destroy();
            const ok = this.schools.filter(s => !s.error && s.trend);
            if (!ok.length) return;
            const days   = Object.keys(ok[0].trend);
            const labels = days.map(d => new Date(d+'T00:00:00').toLocaleDateString('en',{month:'short',day:'numeric'}));
            const colors = ['#7c3aed','#0ea5e9','#0d9488'];
            this.charts.trend = new Chart(canvas, {
                type:'line',
                data:{ labels, datasets: ok.map((s,i) => ({
                    label: SCHOOL_NAMES[s.id]||s.name, data: Object.values(s.trend),
                    borderColor: colors[i%colors.length], backgroundColor: colors[i%colors.length]+'18',
                    borderWidth:2, tension:0.35, fill:true, pointRadius:3,
                }))},
                options:{ responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{ labels:{ font:{size:10}, boxWidth:10, padding:12 }},
                              tooltip:{ callbacks:{ label: ctx => ` ${ctx.dataset.label}: TSh ${Number(ctx.raw).toLocaleString()}` }}},
                    scales:{ y:{ beginAtZero:true, grid:{color:'#f1f5f9'}, ticks:{ font:{size:10}, callback: v => v>=1000?'TSh '+(v/1000).toFixed(0)+'k':'TSh '+v }},
                             x:{ grid:{display:false}, ticks:{font:{size:10}} }}}
            });
        },

        renderComparisonChart() {
            const canvas = document.getElementById('comparisonChart');
            if (!canvas) return;
            if (this.charts.comparison) this.charts.comparison.destroy();
            const ok = this.schools.filter(s => !s.error);
            if (!ok.length) return;
            const colors = ['#7c3aed','#0ea5e9','#0d9488'];
            this.charts.comparison = new Chart(canvas, {
                type:'bar',
                data:{
                    labels: ok.map(s => SCHOOL_NAMES[s.id]||s.name),
                    datasets:[{
                        label:'Collection Rate %',
                        data: ok.map(s => s.collection_rate||0),
                        backgroundColor: ok.map((_,i) => colors[i%colors.length]+'cc'),
                        borderColor: ok.map((_,i) => colors[i%colors.length]),
                        borderWidth:2, borderRadius:6,
                    }]
                },
                options:{ responsive:true, maintainAspectRatio:false, indexAxis:'y',
                    plugins:{ legend:{display:false},
                              tooltip:{ callbacks:{ label: ctx => ` ${ctx.raw}%` }}},
                    scales:{ x:{ beginAtZero:true, max:100, grid:{color:'#f1f5f9'},
                                 ticks:{font:{size:10}, callback: v => v+'%'}},
                             y:{ grid:{display:false}, ticks:{font:{size:10}} }}}
            });
        },

        renderSchoolTrendChart() {
            const canvas = document.getElementById('schoolTrendChart');
            if (!canvas || !this.anl.data) return;
            if (this.charts.schoolTrend) this.charts.schoolTrend.destroy();
            const data   = this.anl.data.trend_6m;
            const colors = ['#7c3aed','#0ea5e9','#0d9488'];
            const idx    = SCHOOL_IDS.indexOf(Number(this.activeSchoolId));
            const color  = colors[Math.max(0,idx)%colors.length];
            this.charts.schoolTrend = new Chart(canvas, {
                type:'bar',
                data:{ labels: data.map(m => m.label), datasets:[{
                    label:'Collections', data: data.map(m => m.amount),
                    backgroundColor: color+'bb', borderColor: color, borderWidth:2, borderRadius:6,
                }]},
                options:{ responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{display:false},
                              tooltip:{ callbacks:{ label: ctx => ` TSh ${Number(ctx.raw).toLocaleString()}` }}},
                    scales:{ y:{ beginAtZero:true, grid:{color:'#f1f5f9'},
                                 ticks:{ font:{size:10}, callback: v => v>=1000?'TSh '+(v/1000).toFixed(0)+'k':'TSh '+v }},
                             x:{ grid:{display:false}, ticks:{font:{size:10}} }}}
            });
        },

        // ── Helpers ───────────────────────────────────────────────────────────
        tsh(v) {
            if (!v && v!==0) return '—';
            return 'TSh '+Number(v).toLocaleString('en-US',{minimumFractionDigits:0,maximumFractionDigits:0});
        },

        summaryTiles() {
            const ok  = this.schools.filter(s => !s.error);
            const sum = k => ok.reduce((a,s) => a+(Number(s[k])||0), 0);
            const totalBilled    = ok.reduce((a,s) => a+(s.total_billed||0), 0);
            const totalCollected = ok.reduce((a,s) => a+(s.total_collected||0), 0);
            const overallRate    = totalBilled > 0 ? Math.round(totalCollected/totalBilled*100) : 0;
            const rateColor      = overallRate>=80?'text-emerald-700':overallRate>=60?'text-amber-500':'text-red-600';
            return [
                { label:'Students',        value: ok.reduce((a,s)=>a+(s.students||0),0).toLocaleString(), color:'text-slate-800' },
                { label:'Today',           value: this.tsh(sum('today_collection')),  color:'text-green-700'   },
                { label:'This Month',      value: this.tsh(sum('month_collection')),  color:'text-blue-700'    },
                { label:'This Year',       value: this.tsh(sum('year_collection')),   color:'text-indigo-700'  },
                { label:'Outstanding',     value: this.tsh(sum('outstanding')),       color:'text-red-600'     },
                { label:'Collection Rate', value: overallRate+'%', color: rateColor,
                  sub: this.tsh(totalCollected)+' of '+this.tsh(totalBilled) },
            ];
        },

        agingBuckets() {
            const ok = this.schools.filter(s => !s.error && s.aging);
            const sum = k => ok.reduce((a,s) => a+(s.aging[k]||0), 0);
            return [
                { label:'Recent Payers', days:'< 30 days', count: sum('recent'),
                  bg:'bg-emerald-50 border-emerald-100', labelColor:'text-emerald-700',
                  badge:'bg-emerald-100 text-emerald-700', numColor:'text-emerald-800',
                  desc:'students with outstanding who paid recently', sub:'text-emerald-600' },
                { label:'Overdue', days:'30 – 90 days', count: sum('overdue'),
                  bg:'bg-amber-50 border-amber-100', labelColor:'text-amber-700',
                  badge:'bg-amber-100 text-amber-700', numColor:'text-amber-800',
                  desc:'students who haven\'t paid in 1–3 months', sub:'text-amber-600' },
                { label:'Critical', days:'90+ days / never', count: sum('critical'),
                  bg:'bg-red-50 border-red-100', labelColor:'text-red-700',
                  badge:'bg-red-100 text-red-700', numColor:'text-red-800',
                  desc:'students with no payment in 3+ months', sub:'text-red-500' },
            ];
        },

        allReceipts() {
            const rows = [];
            this.schools.filter(s => !s.error && s.recent_receipts).forEach(s => {
                (s.recent_receipts||[]).forEach(r => rows.push({...r, school_id:s.id}));
            });
            rows.sort((a,b) => new Date(b.created_at)-new Date(a.created_at));
            return rows.slice(0,20);
        },

        schoolShort(id) {
            const name = SCHOOL_NAMES[id]||'?';
            return name.split(' ').filter(w=>w.length>2).slice(0,2).map(w=>w[0]).join('').toUpperCase()||name.slice(0,3).toUpperCase();
        },

        schoolBadge(id) {
            const idx = SCHOOL_IDS.indexOf(Number(id));
            return ['bg-purple-100 text-purple-700','bg-blue-100 text-blue-700','bg-teal-100 text-teal-700'][Math.max(0,idx)%3];
        },

        // ── Cross-school search ───────────────────────────────────────────────
        async doSearch() {
            const q = this.searchQuery.trim();
            if (q.length<2) { this.searchResults=[]; return; }
            this.searchLoading = true;
            try {
                const res = await fetch(`{{ route('superadmin.owner.search') }}?q=${encodeURIComponent(q)}`);
                this.searchResults = await res.json();
            } finally { this.searchLoading=false; }
        },

        // ── Students list ─────────────────────────────────────────────────────
        async loadStudents(page) {
            if (!this.activeSchoolId) return;
            page = page||1; this.sList.loading=true; this.sList.page=page;
            try {
                const res = await fetch(`/superadmin/owner/students/${this.activeSchoolId}?q=${encodeURIComponent(this.sList.q)}&page=${page}`);
                const j   = await res.json();
                this.sList.data=j.data; this.sList.total=j.total; this.sList.lastPage=j.last_page;
            } finally { this.sList.loading=false; }
        },

        async feeSearch() {
            const q = this.feSearch.q.trim();
            if (q.length<2) { this.feSearch.results=[]; return; }
            const res = await fetch(`/superadmin/owner/students/${this.activeSchoolId}?q=${encodeURIComponent(q)}&page=1`);
            const j   = await res.json();
            this.feSearch.results = (j.data||[]).slice(0,10);
        },

        // ── Per-school analytics ──────────────────────────────────────────────
        async loadAnalytics(schoolId) {
            this.anl.loading = true;
            this.anl.data    = null;
            try {
                const res  = await fetch(`/superadmin/owner/analytics/${schoolId}`);
                const data = await res.json();
                this.anl.data = data;
                this.anlCache[schoolId] = data;   // cache for badge display
                await this.$nextTick();
                this.renderSchoolTrendChart();
            } finally { this.anl.loading=false; }
        },

        // ── Student detail ────────────────────────────────────────────────────
        async openStudent(schoolId, studentId) {
            this.searchResults=[]; this.searchQuery='';
            this.student = { open:true, loading:true, data:null, schoolId, studentId, showReceipt:false };
            this.receipt = { date:new Date().toISOString().slice(0,10), bookId:'', items:[{particularId:'',amount:''}], notes:'', saving:false, msg:'', ok:false };
            const res = await fetch(`/superadmin/owner/student/${schoolId}/${studentId}`);
            this.student.data    = await res.json();
            this.student.loading = false;
        },

        // ── Submit receipt ────────────────────────────────────────────────────
        async submitReceipt() {
            this.receipt.saving=true; this.receipt.msg='';
            const items = this.receipt.items.filter(i => i.particularId && i.amount>0);
            if (!items.length||!this.receipt.bookId||!this.receipt.date) {
                this.receipt.msg='Fill in date, book and at least one particular with amount.';
                this.receipt.ok=false; this.receipt.saving=false; return;
            }
            try {
                const res = await fetch(`/superadmin/owner/receipt/${this.student.schoolId}`, {
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||'{{ csrf_token() }}'},
                    body: JSON.stringify({
                        date:this.receipt.date, student_id:this.student.studentId,
                        book_id:parseInt(this.receipt.bookId), notes:this.receipt.notes,
                        items: items.map(i=>({particular_id:parseInt(i.particularId),amount:parseFloat(i.amount)})),
                        advance_amount:0,
                    }),
                });
                const json = await res.json();
                if (res.ok) {
                    const msg = `Saved! Receipt #${json.voucher_id} — ${this.tsh(json.total)}`;
                    this.receipt.items=[{particularId:'',amount:''}];
                    const sid=this.student.studentId, schid=this.student.schoolId;
                    await this.openStudent(schid,sid);
                    this.student.showReceipt=true;
                    this.receipt.msg=msg; this.receipt.ok=true;
                } else {
                    this.receipt.msg=json.error||'Failed to save.'; this.receipt.ok=false;
                }
            } catch { this.receipt.msg='Network error.'; this.receipt.ok=false; }
            finally  { this.receipt.saving=false; }
        },
    };
}
</script>
@endpush
