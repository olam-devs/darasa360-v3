@extends('layouts.management')
@php
    $money = fn($v) => number_format($v, 0);
    $schoolDots = ['#2a78d6', '#eb6834', '#1baf7a'];
    $dotOf = fn($id) => $schoolDots[array_search($id, array_column($nav['schools'], 'id')) % 3] ?? '#94a3b8';
    $multi = $proposals->filter(fn($p) => $p->items->count() > 1);
@endphp
@section('title', 'Receipt check — School management')
@section('page_title', 'Receipt check · '.($selected ? \App\Http\Controllers\SchoolManagementController::shortName($selected->name) : 'All schools'))
@section('content')
@include('admin.accountant.management.styles')
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">Receipt check · {{ $periodLabel }}</p>
    <h2>{{ $selected ? $selected->name : 'All schools' }}</h2>
    <p class="management-muted">
        Fee entries were recorded one particular at a time, even when the parent paid once. Below, entries for the same student, on the same date, into the same account are
        put together as the payment they probably were. Check each one against the bank slip or statement: untick anything that was a separate payment, set the real bank date
        and the bank reference, then confirm. Confirming changes nothing in the school's books; it only records how the money reached the bank, so the
        <a href="{{ route('accountant.management.bank-ledger', array_filter(['school' => $selected?->id])) }}" style="color:#047857;font-weight:700;">Bank ledger</a> can match the statement.
    </p>
    @include('admin.accountant.management.period')

    <div class="management-grid" style="margin-top:18px;">
        <div class="management-card"><p class="management-muted">To check</p><div class="value">{{ $proposals->count() }}</div><p class="management-muted">{{ $multi->count() }} with more than one item</p></div>
        <div class="management-card"><p class="management-muted">Amount to check</p><div class="value">TSh {{ $money($proposals->sum('total')) }}</div><p class="management-muted">{{ $proposals->sum(fn($p) => $p->items->count()) }} fee entries</p></div>
        <div class="management-card"><p class="management-muted">Confirmed</p><div class="value">{{ $confirmed->count() }}</div><p class="management-muted">TSh {{ $money($confirmed->sum('total')) }} by bank date in this period</p></div>
    </div>

    <section class="management-panel">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <h3>Proposed receipts</h3>
            <div class="management-muted">
                Show:
                <a href="{{ request()->fullUrlWithoutQuery(['multi']) }}" style="color:#047857;font-weight:{{ $onlyMulti ? 600 : 800 }};margin-left:4px;">All</a>
                <a href="{{ request()->fullUrlWithQuery(['multi' => 1]) }}" style="color:#047857;font-weight:{{ $onlyMulti ? 800 : 600 }};margin-left:8px;">Only those with more than one item</a>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px;margin-top:14px;">
        @forelse($proposals as $i => $p)
            <form method="post" action="{{ route('accountant.management.receipts.confirm') }}" class="receipt-card" data-receipt>
                @csrf
                <input type="hidden" name="school_id" value="{{ $p->school_id }}">
                <div class="receipt-head">
                    <div>
                        <strong style="font-size:15px;">{{ $p->student ?: 'Unknown student' }}</strong>
                        <span class="management-muted">{{ $p->class }}</span>
                        <div class="management-muted" style="margin-top:2px;">
                            @unless($selected)<span style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:5px;background:{{ $dotOf($p->school_id) }};"></span>{{ $p->school }} · @endunless
                            {{ $p->book }} · recorded {{ \Carbon\Carbon::parse($p->date)->format('d M Y') }}
                        </div>
                    </div>
                    <div style="text-align:right;"><span class="management-muted">Bank receipt total</span><div style="font-size:18px;font-weight:800;">TSh <span data-total>{{ $money($p->total) }}</span></div></div>
                </div>
                @foreach($p->hints as $hint)<p class="receipt-hint"><i class="fa-solid fa-circle-info"></i> {{ $hint }}</p>@endforeach
                <table class="management-table" style="margin-top:8px;">
                    <thead><tr><th style="width:36px;"></th><th>Particular</th><th>Quarter</th><th>Voucher</th><th>Entered</th><th class="number">Amount (TSh)</th></tr></thead>
                    <tbody>
                    @foreach($p->items as $it)
                        <tr>
                            <td><input type="checkbox" name="voucher_ids[]" value="{{ $it->id }}" data-amount="{{ $it->amount }}" checked aria-label="Include {{ $it->particular }}"></td>
                            <td>{{ $it->particular }}</td>
                            <td>{{ $it->quarter ? 'Q'.$it->quarter : '—' }}</td>
                            <td>{{ $it->number ?: '—' }}</td>
                            <td class="management-muted">{{ $it->entered ? \Carbon\Carbon::parse($it->entered)->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i') : '—' }}</td>
                            <td class="number">{{ $money($it->amount) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="receipt-actions">
                    <label>Bank date<input type="date" name="receipt_date" value="{{ $p->date }}" required></label>
                    <label>Bank reference<input type="text" name="reference" maxlength="120" placeholder="Slip or transaction no."></label>
                    <label style="flex:1;min-width:180px;">Note<input type="text" name="note" maxlength="500" placeholder="Optional"></label>
                    <button type="submit" class="management-primary" data-confirm>Confirm <span data-count>{{ $p->items->count() }}</span> item(s)</button>
                </div>
                @if($p->items->count() > 1)<p class="management-muted" style="margin:6px 0 0;font-size:12px;">To split: untick the items that were a separate payment and confirm the rest; the unticked ones come back as their own receipt.</p>@endif
            </form>
        @empty
            <p class="management-muted">Nothing left to check in this period{{ $onlyMulti ? ' with more than one item' : '' }}.</p>
        @endforelse
        </div>
    </section>

    <section class="management-panel">
        <h3>Confirmed receipts · bank date in {{ $periodLabel }}</h3>
        <div class="management-scroll" style="margin-top:12px;"><table class="management-table" style="min-width:860px;">
            <thead><tr><th>Bank date</th><th>Student</th>@unless($selected)<th>School</th>@endunless<th>Account</th><th>Items</th><th>Reference</th><th class="number">Total (TSh)</th><th></th></tr></thead>
            <tbody>
            @forelse($confirmed as $g)
                <tr>
                    <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($g->receipt_date)->format('d M Y') }}</td>
                    <td><strong>{{ $g->student }}</strong>@if($g->note)<div class="management-muted">{{ $g->note }}</div>@endif</td>
                    @unless($selected)<td style="white-space:nowrap;"><span style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;background:{{ $dotOf($g->school_id) }};"></span>{{ $g->school }}</td>@endunless
                    <td>{{ $g->book }}</td>
                    <td>{{ $g->items->map(fn($it) => $it->particular.($it->quarter ? ' (Q'.$it->quarter.')' : '').' '.$money($it->amount))->implode(' · ') }}
                        @if($g->items->pluck('date')->unique()->contains(fn($d) => $d !== $g->receipt_date))<div class="management-muted">Recorded {{ $g->items->pluck('date')->unique()->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->implode(', ') }}</div>@endif</td>
                    <td>{{ $g->reference ?: '—' }}</td>
                    <td class="number"><strong>{{ $money($g->total) }}</strong></td>
                    <td><form method="post" action="{{ route('accountant.management.receipts.undo', $g->id) }}" onsubmit="return confirm('Undo this confirmed receipt? Its items go back to the proposals.');">@csrf<button type="submit" class="management-chip-button">Undo</button></form></td>
                </tr>
            @empty
                <tr><td colspan="8">No receipts confirmed with a bank date in this period yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</div>
<style>
    .receipt-card { border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; background:#fff; }
    .receipt-head { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start; }
    .receipt-hint { margin:8px 0 0; padding:6px 10px; border-radius:8px; background:#fffbeb; color:#92400e; font-size:13px; }
    .receipt-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:10px; }
    .receipt-actions label { display:flex; flex-direction:column; gap:4px; font-size:12px; font-weight:600; color:#475569; }
    .receipt-actions input { border:1px solid #cbd5e1; border-radius:7px; padding:7px 10px; font-size:14px; font-weight:400; color:#0f172a; }
</style>
@endsection
@push('scripts')
<script>
(() => {
    const fmt = n => Math.round(n).toLocaleString('en-US');
    document.querySelectorAll('[data-receipt]').forEach(form => {
        const boxes = [...form.querySelectorAll('input[name="voucher_ids[]"]')];
        const update = () => {
            const on = boxes.filter(b => b.checked);
            form.querySelector('[data-total]').textContent = fmt(on.reduce((s, b) => s + parseFloat(b.dataset.amount || 0), 0));
            form.querySelector('[data-count]').textContent = on.length;
            form.querySelector('[data-confirm]').disabled = on.length === 0;
        };
        boxes.forEach(b => b.addEventListener('change', update));
    });
})();
</script>
@endpush
