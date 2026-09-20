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
                    <div id="profileErrors" class="hidden mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 space-y-1"></div>
                    <form id="profileForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Phone <span class="text-red-500">*</span></label>
                            <input type="text" id="phone" required maxlength="13"
                                   placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                                   pattern="^(09[0-9]{9}|\+639[0-9]{9})$"
                                   title="Valid Philippine mobile number e.g. 09XXXXXXXXX"
                                   class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                            <p id="err_phone" class="hidden mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Email</label>
                            <input type="email" id="email" maxlength="100"
                                   placeholder="you@example.com"
                                   class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                            <p id="err_email" class="hidden mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Address <span class="text-red-500">*</span></label>
                            <textarea id="address" rows="6" required minlength="10" maxlength="500"
                                      placeholder="Complete delivery address"
                                      class="mt-2 block w-full resize-none rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]"></textarea>
                            <p id="err_address" class="hidden mt-1 text-xs text-red-600"></p>
                        </div>
                        <button type="submit" class="rounded-xl bg-[var(--primary)] px-5 py-3 text-sm font-bold text-white transition hover:brightness-110">Save Profile</button>
                    </form>
                </section>

                <section class="rounded-[2rem] border border-[var(--line)] bg-[var(--card)] p-6 shadow-xl backdrop-blur">
                    <div class="mb-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[var(--muted)]">Security</div>
                        <h2 class="mt-2 text-xl font-semibold text-[var(--ink)]">Change Password</h2>
                    </div>
                    <div id="passwordErrors" class="hidden mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 space-y-1"></div>
                    <form id="passwordForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Current Password <span class="text-red-500">*</span></label>
                            <input type="password" id="current_password" required maxlength="32"
                                   placeholder="Enter current password"
                                   class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                            <p id="err_current_password" class="hidden mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">New Password <span class="text-red-500">*</span></label>
                            <input type="password" id="new_password" required minlength="8" maxlength="32"
                                   placeholder="Min 8 characters"
                                   class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                            <p id="err_new_password" class="hidden mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ink)]">Confirm Password <span class="text-red-500">*</span></label>
                            <input type="password" id="new_password_confirmation" required minlength="8" maxlength="32"
                                   placeholder="Re-enter new password"
                                   class="mt-2 block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]">
                            <p id="err_confirm_password" class="hidden mt-1 text-xs text-red-600"></p>
                        </div>
                        <button type="submit" class="rounded-xl bg-[var(--ink)] px-5 py-3 text-sm font-bold text-white transition hover:brightness-110">Change Password</button>
                    </form>
                </section>
            </div>
        </main>

        <footer class="border-t border-[var(--line)] bg-[rgba(255,255,255,0.88)] backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="space-y-4">
                        <div>
                            <div class="text-xl font-black tracking-tight">
                                <span class="text-[var(--primary)]">RANEY</span>
                                <span class="text-[var(--accent)]"> LUBRICANTS</span>
                            </div>
                            <div class="mt-0.5 text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
                        </div>
                        <p class="text-sm leading-6 text-[var(--muted)]">Your trusted partner for premium engine oils and lubricants. Quality products for maximum performance.</p>
                        <div class="flex gap-3">
                            <span class="cursor-pointer flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            </span>
                            <span class="cursor-pointer flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                            </span>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">About Us</h4>
                        <ul class="space-y-2 text-sm">
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Our Story</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Our Products</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Why Choose Us</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Careers</span></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                        <ul class="space-y-2 text-sm">
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">My Account</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Order Tracking</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Shipping Info</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Returns &amp; Refunds</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">FAQs</span></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Contact Us</h4>
                        <ul class="space-y-3 text-sm text-[var(--muted)]">
                            <li class="flex items-start gap-3">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>123 Industrial Ave, Makati City, Metro Manila, Philippines</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span>+63 2 1234 5678</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>sales@raneylubricants.ph</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Mon - Sat: 8AM - 6PM</span>
                            </li>
                        </ul>
                    </div>

                </div>

                <div class="mt-8 border-t border-[var(--line)] pt-6 flex flex-col items-center justify-between gap-4 text-center sm:flex-row sm:text-left">
                    <p class="text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.</p>
                    <div class="flex gap-4 text-xs text-[var(--muted)]">
                        <span class="cursor-pointer hover:text-[var(--primary)]">Privacy Policy</span>
                        <span class="cursor-pointer hover:text-[var(--primary)]">Terms of Service</span>
                    </div>
                </div>
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

        function clearProfileErrors() {
            ['phone','email','address'].forEach(f => {
                const el = document.getElementById('err_' + f);
                if (el) { el.classList.add('hidden'); el.textContent = ''; }
            });
            const box = document.getElementById('profileErrors');
            box.classList.add('hidden'); box.innerHTML = '';
        }

        function showProfileFieldError(field, msg) {
            const el = document.getElementById('err_' + field);
            if (el) { el.textContent = msg; el.classList.remove('hidden'); }
        }

        function clearPasswordErrors() {
            ['current_password','new_password','confirm_password'].forEach(f => {
                const el = document.getElementById('err_' + f);
                if (el) { el.classList.add('hidden'); el.textContent = ''; }
            });
            const box = document.getElementById('passwordErrors');
            box.classList.add('hidden'); box.innerHTML = '';
        }

        function showPasswordFieldError(field, msg) {
            const el = document.getElementById('err_' + field);
            if (el) { el.textContent = msg; el.classList.remove('hidden'); }
        }

        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            clearProfileErrors();

            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            let valid = true;

            if (!phone) {
                showProfileFieldError('phone', 'Phone number is required.');
                valid = false;
            } else if (!/^(09\d{9}|\+639\d{9})$/.test(phone)) {
                showProfileFieldError('phone', 'Enter a valid PH number e.g. 09XXXXXXXXX.');
                valid = false;
            }

            if (!address) {
                showProfileFieldError('address', 'Address is required.');
                valid = false;
            } else if (address.length < 10) {
                showProfileFieldError('address', 'Address must be at least 10 characters.');
                valid = false;
            }

            if (!valid) return;

            const response = await fetch('/shop-api/profile', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    phone: phone,
                    email: document.getElementById('email').value.trim(),
                    address: address
                })
            });

            const data = await response.json();

            if (response.ok) {
                showMessage(data.message || 'Profile updated.', 'success');
            } else if (data.errors) {
                const errBox = document.getElementById('profileErrors');
                const messages = Object.values(data.errors).flat();
                errBox.innerHTML = messages.map(m => `<div>${m}</div>`).join('');
                errBox.classList.remove('hidden');
                Object.entries(data.errors).forEach(([field, msgs]) => showProfileFieldError(field, msgs[0]));
            } else {
                showMessage(data.message || 'Failed to update profile.', 'error');
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            clearPasswordErrors();

            const current = document.getElementById('current_password').value;
            const newPw   = document.getElementById('new_password').value;
            const confirm = document.getElementById('new_password_confirmation').value;
            let valid = true;

            if (!current) {
                showPasswordFieldError('current_password', 'Current password is required.');
                valid = false;
            }
            if (!newPw) {
                showPasswordFieldError('new_password', 'New password is required.');
                valid = false;
            } else if (newPw.length < 8) {
                showPasswordFieldError('new_password', 'New password must be at least 8 characters.');
                valid = false;
            } else if (newPw.length > 32) {
                showPasswordFieldError('new_password', 'New password must not exceed 32 characters.');
                valid = false;
            }
            if (newPw && confirm !== newPw) {
                showPasswordFieldError('confirm_password', 'Passwords do not match.');
                valid = false;
            }

            if (!valid) return;

            const response = await fetch('/shop-api/profile/password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    current_password: current,
                    new_password: newPw,
                    new_password_confirmation: confirm
                })
            });

            const data = await response.json();

            if (response.ok) {
                showMessage(data.message || 'Password changed.', 'success');
                document.getElementById('passwordForm').reset();
                clearPasswordErrors();
            } else if (data.errors) {
                const errBox = document.getElementById('passwordErrors');
                const messages = Object.values(data.errors).flat();
                errBox.innerHTML = messages.map(m => `<div>${m}</div>`).join('');
                errBox.classList.remove('hidden');
                Object.entries(data.errors).forEach(([field, msgs]) => {
                    const map = { current_password: 'current_password', new_password: 'new_password' };
                    if (map[field]) showPasswordFieldError(map[field], msgs[0]);
                });
            } else {
                showMessage(data.message || 'Failed to change password.', 'error');
            }
        });

        loadProfile();
    </script>
</body>
</html>
