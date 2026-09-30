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
                            <button type="button" id="removeAvatarBtn" onclick="removeAvatar(this)"
                                    class="hidden rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                Remove
                            </button>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-[var(--muted)]">
                            JPG, PNG or WEBP, at least 200 &times; 200. Bigger is better.<br>
                            You choose the crop, and it is shrunk here before it is sent.
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

    {{-- Choosing the crop instead of having the middle of the photograph
         taken. A face is rarely in the centre of a picture, so an automatic
         centre crop cuts heads in half. Everything here happens in the
         browser: only the finished square is ever uploaded. --}}
    <div id="cropModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/60 p-4"
         role="dialog" aria-modal="true" aria-labelledby="cropTitle">
        <div class="w-full max-w-sm overflow-hidden rounded-[1.5rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-[var(--line)] px-6 py-4">
                <div>
                    <h3 id="cropTitle" class="text-base font-bold text-[var(--ink)]">Position your photo</h3>
                    <p class="mt-0.5 text-xs leading-5 text-[var(--muted)]">Drag it about, and zoom below. Whatever is inside the circle is what will be shown.</p>
                </div>
                <button type="button" onclick="closeCrop()" aria-label="Cancel"
                        class="rounded-lg p-1.5 text-[var(--muted)] transition hover:bg-[var(--surface)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- The dimmed part is still on screen while it is positioned, so
                 what is being cut away is visible rather than guessed at. --}}
            <div id="cropStage" tabindex="0"
                 class="relative mx-auto cursor-grab touch-none select-none overflow-hidden bg-slate-900 outline-none">
                <img id="cropImage" alt="" draggable="false" class="pointer-events-none absolute max-w-none">
                <div id="cropHole" class="pointer-events-none absolute rounded-full"></div>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div id="cropZoomRow" class="flex items-center gap-3">
                    <svg class="h-4 w-4 flex-shrink-0 text-[var(--muted)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M8 11h6M20 20l-3.5-3.5"/></svg>
                    <input type="range" id="cropZoom" min="1" max="3" step="0.01" value="1" aria-label="Zoom"
                           class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-[var(--line)] accent-[var(--primary)]"
                           oninput="zoomCrop(this.value)">
                    <svg class="h-4 w-4 flex-shrink-0 text-[var(--muted)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M8 11h6M11 8v6M20 20l-3.5-3.5"/></svg>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeCrop()"
                            class="rounded-xl border border-[var(--line)] px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--surface)]">Cancel</button>
                    <button type="button" id="cropConfirm" onclick="applyCrop()"
                            class="rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Use photo</button>
                </div>
            </div>
        </div>
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

    /* Past this there is nothing left to see, whatever the photo's size. */
    const CROP_ZOOM_CEILING = 4;

    let pendingAvatar = null;
    let messageTimeout;

    /* Where the photograph sits under the circle. x and y are the offset of
       the image's centre from the circle's centre in stage pixels, which
       makes zooming a multiplication and the drag limit symmetrical. */
    const crop = {
        // The decoded photograph, loaded into an image of our own rather than
        // read back off the one on screen. That element is emptied when the
        // dialog closes, and emptying it fires a load event of its own with
        // nothing behind it -- which, read as a photograph, measures 0 by 0.
        image: null,
        url: null, sourceBytes: 0,
        stage: 0, hole: 0, base: 1, zoom: 1, maxZoom: 1, x: 0, y: 0,
        dragging: false, lastX: 0, lastY: 0,
    };

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

    function showFieldError(id, text) {
        const field = document.getElementById(id);
        field.textContent = text;
        field.classList.remove('hidden');
    }

    function pickAvatar(event) {
        const file = event.target.files[0];
        clearErrors();

        if (file) openCrop(file);

        // Cleared so picking the same file again still fires a change.
        event.target.value = '';
    }

    function openCrop(file) {
        const stage = document.getElementById('cropStage');
        const hole = document.getElementById('cropHole');

        // A square stage with room around the circle, so the part being cut
        // away is still on screen. Sized from the window because a phone has
        // no room for the size a desktop wants.
        crop.stage = Math.max(240, Math.min(340, window.innerWidth - 96));
        crop.hole = Math.round(crop.stage * 0.76);
        crop.sourceBytes = file.size;

        stage.style.width = stage.style.height = crop.stage + 'px';
        hole.style.width = hole.style.height = crop.hole + 'px';
        hole.style.left = hole.style.top = Math.round((crop.stage - crop.hole) / 2) + 'px';
        /* One shadow does both jobs: everything outside the circle dimmed,
           and a hairline around the circle itself. It has to be written here
           rather than as a ring class, because Tailwind's ring is a
           box-shadow too and the two would overwrite each other. */
        hole.style.boxShadow = '0 0 0 9999px rgba(15, 23, 42, 0.62), inset 0 0 0 2px rgba(255, 255, 255, 0.85)';

        closeCrop();
        crop.url = URL.createObjectURL(file);

        const source = new Image();

        source.onload = () => {
            const shortest = Math.min(source.naturalWidth, source.naturalHeight);

            if (!shortest) {
                releaseCropUrl();
                showFieldError('err_avatar', 'That file is not an image we can read.');
                return;
            }

            if (shortest < AVATAR_MINIMUM) {
                releaseCropUrl();
                showFieldError('err_avatar', `That photo is ${source.naturalWidth} by ${source.naturalHeight}. `
                    + `Use one at least ${AVATAR_MINIMUM} by ${AVATAR_MINIMUM} pixels.`);
                return;
            }

            crop.image = source;
            document.getElementById('cropImage').src = crop.url;

            // At zoom 1 the short side exactly fills the circle, so the whole
            // of it can always be reached by dragging.
            crop.base = crop.hole / shortest;
            crop.zoom = 1;
            crop.x = crop.y = 0;

            // Zooming in takes fewer of the photo's own pixels, so the limit
            // is whatever still leaves a square the server will accept.
            crop.maxZoom = Math.min(CROP_ZOOM_CEILING, shortest / AVATAR_MINIMUM);

            const slider = document.getElementById('cropZoom');
            slider.max = crop.maxZoom.toFixed(2);
            slider.value = 1;
            // A photo already at the minimum has nothing to zoom into.
            document.getElementById('cropZoomRow').classList.toggle('invisible', crop.maxZoom <= 1.01);

            drawCrop();

            // Shown only once the photograph is known to be usable, so a file
            // that will be refused never opens a dialog at all.
            const modal = document.getElementById('cropModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('cropConfirm').focus();
        };

        source.onerror = () => {
            releaseCropUrl();
            showFieldError('err_avatar', 'That file is not an image we can read.');
        };

        source.src = crop.url;
    }

    function drawCrop() {
        if (!crop.image) return;

        const image = document.getElementById('cropImage');
        const scale = crop.base * crop.zoom;
        const width = crop.image.naturalWidth * scale;
        const height = crop.image.naturalHeight * scale;

        // The photo has to cover the circle at all times, so it can only be
        // dragged as far as its own edges allow. No empty corners, ever.
        const limitX = Math.max(0, (width - crop.hole) / 2);
        const limitY = Math.max(0, (height - crop.hole) / 2);
        crop.x = Math.min(limitX, Math.max(-limitX, crop.x));
        crop.y = Math.min(limitY, Math.max(-limitY, crop.y));

        image.style.width = width + 'px';
        image.style.height = height + 'px';
        image.style.left = (crop.stage / 2 + crop.x - width / 2) + 'px';
        image.style.top = (crop.stage / 2 + crop.y - height / 2) + 'px';
    }

    function zoomCrop(value) {
        const next = Math.min(crop.maxZoom, Math.max(1, parseFloat(value) || 1));

        // Whatever is under the middle of the circle stays under it.
        const ratio = next / crop.zoom;
        crop.x *= ratio;
        crop.y *= ratio;
        crop.zoom = next;

        document.getElementById('cropZoom').value = next;
        drawCrop();
    }

    function applyCrop() {
        if (!crop.image) return;

        const image = crop.image;
        const scale = crop.base * crop.zoom;

        // Back out of stage pixels into the photograph's own.
        const side = crop.hole / scale;
        const sx = Math.min(Math.max(0, image.naturalWidth / 2 - crop.x / scale - side / 2), image.naturalWidth - side);
        const sy = Math.min(Math.max(0, image.naturalHeight / 2 - crop.y / scale - side / 2), image.naturalHeight - side);

        // Never scaled up: a small crop is saved at its own size, and the
        // zoom limit is what keeps that above what the server accepts.
        const target = Math.max(AVATAR_MINIMUM, Math.min(AVATAR_PIXELS, Math.round(side)));

        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = target;
        canvas.getContext('2d').drawImage(image, sx, sy, side, side, 0, 0, target, target);

        canvas.toBlob((blob) => {
            if (!blob) {
                closeCrop();
                showFieldError('err_avatar', 'That image could not be read.');
                return;
            }

            pendingAvatar = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });

            const preview = document.getElementById('avatarPreview');
            preview.src = URL.createObjectURL(blob);
            preview.classList.remove('hidden');
            document.getElementById('avatarInitials').classList.add('hidden');

            const note = document.getElementById('avatarNote');
            note.textContent = `Ready to save: ${target}×${target}, ${Math.round(blob.size / 1024)} KB `
                + `(from ${Math.round(crop.sourceBytes / 1024)} KB). Press Save changes.`;
            note.classList.remove('hidden');

            closeCrop();
        }, 'image/jpeg', 0.85);
    }

    function releaseCropUrl() {
        if (crop.url) {
            URL.revokeObjectURL(crop.url);
            crop.url = null;
        }
    }

    function closeCrop() {
        const modal = document.getElementById('cropModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        crop.dragging = false;
        crop.image = null;
        // Held until now because the canvas reads through it.
        releaseCropUrl();
        document.getElementById('cropImage').removeAttribute('src');
    }

    function cropIsOpen() {
        return !document.getElementById('cropModal').classList.contains('hidden');
    }

    (function bindCropStage() {
        const stage = document.getElementById('cropStage');

        stage.addEventListener('pointerdown', (event) => {
            crop.dragging = true;
            crop.lastX = event.clientX;
            crop.lastY = event.clientY;
            stage.setPointerCapture(event.pointerId);
            stage.classList.add('cursor-grabbing');
        });

        stage.addEventListener('pointermove', (event) => {
            if (!crop.dragging) return;

            crop.x += event.clientX - crop.lastX;
            crop.y += event.clientY - crop.lastY;
            crop.lastX = event.clientX;
            crop.lastY = event.clientY;
            drawCrop();
        });

        ['pointerup', 'pointercancel'].forEach((name) => stage.addEventListener(name, () => {
            crop.dragging = false;
            stage.classList.remove('cursor-grabbing');
        }));

        stage.addEventListener('wheel', (event) => {
            event.preventDefault();
            zoomCrop(crop.zoom * (event.deltaY < 0 ? 1.08 : 1 / 1.08));
        }, { passive: false });

        // Arrow keys nudge it, for anyone not working with a mouse.
        stage.addEventListener('keydown', (event) => {
            const step = event.shiftKey ? 20 : 4;
            const moves = {
                ArrowLeft: [-step, 0], ArrowRight: [step, 0],
                ArrowUp: [0, -step], ArrowDown: [0, step],
            };

            if (!moves[event.key]) return;

            event.preventDefault();
            crop.x += moves[event.key][0];
            crop.y += moves[event.key][1];
            drawCrop();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && cropIsOpen()) closeCrop();
        });
    })();

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

        const done = startBusy(document.getElementById('accountSave'), 'Saving');

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
            done();
        }
    }

    async function removeAvatar(button = null) {
        const sure = await askToConfirm({
            title: 'Remove your photo?',
            body: 'Your initials will be shown instead, in the sidebar and against everything in the activity log.',
            confirm: 'Remove it',
            cancel: 'Keep it',
        });

        if (!sure) return;

        await withBusy(button, async () => {
            const response = await fetch('/admin-api/account/avatar', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            });

            const payload = await response.json();
            showMessage(payload.message, payload.success ? 'success' : 'error');

            if (payload.success) window.location.reload();
        }, 'Removing');
    }

    async function savePassword(event) {
        event.preventDefault();
        clearErrors();

        const current = document.getElementById('current_password');

        await withBusy(busyButtonOf(event.target), async () => {
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
        }, 'Saving');
    }

    loadAccount();
</script>
@endpush
