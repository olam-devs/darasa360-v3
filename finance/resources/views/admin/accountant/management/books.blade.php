@php
    $shown = collect($snapshots)->where('error', false)->values();
    $bankGroups = collect($bookGroups)->where('cash', false);
    $cashGroups = collect($bookGroups)->where('cash', true);
    $short = fn($name) => \App\Http\Controllers\SchoolManagementController::shortName($name);
    $money = fn($v) => number_format($v, 0);
@endphp
<h3 style="margin-bottom:8px;">Books {{ $selectedSchool ? '· '.$short($selectedSchool->name) : '· all schools' }}</h3>
<p class="management-muted" style="margin-bottom:18px;">
    @if(!$selectedSchool)The schools share the same bank accounts, so each account is shown once with each school's share. @endif
    Balances are what the system has recorded since each book was opened (all receipts in, all payments out); they match the bank only after the books are reconciled.
    Money in / out covers {{ $periodLabel }} ({{ $from }} – {{ $to }}).
</p>
<div class="management-grid">
    <div class="management-card"><p class="management-muted">Recorded balance · all books</p><div class="value">TSh {{ $money(collect($bookGroups)->sum('balance')) }}</div><p class="management-muted">{{ $bankGroups->count() }} bank/mobile account(s) · {{ $cashGroups->count() }} cash book(s)</p></div>
    <div class="management-card"><p class="management-muted">Money in · {{ $periodLabel }}</p><div class="value">TSh {{ $money(collect($bookGroups)->sum('in')) }}</div><p class="management-muted">Receipts and other deposits</p></div>
    <div class="management-card"><p class="management-muted">Money out · {{ $periodLabel }}</p><div class="value">TSh {{ $money(collect($bookGroups)->sum('out')) }}</div><p class="management-muted">Expense payments, fees and withdrawals</p></div>
</div>

<section class="management-panel">
    <h3>Balance by account</h3>
    <div style="position:relative;height:{{ max(160, 46 * count($bookGroups)) }}px;margin-top:12px;"><canvas id="chart-books" role="img" aria-label="Recorded balance by account"></canvas></div>
    <div class="management-scroll" style="margin-top:16px;"><table class="management-table" id="books-table">
        <thead><tr><th>Account</th>
            @if(!$selectedSchool)@foreach($shown as $s)<th class="number">{{ $short($s['name']) }}</th>@endforeach @endif
            <th class="number">Balance (TSh)</th><th class="number">In · period</th><th class="number">Out · period</th></tr></thead>
        <tbody>
        @forelse($bookGroups as $g)
            <tr>
                <td><strong>{{ $g['name'] }}</strong><div class="management-muted">{{ $g['cash'] ? 'Cash book' : 'Account '.$g['account'] }}</div></td>
                @if(!$selectedSchool)@foreach($shown as $s)<td class="number">{{ isset($g['schools'][$s['id']]) ? $money($g['schools'][$s['id']]['balance']) : '—' }}</td>@endforeach @endif
                <td class="number"><strong>{{ $money($g['balance']) }}</strong></td>
                <td class="number">{{ $money($g['in']) }}</td>
                <td class="number">{{ $money($g['out']) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No active books.</td></tr>
        @endforelse
        </tbody>
        @if(count($bookGroups) > 1)
        <tfoot><tr><td><strong>Total</strong></td>
            @if(!$selectedSchool)@foreach($shown as $s)<td class="number"><strong>{{ $money(collect($bookGroups)->sum(fn($g) => $g['schools'][$s['id']]['balance'] ?? 0)) }}</strong></td>@endforeach @endif
            <td class="number"><strong>{{ $money(collect($bookGroups)->sum('balance')) }}</strong></td>
            <td class="number"><strong>{{ $money(collect($bookGroups)->sum('in')) }}</strong></td>
            <td class="number"><strong>{{ $money(collect($bookGroups)->sum('out')) }}</strong></td></tr></tfoot>
        @endif
    </table></div>
</section>
