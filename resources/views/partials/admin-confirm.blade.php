{{-- One dialog for every "are you sure" in the back office.

     The browser's own confirm() box carries the address of the site and
     none of its typography, which reads as though the page has been
     hijacked rather than as the system asking a question. It also blocks
     the page while it is open, and cannot say what is about to happen in
     more than one flat line. --}}
<div id="confirmModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-900/50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="w-full max-w-md overflow-hidden rounded-[1.5rem] bg-white shadow-2xl">
        <div class="flex items-start gap-4 px-6 pt-6">
            <span id="confirmIcon" class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full"></span>
            <div class="min-w-0">
                <h3 id="confirmTitle" class="text-base font-bold text-[var(--ink)]"></h3>
                <p id="confirmBody" class="mt-1.5 text-sm leading-6 text-[var(--muted)]"></p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2 border-t border-[var(--line)] px-6 py-4">
            <button type="button" id="confirmNo"
                    class="rounded-xl border border-[var(--line)] px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--surface)]"></button>
            <button type="button" id="confirmYes"
                    class="rounded-xl px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110"></button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    /**
     * Ask before doing something that cannot be taken back.
     *
     * Resolves true or false, so a caller reads as
     * `if (!await askToConfirm({...})) return;` -- the same shape the
     * browser's confirm() had, without the browser's dialog.
     */
    window.askToConfirm = function ({ title, body = '', confirm = 'Confirm', cancel = 'Cancel', tone = 'danger' }) {
        const modal = document.getElementById('confirmModal');
        const yes = document.getElementById('confirmYes');
        const no = document.getElementById('confirmNo');
        const icon = document.getElementById('confirmIcon');

        const tones = {
            danger: {
                button: 'bg-red-600',
                chip: 'bg-red-100 text-red-700',
                path: 'M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            },
            primary: {
                button: 'bg-[var(--primary)]',
                chip: 'bg-[var(--primary-soft)] text-[var(--primary)]',
                path: 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
            },
        };

        const chosen = tones[tone] || tones.danger;

        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmBody').textContent = body;
        yes.textContent = confirm;
        no.textContent = cancel;
        yes.className = 'rounded-xl px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110 ' + chosen.button;
        icon.className = 'flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full ' + chosen.chip;
        icon.innerHTML = `<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${chosen.path}"/></svg>`;

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Cancel holds the focus: the dangerous button should never be the
        // one a stray return key presses.
        no.focus();

        return new Promise((resolve) => {
            const close = (answer) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');

                yes.removeEventListener('click', onYes);
                no.removeEventListener('click', onNo);
                modal.removeEventListener('click', onBackdrop);
                document.removeEventListener('keydown', onKey);

                resolve(answer);
            };

            const onYes = () => close(true);
            const onNo = () => close(false);
            const onBackdrop = (event) => { if (event.target === modal) close(false); };
            const onKey = (event) => { if (event.key === 'Escape') close(false); };

            yes.addEventListener('click', onYes);
            no.addEventListener('click', onNo);
            modal.addEventListener('click', onBackdrop);
            document.addEventListener('keydown', onKey);
        });
    };
</script>
@endpush
