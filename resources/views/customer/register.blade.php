<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account - RANEY LUBRICANTS TRADING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --surface: #f6f8fb;
            --card: rgba(255, 255, 255, 0.82);
            --card-solid: #ffffff;
            --ink: #16202a;
            --muted: #6f7d8c;
            --line: rgba(21, 35, 54, 0.1);
            --primary: #148a67;
            --primary-soft: rgba(20, 138, 103, 0.1);
            --accent: #d9b14a;
            --accent-soft: rgba(217, 177, 74, 0.12);
        }
    </style>
</head>
<body class="bg-[var(--surface)] text-[var(--ink)]">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)] px-4 py-10 sm:px-6">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
            <section class="rounded-[2rem] border border-[var(--line)] bg-[linear-gradient(135deg,_rgba(255,255,255,0.84),_rgba(255,255,255,0.7))] p-8 shadow-xl backdrop-blur-xl sm:p-10">
                <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Customer Registration</div>
                <a href="/shop" class="mt-6 block">
                    <div class="text-3xl font-black tracking-tight sm:text-4xl">
                        <span class="text-[var(--primary)]">RANEY</span>
                        <span class="text-[var(--accent)]"> LUBRICANTS</span>
                    </div>
                    <div class="mt-1 text-[11px] uppercase tracking-[0.32em] text-[var(--muted)]">Trading</div>
                </a>
                <h1 class="mt-6 max-w-2xl text-4xl font-black leading-tight text-[var(--ink)] md:text-5xl">Create your customer account for shopping, order tracking, and delivery-ready checkout.</h1>
                <p class="mt-4 max-w-xl text-sm leading-7 text-[var(--muted)]">Register once to manage your profile, save delivery details, and keep your lubricant purchases organized inside your customer dashboard.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-[var(--line)] bg-white/70 p-4 shadow-sm">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Fast Checkout</div>
                        <div class="mt-2 text-sm text-[var(--ink)]">Saved profile details reduce friction when placing future orders.</div>
                    </div>
                    <div class="rounded-2xl border border-[var(--line)] bg-white/70 p-4 shadow-sm">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Order Visibility</div>
                        <div class="mt-2 text-sm text-[var(--ink)]">Track to-pay, to-receive, and delivered stages from one account.</div>
                    </div>
                    <div class="rounded-2xl border border-[var(--line)] bg-white/70 p-4 shadow-sm">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Delivery Ready</div>
                        <div class="mt-2 text-sm text-[var(--ink)]">Address and contact data are stored for receipts and order coordination.</div>
                    </div>
                </div>
            </section>

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
        </div>
    </div>

    <footer class="border-t border-[var(--line)] bg-[rgba(255,255,255,0.82)] backdrop-blur">
        <div class="max-w-5xl mx-auto grid gap-6 px-4 py-8 md:grid-cols-4">
            <div>
                <h3 class="text-sm font-black uppercase tracking-[0.22em] text-[var(--ink)]">RANEY LUBRICANTS TRADING</h3>
                <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Customer registration for ordering engine oils, coolants, and related lubricant products online.</p>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                <p class="mt-3 text-sm text-[var(--muted)]">Use your account to save delivery details, place orders, and monitor their status.</p>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-[var(--ink)]">Payments & Logistics</h4>
                <p class="mt-3 text-sm text-[var(--muted)]">Saved customer information supports receipts, delivery coordination, and future checkout convenience.</p>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-[var(--ink)]">About</h4>
                <p class="mt-3 text-sm text-[var(--muted)]">Built as an ecommerce and operational management platform for lubricant retail services.</p>
            </div>
        </div>
        <div class="border-t border-[var(--line)] px-4 py-4 text-center text-sm text-[var(--muted)]">
            &copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.
        </div>
    </footer>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
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
</body>
</html>
