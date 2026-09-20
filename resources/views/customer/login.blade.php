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
            <section class="rounded-[2rem] border border-[var(--line)] bg-[var(--card)] p-8 shadow-2xl backdrop-blur">
                <div class="text-center">
                    <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Customer Access</div>
                    <h2 class="mt-4 text-3xl font-extrabold text-[var(--ink)]">Customer Login</h2>
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
                    <button type="submit" class="flex w-full justify-center rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-110">Sign in</button>
                    {{-- Seeded login, for local development only. This block is
                         absent once APP_ENV is anything other than local. --}}
                    @if (app()->environment('local'))
                        <div class="rounded-2xl border border-[var(--line)] bg-white/70 px-4 py-3 text-center text-xs text-[var(--muted)]">
                            <div class="mb-1 font-semibold uppercase tracking-[0.2em] text-[var(--accent)]">Demo account</div>
                            <span class="font-semibold text-[var(--ink)]">john@example.com</span> / <span class="font-semibold text-[var(--ink)]">customer123</span>
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
