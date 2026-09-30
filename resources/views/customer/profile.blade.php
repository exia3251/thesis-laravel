@extends('layouts.customer')

@section('title', 'My Account - RANEY LUBRICANTS TRADING')

@php
    $customer = auth()->user();
    $signedInWithGoogle = filled($customer->google_id ?? null);
    $hasPassword = filled($customer->password);
@endphp

@section('content')
    <main class="container mx-auto px-4 py-8 sm:px-6">
        <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

        @if (session('profile_prompt'))
            <div class="mb-6 flex items-start gap-3 rounded-[1.5rem] border border-[var(--primary)] bg-[var(--primary-soft)] px-5 py-4">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                </svg>
                <p class="text-sm leading-6 text-[var(--ink)]">{{ session('profile_prompt') }}</p>
            </div>
        @endif

        <div class="mb-6">
            <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">My account</h1>
            <p class="mt-1 text-sm text-[var(--muted)]">Manage the details we use to reach you and deliver your orders.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">

            {{-- Sections rather than pages, so nothing is lost by switching. --}}
            <nav class="h-fit rounded-[1.5rem] border border-[var(--line)] bg-white p-3 shadow-sm">
                <div class="mb-2 flex items-center gap-3 rounded-2xl bg-[var(--surface)] px-3 py-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--primary-soft)] text-sm font-bold text-[var(--primary)]">
                        {{ $customer->initials() }}
                    </span>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-bold text-[var(--ink)]">{{ $customer->full_name }}</div>
                        <div class="truncate text-[11px] text-[var(--muted)]">{{ $customer->email }}</div>
                    </div>
                </div>

                <button type="button" data-section="profile" onclick="showSection('profile')" class="account-tab w-full">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0v.75H4.5v-.75Z"/></svg>
                    <span>Profile</span>
                </button>
                <button type="button" data-section="address" onclick="showSection('address')" class="account-tab w-full">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    <span>Delivery address</span>
                    <span id="addressWarning" class="ml-auto hidden h-2 w-2 rounded-full bg-amber-500"></span>
                </button>
                <button type="button" data-section="security" onclick="showSection('security')" class="account-tab w-full">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    <span>Security</span>
                </button>
            </nav>

            <div>
                {{-- --------------------------------------------- profile --}}
                <section id="section-profile" class="account-section rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
                    <div class="border-b border-[var(--line)] px-6 py-5">
                        <h2 class="text-base font-bold text-[var(--ink)]">Profile</h2>
                        <p class="mt-1 text-xs text-[var(--muted)]">Your name appears on every receipt.</p>
                    </div>

                    <form id="profileForm" class="space-y-5 px-6 py-6" onsubmit="saveProfile(event)">
                        <div id="profileErrors" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                        <div class="grid gap-5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:items-center">
                            <label for="full_name" class="text-sm font-medium text-[var(--muted)]">Full name</label>
                            <div>
                                <input type="text" id="full_name" maxlength="100" required
                                       class="block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                <p id="err_full_name" class="mt-1 hidden text-xs text-red-600"></p>
                            </div>

                            <label for="email" class="text-sm font-medium text-[var(--muted)]">Email</label>
                            <div>
                                <input type="email" id="email" maxlength="150" required
                                       @if ($signedInWithGoogle) readonly @endif
                                       class="block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)] read-only:bg-[var(--surface)] read-only:text-[var(--muted)]">

                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    @if ($customer->hasVerifiedEmail())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-800">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            Verified
                                        </span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-800">Not verified</span>
                                        <button type="button" onclick="resendVerification(this)" class="text-[11px] font-bold text-[var(--primary)] hover:underline">Resend link</button>
                                    @endif

                                    @if ($signedInWithGoogle)
                                        <span class="text-[11px] text-[var(--muted)]">Managed by your Google account</span>
                                    @else
                                        <span class="text-[11px] text-[var(--muted)]">You sign in with this</span>
                                    @endif
                                </div>
                                <p id="err_email" class="mt-1 hidden text-xs text-red-600"></p>
                            </div>

                            <label for="phone" class="text-sm font-medium text-[var(--muted)]">Phone</label>
                            <div>
                                <input type="text" id="phone" maxlength="13" required
                                       placeholder="09XXXXXXXXX"
                                       class="block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                <p class="mt-1 text-[11px] text-[var(--muted)]">Our rider calls this number on the day.</p>
                                <p id="err_phone" class="mt-1 hidden text-xs text-red-600"></p>
                            </div>
                        </div>

                        <div class="flex justify-end border-t border-[var(--line)] pt-5">
                            <button type="submit" class="rounded-xl bg-[var(--primary)] px-6 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Save changes</button>
                        </div>
                    </form>
                </section>

                {{-- --------------------------------------------- address --}}
                <section id="section-address" class="account-section hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
                    <div class="border-b border-[var(--line)] px-6 py-5">
                        <h2 class="text-base font-bold text-[var(--ink)]">Delivery address</h2>
                        <p class="mt-1 text-xs text-[var(--muted)]">Where your orders are delivered.</p>
                    </div>

                    <div id="addressIncomplete" class="hidden border-b border-amber-200 bg-amber-50 px-6 py-3.5 text-sm text-amber-900">
                        Complete this before placing an order. The barangay in particular is what couriers here rely on.
                    </div>

                    <form id="addressForm" class="space-y-5 px-6 py-6" onsubmit="saveProfile(event)">
                        <div id="addressErrors" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                        <div>
                            <label for="house_street" class="block text-sm font-medium text-[var(--ink)]">House or building number and street</label>
                            <input type="text" id="house_street" maxlength="160" required
                                   placeholder="123 Rizal Street, Unit 4B"
                                   class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                            <p id="err_house_street" class="mt-1 hidden text-xs text-red-600"></p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="barangay" class="block text-sm font-medium text-[var(--ink)]">Barangay</label>
                                <input type="text" id="barangay" maxlength="100" required
                                       placeholder="Poblacion"
                                       class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                <p id="err_barangay" class="mt-1 hidden text-xs text-red-600"></p>
                            </div>
                            <div>
                                <label for="city" class="block text-sm font-medium text-[var(--ink)]">City or municipality</label>
                                <input type="text" id="city" maxlength="100" required
                                       placeholder="Makati City"
                                       class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                <p id="err_city" class="mt-1 hidden text-xs text-red-600"></p>
                            </div>
                            <div>
                                <label for="province" class="block text-sm font-medium text-[var(--ink)]">Province</label>
                                <input type="text" id="province" maxlength="100" required
                                       placeholder="Metro Manila"
                                       class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                <p id="err_province" class="mt-1 hidden text-xs text-red-600"></p>
                            </div>
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-[var(--ink)]">Postal code <span class="font-normal text-[var(--muted)]">optional</span></label>
                                <input type="text" id="postal_code" maxlength="4" inputmode="numeric"
                                       placeholder="1200"
                                       class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                <p id="err_postal_code" class="mt-1 hidden text-xs text-red-600"></p>
                            </div>
                        </div>

                        <div class="rounded-xl bg-[var(--surface)] px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--muted)]">As it will appear on your order</div>
                            <div id="addressPreview" class="mt-1 text-sm text-[var(--ink)]">—</div>
                        </div>

                        <div class="flex justify-end border-t border-[var(--line)] pt-5">
                            <button type="submit" class="rounded-xl bg-[var(--primary)] px-6 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Save address</button>
                        </div>
                    </form>
                </section>

                {{-- -------------------------------------------- security --}}
                <section id="section-security" class="account-section hidden space-y-5">
                    <div class="rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
                        <div class="border-b border-[var(--line)] px-6 py-5">
                            <h2 class="text-base font-bold text-[var(--ink)]">{{ $hasPassword ? 'Change password' : 'Set a password' }}</h2>
                            <p class="mt-1 text-xs text-[var(--muted)]">
                                @if ($hasPassword)
                                    At least 8 characters.
                                @else
                                    You signed in with Google, so this account has no password yet. Setting one lets you sign in either way.
                                @endif
                            </p>
                        </div>

                        <form id="passwordForm" class="space-y-5 px-6 py-6" onsubmit="savePassword(event)">
                            <div id="passwordErrors" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                            <div class="grid gap-5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:items-center">
                                @if ($hasPassword)
                                    <label for="current_password" class="text-sm font-medium text-[var(--muted)]">Current password</label>
                                    <div>
                                        <input type="password" id="current_password" maxlength="255" required
                                               class="block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                        <p id="err_current_password" class="mt-1 hidden text-xs text-red-600"></p>
                                    </div>
                                @endif

                                <label for="new_password" class="text-sm font-medium text-[var(--muted)]">New password</label>
                                <div>
                                    <input type="password" id="new_password" minlength="8" maxlength="32" required
                                           class="block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                    <p id="err_new_password" class="mt-1 hidden text-xs text-red-600"></p>
                                </div>

                                <label for="new_password_confirmation" class="text-sm font-medium text-[var(--muted)]">Confirm</label>
                                <div>
                                    <input type="password" id="new_password_confirmation" minlength="8" maxlength="32" required
                                           class="block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                                    <p id="err_confirm_password" class="mt-1 hidden text-xs text-red-600"></p>
                                </div>
                            </div>

                            <div class="flex justify-end border-t border-[var(--line)] pt-5">
                                <button type="submit" class="rounded-xl bg-[var(--primary)] px-6 py-2.5 text-sm font-bold text-white transition hover:brightness-110">{{ $hasPassword ? 'Change password' : 'Set password' }}</button>
                            </div>
                        </form>
                    </div>

                    {{-- Single-session enforcement surprises people otherwise. --}}
                    <div class="rounded-[1.5rem] border border-[var(--line)] bg-white px-6 py-5 shadow-sm">
                        <h2 class="text-base font-bold text-[var(--ink)]">Where you are signed in</h2>
                        <p class="mt-1.5 text-sm leading-relaxed text-[var(--muted)]">
                            Your account is used on one device at a time. Signing in somewhere else signs you out here,
                            so nobody can keep a session open on a machine you have walked away from.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </main>
