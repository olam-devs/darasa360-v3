@extends('layouts.management')
@section('title', 'Payroll — School management')
@section('page_title', 'Payroll · '.($selected ? \App\Http\Controllers\SchoolManagementController::shortName($selected->name) : 'All schools'))
@section('content')
@include('admin.accountant.management.styles')
@php
    $period = date('F Y', mktime(0, 0, 0, $month, 1, $year));
    $money = fn($v) => number_format($v, 0);
    $methods = ['bank_transfer' => 'Bank transfer', 'mobile_money' => 'Mobile money', 'cash' => 'Cash', 'cheque' => 'Cheque'];
    $short = fn($name) => \App\Http\Controllers\SchoolManagementController::shortName($name);
    $schoolDots = ['#2a78d6', '#eb6834', '#1baf7a'];
    $dotOf = fn($id) => $schoolDots[array_search($id, array_column($nav['schools'], 'id')) % 3] ?? '#94a3b8';
    $paidThisMonth = $staff->filter(fn($s) => in_array($month, $paidMonths[$s->key] ?? []))->count();
    $schoolBooks = $selected ? ($booksBySchool[$selected->id] ?? []) : [];
    $richest = collect($schoolBooks)->sortByDesc('balance')->first()['id'] ?? null;
    $richestAccount = collect($accountOptions)->sortByDesc('balance')->first()['value'] ?? null;
    $routeQuery = fn(array $q = []) => array_filter($q + ['school' => $selected?->id, 'month' => $month, 'year' => $year]);
