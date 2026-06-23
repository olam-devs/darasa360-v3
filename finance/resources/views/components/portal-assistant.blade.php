{{-- FAQ & Guidance Chatbot — bilingual (EN/SW), full-screen toggle, report-to-admin --}}

{{-- Panel wrapper — starts compact, can expand to full-screen --}}
<div id="da-root" class="fixed bottom-5 right-5 z-[60] flex flex-col items-end gap-3">

    {{-- Chat panel --}}
    <div id="da-panel"
         style="display:none; flex-direction:column; max-height:min(80vh,580px);"
         class="w-[min(100vw-2rem,22rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl transition-all duration-200">

        {{-- Header --}}
        <div class="flex flex-shrink-0 items-center justify-between bg-gradient-to-r from-violet-600 to-blue-600 px-4 py-3 text-white">
            <div>
                <p class="text-sm font-semibold" id="da-title">FAQ &amp; Guidance</p>
                <p class="text-[10px] text-blue-100" id="da-subtitle">Quick answers · Majibu ya haraka</p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Language toggle --}}
                <button type="button" id="da-lang-btn" title="Switch language"
                        class="rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold hover:bg-white/30 transition">EN</button>
                {{-- Full-screen toggle --}}
                <button type="button" id="da-expand-btn" title="Expand" aria-label="Full screen"
                        class="rounded-lg p-1 hover:bg-white/20">
                    <svg id="da-expand-icon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 8V4h4M4 16v4h4M20 8V4h-4M20 16v4h-4"/>
                    </svg>
                </button>
                {{-- Close --}}
                <button type="button" id="da-close" aria-label="Close" class="rounded-lg p-1 hover:bg-white/20">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages area --}}
        <div id="da-messages"
             class="overflow-y-auto space-y-3 bg-slate-50 p-4 text-sm leading-relaxed text-slate-800"
             style="flex:1 1 auto; min-height:120px; max-height:240px;">
        </div>

        {{-- Quick-intent buttons --}}
        <div class="flex-shrink-0 border-t border-slate-100 bg-white px-3 pt-2 pb-1">
            <p id="da-quick-label" class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Quick questions</p>
            <div id="da-intents" class="flex flex-col gap-1.5 overflow-y-auto" style="max-height:140px;"></div>
        </div>

        {{-- Report / Ask Admin --}}
        <div class="flex-shrink-0 border-t border-slate-100 bg-amber-50 px-3 py-2" id="da-report-bar">
            <button type="button" id="da-report-btn"
                    class="w-full text-[10px] text-amber-700 hover:text-amber-900 font-semibold transition underline-offset-2 hover:underline">
                📩 <span id="da-report-label">Report an issue / Ask admin a question</span>
            </button>
        </div>

        {{-- Report form (hidden by default) --}}
        <div id="da-report-form" style="display:none;"
             class="flex-shrink-0 border-t border-amber-200 bg-amber-50 px-3 py-2">
            <p id="da-report-prompt" class="text-[10px] text-amber-800 font-semibold mb-1">Describe your question or issue:</p>
            <textarea id="da-report-text" rows="2" maxlength="500"
                class="w-full rounded-lg border border-amber-300 bg-white px-2 py-1 text-xs text-slate-800 outline-none focus:border-amber-500 resize-none"></textarea>
            <div class="flex gap-2 mt-1.5">
                <button type="button" id="da-report-submit"
                    class="flex-1 rounded-lg bg-amber-500 text-white text-xs font-bold py-1 hover:bg-amber-600 transition">Send to Admin</button>
                <button type="button" id="da-report-cancel"
                    class="rounded-lg bg-slate-200 text-slate-700 text-xs font-bold px-3 py-1 hover:bg-slate-300 transition">Cancel</button>
            </div>
        </div>

        {{-- Free-text input --}}
        <div class="flex-shrink-0 border-t border-slate-200 bg-white p-3">
            <div class="flex items-center gap-2">
                <input id="da-input" type="text" maxlength="500"
                       placeholder="Type a question / Andika swali lako…"
                       autocomplete="off"
                       class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 placeholder-slate-400 outline-none focus:border-violet-400 focus:bg-white focus:ring-1 focus:ring-violet-300 transition" />
                <button id="da-send" type="button" aria-label="Send"
                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white shadow hover:bg-violet-700 disabled:opacity-40 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
            <p id="da-lang-hint" class="mt-1.5 text-[9px] text-slate-400 text-center">English &amp; Kiswahili supported</p>
        </div>
    </div>

    {{-- Floating toggle button --}}
    <div class="relative">
        <button type="button" id="da-toggle"
                class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-violet-600 to-blue-600 text-white shadow-lg shadow-violet-500/30 transition hover:scale-105 hover:from-violet-700 hover:to-blue-700"
                aria-label="Open FAQ & Guidance Chatbot">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
        </button>
        <span id="da-dot" class="pointer-events-none absolute -right-0.5 -top-0.5 hidden h-3.5 w-3.5 rounded-full bg-red-500 ring-2 ring-white"></span>
    </div>
