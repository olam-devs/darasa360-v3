<div class="management-scroll">
<table class="management-table" id="accountant-kpi-table" style="min-width:700px;">
    <thead><tr><th>Accountant / school</th><th>Collections recorded (TSh)</th><th>Receipts recorded</th><th>Recorded on time (%)</th></tr></thead>
    <tbody>
    @forelse(collect($kpiRows)->groupBy(fn($r)=>$r['school_id'].'-'.$r['accountant_id']) as $group)
        @php $person=$group->first(); @endphp
        <tr data-accountant="{{ $person['accountant_id'] }}">
            <td><strong>{{ $person['accountant'] }}</strong><div class="management-muted">{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::afterLast($person['school'],' - ')) }}</div></td>
            @foreach($group as $row)
                @php $colors = ['Met'=>['#dcfce7','#166534'],'Missed'=>['#fee2e2','#991b1b'],'In progress'=>['#dbeafe','#1e40af'],'Not set'=>['#f1f5f9','#475569'],'No data'=>['#fef3c7','#92400e']]; $color=$colors[$row['status']]; $decimals=$row['metric']==='receipt_count'?0:($row['metric']==='recording_timeliness'?1:2); @endphp
                <td data-metric="{{ $row['metric'] }}">
                    <strong>{{ $row['actual'] === null ? 'No receipts' : number_format($row['actual'],$decimals) }}</strong>
                    <div class="management-muted">Target: {{ $row['target'] === null ? 'Not set' : number_format($row['target'],$decimals) }}</div>
                    @if($row['shortfall']!==null)<div class="management-muted">Shortfall: {{ number_format($row['shortfall'],$decimals) }}{{ $row['metric']==='recording_timeliness' ? ' pts' : '' }}</div>@endif
                    @if($row['metric']==='recording_timeliness')<div class="management-muted">Within {{ $row['recording_days'] }} day(s)</div>@endif
                    <span class="management-badge" style="margin-top:5px;background:{{ $color[0] }};color:{{ $color[1] }};">{{ $row['status'] }}</span>
                </td>
            @endforeach
        </tr>
    @empty
        <tr><td colspan="4">No accountant data is available for this period.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<p class="management-muted" style="margin-top:14px;">KPIs cover cash receipts entered during {{ $from }} to {{ $to }}, attributed to the accountant who recorded them. Voided receipts and allocations from existing advance credit are excluded. A zero collection or receipt count is shown when nothing was recorded; a timing percentage needs at least one receipt. Targets apply to these exact dates. “Missed” appears only after the period ends.</p>
