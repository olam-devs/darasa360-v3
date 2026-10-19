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
        .mgmt-nav a { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:9px; font-size:14px; font-weight:500; color:#cbd5e1; }
        .mgmt-nav a:hover { background:rgba(255,255,255,.07); color:#fff; }
        .mgmt-nav a[aria-current="page"] { background:#2563eb; color:#fff; }
        .mgmt-nav .mgmt-count { margin-left:auto; background:#f59e0b; color:#111827; border-radius:999px; font-size:11px; font-weight:700; padding:1px 8px; }
        .mgmt-nav h4 { font-size:11px; letter-spacing:1.2px; text-transform:uppercase; color:#64748b; font-weight:700; margin:18px 12px 6px; }
    </style>
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <aside id="mgmt-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full overflow-y-auto bg-slate-900 px-3 py-5 transition-transform lg:translate-x-0">
        <div class="px-3 pb-4 border-b border-slate-700/60">
            <p class="text-xs font-semibold uppercase tracking-widest text-blue-300">Darasa Finance</p>
            <p class="mt-1 text-base font-bold text-white">School management</p>
            <p class="text-xs text-slate-400">{{ auth()->user()->name }}</p>
        </div>
        <nav class="mgmt-nav mt-3" aria-label="School management">
            <a href="{{ route('accountant.management', $schoolQuery) }}#overview" data-section="overview" @if($active==='overview') aria-current="page" @endif><i class="fa-solid fa-chart-pie w-4"></i> Overview</a>
            <a href="{{ route('accountant.management', $schoolQuery) }}#analytics" data-section="analytics" @if($active==='analytics') aria-current="page" @endif><i class="fa-solid fa-chart-column w-4"></i> Analytics</a>
            <a href="{{ route('accountant.management', $schoolQuery) }}#books" data-section="books" @if($active==='books') aria-current="page" @endif><i class="fa-solid fa-building-columns w-4"></i> Books</a>
            <a href="{{ route('accountant.management', $schoolQuery) }}#quarters" data-section="quarters" @if($active==='quarters') aria-current="page" @endif><i class="fa-solid fa-calendar-days w-4"></i> Quarter analytics</a>
            <a href="{{ route('accountant.management', $schoolQuery) }}#approvals" data-section="approvals" @if($active==='approvals') aria-current="page" @endif><i class="fa-solid fa-file-circle-check w-4"></i> Expense approvals
                @php $navPending = $selected ? (collect($nav['schools'])->firstWhere('id', $selected)['pending'] ?? 0) : $nav['pending']; @endphp
                @if($navPending > 0)<span class="mgmt-count">{{ $navPending }}</span>@endif</a>
            <a href="{{ route('accountant.management.invoices', $schoolQuery) }}" @if($active==='invoices') aria-current="page" @endif><i class="fa-solid fa-file-invoice w-4"></i> Invoices</a>
            <a href="{{ route('accountant.management', $schoolQuery) }}#targets" data-section="targets" @if($active==='targets') aria-current="page" @endif><i class="fa-solid fa-bullseye w-4"></i> Accountant targets</a>

            <h4>Schools</h4>
            <a href="{{ route('accountant.management') }}#overview" @if(!$selected && $active!=='invoices') aria-current="page" @endif><i class="fa-solid fa-layer-group w-4"></i> All schools</a>
            @foreach($nav['schools'] as $s)
                <a href="{{ route('accountant.management', ['school' => $s['id']]) }}#overview" @if($selected === $s['id']) aria-current="page" @endif title="{{ $s['name'] }}">
                    <i class="fa-solid fa-school w-4"></i> {{ $s['short'] }}
                    @if($s['pending'] > 0)<span class="mgmt-count" title="Expenses waiting for approval">{{ $s['pending'] }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="mt-6 border-t border-slate-700/60 pt-4">
            <form method="post" action="{{ route('accountant.management.account-settings') }}">@csrf
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-300 hover:bg-white/5"><i class="fa-solid fa-user-gear w-4"></i> Account settings</button>
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
                        <p class="truncate text-xs font-medium text-blue-700">School management</p>
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
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Logout</button>
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
            const sw = document.getElementById('mgmt-school-switch');
            if (sw) sw.addEventListener('change', function () {
                const url = new URL(window.location.href);
                if (this.value) url.searchParams.set('school', this.value); else url.searchParams.delete('school');
                window.location.href = url.toString();
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
