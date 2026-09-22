@extends('layouts.admin')

@section('title', 'Assistant - RANEY LUBRICANTS TRADING')

@section('content')
    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="mb-7">
        <h1 class="text-3xl font-black tracking-tight text-[var(--ink)]">Assistant</h1>
        <p class="mt-1 text-sm text-[var(--muted)]">What the shop assistant says, and what it could not answer.</p>
    </div>

    <section id="statCards" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"></section>

    <section class="mt-5 rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)] px-6 py-5">
            <div>
                <h2 class="text-base font-bold text-[var(--ink)]">Questions it could not answer</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Most asked first. Each one is a keyword worth adding below.</p>
            </div>
            <select id="unansweredRange" onchange="loadUnanswered()"
                    class="rounded-xl border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--ink)] outline-none focus:border-[var(--primary)]">
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90" selected>Last 90 days</option>
            </select>
        </div>
        <div id="unansweredList" class="divide-y divide-[var(--line)]"></div>
    </section>

    <section class="mt-5 rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)] px-6 py-5">
            <div>
                <h2 class="text-base font-bold text-[var(--ink)]">What it knows</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Entries marked <span class="font-semibold text-amber-700">Needs your text</span> still hold placeholder wording.</p>
            </div>
        </div>
        <div id="intentList" class="divide-y divide-[var(--line)]"></div>
    </section>

    <div id="intentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-[1.5rem] bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-start justify-between gap-3">
                <div>
                    <h3 id="modalTitle" class="text-lg font-bold text-[var(--ink)]">Edit entry</h3>
                    <p id="modalKey" class="mt-0.5 font-mono text-xs text-[var(--muted)]"></p>
                </div>
                <button type="button" onclick="closeIntent()" class="rounded-lg p-1.5 text-[var(--muted)] transition hover:bg-[var(--surface)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div id="modalErrors" class="mb-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

            <form id="intentForm" class="space-y-4" onsubmit="saveIntent(event)">
                <input type="hidden" id="intentId">

                <div>
                    <label class="block text-sm font-semibold text-[var(--ink)]">Shown on the suggestion button</label>
                    <input type="text" id="intentLabel" maxlength="120" required
                           class="mt-1.5 w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm outline-none focus:border-[var(--primary)]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[var(--ink)]">Trigger words</label>
                    <p class="mt-0.5 text-xs text-[var(--muted)]">Separated by spaces. Add the words customers actually use, including misspellings and Filipino.</p>
                    <textarea id="intentKeywords" rows="3" maxlength="1000" required
                              class="mt-1.5 w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm outline-none focus:border-[var(--primary)]"></textarea>
                </div>

                <div id="answerField">
                    <label class="block text-sm font-semibold text-[var(--ink)]">Answer</label>
                    <p class="mt-0.5 text-xs text-[var(--muted)]">Use <code class="rounded bg-[var(--surface)] px-1">:down_payment_percent</code> and <code class="rounded bg-[var(--surface)] px-1">:minimum_extra_payment</code> to quote the live payment rules.</p>
                    <textarea id="intentAnswer" rows="8" maxlength="4000"
                              class="mt-1.5 w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm leading-relaxed outline-none focus:border-[var(--primary)]"></textarea>
                </div>

                <div id="handlerNote" class="hidden rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                    This one looks the answer up in the database when it is asked, so it has no fixed text to edit. You can still change its button wording and trigger words.
                </div>

                <div class="flex flex-wrap gap-5 pt-1">
                    <label class="flex items-center gap-2 text-sm font-medium text-[var(--ink)]">
                        <input type="checkbox" id="intentActive" class="h-4 w-4 rounded border-[var(--line)]"> Active
                    </label>
                    <label class="flex items-center gap-2 text-sm font-medium text-[var(--ink)]">
                        <input type="checkbox" id="intentSuggested" class="h-4 w-4 rounded border-[var(--line)]"> Offer as an opening suggestion
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-[var(--line)] pt-5">
                    <button type="button" onclick="closeIntent()" class="rounded-xl border border-[var(--line)] px-5 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--surface)]">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let intents = [];
    let messageTimeout;

    function showMessage(text, type = 'success') {
        const box = document.getElementById('message');
        const isSuccess = type === 'success';
        box.innerHTML = `<div class="text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>`;
        box.classList.remove('hidden');
        clearTimeout(messageTimeout);
        messageTimeout = setTimeout(() => box.classList.add('hidden'), 3000);
    }

    function card(label, value, note) {
        return `
            <div class="rounded-[1.25rem] border border-[var(--line)] bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-[var(--muted)]">${escapeHtml(label)}</div>
                <div class="mt-3 text-3xl font-black tracking-tight text-[var(--ink)]">${escapeHtml(String(value))}</div>
                <div class="mt-1 text-xs text-[var(--muted)]">${escapeHtml(note)}</div>
            </div>`;
    }

    async function loadUnanswered() {
        const days = document.getElementById('unansweredRange').value;
        const response = await fetch(`/admin-api/assistant/unanswered?days=${days}`, { headers: { Accept: 'application/json' } });
        if (response.status === 401) { window.location.href = '/admin/login'; return; }

        const { data } = await response.json();

        document.getElementById('statCards').innerHTML =
            card('Conversations', data.conversations, 'in this period') +
            card('Questions asked', data.asked, 'by customers') +
            card('Answered', data.answered_percent === null ? '—' : data.answered_percent + '%', 'matched to an entry') +
            card('Unanswered', data.missed, 'worth adding below');

        document.getElementById('unansweredList').innerHTML = data.questions.length
            ? data.questions.map((row) => `
                <div class="flex items-center justify-between gap-4 px-6 py-3.5">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-medium text-[var(--ink)]">${escapeHtml(row.question)}</div>
                        <div class="text-xs text-[var(--muted)]">last asked ${escapeHtml(row.last_asked)}</div>
                    </div>
                    <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">${row.times}&times;</span>
                </div>`).join('')
            : '<p class="px-6 py-10 text-center text-sm text-[var(--muted)]">Nothing went unanswered in this period.</p>';
    }

    async function loadIntents() {
        const response = await fetch('/admin-api/assistant/intents', { headers: { Accept: 'application/json' } });
        if (response.status === 401) { window.location.href = '/admin/login'; return; }

        const { data } = await response.json();
        intents = data.intents;

        document.getElementById('intentList').innerHTML = intents.map((intent) => `
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-[var(--ink)]">${escapeHtml(intent.label)}</span>
                        <span class="rounded-full bg-[var(--surface)] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[var(--muted)]">${escapeHtml(intent.category)}</span>
                        ${intent.is_placeholder ? '<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">Needs your text</span>' : ''}
                        ${intent.handler ? '<span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-800">Live lookup</span>' : ''}
                        ${!intent.is_active ? '<span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700">Off</span>' : ''}
                    </div>
                    <div class="mt-1 truncate text-xs text-[var(--muted)]">${escapeHtml(intent.keywords)}</div>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <span class="text-xs text-[var(--muted)]">${intent.uses} ${intent.uses === 1 ? 'use' : 'uses'}</span>
                    <button type="button" data-id="${intent.intent_id}" onclick="openIntent(this.dataset.id)"
                            class="rounded-xl border border-[var(--line)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Edit</button>
                </div>
            </div>`).join('');
    }

    function openIntent(id) {
        const intent = intents.find((item) => String(item.intent_id) === String(id));
        if (!intent) return;

        document.getElementById('intentId').value = intent.intent_id;
        document.getElementById('modalTitle').textContent = intent.label;
        document.getElementById('modalKey').textContent = intent.intent_key;
        document.getElementById('intentLabel').value = intent.label;
        document.getElementById('intentKeywords').value = intent.keywords;
        document.getElementById('intentAnswer').value = intent.answer || '';
        document.getElementById('intentActive').checked = !!intent.is_active;
        document.getElementById('intentSuggested').checked = !!intent.is_suggested;

        // A live lookup builds its own reply, so there is no text to edit.
        const isHandler = !!intent.handler;
        document.getElementById('answerField').classList.toggle('hidden', isHandler);
        document.getElementById('handlerNote').classList.toggle('hidden', !isHandler);
        document.getElementById('modalErrors').classList.add('hidden');

        const modal = document.getElementById('intentModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeIntent() {
        const modal = document.getElementById('intentModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function saveIntent(event) {
        event.preventDefault();

        const id = document.getElementById('intentId').value;

        const response = await fetch(`/admin-api/assistant/intents/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                label: document.getElementById('intentLabel').value,
                keywords: document.getElementById('intentKeywords').value,
                answer: document.getElementById('intentAnswer').value,
                is_active: document.getElementById('intentActive').checked,
                is_suggested: document.getElementById('intentSuggested').checked,
            }),
        });

        const payload = await response.json();

        if (!response.ok) {
            const errors = payload.errors ? Object.values(payload.errors).flat() : [payload.message || 'Could not save.'];
            const box = document.getElementById('modalErrors');
            box.innerHTML = errors.map(escapeHtml).join('<br>');
            box.classList.remove('hidden');
            return;
        }

        closeIntent();
        showMessage(payload.message || 'Saved.');
        await loadIntents();
    }

    loadUnanswered();
    loadIntents();
</script>
@endpush
