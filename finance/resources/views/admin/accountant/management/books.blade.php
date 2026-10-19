@php
    $shown = collect($snapshots)->where('error', false)->values();
    $bankGroups = collect($bookGroups)->where('cash', false);
    $cashGroups = collect($bookGroups)->where('cash', true);
    $short = fn($name) => \App\Http\Controllers\SchoolManagementController::shortName($name);
    $money = fn($v) => number_format($v, 0);
    $todayDate = now('Africa/Dar_es_Salaam')->toDateString();
    $endLabel = $to > $todayDate ? 'today' : 'on '.\Carbon\Carbon::parse($to)->format('d M Y');
    $startLabel = 'on '.\Carbon\Carbon::parse($from)->format('d M Y');
@endphp
<h3 style="margin-bottom:8px;">Books · {{ $periodLabel }} {{ $selectedSchool ? '· '.$short($selectedSchool->name) : '· all schools' }}</h3>
<p class="management-muted" style="margin-bottom:18px;">
    @if(!$selectedSchool)The schools share the same bank accounts, so each account is shown once with each school's share. @endif
    Everything follows the selected period ({{ $from }} – {{ $to }}): balance at its start, money in and out during it, and balance at its end (or today while it is running).
    Balances are what the system has recorded since each book was opened; they match the bank only after the books are reconciled.
</p>
<div class="management-grid">
    <div class="management-card"><p class="management-muted">Balance {{ $startLabel }}</p><div class="value">TSh {{ $money(collect($bookGroups)->sum('start')) }}</div><p class="management-muted">Start of the period</p></div>
    <div class="management-card"><p class="management-muted">Money in · {{ $periodLabel }}</p><div class="value">TSh {{ $money(collect($bookGroups)->sum('in')) }}</div><p class="management-muted">Receipts and other deposits</p></div>
    <div class="management-card"><p class="management-muted">Money out · {{ $periodLabel }}</p><div class="value">TSh {{ $money(collect($bookGroups)->sum('out')) }}</div><p class="management-muted">Expense payments, salaries, fees and withdrawals</p></div>
    <div class="management-card"><p class="management-muted">Balance {{ $endLabel }}</p><div class="value">TSh {{ $money(collect($bookGroups)->sum('balance')) }}</div><p class="management-muted">{{ $bankGroups->count() }} bank/mobile account(s) · {{ $cashGroups->count() }} cash book(s)</p></div>
</div>

<section class="management-panel">
    <h3>Balance by account · {{ $endLabel }}</h3>
    <div style="position:relative;height:{{ max(160, 46 * count($bookGroups)) }}px;margin-top:12px;"><canvas id="chart-books" role="img" aria-label="Balance by account"></canvas></div>
    <div class="management-scroll" style="margin-top:16px;"><table class="management-table" id="books-table">
        <thead><tr><th>Account</th>
            @if(!$selectedSchool)@foreach($shown as $s)<th class="number">{{ $short($s['name']) }}</th>@endforeach @endif
            <th class="number">Balance {{ $startLabel }}</th><th class="number">In</th><th class="number">Out</th><th class="number">Balance {{ $endLabel }}</th></tr></thead>
        <tbody>
        @forelse($bookGroups as $g)
            <tr>
                <td><strong>{{ $g['name'] }}</strong><div class="management-muted">{{ $g['cash'] ? 'Cash book' : 'Account '.$g['account'] }}</div></td>
                @if(!$selectedSchool)@foreach($shown as $s)<td class="number">{{ isset($g['schools'][$s['id']]) ? $money($g['schools'][$s['id']]['balance']) : '—' }}</td>@endforeach @endif
                <td class="number">{{ $money($g['start']) }}</td>
                <td class="number">{{ $money($g['in']) }}</td>
                <td class="number">{{ $money($g['out']) }}</td>
                <td class="number"><strong>{{ $money($g['balance']) }}</strong></td>
            </tr>
        @empty
            <tr><td colspan="8">No active books.</td></tr>
        @endforelse
        </tbody>
        @if(count($bookGroups) > 1)
        <tfoot><tr><td><strong>Total</strong></td>
            @if(!$selectedSchool)@foreach($shown as $s)<td class="number"><strong>{{ $money(collect($bookGroups)->sum(fn($g) => $g['schools'][$s['id']]['balance'] ?? 0)) }}</strong></td>@endforeach @endif
            <td class="number"><strong>{{ $money(collect($bookGroups)->sum('start')) }}</strong></td>
            <td class="number"><strong>{{ $money(collect($bookGroups)->sum('in')) }}</strong></td>
            <td class="number"><strong>{{ $money(collect($bookGroups)->sum('out')) }}</strong></td>
            <td class="number"><strong>{{ $money(collect($bookGroups)->sum('balance')) }}</strong></td></tr></tfoot>
        @endif
    </table></div>
    @if(!$selectedSchool)<p class="management-muted" style="margin-top:10px;">School columns show each school's balance {{ $endLabel }}.</p>@endif
</section>
