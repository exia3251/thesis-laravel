{{--
    One picture cropper, used everywhere a picture is uploaded.

    It was written for the staff photograph and lived inside that one page. The
    content editor needs the same thing for a logo, a banner and a QR code, and
    a second copy of two hundred lines of positioning arithmetic is a second
    copy to keep in step. So it moved here and grew two options: the selection
    can be a rectangle of any ratio rather than only a circle, and the size the
    picture will be saved at is shown while it is being positioned.

    Everything happens in the browser. A photograph from a telephone is several
    megabytes and ends up a few hundred pixels wide; cropping and shrinking it
    here means the rest never travels, and it avoids depending on the GD
    extension, which is not enabled on this installation.

    Include once per page, then:

        openImageCropper(file, {
            shape: 'circle',            // or 'rect'
            ratio: 1,                   // width / height of the selection
            width: 512, height: 512,    // what it saves at, at most
            minWidth: 200, minHeight: 200,
            title: 'Position your photo',
            onDone: (file, meta) => { ... },
            onError: (message) => { ... },
        });
--}}

<div id="cropModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-900/60 p-4"
     role="dialog" aria-modal="true" aria-labelledby="cropTitle">
    <div class="w-full max-w-lg overflow-hidden rounded-[1.5rem] bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-3 border-b border-[var(--line)] px-6 py-4">
            <div>
                <h3 id="cropTitle" class="text-base font-bold text-[var(--ink)]">Position your picture</h3>
                <p id="cropHint" class="mt-0.5 text-xs leading-5 text-[var(--muted)]">Drag it about, and zoom below. Whatever is inside the frame is what will be shown.</p>
            </div>
            <button type="button" onclick="closeCrop()" aria-label="Cancel"
                    class="rounded-lg p-1.5 text-[var(--muted)] transition hover:bg-[var(--surface)]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- The dimmed part stays on screen while the picture is positioned,
             so what is being cut away is visible rather than guessed at. --}}
        <div id="cropStage" tabindex="0"
             class="relative mx-auto cursor-grab touch-none select-none overflow-hidden bg-slate-900 outline-none">
            <img id="cropImage" alt="" draggable="false" class="pointer-events-none absolute max-w-none">
            <div id="cropHole" class="pointer-events-none absolute"></div>
        </div>

        <div class="space-y-4 px-6 py-5">
            <div id="cropZoomRow" class="flex items-center gap-3">
                <svg class="h-4 w-4 flex-shrink-0 text-[var(--muted)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M8 11h6M20 20l-3.5-3.5"/></svg>
                <input type="range" id="cropZoom" min="1" max="3" step="0.01" value="1" aria-label="Zoom"
                       class="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-[var(--line)] accent-[var(--primary)]"
                       oninput="zoomCrop(this.value)">
                <svg class="h-4 w-4 flex-shrink-0 text-[var(--muted)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M8 11h6M11 8v6M20 20l-3.5-3.5"/></svg>
            </div>

            {{-- The numbers, because "it looked fine in the box" is not the
                 same as knowing what is about to be saved. Updated as the
                 picture is dragged and zoomed. --}}
            <dl class="grid grid-cols-3 gap-2 rounded-xl bg-[var(--surface)] px-3 py-2.5 text-center">
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.14em] text-[var(--muted)]">Original</dt>
                    <dd id="cropSourceSize" class="mt-0.5 text-xs font-bold text-[var(--ink)]">&mdash;</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.14em] text-[var(--muted)]">Selected</dt>
                    <dd id="cropSelectionSize" class="mt-0.5 text-xs font-bold text-[var(--ink)]">&mdash;</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-[0.14em] text-[var(--muted)]">Saves as</dt>
                    <dd id="cropOutputSize" class="mt-0.5 text-xs font-bold text-[var(--primary)]">&mdash;</dd>
                </div>
            </dl>

            <p id="cropWarning" class="hidden text-xs leading-5 text-amber-700"></p>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeCrop()"
                        class="rounded-xl border border-[var(--line)] px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--surface)]">Cancel</button>
                <button type="button" id="cropConfirm" onclick="applyCrop()"
                        class="rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Use picture</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    /* Past this there is nothing left to see, whatever the picture's size. */
    const CROP_ZOOM_CEILING = 4;

    /* Where the picture sits under the frame. x and y are the offset of the
       picture's centre from the frame's centre in stage pixels, which makes
       zooming a multiplication and the drag limit symmetrical. */
    const crop = {
        // The decoded picture, loaded into an image of our own rather than
        // read back off the one on screen. That element is emptied when the
        // dialog closes, and emptying it fires a load event of its own with
        // nothing behind it -- which, read as a picture, measures 0 by 0.
        image: null,
        url: null,
        sourceBytes: 0,
        options: null,
        stage: 0, holeW: 0, holeH: 0,
        base: 1, zoom: 1, maxZoom: 1, x: 0, y: 0,
        dragging: false, lastX: 0, lastY: 0,
    };

    function openImageCropper(file, options) {
        const settings = Object.assign({
            shape: 'circle',
            ratio: 1,
            width: 512,
            height: 512,
            minWidth: 200,
            minHeight: 200,
            title: 'Position your picture',
            hint: 'Drag it about, and zoom below. Whatever is inside the frame is what will be shown.',
            onDone: () => {},
            onError: () => {},
        }, options || {});

        const stage = document.getElementById('cropStage');
        const hole = document.getElementById('cropHole');

        /* A stage with room around the frame, so the part being cut away is
           still on screen. Sized from the window because a telephone has no
           room for the size a desktop wants. */
        crop.stage = Math.max(260, Math.min(420, window.innerWidth - 96));
        crop.options = settings;
        crop.sourceBytes = file.size;

        // The frame fits inside the stage at the ratio asked for.
        const margin = 0.82;
        if (settings.ratio >= 1) {
            crop.holeW = Math.round(crop.stage * margin);
            crop.holeH = Math.round(crop.holeW / settings.ratio);
        } else {
            crop.holeH = Math.round(crop.stage * margin);
            crop.holeW = Math.round(crop.holeH * settings.ratio);
        }

        // A wide frame leaves a short stage; keep enough height to drag in.
        const stageHeight = Math.max(crop.holeH + 80, Math.round(crop.stage * 0.6));

        stage.style.width = crop.stage + 'px';
        stage.style.height = stageHeight + 'px';

        hole.style.width = crop.holeW + 'px';
        hole.style.height = crop.holeH + 'px';
        hole.style.left = Math.round((crop.stage - crop.holeW) / 2) + 'px';
        hole.style.top = Math.round((stageHeight - crop.holeH) / 2) + 'px';
        hole.style.borderRadius = settings.shape === 'circle' ? '9999px' : '10px';
        /* One shadow does both jobs: everything outside the frame dimmed, and
           a hairline around the frame itself. It has to be written here rather
           than as a ring class, because Tailwind's ring is a box-shadow too
           and the two would overwrite each other. */
        hole.style.boxShadow = '0 0 0 9999px rgba(15, 23, 42, 0.62), inset 0 0 0 2px rgba(255, 255, 255, 0.85)';

        crop.stageHeight = stageHeight;

        document.getElementById('cropTitle').textContent = settings.title;
        document.getElementById('cropHint').textContent = settings.hint;

        closeCrop();
        crop.url = URL.createObjectURL(file);

        const source = new Image();

        source.onload = () => {
            const width = source.naturalWidth;
            const height = source.naturalHeight;

            if (!width || !height) {
                releaseCropUrl();
                settings.onError('That file is not a picture we can read.');
                return;
            }

            if (width < settings.minWidth || height < settings.minHeight) {
                releaseCropUrl();
                settings.onError(`That picture is ${width} by ${height}. `
                    + `Use one at least ${settings.minWidth} by ${settings.minHeight} pixels.`);
                return;
            }

            crop.image = source;
            document.getElementById('cropImage').src = crop.url;

            /* At zoom 1 the picture just covers the frame, so the whole of it
               can always be reached by dragging and no corner is ever empty. */
            crop.base = Math.max(crop.holeW / width, crop.holeH / height);
            crop.zoom = 1;
            crop.x = crop.y = 0;

            /* Zooming in takes fewer of the picture's own pixels, so the limit
               is whatever still leaves a selection the server will accept.

               At zoom z the selection measures holeW / (base * z) source
               pixels across, so requiring that to stay at or above the
               minimum gives z <= holeW / (base * minWidth), and the same for
               the height. */
            crop.maxZoom = Math.max(1, Math.min(
                CROP_ZOOM_CEILING,
                crop.holeW / (crop.base * settings.minWidth),
                crop.holeH / (crop.base * settings.minHeight)
            ));

            const slider = document.getElementById('cropZoom');
            slider.max = crop.maxZoom.toFixed(2);
            slider.value = 1;
            // A picture already at the minimum has nothing to zoom into.
            document.getElementById('cropZoomRow').classList.toggle('invisible', crop.maxZoom <= 1.01);

            document.getElementById('cropSourceSize').textContent = `${width} × ${height}`;

            drawCrop();

            // Shown only once the picture is known to be usable, so a file
            // that will be refused never opens a dialog at all.
            const modal = document.getElementById('cropModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('cropConfirm').focus();
        };

        source.onerror = () => {
            releaseCropUrl();
            settings.onError('That file is not a picture we can read.');
        };

        source.src = crop.url;
    }

    /** The selection in the picture's own pixels, and what it will be saved at. */
    function cropMeasurements() {
        const settings = crop.options;
        const scale = crop.base * crop.zoom;

        const selectionW = crop.holeW / scale;
        const selectionH = crop.holeH / scale;

        // Never scaled up: a small selection is saved at its own size, which
        // is what keeps a picture from being blown up into a blurry one.
        const shrink = Math.min(1, settings.width / selectionW, settings.height / selectionH);

        return {
            selectionW: Math.round(selectionW),
            selectionH: Math.round(selectionH),
            outputW: Math.max(1, Math.round(selectionW * shrink)),
            outputH: Math.max(1, Math.round(selectionH * shrink)),
            scale,
        };
    }

    function drawCrop() {
        if (!crop.image) return;

        const image = document.getElementById('cropImage');
        const scale = crop.base * crop.zoom;
        const width = crop.image.naturalWidth * scale;
        const height = crop.image.naturalHeight * scale;

        // The picture has to cover the frame at all times, so it can only be
        // dragged as far as its own edges allow.
        const limitX = Math.max(0, (width - crop.holeW) / 2);
        const limitY = Math.max(0, (height - crop.holeH) / 2);
        crop.x = Math.min(limitX, Math.max(-limitX, crop.x));
        crop.y = Math.min(limitY, Math.max(-limitY, crop.y));

        image.style.width = width + 'px';
        image.style.height = height + 'px';
        image.style.left = (crop.stage / 2 + crop.x - width / 2) + 'px';
        image.style.top = (crop.stageHeight / 2 + crop.y - height / 2) + 'px';

        const measured = cropMeasurements();
        document.getElementById('cropSelectionSize').textContent =
            `${measured.selectionW} × ${measured.selectionH}`;
        document.getElementById('cropOutputSize').textContent =
            `${measured.outputW} × ${measured.outputH}`;

        /* Said plainly rather than left to be discovered: zooming in past the
           point where the selection is smaller than the size we save at means
           the picture will be softer than it could have been. */
        const warning = document.getElementById('cropWarning');
        const short = measured.outputW < crop.options.width || measured.outputH < crop.options.height;

        warning.textContent = short
            ? `This saves at ${measured.outputW} × ${measured.outputH}, smaller than the `
              + `${crop.options.width} × ${crop.options.height} this slot uses. Zoom out, or use a larger picture, for a sharper result.`
            : '';
        warning.classList.toggle('hidden', !short);
    }

    function zoomCrop(value) {
        const next = Math.min(crop.maxZoom, Math.max(1, parseFloat(value) || 1));

        // Whatever is under the middle of the frame stays under it.
        const ratio = next / crop.zoom;
        crop.x *= ratio;
        crop.y *= ratio;
        crop.zoom = next;

        document.getElementById('cropZoom').value = next;
        drawCrop();
    }

    function applyCrop() {
        if (!crop.image) return;

        const settings = crop.options;
        const image = crop.image;
        const measured = cropMeasurements();
        const scale = measured.scale;

        // Back out of stage pixels into the picture's own.
        const sw = crop.holeW / scale;
        const sh = crop.holeH / scale;
        const sx = Math.min(Math.max(0, image.naturalWidth / 2 - crop.x / scale - sw / 2), image.naturalWidth - sw);
        const sy = Math.min(Math.max(0, image.naturalHeight / 2 - crop.y / scale - sh / 2), image.naturalHeight - sh);

        const canvas = document.createElement('canvas');
        canvas.width = measured.outputW;
        canvas.height = measured.outputH;
        canvas.getContext('2d').drawImage(image, sx, sy, sw, sh, 0, 0, measured.outputW, measured.outputH);

        /* A QR code is line art, and JPEG smears the edges of line art badly
           enough to stop some readers scanning it. Anything square and
           machine-read keeps its lossless format. */
        const type = settings.format === 'png' ? 'image/png' : 'image/jpeg';
        const extension = type === 'image/png' ? 'png' : 'jpg';

        canvas.toBlob((blob) => {
            if (!blob) {
                closeCrop();
                settings.onError('That picture could not be read.');
                return;
            }

            const file = new File([blob], `${settings.name || 'image'}.${extension}`, { type });

            settings.onDone(file, {
                width: measured.outputW,
                height: measured.outputH,
                bytes: blob.size,
                sourceBytes: crop.sourceBytes,
                url: URL.createObjectURL(blob),
            });

            closeCrop();
        }, type, 0.85);
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
</script>
@endpush
