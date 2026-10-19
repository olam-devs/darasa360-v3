@php
    $quarterFees=$available->map(fn($s)=>$s['quarter_report']['selected']);
    $required=$quarterFees->sum('required'); $paid=$quarterFees->sum('paid');
@endphp
<h3 style="margin-bottom:8px;">Q{{ $quarter }} {{ $year }} · fees at a glance</h3>
<p class="management-muted" style="margin-bottom:18px;">Required and paid amounts follow the student's academic year and assigned quarter. Paid includes payments applied to those assignments at any date, including advance credit. These are current fee balances; cash received during the quarter is shown in Overview.</p>
<div class="management-grid" id="quarter-summary">
    <div class="management-card"><p class="management-muted">Required (TSh)</p><div class="value">{{ number_format($required,0) }}</div></div>
    <div class="management-card"><p class="management-muted">Paid (TSh)</p><div class="value">{{ number_format($paid,0) }}</div></div>
    <div class="management-card"><p class="management-muted">Unpaid balance (TSh)</p><div class="value">{{ number_format($quarterFees->sum('outstanding'),0) }}</div></div>
    <div class="management-card"><p class="management-muted">Collection rate</p><div class="value">{{ $required>0?number_format(100*$paid/$required,1).'%':'No fees assigned' }}</div><p class="management-muted">{{ $quarterFees->where('assignments','>',0)->count() }} of {{ $available->count() }} available schools have assignments</p></div>
</div>
<section class="management-panel">
    <h3>{{ $year }} · quarter comparison</h3>
    <p class="management-muted" style="margin:8px 0 16px;">All available schools combined. “No assignments” means there are no recorded charges for that quarter.</p>
    <div class="management-scroll"><table class="management-table" id="quarter-comparison-table">
        <thead><tr><th>Quarter</th><th class="number">Required (TSh)</th><th class="number">Paid (TSh)</th><th class="number">Unpaid (TSh)</th><th class="number">Collection rate</th><th>Schools with assignments</th></tr></thead>
        <tbody>@foreach($quarterLabels as $number=>$label)
        @php
            $rows=$available->map(fn($s)=>$s['quarter_report']['quarters'][$number]);
            $charged=$rows->sum('required'); $settled=$rows->sum('paid'); $hasData=$rows->sum('assignments')>0;
        @endphp
        <tr data-quarter="{{ $number }}" @if($number===$quarter) class="management-selected-quarter" @endif>
            <td><a href="{{ route('accountant.management',['year'=>$year,'quarter'=>$number]) }}#quarters" class="management-quarter-link">{{ $label }}</a></td>
            <td class="number">{{ $hasData?number_format($charged,0):'—' }}</td><td class="number">{{ $hasData?number_format($settled,0):'—' }}</td><td class="number">{{ $hasData?number_format($rows->sum('outstanding'),0):'—' }}</td>
            <td class="number">{{ $charged>0?number_format(100*$settled/$charged,1).'%':($hasData?'No amount due':'No assignments') }}</td><td>{{ $rows->where('assignments','>',0)->count() }} / {{ $available->count() }}</td>
        </tr>
        @endforeach</tbody>
    </table></div>
</section>
<section class="management-panel">
    <h3>Q{{ $quarter }} {{ $year }} · school comparison</h3>
    <div class="management-scroll"><table class="management-table" id="quarter-school-table">
        <thead><tr><th>School</th><th class="number">Students assigned</th><th class="number">Required (TSh)</th><th class="number">Paid (TSh)</th><th class="number">Unpaid (TSh)</th><th class="number">Collection rate</th></tr></thead>
        <tbody>@foreach($available as $school)
        @php($fees=$school['quarter_report']['selected'])
        <tr data-school="{{ $school['id'] }}"><td>{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::afterLast($school['name'],' - ')) }}</td>
        @if($fees['assignments']===0)<td colspan="5">{{ $school['quarter_report']['year_exists']?'No fee assignments recorded for this quarter.':'This academic year has not been set up in this school.' }}</td>
        @else<td class="number">{{ $fees['students'] }}</td><td class="number">{{ number_format($fees['required'],0) }}</td><td class="number">{{ number_format($fees['paid'],0) }}</td><td class="number">{{ number_format($fees['outstanding'],0) }}</td><td class="number">{{ $fees['rate']!==null?number_format($fees['rate'],1).'%':'No amount due' }}</td>@endif</tr>
        @endforeach</tbody>
    </table></div>
</section>
<section class="management-panel">
    <h3>Q{{ $quarter }} {{ $year }} · particulars</h3>
    <p class="management-muted" style="margin:8px 0 16px;">Fees, transport, admission and other charges, grouped by particular in each school.</p>
    <div class="management-scroll"><table class="management-table" id="quarter-particulars-table">
        <thead><tr><th>School</th><th>Particular</th><th class="number">Required (TSh)</th><th class="number">Paid (TSh)</th><th class="number">Unpaid (TSh)</th></tr></thead>
        <tbody>@foreach($available as $school)
            @forelse($school['quarter_report']['particulars'] as $particular)
            <tr><td>{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::afterLast($school['name'],' - ')) }}</td><td>{{ $particular->name ?? 'Particular #'.$particular->particular_id }}</td><td class="number">{{ number_format($particular->required_amount,0) }}</td><td class="number">{{ number_format($particular->paid,0) }}</td><td class="number">{{ number_format($particular->outstanding,0) }}</td></tr>
            @empty<tr><td>{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::afterLast($school['name'],' - ')) }}</td><td colspan="4">No fee assignments recorded for this quarter.</td></tr>@endforelse
        @endforeach</tbody>
    </table></div>
    @foreach($available as $school)
        @if($school['quarter_report']['unassigned']>0)<p class="management-notice">{{ $school['name'] }}: {{ $school['quarter_report']['unassigned'] }} assignment(s) in {{ $year }} have no valid quarter and are excluded from quarter totals.</p>@endif
    @endforeach
</section>
