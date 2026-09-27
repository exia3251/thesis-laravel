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
                            <input id="password" name="password" type="password" required minlength="6" maxlength="255"
                                   class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]"
                                   placeholder="Enter your password">
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
                            Administrator: <span class="select-all font-semibold text-[var(--ink)]">admin@raney.test</span> / <span class="select-all font-semibold text-[var(--ink)]">admin123</span><br>
                            Inventory: <span class="select-all font-semibold text-[var(--ink)]">inventory@raney.test</span> / <span class="select-all font-semibold text-[var(--ink)]">inventory123</span><br>
                            Accounting: <span class="select-all font-semibold text-[var(--ink)]">accounting@raney.test</span> / <span class="select-all font-semibold text-[var(--ink)]">accounting123</span>
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
