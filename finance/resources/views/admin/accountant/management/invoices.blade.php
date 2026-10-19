@extends('layouts.management')
@section('title', 'Invoices — School management')
@section('page_title', 'Invoices · '.\App\Http\Controllers\SchoolManagementController::shortName($school->name))
@section('content')
@include('admin.accountant.management.styles')
@php $download = route('accountant.management.invoices.download', $school->id); @endphp
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">Invoices · fee statements</p>
    <h2>{{ $school->name }}</h2>
    <p class="management-muted">Invoices are built from each student's assigned fees and payments. Choose the heading and format, then download for the whole school, one class or one student.
        @if(count($nav['schools']) > 1) Other schools:
            @foreach($nav['schools'] as $s)@if($s['id'] !== $school->id)<a href="{{ route('accountant.management.invoices', ['school' => $s['id']]) }}" style="color:#1d4ed8;font-weight:600;margin-left:6px;">{{ $s['short'] }}</a>@endif @endforeach
        @endif
    </p>

    <section class="management-panel" style="margin-top:18px;">
        <h3>Invoice settings</h3>
        <div class="management-target">
            <label>Heading on every page<input type="text" id="inv-heading" value="INVOICE:" maxlength="80" style="text-transform:uppercase;"></label>
            <label>Format<select id="inv-format"><option value="parent">Parent copy</option><option value="accountant">Accountant copy (full detail)</option></select></label>
        </div>
    </section>

    <div class="management-pair">
        <section class="management-panel">
            <h3>Whole school</h3>
            <p class="management-muted" style="margin:6px 0 14px;">One PDF with an invoice for every active student ({{ count($students) }}).</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;"><a class="management-primary" data-invoice-link data-scope="all" href="#" style="display:inline-block;"><i class="fa-solid fa-download"></i> Download all invoices</a>
            <a class="management-chip-button" data-bundle-link href="{{ route('accountant.management.invoices.bundle', ['kind' => 'all', 'mode' => 'zip', 'school' => $school->id]) }}" style="display:inline-block;padding:10px 14px;"><i class="fa-solid fa-file-zipper"></i> Separate PDF per student (ZIP)</a></div>
        </section>
        <section class="management-panel">
            <h3>One class</h3>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;align-items:center;">
                <select id="inv-class" style="border:1px solid #cbd5e1;border-radius:7px;padding:9px 12px;min-width:200px;">
                    @forelse($classes as $c)<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>@empty<option value="">No classes set up</option>@endforelse
                </select>
                <a class="management-primary" data-invoice-link data-scope="class" href="#" style="display:inline-block;"><i class="fa-solid fa-download"></i> Download class</a>
            </div>
        </section>
    </div>

    <section class="management-panel">
        <h3>One student</h3>
        <input type="search" id="inv-search" placeholder="Search by name, number or class" style="margin:12px 0;width:100%;max-width:420px;border:1px solid #cbd5e1;border-radius:7px;padding:9px 12px;">
        <div class="management-scroll"><table class="management-table" id="inv-students" style="min-width:640px;">
            <thead><tr><th>Student</th><th>Class</th><th class="number">Unpaid (TSh)</th><th>Invoice</th></tr></thead>
            <tbody>
            @forelse($students as $st)
                <tr data-search="{{ strtolower($st->name.' '.$st->student_reg_no.' '.$st->class) }}">
                    <td><strong>{{ $st->name }}</strong><div class="management-muted">{{ $st->student_reg_no }}</div></td>
                    <td>{{ $st->class ?: '—' }}</td>
                    <td class="number">{{ number_format($balances[$st->id] ?? 0, 0) }}</td>
                    <td style="white-space:nowrap;">
                        <a href="#" data-invoice-link data-scope="student" data-student="{{ $st->id }}" data-preview="1" target="_blank" rel="noopener" style="color:#1d4ed8;font-weight:600;margin-right:12px;">View</a>
                        <a href="#" data-invoice-link data-scope="student" data-student="{{ $st->id }}" style="color:#1d4ed8;font-weight:600;"><i class="fa-solid fa-download"></i> PDF</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No active students in this school.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <p class="management-muted" style="margin-top:12px;">To add or change the fees on an invoice, use Fee entry under "Work inside this school's accounts" on the overview.</p>
    </section>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const base = @json($download);
    document.querySelectorAll('[data-invoice-link]').forEach(a => a.addEventListener('click', e => {
        const p = new URLSearchParams({ scope: a.dataset.scope, format: document.getElementById('inv-format').value,
            heading: (document.getElementById('inv-heading').value.trim() || 'INVOICE:').toUpperCase() });
        if (a.dataset.scope === 'class') { const c = document.getElementById('inv-class').value; if (!c) { e.preventDefault(); return; } p.set('class', c); }
        if (a.dataset.student) p.set('student', a.dataset.student);
        if (a.dataset.preview) p.set('preview', '1');
        a.href = base + '?' + p.toString();
    }));
    document.querySelectorAll('[data-bundle-link]').forEach(a => a.addEventListener('click', () => {
        const url = new URL(a.href);
        url.searchParams.set('format', document.getElementById('inv-format').value);
        url.searchParams.set('heading', (document.getElementById('inv-heading').value.trim() || 'INVOICE:').toUpperCase());
        a.href = url.toString();
    }));
    const search = document.getElementById('inv-search');
    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        document.querySelectorAll('#inv-students tbody tr[data-search]').forEach(r => r.hidden = q && !r.dataset.search.includes(q));
    });
})();
</script>
@endpush
