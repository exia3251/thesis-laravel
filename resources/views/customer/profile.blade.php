<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profile - RANEY LUBRICANTS TRADING</title>
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
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)]">
        <header class="sticky top-0 z-50 border-b border-[var(--line)] bg-[rgba(246,248,251,0.84)] backdrop-blur-xl">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="flex min-h-16 flex-col gap-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <a href="/shop" class="group block">
                        <div class="text-lg font-black tracking-tight sm:text-xl">
                            <span class="text-[var(--primary)]">RANEY</span>
                            <span class="text-[var(--accent)]"> LUBRICANTS</span>
                        </div>
                        <div class="text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
                    </a>

                    <div class="flex flex-wrap gap-2 items-center">
                        <a href="/shop" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5M5.25 9.5V20a1 1 0 0 0 1 1h11.5a1 1 0 0 0 1-1V9.5"/></svg><span>Shop</span></a>
                        <a href="/cart" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l.4 2m0 0L7 14h10l2-8H5.4ZM7 14l-1 5h12M9 20a1 1 0 1 0 0 .01M17 20a1 1 0 1 0 0 .01"/></svg><span>Cart</span></a>
                        <a href="/orders" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h10M9 9h10M9 13h10M5 5h.01M5 9h.01M5 13h.01M5 17h.01M9 17h10"/></svg><span>Orders</span></a>
                        <button onclick="logout()" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"/></svg><span>Logout</span></button>
                    </div>
                </div>
            </div>
        </header>

        <main class="container mx-auto px-4 py-8 sm:px-6">
            <section class="mb-8 rounded-[2rem] border border-[var(--line)] bg-[linear-gradient(135deg,_rgba(255,255,255,0.84),_rgba(255,255,255,0.7))] px-6 py-7 shadow-xl backdrop-blur-xl sm:px-8">
                <div class="max-w-3xl">
                    <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Account Settings</div>
                    <h1 class="mt-5 text-3xl font-black leading-tight text-[var(--ink)] sm:text-4xl">Manage your delivery details and account security.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Keep your profile complete so receipts, delivery coordination, and future checkouts stay accurate.</p>
                </div>
            </section>

            <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-[2rem] border border-[var(--line)] bg-[var(--card)] p-6 shadow-xl backdrop-blur">
                    <div class="mb-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[var(--primary)]">Customer Details</div>
                        <h2 class="mt-2 text-xl font-semibold text-[var(--ink)]">Contact Information</h2>
                    </div>
                    <form id="profileForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Phone</label>
                            <input type="text" id="phone" class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Email</label>
                            <input type="email" id="email" class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Address</label>
                            <textarea id="address" rows="6" class="mt-2 block w-full resize-none rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]"></textarea>
                        </div>
                        <button type="submit" class="rounded-xl bg-[var(--primary)] px-5 py-3 text-sm font-bold text-white transition hover:brightness-110">Save Profile</button>
                    </form>
                </section>

                <section class="rounded-[2rem] border border-[var(--line)] bg-[var(--card)] p-6 shadow-xl backdrop-blur">
                    <div class="mb-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[var(--muted)]">Security</div>
                        <h2 class="mt-2 text-xl font-semibold text-[var(--ink)]">Change Password</h2>
                    </div>
                    <form id="passwordForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Current Password</label>
                            <input type="password" id="current_password" class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">New Password</label>
                            <input type="password" id="new_password" class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Confirm Password</label>
                            <input type="password" id="new_password_confirmation" class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                        </div>
                        <button type="submit" class="rounded-xl bg-[var(--ink)] px-5 py-3 text-sm font-bold text-white transition hover:brightness-110">Change Password</button>
                    </form>
                </section>
            </div>
        </main>

        <footer class="border-t border-[var(--line)] bg-[rgba(255,255,255,0.82)] backdrop-blur">
            <div class="container mx-auto grid gap-6 px-4 py-8 md:grid-cols-4">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[0.22em] text-[var(--ink)]">RANEY LUBRICANTS TRADING</h3>
                    <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Maintain your delivery address, contact details, and account security for future orders.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Keep your profile complete so checkout and receipt details stay accurate.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">Payments & Logistics</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Saved contact information is used for order confirmation, receipts, and delivery coordination.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">About</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">This account area supports secure profile management for lubricant ecommerce customers.</p>
                </div>
            </div>
            <div class="border-t border-[var(--line)] px-4 py-4 text-center text-sm text-[var(--muted)]">
                &copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.
            </div>
        </footer>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            const isSuccess = type === 'success';
            box.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">
                        ${isSuccess
                            ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
                            : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'}
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${isSuccess ? 'Profile Update' : 'Action Needed'}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${text}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        async function logout() {
            const response = await fetch('/shop/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                window.location.href = '/shop';
            }
        }

        async function loadProfile() {
            const response = await fetch('/shop-api/profile', { headers: { Accept: 'application/json' } });
            const data = await response.json();

            if (data.success && data.data) {
                document.getElementById('phone').value = data.data.phone || '';
                document.getElementById('email').value = data.data.email || '';
                document.getElementById('address').value = data.data.address || '';
            }
        }

        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const response = await fetch('/shop-api/profile', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    phone: document.getElementById('phone').value,
                    email: document.getElementById('email').value,
                    address: document.getElementById('address').value
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Profile updated.', response.ok ? 'success' : 'error');
        });

        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const response = await fetch('/shop-api/profile/password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    current_password: document.getElementById('current_password').value,
                    new_password: document.getElementById('new_password').value,
                    new_password_confirmation: document.getElementById('new_password_confirmation').value
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Password updated.', response.ok ? 'success' : 'error');

            if (response.ok) {
                document.getElementById('passwordForm').reset();
            }
        });

        loadProfile();
    </script>
</body>
</html>
