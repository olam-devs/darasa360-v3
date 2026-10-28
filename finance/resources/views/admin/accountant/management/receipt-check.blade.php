@extends('layouts.management')
@php
    $palette = ['#2a78d6', '#eb6834', '#1baf7a'];
    $schoolDots = collect($nav['schools'])->values()->mapWithKeys(fn($s, $i) => [$s['id'] => $palette[$i % 3]])->all();
@endphp
@section('title', 'Receipt check — School management')
@section('page_title', 'Receipt check · '.($selected ? \App\Http\Controllers\SchoolManagementController::shortName($selected->name) : 'All schools'))
@section('content')
@include('admin.accountant.management.styles')
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">Receipt check · {{ $periodLabel }}</p>
    <h2>{{ $selected ? $selected->name : 'All schools' }}</h2>
    <p class="management-muted">
        Check fee entries against the physical receipts and bank slips: confirm which entries were one payment into the bank, with the real bank date and reference.
        Confirming changes nothing in the school's books; it records how the money reached the bank so the
        <a href="{{ route('accountant.management.bank-ledger', array_filter(['school' => $selected?->id])) }}" style="color:#047857;font-weight:700;">Bank ledger</a> matches the statement.
        The school accountants can do the same from their own account (Finance → Receipt check).
    </p>
    @include('admin.accountant.management.period')
    @include('admin.accountant.management.partials.receipt-body', [
        'showSchool' => !$selected, 'searchSchoolId' => $selected?->id,
        'routes' => ['confirm' => route('accountant.management.receipts.confirm'),
            'update' => fn($id) => route('accountant.management.receipts.update', $id), 'undo' => fn($id) => route('accountant.management.receipts.undo', $id)],
        'ledgerUrl' => route('accountant.management.bank-ledger', array_filter(['school' => $selected?->id])),
    ])
</div>
@endsection
