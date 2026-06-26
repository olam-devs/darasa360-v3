<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal — {{ $school->school_name ?? 'Darasa360' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; min-height: 100vh; display: flex; flex-direction: column; background: #f0f4ff; }
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
        .form-side { width:54%; background:#fff; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:40px 20px; }
        .card { width:100%; max-width:400px; animation:slideUp .55s cubic-bezier(.22,.68,0,1.15) both; }
        @keyframes slideUp { from{opacity:0;transform:translateY(24px);} to{opacity:1;transform:translateY(0);} }
        .brand-chip { display:inline-flex; align-items:center; gap:10px; background:#eff6ff; border-radius:12px; padding:10px 16px; margin-bottom:20px; }
        .brand-chip img { width:28px; height:28px; object-fit:contain; }
        .brand-chip-name { font-size:15px; font-weight:700; color:#1e3a8a; }
        .brand-chip-name span { color:#2563eb; }
        .brand-powered { font-size:10px; color:#94a3b8; margin-top:2px; }
        .school-name-badge { font-size:11px; color:#64748b; margin-top:2px; }
        .portal-heading { font-size:22px; font-weight:800; color:#0f172a; margin-bottom:4px; }
        .portal-sub { font-size:13px; color:#64748b; margin-bottom:28px; }
        label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
        .inp { width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; background:#fafbff; transition:border-color .18s,box-shadow .18s; }
        .inp:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); background:#fff; }
        .field { margin-bottom:16px; }
        .help-text { font-size:12px; color:#94a3b8; margin-top:5px; }
        .btn-submit { width:100%; padding:12px; border:none; border-radius:10px; cursor:pointer; font-size:14px; font-weight:700; color:#fff; background:linear-gradient(135deg,#2563eb,#1d4ed8); box-shadow:0 4px 14px rgba(37,99,235,.35); transition:transform .15s,box-shadow .15s; margin-top:4px; }
        .btn-submit:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(37,99,235,.45); }
        .alert-error { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:18px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }
        .lang-toggle { display:flex; gap:8px; margin-bottom:24px; }
        .lang-btn { padding:6px 16px; border-radius:20px; font-size:12px; font-weight:600; cursor:pointer; transition:all .2s; border:none; }
        .lang-btn.active { background:#2563eb; color:#fff; }
        .lang-btn.inactive { background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; }
        .contact-block { margin-top:20px; padding-top:18px; border-top:1px solid #f1f5f9; text-align:center; }
        .contact-block p { font-size:12px; color:#94a3b8; margin-bottom:5px; }
        .contact-block a { font-size:13px; font-weight:600; color:#2563eb; text-decoration:none; }
        footer { background:#fff; border-top:1px solid #e2e8f0; padding:9px 24px; display:flex; align-items:center; justify-content:space-between; font-size:11px; color:#94a3b8; flex-shrink:0; }
        footer strong { color:#2563eb; font-weight:700; }
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
            <div class="hero-desc" id="hero-desc">Access fee statements, invoices, and school communications in one place.</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <div class="feat-item"><div class="feat-icon"><i class="fas fa-file-invoice-dollar"></i></div><span id="f1">Fee statements &amp; balances</span></div>
            <div class="feat-item"><div class="feat-icon"><i class="fas fa-receipt"></i></div><span id="f2">Invoices &amp; payment history</span></div>
            <div class="feat-item"><div class="feat-icon"><i class="fas fa-envelope"></i></div><span id="f3">School messages</span></div>
            <div class="feat-item"><div class="feat-icon"><i class="fas fa-bell"></i></div><span id="f4">Notifications &amp; alerts</span></div>
        </div>
    </div>

    <div class="form-side">
        <div class="card">
            <!-- Language toggle -->
            <div class="lang-toggle">
                <button onclick="switchLang('en')" id="lang-en" class="lang-btn active">EN &nbsp;English</button>
                <button onclick="switchLang('sw')" id="lang-sw" class="lang-btn inactive">SW &nbsp;Kiswahili</button>
            </div>

            <div style="text-align:center;margin-bottom:24px;">
                <img src="/darasa360-book.png" alt="Darasa360" style="width:100px;height:100px;object-fit:contain;display:block;margin:0 auto 10px;">
                <div style="font-size:26px;font-weight:800;color:#1e3a8a;letter-spacing:2px;">DARASA360</div>
                @if(!empty($school->school_name))
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $school->school_name }}</div>
                @endif
            </div>

            <div class="portal-heading" id="form-title">Parent Portal</div>
            <div class="portal-sub" id="form-sub">Access your child's academic and financial records</div>

            @if (session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif

            <form action="{{ route('parent.login') }}" method="POST">
                @csrf
                <input type="hidden" name="language" id="selected-lang" value="en">
                <div class="field">
                    <label id="lbl-regno">Student Registration Number</label>
                    <input id="inp-regno" name="student_reg_no" type="text" required
                        value="{{ old('student_reg_no') }}" placeholder="e.g. STD/2024/001" class="inp">
                    <p class="help-text" id="help-text">Enter the student's registration number provided by the school</p>
                </div>
                <div class="field">
                    <label id="lbl-pwd">Portal Password</label>
                    <div style="position:relative;">
                        <input id="inp-pwd" name="portal_password" type="password" required
                            placeholder="Enter portal password" class="inp" style="padding-right:42px;">
                        <button type="button" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;" onclick="var i=document.getElementById('inp-pwd');i.type=i.type==='password'?'text':'password';">
                            <i class="fas fa-eye" style="font-size:15px;"></i>
                        </button>
                    </div>
                    <p class="help-text" id="help-pwd-text">Password is provided by the school accountant</p>
                </div>
                <button type="submit" class="btn-submit" id="btn-submit">Sign In to Portal</button>
            </form>

            @if(!empty($school->phone))
                <div class="contact-block">
                    <p id="contact-label">Need help? Contact school administration</p>
                    <a href="tel:{{ $school->phone }}"><i class="fas fa-phone" style="margin-right:4px;font-size:11px;"></i>{{ $school->phone }}</a>
                </div>
            @endif
        </div>
    </div>
</div>

<footer>
    <span>&copy; {{ date('Y') }} {{ $school->school_name ?? 'Darasa Finance' }}</span>
    <span>Powered by <strong>Darasa360</strong> | Olam Technologies</span>
</footer>

<script>
const T = {
    en: { title:'Parent Portal', sub:"Access your child's academic and financial records",
          lbl:'Student Registration Number', help:'Enter the student\'s registration number provided by the school',
          lblPwd:'Portal Password', helpPwd:'Password is provided by the school accountant',
          btn:'Sign In to Portal', contact:'Need help? Contact school administration',
          ph:'e.g. STD/2024/001', heroDesc:'Access fee statements, invoices, and school communications in one place.',
          f1:'Fee statements &amp; balances', f2:'Invoices &amp; payment history', f3:'School messages', f4:'Notifications &amp; alerts' },
    sw: { title:'Mlango wa Wazazi', sub:'Pata taarifa za elimu na fedha za mwanao kwa urahisi',
          lbl:'Nambari ya Usajili wa Mwanafunzi', help:'Weka nambari ya usajili iliyotolewa na shule',
          lblPwd:'Nywila ya Mlango', helpPwd:'Nywila hutolewa na mhasibu wa shule',
          btn:'Ingia kwenye Mlango', contact:'Unahitaji msaada? Wasiliana na shule',
          ph:'mfano: STD/2024/001', heroDesc:'Pata taarifa za ada, ankara, na mawasiliano ya shule mahali pamoja.',
          f1:'Taarifa za ada &amp; salio', f2:'Ankara &amp; historia ya malipo', f3:'Ujumbe wa shule', f4:'Arifa &amp; tahadhari' },
};
function switchLang(lang) {
    document.getElementById('selected-lang').value = lang;
    var t = T[lang];
    document.getElementById('form-title').textContent = t.title;
    document.getElementById('form-sub').textContent   = t.sub;
    document.getElementById('lbl-regno').textContent  = t.lbl;
    document.getElementById('help-text').textContent  = t.help;
    document.getElementById('lbl-pwd').textContent    = t.lblPwd;
    document.getElementById('help-pwd-text').textContent = t.helpPwd;
    document.getElementById('btn-submit').textContent = t.btn;
    document.getElementById('inp-regno').placeholder  = t.ph;
    document.getElementById('hero-desc').textContent  = t.heroDesc;
    document.getElementById('f1').innerHTML = t.f1;
    document.getElementById('f2').innerHTML = t.f2;
    document.getElementById('f3').innerHTML = t.f3;
    document.getElementById('f4').innerHTML = t.f4;
    var cl = document.getElementById('contact-label');
    if (cl) cl.textContent = t.contact;
    document.getElementById('lang-en').className = 'lang-btn ' + (lang==='en' ? 'active' : 'inactive');
    document.getElementById('lang-sw').className = 'lang-btn ' + (lang==='sw' ? 'active' : 'inactive');
    localStorage.setItem('parent_lang', lang);
}
switchLang(localStorage.getItem('parent_lang') || 'en');
</script>
</body>
</html>
