{{--
    The storefront assistant.

    Included once from the customer layout, so it survives navigation between
    shop pages. The conversation itself lives on the server; this only draws
    what it is given and posts what is typed.
--}}
<div id="chatWidget" class="no-print">
    <button type="button" id="chatLauncher" onclick="chatToggle()" aria-label="Open the assistant" aria-expanded="false"
            class="fixed bottom-6 right-6 z-[60] flex h-14 w-14 items-center justify-center rounded-full bg-[var(--primary)] text-white shadow-2xl transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[var(--primary)]/30">
        <svg id="chatLauncherOpen" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m-5 6.25-3.2 2.4A.75.75 0 0 1 3.6 22.1V18.5A4.5 4.5 0 0 1 2 15V7a4 4 0 0 1 4-4h12a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H8Z"/>
        </svg>
        <svg id="chatLauncherClose" class="hidden h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
        </svg>
        <span id="chatDot" class="absolute right-0 top-0 h-3.5 w-3.5 rounded-full border-2 border-white bg-[var(--accent)]"></span>
    </button>

    {{-- Full screen on a phone, a floating card from small upwards. --}}
    <section id="chatPanel" aria-label="Assistant"
             class="fixed inset-0 z-[59] hidden flex-col bg-white sm:inset-auto sm:bottom-24 sm:right-6 sm:h-[32rem] sm:max-h-[calc(100vh-8rem)] sm:w-[23rem] sm:rounded-[1.25rem] sm:border sm:border-[var(--line)] sm:shadow-2xl">

        <header class="flex shrink-0 items-center gap-3 bg-[var(--primary)] px-4 py-3 sm:rounded-t-[1.25rem]">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75A2.25 2.25 0 0 1 11.25 6v1.5h1.5V6A2.25 2.25 0 0 1 15 3.75M7.5 21h9a2.25 2.25 0 0 0 2.25-2.25v-7.5A2.25 2.25 0 0 0 16.5 9h-9a2.25 2.25 0 0 0-2.25 2.25v7.5A2.25 2.25 0 0 0 7.5 21Zm2.25-6.75h.008v.008H9.75v-.008Zm4.5 0h.008v.008h-.008v-.008Z"/>
                </svg>
            </span>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-bold text-white">Raney assistant</div>
                <div class="text-[11px] text-white/70">Answers instantly</div>
            </div>
            <button type="button" onclick="chatReset()" aria-label="Start a new conversation"
                    class="rounded-lg p-1.5 text-white/70 transition hover:bg-white/10 hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-.582 4.992a8.25 8.25 0 1 0-1.664 4.5"/>
                </svg>
            </button>
            <button type="button" onclick="chatToggle()" aria-label="Close the assistant"
                    class="rounded-lg p-1.5 text-white/70 transition hover:bg-white/10 hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <div id="chatLog" class="flex-1 space-y-3 overflow-y-auto bg-[var(--surface)] px-3.5 py-4" aria-live="polite"></div>

        <form id="chatForm" class="flex shrink-0 items-center gap-2 border-t border-[var(--line)] bg-white px-3 py-2.5 sm:rounded-b-[1.25rem]" onsubmit="chatSubmit(event)">
            <input id="chatInput" type="text" maxlength="500" autocomplete="off"
                   placeholder="Ask about an order or a product"
                   class="min-w-0 flex-1 rounded-xl border border-transparent bg-[var(--surface)] px-3 py-2.5 text-sm text-[var(--ink)] outline-none transition focus:border-[var(--primary)] focus:bg-white">
            <button type="submit" id="chatSend" aria-label="Send"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[var(--primary)] text-white transition hover:brightness-110 disabled:opacity-40">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.77 59.77 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5"/>
                </svg>
            </button>
        </form>
    </section>
</div>

