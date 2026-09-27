@extends('layouts.bare')

@section('title', 'Customer Login - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)] px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="/shop">
                    <div class="text-3xl font-black tracking-tight sm:text-4xl">
                        <span class="text-[var(--primary)]">RANEY</span>
                        <span class="text-[var(--accent)]"> LUBRICANTS</span>
                    </div>
                    <div class="mt-1 text-[11px] uppercase tracking-[0.32em] text-[var(--muted)]">Trading</div>
                </a>
            </div>
            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm sm:p-8">
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-[var(--ink)]">Customer Login</h2>
                    <p class="mt-2 text-sm font-semibold tracking-[0.2em] text-[var(--muted)]">RANEY LUBRICANTS TRADING</p>
                </div>
                @if (session('status'))
                    <div class="mt-6 rounded-2xl border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-3.5 text-sm leading-6 text-[var(--primary)]">
                        {{ session('status') }}
                    </div>
                @endif

                <div id="error-message" class="hidden mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700"></div>
                <form id="loginForm" class="mt-8 space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Email</label>
                            <input id="email" name="email" type="email" required maxlength="150" autocomplete="username"
                                   class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]"
                                   placeholder="you@example.com">
                        </div>
                        <div>
                            <div class="mb-2 flex items-baseline justify-between gap-3">
                                <label class="block text-sm font-medium text-[var(--ink)]">Password</label>
                                <a href="/shop/forgot-password" class="text-xs font-semibold text-[var(--primary)] hover:underline">Forgot password?</a>
                            </div>
                            <div class="relative">
                                <input id="password" name="password" type="password" required minlength="6" maxlength="255"
                                       class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 pr-12 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]"
                                       placeholder="Enter your password">
                                {{-- Shows what was typed. Worth having anywhere,
                                     and worth more here: the password is printed
                                     on this very page, and somebody whose browser
                                     autofilled a different one has no way to see
                                     that is what happened. --}}
                                <button type="button" onclick="togglePassword(this)" aria-label="Show password"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-[var(--muted)] transition hover:text-[var(--ink)]">
                                    <svg class="eye-open h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5s8.58 3.01 9.96 7.18a1 1 0 0 1 0 .64C20.58 16.49 16.64 19.5 12 19.5s-8.58-3.01-9.96-7.18Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    <svg class="eye-shut hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 0 0 2.04 11.68a1 1 0 0 0 0 .64C3.42 16.49 7.36 19.5 12 19.5c.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0 1 12 4.5c4.64 0 8.58 3.01 9.96 7.18a1 1 0 0 1 0 .64 10.52 10.52 0 0 1-4.29 5.45M6.23 6.23 3 3m3.23 3.23 3.65 3.65m7.89 7.89L21 21m-3.23-3.23-3.65-3.65m0 0a3 3 0 1 1-4.24-4.24m4.24 4.24L9.88 9.88"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    @if (session('error'))
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">
                            {{ session('error') }}
                        </div>
                    @endif

                    <button type="submit" class="flex w-full justify-center rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-110">Sign in</button>

                    @include('partials.google-button')
                    {{-- Shown to everybody the link is given to, on purpose.

                         The system is demonstrated by sharing the running
                         server, and a survey respondent who has to register and
                         confirm an email before seeing anything is a respondent
                         who closes the tab. This account is seeded with an order
                         history so there is something to look at.

                         Still gated on APP_ENV, so a real deployment drops it. --}}
                    @if (app()->environment('local'))
                        <div class="rounded-2xl border border-[var(--line)] bg-white/70 px-4 py-3 text-xs text-[var(--muted)]">
                            <div class="mb-2 text-center font-semibold uppercase tracking-[0.2em] text-[var(--accent)]">Demo account &mdash; sign in with this</div>
                            <div class="flex justify-between gap-3 py-0.5">
                                <span>Email</span>
                                <span class="select-all font-semibold text-[var(--ink)]">john@example.com</span>
                            </div>
                            <div class="flex justify-between gap-3 py-0.5">
                                <span>Password</span>
                                <span class="select-all font-semibold text-[var(--ink)]">customer123</span>
                            </div>
                            <button type="button" onclick="fillDemoAccount('john@example.com', 'customer123')"
                                    class="mt-3 w-full rounded-lg border border-[var(--primary)] px-3 py-2 text-xs font-bold text-[var(--primary)] transition hover:bg-[var(--primary-soft)]">
                                Fill this in for me
                            </button>
                        </div>
                    @endif
                </form>
                <div class="mt-6 text-center">
                    <a href="/shop" class="text-sm font-semibold text-[var(--primary)] hover:underline">Continue as Guest</a>
                </div>
                <div class="mt-3 text-center text-sm text-[var(--muted)]">
                    New customer? <a href="/shop/register" class="font-semibold text-[var(--primary)] hover:underline">Create an account</a>
                </div>

            </section>
            <p class="mt-6 text-center text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.</p>
        </div>
    </div>

@endsection

@push('scripts')
<script>
        /** Swaps the field between hidden and readable, and the icon with it. */
        function togglePassword(button) {
            const field = button.parentElement.querySelector('input');
            const showing = field.type === 'text';

            field.type = showing ? 'password' : 'text';
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            button.querySelector('.eye-open').classList.toggle('hidden', !showing);
            button.querySelector('.eye-shut').classList.toggle('hidden', showing);
            field.focus();
        }


    {{-- One tap rather than a retype.

         The browser saves a password per origin, and a demonstration is
         reached from several -- localhost, 127.0.0.1, the tunnel -- so
         autofill cheerfully puts an old one into a form whose correct
         password is printed directly underneath it. Somebody then reads the
         right password off the page, watches the system refuse it, and
         concludes the system is broken.

         This writes the pair into the fields and fires the events a listener
         would expect, replacing whatever autofill put there. --}}
    function fillDemoAccount(email, password) {
        const fields = { email: document.getElementById('email'), password: document.getElementById('password') };

        for (const [key, field] of Object.entries(fields)) {
            if (!field) continue;
            field.value = key === 'email' ? email : password;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }

        fields.password?.focus();
    }

        const params = new URLSearchParams(window.location.search);

        if (params.get('registered') === '1') {
            const errorDiv = document.getElementById('error-message');
            errorDiv.textContent = 'Registration successful. You can now sign in.';
            errorDiv.className = 'mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700';
            errorDiv.classList.remove('hidden');
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const response = await fetch('/shop/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    email: document.getElementById('email').value.trim(),
                    password: document.getElementById('password').value
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                window.location.href = '/shop';
                return;
            }

            const errorDiv = document.getElementById('error-message');
            errorDiv.textContent = data.message || 'Login failed.';
            errorDiv.classList.remove('hidden');
        });
</script>
@endpush
