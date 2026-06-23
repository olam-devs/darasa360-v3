<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Super Admin — Darasa Finance')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('head')
    <style>html, body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/50 text-slate-900 antialiased">
    <nav class="sticky top-0 z-50 border-b border-blue-100/80 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('superadmin.dashboard') }}" class="text-lg font-bold text-blue-700 sm:text-xl">Darasa Finance</a>
                <span class="hidden text-slate-300 sm:inline">|</span>
                <span class="hidden truncate text-sm text-slate-600 sm:inline">@yield('nav_title', 'Super Admin')</span>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2 text-sm sm:gap-4">
                <a href="{{ route('superadmin.dashboard') }}" class="rounded-lg px-2 py-1 text-slate-600 hover:bg-blue-50 hover:text-blue-800 {{ request()->routeIs('superadmin.dashboard') ? 'bg-blue-100 font-semibold text-blue-800' : '' }}">Dashboard</a>
                <a href="{{ route('superadmin.schools.index') }}" class="rounded-lg px-2 py-1 text-slate-600 hover:bg-blue-50 hover:text-blue-800 {{ request()->routeIs('superadmin.schools.*') ? 'bg-blue-100 font-semibold text-blue-800' : '' }}">Schools</a>
                <a href="{{ route('superadmin.activity-logs') }}" class="rounded-lg px-2 py-1 text-slate-600 hover:bg-blue-50 hover:text-blue-800 {{ request()->routeIs('superadmin.activity-logs') ? 'bg-blue-100 font-semibold text-blue-800' : '' }}">Logs</a>
                <a href="{{ route('superadmin.admins.index') }}" class="hidden rounded-lg px-2 py-1 text-slate-600 hover:bg-blue-50 hover:text-blue-800 sm:inline {{ request()->routeIs('superadmin.admins.*') ? 'bg-blue-100 font-semibold text-blue-800' : '' }}">Admins</a>
                <a href="{{ route('superadmin.profile') }}" class="hidden text-slate-600 hover:text-blue-800 md:inline">{{ auth('superadmin')->user()->name ?? 'Profile' }}</a>
                <form method="POST" action="{{ route('superadmin.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-900">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