@push('scripts')
<script>
    /* The toast on these pages also lives in the bottom-right corner. It is
       lifted above the launcher while the widget is mounted, so an "Added to
       cart" message never lands underneath it. */
    (function liftToast() {
        const toast = document.getElementById('message');
        if (toast) toast.style.bottom = '6rem';
    })();

    const CHAT_OPEN_KEY = 'raney.chat.open';
    let chatLoaded = false;
    let chatBusy = false;

    function chatEl(id) {
        return document.getElementById(id);
    }

    function chatToggle(force) {
        const panel = chatEl('chatPanel');
        const open = force !== undefined ? force : panel.classList.contains('hidden');

        panel.classList.toggle('hidden', !open);
        panel.classList.toggle('flex', open);
        chatEl('chatLauncherOpen').classList.toggle('hidden', open);
        chatEl('chatLauncherClose').classList.toggle('hidden', !open);
        chatEl('chatLauncher').setAttribute('aria-expanded', String(open));
        chatEl('chatDot').classList.toggle('hidden', open);

        try {
            localStorage.setItem(CHAT_OPEN_KEY, open ? '1' : '0');
        } catch (error) {
            /* Private browsing refuses storage; the widget works without it. */
        }

        if (open) {
            if (!chatLoaded) chatLoad();
            if (window.matchMedia('(min-width: 640px)').matches) chatEl('chatInput').focus();
        }
    }

    function chatBubble(message) {
        const mine = message.role === 'user';
        const payload = message.payload || {};

        const body = `
            <div class="flex ${mine ? 'justify-end' : 'justify-start'}">
                <div class="max-w-[85%] whitespace-pre-line rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed ${mine
                    ? 'rounded-br-md bg-[var(--primary)] text-white'
                    : 'rounded-bl-md border border-[var(--line)] bg-white text-[var(--ink)]'}">${escapeHtml(message.body)}</div>
            </div>`;

        const products = (payload.products || []).map((product) => `
            <a href="${escapeHtml(product.url)}" class="flex items-center gap-2.5 rounded-xl border border-[var(--line)] bg-white p-2 transition hover:border-[var(--primary)]">
                ${product.image
                    ? `<img src="${escapeHtml(product.image)}" alt="" class="h-11 w-11 shrink-0 rounded-lg object-cover">`
                    : `<span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-[var(--surface)]">
                           <svg class="h-5 w-5 text-[var(--muted)]" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                       </span>`}
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-xs font-semibold text-[var(--ink)]">${escapeHtml(product.name)}</span>
                    <span class="block text-[11px] text-[var(--muted)]">${escapeHtml(product.price)} · ${product.stock} in stock</span>
                </span>
            </a>`).join('');

        const link = payload.link
            ? `<a href="${escapeHtml(payload.link.url)}" class="inline-flex items-center gap-1 text-xs font-bold text-[var(--primary)] hover:underline">
                   ${escapeHtml(payload.link.label)}
                   <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
               </a>`
            : '';

        /* Values ride in data attributes rather than an onclick string, so
           nothing from the server is ever parsed as code. */
        const chips = (payload.chips || []).map((chip) => `
            <button type="button" data-value="${escapeHtml(chip.value)}" data-intent="${escapeHtml(chip.intent || '')}"
                    onclick="chatChip(this)"
                    class="rounded-full border border-[var(--primary)] bg-[var(--primary-soft)] px-3 py-1.5 text-xs font-semibold text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                ${escapeHtml(chip.label)}
            </button>`).join('');

        const extras = [products, link, chips].filter(Boolean).length
            ? `<div class="mt-2 flex flex-col gap-2 pl-1">
                   ${products ? `<div class="flex flex-col gap-1.5">${products}</div>` : ''}
                   ${link}
                   ${chips ? `<div class="flex flex-wrap gap-1.5">${chips}</div>` : ''}
               </div>`
            : '';

        return body + extras;
    }

    function chatRender(messages, append = false) {
        const log = chatEl('chatLog');
        const html = messages.map(chatBubble).join('');

        if (append) {
            log.insertAdjacentHTML('beforeend', html);
        } else {
            log.innerHTML = html;
        }

        log.scrollTop = log.scrollHeight;
    }

    function chatTyping(on) {
        const existing = chatEl('chatTyping');
        if (existing) existing.remove();
        if (!on) return;

        chatEl('chatLog').insertAdjacentHTML('beforeend', `
            <div id="chatTyping" class="flex justify-start">
                <div class="flex gap-1 rounded-2xl rounded-bl-md border border-[var(--line)] bg-white px-3.5 py-3">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[var(--muted)]" style="animation-delay:0ms"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[var(--muted)]" style="animation-delay:150ms"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[var(--muted)]" style="animation-delay:300ms"></span>
                </div>
            </div>`);

        chatEl('chatLog').scrollTop = chatEl('chatLog').scrollHeight;
    }

    async function chatLoad() {
        try {
            const response = await fetch('/shop-api/chat', { headers: { Accept: 'application/json' } });
            const payload = await response.json();

            chatRender(payload.data.messages);
            chatLoaded = true;
        } catch (error) {
            chatEl('chatLog').innerHTML =
                '<p class="rounded-xl bg-white p-4 text-center text-xs text-[var(--muted)]">The assistant is unavailable right now.</p>';
        }
    }

    async function chatAsk(message, intent) {
        if (chatBusy || !message.trim()) return;

        chatBusy = true;
        chatEl('chatSend').disabled = true;

        /* Shown immediately, so the conversation does not appear to stall
           while the round trip happens. */
        chatRender([{ role: 'user', body: message, payload: {} }], true);
        chatTyping(true);

        try {
            const response = await fetch('/shop-api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ message, intent: intent || null }),
            });

            const payload = await response.json();
            chatTyping(false);

            if (!payload.success) {
                throw new Error(payload.message || 'failed');
            }

            /* The echoed visitor message is dropped: it is already on screen. */
            chatRender(payload.data.messages.filter((m) => m.role !== 'user'), true);
        } catch (error) {
            chatTyping(false);
            chatRender([{ role: 'bot', body: 'Sorry, I could not answer that just now. Please try again.', payload: {} }], true);
        } finally {
            chatBusy = false;
            chatEl('chatSend').disabled = false;
        }
    }

    function chatChip(button) {
        chatAsk(button.dataset.value, button.dataset.intent || null);
    }

    function chatSubmit(event) {
        event.preventDefault();

        const input = chatEl('chatInput');
        const message = input.value;

        input.value = '';
        chatAsk(message, null);
    }

    async function chatReset() {
        if (!confirm('Start a new conversation? This clears what is on screen.')) return;

        try {
            const response = await fetch('/shop-api/chat/reset', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            });

            const payload = await response.json();
            chatRender(payload.data.messages);
        } catch (error) {
            /* Leaving what is on screen is a reasonable failure here. */
        }
    }

    /* Reopen where the visitor left it, but never open by itself on a first
       visit: an uninvited panel over the catalogue is an annoyance. */
    (function restoreChat() {
        let wasOpen = false;

        try {
            wasOpen = localStorage.getItem(CHAT_OPEN_KEY) === '1';
        } catch (error) {
            wasOpen = false;
        }

        if (wasOpen) chatToggle(true);
    })();
</script>
@endpush
