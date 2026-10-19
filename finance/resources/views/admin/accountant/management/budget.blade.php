@php
    $money = fn($v) => number_format($v, 0);
    $totalBudget = collect($budgetRows)->sum('budget'); $totalSpent = collect($budgetRows)->sum('spent');
    $over = collect($budgetRows)->filter(fn($r) => $r['budget'] > 0 && $r['spent'] > $r['budget']);
    $unbudgeted = collect($budgetRows)->filter(fn($r) => $r['budget'] <= 0 && $r['spent'] > 0);
@endphp
<h3 style="margin-bottom:8px;">Budget vs actual · {{ $periodLabel }} {{ $selectedSchool ? '· '.\App\Http\Controllers\SchoolManagementController::shortName($selectedSchool->name) : '· all schools' }}</h3>
<p class="management-muted" style="margin-bottom:18px;">Each expense category's budget against approved spending. Budgets have their own date windows, so every budget that overlaps {{ $from }} – {{ $to }} is compared with the approved spending inside that budget's own dates. @unless($selectedSchool)Categories with the same name in different schools are combined.@endunless</p>
<div class="management-grid">
    <div class="management-card"><p class="management-muted">Budget</p><div class="value">TSh {{ $money($totalBudget) }}</div><p class="management-muted">{{ collect($budgetRows)->where('budget', '>', 0)->count() }} budgeted categories</p></div>
    <div class="management-card"><p class="management-muted">Approved spending</p><div class="value">TSh {{ $money($totalSpent) }}</div><p class="management-muted">{{ $totalBudget > 0 ? number_format(100 * $totalSpent / $totalBudget, 1).'% of budget' : 'No budget set' }}</p></div>
    <div class="management-card"><p class="management-muted">Over budget</p><div class="value" @if($over->isNotEmpty()) style="color:#b91c1c;" @endif>{{ $over->count() }}</div><p class="management-muted">{{ $over->isNotEmpty() ? 'TSh '.$money($over->sum(fn($r) => $r['spent'] - $r['budget'])).' above budget' : 'All within budget' }}</p></div>
    <div class="management-card"><p class="management-muted">Spent with no budget</p><div class="value">TSh {{ $money($unbudgeted->sum('spent')) }}</div><p class="management-muted">{{ $unbudgeted->count() }} categor{{ $unbudgeted->count() === 1 ? 'y' : 'ies' }}</p></div>
</div>
<section class="management-panel">
    <h3>By category</h3>
    @if(empty($budgetRows))
        <p class="management-muted" style="margin-top:10px;">No budgets or approved spending in this period.</p>
    @else
    <div style="position:relative;height:{{ max(200, 38 * count($budgetRows) + 60) }}px;margin-top:12px;"><canvas id="chart-budget" role="img" aria-label="Budget and approved spending by category"></canvas></div>
    <div class="management-scroll" style="margin-top:16px;"><table class="management-table" id="budget-table" style="min-width:760px;">
        <thead><tr><th>Category</th><th class="number">Budget (TSh)</th><th class="number">Spent (TSh)</th><th class="number">Remaining (TSh)</th><th style="min-width:180px;">Used</th></tr></thead>
        <tbody>@foreach($budgetRows as $r)
            @php $pct = $r['budget'] > 0 ? 100 * $r['spent'] / $r['budget'] : null; $isOver = $pct !== null && $pct > 100; @endphp
            <tr>
                <td><strong>{{ $r['category'] }}</strong></td>
                <td class="number">{{ $r['budget'] > 0 ? $money($r['budget']) : '—' }}</td>
                <td class="number">{{ $money($r['spent']) }}</td>
                <td class="number" @if($isOver) style="color:#b91c1c;font-weight:700;" @endif>{{ $r['budget'] > 0 ? $money($r['budget'] - $r['spent']) : '—' }}</td>
                <td>
                    @if($pct === null)<span class="management-badge" style="background:#fef3c7;color:#92400e;">⚠ No budget</span>
                    @else
                        <div class="management-track" style="margin:4px 0;"><div style="width:{{ min(100, $pct) }}%;background:{{ $isOver ? '#dc2626' : ($pct >= 80 ? '#d97706' : '#059669') }};"></div></div>
                        <span style="font-size:12px;font-weight:700;color:{{ $isOver ? '#b91c1c' : '#334155' }};">{{ number_format($pct, 0) }}%{{ $isOver ? ' · ✕ Over budget' : ($pct >= 80 ? ' · nearly used' : '') }}</span>
                    @endif
                </td>
            </tr>
        @endforeach</tbody>
        <tfoot><tr><td><strong>Total</strong></td><td class="number"><strong>{{ $money($totalBudget) }}</strong></td><td class="number"><strong>{{ $money($totalSpent) }}</strong></td><td class="number"><strong>{{ $money($totalBudget - $totalSpent) }}</strong></td><td></td></tr></tfoot>
    </table></div>
    @endif
</section>