@endphp
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">Payroll · staff salaries</p>
    <h2>{{ $selected ? $selected->name : 'All schools' }}</h2>
    <p class="management-muted">Pay staff for one or more months and choose the account the money comes out of. Each payment is recorded in the staff member's own school, exactly like the school's payroll screen.
        Show: <a href="{{ route('accountant.management.payroll', array_filter(['month' => $month, 'year' => $year])) }}" style="color:#047857;font-weight:{{ $selected ? 600 : 800 }};margin-left:4px;">All schools</a>
        @foreach($nav['schools'] as $s)<a href="{{ route('accountant.management.payroll', ['school' => $s['id'], 'month' => $month, 'year' => $year]) }}" style="color:#047857;font-weight:{{ $selected?->id === $s['id'] ? 800 : 600 }};margin-left:8px;">{{ $s['short'] }}</a>@endforeach
    </p>

    <form method="get" class="management-period">
        @if($selected)<input type="hidden" name="school" value="{{ $selected->id }}">@endif
        <label>Month<select name="month">@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected($m === $month)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>@endforeach</select></label>
        <label>Year<select name="year">@foreach(range(now('Africa/Dar_es_Salaam')->year + 1, 2025) as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach</select></label>
        <button class="management-primary" type="submit">Show</button>
    </form>
    @if(session('success'))<div class="management-notice" style="background:#ecfdf5;color:#065f46;border-color:#a7f3d0;">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="management-notice">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="management-notice">{{ $errors->first() }}</div>@endif

    <div class="management-grid">
        <div class="management-card"><p class="management-muted">Active staff</p><div class="value">{{ $staff->count() }}</div><p class="management-muted">{{ $paidThisMonth }} paid for {{ $period }} · {{ $staff->count() - $paidThisMonth }} not yet</p></div>
        <div class="management-card"><p class="management-muted">Monthly salaries (gross)</p><div class="value">TSh {{ $money($staff->sum('monthly_salary')) }}</div><p class="management-muted">From each staff member's saved salary</p></div>
        <div class="management-card"><p class="management-muted">Paid for {{ $period }} (net)</p><div class="value">TSh {{ $money($paid->sum('net_salary')) }}</div><p class="management-muted">Gross TSh {{ $money($paid->sum('gross_salary')) }} · deductions TSh {{ $money($paid->sum('total_deductions')) }}</p></div>
    </div>

    @if($staff->isEmpty())
        <section class="management-panel"><h3>No active staff</h3><p class="management-muted">Register staff below.</p></section>
    @else
    <form method="post" action="{{ route('accountant.management.payroll.pay') }}" id="payroll-form" class="management-panel">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="year" value="{{ $year }}">
        @if($selected)<input type="hidden" name="school" value="{{ $selected->id }}">@endif
        <h3>Salaries · {{ $year }}</h3>
        <p class="management-muted" style="margin:6px 0 14px;">Saved salaries and deductions are filled in; change any amount before paying. Percentage deductions are a percent of gross. Tick the months and the staff to pay: each ticked person is paid the same amount for every ticked month they are not yet paid for; months already paid are skipped.</p>

        <div class="management-months" role="group" aria-label="Months to pay">
            <span class="management-muted" style="font-weight:600;">Months to pay</span>
            @foreach(range(1, 12) as $m)
                @php $paidAll = $staff->every(fn($s) => in_array($m, $paidMonths[$s->key] ?? [])); @endphp
                <label class="management-month @if($paidAll) is-paid @endif" title="{{ $paidAll ? 'All staff paid' : date('F', mktime(0, 0, 0, $m, 1)) }}">
                    <input type="checkbox" name="months[]" value="{{ $m }}" data-month @checked($m === $month && !$paidAll) @disabled($paidAll)>
                    <span>{{ date('M', mktime(0, 0, 0, $m, 1)) }}@if($paidAll) ✓@endif</span>
                </label>
            @endforeach
        </div>

        <div class="management-bulk">
            <div class="management-bulk-total"><span class="management-muted">Selected</span><strong><span data-sel-count>0</span> payment(s) · net TSh <span data-sel-net>0</span></strong><span class="management-muted"><span data-sel-staff>0</span> staff × <span data-sel-months>0</span> month(s) · gross TSh <span data-sel-gross>0</span> · deductions TSh <span data-sel-ded>0</span></span></div>
            <label>Pay from
                <select name="pay_from" id="payroll-book" required>
                    @if($selected)
                        @foreach($schoolBooks as $b)<option value="book:{{ $b['id'] }}" data-balance="{{ $b['balance'] }}" @selected($b['id'] === $richest)>{{ $b['name'] }} · TSh {{ $money($b['balance']) }}</option>@endforeach
                    @else
                        @foreach($accountOptions as $o)<option value="{{ $o['value'] }}" data-balance="{{ $o['balance'] }}" @selected($o['value'] === $richestAccount)>{{ $o['label'] }} · TSh {{ $money($o['balance']) }} (all schools)</option>@endforeach
                        <option value="cash" data-balance="">Cash book (each school's own)</option>
                    @endif
                </select>
            </label>
            @if($selected)<label>Transaction fee<select name="book_fee_category_id" id="payroll-fee"><option value="">None</option></select></label>@endif
            <label>Payment date<input type="date" name="payment_date" value="{{ now('Africa/Dar_es_Salaam')->toDateString() }}" required style="border:1px solid #cbd5e1;border-radius:7px;padding:7px 10px;"></label>
            <label>Method<select name="payment_method">@foreach($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></label>
            <label>Reference<input type="text" name="reference_number" maxlength="255" placeholder="optional" style="border:1px solid #cbd5e1;border-radius:7px;padding:7px 10px;width:130px;"></label>
            <button type="submit" class="management-primary" id="payroll-pay" disabled>Pay selected</button>
        </div>
        @unless($selected)<p class="management-muted" style="margin:-4px 0 10px;">All schools: each person is paid from their own school's book for the chosen account. Each school's balance is checked separately; transaction fees are chosen on a single school's page.</p>@endunless
        <p class="management-notice" id="payroll-balance-warning" hidden>The selected net pay is more than the balance shown for this account. Payments are made one by one and are refused once a book runs out.</p>

        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
            <button type="button" class="management-chip-button" data-select="all">Select all</button>
            <button type="button" class="management-chip-button" data-select="unpaid">Select everyone not paid for the ticked months</button>
            <button type="button" class="management-chip-button" data-select="none">Select none</button>
        </div>
        <div class="management-scroll"><table class="management-table" id="payroll-table" style="min-width:960px;">
            <thead><tr><th style="width:36px;"><input type="checkbox" data-select-all aria-label="Select all staff"></th><th>Staff</th>@unless($selected)<th>School</th>@endunless<th class="number">Gross (TSh)</th><th>Deductions</th><th class="number">Net pay (TSh)</th><th>Paid in {{ $year }}</th></tr></thead>
            <tbody>
            @foreach($staff as $s)
                @php $deds = $deductions[$s->key] ?? collect(); $doneMonths = $paidMonths[$s->key] ?? []; sort($doneMonths); @endphp
                <tr data-row data-paid-months="{{ implode(',', $doneMonths) }}">
                    <td><input type="checkbox" data-select-item name="selected[]" value="{{ $s->key }}" aria-label="Select {{ $s->name }}"></td>
                    <td><strong>{{ $s->name }}</strong><div class="management-muted">{{ $s->position }}@if($s->bank_account) · {{ $s->bank_name }} {{ $s->bank_account }}@endif</div></td>
                    @unless($selected)<td style="white-space:nowrap;"><span class="mgmt-dot" style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;background:{{ $dotOf($s->school_id) }};"></span>{{ $s->school }}</td>@endunless
                    <td class="number"><input type="number" name="rows[{{ $s->key }}][gross]" value="{{ (float)$s->monthly_salary }}" min="0" step="1" data-gross style="width:120px;border:1px solid #cbd5e1;border-radius:6px;padding:5px;text-align:right;"></td>
                    <td>
                        @forelse($deds as $i => $d)
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;font-size:12px;" data-ded data-type="{{ $d->type }}">
                                <input type="hidden" name="rows[{{ $s->key }}][deductions][{{ $i }}][name]" value="{{ $d->name }}">
                                <input type="hidden" name="rows[{{ $s->key }}][deductions][{{ $i }}][type]" value="{{ $d->type }}">
                                @if($d->deduction_type_id)<input type="hidden" name="rows[{{ $s->key }}][deductions][{{ $i }}][deduction_type_id]" value="{{ $d->deduction_type_id }}">@endif
                                <span style="min-width:60px;font-weight:600;">{{ $d->name }}</span>
                                <input type="number" name="rows[{{ $s->key }}][deductions][{{ $i }}][amount]" value="{{ (float)$d->default_amount }}" min="0" step="any" data-ded-amount style="width:90px;border:1px solid #cbd5e1;border-radius:6px;padding:3px 5px;text-align:right;">
                                <span class="management-muted">{{ $d->type === 'percentage' ? '%' : 'TSh' }}</span>
                                @if($d->type === 'percentage')<span class="management-muted" data-ded-value></span>@endif
                            </div>
                        @empty
                            <span class="management-muted">None</span>
                        @endforelse
                    </td>
                    <td class="number"><strong data-net>0</strong></td>
                    <td>@forelse($doneMonths as $m)<span class="management-badge" style="background:#dcfce7;color:#166534;margin:0 3px 3px 0;">{{ date('M', mktime(0, 0, 0, $m, 1)) }}</span>@empty<span class="management-badge" style="background:#fef3c7;color:#92400e;">None yet</span>@endforelse</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </form>
    @endif

    <section class="management-panel">
        <h3>Payments for {{ $period }}</h3>
        <div class="management-scroll"><table class="management-table" style="min-width:820px;">
            <thead><tr><th>Staff</th>@unless($selected)<th>School</th>@endunless<th class="number">Gross</th><th class="number">Deductions</th><th class="number">Net paid</th><th class="number">Fee</th><th>Book</th><th>Paid</th></tr></thead>
            <tbody>
            @forelse($paid as $e)
                <tr><td><strong>{{ $e->name ?? 'Staff #'.$e->staff_id }}</strong></td>@unless($selected)<td>{{ $e->school }}</td>@endunless
                    <td class="number">{{ $money($e->gross_salary) }}</td><td class="number">{{ $money($e->total_deductions) }}</td>
                    <td class="number"><strong>{{ $money($e->net_salary) }}</strong></td><td class="number">{{ $e->bank_fee_amount ? $money($e->bank_fee_amount) : '—' }}</td>
                    <td>{{ $e->book_name ?? '—' }}</td><td>{{ \Carbon\Carbon::parse($e->payment_date)->format('d M Y') }}<div class="management-muted">{{ $methods[$e->payment_method] ?? $e->payment_method }} · by {{ $payerNames[$e->created_by] ?? 'Unknown' }}</div></td></tr>
            @empty
                <tr><td colspan="8">No salaries recorded for {{ $period }}.</td></tr>
            @endforelse
            </tbody>
            @if($paid->isNotEmpty())
            <tfoot><tr><td @unless($selected) colspan="2" @endunless><strong>Total</strong></td><td class="number"><strong>{{ $money($paid->sum('gross_salary')) }}</strong></td><td class="number"><strong>{{ $money($paid->sum('total_deductions')) }}</strong></td>
                <td class="number"><strong>{{ $money($paid->sum('net_salary')) }}</strong></td><td class="number"><strong>{{ $money($paid->sum('bank_fee_amount')) }}</strong></td><td colspan="2"></td></tr></tfoot>
            @endif
        </table></div>
    </section>

    {{-- Staff management: register, edit and move --}}
    <section class="management-panel" id="staff">
        <h3>Staff · register, edit or move</h3>
        <p class="management-muted" style="margin:6px 0 14px;">Register several people at once (rows without a name are ignored), edit anyone's details and standing deductions, or move someone to another school. Moving copies the person and their deductions to the new school and marks them inactive in the old one; salaries already paid stay in the old school's books.</p>

        <form method="post" action="{{ route('accountant.management.staff.register') }}" id="register-form" style="margin-bottom:24px;">
            @csrf
            @if($selected)<input type="hidden" name="return_school" value="{{ $selected->id }}">@endif
            <div class="management-scroll"><table class="management-table" style="min-width:980px;">
                <thead><tr><th>School</th><th>Name</th><th>Job title</th><th class="number">Monthly salary</th><th>Phone</th><th>Bank / mobile</th><th>Account no.</th><th>NSSF 10%</th><th class="number">PAYE (TSh)</th><th></th></tr></thead>
                <tbody id="register-rows">
                @foreach(range(0, 2) as $i)
                    <tr data-reg-row>
                        <td><select name="staff[{{ $i }}][school_id]" class="reg-input">@foreach($allSchools as $sc)<option value="{{ $sc->id }}" @selected($selected?->id === $sc->id)>{{ $short($sc->name) }}</option>@endforeach</select></td>
                        <td><input type="text" name="staff[{{ $i }}][name]" maxlength="255" class="reg-input" style="width:170px;"></td>
                        <td><input type="text" name="staff[{{ $i }}][position]" maxlength="255" class="reg-input" style="width:140px;"></td>
                        <td class="number"><input type="number" name="staff[{{ $i }}][monthly_salary]" min="0" step="1" class="reg-input" style="width:110px;text-align:right;"></td>
                        <td><input type="text" name="staff[{{ $i }}][phone]" maxlength="255" class="reg-input" style="width:110px;"></td>
                        <td><input type="text" name="staff[{{ $i }}][bank_name]" maxlength="255" class="reg-input" style="width:90px;" placeholder="CRDB"></td>
                        <td><input type="text" name="staff[{{ $i }}][bank_account]" maxlength="255" class="reg-input" style="width:120px;"></td>
                        <td style="text-align:center;"><input type="checkbox" name="staff[{{ $i }}][nssf]" value="1"></td>
                        <td class="number"><input type="number" name="staff[{{ $i }}][paye]" min="0" step="1" class="reg-input" style="width:90px;text-align:right;"></td>
                        <td><button type="button" data-remove-row title="Remove row" style="color:#b91c1c;font-weight:700;">✕</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div style="display:flex;gap:10px;margin-top:12px;">
                <button type="button" id="add-register-row" style="border:1px solid #cbd5e1;border-radius:8px;padding:9px 14px;background:#fff;font-size:13px;font-weight:600;">+ Add another row</button>
                <button class="management-primary" type="submit">Register staff</button>
            </div>
        </form>

        <div class="management-scroll"><table class="management-table" style="min-width:760px;">
            <thead><tr><th>Staff</th><th>School</th><th class="number">Salary</th><th>Standing deductions</th><th>Edit</th><th>Move to</th></tr></thead>
            <tbody>
            @forelse($staff as $s)
                @php $standing = $deductions[$s->key] ?? collect(); @endphp
                <tr><td><strong>{{ $s->name }}</strong><div class="management-muted">{{ $s->position }} · code {{ $s->staff_id }}</div></td>
                    <td style="white-space:nowrap;"><span style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;background:{{ $dotOf($s->school_id) }};"></span>{{ $s->school }}</td>
                    <td class="number">{{ $money($s->monthly_salary) }}</td>
                    <td>@forelse($standing as $d)<span class="management-badge" style="background:#f1f5f9;color:#334155;margin:0 3px 3px 0;">{{ $d->name }} {{ $d->type === 'percentage' ? (float)$d->default_amount.'%' : $money($d->default_amount) }}</span>@empty<span class="management-muted">None</span>@endforelse</td>
                    <td><button type="button" data-edit-toggle="edit-{{ $s->key }}" style="border:1px solid #cbd5e1;border-radius:6px;padding:5px 10px;background:#f8fafc;font-size:12px;font-weight:600;">Edit</button></td>
                    <td><form method="post" action="{{ route('accountant.management.staff.move') }}" style="display:flex;gap:6px;" data-move-form>
                        @csrf<input type="hidden" name="key" value="{{ $s->key }}">@if($selected)<input type="hidden" name="return_school" value="{{ $selected->id }}">@endif
                        <select name="to_school_id" style="border:1px solid #cbd5e1;border-radius:6px;padding:5px;">@foreach($allSchools as $sc)@if($sc->id !== $s->school_id)<option value="{{ $sc->id }}">{{ $short($sc->name) }}</option>@endif @endforeach</select>
                        <button type="submit" style="border:1px solid #cbd5e1;border-radius:6px;padding:5px 10px;background:#f8fafc;font-size:12px;font-weight:600;" data-name="{{ $s->name }}">Move</button>
                    </form></td></tr>
                <tr id="edit-{{ $s->key }}" hidden><td colspan="6" style="background:#f8fafc;">
                    <form method="post" action="{{ route('accountant.management.staff.update') }}">
                        @csrf<input type="hidden" name="key" value="{{ $s->key }}">@if($selected)<input type="hidden" name="return_school" value="{{ $selected->id }}">@endif
                        <div class="management-target" style="margin-top:4px;">
                            <label>Name<input type="text" name="name" value="{{ $s->name }}" required maxlength="255"></label>
                            <label>Job title<input type="text" name="position" value="{{ $s->position }}" required maxlength="255"></label>
                            <label>Monthly salary (TSh)<input type="number" name="monthly_salary" value="{{ (float)$s->monthly_salary }}" required min="0" step="1"></label>
                            <label>Phone<input type="text" name="phone" value="{{ $s->phone }}" maxlength="255"></label>
                            <label>Bank / mobile<input type="text" name="bank_name" value="{{ $s->bank_name }}" maxlength="255"></label>
                            <label>Account number<input type="text" name="bank_account" value="{{ $s->bank_account }}" maxlength="255"></label>
                        </div>
                        <p style="font-size:13px;font-weight:700;margin:14px 0 6px;">Standing deductions</p>
                        @forelse($standing as $d)
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:13px;">
                                <span style="min-width:70px;font-weight:600;">{{ $d->name }}</span>
                                <input type="number" name="deductions[{{ $d->id }}][amount]" value="{{ (float)$d->default_amount }}" min="0" step="any" style="width:110px;border:1px solid #cbd5e1;border-radius:6px;padding:4px 6px;text-align:right;">
                                <span class="management-muted">{{ $d->type === 'percentage' ? '% of gross' : 'TSh' }}</span>
                                <label style="display:flex;align-items:center;gap:5px;color:#b91c1c;font-weight:600;margin-left:10px;"><input type="checkbox" name="deductions[{{ $d->id }}][remove]" value="1"> Remove</label>
                            </div>
                        @empty
                            <p class="management-muted">No standing deductions.</p>
                        @endforelse
                        @foreach(range(0, 1) as $n)
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:13px;">
                                <span class="management-muted" style="min-width:70px;">Add</span>
                                <input type="text" name="new_deductions[{{ $n }}][name]" placeholder="e.g. NSSF, PAYEE, loan" maxlength="255" style="width:150px;border:1px solid #cbd5e1;border-radius:6px;padding:4px 6px;">
                                <select name="new_deductions[{{ $n }}][type]" style="border:1px solid #cbd5e1;border-radius:6px;padding:4px;"><option value="fixed">TSh</option><option value="percentage">% of gross</option></select>
                                <input type="number" name="new_deductions[{{ $n }}][amount]" min="0" step="any" placeholder="amount" style="width:110px;border:1px solid #cbd5e1;border-radius:6px;padding:4px 6px;text-align:right;">
                            </div>
                        @endforeach
                        <div style="display:flex;gap:10px;margin-top:12px;"><button class="management-primary" type="submit">Save changes</button><button type="button" data-edit-toggle="edit-{{ $s->key }}" style="font-size:13px;color:#475569;">Cancel</button></div>
                    </form>
                </td></tr>
            @empty
                <tr><td colspan="6">No active staff yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</div>
@endsection
@push('scripts')
<script>
(() => {
    document.querySelectorAll('[data-move-form]').forEach(f => f.addEventListener('submit', e => {
        const to = f.querySelector('select').selectedOptions[0].text;
        if (!window.confirm(`Move ${f.querySelector('button').dataset.name} to ${to}? Salaries already paid stay in the current school.`)) e.preventDefault();
    }));
    document.querySelectorAll('[data-edit-toggle]').forEach(b => b.addEventListener('click', () => {
        const row = document.getElementById(b.dataset.editToggle); row.hidden = !row.hidden;
    }));
    // Register form: add rows (copies of the first, emptied) and remove rows; names are renumbered on submit.
    const regBody = document.getElementById('register-rows');
    document.getElementById('add-register-row')?.addEventListener('click', () => {
        const row = regBody.querySelector('[data-reg-row]').cloneNode(true);
        row.querySelectorAll('input').forEach(i => { if (i.type === 'checkbox') i.checked = false; else i.value = ''; });
        regBody.appendChild(row); row.querySelector('input[type=text]').focus();
    });
    regBody?.addEventListener('click', e => {
        if (!e.target.matches('[data-remove-row]')) return;
        const rows = regBody.querySelectorAll('[data-reg-row]');
        if (rows.length > 1) e.target.closest('tr').remove();
        else e.target.closest('tr').querySelectorAll('input').forEach(i => { if (i.type === 'checkbox') i.checked = false; else i.value = ''; });
    });
    document.getElementById('register-form')?.addEventListener('submit', () => {
        regBody.querySelectorAll('[data-reg-row]').forEach((row, n) => row.querySelectorAll('[name^="staff["]').forEach(i => i.name = i.name.replace(/^staff\[\d+\]/, `staff[${n}]`)));
    });
    const form = document.getElementById('payroll-form');
    if (!form) return;
    const books = @json($schoolBooks);
    const fmt = n => Math.round(n).toLocaleString('en-US');
    const book = document.getElementById('payroll-book'), fee = document.getElementById('payroll-fee');
    function fillFees() {
        if (!fee) return;
        const b = books.find(x => 'book:' + x.id === book.value);
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
        const months = [...form.querySelectorAll('[data-month]:checked')].map(m => +m.value);
        let count = 0, people = 0, gross = 0, ded = 0, net = 0;
        form.querySelectorAll('tr[data-row]').forEach(row => {
            const f = rowFigures(row);
            if (!row.querySelector('[data-select-item]').checked) return;
            people++;
            // Months this person is already paid for are skipped, so they do not count.
            const done = (row.dataset.paidMonths || '').split(',').filter(Boolean).map(Number);
            const due = months.filter(m => !done.includes(m)).length;
            count += due; gross += f.gross * due; ded += f.ded * due; net += f.net * due;
        });
        form.querySelector('[data-sel-count]').textContent = count;
        form.querySelector('[data-sel-staff]').textContent = people;
        form.querySelector('[data-sel-months]').textContent = months.length;
        form.querySelector('[data-sel-net]').textContent = fmt(net);
        form.querySelector('[data-sel-gross]').textContent = fmt(gross);
        form.querySelector('[data-sel-ded]').textContent = fmt(ded);
        document.getElementById('payroll-pay').disabled = count === 0;
        const balance = book.selectedOptions[0]?.dataset.balance;
        document.getElementById('payroll-balance-warning').hidden = balance === '' || balance === undefined || net <= parseFloat(balance);
        const items = [...form.querySelectorAll('[data-select-item]')], all = form.querySelector('[data-select-all]');
        if (all) { all.checked = items.length > 0 && items.every(i => i.checked); all.indeterminate = !all.checked && items.some(i => i.checked); }
        return {count, net, months};
    }
    form.querySelectorAll('[data-select]').forEach(b => b.addEventListener('click', () => {
        const months = [...form.querySelectorAll('[data-month]:checked')].map(m => +m.value);
        form.querySelectorAll('tr[data-row]').forEach(row => {
            const done = (row.dataset.paidMonths || '').split(',').filter(Boolean).map(Number);
            row.querySelector('[data-select-item]').checked = b.dataset.select === 'all'
                || (b.dataset.select === 'unpaid' && months.some(m => !done.includes(m)));
        });
        update();
    }));
    form.addEventListener('input', update);
    form.addEventListener('change', e => {
        if (e.target.matches('[data-select-all]')) form.querySelectorAll('[data-select-item]').forEach(i => i.checked = e.target.checked);
        if (e.target === book) fillFees();
        update();
    });
    form.addEventListener('submit', e => {
        const {count, net, months} = update();
        const names = months.map(m => new Date(2000, m - 1, 1).toLocaleString('en', {month: 'short'})).join(', ');
        if (!form.reportValidity() || !window.confirm(`Make ${count} salary payment(s) for ${names} {{ $year }}, total net TSh ${fmt(net)}, from ${book.selectedOptions[0].text.split(' · ')[0]}?\n\nMonths already paid are skipped.`)) { e.preventDefault(); return; }
        document.getElementById('payroll-pay').disabled = true;
    });
    fillFees(); update();
})();
</script>
@endpush
