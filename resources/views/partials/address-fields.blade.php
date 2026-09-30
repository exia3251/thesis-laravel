{{--
    The three place boxes, as searchable lists that narrow each other.

    Province, city or municipality and barangay were free text, so the same
    address arrived spelled six ways and a barangay could be typed that does
    not exist in the city beside it. These are the Philippine Statistics
    Authority's own lists: pick Cavite and the second box holds Cavite's 23
    towns and cities; pick Imus and the third holds its 97 barangays. The
    street stays free text, because no list of those exists.

    Included by the registration form, the customer's own profile and the back
    office's user panel, so all three ask for an address the same way and agree
    on what a valid one is.

    Values are posted as names, which is what receipts, delivery notes and the
    courier read. The codes travel alongside so the server can check that the
    three belong together.

    @param $prefix    unique on the page, so two copies do not collide
    @param $values    ['province' =>, 'city' =>, 'barangay' =>, 'house_street' =>, 'postal_code' =>]
    @param $required  whether the three lists have to be filled in
--}}

@php
    $prefix = $prefix ?? 'addr';
    $values = $values ?? [];
    $required = $required ?? true;

    // The storefront and the back office are styled differently, so the caller
    // hands over how its own inputs look rather than this guessing.
    $inputClass = $inputClass ?? 'block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 pr-9 text-sm outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)] disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400';
    $labelClass = $labelClass ?? 'mb-1 block text-sm font-medium text-[var(--ink)]';

    /*
     * A saved address is names, not codes, and the lists cannot be narrowed
     * without codes -- so the names are looked up once here rather than the
     * browser asking for a resolution round trip on every page load.
     *
     * Addresses saved before these lists existed still resolve, because the
     * lookup ignores the ways a place name gets written: "Imus", "Imus City"
     * and the PSA's own "City of Imus" all find the same row.
     */
    $province = filled($values['province'] ?? null)
        ? \App\Models\PsgcLocation::findNamed($values['province'], 'province')
        : null;

    $city = ($province && filled($values['city'] ?? null))
        ? \App\Models\PsgcLocation::findNamed($values['city'], 'city', $province->code)
        : null;

    $barangay = ($city && filled($values['barangay'] ?? null))
        ? \App\Models\PsgcLocation::findNamed($values['barangay'], 'barangay', $city->code)
        : null;

    $boxes = [
        [
            'key' => 'province',
            'label' => 'Province',
            'placeholder' => 'Search for a province',
            'found' => $province,
            'saved' => $values['province'] ?? '',
            'waitingFor' => null,
        ],
        [
            'key' => 'city',
            'label' => 'City or municipality',
            'placeholder' => 'Search for a city or municipality',
            'found' => $city,
            'saved' => $values['city'] ?? '',
            'waitingFor' => 'province',
        ],
        [
            'key' => 'barangay',
            'label' => 'Barangay',
            'placeholder' => 'Search for a barangay',
            'found' => $barangay,
            'saved' => $values['barangay'] ?? '',
            'waitingFor' => 'city',
        ],
    ];
@endphp

