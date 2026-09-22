@extends('layouts.admin')

@section('title', 'My account - RANEY LUBRICANTS TRADING')

@php
    $staff = auth()->user();
    $hasPassword = filled($staff->password);
@endphp

@section('content')
    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="mb-7">
        <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">My account</h1>
        <p class="mt-1 text-sm text-[var(--muted)]">Your name and photo appear in the sidebar and against everything you do in the activity log.</p>
    </div>

    <div class="grid max-w-4xl gap-5 lg:grid-cols-[1fr_1fr]">

        <section class="rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
            <div class="border-b border-[var(--line)] px-6 py-5">
                <h2 class="text-base font-bold text-[var(--ink)]">Details</h2>
            </div>

            <form id="accountForm" class="space-y-5 px-6 py-6" onsubmit="saveAccount(event)">
                <div id="accountErrors" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div class="flex flex-wrap items-center gap-5">
                    <div class="relative">
                        <img id="avatarPreview" src="" alt=""
                             class="hidden h-24 w-24 rounded-full border border-[var(--line)] object-cover">
                        <span id="avatarInitials"
                              class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-[var(--primary-soft)] text-2xl font-black text-[var(--primary)]"></span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap gap-2">
                            <label class="cursor-pointer rounded-xl border border-[var(--line)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">
                                Choose photo
                                <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="pickAvatar(event)">
                            </label>
                            <button type="button" id="removeAvatarBtn" onclick="removeAvatar()"
                                    class="hidden rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                Remove
                            </button>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-[var(--muted)]">
                            Square photo, 400 &times; 400 or larger. JPG, PNG or WEBP.<br>
                            It is cropped to a square and shrunk here before it is sent.
                        </p>
                        <p id="avatarNote" class="mt-1 hidden text-xs font-semibold text-[var(--primary)]"></p>
                        <p id="err_avatar" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>
                </div>

                <div class="border-t border-[var(--line)] pt-5">
                    <label for="full_name" class="block text-sm font-medium text-[var(--ink)]">Full name</label>
                    <input type="text" id="full_name" maxlength="60" required
                           class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                    <p id="err_full_name" class="mt-1 hidden text-xs text-red-600"></p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-[var(--ink)]">Email</label>
                    <input type="email" id="email" maxlength="150" required
                           class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                    <p class="mt-1 text-xs text-[var(--muted)]">You sign in with this.</p>
                    <p id="err_email" class="mt-1 hidden text-xs text-red-600"></p>
                </div>

                <div class="grid gap-4 rounded-xl bg-[var(--surface)] px-4 py-3.5 sm:grid-cols-2">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-[var(--muted)]">Role</div>
                        <div id="roleLabel" class="mt-0.5 text-sm font-semibold text-[var(--ink)]">&mdash;</div>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-[var(--muted)]">Last signed in</div>
                        <div id="lastSignIn" class="mt-0.5 text-sm font-semibold text-[var(--ink)]">&mdash;</div>
                    </div>
                    <p class="text-xs leading-5 text-[var(--muted)] sm:col-span-2">
                        Only an administrator can change your role, through Users.
                    </p>
                </div>

                <div class="flex justify-end border-t border-[var(--line)] pt-5">
                    <button type="submit" id="accountSave" class="rounded-xl bg-[var(--primary)] px-6 py-2.5 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-50">Save changes</button>
                </div>
            </form>
        </section>

        <section class="h-fit rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
            <div class="border-b border-[var(--line)] px-6 py-5">
                <h2 class="text-base font-bold text-[var(--ink)]">{{ $hasPassword ? 'Change password' : 'Set a password' }}</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">
                    @if ($hasPassword)
                        At least 8 characters. You stay signed in here afterwards.
                    @else
                        This account has no password yet. Setting one lets you sign in without Google.
                    @endif
                </p>
            </div>

            <form id="passwordForm" class="space-y-5 px-6 py-6" onsubmit="savePassword(event)">
                <div id="passwordErrors" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                @if ($hasPassword)
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-[var(--ink)]">Current password</label>
                        <input type="password" id="current_password" maxlength="255" required autocomplete="current-password"
                               class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                        <p id="err_current_password" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>
                @endif

                <div>
                    <label for="new_password" class="block text-sm font-medium text-[var(--ink)]">New password</label>
                    <input type="password" id="new_password" minlength="8" maxlength="32" required autocomplete="new-password"
                           class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                    <p id="err_new_password" class="mt-1 hidden text-xs text-red-600"></p>
                </div>

                <div>
                    <label for="new_password_confirmation" class="block text-sm font-medium text-[var(--ink)]">Confirm new password</label>
                    <input type="password" id="new_password_confirmation" minlength="8" maxlength="32" required autocomplete="new-password"
                           class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                </div>

                <div class="flex justify-end border-t border-[var(--line)] pt-5">
                    <button type="submit" class="rounded-xl bg-[var(--primary)] px-6 py-2.5 text-sm font-bold text-white transition hover:brightness-110">{{ $hasPassword ? 'Change password' : 'Set password' }}</button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    /* The photo is cropped and shrunk in the browser rather than on the
       server. A phone photograph is several megabytes and ends up in a 40
       pixel circle, and resizing it here means that never travels at all.
       It also avoids depending on the GD extension, which is not enabled. */
    const AVATAR_PIXELS = 512;
    const AVATAR_MINIMUM = 200;

    let pendingAvatar = null;
    let messageTimeout;

    function showMessage(text, type = 'success') {
        const box = document.getElementById('message');
        const ok = type === 'success';
        box.innerHTML = `<div class="text-sm font-medium ${ok ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>`;
        box.classList.remove('hidden');
        clearTimeout(messageTimeout);
        messageTimeout = setTimeout(() => box.classList.add('hidden'), 3200);
    }

    function clearErrors() {
        document.querySelectorAll('[id^="err_"]').forEach((el) => el.classList.add('hidden'));
        ['accountErrors', 'passwordErrors'].forEach((id) => document.getElementById(id).classList.add('hidden'));
    }

    function showErrors(errors, boxId) {
        let placed = 0;

        Object.entries(errors).forEach(([field, messages]) => {
            const el = document.getElementById(`err_${field}`);
            if (el) {
                el.textContent = messages[0];
                el.classList.remove('hidden');
                placed++;
            }
        });

        if (!placed) {
            const box = document.getElementById(boxId);
            box.innerHTML = Object.values(errors).flat().map(escapeHtml).join('<br>');
            box.classList.remove('hidden');
        }
    }

    /** Centre-crops to a square, then scales down. Never scales up. */
    function squareThumbnail(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const image = new Image();

            image.onload = () => {
                URL.revokeObjectURL(url);

                const side = Math.min(image.width, image.height);

                if (side < AVATAR_MINIMUM) {
                    reject(new Error(`That photo is ${image.width} by ${image.height}. Use one at least ${AVATAR_MINIMUM} by ${AVATAR_MINIMUM} pixels.`));
                    return;
                }

                const target = Math.min(AVATAR_PIXELS, side);
                const canvas = document.createElement('canvas');
                canvas.width = canvas.height = target;

                const context = canvas.getContext('2d');
                context.drawImage(image, (image.width - side) / 2, (image.height - side) / 2, side, side, 0, 0, target, target);

                canvas.toBlob(
                    (blob) => blob ? resolve({ blob, pixels: target }) : reject(new Error('That image could not be read.')),
                    'image/jpeg',
                    0.85
                );
            };

            image.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('That file is not an image we can read.'));
            };

            image.src = url;
        });
    }

    async function pickAvatar(event) {
        const file = event.target.files[0];
        clearErrors();

        if (!file) return;

        try {
            const { blob, pixels } = await squareThumbnail(file);

            pendingAvatar = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });

            const preview = document.getElementById('avatarPreview');
            preview.src = URL.createObjectURL(blob);
            preview.classList.remove('hidden');
            document.getElementById('avatarInitials').classList.add('hidden');

            const note = document.getElementById('avatarNote');
            note.textContent = `Ready to save: ${pixels}×${pixels}, ${Math.round(blob.size / 1024)} KB `
                + `(from ${Math.round(file.size / 1024)} KB). Press Save changes.`;
            note.classList.remove('hidden');
        } catch (error) {
            pendingAvatar = null;
            const el = document.getElementById('err_avatar');
            el.textContent = error.message;
            el.classList.remove('hidden');
        } finally {
            // Cleared so picking the same file again still fires a change.
            event.target.value = '';
        }
    }

    async function loadAccount() {
        const response = await fetch('/admin-api/account', { headers: { Accept: 'application/json' } });
        if (response.status === 401) { window.location.href = '/admin/login'; return; }

        const { data } = await response.json();

        document.getElementById('full_name').value = data.full_name || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('roleLabel').textContent = data.role_label || '—';
        document.getElementById('lastSignIn').textContent = data.last_signed_in || 'This is your first session';

        const preview = document.getElementById('avatarPreview');
        const initials = document.getElementById('avatarInitials');

        if (data.avatar_url) {
            preview.src = data.avatar_url;
            preview.classList.remove('hidden');
            initials.classList.add('hidden');
            document.getElementById('removeAvatarBtn').classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
            initials.textContent = data.initials || '?';
            initials.classList.remove('hidden');
            document.getElementById('removeAvatarBtn').classList.add('hidden');
        }
    }

    async function saveAccount(event) {
        event.preventDefault();
        clearErrors();

        const button = document.getElementById('accountSave');
        button.disabled = true;

        const body = new FormData();
        body.append('full_name', document.getElementById('full_name').value.trim());
        body.append('email', document.getElementById('email').value.trim());

        if (pendingAvatar) body.append('avatar', pendingAvatar);

        try {
            const response = await fetch('/admin-api/account', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body,
            });

            const payload = await response.json();

            if (!response.ok) {
                showErrors(payload.errors || { form: [payload.message || 'Could not save.'] }, 'accountErrors');
                return;
            }

            pendingAvatar = null;
            document.getElementById('avatarNote').classList.add('hidden');
            showMessage(payload.message || 'Saved.');

            // The sidebar shows the same name and photo, so it has to catch up.
            window.location.reload();
        } catch (error) {
            showErrors({ form: ['Could not save just now. Try again.'] }, 'accountErrors');
        } finally {
            button.disabled = false;
        }
    }

    async function removeAvatar() {
        if (!confirm('Remove your photo? Your initials will be shown instead.')) return;

        const response = await fetch('/admin-api/account/avatar', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
        });

        const payload = await response.json();
        showMessage(payload.message, payload.success ? 'success' : 'error');

        if (payload.success) window.location.reload();
    }

    async function savePassword(event) {
        event.preventDefault();
        clearErrors();

        const current = document.getElementById('current_password');

        const response = await fetch('/admin-api/account/password', {
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
            showErrors(payload.errors || { form: [payload.message || 'Could not save.'] }, 'passwordErrors');
            return;
        }

        document.getElementById('passwordForm').reset();
        showMessage(payload.message);
    }

    loadAccount();
</script>
@endpush
