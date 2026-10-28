{{--
    Receipt check body, shared by the management portal and the school accountants.
    Needs: $proposals, $confirmed, $onlyMulti, $q, $found, $student, $periodLabel, $schoolDots (school id => colour) or empty,
    $showSchool (bool), $searchSchoolId (int|null: search and merge work in one school), $routes = ['confirm' => url, 'update' => fn($id), 'undo' => fn($id)],
    $ledgerUrl.
--}}
@php
    $money = fn($v) => number_format($v, 0);
    $multi = $proposals->filter(fn($p) => $p->items->count() > 1);
    $dot = fn($id) => '<span style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:5px;background:'.($schoolDots[$id] ?? '#94a3b8').';"></span>';
    $baseQuery = request()->except(['q', 'student', 'multi', 'page']);
@endphp

<div class="management-grid" style="margin-top:18px;">
    <div class="management-card"><p class="management-muted">{{ $student ? 'Open entries' : 'To check' }}</p><div class="value">{{ $proposals->sum(fn($p) => $p->items->count()) }}</div><p class="management-muted">{{ $student ? 'not yet confirmed' : $proposals->count().' proposed receipt(s) · '.$multi->count().' with more than one entry' }}</p></div>
    <div class="management-card"><p class="management-muted">Amount to check</p><div class="value">TSh {{ $money($proposals->sum('total')) }}</div><p class="management-muted">{{ $student ? $student->name : $periodLabel }}</p></div>
    <div class="management-card"><p class="management-muted">Confirmed</p><div class="value">{{ $confirmed->count() }}</div><p class="management-muted">TSh {{ $money($confirmed->sum('total')) }}{{ $student ? '' : ' by bank date in this period' }}</p></div>
</div>

<section class="management-panel">
    <h3>Find a student</h3>
    @if($searchSchoolId)
        <p class="management-muted">See all of a student's fee entries (any date) to merge entries that were one bank payment, or to correct a bank date.</p>
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
            @foreach($baseQuery as $k => $v)@if(is_array($v))@foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach @else<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
            <input type="search" name="q" value="{{ $q }}" placeholder="Student name or registration number" style="flex:1;min-width:220px;border:1px solid #cbd5e1;border-radius:7px;padding:8px 10px;font-size:14px;">
            <button class="management-primary" type="submit">Search</button>
            @if($student || $q)<a href="{{ url()->current().'?'.http_build_query($baseQuery) }}" style="align-self:center;color:#047857;font-weight:600;">Back to all receipts</a>@endif
        </form>
        @if($q !== '')
            <div class="management-scroll" style="margin-top:10px;"><table class="management-table">
                <thead><tr><th>Student</th><th>Class</th><th>Reg. no</th><th class="number">Fee entries</th><th></th></tr></thead>
                <tbody>
                @forelse($found as $s)
                    <tr><td><strong>{{ $s->name }}</strong>@if($s->status !== 'active')<span class="management-muted"> ({{ $s->status }})</span>@endif</td><td>{{ $s->class ?: '—' }}</td><td>{{ $s->student_reg_no ?: '—' }}</td><td class="number">{{ $s->receipts }}</td>
                        <td><a href="{{ url()->current().'?'.http_build_query($baseQuery + ['student' => $s->id]) }}" style="color:#047857;font-weight:700;">View receipts</a></td></tr>
                @empty
                    <tr><td colspan="5">No student matches "{{ $q }}".</td></tr>
                @endforelse
                </tbody>
            </table></div>
        @endif
    @else
        <p class="management-muted">Choose one school (in the sidebar) to search a student and merge their entries.</p>
    @endif
</section>

