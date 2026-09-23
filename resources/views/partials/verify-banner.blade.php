{{-- Shown once, where the nag used to be, so confirming has visible
     consequence rather than silently dropping someone back on the shop. --}}
@if (session('verified'))
    <div class="border-b border-emerald-200 bg-emerald-50 no-print">
        <div class="container mx-auto flex items-start gap-3 px-4 py-3 sm:px-6">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
            <p class="text-sm leading-6 text-emerald-900">{{ session('verified') }}</p>
        </div>
    </div>
@endif

@auth
    @if (auth()->user()->isCustomer() && !auth()->user()->hasVerifiedEmail())
        {{-- Ordering is blocked until this is done, so the prompt follows the
             customer around rather than hiding on one page. --}}
        <div id="verifyBanner" class="border-b border-amber-200 bg-amber-50 no-print">
            <div class="container mx-auto flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                    <p class="text-sm leading-6 text-amber-900">
                        Confirm <strong>{{ auth()->user()->email }}</strong> to place orders. We sent you a link when you registered.
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <span id="verifyBannerNote" class="hidden text-xs font-semibold text-amber-800"></span>
                    <button type="button" onclick="resendVerification(this)" class="rounded-xl border border-amber-300 bg-white px-4 py-2 text-xs font-bold text-amber-900 transition hover:bg-amber-100">
                        Resend link
                    </button>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            async function resendVerification(button) {
                const note = document.getElementById('verifyBannerNote');
                const original = button.textContent;

                button.disabled = true;
                button.textContent = 'Sending...';

                try {
                    const response = await fetch('/email/verify/resend', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    });

                    const data = await response.json();
                    note.textContent = data.message || (response.ok ? 'Link sent.' : 'Could not send the link.');
                    note.classList.remove('hidden');
                    note.classList.toggle('text-amber-800', response.ok);
                    note.classList.toggle('text-red-700', !response.ok);
                } finally {
                    button.disabled = false;
                    button.textContent = original;
                }
            }
        </script>
        @endpush
    @endif
@endauth
