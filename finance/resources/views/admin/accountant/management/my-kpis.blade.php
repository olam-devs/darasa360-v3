@extends('layouts.accountant')
@section('title','My targets — Darasa Finance')
@section('page_title','My targets')
@section('content')
@include('admin.accountant.management.styles')
<div class="management-page">
    <h2>My targets & results</h2>
    <p class="management-muted">See your targets, recorded results and remaining shortfalls for a chosen period.</p>
    @include('admin.accountant.management.period')
    <div class="management-panel">
        @include('admin.accountant.management.kpi-table')
    </div>
    <p class="management-muted">If a target is not set or needs clarification, ask your main accountant. Collection results show receipts you entered; they do not measure all work you do or responsibility for every unpaid student balance.</p>
</div>
@endsection