<section class="management-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <h3>{{ $student ? 'Fee entries of '.$student->name.($student->class ? ' · '.$student->class : '') : 'Proposed receipts' }}</h3>
        @unless($student)
        <div class="management-muted">Show:
            <a href="{{ request()->fullUrlWithoutQuery(['multi']) }}" style="color:#047857;font-weight:{{ $onlyMulti ? 600 : 800 }};margin-left:4px;">All</a>
            <a href="{{ request()->fullUrlWithQuery(['multi' => 1]) }}" style="color:#047857;font-weight:{{ $onlyMulti ? 800 : 600 }};margin-left:8px;">Only those with more than one entry</a>
        </div>
        @endunless
    </div>
    <p class="management-muted" style="margin-top:4px;">
        @if($student)Every entry of this student not yet confirmed, one box per account. Tick the entries that were one bank payment (even if recorded on different days), set the bank date and reference, and confirm. Repeat for the next payment.
        @else Entries of the same student, recorded on the same day, into the same account, are put together as one probable payment. Untick anything that was a separate payment; it comes back on its own.@endif
    </p>
    <div style="display:flex;flex-direction:column;gap:12px;margin-top:14px;">
    @forelse($proposals as $p)
        <form method="post" action="{{ $routes['confirm'] }}" class="receipt-card" data-receipt>
            @csrf
            <input type="hidden" name="school_id" value="{{ $p->school_id }}">
            <div class="receipt-head">
                <div>
                    <strong style="font-size:15px;">{{ $p->student ?: 'Unknown student' }}</strong> <span class="management-muted">{{ $p->class }}</span>
                    <div class="management-muted" style="margin-top:2px;">@if($showSchool){!! $dot($p->school_id) !!}{{ $p->school }} · @endif{{ $p->book }} ·
                        recorded {{ $p->dates->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M Y'))->implode(', ') }}
                        @if(!$student && $searchSchoolId && $p->student_id) · <a href="{{ url()->current().'?'.http_build_query($baseQuery + ['student' => $p->student_id]) }}" style="color:#047857;font-weight:600;">all entries of this student</a>@endif</div>
                </div>
                <div style="text-align:right;"><span class="management-muted">Bank receipt total</span><div style="font-size:18px;font-weight:800;">TSh <span data-total>{{ $money($p->total) }}</span></div></div>
            </div>
            @foreach($p->hints as $hint)<p class="receipt-hint"><i class="fa-solid fa-circle-info"></i> {{ $hint }}</p>@endforeach
            <div class="management-scroll"><table class="management-table" style="margin-top:8px;">
                <thead><tr><th style="width:36px;"></th><th>Recorded</th><th>Particular</th><th>Quarter</th><th>Voucher</th><th>Entered</th><th class="number">Amount (TSh)</th></tr></thead>
                <tbody>
                @foreach($p->items as $it)
                    <tr>
                        <td><input type="checkbox" name="voucher_ids[]" value="{{ $it->id }}" data-amount="{{ $it->amount }}" data-date="{{ $it->date }}" @checked(!$student || $p->items->count() === 1) aria-label="Include {{ $it->particular }}"></td>
                        <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($it->date)->format('d M Y') }}</td>
                        <td>{{ $it->particular }}</td>
                        <td>{{ $it->quarter ? 'Q'.$it->quarter : '—' }}</td>
                        <td>{{ $it->number ?: '—' }}</td>
                        <td class="management-muted">{{ $it->entered ? \Carbon\Carbon::parse($it->entered)->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i') : '—' }}</td>
                        <td class="number">{{ $money($it->amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            <div class="receipt-actions">
                <label>Bank date<input type="date" name="receipt_date" value="{{ $p->date }}" required data-bank-date></label>
                <label>Bank reference<input type="text" name="reference" maxlength="120" placeholder="Slip or transaction no."></label>
                <label style="flex:1;min-width:180px;">Note<input type="text" name="note" maxlength="500" placeholder="Optional"></label>
                <button type="submit" class="management-primary" data-confirm>Confirm <span data-count>{{ $student && $p->items->count() > 1 ? 0 : $p->items->count() }}</span> entr(ies) as one receipt</button>
            </div>
        </form>
    @empty
        <p class="management-muted">{{ $student ? 'All of this student\'s entries are confirmed.' : 'Nothing left to check in this period'.($onlyMulti ? ' with more than one entry' : '').'.' }}</p>
    @endforelse
    </div>
</section>

<section class="management-panel">
    <h3>Confirmed receipts{{ $student ? ' · '.$student->name : ' · bank date in '.$periodLabel }}</h3>
    <p class="management-muted">Change a wrong bank date or reference with Edit. Undo splits the receipt back into its entries.</p>
    <div class="management-scroll" style="margin-top:12px;"><table class="management-table" style="min-width:880px;">
        <thead><tr><th>Bank date</th><th>Student</th>@if($showSchool)<th>School</th>@endif<th>Account</th><th>Entries</th><th>Reference</th><th class="number">Total (TSh)</th><th></th></tr></thead>
        <tbody>
        @forelse($confirmed as $g)
            <tr>
                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($g->receipt_date)->format('d M Y') }}</td>
                <td><strong>{{ $g->student }}</strong>@if($g->note)<div class="management-muted">{{ $g->note }}</div>@endif</td>
                @if($showSchool)<td style="white-space:nowrap;">{!! $dot($g->school_id) !!}{{ $g->school }}</td>@endif
                <td>{{ $g->book }}</td>
                <td>{{ $g->items->map(fn($it) => $it->particular.($it->quarter ? ' (Q'.$it->quarter.')' : '').' '.$money($it->amount))->implode(' · ') }}
                    @if($g->items->pluck('date')->unique()->contains(fn($d) => $d !== $g->receipt_date))<div class="management-muted">Recorded {{ $g->items->pluck('date')->unique()->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->implode(', ') }}</div>@endif</td>
                <td>{{ $g->reference ?: '—' }}</td>
                <td class="number"><strong>{{ $money($g->total) }}</strong></td>
                <td style="white-space:nowrap;">
                    <button type="button" class="management-chip-button" data-edit-toggle>Edit</button>
                    <form method="post" action="{{ $routes['undo']($g->id) }}" style="display:inline;" onsubmit="return confirm('Undo this confirmed receipt? Its entries go back to the list.');">@csrf<input type="hidden" name="school_id" value="{{ $g->school_id }}"><button type="submit" class="management-chip-button">Undo</button></form>
                </td>
            </tr>
            <tr class="receipt-edit-row" hidden>
                <td colspan="8">
                    <form method="post" action="{{ $routes['update']($g->id) }}" class="receipt-edit">
                        @csrf<input type="hidden" name="school_id" value="{{ $g->school_id }}">
                        <strong style="align-self:center;">Change this receipt:</strong>
                        <label>Bank date<input type="date" name="receipt_date" value="{{ $g->receipt_date }}" required></label>
                        <label>Bank reference<input type="text" name="reference" value="{{ $g->reference }}" maxlength="120"></label>
                        <label style="flex:1;min-width:180px;">Note<input type="text" name="note" value="{{ $g->note }}" maxlength="500"></label>
                        <button type="submit" class="management-primary">Save</button>
                        <button type="button" class="management-chip-button" data-edit-toggle>Cancel</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8">No confirmed receipts {{ $student ? 'for this student yet.' : 'with a bank date in this period yet.' }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <p class="management-muted" style="margin-top:10px;">Confirmed receipts appear as one line on their bank date in the <a href="{{ $ledgerUrl }}" style="color:#047857;font-weight:700;">Bank ledger</a>.</p>
</section>

<style>
    .receipt-card { border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; background:#fff; }
    .receipt-head { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start; }
    .receipt-hint { margin:8px 0 0; padding:6px 10px; border-radius:8px; background:#fffbeb; color:#92400e; font-size:13px; }
    .receipt-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:10px; }
    .receipt-actions label, .receipt-edit label { display:flex; flex-direction:column; gap:4px; font-size:12px; font-weight:600; color:#475569; }
    .receipt-actions input, .receipt-edit input { border:1px solid #cbd5e1; border-radius:7px; padding:7px 10px; font-size:14px; font-weight:400; color:#0f172a; }
    .receipt-edit { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; padding:12px; border:1px solid #e2e8f0; border-radius:10px; background:#f8fafc; }
    .receipt-edit-row td { background:#fff; }
    [data-confirm]:disabled { opacity:.4; cursor:not-allowed; }
</style>
@push('scripts')
<script>
(() => {
    const fmt = n => Math.round(n).toLocaleString('en-US');
    document.querySelectorAll('[data-receipt]').forEach(form => {
        const boxes = [...form.querySelectorAll('input[name="voucher_ids[]"]')];
        const dateBox = form.querySelector('[data-bank-date]');
        let dateTouched = false;
        dateBox.addEventListener('input', () => dateTouched = true);
        const update = () => {
            const on = boxes.filter(b => b.checked);
            form.querySelector('[data-total]').textContent = fmt(on.reduce((s, b) => s + parseFloat(b.dataset.amount || 0), 0));
            form.querySelector('[data-count]').textContent = on.length;
            form.querySelector('[data-confirm]').disabled = on.length === 0;
            // Until the date is changed by hand, suggest the latest recorded date of the ticked entries.
            if (!dateTouched && on.length) dateBox.value = on.map(b => b.dataset.date).sort().pop();
        };
        boxes.forEach(b => b.addEventListener('change', update));
        update();
    });
    // Edit opens the change form on its own full-width row under the receipt.
    document.querySelectorAll('[data-edit-toggle]').forEach(btn => btn.addEventListener('click', () => {
        const row = btn.closest('tr').classList.contains('receipt-edit-row') ? btn.closest('tr') : btn.closest('tr').nextElementSibling;
        row.hidden = !row.hidden;
    }));
})();
</script>
@endpush
