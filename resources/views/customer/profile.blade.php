@extends('layouts.customer')

@section('title', 'Profile - RANEY LUBRICANTS TRADING')

@section('content')

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
                        <label class="block text-sm font-medium text-[var(--ink)]">Email <span class="font-normal text-[var(--muted)]">— you sign in with this</span></label>
                        <input type="email" id="email" required maxlength="150"
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

@endsection

@push('scripts')
<script>
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
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        async function loadProfile() {
            const response = await fetch('/shop-api/profile', { headers: { Accept: 'application/json' } });
            const data = await response.json();

            if (data.success && data.data) {
                document.getElementById('phone').value = data.data.phone || '';
                document.getElementById('email').value = data.data.user?.email || '';
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
@endpush
