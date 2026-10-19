@extends('layouts.management')
@section('title', 'Staff — School management')
@section('page_title', 'Staff · '.($selected ? \App\Http\Controllers\SchoolManagementController::shortName($selected->name) : 'All schools'))
@section('content')
@include('admin.accountant.management.styles')
@php
    $money = fn($v) => number_format($v, 0);
    $short = fn($name) => \App\Http\Controllers\SchoolManagementController::shortName($name);
    $schoolDots = ['#2a78d6', '#eb6834', '#1baf7a'];
    $dotOf = fn($id) => $schoolDots[array_search($id, array_column($nav['schools'], 'id')) % 3] ?? '#94a3b8';
@endphp
<div class="management-page">
    <p class="management-muted"><a href="{{ route('accountant.management.payroll', array_filter(['school' => $selected?->id])) }}" style="color:#047857;font-weight:600;">← Back to payroll</a></p>
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;margin-top:6px;">Staff</p>
    <h2>{{ $selected ? $selected->name : 'All schools' }}</h2>
    <p class="management-muted">{{ $staff->count() }} active staff · monthly salaries TSh {{ $money($staff->sum('monthly_salary')) }} (gross).
        Show: <a href="{{ route('accountant.management.staff') }}" style="color:#047857;font-weight:{{ $selected ? 600 : 800 }};margin-left:4px;">All schools</a>
        @foreach($nav['schools'] as $s)<a href="{{ route('accountant.management.staff', ['school' => $s['id']]) }}" style="color:#047857;font-weight:{{ $selected?->id === $s['id'] ? 800 : 600 }};margin-left:8px;">{{ $s['short'] }}</a>@endforeach
    </p>
    <div style="margin-top:18px;">
        @include('admin.accountant.management.staff-section')
    </div>
</div>
@endsection
