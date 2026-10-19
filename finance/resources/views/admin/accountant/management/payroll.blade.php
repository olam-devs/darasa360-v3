@extends('layouts.management')
@section('title', 'Payroll — School management')
@section('page_title', 'Payroll · '.\App\Http\Controllers\SchoolManagementController::shortName($school->name))
@section('content')
@include('admin.accountant.management.styles')
@php
    $period = date('F Y', mktime(0, 0, 0, $month, 1, $year));
    $unpaid = $staff->filter(fn($s) => !$paid->has($s->id));
    $money = fn($v) => number_format($v, 0);
    $methods = ['bank_transfer' => 'Bank transfer', 'mobile_money' => 'Mobile money', 'cash' => 'Cash', 'cheque' => 'Cheque'];
@endphp
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">Payroll · staff salaries</p>
    <h2>{{ $school->name }}</h2>
    <p class="management-muted">Pay staff for a month and choose the book the money comes out of. Each payment records the net salary as a payment from that book, plus any transaction fee, exactly like the school's own payroll screen.
        @if(count($nav['schools']) > 1) Other schools:
            @foreach($nav['schools'] as $s)@if($s['id'] !== $school->id)<a href="{{ route('accountant.management.payroll', ['school' => $s['id'], 'month' => $month, 'year' => $year]) }}" style="color:#047857;font-weight:600;margin-left:6px;">{{ $s['short'] }}</a>@endif @endforeach
        @endif
    </p>

    <form method="get" class="management-period">
        <input type="hidden" name="school" value="{{ $school->id }}">
        <label>Month<select name="month">@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected($m === $month)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>@endforeach</select></label>
        <label>Year<select name="year">@foreach(range(now('Africa/Dar_es_Salaam')->year + 1, 2025) as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach</select></label>
        <button class="management-primary" type="submit">Show</button>
    </form>
    @if(session('success'))<div class="management-notice" style="background:#ecfdf5;color:#065f46;border-color:#a7f3d0;">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="management-notice">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="management-notice">{{ $errors->first() }}</div>@endif

    <div class="management-grid">
        <div class="management-card"><p class="management-muted">Active staff</p><div class="value">{{ $staff->count() }}</div><p class="management-muted">{{ $paid->count() }} paid for {{ $period }} · {{ $unpaid->count() }} not yet</p></div>
        <div class="management-card"><p class="management-muted">Monthly salaries (gross)</p><div class="value">TSh {{ $money($staff->sum('monthly_salary')) }}</div><p class="management-muted">From each staff member's saved salary</p></div>
        <div class="management-card"><p class="management-muted">Paid for {{ $period }} (net)</p><div class="value">TSh {{ $money($paid->sum('net_salary')) }}</div><p class="management-muted">Gross TSh {{ $money($paid->sum('gross_salary')) }} · deductions TSh {{ $money($paid->sum('total_deductions')) }}</p></div>
    </div>

    @if($staff->isEmpty())
        <section class="management-panel"><h3>No active staff</h3><p class="management-muted">This school has no staff set up yet. Staff are added on the school's payroll screen ("Work inside this school's accounts").</p></section>
    @else
    <form method="post" action="{{ route('accountant.management.payroll.pay', $school->id) }}" id="payroll-form" class="management-panel" data-select-group="payroll">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="year" value="{{ $year }}">
        <h3>Salaries for {{ $period }}</h3>
        <p class="management-muted" style="margin:6px 0 14px;">Saved salaries and deductions are filled in; change any amount for this month before paying. Percentage deductions are a percent of gross. Tick staff to see the totals.</p>

        <div class="management-bulk">
            <div class="management-bulk-total"><span class="management-muted">Selected</span><strong><span data-sel-count>0</span> staff · net TSh <span data-sel-net>0</span></strong><span class="management-muted">gross TSh <span data-sel-gross>0</span> · deductions TSh <span data-sel-ded>0</span></span></div>
            <label>Pay from
                <select name="book_id" id="payroll-book" required>
                    @php $richest = collect($books)->sortByDesc('balance')->first()['id'] ?? null; @endphp
                    @foreach($books as $b)<option value="{{ $b['id'] }}" data-balance="{{ $b['balance'] }}" @selected($b['id'] === $richest)>{{ $b['name'] }} · TSh {{ $money($b['balance']) }}</option>@endforeach
                </select>
            </label>
            <label>Transaction fee<select name="book_fee_category_id" id="payroll-fee"><option value="">None</option></select></label>
            <label>Payment date<input type="date" name="payment_date" value="{{ now('Africa/Dar_es_Salaam')->toDateString() }}" required style="border:1px solid #cbd5e1;border-radius:7px;padding:7px 10px;"></label>
            <label>Method<select name="payment_method">@foreach($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></label>
            <label>Reference<input type="text" name="reference_number" maxlength="255" placeholder="optional" style="border:1px solid #cbd5e1;border-radius:7px;padding:7px 10px;width:130px;"></label>
            <button type="submit" class="management-primary" id="payroll-pay" disabled>Pay selected</button>
        </div>
        <p class="management-notice" id="payroll-balance-warning" hidden>The selected net pay is more than this book's balance. Payments are made one by one and stop being accepted once the book runs out; choose another book or fewer staff.</p>

        <div class="management-scroll"><table class="management-table" id="payroll-table" style="min-width:900px;">
            <thead><tr><th style="width:36px;"><input type="checkbox" data-select-all aria-label="Select all unpaid staff" @disabled($unpaid->isEmpty())></th><th>Staff</th><th class="number">Gross (TSh)</th><th>Deductions</th><th class="number">Net pay (TSh)</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($staff as $s)
                @php $entry = $paid->get($s->id); $deds = $deductions->get($s->id, collect()); @endphp
                <tr data-row @if($entry) data-paid @endif>
                    <td>@unless($entry)<input type="checkbox" data-select-item name="selected[]" value="{{ $s->id }}" aria-label="Select {{ $s->name }}">@endunless</td>
                    <td><strong>{{ $s->name }}</strong><div class="management-muted">{{ $s->position }}@if($s->bank_account) · {{ $s->bank_name }} {{ $s->bank_account }}@endif</div></td>
                    @if($entry)
                        <td class="number">{{ $money($entry->gross_salary) }}</td>
                        <td class="management-muted">TSh {{ $money($entry->total_deductions) }}</td>
                        <td class="number"><strong>{{ $money($entry->net_salary) }}</strong></td>
                        <td><span class="management-badge" style="background:#dcfce7;color:#166534;">Paid</span><div class="management-muted">{{ \Carbon\Carbon::parse($entry->payment_date)->format('d M Y') }}</div></td>
                    @else
                        <td class="number"><input type="number" name="rows[{{ $s->id }}][gross]" value="{{ (float)$s->monthly_salary }}" min="0" step="1" data-gross style="width:120px;border:1px solid #cbd5e1;border-radius:6px;padding:5px;text-align:right;"></td>
                        <td>
                            @forelse($deds as $i => $d)
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;font-size:12px;" data-ded data-type="{{ $d->type }}">
                                    <input type="hidden" name="rows[{{ $s->id }}][deductions][{{ $i }}][name]" value="{{ $d->name }}">
                                    <input type="hidden" name="rows[{{ $s->id }}][deductions][{{ $i }}][type]" value="{{ $d->type }}">
                                    @if($d->deduction_type_id)<input type="hidden" name="rows[{{ $s->id }}][deductions][{{ $i }}][deduction_type_id]" value="{{ $d->deduction_type_id }}">@endif
                                    <span style="min-width:60px;font-weight:600;">{{ $d->name }}</span>
                                    <input type="number" name="rows[{{ $s->id }}][deductions][{{ $i }}][amount]" value="{{ (float)$d->default_amount }}" min="0" step="any" data-ded-amount style="width:90px;border:1px solid #cbd5e1;border-radius:6px;padding:3px 5px;text-align:right;">
                                    <span class="management-muted">{{ $d->type === 'percentage' ? '%' : 'TSh' }}</span>
                                    @if($d->type === 'percentage')<span class="management-muted" data-ded-value></span>@endif
                                </div>
                            @empty
                                <span class="management-muted">None</span>
                            @endforelse
                        </td>
                        <td class="number"><strong data-net>0</strong></td>
                        <td><span class="management-badge" style="background:#fef3c7;color:#92400e;">Not paid</span></td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </form>
    @endif

    <section class="management-panel">
        <h3>Payments for {{ $period }}</h3>
        <div class="management-scroll"><table class="management-table" style="min-width:800px;">
            <thead><tr><th>Staff</th><th class="number">Gross</th><th class="number">Deductions</th><th class="number">Net paid</th><th class="number">Fee</th><th>Book</th><th>Paid</th></tr></thead>
            <tbody>
            @forelse($paid as $staffId => $e)
                <tr><td><strong>{{ $paidNames[$staffId] ?? 'Staff #'.$staffId }}</strong></td><td class="number">{{ $money($e->gross_salary) }}</td><td class="number">{{ $money($e->total_deductions) }}</td>
                    <td class="number"><strong>{{ $money($e->net_salary) }}</strong></td><td class="number">{{ $e->bank_fee_amount ? $money($e->bank_fee_amount) : '—' }}</td>
                    <td>{{ $e->book_name ?? '—' }}</td><td>{{ \Carbon\Carbon::parse($e->payment_date)->format('d M Y') }}<div class="management-muted">{{ $methods[$e->payment_method] ?? $e->payment_method }} · by {{ $payerNames[$e->created_by] ?? 'Unknown' }}</div></td></tr>
            @empty
                <tr><td colspan="7">No salaries recorded for {{ $period }}.</td></tr>
            @endforelse
            </tbody>
            @if($paid->isNotEmpty())
            <tfoot><tr><td><strong>Total</strong></td><td class="number"><strong>{{ $money($paid->sum('gross_salary')) }}</strong></td><td class="number"><strong>{{ $money($paid->sum('total_deductions')) }}</strong></td>
                <td class="number"><strong>{{ $money($paid->sum('net_salary')) }}</strong></td><td class="number"><strong>{{ $money($paid->sum('bank_fee_amount')) }}</strong></td><td colspan="2"></td></tr></tfoot>
            @endif
        </table></div>
    </section>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const form = document.getElementById('payroll-form');
    if (!form) return;
    const books = @json($books);
    const fmt = n => Math.round(n).toLocaleString('en-US');
    const book = document.getElementById('payroll-book'), fee = document.getElementById('payroll-fee');
    function fillFees() {
        const b = books.find(x => String(x.id) === book.value);
        fee.innerHTML = '<option value="">None</option>' + (b ? b.fees.map(f => `<option value="${f.id}">${f.name.replace(/</g, '&lt;')}</option>`).join('') : '');
    }
    function rowFigures(row) {
        const gross = parseFloat(row.querySelector('[data-gross]').value) || 0;
        let ded = 0;
        row.querySelectorAll('[data-ded]').forEach(d => {
            const v = parseFloat(d.querySelector('[data-ded-amount]').value) || 0;
            const amount = d.dataset.type === 'percentage' ? Math.round(gross * v / 100 * 100) / 100 : v;
            const label = d.querySelector('[data-ded-value]'); if (label) label.textContent = '= ' + fmt(amount);
            ded += amount;
        });
        const net = Math.max(0, gross - ded);
        row.querySelector('[data-net]').textContent = fmt(net);
        return {gross, ded, net};
    }
    function update() {
        let count = 0, gross = 0, ded = 0, net = 0;
        form.querySelectorAll('tr[data-row]:not([data-paid])').forEach(row => {
            const f = rowFigures(row);
            if (row.querySelector('[data-select-item]').checked) { count++; gross += f.gross; ded += f.ded; net += f.net; }
        });
        form.querySelector('[data-sel-count]').textContent = count;
        form.querySelector('[data-sel-net]').textContent = fmt(net);
        form.querySelector('[data-sel-gross]').textContent = fmt(gross);
        form.querySelector('[data-sel-ded]').textContent = fmt(ded);
        document.getElementById('payroll-pay').disabled = count === 0;
        const balance = parseFloat(book.selectedOptions[0]?.dataset.balance || '0');
        document.getElementById('payroll-balance-warning').hidden = net <= balance;
        const items = [...form.querySelectorAll('[data-select-item]')], all = form.querySelector('[data-select-all]');
        if (all) { all.checked = items.length > 0 && items.every(i => i.checked); all.indeterminate = !all.checked && items.some(i => i.checked); }
        return {count, net};
    }
    form.addEventListener('input', update);
    form.addEventListener('change', e => {
        if (e.target.matches('[data-select-all]')) form.querySelectorAll('[data-select-item]').forEach(i => i.checked = e.target.checked);
        if (e.target === book) fillFees();
        update();
    });
    form.addEventListener('submit', e => {
        const {count, net} = update();
        if (!form.reportValidity() || !window.confirm(`Pay ${count} staff a total net of TSh ${fmt(net)} from ${book.selectedOptions[0].text.split(' · ')[0]}?`)) { e.preventDefault(); return; }
        document.getElementById('payroll-pay').disabled = true;
    });
    fillFees(); update();
})();
</script>
@endpush
