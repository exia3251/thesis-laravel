<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Login - RANEY LUBRICANTS TRADING</title>
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
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Username</label>
                            <input id="username" name="username" type="text" required
                                   minlength="3" maxlength="30" pattern="[A-Za-z][A-Za-z0-9._-]*" title="Username must start with a letter and may contain letters, numbers, dots, underscores, or hyphens."
                                   class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]"
                                   placeholder="Enter your username">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Password</label>
                            <input id="password" name="password" type="password" required
                                   minlength="6" maxlength="255"
                                   class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]"
                                   placeholder="Enter your password">
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="flex w-full justify-center rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-110">
                            Sign in
                        </button>
                    </div>

                    <div class="rounded-2xl border border-[var(--line)] bg-white/70 px-4 py-3 text-center text-sm text-[var(--muted)]">
                        Test Account: <span class="font-semibold text-[var(--ink)]">customer / customer123</span>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <a href="/shop" class="text-sm font-semibold text-[var(--primary)] hover:underline">Continue as Guest</a>
                </div>
                <div class="mt-3 text-center text-sm text-[var(--muted)]">
                    New customer?
                    <a href="/shop/register" class="font-semibold text-[var(--primary)] hover:underline">Create an account</a>
                </div>
            </section>

            <p class="mt-6 text-center text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.</p>
        </div>
    </div>


    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
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
                    username: document.getElementById('username').value.trim(),
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
</body>
</html>