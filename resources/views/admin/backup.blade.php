@extends('layouts.admin')

@section('title', 'Backup - RANEY LUBRICANTS TRADING')

@section('content')
    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="mb-7">
        <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">Database backup</h1>
        <p class="mt-1 text-sm text-[var(--muted)]">A complete copy of every table, written to a file you can download and keep.</p>
    </div>

    <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <h2 class="text-base font-bold text-[var(--ink)]">Take a backup now</h2>
                <p class="mt-1.5 text-sm leading-6 text-[var(--muted)]">
                    Dumps the whole database &mdash; products, inventory, orders, payments, accounts and the activity log &mdash;
                    into a single SQL file. Uploaded images and receipts are files on disk and are not included.
                </p>
                <p id="retentionNote" class="mt-2 text-xs text-[var(--muted)]"></p>
            </div>

            <button id="createBtn" type="button" onclick="createBackup()"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                <span id="createBtnLabel">Back up now</span>
            </button>
        </div>
    </section>

    <section class="mt-5 rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-[var(--line)] px-6 py-5">
            <div>
                <h2 class="text-base font-bold text-[var(--ink)]">Saved backups</h2>
                <p id="summary" class="mt-1 text-xs text-[var(--muted)]">Loading...</p>
            </div>
        </div>

        <div id="backupList" class="divide-y divide-[var(--line)]"></div>
    </section>

    <section class="mt-5 rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold text-[var(--ink)]">What restoring does</h2>
        <p class="mt-1.5 text-sm leading-6 text-[var(--muted)]">
            It puts the database back to exactly how it was when that backup was taken. Everything recorded since
            &mdash; orders, payments, stock movements, accounts &mdash; is gone, because the backup does not know
            about any of it.
        </p>
        <p class="mt-3 text-sm leading-6 text-[var(--muted)]">
            A copy of the database as it stands is taken automatically first, so restoring the wrong night can itself
            be undone. Uploaded pictures and receipts are files on disk and are not touched either way.
        </p>
    </section>

    {{-- Typed confirmation rather than a yes/no: this is the one control in
         the back office that destroys work somebody did. --}}
    <div id="restoreModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-900/60 p-4">
        <div class="w-full max-w-md overflow-hidden rounded-[1.5rem] bg-white shadow-2xl">
            <div class="flex items-start gap-4 px-6 pt-6">
                <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-[var(--ink)]">Restore this backup?</h3>
                    <p class="mt-1.5 text-sm leading-6 text-[var(--muted)]">
                        The database goes back to <span id="restoreWhen" class="font-semibold text-[var(--ink)]"></span>.
                        Everything recorded since then is lost.
                    </p>
                    <p id="restoreFile" class="mt-2 break-all font-mono text-xs text-[var(--muted)]"></p>
                </div>
            </div>

            <div class="px-6 pt-5">
                <label for="restoreConfirm" class="block text-sm font-medium text-[var(--ink)]">Type RESTORE to continue</label>
                <input type="text" id="restoreConfirm" autocomplete="off" placeholder="RESTORE"
                       class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm uppercase tracking-widest outline-none transition focus:border-[var(--primary)]">
                <p id="restoreError" class="mt-1.5 hidden text-xs text-red-600"></p>
                <p class="mt-2 text-xs leading-5 text-[var(--muted)]">
                    A copy of the database as it is right now is saved first, so this can be undone.
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-2 border-t border-[var(--line)] px-6 py-4">
                <button type="button" onclick="closeRestore()"
                        class="rounded-xl border border-[var(--line)] px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--surface)]">Cancel</button>
                <button type="button" id="restoreGo" onclick="confirmRestore()"
                        class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-50">Restore it</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    let messageTimeout;

    function showMessage(text, type = 'success') {
        const box = document.getElementById('message');
        const isSuccess = type === 'success';
        box.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">
                    ${isSuccess
                        ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>'
                        : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'}
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${isSuccess ? 'Backup' : 'Action Needed'}</div>
                    <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
                </div>
            </div>
        `;
        box.classList.remove('hidden');
        clearTimeout(messageTimeout);
        messageTimeout = setTimeout(() => box.classList.add('hidden'), 4000);
    }

    /* The filename goes into a data attribute rather than the onclick string,
       so nothing from the server is ever parsed as code. */
    /* Kept so the restore dialog can name the night it would go back to. */
    let loadedBackups = [];

    function renderBackups(backups) {
        loadedBackups = backups;

        const list = document.getElementById('backupList');

        if (!backups.length) {
            list.innerHTML = '<p class="px-6 py-10 text-center text-sm text-[var(--muted)]">No backups yet. Take one using the button above.</p>';
            return;
        }

        list.innerHTML = backups.map((backup, index) => `
            <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="truncate font-mono text-sm font-semibold text-[var(--ink)]">${escapeHtml(backup.filename)}</span>
                        ${index === 0 ? '<span class="shrink-0 rounded-full bg-[var(--primary-soft)] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[var(--primary)]">Latest</span>' : ''}
                    </div>
                    <div class="mt-1 text-xs text-[var(--muted)]">
                        ${escapeHtml(backup.created_label)} &middot; ${escapeHtml(backup.age)} &middot; ${escapeHtml(backup.size)}
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <button type="button" data-filename="${escapeHtml(backup.filename)}" onclick="openRestore(this.dataset.filename)"
                            class="rounded-xl border border-[var(--line)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">
                        Restore
                    </button>
                    <button type="button" data-filename="${escapeHtml(backup.filename)}" onclick="downloadBackup(this.dataset.filename)"
                            class="rounded-xl border border-[var(--line)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">
                        Download
                    </button>
                    <button type="button" data-filename="${escapeHtml(backup.filename)}" onclick="deleteBackup(this.dataset.filename)"
                            class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                        Delete
                    </button>
                </div>
            </div>
        `).join('');
    }

    async function loadBackups() {
        try {
            const response = await fetch('/admin-api/backups', { headers: { Accept: 'application/json' } });
            const payload = await response.json();

            if (!payload.success) throw new Error();

            const { backups, total_bytes: totalBytes, keep } = payload.data;
            const megabytes = (totalBytes / 1048576).toFixed(1);

            document.getElementById('summary').textContent = backups.length
                ? `${backups.length} ${backups.length === 1 ? 'backup' : 'backups'}, ${megabytes} MB in total`
                : 'Nothing saved yet';

            document.getElementById('retentionNote').textContent =
                `The most recent ${keep} backups are kept. Older ones are removed automatically.`;

            renderBackups(backups);
        } catch (error) {
            document.getElementById('summary').textContent = 'Could not read the backup folder.';
            document.getElementById('backupList').innerHTML =
                '<p class="px-6 py-10 text-center text-sm text-red-600">Failed to load backups. Refresh to try again.</p>';
        }
    }

    async function createBackup() {
        const button = document.getElementById('createBtn');
        const label = document.getElementById('createBtnLabel');

        button.disabled = true;
        label.textContent = 'Backing up...';

        try {
            const response = await fetch('/admin-api/backups', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            });

            const payload = await response.json();

            showMessage(
                payload.success ? `Saved ${payload.data.filename} (${payload.data.size}).` : payload.message,
                payload.success ? 'success' : 'error'
            );

            if (payload.success) await loadBackups();
        } catch (error) {
            showMessage('The backup could not be started.', 'error');
        } finally {
            button.disabled = false;
            label.textContent = 'Back up now';
        }
    }

    let restoreTarget = null;

    function openRestore(filename) {
        const backup = loadedBackups.find((b) => b.filename === filename);

        restoreTarget = filename;
        document.getElementById('restoreFile').textContent = filename;
        document.getElementById('restoreWhen').textContent = backup ? backup.created_label : 'when that backup was taken';
        document.getElementById('restoreConfirm').value = '';
        document.getElementById('restoreError').classList.add('hidden');

        const modal = document.getElementById('restoreModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('restoreConfirm').focus();
    }

    function closeRestore() {
        const modal = document.getElementById('restoreModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        restoreTarget = null;
    }

    async function confirmRestore() {
        const typed = document.getElementById('restoreConfirm').value.trim().toUpperCase();
        const error = document.getElementById('restoreError');

        if (typed !== 'RESTORE') {
            error.textContent = 'Type RESTORE exactly, to show this is deliberate.';
            error.classList.remove('hidden');
            return;
        }

        const button = document.getElementById('restoreGo');
        button.disabled = true;
        button.textContent = 'Restoring...';

        try {
            const response = await fetch('/admin-api/backups/restore', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify({ filename: restoreTarget, confirm: 'RESTORE' }),
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                error.textContent = payload.message || 'The restore could not be completed.';
                error.classList.remove('hidden');
                return;
            }

            closeRestore();
            showMessage(payload.message);
            loadBackups();
        } catch (e) {
            error.textContent = 'The restore could not be completed. Nothing has been changed.';
            error.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Restore it';
        }
    }

    function downloadBackup(filename) {
        window.location.href = `/admin-api/backups/${encodeURIComponent(filename)}/download`;
    }

    async function deleteBackup(filename) {
        const sure = await askToConfirm({
            title: 'Delete this backup?',
            body: `${filename} will be removed from the server. There is no undoing it, and if it is the only copy the data it holds goes with it.`,
            confirm: 'Delete it',
            cancel: 'Keep it',
        });

        if (!sure) return;

        try {
            const response = await fetch(`/admin-api/backups/${encodeURIComponent(filename)}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            });

            const payload = await response.json();
            showMessage(payload.message, payload.success ? 'success' : 'error');

            if (payload.success) await loadBackups();
        } catch (error) {
            showMessage('The backup could not be deleted.', 'error');
        }
    }

    loadBackups();
</script>
@endpush
