{{--
    Success / failure popup for the result of a form submission.
    Reads session('success'), session('error'), the Breeze profile statuses and validation errors (any bag).
    Self-contained styles so it works in every layout.
--}}
@php
    $statusMessages = [
        'profile-updated' => 'Your profile was saved.',
        'password-updated' => 'Your password was changed.',
        'verification-link-sent' => 'A new verification link was sent to your email.',
    ];
    $toasts = [];
    if (session('success')) $toasts[] = ['success', 'Done', session('success')];
    if (session('status') && isset($statusMessages[session('status')])) $toasts[] = ['success', 'Saved', $statusMessages[session('status')]];
    if (session('error')) $toasts[] = ['error', 'Not completed', session('error')];
    $allErrors = collect($errors?->getBags() ?? [])->flatMap(fn ($bag) => $bag->all())->unique()->values();
    if ($allErrors->isNotEmpty()) $toasts[] = ['error', 'Could not save', $allErrors->take(4)->implode("\n")];
@endphp
@if($toasts)
<div id="flash-toasts" aria-live="assertive" style="position:fixed;top:76px;right:18px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:min(420px,calc(100vw - 36px));">
    @foreach($toasts as [$type, $title, $message])
        <div role="{{ $type === 'error' ? 'alert' : 'status' }}" data-flash-toast
             style="display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:14px;font-family:Inter,system-ui,sans-serif;box-shadow:0 10px 30px rgba(15,23,42,.18);
                    background:{{ $type === 'error' ? '#fef2f2' : '#ecfdf5' }};border:1px solid {{ $type === 'error' ? '#fecaca' : '#a7f3d0' }};color:{{ $type === 'error' ? '#7f1d1d' : '#064e3b' }};
                    animation:flash-in .25s ease-out;">
            <div style="flex:none;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;background:{{ $type === 'error' ? '#dc2626' : '#059669' }};">{{ $type === 'error' ? '!' : '✓' }}</div>
            <div style="flex:1;min-width:0;">
                <p style="margin:0;font-weight:700;font-size:14px;">{{ $title }}</p>
                <p style="margin:3px 0 0;font-size:13px;line-height:1.45;white-space:pre-line;overflow-wrap:anywhere;">{{ $message }}</p>
            </div>
            <button type="button" aria-label="Close" onclick="this.closest('[data-flash-toast]').remove()" style="flex:none;background:none;border:0;font-size:20px;line-height:1;cursor:pointer;color:inherit;opacity:.6;">&times;</button>
        </div>
    @endforeach
</div>
<style>@keyframes flash-in{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}</style>
<script>
    // Success popups close by themselves; failures stay until closed so they can be read.
    setTimeout(() => document.querySelectorAll('[data-flash-toast][role="status"]').forEach(t => t.remove()), 6000);
</script>
@endif
