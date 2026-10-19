@extends('layouts.management')
@section('title', 'SMS campaigns — School management')
@section('page_title', 'SMS campaigns')
@section('content')
@include('admin.accountant.management.styles')
@php
    $ready = $schools->filter(fn($s) => $s->gateway?->is_active);
    $defaultSender = old('send_school_id', $selected?->id && $ready->contains('id', $selected->id) ? $selected->id : $ready->first()?->id);
@endphp
<div class="management-page">
    <p class="management-muted" style="text-transform:uppercase;letter-spacing:1.5px;font-size:11px;font-weight:700;">SMS campaigns</p>
    <h2>Send a campaign</h2>
    <p class="management-muted">Send one message to many people: a contact list (for example potential customers), parents of your schools, or numbers you type.
        Write <code>{name}</code> to put each person's first name in the message. You see the full count and cost before anything is sent.</p>

    @if($ready->isEmpty())
        <div class="management-panel" style="border-color:#fcd34d;background:#fffbeb;"><strong>No school has an SMS account yet.</strong> Set one up in <a href="{{ route('accountant.management.sms.setup') }}" style="color:#047857;font-weight:700;">SMS setup</a> first.</div>
    @endif

    <form method="post" action="{{ route('accountant.management.sms.preview') }}" class="management-panel" id="campaign-form">
        @csrf
        @if($selected)<input type="hidden" name="school" value="{{ $selected->id }}">@endif
        <h3>1 · Message</h3>
        <div class="sms-form-grid" style="margin-top:10px;">
            <label class="sms-field">Campaign name (for your history)<input type="text" name="title" value="{{ old('title') }}" required maxlength="150" placeholder="e.g. January 2027 admissions"></label>
            <label class="sms-field">Send from (account and sender ID)<select name="send_school_id" required>
                @foreach($schools as $s)<option value="{{ $s->id }}" @selected($defaultSender == $s->id) @disabled(!$s->gateway?->is_active)>{{ $s->short }}{{ $s->gateway?->is_active ? ' · '.$s->gateway->sender_id : ' · no SMS account' }}</option>@endforeach
            </select></label>
        </div>
        <label class="sms-field" style="margin-top:12px;">Message<textarea name="message" rows="5" maxlength="918" required id="sms-message" placeholder="Habari {name}! Little Doves School inapokea wanafunzi wapya...">{{ old('message') }}</textarea></label>
        <p class="management-muted" style="margin-top:4px;"><span id="sms-chars">0</span> characters · <strong><span id="sms-parts">1</span> SMS</strong> per person <span id="sms-unicode" hidden>· contains special characters (70 per SMS)</span></p>

        <h3 style="margin-top:18px;">2 · Who receives it</h3>
        <div class="sms-audience">
            <div>
                <p class="sms-audience-title">Contact lists</p>
                @forelse($lists as $l)
                    <label class="sms-check"><input type="checkbox" name="lists[]" value="{{ $l->id }}" @checked(in_array($l->id, old('lists', [])))> {{ $l->name }} <span class="management-muted">({{ $l->contacts - (int)$l->opted_out }})</span></label>
                @empty
                    <p class="management-muted">No lists yet — add one below.</p>
                @endforelse
            </div>
            <div>
                <p class="sms-audience-title">Parents</p>
                @foreach($schools as $s)
                    <label class="sms-check"><input type="checkbox" name="parent_schools[]" value="{{ $s->id }}" data-parents="{{ $s->id }}" @checked(in_array($s->id, old('parent_schools', [])))> Parents · {{ $s->short }}</label>
                    <div class="sms-classes" data-classes-for="{{ $s->id }}" hidden>
                        <span class="management-muted">Only these classes (none ticked = all):</span>
                        @foreach($classes[$s->id] ?? [] as $c)<label class="sms-check small"><input type="checkbox" name="classes[{{ $s->id }}][]" value="{{ $c }}"> {{ $c }}</label>@endforeach
                    </div>
                @endforeach
                <label class="sms-field" style="margin-top:6px;max-width:240px;">Parent numbers<select name="parent_phone"><option value="phone_1">Main number only</option><option value="both" @selected(old('parent_phone') === 'both')>Both numbers</option></select></label>
            </div>
            <div>
                <p class="sms-audience-title">Type numbers</p>
                <textarea name="numbers" rows="5" class="sms-textarea" placeholder="One per line, optionally with a name:&#10;0752434818&#10;Asha Juma, 0713 000 000">{{ old('numbers') }}</textarea>
            </div>
        </div>
        <p class="management-muted" style="margin-top:8px;">Each number gets the message once, even if it is in several groups. Numbers marked "no more SMS" in any list are always left out.</p>
        <button type="submit" class="management-primary" style="margin-top:12px;" @disabled($ready->isEmpty())>Preview campaign</button>
    </form>

    <section class="management-panel" id="lists">
        <h3>Contact lists</h3>
        <p class="management-muted">For people who are not in the school system, such as potential customers. Add numbers by pasting them or uploading a CSV file (name, phone).</p>
        <div class="management-scroll" style="margin-top:10px;"><table class="management-table">
            <thead><tr><th>List</th><th class="number">Numbers</th><th class="number">No more SMS</th><th>Created</th><th></th></tr></thead>
            <tbody>
            @forelse($lists as $l)
                <tr><td><strong>{{ $l->name }}</strong>@if($l->notes)<div class="management-muted">{{ $l->notes }}</div>@endif</td><td class="number">{{ number_format($l->contacts) }}</td><td class="number">{{ (int)$l->opted_out }}</td>
                    <td class="management-muted">{{ \Carbon\Carbon::parse($l->created_at)->format('d M Y') }}</td>
                    <td><a href="{{ request()->fullUrlWithQuery(['list' => $l->id]) }}#lists" style="color:#047857;font-weight:700;">Open</a></td></tr>
            @empty
                <tr><td colspan="5">No contact lists yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>

        <form method="post" action="{{ route('accountant.management.sms.lists.store') }}" enctype="multipart/form-data" style="margin-top:16px;">
            @csrf
            @if($selected)<input type="hidden" name="school" value="{{ $selected->id }}">@endif
            <div class="sms-form-grid">
                @if($openList)
                    <input type="hidden" name="list_id" value="{{ $openList->id }}">
                    <p style="margin:0;font-weight:700;">Add numbers to "{{ $openList->name }}"</p>
                @else
                    <label class="sms-field">New list name<input type="text" name="name" required maxlength="120" placeholder="e.g. Potential customers Kigamboni"></label>
                    <label class="sms-field">Note (optional)<input type="text" name="notes" maxlength="500" placeholder="Where the numbers came from"></label>
                @endif
                <label class="sms-field">CSV file (optional)<input type="file" name="file" accept=".csv,.txt"></label>
            </div>
            <label class="sms-field" style="margin-top:10px;">Numbers<textarea name="numbers" rows="4" class="sms-textarea" placeholder="One per line, optionally with a name"></textarea></label>
            <button type="submit" class="management-primary" style="margin-top:10px;">{{ $openList ? 'Add numbers' : 'Create list' }}</button>
            @if($openList)<a href="{{ request()->fullUrlWithoutQuery(['list']) }}#lists" style="margin-left:10px;color:#047857;font-weight:600;">New list instead</a>@endif
        </form>

        @if($openList)
            <h3 style="margin-top:20px;">{{ $openList->name }} · {{ $openContacts->count() }} number(s)</h3>
            <div class="management-scroll" style="margin-top:8px;max-height:420px;overflow:auto;"><table class="management-table">
                <thead><tr><th>Name</th><th>Phone</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($openContacts as $c)
                    <tr><td>{{ $c->name ?: '—' }}</td><td>+{{ $c->phone }}</td><td>@if($c->opted_out_at)<span style="color:#b45309;font-weight:700;">No more SMS</span>@else Active @endif</td>
                        <td><form method="post" action="{{ route('accountant.management.sms.contacts.toggle', $c->id) }}">@csrf<button type="submit" class="management-chip-button">{{ $c->opted_out_at ? 'Allow SMS again' : 'No more SMS' }}</button></form></td></tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>

    <section class="management-panel">
        <h3>Campaign history</h3>
        <div class="management-scroll" style="margin-top:10px;"><table class="management-table" style="min-width:820px;">
            <thead><tr><th>Date</th><th>Campaign</th><th>From</th><th>Audience</th><th class="number">Sent</th><th class="number">Failed</th><th class="number">SMS used</th><th></th></tr></thead>
            <tbody>
            @forelse($campaigns as $c)
                <tr><td class="management-muted" style="white-space:nowrap;">{{ \Carbon\Carbon::parse($c->created_at)->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i') }}</td>
                    <td><strong>{{ $c->title }}</strong><div class="management-muted">{{ \Illuminate\Support\Str::limit($c->message, 70) }}</div></td>
                    <td>{{ $schools->firstWhere('id', $c->school_id)?->short }}</td><td class="management-muted">{{ $c->audience }}</td>
                    <td class="number">{{ number_format($c->sent) }}</td><td class="number" @if($c->failed) style="color:#b91c1c;font-weight:700;" @endif>{{ number_format($c->failed) }}</td>
                    <td class="number">{{ number_format($c->sent * $c->parts) }}</td>
                    <td><a href="{{ route('accountant.management.sms.campaign', array_filter(['campaignId' => $c->id, 'school' => $selected?->id])) }}" style="color:#047857;font-weight:700;">Details</a></td></tr>
            @empty
                <tr><td colspan="8">No campaigns sent yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</div>
<style>
    .sms-form-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; align-items:end; }
    .sms-field { display:flex; flex-direction:column; gap:4px; font-size:12px; font-weight:600; color:#475569; }
    .sms-field input, .sms-field textarea, .sms-field select, .sms-textarea { border:1px solid #cbd5e1; border-radius:7px; padding:8px 10px; font-size:14px; font-weight:400; color:#0f172a; background:#fff; width:100%; box-sizing:border-box; font-family:inherit; }
    .sms-audience { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px; margin-top:10px; }
    .sms-audience-title { font-weight:700; margin:0 0 6px; }
    .sms-check { display:flex; gap:6px; align-items:center; margin:4px 0; font-size:14px; }
    .sms-check.small { display:inline-flex; font-size:12px; margin:2px 8px 2px 0; }
    .sms-classes { margin:2px 0 8px 22px; }
</style>
@endsection
@push('scripts')
<script>
(() => {
    const box = document.getElementById('sms-message');
    function count() {
        const t = box.value.replace(/\{name\}|\{jina\}/g, 'Abcdefgh');
        const uni = /[^\x00-\x7F]/.test(t), len = [...t].length;
        const parts = len <= (uni ? 70 : 160) ? 1 : Math.ceil(len / (uni ? 67 : 153));
        document.getElementById('sms-chars').textContent = len;
        document.getElementById('sms-parts').textContent = parts;
        document.getElementById('sms-unicode').hidden = !uni;
    }
    box.addEventListener('input', count); count();
    document.querySelectorAll('[data-parents]').forEach(cb => {
        const show = () => document.querySelector(`[data-classes-for="${cb.dataset.parents}"]`).hidden = !cb.checked;
        cb.addEventListener('change', show); show();
    });
})();
</script>
@endpush
