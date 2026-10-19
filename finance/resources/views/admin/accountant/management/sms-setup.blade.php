@extends('layouts.management')
@section('title', 'SMS setup — School management')
@section('page_title', 'SMS setup')
@section('content')
@include('admin.accountant.management.styles')
@php $first = $schools->first(fn($s) => $s->gateway)?->gateway; @endphp
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">SMS setup</p>
    <h2>Each school's SMS account</h2>
    <p class="management-muted">A school with its own SMS account (Onfon) sends every message — fee reminders from the accountants and your campaigns — through that account and its sender ID,
        and pays from that account's balance instead of Darasa SMS credits. Schools without one keep using Darasa SMS credits.</p>

    <section class="management-panel">
        <h3>Accounts</h3>
        <div class="management-scroll" style="margin-top:12px;"><table class="management-table">
            <thead><tr><th>School</th><th>SMS account</th><th>Sender ID</th><th>Status</th><th>Last changed</th><th></th></tr></thead>
            <tbody>
            @foreach($schools as $s)
                <tr>
                    <td><strong>{{ $s->short }}</strong></td>
                    <td>@if($s->gateway)Onfon · client <strong>{{ $s->gateway->client_id }}</strong>@else<span class="management-muted">Darasa SMS credits</span>@endif</td>
                    <td>{{ $s->gateway->sender_id ?? '—' }}</td>
                    <td>@if($s->gateway)@if($s->gateway->is_active)<span style="color:#047857;font-weight:700;">On</span>@else<span style="color:#b45309;font-weight:700;">Off</span> <span class="management-muted">(uses Darasa credits)</span>@endif @else — @endif</td>
                    <td class="management-muted">{{ $s->gateway ? \Carbon\Carbon::parse($s->gateway->updated_at)->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i') : '—' }}</td>
                    <td>@if($s->gateway?->is_active)<form method="post" action="{{ route('accountant.management.sms.setup.test', $s->id) }}">@csrf<button class="management-chip-button" type="submit">Check connection</button></form>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </section>

    <form method="post" action="{{ route('accountant.management.sms.setup.save') }}" class="management-panel" autocomplete="off">
        @csrf
        <h3>Set the SMS account</h3>
        <p class="management-muted" style="margin-bottom:12px;">Tick the schools that use this account. The API key is stored encrypted and never shown again; leave it empty to keep the saved key.</p>
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:14px;">
            @foreach($schools as $s)
                <label style="display:flex;gap:6px;align-items:center;font-weight:600;"><input type="checkbox" name="schools[]" value="{{ $s->id }}" @checked(in_array($s->id, old('schools', $schools->pluck('id')->all())))> {{ $s->short }}</label>
            @endforeach
        </div>
        <div class="sms-form-grid">
            <label>Provider<input type="text" value="Onfon Media" disabled></label>
            <label>Client ID<input type="text" name="client_id" value="{{ old('client_id', $first->client_id ?? '') }}" required maxlength="120"></label>
            <label>API key<input type="password" name="api_key" placeholder="{{ $first ? 'Saved — leave empty to keep' : 'Paste the API key' }}" maxlength="255" autocomplete="new-password"></label>
            <label>Sender ID<input type="text" name="sender_id" value="{{ old('sender_id', $first->sender_id ?? '') }}" required maxlength="11" placeholder="Approved by Onfon, e.g. LITTLEDOVES"></label>
        </div>
        <label style="display:flex;gap:6px;align-items:center;margin-top:12px;font-weight:600;"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $first->is_active ?? true))> Send through this account</label>
        <button class="management-primary" type="submit" style="margin-top:14px;">Save SMS account</button>
    </form>

    <section class="management-panel">
        <h3>Delivery reports</h3>
        <p class="management-muted">To see whether each message was delivered, ask Onfon to turn on "Http Web push DLR" and send reports to the delivery address Darasa support gives you
            (it contains a secret token, so it is not shown here). Without it, messages show as "sent" but never "delivered".</p>
    </section>
</div>
<style>
    .sms-form-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; }
    .sms-form-grid label, .sms-field { display:flex; flex-direction:column; gap:4px; font-size:12px; font-weight:600; color:#475569; }
    .sms-form-grid input, .sms-field input, .sms-field textarea, .sms-field select { border:1px solid #cbd5e1; border-radius:7px; padding:8px 10px; font-size:14px; font-weight:400; color:#0f172a; background:#fff; }
</style>
@endsection