@endsection

@push('styles')
<style>
    .account-tab {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-radius: 0.9rem;
        padding: 0.7rem 0.85rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--muted);
        transition: background-color .15s, color .15s;
    }

    .account-tab:hover {
        background: var(--surface);
        color: var(--ink);
    }

    .account-tab[aria-current="true"] {
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 700;
    }
</style>
@endpush

@push('scripts')
<script>
    const ADDRESS_FIELDS = ['house_street', 'barangay', 'city', 'province', 'postal_code'];
    const PROFILE_FIELDS = ['full_name', 'email', 'phone'];
    let messageTimeout;

    function showMessage(text, type = 'success') {
        const box = document.getElementById('message');
        const ok = type === 'success';
        box.innerHTML = `<div class="text-sm font-medium ${ok ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>`;
        box.classList.remove('hidden');
        clearTimeout(messageTimeout);
        messageTimeout = setTimeout(() => box.classList.add('hidden'), 3200);
    }

    function showSection(name) {
        document.querySelectorAll('.account-section').forEach((section) => {
            section.classList.toggle('hidden', section.id !== `section-${name}`);
        });

        document.querySelectorAll('.account-tab').forEach((tab) => {
            tab.setAttribute('aria-current', String(tab.dataset.section === name));
        });

        // Survives the reload that follows saving, and a link straight here.
        history.replaceState(null, '', `#${name}`);
    }

    function clearErrors() {
        document.querySelectorAll('[id^="err_"]').forEach((el) => el.classList.add('hidden'));
        ['profileErrors', 'addressErrors', 'passwordErrors'].forEach((id) => {
            document.getElementById(id)?.classList.add('hidden');
        });
    }

    function showFieldErrors(errors, boxId) {
        let placed = 0;

        Object.entries(errors).forEach(([field, messages]) => {
            const el = document.getElementById(`err_${field}`);
            if (el) {
                el.textContent = messages[0];
                el.classList.remove('hidden');
                placed++;
            }
        });

        // Anything with no field of its own still has to be visible somewhere.
        if (!placed) {
            const box = document.getElementById(boxId);
            box.innerHTML = Object.values(errors).flat().map(escapeHtml).join('<br>');
            box.classList.remove('hidden');
        }
    }

    function renderAddressPreview() {
        const parts = [
            document.getElementById('house_street').value.trim(),
            document.getElementById('barangay').value.trim() ? 'Brgy. ' + document.getElementById('barangay').value.trim() : '',
            document.getElementById('city').value.trim(),
            document.getElementById('province').value.trim(),
            document.getElementById('postal_code').value.trim(),
        ].filter(Boolean);

        document.getElementById('addressPreview').textContent = parts.length ? parts.join(', ') : '—';
    }

    async function loadProfile() {
        const response = await fetch('/shop-api/profile', { headers: { Accept: 'application/json' } });
        if (response.status === 401) { window.location.href = '/shop/login'; return; }

        const { data } = await response.json();
        if (!data) return;

        document.getElementById('full_name').value = data.user?.full_name || '';
        document.getElementById('email').value = data.user?.email || '';
        document.getElementById('phone').value = data.phone || '';
        ADDRESS_FIELDS.forEach((field) => {
            document.getElementById(field).value = data[field] || '';
        });

        renderAddressPreview();

        document.getElementById('addressIncomplete').classList.toggle('hidden', !!data.is_complete);
        document.getElementById('addressWarning').classList.toggle('hidden', !!data.is_complete);
    }

    /* Both forms write the same record, so one request covers either. */
    async function saveProfile(event) {
        event.preventDefault();
        clearErrors();

        const body = { full_name: document.getElementById('full_name').value.trim() };
        PROFILE_FIELDS.concat(ADDRESS_FIELDS).forEach((field) => {
            body[field] = document.getElementById(field).value.trim();
        });

        await withBusy(busyButtonOf(event.target), async () => {
            const response = await fetch('/shop-api/profile', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify(body),
            });

            const payload = await response.json();

            if (!response.ok) {
                const isAddress = event.target.id === 'addressForm';
                showFieldErrors(payload.errors || { form: [payload.message || 'Could not save.'] },
                    isAddress ? 'addressErrors' : 'profileErrors');
                return;
            }

            showMessage(payload.message || 'Saved.');
            await loadProfile();
        }, 'Saving');
    }

    async function savePassword(event) {
        event.preventDefault();
        clearErrors();

        const current = document.getElementById('current_password');

        await withBusy(busyButtonOf(event.target), async () => {
            const response = await fetch('/shop-api/profile/password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify({
                    current_password: current ? current.value : null,
                    new_password: document.getElementById('new_password').value,
                    new_password_confirmation: document.getElementById('new_password_confirmation').value,
                }),
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                showFieldErrors(payload.errors || { form: [payload.message || 'Could not save.'] }, 'passwordErrors');
                return;
            }

            document.getElementById('passwordForm').reset();
            showMessage(payload.message || 'Password updated.');
        }, 'Saving');
    }

    ADDRESS_FIELDS.forEach((field) => {
        document.getElementById(field).addEventListener('input', renderAddressPreview);
    });

    showSection((location.hash || '#profile').slice(1));
    loadProfile();
</script>
@endpush
