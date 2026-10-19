@extends('layouts.management')
@section('title', 'Campaign — School management')
@section('page_title', 'SMS campaigns · '.$campaign->title)
@section('content')
@include('admin.accountant.management.styles')
@php $by = $rows->countBy('status'); @endphp
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">
        <a href="{{ route('accountant.management.sms', array_filter(['school' => $selected?->id])) }}" style="color:#047857;">SMS campaigns</a> · {{ \Carbon\Carbon::parse($campaign->created_at)->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i') }}</p>
    <h2>{{ $campaign->title }}</h2>
    <p class="management-muted">From {{ $sender?->short }}{{ $sender?->gateway ? ' · '.$sender->gateway->sender_id : '' }} · {{ $campaign->audience }}</p>

    <div class="management-grid" style="margin-top:14px;">
        <div class="management-card"><p class="management-muted">People</p><div class="value">{{ number_format($campaign->recipients) }}</div><p class="management-muted">{{ $campaign->parts }} SMS each</p></div>
        <div class="management-card"><p class="management-muted">Sent</p><div class="value">{{ number_format(($by['sent'] ?? 0) + ($by['delivered'] ?? 0)) }}</div><p class="management-muted">{{ number_format($by['delivered'] ?? 0) }} confirmed delivered</p></div>
        <div class="management-card"><p class="management-muted">Failed</p><div class="value" @if($by['failed'] ?? 0) style="color:#b91c1c;" @endif>{{ number_format($by['failed'] ?? 0) }}</div><p class="management-muted">Wrong number, rejected or not delivered</p></div>
    </div>

    <section class="management-panel">
        <h3>Message</h3>
        <div style="margin-top:8px;max-width:520px;background:#f1f5f9;border-radius:14px;padding:12px 14px;white-space:pre-wrap;font-size:14px;">{{ $campaign->message }}</div>
    </section>

    <section class="management-panel">
        <h3>Recipients</h3>
        <div class="management-scroll" style="margin-top:8px;max-height:560px;overflow:auto;"><table class="management-table">
            <thead><tr><th>Name</th><th>Phone</th><th>Status</th><th>Details</th></tr></thead>
            <tbody>
            @foreach($rows as $r)
                <tr><td>{{ $r->name ?: '—' }}</td><td>+{{ $r->phone }}</td>
                    <td><span style="font-weight:700;color:{{ ['delivered' => '#047857', 'sent' => '#0f766e', 'failed' => '#b91c1c'][$r->status] ?? '#64748b' }};">{{ ucfirst($r->status) }}</span></td>
                    <td class="management-muted">{{ $r->description }}</td></tr>
            @endforeach
            </tbody>
        </table></div>
    </section>
</div>
@endsection
