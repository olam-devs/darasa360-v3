@extends('layouts.management')
@section('title', 'Check campaign — School management')
@section('page_title', 'SMS campaigns · check before sending')
@section('content')
@include('admin.accountant.management.styles')
@php
    $total = count($recipients) * $parts;
    $credits = $balance['ok'] ? (int) preg_replace('/[^0-9]/', '', explode('.', (string) $balance['credits'])[0]) : null;
    $short = fn($n) => \App\Http\Controllers\SchoolManagementController::shortName($n);
@endphp
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">Check before sending</p>
    <h2>{{ $v['title'] }}</h2>

    <div class="management-grid" style="margin-top:14px;">
        <div class="management-card"><p class="management-muted">People</p><div class="value">{{ number_format(count($recipients)) }}</div><p class="management-muted">{{ $audience }}</p></div>
        <div class="management-card"><p class="management-muted">SMS needed</p><div class="value">{{ number_format($total) }}</div><p class="management-muted">{{ $parts }} SMS per person</p></div>
        <div class="management-card"><p class="management-muted">Balance · {{ $short($school->name) }} account</p><div class="value">{{ $balance['ok'] ? $balance['credits'] : '—' }}</div>
            <p class="management-muted" @if(!$balance['ok'] || ($credits !== null && $credits < $total)) style="color:#b91c1c;font-weight:700;" @endif>
                @if(!$balance['ok']){{ $balance['error'] }}@elseif($credits !== null && $credits < $total)Not enough balance for this campaign @else Sender ID: {{ $gateway->sender_id }}@endif</p></div>
    </div>

    <section class="management-panel">
        <h3>Message as received</h3>
        <div style="margin-top:10px;max-width:420px;background:#f1f5f9;border-radius:14px;padding:12px 14px;white-space:pre-wrap;font-size:14px;">{{ $example }}</div>
        <p class="management-muted" style="margin-top:6px;">From <strong>{{ $gateway->sender_id }}</strong> · example for {{ $firstName ?: 'a number without a name' }}@if(str_contains($v['message'], '{name}') || str_contains($v['message'], '{jina}')) · people without a name get "Mzazi"@endif</p>
    </section>

    <section class="management-panel">
        <h3>First {{ count($sample) }} of {{ number_format(count($recipients)) }} numbers</h3>
        <div class="management-scroll" style="margin-top:8px;"><table class="management-table">
            <thead><tr><th>Name</th><th>Phone</th></tr></thead>
            <tbody>@foreach($sample as $phone => $name)<tr><td>{{ $name ?: '—' }}</td><td>+{{ $phone }}</td></tr>@endforeach</tbody>
        </table></div>
    </section>

    <form method="post" action="{{ route('accountant.management.sms.send') }}" class="management-panel" id="send-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @if($selected)<input type="hidden" name="school" value="{{ $selected->id }}">@endif
        @foreach(['send_school_id', 'title', 'message', 'parent_phone', 'numbers'] as $f)@isset($v[$f])<input type="hidden" name="{{ $f }}" value="{{ $v[$f] }}">@endisset @endforeach
        @foreach($v['lists'] ?? [] as $x)<input type="hidden" name="lists[]" value="{{ $x }}">@endforeach
        @foreach($v['parent_schools'] ?? [] as $x)<input type="hidden" name="parent_schools[]" value="{{ $x }}">@endforeach
        @foreach($v['classes'] ?? [] as $sid => $cs)@foreach((array)$cs as $c)<input type="hidden" name="classes[{{ $sid }}][]" value="{{ $c }}">@endforeach @endforeach
        <p style="margin:0 0 12px;">This sends <strong>{{ number_format(count($recipients)) }}</strong> message(s) now ({{ number_format($total) }} SMS from the {{ $short($school->name) }} account). It cannot be undone.</p>
        <button type="submit" class="management-primary" id="send-btn" @disabled(!$balance['ok'] || ($credits !== null && $credits < $total))>Send to {{ number_format(count($recipients)) }} people</button>
        <a href="javascript:history.back()" style="margin-left:12px;color:#047857;font-weight:600;">Go back and change</a>
    </form>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('send-form').addEventListener('submit', e => {
    const b = document.getElementById('send-btn');
    if (b.dataset.sending) { e.preventDefault(); return; }
    if (!confirm('Send this campaign now?')) { e.preventDefault(); return; }
    b.dataset.sending = '1'; b.textContent = 'Sending… please wait';
});
</script>
@endpush
