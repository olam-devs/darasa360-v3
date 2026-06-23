@php
    /** @var \App\Models\SchoolSetting|null $settings */
    $settings = $settings ?? \App\Models\SchoolSetting::getSettings();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Darasa Finance')</title>

    <!-- Typography (Inter) -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind (CDN for current modules) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Shared JS -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    @stack('head')

    <style>
        html, body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; }
        @keyframes darasa-toast-in { from { opacity: 0; transform: translateX(12px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes darasa-toast-out { to { opacity: 0; transform: translateX(12px); } }
        .darasa-toast-enter { animation: darasa-toast-in 0.25s ease-out forwards; }
        .darasa-toast-leave { animation: darasa-toast-out 0.2s ease-in forwards; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/50 text-slate-900 antialiased">
    @include('components.sidebar')

    <div class="flex min-h-screen flex-1 flex-col lg:pl-72">
    <!-- Top bar -->
    <header class="sticky top-0 z-40 border-b border-blue-100/80 bg-white/95 shadow-sm shadow-blue-900/5 backdrop-blur">
        <div class="w-full px-3 sm:px-4 lg:px-5 xl:px-6">
            <div class="flex h-16 items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" onclick="toggleSidebar()"
                        class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-blue-50/80 text-blue-800 hover:bg-blue-100 lg:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    @if(optional($settings)->logo_path && file_exists(public_path('storage/' . $settings->logo_path)))
                        <img src="{{ asset('storage/' . $settings->logo_path) }}" alt=""
                            class="h-12 w-12 min-h-12 min-w-12 flex-shrink-0 rounded-lg bg-white object-contain shadow-sm" />
                    @endif

                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium text-blue-700/80">
                            {{ optional($settings)->school_name ?: 'Darasa Finance' }}
                        </p>
                        <h1 class="truncate text-sm font-semibold text-slate-900">@yield('page_title', 'Accountant')</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @yield('topbar_actions')
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center rounded-xl border border-blue-200 bg-gradient-to-r from-blue-500 to-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-500/30 transition hover:from-blue-600 hover:to-sky-700">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="w-full flex-1 px-3 py-5 pb-14 sm:px-4 sm:py-6 lg:px-5 xl:px-6">
        @yield('content')
    </main>

    @include('components.accountant-footer')

    <div id="darasa-toast-stack" class="pointer-events-none fixed right-4 top-20 z-[100] flex max-w-sm flex-col gap-2 sm:right-6 sm:max-w-md" aria-live="polite"></div>

    <div id="darasa-confirm-root" class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true"></div>

    <script>
        (function () {
            window.darasaAxiosMessage = function (err) {
                if (!err) return 'Something went wrong.';
                var d = err.response && err.response.data;
                if (typeof d === 'string') return d;
                if (d && d.message) return d.message;
                if (d && d.error) return typeof d.error === 'string' ? d.error : JSON.stringify(d.error);
                return err.message || 'Request failed.';
            };

            window.showDarasaToast = function (opts) {
                var type = opts.type || 'info';
                var message = opts.message || '';
                var title = opts.title != null ? opts.title : (type === 'error' ? 'Error' : type === 'success' ? 'Success' : type === 'warning' ? 'Notice' : 'Info');
                var duration = opts.duration === undefined ? 5200 : opts.duration;
                var stack = document.getElementById('darasa-toast-stack');
                if (!stack) { window.alert(message); return; }

                var styles = {
                    success: 'border-emerald-200/80 bg-emerald-50 text-emerald-950 shadow-emerald-900/5',
                    error: 'border-red-200/80 bg-red-50 text-red-950 shadow-red-900/5',
                    warning: 'border-amber-200/80 bg-amber-50 text-amber-950 shadow-amber-900/5',
                    info: 'border-slate-200/90 bg-white text-slate-900 shadow-slate-900/5'
                };
                var ring = { success: 'ring-emerald-500/25', error: 'ring-red-500/25', warning: 'ring-amber-500/25', info: 'ring-slate-500/15' };

                var el = document.createElement('div');
                el.className = 'pointer-events-auto darasa-toast-enter max-w-md rounded-xl border px-4 py-3 shadow-lg ring-1 ' + (styles[type] || styles.info) + ' ' + (ring[type] || ring.info);
                el.innerHTML =
                    '<div class="flex gap-3">' +
                    '<div class="min-w-0 flex-1">' +
                    '<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">' + String(title).replace(/</g, '&lt;') + '</p>' +
                    '<p class="mt-1 text-sm leading-snug text-slate-800 whitespace-pre-wrap">' + String(message).replace(/</g, '&lt;') + '</p>' +
                    '</div>' +
                    '<button type="button" class="flex-shrink-0 rounded-lg p-1 text-slate-400 hover:bg-black/5 hover:text-slate-700" aria-label="Dismiss">&times;</button>' +
                    '</div>';
                var close = function () {
                    el.classList.remove('darasa-toast-enter');
                    el.classList.add('darasa-toast-leave');
                    setTimeout(function () { el.remove(); }, 220);
                };
                el.querySelector('button').addEventListener('click', close);
                stack.appendChild(el);
                if (duration > 0) setTimeout(close, duration);
            };

            window.darasaConfirm = function (message, title) {
                title = title || 'Please confirm';
                var root = document.getElementById('darasa-confirm-root');
                if (!root) return Promise.resolve(window.confirm(message));

                return new Promise(function (resolve) {
                    root.classList.remove('hidden');
                    root.innerHTML =
                        '<div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">' +
                        '<h2 class="text-lg font-semibold text-slate-900">' + String(title).replace(/</g, '&lt;') + '</h2>' +
                        '<p class="mt-3 text-sm leading-relaxed text-slate-600 whitespace-pre-wrap">' + String(message).replace(/</g, '&lt;') + '</p>' +
                        '<div class="mt-6 flex justify-end gap-2">' +
                        '<button type="button" class="darasa-confirm-cancel rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>' +
                        '<button type="button" class="darasa-confirm-ok rounded-lg bg-gradient-to-r from-blue-500 to-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-500/25 hover:from-blue-600 hover:to-sky-700">Confirm</button>' +
                        '</div></div>';

                    function done(val) {
                        root.classList.add('hidden');
                        root.innerHTML = '';
                        resolve(val);
                    }
                    root.querySelector('.darasa-confirm-cancel').onclick = function () { done(false); };
                    root.querySelector('.darasa-confirm-ok').onclick = function () { done(true); };
                    root.onclick = function (e) { if (e.target === root) done(false); };
                });
            };
        })();
    </script>

    @stack('scripts')

    @include('components.portal-assistant')
    </div>
</body>
</html>

