<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — Darasa360</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; min-height: 100vh; display: flex; flex-direction: column; background: #0d1117; }
        .wrap { flex: 1; display: flex; }
        .hero {
            width: 46%;
            background: linear-gradient(160deg, #0a1f5e 0%, #1338b0 55%, #1e4fcf 100%);
            position: relative; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between; padding: 48px 36px;
        }
        .hero::after { content:''; position:absolute; inset:0;
            background: radial-gradient(ellipse at 20% 80%, rgba(99,179,255,.12) 0%, transparent 60%); pointer-events:none; }
        .ring { position:absolute; border-radius:50%; border:1px solid rgba(255,255,255,.06); top:50%; left:50%; transform:translate(-50%,-50%); pointer-events:none; }
        .fi { position:absolute; color:rgba(255,255,255,.18); pointer-events:none; animation:floatUp linear infinite; }
        @keyframes floatUp { 0%{transform:translateY(0) rotate(0deg);opacity:0;} 8%{opacity:1;} 92%{opacity:.7;} 100%{transform:translateY(-110vh) rotate(25deg);opacity:0;} }
        @keyframes twinkle { 0%,100%{opacity:.1;} 50%{opacity:.7;} }
        .star { position:absolute; background:#fff; border-radius:50%; animation:twinkle ease-in-out infinite; }
        .hero-brand-name { font-size:22px; font-weight:800; color:#fff; letter-spacing:-.3px; }
        .hero-brand-sub  { font-size:11px; color:rgba(255,255,255,.5); letter-spacing:.8px; text-transform:uppercase; margin-top:3px; }
        .hero-tagline { font-size:28px; font-weight:800; color:#fff; line-height:1.25; letter-spacing:-.5px; }
        .hero-tagline span { color:#7dd3fc; }
        .hero-desc { font-size:13px; color:rgba(255,255,255,.6); margin-top:10px; line-height:1.6; }
        .feat-item { display:flex; align-items:center; gap:10px; font-size:13px; color:rgba(255,255,255,.8); }
        .feat-icon { width:30px; height:30px; border-radius:8px; background:rgba(255,255,255,.12); display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
        /* right panel */
        .form-side { width:54%; background:#fff; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:40px 20px; }
        .card { width:100%; max-width:400px; animation:slideUp .55s cubic-bezier(.22,.68,0,1.15) both; }
        @keyframes slideUp { from{opacity:0;transform:translateY(24px);} to{opacity:1;transform:translateY(0);} }
        .brand-chip { display:inline-flex; align-items:center; gap:10px; background:#eff6ff; border-radius:12px; padding:10px 16px; margin-bottom:20px; }
        .brand-chip img { width:28px; height:28px; object-fit:contain; }
        .brand-chip-name { font-size:15px; font-weight:700; color:#1e3a8a; }
        .brand-chip-name span { color:#2563eb; }
        .brand-powered { font-size:10px; color:#94a3b8; margin-top:2px; }
        .portal-heading { font-size:22px; font-weight:800; color:#0f172a; margin-bottom:4px; }
        .portal-sub { font-size:13px; color:#64748b; margin-bottom:20px; }
        .restricted-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; background:#fef3c7; border:1px solid #fde68a; border-radius:20px; font-size:11px; color:#92400e; font-weight:600; margin-bottom:20px; }
        label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
        .inp { width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:#fafbff; color:#0f172a; transition:border-color .18s,box-shadow .18s; }
        .inp:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); background:#fff; }
        .inp::placeholder { color:#94a3b8; }
        .inp.err { border-color:#f87171; }
        .field { margin-bottom:16px; }
        .pwd-wrap { position:relative; }
        .pwd-wrap .inp { padding-right:42px; }
        .eye-btn { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:4px; }
        .eye-btn:hover { color:#475569; }
        .remember { display:flex; align-items:center; gap:8px; font-size:13px; color:#64748b; margin-bottom:20px; }
        .remember input { width:15px; height:15px; accent-color:#2563eb; }
        .btn-submit { width:100%; padding:12px; border:none; border-radius:10px; cursor:pointer; font-size:14px; font-weight:700; color:#fff; background:linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow:0 4px 14px rgba(37,99,235,.35); transition:transform .15s,box-shadow .15s; }
        .btn-submit:hover { transform:translateY(-1px); box-shadow:0 6px 22px rgba(37,99,235,.45); }
        .err-msg { color:#ef4444; font-size:12px; margin-top:4px; }
        .alert-success { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:18px; background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }
        .alert-error   { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:18px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }
        footer { background:#fff; border-top:1px solid #e2e8f0; padding:9px 24px; display:flex; align-items:center; justify-content:space-between; font-size:11px; color:#94a3b8; flex-shrink:0; }
        footer strong { color:#2563eb; }
        @media(max-width:768px){.wrap{flex-direction:column;}.hero{width:100%;padding:32px 24px;min-height:220px;}.form-side{width:100%;}}
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <div class="ring" style="width:220px;height:220px;"></div>
        <div class="ring" style="width:380px;height:380px;"></div>
        <div class="ring" style="width:520px;height:520px;"></div>
        <div class="star" style="width:2px;height:2px;top:12%;left:18%;animation-duration:2.1s;animation-delay:.3s;"></div>
        <div class="star" style="width:3px;height:3px;top:28%;left:72%;animation-duration:1.8s;animation-delay:.9s;"></div>
        <div class="star" style="width:2px;height:2px;top:50%;left:8%;animation-duration:2.5s;animation-delay:.5s;"></div>
        <div class="star" style="width:2px;height:2px;top:72%;left:85%;animation-duration:1.6s;animation-delay:1.3s;"></div>
        <i class="fi fas fa-book-open"          style="font-size:24px;left:6%;bottom:-40px;animation-duration:10s;animation-delay:0s;"></i>
        <i class="fi fas fa-pencil-alt"         style="font-size:16px;left:20%;bottom:-40px;animation-duration:12s;animation-delay:1.5s;"></i>
        <i class="fi fas fa-graduation-cap"     style="font-size:22px;left:38%;bottom:-40px;animation-duration:9s;animation-delay:3s;"></i>
        <i class="fi fas fa-ruler-combined"     style="font-size:14px;left:55%;bottom:-40px;animation-duration:13s;animation-delay:.8s;"></i>
        <i class="fi fas fa-calculator"         style="font-size:18px;left:70%;bottom:-40px;animation-duration:11s;animation-delay:2s;"></i>
        <i class="fi fas fa-microscope"         style="font-size:20px;left:84%;bottom:-40px;animation-duration:8s;animation-delay:4s;"></i>
        <i class="fi fas fa-pen-nib"            style="font-size:13px;left:46%;bottom:-40px;animation-duration:14s;animation-delay:1s;"></i>
        <i class="fi fas fa-flask"              style="font-size:15px;left:13%;bottom:-40px;animation-duration:9.5s;animation-delay:5s;"></i>
        <i class="fi fas fa-chalkboard-teacher" style="font-size:17px;left:62%;bottom:-40px;animation-duration:11.5s;animation-delay:2.5s;"></i>
        <div>
            <div class="hero-brand-name">Darasa Finance</div>
            <div class="hero-brand-sub">Powered by Darasa360 | Olam Technologies</div>
        </div>
        <div>
            <div class="hero-tagline">Smarter School<br><span>Communications</span> Simplified</div>
            <div class="hero-desc">Manage all schools, users, permissions and system configuration from a single admin console.</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <div class="feat-item"><div class="feat-icon"><i class="fas fa-school"></i></div>Multi-school management</div>
            <div class="feat-item"><div class="feat-icon"><i class="fas fa-user-shield"></i></div>User &amp; role administration</div>
            <div class="feat-item"><div class="feat-icon"><i class="fas fa-chart-pie"></i></div>Platform-wide analytics</div>
            <div class="feat-item"><div class="feat-icon"><i class="fas fa-cog"></i></div>System configuration</div>
        </div>
    </div>

    <div class="form-side">
        <div class="card">
            <div style="text-align:center;margin-bottom:24px;">
                <img src="/darasa360-book.png" alt="Darasa360" style="width:100px;height:100px;object-fit:contain;display:block;margin:0 auto 10px;">
                <div style="font-size:26px;font-weight:800;color:#1e3a8a;letter-spacing:2px;">DARASA360</div>
            </div>
            <div class="portal-heading">Super Admin</div>
            <div class="portal-sub">Platform management and school oversight console</div>
            <div class="restricted-badge"><i class="fas fa-lock" style="font-size:10px;"></i> Restricted Access</div>

            @if(session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('superadmin.login.post') }}">
                @csrf
                <div class="field">
                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        placeholder="admin@darasa360.com" class="inp{{ $errors->has('email') ? ' err' : '' }}">
                    @error('email')<p class="err-msg">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label>Password</label>
                    <div class="pwd-wrap">
                        <input id="pwd" type="password" name="password" required placeholder="Enter your password"
                            class="inp{{ $errors->has('password') ? ' err' : '' }}">
                        <button type="button" class="eye-btn" onclick="togglePwd()">
                            <i id="eye-on" class="fas fa-eye" style="font-size:15px;"></i>
                            <i id="eye-off" class="fas fa-eye-slash" style="font-size:15px;display:none;"></i>
                        </button>
                    </div>
                    @error('password')<p class="err-msg">{{ $message }}</p>@enderror
                </div>
                <div class="remember">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember" style="margin:0;font-weight:400;">Remember me</label>
                </div>
                <button type="submit" class="btn-submit">Login to Admin Panel</button>
            </form>
        </div>
    </div>
</div>

<footer>
    <span>&copy; {{ date('Y') }} Darasa360 Platform. All rights reserved.</span>
    <span>Powered by <strong>Darasa360</strong> | Olam Technologies</span>
</footer>
<script>
function togglePwd() {
    var i = document.getElementById('pwd');
    i.type = i.type === 'password' ? 'text' : 'password';
    document.getElementById('eye-on').style.display  = i.type === 'text' ? 'none' : 'inline';
    document.getElementById('eye-off').style.display = i.type === 'text' ? 'inline' : 'none';
}
</script>
</body>
</html>
