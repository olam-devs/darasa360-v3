@extends('layouts.management')
@php
    $isOverdue = $kind === 'overdue';
    $title = $isOverdue ? 'Overdue fees' : 'Advance credit';
    $route = $isOverdue ? 'accountant.management.overdue' : 'accountant.management.advance';
    $money = fn($v) => number_format($v, 0);
    $schoolDots = ['#2a78d6', '#eb6834', '#1baf7a'];
    $dotOf = fn($id) => $schoolDots[array_search($id, array_column($nav['schools'], 'id')) % 3] ?? '#94a3b8';
    $amountCol = $isOverdue ? 'overdue' : 'advance_balance';
@endphp
@section('title', $title.' — School management')
@section('page_title', $title.' · '.($selected ? \App\Http\Controllers\SchoolManagementController::shortName($selected->name) : 'All schools'))
@section('content')
@include('admin.accountant.management.styles')
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">{{ $title }} · as of {{ now('Africa/Dar_es_Salaam')->format('d M Y') }}</p>
    <h2>{{ $selected ? $selected->name : 'All schools' }}</h2>
    <p class="management-muted">
        @if($isOverdue)Students with fees whose payment deadline has passed and are not fully paid. Use the invoices to follow up with parents.
        @else Students holding advance credit: money paid in advance that has not yet been applied to fees.@endif
        Show: <a href="{{ route($route) }}" style="color:#047857;font-weight:{{ $selected ? 600 : 800 }};margin-left:4px;">All schools</a>
        @foreach($nav['schools'] as $s)<a href="{{ route($route, ['school' => $s['id']]) }}" style="color:#047857;font-weight:{{ $selected?->id === $s['id'] ? 800 : 600 }};margin-left:8px;">{{ $s['short'] }}</a>@endforeach
    </p>

    <div class="management-grid" style="margin-top:18px;">
        <div class="management-card"><p class="management-muted">Students</p><div class="value">{{ $rows->count() }}</div><p class="management-muted">@unless($selected){{ $rows->groupBy('school')->map->count()->map(fn($n, $s) => "$s $n")->implode(' · ') }}@endunless</p></div>
        <div class="management-card"><p class="management-muted">{{ $isOverdue ? 'Total overdue' : 'Total advance credit' }}</p><div class="value">TSh {{ $money($rows->sum($amountCol)) }}</div><p class="management-muted">{{ $isOverdue ? 'Past the deadline, still unpaid' : 'Held for future fees' }}</p></div>
        <div class="management-card"><p class="management-muted">Their total unpaid fees</p><div class="value">TSh {{ $money($rows->sum('unpaid')) }}</div><p class="management-muted">All their fees, due or not yet due</p></div>
    </div>

    <form method="get" action="{{ route('accountant.management.invoices.bundle') }}" class="management-panel" id="list-form">
        <input type="hidden" name="kind" value="{{ $kind }}">
        @if($selected)<input type="hidden" name="school" value="{{ $selected->id }}">@endif
        <h3>Invoices</h3>
        <div class="management-bulk" style="margin-top:12px;">
            <div class="management-bulk-total"><span class="management-muted">Selected</span><strong><span data-sel-count>0</span> student(s) · TSh <span data-sel-total>0</span></strong></div>
            <label>Heading<input type="text" name="heading" value="INVOICE:" maxlength="80" style="border:1px solid #cbd5e1;border-radius:7px;padding:7px 10px;text-transform:uppercase;width:130px;"></label>
            <label>Format<select name="format"><option value="parent">Parent copy</option><option value="accountant">Accountant copy</option></select></label>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <span class="management-muted" style="font-size:12px;font-weight:600;">Selected students</span>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="submit" name="mode" value="pdf" class="management-primary" data-needs-selection disabled>One PDF</button>
                    <button type="submit" name="mode" value="zip" class="management-chip-button" data-needs-selection disabled>Separate PDFs (ZIP)</button>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <span class="management-muted" style="font-size:12px;font-weight:600;">Everyone in this list ({{ $rows->count() }})</span>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="submit" name="mode" value="pdf" formaction="{{ route('accountant.management.invoices.bundle') }}" data-all class="management-primary" @disabled($rows->isEmpty())>Download all · one PDF</button>
                    <button type="submit" name="mode" value="zip" data-all class="management-chip-button" @disabled($rows->isEmpty())>Download all · separate PDFs</button>
                </div>
            </div>
        </div>
        <p class="management-muted" style="margin:-4px 0 12px;">"Separate PDFs" downloads a ZIP file with one invoice per student, each file named after the student.@unless($selected) With all schools, "one PDF" gives one PDF per school (zipped together), because each school has its own invoice header.@endunless</p>

        <div class="management-scroll"><table class="management-table" id="student-table" style="min-width:900px;">
            <thead><tr><th style="width:36px;"><input type="checkbox" data-select-all aria-label="Select all"></th><th>Student</th>@unless($selected)<th>School</th>@endunless<th>Class</th><th>Parent</th>
                <th class="number">{{ $isOverdue ? 'Overdue (TSh)' : 'Advance (TSh)' }}</th>@if($isOverdue)<th>Oldest deadline</th>@endif<th class="number">Total unpaid (TSh)</th><th>Invoice</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                @php $dl = route('accountant.management.invoices.download', $r->school_id); @endphp
                <tr>
                    <td><input type="checkbox" name="students[]" value="{{ $r->key }}" data-select-item data-amount="{{ (float)$r->{$amountCol} }}" aria-label="Select {{ $r->name }}"></td>
                    <td><strong>{{ $r->name }}</strong><div class="management-muted">{{ $r->student_reg_no }}</div></td>
                    @unless($selected)<td style="white-space:nowrap;"><span style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;background:{{ $dotOf($r->school_id) }};"></span>{{ $r->school }}</td>@endunless
                    <td>{{ $r->class ?: '—' }}</td>
                    <td>{{ $r->parent_name ?: '—' }}@if($r->parent_phone_1)<div class="management-muted">{{ $r->parent_phone_1 }}</div>@endif</td>
                    <td class="number"><strong @if($isOverdue) style="color:#b91c1c;" @endif>{{ $money($r->{$amountCol}) }}</strong></td>
                    @if($isOverdue)<td>{{ \Carbon\Carbon::parse($r->oldest_deadline)->format('d M Y') }}<div class="management-muted">{{ (int) \Carbon\Carbon::parse($r->oldest_deadline)->startOfDay()->diffInDays(now('Africa/Dar_es_Salaam')->startOfDay()) }} days ago</div></td>@endif
                    <td class="number">{{ $money($r->unpaid) }}</td>
                    <td style="white-space:nowrap;">
                        <a href="{{ $dl }}?scope=student&student={{ explode('_', $r->key)[1] }}&format=parent&heading=INVOICE%3A&preview=1" target="_blank" rel="noopener" data-invoice-link style="color:#047857;font-weight:700;margin-right:10px;">View</a>
                        <a href="{{ $dl }}?scope=student&student={{ explode('_', $r->key)[1] }}&format=parent&heading=INVOICE%3A" data-invoice-link style="color:#047857;font-weight:700;"><i class="fa-solid fa-download"></i> PDF</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">{{ $isOverdue ? 'No overdue fees. Every fee past its deadline is paid.' : 'No student holds advance credit.' }}</td></tr>
            @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot><tr><td></td><td @unless($selected) colspan="2" @endunless><strong>Total</strong></td><td colspan="2"></td><td class="number"><strong>{{ $money($rows->sum($amountCol)) }}</strong></td>@if($isOverdue)<td></td>@endif<td class="number"><strong>{{ $money($rows->sum('unpaid')) }}</strong></td><td></td></tr></tfoot>
            @endif
        </table></div>
    </form>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const form = document.getElementById('list-form');
    const fmt = n => Math.round(n).toLocaleString('en-US');
    const items = [...form.querySelectorAll('[data-select-item]')], all = form.querySelector('[data-select-all]');
    function update() {
        const picked = items.filter(i => i.checked);
        form.querySelector('[data-sel-count]').textContent = picked.length;
        form.querySelector('[data-sel-total]').textContent = fmt(picked.reduce((s, i) => s + parseFloat(i.dataset.amount || 0), 0));
        form.querySelectorAll('[data-needs-selection]').forEach(b => b.disabled = picked.length === 0);
        if (all) { all.checked = items.length > 0 && picked.length === items.length; all.indeterminate = picked.length > 0 && picked.length < items.length; }
    }
    form.addEventListener('change', e => { if (e.target === all) items.forEach(i => i.checked = all.checked); update(); });
    // "Download all" ignores the ticks; "Selected students" sends only the ticked ones.
    form.addEventListener('submit', e => {
        const everyone = e.submitter && e.submitter.hasAttribute('data-all');
        items.forEach(i => i.disabled = everyone);
        setTimeout(() => items.forEach(i => i.disabled = false), 0);
    });
    // The per-student links follow the chosen heading and format.
    form.querySelectorAll('[data-invoice-link]').forEach(a => a.addEventListener('click', () => {
        const url = new URL(a.href);
        url.searchParams.set('format', form.querySelector('[name=format]').value);
        url.searchParams.set('heading', (form.querySelector('[name=heading]').value.trim() || 'INVOICE:').toUpperCase());
        a.href = url.toString();
    }));
    update();
})();
</script>
@endpush
