@extends('layouts.management')
@section('title','School management — Darasa Finance')
@section('page_title', $selectedSchool ? \App\Http\Controllers\SchoolManagementController::shortName($selectedSchool->name) : 'All schools')
@section('content')
@include('admin.accountant.management.styles')
@php
    $available=collect($snapshots)->where('error',false);
    $collections=$available->sum('collections'); $expenses=$available->sum('expenses');
    $missed=collect($kpiRows)->where('status','Missed');
    $maxCollections=max(1,$available->max('collections')??0);
    $accounts=collect($kpiRows)->unique('accountant_id')->values();
@endphp
<div class="management-page">
    @if($selectedSchool)
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">School management · one school</p>
    <h2>{{ $selectedSchool->name }}</h2>
    <p class="management-muted">Figures for this school only. <a href="{{ route('accountant.management') }}#overview" style="color:#1d4ed8;font-weight:600;">Compare all schools</a></p>
    @else
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">School management · {{ count($snapshots) }} schools</p>
    <h2>School overview</h2>
    <p class="management-muted">Welcome, {{ auth()->user()->name }}. Compare results, see what needs attention and open any school for its own figures.</p>
    @endif
    @if(session('error'))<div class="management-notice">{{ session('error') }}</div>@endif
    @include('admin.accountant.management.period')
    @if($from <= now('Africa/Dar_es_Salaam')->toDateString() && $to >= now('Africa/Dar_es_Salaam')->toDateString())
    <p class="management-muted">This period is still in progress. Previous-period figures cover the full {{ $periodMode==='quarter'?'previous quarter':'selected duration' }}.</p>
    @endif
    <nav class="management-tabs" role="tablist" aria-label="School management sections">
        <button type="button" role="tab" id="tab-overview" aria-controls="panel-overview" aria-selected="true" data-management-tab="overview">Overview</button>
        <button type="button" role="tab" id="tab-quarters" aria-controls="panel-quarters" aria-selected="false" data-management-tab="quarters">Quarter analytics</button>
        <button type="button" role="tab" id="tab-approvals" aria-controls="panel-approvals" aria-selected="false" data-management-tab="approvals">Expense approvals ({{ $available->sum('pending') }})</button>
        <button type="button" role="tab" id="tab-targets" aria-controls="panel-targets" aria-selected="false" data-management-tab="targets">Accountant targets</button>
    </nav>
    @if($available->count()!==count($snapshots))
    <div class="management-notice">Some school data is unavailable. Combined amounts below include only the {{ $available->count() }} available school(s). Unavailable data has not been counted as zero.</div>
    @endif
    <div id="panel-overview" role="tabpanel" aria-labelledby="tab-overview" data-management-panel="overview">
    <div class="management-grid">
        <div class="management-card"><p class="management-muted">Collections · selected payment dates</p><div class="value">TSh {{ number_format($collections,0) }}</div><p class="management-muted">Fresh receipts into school books</p></div>
        <div class="management-card"><p class="management-muted">Approved expenses · selected dates</p><div class="value">TSh {{ number_format($expenses,0) }}</div><p class="management-muted">Approved and partially approved submissions</p></div>
        <div class="management-card"><p class="management-muted">Collections less approved expenses</p><div class="value">TSh {{ number_format($collections-$expenses,0) }}</div><p class="management-muted">A comparison of these two measures, before other book movements</p></div>
        <div class="management-card"><p class="management-muted">Accountant targets missed</p><div class="value">{{ $missed->count() }}</div><p class="management-muted">{{ collect($kpiRows)->where('status','In progress')->count() }} in progress · {{ collect($kpiRows)->where('status','Not set')->count() }} not set</p></div>
    </div>

    <div class="management-grid" id="management-schools">
    @foreach($snapshots as $school)
        <section class="management-card" data-school="{{ $school['id'] }}">
            <h3 title="{{ $school['name'] }}">@if($selectedSchool){{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::afterLast($school['name'],' - ')) }}@else<a href="{{ route('accountant.management',['school'=>$school['id']]) }}#overview" style="color:#1d4ed8;">{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::afterLast($school['name'],' - ')) }} <span style="font-size:13px;">→</span></a>@endif</h3>
            @if($school['error'])
                <p class="management-error" style="margin-top:14px;">School data is temporarily unavailable. Refresh to retry.</p>
            @else
                <p class="management-muted" style="margin-top:8px;">Collections in the selected period</p>
                <div class="value">TSh {{ number_format($school['collections'],0) }}</div>
                <div class="management-track"><div style="width:{{ max(0,100*$school['collections']/$maxCollections) }}%;"></div></div>
                <p class="management-muted">{{ $periodMode==='quarter'?'Previous quarter':'Previous '.((int)\Carbon\CarbonImmutable::parse($from)->diffInDays(\Carbon\CarbonImmutable::parse($to))+1).' days' }}: TSh {{ number_format($school['previous'],0) }}
                    @if($school['previous']>0) · {{ $school['collections'] >= $school['previous'] ? '+' : '' }}{{ number_format(100*($school['collections']-$school['previous'])/$school['previous'],1) }}%@endif
                </p>
                <div class="management-school-line"><span>Approved expenses in period</span><strong>{{ number_format($school['expenses'],0) }}</strong></div>
                @if($school['quarter_report']['selected']['assignments']>0)
                <div class="management-school-line"><span>Q{{ $quarter }} required / paid</span><strong>{{ number_format($school['quarter_report']['selected']['required'],0) }} / {{ number_format($school['quarter_report']['selected']['paid'],0) }}</strong></div>
                <div class="management-school-line"><span>Q{{ $quarter }} unpaid balance</span><strong>{{ number_format($school['quarter_report']['selected']['outstanding'],0) }}</strong></div>
                @else
                <p class="management-muted" style="margin-top:10px;">Q{{ $quarter }} {{ $year }}: no fee assignments recorded.</p>
                @endif
                <div class="management-school-line"><span>Current outstanding fees</span><strong>{{ number_format($school['outstanding'],0) }}</strong></div>
                <div class="management-school-line"><span>Current overdue fees</span><strong>{{ number_format($school['overdue'],0) }}</strong></div>
                <div class="management-school-line"><span>Current collection rate</span><strong>{{ $school['billed']>0?number_format(100*$school['paid']/$school['billed'],1).'%' : 'No fees assigned' }}</strong></div>
                <div class="management-school-line"><span>Current book balances</span><strong>{{ number_format($school['book_balance'],0) }}</strong></div>
                <div class="management-school-line"><span>Advance credit held</span><strong>{{ number_format($school['advance'],0) }}</strong></div>
                <div class="management-school-line"><span>Active students / expenses to review</span><strong>{{ $school['students'] }} / {{ $school['pending'] }}</strong></div>
            @endif
            <div class="management-links">
                <a href="{{ route('accountant.management',['school'=>$school['id']]) }}#approvals" class="management-link-primary">Approvals ({{ $school['pending'] ?? 0 }})</a>
                <a href="{{ route('accountant.management.invoices',['school'=>$school['id']]) }}" class="management-link-primary">Invoices</a>
            </div>
            <details class="management-work-inside">
                <summary>Work inside this school's accounts</summary>
                <div class="management-links">
                @foreach(['dashboard'=>'Dashboard','fee-entry'=>'Fee entry','overdue'=>'Overdue fees','ledgers'=>'Ledgers'] as $destination=>$label)
                    <form method="post" action="{{ route('accountant.management.enter',$school['id']) }}">@csrf<input type="hidden" name="page" value="{{ $destination }}"><button type="submit">{{ $label }}</button></form>
                @endforeach
                </div>
            </details>
        </section>
    @endforeach
    </div>
    <p class="management-muted" style="margin-bottom:20px;">Collections and expenses use the selected transaction dates. Outstanding fees, overdue fees, advances, book balances and pending reviews are current positions. Book balances show money recorded in the system since each book was opened; they match the bank only after the books are reconciled. "Work inside this school's accounts" switches your active school in this browser; return through School management to choose another.</p>

    <div class="management-pair">
        <section class="management-panel">
            <h3>Needs attention now</h3>
            @foreach($available as $school)
                @if($school['pending']>0)<div class="management-attention"><strong>{{ $school['pending'] }} pending expense(s)</strong><br>{{ $school['name'] }}<div class="management-muted">Oldest submitted: {{ $school['oldest_pending'] ? \Carbon\Carbon::parse($school['oldest_pending'])->format('d M Y') : 'Unknown' }}</div></div>@endif
                @if($school['overdue']>0)<div class="management-attention"><strong>TSh {{ number_format($school['overdue'],0) }} overdue</strong><br>{{ $school['name'] }}<div class="management-muted">Open Overdue fees to follow up with parents.</div></div>@endif
            @endforeach
            @foreach($missed as $row)<div class="management-attention management-error"><strong>{{ $row['accountant'] }} · {{ $row['label'] }}</strong><br>Target missed for {{ $from }} to {{ $to }}. Shortfall: {{ number_format($row['shortfall'],2) }}.</div>@endforeach
            @if($available->sum('pending')===0 && $available->sum('overdue')<=0 && $missed->isEmpty())<p class="management-muted" style="margin-top:12px;">No pending reviews, overdue balances or missed targets in the available data.</p>@endif
        </section>
        <section class="management-panel">
            <h3>Largest current unpaid balances</h3>
            <p class="management-muted">Top five students in each school. Open Overdue fees for deadlines and follow-up.</p>
            @foreach($available as $school)
                <p style="font-size:12px;font-weight:700;margin-top:14px;">{{ $school['name'] }}</p>
                @forelse($school['debtors'] as $student)<div class="management-school-line"><span>{{ $student->name }}</span><strong>{{ number_format($student->balance,0) }}</strong></div>@empty<p class="management-muted">No unpaid balances.</p>@endforelse
            @endforeach
        </section>
    </div>

    </div>
    <div id="panel-quarters" role="tabpanel" aria-labelledby="tab-quarters" data-management-panel="quarters" hidden>
        @include('admin.accountant.management.quarters')
    </div>
    <div id="panel-approvals" role="tabpanel" aria-labelledby="tab-approvals" data-management-panel="approvals" hidden>
        <section class="management-panel">
            <h3>Expenses waiting for your approval</h3>
            <p class="management-muted" style="margin:8px 0 18px;">{{ $selectedSchool ? 'Pending expenses for this school.' : 'All schools in one list, grouped by school.' }} Review opens the expense here, where you check the items and the book before approving or denying. This queue shows current pending submissions, regardless of the analytics period.</p>
            <div class="management-scroll"><table class="management-table" id="management-approval-table">
                <thead><tr><th>School / submitted</th><th>Expense</th><th class="number">Requested (TSh)</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($available as $school)
                    @foreach($school['pending_items'] as $expense)
                    <tr>
                        <td>{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::afterLast($school['name'],' - ')) }}<div class="management-muted">{{ \Carbon\Carbon::parse($expense->created_at)->format('d M Y') }}</div></td>
                        <td><strong>{{ $expense->title ?: $expense->submission_number }}</strong><div class="management-muted">{{ $expense->category_name }} · {{ $expense->submission_number }}</div></td>
                        <td class="number">{{ number_format($expense->total_amount,2) }}</td>
                        <td><a class="management-primary" style="display:inline-block;" href="{{ route('accountant.management.expense',[$school['id'],$expense->id]) }}">Review</a></td>
                    </tr>
                    @endforeach
                    @if($school['pending']>100)<tr><td colspan="4">{{ $school['name'] }}: showing the oldest 100. Open the school's Expenses page for the full queue.</td></tr>@endif
                @endforeach
                @if($available->sum('pending')===0)<tr><td colspan="4">Nothing is waiting for your approval.</td></tr>@endif
                </tbody>
            </table></div>
        </section>
    </div>
    <div id="panel-targets" role="tabpanel" aria-labelledby="tab-targets" data-management-panel="targets" hidden>
    <section class="management-panel">
        <h3>Accountant scorecard</h3>
        <p class="management-muted" style="margin:8px 0 18px;">{{ $periodLabel }} · {{ $from }} to {{ $to }} · Private management scorecard for Ritha.</p>
        @include('admin.accountant.management.kpi-table')
    </section>
    <section class="management-panel">
        <h3>Set or update a target for this period</h3>
        <p class="management-muted">Choose a school, accountant and KPI. No target is assumed automatically. Changes are recorded in the activity log.</p>
        <form method="post" action="{{ route('accountant.management.targets') }}" class="management-target" id="management-target-form">
            @csrf
            <input type="hidden" name="from_date" value="{{ $from }}"><input type="hidden" name="to_date" value="{{ $to }}">
            <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="quarter" value="{{ $quarter }}"><input type="hidden" name="period_mode" value="{{ $periodMode }}">
            <label>School<select name="school_id" id="target-school" required>@foreach($schools as $school)<option value="{{ $school->id }}">{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::afterLast($school->name,' - ')) }}</option>@endforeach</select></label>
            <label>Accountant<select name="accountant_id" id="target-accountant" required>@foreach($accounts as $account)<option value="{{ $account['accountant_id'] }}" data-school="{{ $account['school_id'] }}">{{ $account['accountant'] }}</option>@endforeach</select></label>
            <label>KPI<select name="metric" id="target-metric" required>@foreach($metrics as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label>Target<input type="number" name="target_value" id="target-value" min="0.01" step="0.01" max="999999999999.99" required></label>
            <label>Recording limit (days)<input type="number" name="recording_days" min="0" max="30" step="1" value="1" required><span class="management-muted">Used for the on-time percentage only.</span></label>
            <div style="display:flex;align-items:start;padding-top:23px;"><button class="management-primary" type="submit">Save target</button></div>
        </form>
    </section>
    </div>
</div>
@endsection
@push('scripts')
<script>
(() => {
    function showManagementTab(name) {
        if(!['overview','quarters','approvals','targets'].includes(name)) name='overview';
        document.querySelectorAll('[data-management-panel]').forEach(p=>p.hidden=p.dataset.managementPanel!==name);
        document.querySelectorAll('[data-management-tab]').forEach(b=>b.setAttribute('aria-selected',String(b.dataset.managementTab===name)));
        document.querySelectorAll('.mgmt-nav a[data-section]').forEach(a=>{ if(a.dataset.section===name) a.setAttribute('aria-current','page'); else a.removeAttribute('aria-current'); });
        history.replaceState(null,'','#'+name);
    }
    document.querySelectorAll('[data-management-tab]').forEach(b=>b.addEventListener('click',()=>showManagementTab(b.dataset.managementTab)));
    window.addEventListener('hashchange',()=>showManagementTab(location.hash.slice(1)));
    showManagementTab(location.hash.slice(1)||'overview');
    const periodMode=document.getElementById('management-period-mode');
    function toggleCustomDates() {
        document.querySelectorAll('[data-custom-dates]').forEach(label=>{
            label.hidden=periodMode.value!=='custom';
            label.querySelector('input').disabled=label.hidden;
        });
    }
    periodMode.addEventListener('change',toggleCustomDates);toggleCustomDates();
    document.getElementById('management-period-form').addEventListener('submit',function(){this.action='#'+(location.hash.slice(1)||'overview');});
    const school=document.getElementById('target-school'), accountant=document.getElementById('target-accountant');
    const metric=document.getElementById('target-metric'), value=document.getElementById('target-value');
    function filterAccounts() {
        for (const option of accountant.options) option.hidden=option.disabled=option.dataset.school!==school.value;
        const available=[...accountant.options].find(o=>!o.disabled);
        accountant.value=available?.value||'';
    }
    function metricRules() {
        value.step=metric.value==='receipt_count'?'1':'0.01';
        value.min=metric.value==='receipt_count'?'1':'0.01';
        value.max=metric.value==='recording_timeliness'?'100':'999999999999.99';
    }
    school.addEventListener('change',filterAccounts);metric.addEventListener('change',metricRules);
    filterAccounts();metricRules();
})();
</script>
@endpush