<div class="contents" data-address-group="{{ $prefix }}">
    @foreach ($boxes as $box)
        @php
            $id = $prefix . '_' . $box['key'];
            // A saved name that matched nothing is still shown. Losing
            // somebody's address because the list disagrees with it would be
            // worse than showing a name this form cannot narrow by.
            $display = $box['found']?->name ?? $box['saved'];
        @endphp

        <div class="relative" data-place="{{ $box['key'] }}">
            <label for="{{ $id }}_search" class="{{ $labelClass }}">
                {{ $box['label'] }} @if ($required)<span class="text-red-500">*</span>@endif
            </label>

            {{-- What the form posts: the name for the records, the code so
                 the server can check the three belong together. --}}
            <input type="hidden" id="{{ $id }}" name="{{ $box['key'] }}" value="{{ $display }}">
            <input type="hidden" id="{{ $id }}_code" name="{{ $box['key'] }}_code" value="{{ $box['found']?->code }}">

            <div class="relative">
                <input type="text" role="combobox" autocomplete="off" spellcheck="false"
                       id="{{ $id }}_search"
                       aria-expanded="false" aria-autocomplete="list"
                       aria-controls="{{ $id }}_list"
                       data-for="{{ $id }}"
                       placeholder="{{ $box['placeholder'] }}"
                       value="{{ $display }}"
                       @if ($box['waitingFor'] && ! $box['found']) disabled @endif
                       class="{{ $inputClass }}">

                <button type="button" tabindex="-1" aria-label="Show the list"
                        data-toggle-for="{{ $id }}"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-[var(--muted)] transition hover:text-[var(--ink)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                </button>
            </div>

            {{-- Sits over the page rather than pushing it about, so choosing a
                 province does not move the box under the pointer. --}}
            <ul id="{{ $id }}_list" role="listbox" tabindex="-1"
                class="absolute left-0 right-0 top-full z-40 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-[var(--line)] bg-white py-1 shadow-xl"></ul>

            <p id="{{ $id }}_hint" class="mt-1 text-xs text-[var(--muted)]">
                @if ($box['waitingFor'] && ! $box['found'])
                    Choose a {{ $box['waitingFor'] === 'province' ? 'province' : 'city or municipality' }} first.
                @endif
            </p>
            <p id="err_{{ $box['key'] }}" class="hidden mt-1 text-xs text-red-600"></p>
        </div>
    @endforeach
</div>

@once
    @push('scripts')
    <script>
        /**
         * Three lists that narrow each other, from the PSA's place names.
         *
         * Each box is a text input people type into and a list they pick from.
         * Picking a province loads that province's cities and clears the two
         * below it, because a barangay chosen under the old city would now be
         * in the wrong place.
         *
         * A whole child list is fetched at once -- the largest is one city's
         * barangays -- so typing filters what is already here rather than
         * asking the server per keystroke.
         */
        (function addressFields() {
            const CHAIN = { province: 'city', city: 'barangay', barangay: null };
            const loaded = new Map();

            /** Fetched once per code. These lists do not change while we run. */
            async function placesUnder(code) {
                const url = code === null ? '/shop-api/places/provinces' : `/shop-api/places/${code}`;

                if (!loaded.has(url)) {
                    loaded.set(url, fetch(url, { headers: { Accept: 'application/json' } })
                        .then((response) => response.json())
                        .then((payload) => payload.data || [])
                        .catch(() => {
                            // Let the next attempt try again rather than
                            // caching a failure for the life of the page.
                            loaded.delete(url);
                            return [];
                        }));
                }

                return loaded.get(url);
            }

            function parts(group, key) {
                const id = `${group}_${key}`;

                return {
                    id,
                    name: document.getElementById(id),
                    code: document.getElementById(`${id}_code`),
                    search: document.getElementById(`${id}_search`),
                    list: document.getElementById(`${id}_list`),
                    hint: document.getElementById(`${id}_hint`),
                };
            }

            /* The same matching as everywhere else on the site: every word has
               to land somewhere, with punctuation ignored -- so "gen trias"
               finds "City of General Trias" and "imus" finds "City of Imus". */
            function draw(box, rows, term) {
                const matches = rows.filter((row) => searchMatches(term, [row.name]));

                if (matches.length === 0) {
                    box.list.innerHTML = `<li class="px-3 py-2 text-sm text-[var(--muted)]">Nothing matches &ldquo;${escapeHtml(term)}&rdquo;.</li>`;
                    return [];
                }

                // Capped so a province with hundreds of barangays does not
                // build hundreds of elements on every keystroke; typing one
                // more letter is how the rest are reached.
                const shown = matches.slice(0, 80);

                box.list.innerHTML = shown.map((row, index) => `
                    <li role="option" id="${box.id}_opt_${index}" data-code="${escapeHtml(row.code)}" data-name="${escapeHtml(row.name)}"
                        class="cursor-pointer px-3 py-2 text-sm text-[var(--ink)] transition hover:bg-[var(--primary-soft)]">${escapeHtml(row.name)}</li>
                `).join('') + (matches.length > shown.length
                    ? `<li class="px-3 py-2 text-xs text-[var(--muted)]">${matches.length - shown.length} more &mdash; keep typing to narrow it.</li>`
                    : '');

                return shown;
            }

            function open(box) {
                box.list.classList.remove('hidden');
                box.search.setAttribute('aria-expanded', 'true');
            }

            function close(box) {
                box.list.classList.add('hidden');
                box.search.setAttribute('aria-expanded', 'false');
                box.search.removeAttribute('aria-activedescendant');
            }

            function highlight(box, index) {
                const options = [...box.list.querySelectorAll('[role="option"]')];

                options.forEach((option, i) => {
                    const on = i === index;
                    option.classList.toggle('bg-[var(--primary-soft)]', on);
                    if (on) {
                        option.scrollIntoView({ block: 'nearest' });
                        box.search.setAttribute('aria-activedescendant', option.id);
                    }
                });
            }

            /** Empties a box and everything below it. */
            function clearFrom(group, key) {
                let current = key;

                while (current) {
                    const box = parts(group, current);

                    box.name.value = '';
                    box.code.value = '';
                    box.search.value = '';
                    box.search.disabled = true;
                    box.list.innerHTML = '';
                    close(box);

                    box.hint.textContent = current === 'city'
                        ? 'Choose a province first.'
                        : 'Choose a city or municipality first.';

                    current = CHAIN[current];
                }
            }

            function setUp(group, key) {
                const box = parts(group, key);
                if (!box.search) return;

                let rows = [];
                let shown = [];
                let active = -1;

                /* Which parent the rows in hand belong to.

                   Without this the list was fetched once and kept: choosing
                   Cavite, then changing the province to Batangas, left the
                   city box still offering Cavite's towns -- and picking one
                   would have posted a town under a province it is not in. The
                   rows are only reused while the box above them has not
                   moved. */
                let loadedFor;

                const parent = () => {
                    const above = Object.keys(CHAIN).find((k) => CHAIN[k] === key);
                    return above ? parts(group, above).code.value : null;
                };

                async function load() {
                    const code = key === 'province' ? null : parent();

                    if (key !== 'province' && !code) {
                        rows = [];
                        loadedFor = undefined;
                        return;
                    }

                    if (loadedFor === code && rows.length > 0) {
                        return;
                    }

                    rows = await placesUnder(code);
                    loadedFor = code;
                }

                async function show(term = '') {
                    await load();

                    shown = draw(box, rows, term);
                    active = -1;
                    open(box);
                }

                function choose(option) {
                    box.name.value = option.dataset.name;
                    box.code.value = option.dataset.code;
                    box.search.value = option.dataset.name;
                    box.hint.textContent = '';

                    document.getElementById(`err_${key}`)?.classList.add('hidden');

                    close(box);

                    const below = CHAIN[key];

                    if (below) {
                        // Whatever was chosen below belonged to the old parent.
                        clearFrom(group, below);

                        const next = parts(group, below);
                        next.search.disabled = false;
                        next.hint.textContent = '';
                    }

                    box.search.dispatchEvent(new Event('change', { bubbles: true }));
                }

                box.search.addEventListener('input', () => {
                    /* Typing is not choosing. The hidden field is emptied the
                       moment the text stops being a name that was picked, so a
                       half typed province can never be submitted as if it had
                       been selected from the list. */
                    if (box.search.value !== box.name.value) {
                        box.name.value = '';
                        box.code.value = '';

                        const below = CHAIN[key];
                        if (below) clearFrom(group, below);
                    }

                    show(box.search.value);
                });

                box.search.addEventListener('focus', () => show(box.search.value));

                document.querySelector(`[data-toggle-for="${box.id}"]`)?.addEventListener('click', () => {
                    if (box.list.classList.contains('hidden')) {
                        box.search.focus();
                        show('');
                    } else {
                        close(box);
                    }
                });

                box.list.addEventListener('mousedown', (event) => {
                    // mousedown rather than click: blur would close the list
                    // out from under the pointer first.
                    const option = event.target.closest('[role="option"]');
                    if (option) {
                        event.preventDefault();
                        choose(option);
                    }
                });

                box.search.addEventListener('keydown', (event) => {
                    const options = [...box.list.querySelectorAll('[role="option"]')];

                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();

                        if (box.list.classList.contains('hidden')) {
                            show(box.search.value);
                            return;
                        }

                        if (options.length === 0) return;

                        active = event.key === 'ArrowDown'
                            ? (active + 1) % options.length
                            : (active - 1 + options.length) % options.length;

                        highlight(box, active);
                        return;
                    }

                    if (event.key === 'Enter') {
                        // Only swallowed when the list is answering, so return
                        // still submits the form the rest of the time.
                        if (!box.list.classList.contains('hidden') && options.length > 0) {
                            event.preventDefault();
                            choose(options[active >= 0 ? active : 0]);
                        }
                        return;
                    }

                    if (event.key === 'Escape') {
                        close(box);
                    }
                });

                box.search.addEventListener('blur', () => {
                    // Text left behind that was never picked is put back to the
                    // chosen name, so the box never shows something the form is
                    // not actually holding.
                    setTimeout(() => {
                        if (box.search.value !== box.name.value) {
                            box.search.value = box.name.value;
                        }
                        close(box);
                    }, 120);
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-address-group]').forEach((wrap) => {
                    const group = wrap.dataset.addressGroup;
                    ['province', 'city', 'barangay'].forEach((key) => setUp(group, key));
                });
            });

            /*
             * Fills the three boxes from outside.
             *
             * The back office's user panel is one dialog reused for whichever
             * row was clicked, so its address cannot be rendered with the page
             * the way the profile form's is. It looks the three names up
             * through /shop-api/places/resolve and hands the answer here.
             *
             * A name that resolved to nothing is still shown, without a code.
             * The box below it stays shut -- there is no city list to narrow
             * without a province -- but nobody's saved address disappears
             * because the list disagrees with how it was spelled.
             */
            window.setAddressFields = function (group, chosen) {
                ['province', 'city', 'barangay'].forEach((key) => {
                    const box = parts(group, key);
                    if (!box.search) return;

                    const value = chosen?.[key] || null;
                    const above = key === 'province' ? null : parts(group, key === 'city' ? 'province' : 'city');

                    box.name.value = value?.name ?? (chosen?.[`${key}_name`] || '');
                    box.code.value = value?.code ?? '';
                    box.search.value = box.name.value;
                    box.list.innerHTML = '';
                    close(box);

                    // Openable once the box above it has settled on a place.
                    box.search.disabled = above !== null && !above.code.value;
                    box.hint.textContent = box.search.disabled
                        ? (key === 'city' ? 'Choose a province first.' : 'Choose a city or municipality first.')
                        : '';

                    document.getElementById(`err_${key}`)?.classList.add('hidden');
                });
            };

            /** Empties all three, for a form being opened blank. */
            window.clearAddressFields = function (group) {
                const province = parts(group, 'province');

                if (province.search) {
                    province.name.value = '';
                    province.code.value = '';
                    province.search.value = '';
                    province.search.disabled = false;
                    province.hint.textContent = '';
                    close(province);
                }

                clearFrom(group, 'city');
            };
        })();
    </script>
    @endpush
@endonce
