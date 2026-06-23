<!DOCTYPE html>
<html lang="{{ Session::get('parent_language', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Parent Portal - Darasa Finance</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>html, body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="flex h-screen overflow-hidden bg-gradient-to-br from-slate-50 via-white to-blue-50/50 text-slate-900 antialiased">

    <div class="fixed left-0 top-0 z-50 flex w-full items-center justify-between border-b border-blue-100/80 bg-white/95 p-4 shadow-sm backdrop-blur md:hidden">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 font-bold text-white">D</div>
            <span class="font-bold text-slate-800" data-translate="portal-title">Darasa Parent</span>
        </div>
        <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-slate-600">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <div id="mobile-menu" class="fixed inset-0 z-40 hidden bg-slate-900/50 md:hidden" onclick="this.classList.add('hidden')">
        <div class="absolute right-0 top-0 h-full w-64 bg-white p-6 shadow-xl" onclick="event.stopPropagation()">
             <div class="mt-12 flex flex-col gap-4">
                <a href="{{ route('parent.dashboard') }}" class="flex items-center gap-3 font-medium text-slate-700 hover:text-blue-700"><i class="fas fa-home w-6"></i> <span data-translate="menu-dashboard">Dashboard</span></a>
                <a href="{{ route('parent.fees') }}" class="flex items-center gap-3 font-medium text-slate-700 hover:text-blue-700"><i class="fas fa-file-invoice-dollar w-6"></i> <span data-translate="menu-fees">Fees & Statements</span></a>
                <a href="{{ route('parent.invoices') }}" class="flex items-center gap-3 font-medium text-slate-700 hover:text-blue-700"><i class="fas fa-file-invoice w-6"></i> <span data-translate="menu-invoices">Invoices</span></a>
                <a href="{{ route('parent.messages') }}" class="flex items-center gap-3 font-medium text-slate-700 hover:text-blue-700"><i class="fas fa-envelope w-6"></i> <span data-translate="menu-messages">Messages</span></a>
                <a href="{{ route('parent.notifications') }}" class="flex items-center gap-3 font-medium text-slate-700 hover:text-blue-700"><i class="fas fa-bell w-6"></i> <span data-translate="menu-notifications">Notifications</span></a>
                <hr class="border-slate-200">
                <form action="{{ route('parent.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 font-medium text-red-600 hover:text-red-700"><i class="fas fa-sign-out-alt w-6"></i> <span data-translate="menu-logout">Logout</span></button>
                </form>
             </div>
        </div>
    </div>

    <aside class="z-10 hidden w-64 flex-col border-r border-blue-100/80 bg-white shadow-sm md:flex">
        <div class="flex items-center gap-3 border-b border-slate-100 p-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-600/20">
                <i class="fas fa-graduation-cap text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight text-slate-900">Darasa</h1>
                <p class="text-xs font-medium uppercase tracking-wider text-blue-600/80" data-translate="portal-subtitle">Parent Portal</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @php
                $navLink = fn ($active) => $active
                    ? 'bg-blue-50 text-blue-800 shadow-sm ring-1 ring-blue-100'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-blue-800';
            @endphp
            <a href="{{ route('parent.dashboard') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 transition {{ $navLink(request()->routeIs('parent.dashboard')) }}">
                <i class="fas fa-home w-5 text-center"></i>
                <span class="font-medium" data-translate="menu-dashboard">Dashboard</span>
            </a>
            <a href="{{ route('parent.fees') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 transition {{ $navLink(request()->routeIs('parent.fees')) }}">
                <i class="fas fa-file-invoice-dollar w-5 text-center"></i>
                <span class="font-medium" data-translate="menu-fees">Fee Statements</span>
            </a>
            <a href="{{ route('parent.invoices') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 transition {{ $navLink(request()->routeIs('parent.invoices')) }}">
                <i class="fas fa-file-invoice w-5 text-center"></i>
                <span class="font-medium" data-translate="menu-invoices">Invoices</span>
            </a>
            <a href="{{ route('parent.messages') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 transition {{ $navLink(request()->routeIs('parent.messages')) }}">
                <i class="fas fa-envelope w-5 text-center"></i>
                <span class="font-medium" data-translate="menu-messages">Messages</span>
            </a>
            <a href="{{ route('parent.notifications') }}" class="group flex items-center gap-3 rounded-xl px-4 py-3 transition {{ $navLink(request()->routeIs('parent.notifications')) }}">
                <i class="fas fa-bell w-5 text-center"></i>
                <span class="font-medium" data-translate="menu-notifications">Notifications</span>
            </a>
        </nav>

        <div class="border-t border-slate-100 p-4">
            <div class="mb-3 flex gap-2">
                <button type="button" onclick="switchLanguage('en')" id="lang-btn-en" class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition {{ Session::get('parent_language', 'en') == 'en' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">EN</button>
                <button type="button" onclick="switchLanguage('sw')" id="lang-btn-sw" class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold transition {{ Session::get('parent_language', 'en') == 'sw' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">SW</button>
            </div>
            <div class="mb-4 rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                <p class="mb-1 text-xs text-slate-500" data-translate="logged-in-as">Logged in as parent of</p>
                <p class="truncate font-bold text-slate-900">{{ Session::get('parent_student_name') }}</p>
            </div>
            <form action="{{ route('parent.logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-800 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-slate-900">
                    <i class="fas fa-sign-out-alt"></i> <span data-translate="menu-logout">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="relative z-0 flex flex-1 flex-col overflow-hidden">
        <header class="hidden items-center justify-between border-b border-blue-100/80 bg-white/80 px-8 py-6 backdrop-blur md:flex">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">@yield('title', 'Overview')</h2>
                <p class="text-sm text-slate-500" data-translate="welcome-back">Welcome back to your portal</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-semibold text-slate-800">{{ now()->format('l, F j, Y') }}</p>
                    <p class="text-xs text-slate-500" data-translate="today">Today</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                    <i class="far fa-calendar-alt"></i>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto px-4 py-6 pt-20 md:px-8 md:pb-8 md:pt-4">
            @if(session('error'))
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800" role="alert">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800" role="alert">{{ session('success') }}</div>
            @endif
            @yield('content')
        </div>
    </main>

    <script>
        const translations = {
            en: {
                'portal-title': 'Darasa Parent', 'portal-subtitle': 'Parent Portal',
                'menu-dashboard': 'Dashboard', 'menu-fees': 'Fee Statements', 'menu-invoices': 'Invoices',
                'menu-messages': 'Messages', 'menu-notifications': 'Notifications', 'menu-logout': 'Logout',
                'logged-in-as': 'Logged in as parent of', 'welcome-back': 'Welcome back to your portal', 'today': 'Today'
            },
            sw: {
                'portal-title': 'Darasa Wazazi', 'portal-subtitle': 'Mlango wa Wazazi',
                'menu-dashboard': 'Dashibodi', 'menu-fees': 'Taarifa za Ada', 'menu-invoices': 'Ankara',
                'menu-messages': 'Ujumbe', 'menu-notifications': 'Arifa', 'menu-logout': 'Toka',
                'logged-in-as': 'Umeingia kama mzazi wa', 'welcome-back': 'Karibu tena kwenye mlango wako', 'today': 'Leo'
            }
        };
        let currentLang = '{{ Session::get("parent_language", "en") }}';
        function switchLanguage(lang) {
            currentLang = lang;
            document.getElementById('lang-btn-en').className = lang === 'en'
                ? 'flex-1 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition'
                : 'flex-1 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-200';
            document.getElementById('lang-btn-sw').className = lang === 'sw'
                ? 'flex-1 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition'
                : 'flex-1 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-200';
            const t = translations[lang];
            document.querySelectorAll('[data-translate]').forEach(el => {
                const key = el.getAttribute('data-translate');
                if (t[key]) el.textContent = t[key];
            });
            axios.post('{{ route("parent.change-language") }}', { language: lang }, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            const t = translations[currentLang];
            document.querySelectorAll('[data-translate]').forEach(el => {
                const key = el.getAttribute('data-translate');
                if (t[key]) el.textContent = t[key];
            });
        });
    </script>
    @include('components.portal-assistant')
    <footer class="sticky bottom-0 z-30 bg-white border-t border-gray-200">
        <div class="px-4 py-2.5 flex flex-col gap-1 text-center text-xs text-gray-400 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-gray-600 font-medium">&copy; {{ date('Y') }} {{ session('school_name') ?? 'Darasa Finance' }}</span>
            <span class="inline-flex items-center gap-1.5 mx-auto sm:mx-0">
                Powered by
                <img src="/darasa360-logo.png" alt="Darasa360" class="h-4 w-4 object-contain rounded-full border border-gray-200">
                <strong class="text-blue-600">Darasa<span class="font-normal text-gray-500">360</span></strong>
            </span>
        </div>
    </footer>
</body>
</html>
