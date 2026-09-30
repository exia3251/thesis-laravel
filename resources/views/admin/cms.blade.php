@extends('layouts.admin')

@section('title', 'Shop content - RANEY LUBRICANTS TRADING')

@section('content')
    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="mb-6">
        <h1 class="text-2xl font-black text-[var(--ink)]">Shop content</h1>
        <p class="mt-1 text-sm text-[var(--muted)]">
            What the shop says about the business, and the pictures it shows. Changes appear on the storefront straight away.
        </p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">

        {{-- ------------------------------------------------ contact details --}}
        <section class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
            <div class="border-b border-[var(--line)] px-6 py-5">
                <h2 class="text-base font-bold text-[var(--ink)]">Contact details</h2>
                <p class="mt-0.5 text-xs leading-5 text-[var(--muted)]">
                    Shown in the footer of every page, on receipts, and in emails. Leave a box empty to hide that line entirely.
                </p>
            </div>

            <form id="contentForm" class="space-y-5 px-6 py-6" onsubmit="saveContent(event)">
                <div id="contentErrors" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                @php
                    $boxes = [
                        ['name', 'Business name', 'text', 'RANEY LUBRICANTS TRADING'],
                        ['tagline', 'Footer tagline', 'textarea', 'Your trusted partner for premium engine oils and lubricants.'],
                        ['address', 'Address', 'textarea', '2 Andalucia St., Brgy. Dulong Bayan, Bacoor City, Cavite'],
                        ['phone', 'Telephone number', 'text', '0960 239 7797'],
                        ['email', 'Email address', 'email', 'sales@example.com'],
                        ['hours', 'Opening hours', 'text', 'Mon - Sat: 8AM - 6PM'],
                        ['facebook', 'Facebook page', 'url', 'https://www.facebook.com/...'],
                    ];
                @endphp

                @foreach ($boxes as [$key, $label, $type, $placeholder])
                    <div>
                        <label for="{{ $key }}" class="block text-sm font-medium text-[var(--ink)]">{{ $label }}</label>

                        @if ($type === 'textarea')
                            <textarea id="{{ $key }}" rows="2" maxlength="300" placeholder="{{ $placeholder }}"
                                      class="mt-1.5 block w-full resize-none rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]"></textarea>
                        @else
                            <input type="{{ $type }}" id="{{ $key }}" maxlength="255" placeholder="{{ $placeholder }}"
                                   class="mt-1.5 block w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)]">
                        @endif

                        {{-- What the shop shows if this is left empty, so an
                             empty box reads as a choice rather than a gap. --}}
                        <p id="fallback_{{ $key }}" class="mt-1 hidden text-xs leading-5 text-[var(--muted)]"></p>
                        <p id="err_{{ $key }}" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>
                @endforeach

                <div class="flex justify-end border-t border-[var(--line)] pt-5">
                    <button type="submit" class="rounded-xl bg-[var(--primary)] px-6 py-2.5 text-sm font-bold text-white transition hover:brightness-110">
                        Save details
                    </button>
                </div>
            </form>
        </section>

        {{-- -------------------------------------------------------- pictures --}}
        <section class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
            <div class="border-b border-[var(--line)] px-6 py-5">
                <h2 class="text-base font-bold text-[var(--ink)]">Pictures</h2>
                <p class="mt-0.5 text-xs leading-5 text-[var(--muted)]">
                    Each one is positioned and cropped here before it is sent, and the size it will be saved at is shown while you do it.
                </p>
            </div>

            <div class="divide-y divide-[var(--line)]">
                @foreach (\App\Support\SiteContent::IMAGE_FIELDS as $field => $shape)
                    <div class="px-6 py-6" data-image="{{ $field }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-[var(--ink)]">{{ $shape['label'] }}</h3>
                                <p class="mt-0.5 text-xs leading-5 text-[var(--muted)]">{{ $shape['note'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-[var(--surface)] px-3 py-1 text-[11px] font-bold text-[var(--muted)]">
                                {{ $shape['width'] }} &times; {{ $shape['height'] }}
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            {{-- The slot is drawn at the ratio the picture will
                                 be, so an empty one already shows its shape. --}}
                            <div class="flex shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-[var(--line)] bg-[var(--surface)]"
                                 style="width: {{ $shape['ratio'] >= 1 ? 150 : round(150 * $shape['ratio']) }}px; height: {{ $shape['ratio'] >= 1 ? round(150 / $shape['ratio']) : 150 }}px;">
                                <img id="preview_{{ $field }}" alt="" class="hidden h-full w-full object-contain">
                                <span id="empty_{{ $field }}" class="px-2 text-center text-[10px] uppercase tracking-[0.18em] text-[var(--muted)]">Not set</span>
                            </div>

                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    <label class="cursor-pointer rounded-xl border border-[var(--line)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">
                                        Choose picture
                                        <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                                               onchange="pickSiteImage(event, '{{ $field }}')">
                                    </label>
                                    <button type="button" id="remove_{{ $field }}" onclick="removeSiteImage('{{ $field }}', this)"
                                            class="hidden rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                        Remove
                                    </button>
                                </div>
                                <p id="note_{{ $field }}" class="hidden text-xs font-semibold text-[var(--primary)]"></p>
                                <p id="err_image_{{ $field }}" class="hidden text-xs text-red-600"></p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    @include('partials.image-cropper')
@endsection

@push('scripts')
<script>
    const IMAGE_SHAPES = @json(\App\Support\SiteContent::IMAGE_FIELDS);
    const TEXT_FIELDS = @json(array_keys(\App\Support\SiteContent::TEXT_FIELDS));

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
        document.getElementById('contentErrors').classList.add('hidden');
    }

    async function loadContent() {
        const response = await fetch('/admin-api/site-content', { headers: { Accept: 'application/json' } });
        if (response.status === 401) { window.location.href = '/admin/login'; return; }

        const { data } = await response.json();

        TEXT_FIELDS.forEach((key) => {
            const field = document.getElementById(key);
            if (!field) return;

            const entry = data.fields[key] || {};
            field.value = entry.value ?? '';

            /* Only worth saying where a default exists and the box is empty:
               otherwise it is noise under every field. */
            const note = document.getElementById(`fallback_${key}`);
            const showing = !entry.value && entry.fallback;

            note.textContent = showing ? `Left empty, the shop shows: ${entry.fallback}` : '';
            note.classList.toggle('hidden', !showing);
        });

        Object.entries(data.images).forEach(([field, image]) => paintImage(field, image.url, image.uploaded));
    }

    /* `uploaded` is not the same as "something is showing". The GCash slot
       falls back to the code that ships with the system, which should be
       previewed but not offered for removal -- there is nothing to remove. */
    function paintImage(field, url, uploaded = true) {
        const preview = document.getElementById(`preview_${field}`);
        const empty = document.getElementById(`empty_${field}`);
        const remove = document.getElementById(`remove_${field}`);

        if (url) {
            preview.src = url;
            preview.classList.remove('hidden');
            empty.classList.add('hidden');
            remove.classList.toggle('hidden', !uploaded);
        } else {
            preview.removeAttribute('src');
            preview.classList.add('hidden');
            empty.classList.remove('hidden');
            remove.classList.add('hidden');
        }
    }

    async function saveContent(event) {
        event.preventDefault();
        clearErrors();

        const body = {};
        TEXT_FIELDS.forEach((key) => {
            const field = document.getElementById(key);
            if (field) body[key] = field.value.trim();
        });

        await withBusy(busyButtonOf(event.target), async () => {
            const response = await fetch('/admin-api/site-content', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify(body),
            });

            const payload = await response.json();

            if (!response.ok) {
                const errors = payload.errors || { form: [payload.message || 'Could not save.'] };
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
                    const box = document.getElementById('contentErrors');
                    box.innerHTML = Object.values(errors).flat().map(escapeHtml).join('<br>');
                    box.classList.remove('hidden');
                }
                return;
            }

            showMessage(payload.message || 'Saved.');
            await loadContent();
        }, 'Saving');
    }

    /* ------------------------------------------------------------ pictures */

    function pickSiteImage(event, field) {
        const file = event.target.files[0];
        const shape = IMAGE_SHAPES[field];
        const error = document.getElementById(`err_image_${field}`);

        error.classList.add('hidden');

        if (file) {
            openImageCropper(file, {
                shape: 'rect',
                ratio: shape.ratio,
                width: shape.width,
                height: shape.height,
                // A quarter of the target still gives something usable, and
                // refusing anything smaller stops a thumbnail being stretched
                // across the top of the shop.
                minWidth: Math.round(shape.width / 4),
                minHeight: Math.round(shape.height / 4),
                name: field,
                // A QR code is line art, and JPEG smears line art enough to
                // stop some telephones scanning it.
                format: field === 'gcash_qr' ? 'png' : 'jpeg',
                title: `Position the ${shape.label.toLowerCase()}`,
                hint: 'Drag it about, and zoom below. Whatever is inside the frame is what the shop will show.',
                onError: (message) => {
                    error.textContent = message;
                    error.classList.remove('hidden');
                },
                onDone: (cropped, meta) => uploadSiteImage(field, cropped, meta),
            });
        }

        // Cleared so picking the same file again still fires a change.
        event.target.value = '';
    }

    async function uploadSiteImage(field, file, meta) {
        const note = document.getElementById(`note_${field}`);
        const error = document.getElementById(`err_image_${field}`);
        const picker = document.querySelector(`[data-image="${field}"] input[type=file]`);

        /* The upload starts from the crop dialog rather than from a button, so
           there is nothing on screen to hold busy -- but a second picture can
           still be chosen while the first is in flight, and the two would race
           to be the one that is kept. The picker rests until this one lands. */
        if (picker) picker.disabled = true;

        note.textContent = 'Uploading…';
        note.classList.remove('hidden');

        const body = new FormData();
        body.append('image', file);

        try {
            const response = await fetch(`/admin-api/site-content/images/${field}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body,
            });

            const payload = await response.json();

            if (!response.ok) {
                note.classList.add('hidden');
                error.textContent = payload.message
                    || Object.values(payload.errors || {})[0]?.[0]
                    || 'That picture could not be saved.';
                error.classList.remove('hidden');
                return;
            }

            paintImage(field, payload.data.url);

            note.textContent = `Saved at ${meta.width} × ${meta.height}, `
                + `${Math.round(meta.bytes / 1024)} KB (from ${Math.round(meta.sourceBytes / 1024)} KB).`;
            showMessage(payload.message);
        } catch (e) {
            note.classList.add('hidden');
            error.textContent = 'Could not reach the server. Please try again.';
            error.classList.remove('hidden');
        } finally {
            if (picker) picker.disabled = false;
        }
    }

    async function removeSiteImage(field, button) {
        const shape = IMAGE_SHAPES[field];

        if (!await askToConfirm({
            title: `Remove the ${shape.label.toLowerCase()}?`,
            body: 'The shop goes back to its built-in design. You can upload another at any time.',
            confirm: 'Remove it',
            cancel: 'Keep it',
        })) return;

        await withBusy(button, async () => {
            const response = await fetch(`/admin-api/site-content/images/${field}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            });

            const payload = await response.json();

            if (response.ok) {
                paintImage(field, payload.data.url);
                document.getElementById(`note_${field}`).classList.add('hidden');
            }

            showMessage(payload.message, response.ok ? 'success' : 'error');
        }, 'Removing');
    }

    loadContent();
</script>
@endpush
