@extends('layouts.management')
@php
    $palette = ['#2a78d6', '#eb6834', '#1baf7a'];
    $schoolDots = collect($nav['schools'])->values()->mapWithKeys(fn($s, $i) => [$s['short'] => $palette[$i % 3]])->all();
@endphp
@section('title', 'Bank ledger — School management')
@section('page_title', 'Bank ledger · '.($selected ? \App\Http\Controllers\SchoolManagementController::shortName($selected->name) : 'All schools'))
@section('content')
@include('admin.accountant.management.styles')
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">Bank ledger · {{ $periodLabel }}</p>
    <h2>{{ $choices[$account]['label'] ?? 'No account' }}</h2>
    <p class="management-muted">Every movement in one account, in date order with a running balance, to tick off against the bank statement.</p>
    @include('admin.accountant.management.period')
    @include('admin.accountant.management.partials.ledger-body', ['receiptsUrl' => route('accountant.management.receipts', array_filter(['school' => $selected?->id]))])
</div>
@endsection
