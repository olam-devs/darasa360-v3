@php
    $shown = collect($snapshots)->where('error', false)->values();
    $short = fn($name) => \App\Http\Controllers\SchoolManagementController::shortName($name);
    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    // Colour follows the school (its place among all granted schools), never its rank in the current view.
    $schoolColors = ['#2a78d6', '#eb6834', '#1baf7a'];
    $colorOf = fn($id) => $schoolColors[array_search($id, array_column($nav['schools'], 'id')) % 3] ?? '#2a78d6';
    $sumMonthly = fn($key) => collect(range(1, 12))->map(fn($m) => $shown->sum(fn($s) => $s[$key][$m]))->all();
    $chartData = [
        'months' => $months,
        'schools' => $shown->map(fn($s) => ['label' => $short($s['name']), 'color' => $colorOf($s['id']), 'data' => array_values($s['monthly_collections'])])->all(),
        'collections' => $sumMonthly('monthly_collections'),
        'expenses' => $sumMonthly('monthly_expenses'),
        'quarters' => collect($quarterLabels)->map(fn($label, $q) => ['label' => $label,
            'required' => $shown->sum(fn($s) => $s['quarter_report']['quarters'][$q]['required']),
            'paid' => $shown->sum(fn($s) => $s['quarter_report']['quarters'][$q]['paid'])])->values()->all(),
        'books' => collect($bookGroups)->map(fn($g) => ['label' => $g['name'].($g['cash'] ? '' : ' · '.substr((string)$g['account'], -4)), 'value' => $g['balance']])->all(),
    ];
    $money = fn($v) => number_format($v, 0);
@endphp
<h3 style="margin-bottom:8px;">Analytics {{ $year }} {{ $selectedSchool ? '· '.$short($selectedSchool->name) : '· all schools' }}</h3>
<p class="management-muted" style="margin-bottom:18px;">Collections are fresh receipts by payment date; expenses are approved submissions by spending date; quarter fees follow each fee's assigned quarter. Hover a bar for exact amounts.</p>

<div class="management-pair">
    <section class="management-panel">
        <h3>Collections by month{{ $selectedSchool ? '' : ' · by school' }}</h3>
        <div style="position:relative;height:300px;margin-top:12px;"><canvas id="chart-collections" role="img" aria-label="Collections by month"></canvas></div>
    </section>
    <section class="management-panel">
        <h3>Collections vs approved expenses</h3>
        <div style="position:relative;height:300px;margin-top:12px;"><canvas id="chart-cashflow" role="img" aria-label="Collections and approved expenses by month"></canvas></div>
    </section>
</div>
<section class="management-panel">
    <h3>Quarter fees · required vs paid</h3>
    <div style="position:relative;height:260px;margin-top:12px;"><canvas id="chart-quarters" role="img" aria-label="Required and paid fees by quarter"></canvas></div>
</section>

<details class="management-panel">
    <summary style="cursor:pointer;font-weight:700;">Show the figures as a table</summary>
    <div class="management-scroll" style="margin-top:14px;"><table class="management-table" id="analytics-table">
        <thead><tr><th>Month</th>
            @if(!$selectedSchool)@foreach($shown as $s)<th class="number">{{ $short($s['name']) }}</th>@endforeach @endif
            <th class="number">Collections</th><th class="number">Approved expenses</th><th class="number">Difference</th></tr></thead>
        <tbody>@foreach($months as $i => $label)
            <tr><td>{{ $label }} {{ $year }}</td>
                @if(!$selectedSchool)@foreach($shown as $s)<td class="number">{{ $money($s['monthly_collections'][$i+1]) }}</td>@endforeach @endif
                <td class="number">{{ $money($chartData['collections'][$i]) }}</td><td class="number">{{ $money($chartData['expenses'][$i]) }}</td>
                <td class="number">{{ $money($chartData['collections'][$i] - $chartData['expenses'][$i]) }}</td></tr>
        @endforeach</tbody>
        <tfoot><tr><td><strong>{{ $year }}</strong></td>
            @if(!$selectedSchool)@foreach($shown as $s)<td class="number"><strong>{{ $money(array_sum($s['monthly_collections'])) }}</strong></td>@endforeach @endif
            <td class="number"><strong>{{ $money(array_sum($chartData['collections'])) }}</strong></td><td class="number"><strong>{{ $money(array_sum($chartData['expenses'])) }}</strong></td>
            <td class="number"><strong>{{ $money(array_sum($chartData['collections']) - array_sum($chartData['expenses'])) }}</strong></td></tr></tfoot>
    </table></div>
</details>

<script>
window.managementChartData = @json($chartData);
window.managementChartInit = (() => {
    const d = window.managementChartData, done = {};
    const tsh = v => 'TSh ' + Math.round(v).toLocaleString('en-US');
    const short = v => v >= 1e6 ? (v / 1e6).toLocaleString('en-US', {maximumFractionDigits: 1}) + 'M' : v >= 1e3 ? Math.round(v / 1e3) + 'k' : v;
    const ink = '#52514e', grid = '#e7e5e0';
    const base = (stacked, legend) => ({
        responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
        plugins: {legend: {display: legend, position: 'top', align: 'start', labels: {color: ink, boxWidth: 12, boxHeight: 12, useBorderRadius: true, borderRadius: 3}},
                  tooltip: {callbacks: {label: c => `${c.dataset.label}: ${tsh(c.parsed.y ?? c.parsed.x)}`}}},
        scales: {x: {stacked, grid: {display: false}, ticks: {color: ink}},
                 y: {stacked, beginAtZero: true, grid: {color: grid}, border: {display: false}, ticks: {color: ink, callback: short}}},
    });
    const bar = (label, data, color) => ({label, data, backgroundColor: color, borderColor: '#ffffff', borderWidth: 2, borderRadius: 4, borderSkipped: 'bottom'});
    const make = {
        analytics() {
            new Chart(document.getElementById('chart-collections'), {type: 'bar',
                data: {labels: d.months, datasets: d.schools.map(s => bar(s.label, s.data, s.color))},
                options: base(true, d.schools.length > 1)});
            new Chart(document.getElementById('chart-cashflow'), {type: 'bar',
                data: {labels: d.months, datasets: [bar('Collections', d.collections, '#008300'), bar('Approved expenses', d.expenses, '#6250d6')]},
                options: base(false, true)});
            new Chart(document.getElementById('chart-quarters'), {type: 'bar',
                data: {labels: d.quarters.map(q => q.label), datasets: [bar('Required', d.quarters.map(q => q.required), '#a3a29d'), bar('Paid', d.quarters.map(q => q.paid), '#008300')]},
                options: base(false, true)});
        },
        books() {
            const o = base(false, false);
            o.indexAxis = 'y'; o.interaction = {mode: 'nearest', axis: 'y', intersect: false};
            o.scales = {x: {beginAtZero: true, grid: {color: grid}, border: {display: false}, ticks: {color: ink, callback: short}}, y: {grid: {display: false}, ticks: {color: ink}}};
            o.plugins.tooltip.callbacks.label = c => tsh(c.parsed.x);
            new Chart(document.getElementById('chart-books'), {type: 'bar',
                data: {labels: d.books.map(b => b.label), datasets: [{...bar('Balance', d.books.map(b => b.value), '#334155'), borderSkipped: 'left'}]}, options: o});
        },
    };
    // Charts are drawn the first time their tab is shown (a hidden canvas has no size).
    return name => { if (make[name] && !done[name] && window.Chart) { done[name] = true; make[name](); } };
})();
</script>
