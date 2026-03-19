<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - Engine Oil Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --shell-yellow: #ffd500;
            --shell-red: #e11d2e;
            --oil-black: #101114;
            --metal: #d4d7dd;
        }
    </style>
</head>
<body class="min-h-screen bg-[var(--oil-black)] text-white">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(255,213,0,0.16),_transparent_28%),linear-gradient(135deg,_#121317_0%,_#1d2027_45%,_#121317_100%)]">
        <div class="mx-auto flex min-h-screen max-w-6xl items-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid w-full gap-8 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="hidden rounded-[2rem] border border-white/10 bg-white/5 p-10 shadow-2xl backdrop-blur lg:block">
                    <div class="mb-8 inline-flex items-center gap-3 rounded-full border border-white/10 bg-white/10 px-4 py-2 text-xs uppercase tracking-[0.35em] text-[var(--shell-yellow)]">
                        Engine Oil Command Center
                    </div>
                    <h1 class="max-w-xl text-5xl font-black leading-tight text-white">Built for inventory control, sales tracking, and a defense-ready admin workflow.</h1>
                    <p class="mt-6 max-w-2xl text-base leading-7 text-slate-300">
                        Inspired by the strong retail energy of major fuel brands, this backend keeps the experience bold, industrial, and focused on operations.
                    </p>
                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                            <div class="text-xs uppercase tracking-[0.3em] text-slate-400">Inventory</div>
                            <div class="mt-2 text-2xl font-bold text-[var(--shell-yellow)]">Stock First</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                            <div class="text-xs uppercase tracking-[0.3em] text-slate-400">Sales</div>
                            <div class="mt-2 text-2xl font-bold text-white">Track Fast</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                            <div class="text-xs uppercase tracking-[0.3em] text-slate-400">Security</div>
                            <div class="mt-2 text-2xl font-bold text-[var(--shell-red)]">One Device</div>
                        </div>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-md rounded-[2rem] border border-white/10 bg-white/95 p-8 text-slate-900 shadow-2xl">
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-3 rounded-full bg-slate-950 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-[var(--shell-yellow)]">
                            Admin Login
                        </div>
                        <h2 class="mt-5 text-3xl font-black text-slate-950">
                            Engine Oil Inventory
                        </h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Secure access for super admin and admin operations.
                        </p>
                    </div>

                    <div id="error-message" class="hidden rounded-2xl border border-red-300 bg-red-50 px-4 py-3 text-red-700"></div>

                    <form id="loginForm" class="mt-6 space-y-5">
                        <div class="space-y-4">
                            <div>
                                <label for="username" class="mb-2 block text-sm font-semibold text-slate-700">Username</label>
                                <input id="username" name="username" type="text" maxlength="50" required
                                       class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-[var(--shell-red)] focus:ring-4 focus:ring-red-100"
                                       placeholder="Enter username">
                            </div>
                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                                <input id="password" name="password" type="password" maxlength="255" required
                                       class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-[var(--shell-red)] focus:ring-4 focus:ring-red-100"
                                       placeholder="Enter password">
                            </div>
                        </div>

                        <div>
                            <button type="submit"
                                    class="group relative flex w-full justify-center rounded-2xl border border-transparent bg-[var(--shell-red)] px-4 py-3 text-sm font-bold uppercase tracking-[0.18em] text-white transition hover:brightness-110">
                                Sign in
                            </button>
                        </div>
                    </form>

                    <div class="mt-6 rounded-2xl bg-slate-100 px-4 py-4 text-sm text-slate-600">
                        <p class="font-semibold text-slate-800">Test Accounts</p>
                        <p class="mt-1">Super Admin: `superadmin / superadmin123`</p>
                        <p>Admin: `admin / admin123`</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error-message');

            try {
                const response = await fetch('/admin/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    window.location.href = '/admin/dashboard';
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
</body>
</html>
