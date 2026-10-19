@php
    /** Navigation data from SchoolManagementController::nav(): schools (id, name, short, pending), selected, pending. */
    $nav = $nav ?? ['schools' => [], 'selected' => null, 'pending' => 0];
    $selected = $nav['selected'];
    $schoolQuery = $selected ? ['school' => $selected] : [];
    $active = $activeSection ?? 'overview';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'School management — Darasa Finance')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        html, body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; }
        #mgmt-sidebar { background: linear-gradient(180deg, #ecfdf5 0%, #f7fdf9 45%, #ffffff 100%); }
        .mgmt-brand { width:38px; height:38px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-weight:800; color:#fff;
            background: linear-gradient(135deg, #10b981, #047857); box-shadow: 0 4px 10px rgba(4,120,87,.25); }
        .mgmt-nav a { position:relative; display:flex; align-items:center; gap:10px; padding:7px 10px; margin:2px 0; border-radius:12px; font-size:14px; font-weight:500; color:#334155; transition: background .15s, box-shadow .15s; }
        .mgmt-nav a:hover { background:rgba(255,255,255,.85); }
        .mgmt-nav a[aria-current="page"] { background:#fff; color:#065f46; font-weight:650; box-shadow: 0 1px 2px rgba(15,23,42,.06), 0 4px 12px rgba(4,120,87,.10); }
        .mgmt-nav a[aria-current="page"]::before { content:''; position:absolute; left:-3px; top:10px; bottom:10px; width:4px; border-radius:4px; background:#10b981; }
        .mgmt-ico { width:28px; height:28px; flex:none; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:13px; }
        .mgmt-ico.green { background:#d1fae5; color:#047857; } .mgmt-ico.violet { background:#ede9fe; color:#6d28d9; } .mgmt-ico.amber { background:#fef3c7; color:#b45309; }
        .mgmt-ico.rose { background:#ffe4e6; color:#be123c; } .mgmt-ico.teal { background:#ccfbf1; color:#0f766e; } .mgmt-ico.orange { background:#ffedd5; color:#c2410c; } .mgmt-ico.slate { background:#f1f5f9; color:#475569; }
        .mgmt-dot { width:10px; height:10px; border-radius:50%; flex:none; margin:0 9px 0 9px; box-shadow: 0 0 0 3px rgba(255,255,255,.9); }
        .mgmt-nav .mgmt-count { margin-left:auto; background:#f59e0b; color:#fff; border-radius:999px; font-size:11px; font-weight:700; padding:1px 8px; box-shadow: 0 1px 3px rgba(180,83,9,.3); }
        .mgmt-nav h4 { font-size:11px; letter-spacing:1.2px; text-transform:uppercase; color:#059669; font-weight:700; margin:20px 12px 6px; }
        .mgmt-group summary { list-style:none; display:flex; align-items:center; gap:10px; padding:7px 10px; margin:2px 0; border-radius:12px; font-size:14px; font-weight:600; color:#1e293b; cursor:pointer; }
        .mgmt-group summary::-webkit-details-marker { display:none; }
        .mgmt-group summary:hover { background:rgba(255,255,255,.85); }
        .mgmt-group .mgmt-chev { margin-left:auto; font-size:11px; color:#94a3b8; transition:transform .15s; }
        .mgmt-group summary .mgmt-count + .mgmt-chev { margin-left:8px; }
        .mgmt-group[open] .mgmt-chev { transform:rotate(180deg); }
        .mgmt-group[open] summary .mgmt-count { display:none; }
        .mgmt-group > a { margin-left:14px; padding-left:8px; }
    </style>
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <aside id="mgmt-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full overflow-y-auto border-r border-emerald-100 px-3 py-5 transition-transform lg:translate-x-0">
        <div class="flex items-center gap-3 px-2 pb-4 border-b border-emerald-100">
            <div class="mgmt-brand">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600">Darasa Finance</p>
                <p class="text-[15px] font-bold leading-tight text-slate-900">School management</p>
                <p class="truncate text-xs text-slate-500">{{ auth()->user()->name }}</p>
            </div>
        </div>
        @php $navPending = $selected ? (collect($nav['schools'])->firstWhere('id', $selected)['pending'] ?? 0) : $nav['pending']; @endphp
        @php
            // Grouped menu; a group opens when one of its pages is the current one. Add SMS as its own group later.
            $link = fn($section) => route('accountant.management', $schoolQuery).'#'.$section;
            $groups = [
                'reports' => ['Reports', 'fa-chart-line', ['quarters', 'analytics', 'budget', 'books']],
                'spending' => ['Spending', 'fa-wallet', ['approvals', 'payroll', 'staff']],
                'fees' => ['Fees', 'fa-file-invoice-dollar', ['invoices', 'overdue', 'advance', 'receipts', 'ledger']],
            ];
            $openGroup = collect($groups)->search(fn($g) => in_array($active, $g[2]));
            // School links keep the current page (and tab, added in the browser); only the school changes.
            $onReview = request()->routeIs('accountant.management.expense');
            $schoolUrl = fn($id) => $onReview ? route('accountant.management', array_filter(['school' => $id])).'#approvals'
                : ($id ? request()->fullUrlWithQuery(['school' => $id]) : request()->fullUrlWithoutQuery(['school']));
        @endphp
        <nav class="mgmt-nav mt-3" aria-label="School management">
            <a href="{{ $link('overview') }}" data-section="overview" @if($active==='overview') aria-current="page" @endif><span class="mgmt-ico green"><i class="fa-solid fa-chart-pie"></i></span> Overview</a>

            <details class="mgmt-group" data-group="reports" @if($openGroup === 'reports') open @endif>
                <summary><span class="mgmt-ico violet"><i class="fa-solid fa-chart-line"></i></span> Reports<i class="fa-solid fa-chevron-down mgmt-chev"></i></summary>
                <a href="{{ $link('quarters') }}" data-section="quarters" @if($active==='quarters') aria-current="page" @endif><span class="mgmt-ico orange"><i class="fa-solid fa-calendar-days"></i></span> Quarter analytics</a>
                <a href="{{ $link('analytics') }}" data-section="analytics" @if($active==='analytics') aria-current="page" @endif><span class="mgmt-ico violet"><i class="fa-solid fa-chart-column"></i></span> Analytics</a>
                <a href="{{ $link('budget') }}" data-section="budget" @if($active==='budget') aria-current="page" @endif><span class="mgmt-ico violet"><i class="fa-solid fa-scale-balanced"></i></span> Budget</a>
                <a href="{{ $link('books') }}" data-section="books" @if($active==='books') aria-current="page" @endif><span class="mgmt-ico teal"><i class="fa-solid fa-building-columns"></i></span> Books</a>
            </details>

            <details class="mgmt-group" data-group="spending" @if($openGroup === 'spending') open @endif>
                <summary><span class="mgmt-ico amber"><i class="fa-solid fa-wallet"></i></span> Spending
                    @if($navPending > 0)<span class="mgmt-count">{{ $navPending }}</span>@endif<i class="fa-solid fa-chevron-down mgmt-chev"></i></summary>
                <a href="{{ $link('approvals') }}" data-section="approvals" @if($active==='approvals') aria-current="page" @endif><span class="mgmt-ico amber"><i class="fa-solid fa-file-circle-check"></i></span> Expense approvals
                    @if($navPending > 0)<span class="mgmt-count">{{ $navPending }}</span>@endif</a>
                <a href="{{ route('accountant.management.payroll', $schoolQuery) }}" @if(in_array($active, ['payroll', 'staff'])) aria-current="page" @endif><span class="mgmt-ico teal"><i class="fa-solid fa-money-check-dollar"></i></span> Payroll &amp; staff</a>
            </details>

            <details class="mgmt-group" data-group="fees" @if($openGroup === 'fees') open @endif>
                <summary><span class="mgmt-ico rose"><i class="fa-solid fa-file-invoice-dollar"></i></span> Fees<i class="fa-solid fa-chevron-down mgmt-chev"></i></summary>
                <a href="{{ route('accountant.management.invoices', $schoolQuery) }}" @if($active==='invoices') aria-current="page" @endif><span class="mgmt-ico rose"><i class="fa-solid fa-file-invoice"></i></span> Invoices</a>
                <a href="{{ route('accountant.management.overdue', $schoolQuery) }}" @if($active==='overdue') aria-current="page" @endif><span class="mgmt-ico rose"><i class="fa-solid fa-triangle-exclamation"></i></span> Overdue fees</a>
                <a href="{{ route('accountant.management.advance', $schoolQuery) }}" @if($active==='advance') aria-current="page" @endif><span class="mgmt-ico green"><i class="fa-solid fa-piggy-bank"></i></span> Advance credit</a>
                <a href="{{ route('accountant.management.receipts', $schoolQuery) }}" @if($active==='receipts') aria-current="page" @endif><span class="mgmt-ico teal"><i class="fa-solid fa-receipt"></i></span> Receipt check</a>
                <a href="{{ route('accountant.management.bank-ledger', $schoolQuery) }}" @if($active==='ledger') aria-current="page" @endif><span class="mgmt-ico teal"><i class="fa-solid fa-book"></i></span> Bank ledger</a>
            </details>

            <a href="{{ $link('targets') }}" data-section="targets" @if($active==='targets') aria-current="page" @endif><span class="mgmt-ico slate"><i class="fa-solid fa-bullseye"></i></span> Accountant targets</a>

            <h4>Schools</h4>
            <a href="{{ $schoolUrl(null) }}" data-school-link @if(!$selected) aria-current="page" @endif><span class="mgmt-ico green"><i class="fa-solid fa-layer-group"></i></span> All schools</a>
            @php $schoolDots = ['#2a78d6', '#eb6834', '#1baf7a']; @endphp
            @foreach($nav['schools'] as $i => $s)
                <a href="{{ $schoolUrl($s['id']) }}" data-school-link @if($selected === $s['id']) aria-current="page" @endif title="{{ $s['name'] }}">
                    <span class="mgmt-dot" style="background:{{ $schoolDots[$i % 3] }};"></span> {{ $s['short'] }}
                    @if($s['pending'] > 0)<span class="mgmt-count" title="Expenses waiting for approval">{{ $s['pending'] }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="mt-6 border-t border-emerald-100 pt-4">
            <form method="post" action="{{ route('accountant.management.account-settings') }}">@csrf
                <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-2 py-2 text-left text-sm text-slate-600 hover:bg-white"><span class="mgmt-ico slate"><i class="fa-solid fa-user-gear"></i></span> Account settings</button>
            </form>
        </div>
    </aside>
    <div id="mgmt-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/40 lg:hidden" onclick="toggleMgmtSidebar()"></div>

    <div class="flex min-h-screen flex-col lg:pl-64">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="flex h-16 items-center justify-between gap-3 px-4 lg:px-6">
                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" onclick="toggleMgmtSidebar()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 lg:hidden" aria-label="Open menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium text-emerald-600">School management</p>
                        <h1 class="truncate text-sm font-semibold">@yield('page_title', 'Overview')</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <label class="hidden sm:flex items-center gap-2 text-sm text-slate-600">
                        <span>Viewing</span>
                        <select id="mgmt-school-switch" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm font-semibold text-slate-800">
                            <option value="" @selected(!$selected)>All schools</option>
                            @foreach($nav['schools'] as $s)<option value="{{ $s['id'] }}" @selected($selected === $s['id'])>{{ $s['short'] }}</option>@endforeach
                        </select>
                    </label>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button type="submit" class="rounded-xl border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Logout</button>
                    </form>
                </div>
            </div>
        </header>
        <main class="w-full flex-1 px-4 py-6 lg:px-6">
            @yield('content')
        </main>
    </div>
    <script>
        function toggleMgmtSidebar() {
            document.getElementById('mgmt-sidebar').classList.toggle('-translate-x-full');
            document.getElementById('mgmt-backdrop').classList.toggle('hidden');
        }
        (function () {
            // Switching school keeps the current tab too (the #tab part of the address).
            document.querySelectorAll('[data-school-link]').forEach(a => a.addEventListener('click', () => {
                if (location.hash && !a.href.includes('#')) a.href = a.href + location.hash;
            }));
            const sw = document.getElementById('mgmt-school-switch');
            if (sw) sw.addEventListener('change', function () {
                const url = new URL(window.location.href);
                if (this.value) url.searchParams.set('school', this.value); else url.searchParams.delete('school');
                window.location.href = url.toString();
            });
        })();
    </script>
    @include('components.flash-toast')
    @stack('scripts')
</body>
</html>
