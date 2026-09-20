@extends('layouts.bare')

@section('title', 'Create Account - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)] px-4 py-10 sm:px-6">
        <div class="w-full max-w-xl">
            <div class="mb-8 text-center">
                <a href="/shop">
                    <div class="text-3xl font-black tracking-tight sm:text-4xl">
                        <span class="text-[var(--primary)]">RANEY</span>
                        <span class="text-[var(--accent)]"> LUBRICANTS</span>
                    </div>
                    <div class="mt-1 text-[11px] uppercase tracking-[0.32em] text-[var(--muted)]">Trading</div>
                </a>
            </div>
            <section class="mx-auto w-full max-w-xl rounded-[2rem] border border-[var(--line)] bg-[var(--card)] p-8 shadow-2xl backdrop-blur">
                <div class="text-center">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.32em] text-[var(--primary)]">Customer Portal</div>
                    <h2 class="mt-4 text-3xl font-extrabold text-[var(--ink)]">Create Account</h2>
                    <p class="mt-2 text-sm font-semibold tracking-[0.2em] text-[var(--muted)]">RANEY LUBRICANTS TRADING</p>
                </div>

                <div id="message" class="hidden mt-6 rounded-2xl px-4 py-3"></div>

                <form id="registerForm" class="mt-8 space-y-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Full Name</label>
                            <input id="full_name" type="text" required minlength="2" maxlength="100" pattern="[A-Za-z][A-Za-z\s'.-]*" title="Full name must contain letters only. Spaces, apostrophes, periods, and hyphens are allowed." class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Juan Dela Cruz">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Username</label>
                            <input id="username" type="text" required minlength="3" maxlength="30" pattern="[A-Za-z][A-Za-z0-9._-]*" title="Username must start with a letter and may contain letters, numbers, dots, underscores, or hyphens." class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Choose a username">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Phone</label>
                            <input id="phone" type="text" required inputmode="numeric" maxlength="13" pattern="^(09\d{9}|\+639\d{9})$" title="Enter a valid Philippine mobile number like 09XXXXXXXXX or +639XXXXXXXXX." class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="09XXXXXXXXX">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Email</label>
                            <input id="email" type="email" class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="you@example.com">
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Address</label>
                        <textarea id="address" rows="5" required minlength="10" maxlength="500" class="block w-full resize-none rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Complete delivery address"></textarea>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Password</label>
                            <input id="password" type="password" required minlength="8" maxlength="100" class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Create a password">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Confirm Password</label>
                            <input id="password_confirmation" type="password" required minlength="8" maxlength="100" class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Confirm your password">
                        </div>
                    </div>

                    <button type="submit" class="flex w-full justify-center rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-110">
                        Create Account
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-[var(--muted)]">
                    Already have an account?
                    <a href="/shop/login" class="font-semibold text-[var(--primary)] hover:underline">Sign in here</a>
                </div>
            </section>
            <p class="mt-6 text-center text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.</p>
        </div>
    </div>


@endsection

@push('scripts')
<script>
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `mt-6 rounded-2xl px-4 py-3 ${type === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'}`;
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 4000);
        }

        document.getElementById('registerForm').addEventListener('submit', async (event) => {
            event.preventDefault();

            const payload = {
                full_name: document.getElementById('full_name').value.trim(),
                username: document.getElementById('username').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                email: document.getElementById('email').value.trim(),
                address: document.getElementById('address').value.trim(),
                password: document.getElementById('password').value,
                password_confirmation: document.getElementById('password_confirmation').value
            };

            const response = await fetch('/shop/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showMessage('Registration successful. Redirecting to login...', 'success');
                document.getElementById('registerForm').reset();

                setTimeout(() => {
                    window.location.href = '/shop/login?registered=1';
                }, 1000);
                return;
            }

            if (data.errors) {
                const firstError = Object.values(data.errors)[0];
                showMessage(Array.isArray(firstError) ? firstError[0] : 'Registration failed.', 'error');
                return;
            }

            showMessage(data.message || 'Registration failed.', 'error');
        });
</script>
@endpush
