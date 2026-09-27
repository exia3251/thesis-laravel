@extends('layouts.bare')

@section('title', 'Admin Login - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)] px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">

            <div class="mb-8 text-center">
                <div class="text-3xl font-black tracking-tight sm:text-4xl">
                    <span class="text-[var(--primary)]">RANEY</span>
                    <span class="text-[var(--accent)]"> LUBRICANTS</span>
                </div>
                <div class="mt-1 text-[11px] uppercase tracking-[0.32em] text-[var(--muted)]">Trading</div>
            </div>

            <section class="rounded-[2rem] border border-[var(--line)] bg-[var(--card)] p-8 shadow-2xl backdrop-blur">
                <div class="text-center">
                    <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Admin Access</div>
                    <h2 class="mt-4 text-3xl font-extrabold text-[var(--ink)]">Admin Login</h2>
                    <p class="mt-2 text-sm font-semibold tracking-[0.2em] text-[var(--muted)]">RANEY LUBRICANTS TRADING</p>
                </div>

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
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Password</label>
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

                    <button type="submit" class="flex w-full justify-center rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-110">
                        Sign in
                    </button>

                    {{-- Shown to everybody the link is given to, on purpose, so
                         somebody reviewing the system can see the staff side
                         without being issued an account first.

                         These are full staff logins and the admin one can do
                         anything: edit the catalogue, read every customer, take
                         a backup. Whoever holds the link holds the system, which
                         is the trade being made for a demonstration that needs
                         no setup. Take the link down when the survey closes.

                         Still gated on APP_ENV, so a real deployment drops it. --}}
                    @if (app()->environment('local'))
                        <div class="rounded-2xl border border-[var(--line)] bg-white/70 px-4 py-3 text-center text-xs text-[var(--muted)]">
                            <div class="mb-1 font-semibold uppercase tracking-[0.2em] text-[var(--accent)]">Demo accounts &mdash; sign in with any of these</div>
                            <div class="space-y-1.5">
                                @foreach ([
                                    ['Administrator', 'admin@raney.test', 'admin123'],
                                    ['Inventory', 'inventory@raney.test', 'inventory123'],
                                    ['Accounting', 'accounting@raney.test', 'accounting123'],
                                ] as [$label, $email, $password])
                                    <button type="button" onclick="fillDemoAccount('{{ $email }}', '{{ $password }}')"
                                            class="flex w-full items-center justify-between gap-3 rounded-lg border border-[var(--line)] px-3 py-2 text-left transition hover:border-[var(--primary)] hover:bg-[var(--primary-soft)]">
                                        <span class="font-bold text-[var(--ink)]">{{ $label }}</span>
                                        <span class="text-[11px] text-[var(--muted)]">{{ $email }} / {{ $password }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-2 text-[11px] text-[var(--muted)]">Tap one to fill the form.</p>
                        </div>
                    @endif
                </form>
            </section>

            <p class="mt-5 text-center text-sm text-[var(--muted)]">
                Not staff? <a href="/shop/login" class="font-semibold text-[var(--primary)] hover:underline">Customer sign-in</a>
            </p>

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


        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error-message');

            errorDiv.classList.add('hidden');

            try {
                const response = await fetch('/admin/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Staff roles do not share a landing page.
                    window.location.href = data.data?.redirect || '/admin/dashboard';
                    return;
                }

                errorDiv.textContent = data.message || 'Login failed. Please try again.';
                errorDiv.classList.remove('hidden');
            } catch (error) {
                errorDiv.textContent = 'Login failed. Please try again.';
                errorDiv.classList.remove('hidden');
            }
        });
</script>
@endpush
