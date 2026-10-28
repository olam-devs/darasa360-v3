{{--
    Bank ledger body, shared by the management portal and the school accountants.
    Needs: $lines, $opening, $balance, $choices, $account, $multiSchool, $from, $to, $schoolDots (short name => colour), $receiptsUrl.
--}}
@php
    $money = fn($v) => number_format($v, 0);
    $chosen = $choices[$account] ?? null;
    $unconfirmed = $lines->where('type', 'Receipt')->count();
@endphp
<form method="get" class="management-period" style="margin-top:12px;">
    @foreach(request()->except(['account', 'download']) as $k => $v)
        @if(is_array($v))@foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
        @else<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <label>Account<select name="account" onchange="this.form.submit()">
        @foreach($choices as $c)<option value="{{ $c['value'] }}" @selected($c['value'] === $account)>{{ $c['label'] }}</option>@endforeach
    </select></label>
    <button type="submit" name="download" value="csv" class="management-chip-button"><i class="fa-solid fa-download"></i> Download CSV (Excel)</button>
</form>
@if($multiSchool)<p class="management-muted" style="margin-top:6px;">This account is shared: the ledger includes the students and payments of every school using it ({{ collect($chosen['books'])->map(fn($b) => \App\Services\ReceiptCheckService::shortName($b[0]->name))->implode(', ') }}).</p>@endif

<div class="management-grid" style="margin-top:18px;">
    <div class="management-card"><p class="management-muted">Opening balance · {{ \Carbon\Carbon::parse($from)->format('d M Y') }}</p><div class="value">TSh {{ $money($opening) }}</div><p class="management-muted">As recorded in the system</p></div>
    <div class="management-card"><p class="management-muted">Money in</p><div class="value">TSh {{ $money($lines->sum('in')) }}</div><p class="management-muted">{{ $lines->where('in', '>', 0)->count() }} line(s)</p></div>
    <div class="management-card"><p class="management-muted">Money out</p><div class="value">TSh {{ $money($lines->sum('out')) }}</div><p class="management-muted">{{ $lines->where('out', '>', 0)->count() }} line(s)</p></div>
    <div class="management-card"><p class="management-muted">Closing balance · {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p><div class="value">TSh {{ $money($balance) }}</div><p class="management-muted">@if($unconfirmed){{ $unconfirmed }} receipt line(s) not yet checked @else All receipts checked @endif</p></div>
</div>

<section class="management-panel">
    <p class="management-muted" style="margin-bottom:10px;">Receipts confirmed in <a href="{{ $receiptsUrl }}" style="color:#047857;font-weight:700;">Receipt check</a> show as one line on their bank date; entries not yet checked show one line each on the date they were recorded.</p>
    <div class="management-scroll"><table class="management-table" style="min-width:980px;">
        <thead><tr><th>Date</th>@if($multiSchool)<th>School</th>@endif<th>Type</th><th>Details</th><th>Reference</th><th>Number</th><th class="number">In (TSh)</th><th class="number">Out (TSh)</th><th class="number">Balance (TSh)</th></tr></thead>
        <tbody>
            <tr><td>{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</td>@if($multiSchool)<td></td>@endif<td colspan="5"><strong>Opening balance</strong></td><td></td><td class="number"><strong>{{ $money($opening) }}</strong></td></tr>
            @forelse($lines as $l)
                <tr>
                    <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($l->date)->format('d M Y') }}</td>
                    @if($multiSchool)<td style="white-space:nowrap;"><span style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;background:{{ $schoolDots[$l->school] ?? '#94a3b8' }};"></span>{{ $l->school }}</td>@endif
                    <td style="white-space:nowrap;">@if($l->confirmed)<span style="color:#047857;font-weight:700;"><i class="fa-solid fa-circle-check"></i> Bank receipt</span>@else{{ $l->type }}@endif</td>
                    <td>{{ $l->details ?: '—' }}</td>
                    <td>{{ $l->reference ?: '—' }}</td>
                    <td class="management-muted" style="white-space:nowrap;">{{ $l->number }}</td>
                    <td class="number">{{ $l->in ? $money($l->in) : '' }}</td>
                    <td class="number">{{ $l->out ? $money($l->out) : '' }}</td>
                    <td class="number">{{ $money($l->balance) }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No movements in this account during the period.</td></tr>
            @endforelse
            <tr><td>{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</td>@if($multiSchool)<td></td>@endif<td colspan="5"><strong>Closing balance</strong></td><td></td><td class="number"><strong>{{ $money($balance) }}</strong></td></tr>
        </tbody>
    </table></div>
</section>