</div>

<script>
(function () {
    var panel       = document.getElementById('da-panel');
    var toggle      = document.getElementById('da-toggle');
    var closeBtn    = document.getElementById('da-close');
    var messagesEl  = document.getElementById('da-messages');
    var intentsEl   = document.getElementById('da-intents');
    var input       = document.getElementById('da-input');
    var sendBtn     = document.getElementById('da-send');
    var dot         = document.getElementById('da-dot');
    var expandBtn   = document.getElementById('da-expand-btn');
    var langBtn     = document.getElementById('da-lang-btn');
    var reportBtn   = document.getElementById('da-report-btn');
    var reportForm  = document.getElementById('da-report-form');
    var reportText  = document.getElementById('da-report-text');
    var reportSub   = document.getElementById('da-report-submit');
    var reportCan   = document.getElementById('da-report-cancel');
    var root        = document.getElementById('da-root');

    var loaded      = false;
    var busy        = false;
    var open        = false;
    var expanded    = false;
    var lang        = 'en'; // 'en' | 'sw'

    // ── translations ────────────────────────────────────────────────────────

    var T = {
        en: {
            title:        'FAQ & Guidance',
            subtitle:     'Quick answers · Majibu ya haraka',
            quickLabel:   'Quick questions',
            placeholder:  'Type a question…',
            langHint:     'English & Kiswahili supported',
            reportLabel:  'Report an issue / Ask admin a question',
            reportPrompt: 'Describe your question or issue:',
            reportSend:   'Send to Admin',
            reportCancel: 'Cancel',
            reportThanks: 'Your message has been sent to the admin. Thank you!',
        },
        sw: {
            title:        'Maswali ya Kawaida',
            subtitle:     'Majibu ya haraka · Quick answers',
            quickLabel:   'Maswali ya haraka',
            placeholder:  'Andika swali lako…',
            langHint:     'Kiswahili & English inasaidiwa',
            reportLabel:  'Ripoti tatizo / Uliza swali kwa admin',
            reportPrompt: 'Eleza swali au tatizo lako:',
            reportSend:   'Tuma kwa Admin',
            reportCancel: 'Ghairi',
            reportThanks: 'Ujumbe wako umetumwa kwa admin. Asante!',
        },
    };

    function t(key) { return (T[lang] || T.en)[key] || key; }

    function applyLang() {
        langBtn.textContent = lang === 'en' ? 'SW' : 'EN';
        document.getElementById('da-title').textContent      = t('title');
        document.getElementById('da-subtitle').textContent   = t('subtitle');
        document.getElementById('da-quick-label').textContent = t('quickLabel');
        input.placeholder                                    = t('placeholder');
        document.getElementById('da-lang-hint').textContent  = t('langHint');
        document.getElementById('da-report-label').textContent = t('reportLabel');
        document.getElementById('da-report-prompt').textContent = t('reportPrompt');
        reportSub.textContent  = t('reportSend');
        reportCan.textContent  = t('reportCancel');
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function md(s) {
        return esc(s).replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>');
    }

    function appendMessage(html, role) {
        var b = document.createElement('div');
        b.className = role === 'user'
            ? 'ml-8 rounded-xl rounded-br-sm bg-violet-600 px-3 py-2 text-white text-xs'
            : 'mr-4 rounded-xl rounded-bl-sm border border-slate-200 bg-white px-3 py-2 shadow-sm text-xs';
        b.innerHTML = html;
        messagesEl.appendChild(b);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function appendLinks(links) {
        if (!links || !links.length) return;
        var wrap = document.createElement('div');
        wrap.className = 'flex flex-wrap gap-2 mr-4 -mt-1 mb-1';
        links.forEach(function (l) {
            var a = document.createElement('a');
            a.href = l.url; a.target = '_blank';
            a.className = 'inline-block rounded-md bg-violet-100 px-2 py-1 text-[10px] font-semibold text-violet-800 hover:bg-violet-200 transition';
            a.textContent = l.label;
            wrap.appendChild(a);
        });
        messagesEl.appendChild(wrap);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function setBusy(state) {
        busy = state;
        input.disabled = sendBtn.disabled = state;
        intentsEl.querySelectorAll('button').forEach(function (b) { b.disabled = state; });
    }

    function showTyping() {
        var el = document.createElement('div');
        el.id = 'da-typing';
        el.className = 'mr-4 rounded-xl rounded-bl-sm border border-slate-200 bg-white px-3 py-2 shadow-sm text-xs text-slate-400 italic';
        el.textContent = lang === 'sw' ? 'Inaandika…' : 'Typing…';
        messagesEl.appendChild(el);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function removeTyping() {
        var el = document.getElementById('da-typing');
        if (el) el.remove();
    }

    // ── panel visibility ─────────────────────────────────────────────────────

    function showPanel() {
        open = true;
        panel.style.display = 'flex';
        dot.classList.add('hidden');
    }

    function hidePanel() {
        open = false;
        panel.style.display = 'none';
        if (expanded) collapsePanel();
    }

    // ── full-screen expand/collapse ──────────────────────────────────────────

    function expandPanel() {
        expanded = true;
        root.classList.remove('bottom-5','right-5');
        root.classList.add('inset-0','bottom-0','right-0');
        panel.classList.remove('w-[min(100vw-2rem,22rem)]','rounded-2xl');
        panel.classList.add('w-full','rounded-none');
        panel.style.maxHeight = '100vh';
        messagesEl.style.maxHeight = 'calc(100vh - 260px)';
        expandBtn.title = 'Collapse';
        document.getElementById('da-expand-icon').innerHTML =
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9L4 4M9 9H4M9 9V4M15 15l5 5m0 0v-5m0 5h-5M9 15l-5 5m5-5H4m5 5v-5M15 9l5-5m0 0h-5m5 0v5"/>';
    }

    function collapsePanel() {
        expanded = false;
        root.classList.add('bottom-5','right-5');
        root.classList.remove('inset-0','bottom-0','right-0');
        panel.classList.add('w-[min(100vw-2rem,22rem)]','rounded-2xl');
        panel.classList.remove('w-full','rounded-none');
        panel.style.maxHeight = 'min(80vh,580px)';
        messagesEl.style.maxHeight = '240px';
        expandBtn.title = 'Expand';
        document.getElementById('da-expand-icon').innerHTML =
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M4 16v4h4M20 8V4h-4M20 16v4h-4"/>';
    }

    // ── intent buttons ───────────────────────────────────────────────────────

    function renderIntents(intents) {
        intentsEl.innerHTML = intents.map(function (item) {
            return '<button type="button" data-intent="' + esc(item.id) + '" data-label-en="' + esc(item.label) + '" data-label-sw="' + esc(item.label_sw || item.label) + '" '
                + 'class="da-intent-btn text-left rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] font-medium text-slate-800 transition hover:border-violet-300 hover:bg-violet-50">'
                + esc(lang === 'sw' ? (item.label_sw || item.label) : item.label)
                + '</button>';
        }).join('');

        intentsEl.querySelectorAll('.da-intent-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (busy) return;
                var label = lang === 'sw'
                    ? (btn.getAttribute('data-label-sw') || btn.getAttribute('data-label-en'))
                    : btn.getAttribute('data-label-en');
                askIntent(btn.getAttribute('data-intent'), label);
            });
        });
    }

    function refreshIntentLabels() {
        intentsEl.querySelectorAll('.da-intent-btn').forEach(function (btn) {
            var label = lang === 'sw'
                ? (btn.getAttribute('data-label-sw') || btn.getAttribute('data-label-en'))
                : btn.getAttribute('data-label-en');
            btn.textContent = label;
        });
    }

    // ── ask intent ───────────────────────────────────────────────────────────

    function askIntent(intentId, label) {
        appendMessage(esc(label), 'user');
        setBusy(true); showTyping();

        (window.axios || axios).post('{{ url('/api/assistant/ask') }}', { intent: intentId }, {
            headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '' }
        }).then(function (res) {
            removeTyping();
            appendMessage(md(res.data.reply || '—'), 'bot');
            appendLinks(res.data.links);
        }).catch(function (err) {
            removeTyping();
            appendMessage(esc((err.response && err.response.data && err.response.data.error) || 'Error. Please try again.'), 'bot');
        }).finally(function () { setBusy(false); });
    }

    // ── send free text ───────────────────────────────────────────────────────

    function sendMessage() {
        var text = input.value.trim();
        if (!text || busy) return;
        input.value = '';

        appendMessage(esc(text), 'user');
        setBusy(true); showTyping();

        (window.axios || axios).post('{{ url('/api/assistant/chat') }}', { message: text }, {
            headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '' }
        }).then(function (res) {
            removeTyping();
            appendMessage(md(res.data.reply || '—'), 'bot');
            appendLinks(res.data.links);
        }).catch(function (err) {
            removeTyping();
            appendMessage(esc((err.response && err.response.data && err.response.data.error) || 'Error. Please try again.'), 'bot');
        }).finally(function () { setBusy(false); input.focus(); });
    }

    // ── report to admin ──────────────────────────────────────────────────────

    reportBtn.addEventListener('click', function () {
        reportForm.style.display = 'block';
        reportText.focus();
    });

    reportCan.addEventListener('click', function () {
        reportForm.style.display = 'none';
        reportText.value = '';
    });

    reportSub.addEventListener('click', function () {
        var msg = reportText.value.trim();
        if (!msg) return;
        reportSub.disabled = true;

        (window.axios || axios).post('{{ url('/api/assistant/report') }}', { message: msg }, {
            headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '' }
        }).then(function () {
            reportForm.style.display = 'none';
            reportText.value = '';
            appendMessage(esc(t('reportThanks')), 'bot');
        }).catch(function () {
            appendMessage(esc('Could not send. Please try again.'), 'bot');
        }).finally(function () { reportSub.disabled = false; });
    });

    // ── load intents on first open ────────────────────────────────────────────

    function loadAssistant() {
        if (loaded) return;
        loaded = true;

        (window.axios || axios).get('{{ url('/api/assistant/intents') }}')
            .then(function (res) {
                appendMessage(md(res.data.welcome || 'Hello! How can I help?'), 'bot');
                renderIntents(res.data.intents || []);
                if (!open) dot.classList.remove('hidden');
            })
            .catch(function () {
                appendMessage('Sign in to use the school guide.', 'bot');
            });
    }

    // ── events ───────────────────────────────────────────────────────────────

    toggle.addEventListener('click', function () {
        if (open) { hidePanel(); }
        else { showPanel(); loadAssistant(); setTimeout(function () { input.focus(); }, 120); }
    });

    closeBtn.addEventListener('click', hidePanel);
    sendBtn.addEventListener('click', sendMessage);

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    expandBtn.addEventListener('click', function () {
        if (expanded) collapsePanel(); else expandPanel();
    });

    langBtn.addEventListener('click', function () {
        lang = lang === 'en' ? 'sw' : 'en';
        applyLang();
        refreshIntentLabels();
    });

    applyLang();
})();
</script>
