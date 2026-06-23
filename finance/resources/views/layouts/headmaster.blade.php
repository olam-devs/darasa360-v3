<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Headmaster Portal')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    @stack('head')
    <style>html, body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/50 text-slate-900 antialiased">
    <nav class="sticky top-0 z-50 border-b border-blue-100/80 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('headmaster.dashboard') }}" class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white">H</a>
                <div>
                    <p class="text-xs font-medium text-blue-700">Darasa Finance</p>
                    <p class="text-sm font-semibold text-slate-900">Headmaster portal <span class="text-xs font-normal text-slate-500">(read-only)</span></p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('headmaster.dashboard') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('headmaster.dashboard') ? 'bg-blue-100 font-semibold text-blue-800' : 'text-slate-600 hover:bg-blue-50' }}">Dashboard</a>
                <a href="{{ route('headmaster.ledgers') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('headmaster.ledgers') ? 'bg-blue-100 font-semibold text-blue-800' : 'text-slate-600 hover:bg-blue-50' }}">Ledgers</a>
                <a href="{{ route('headmaster.particular-ledger') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('headmaster.particular-ledger') ? 'bg-blue-100 font-semibold text-blue-800' : 'text-slate-600 hover:bg-blue-50' }}">Particulars</a>
                <a href="{{ route('headmaster.overdue') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('headmaster.overdue') ? 'bg-blue-100 font-semibold text-blue-800' : 'text-slate-600 hover:bg-blue-50' }}">Overdue</a>
                <a href="{{ route('headmaster.invoices') }}" class="rounded-lg px-2 py-1 {{ request()->routeIs('headmaster.invoices') ? 'bg-blue-100 font-semibold text-blue-800' : 'text-slate-600 hover:bg-blue-50' }}">Invoices</a>
                <span class="hidden text-slate-600 sm:inline">{{ session('headmaster_name') }}</span>
                <form method="POST" action="{{ route('headmaster.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-900">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        @if(!empty($readOnly))
        <p class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm text-blue-900">Read-only view — contact the accountant to make changes.</p>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
    @include('components.portal-assistant')
    <footer class="sticky bottom-0 z-30 bg-white border-t border-gray-200">
        <div class="px-4 py-2.5 flex flex-col gap-1 text-center text-xs text-gray-400 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-gray-600 font-medium">&copy; {{ date('Y') }} {{ optional($settings ?? null)->school_name ?? session('headmaster_school') ?? 'Darasa Finance' }}</span>
            <span class="inline-flex items-center gap-1.5 mx-auto sm:mx-0">
                Powered by
                <img src="/darasa360-logo.png" alt="Darasa360" class="h-4 w-4 object-contain rounded-full border border-gray-200">
                <strong class="text-blue-600">Darasa<span class="font-normal text-gray-500">360</span></strong>
            </span>
        </div>
    </footer>
</body>
</html>
