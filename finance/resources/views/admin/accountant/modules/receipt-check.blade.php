@extends('layouts.accountant')
@section('title', 'Receipt check — Darasa Finance')
@section('page_title', 'Receipt check')
@section('content')
@include('admin.accountant.management.styles')
@include('components.flash-toast')
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">Receipt check · {{ $periodLabel }}</p>
    <h2>{{ $school->name }}</h2>
    <p class="management-muted">
        Check fee entries against the physical receipts and bank slips. Where one payment was entered as several particulars, tick them and confirm them as one receipt;
        where the date is wrong, set the real bank date and reference. Nothing in the fee records changes: this records how the money reached the bank, so the
        <a href="{{ route('accountant.bank-ledger') }}" style="color:#047857;font-weight:700;">Bank ledger</a> matches the bank statement.
    </p>
    @include('admin.accountant.management.period')
    @include('admin.accountant.management.partials.receipt-body', [
        'showSchool' => false, 'searchSchoolId' => $school->id, 'schoolDots' => [],
        'routes' => ['confirm' => route('accountant.receipt-check.confirm'),
            'update' => fn($id) => route('accountant.receipt-check.update', $id), 'undo' => fn($id) => route('accountant.receipt-check.undo', $id)],
        'ledgerUrl' => route('accountant.bank-ledger'),
    ])
</div>
@endsection
