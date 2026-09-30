<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RANEY LUBRICANTS TRADING')</title>
    @vite(['resources/css/app.css'])
    <style>
    /*
     * On a phone, a table becomes a list of cards.
     *
     * These are eight and ten columns wide. Inside a 375px screen that is a
     * 928px table in a box you scroll sideways -- and the columns that were
     * off the right edge were Payment, Delivery and Actions, which are the
     * ones somebody opens Sales to look at. The order number and the customer
     * were visible and nothing else.
     *
     * Each row becomes a card and each cell a labelled line, with the label
     * taken from the column heading it belongs to. Nothing is hidden and
     * nothing has to be scrolled to.
     */
    @media (max-width: 767px) {
        .stack-table {
            overflow-x: visible;
        }

        .stack-table > table,
        .stack-table > table > tbody,
        .stack-table > table > tbody > tr,
        .stack-table > table > tbody > tr > td {
            display: block;
            width: auto;
            min-width: 0;
        }

        .stack-table > table > thead {
            /* Off-screen rather than display:none, so the headings are still
               read out and still available to copy into the labels below. */
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        .stack-table > table > tbody > tr {
            border: 1px solid var(--line);
            border-radius: 1rem;
            background: #fff;
            padding: 0.25rem 0.9rem 0.6rem;
            margin-bottom: 0.75rem;
        }

        .stack-table > table > tbody > tr > td {
            border: 0;
            padding: 0.5rem 0;
            text-align: right !important;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            overflow-wrap: anywhere;
        }

        .stack-table > table > tbody > tr > td + td {
            border-top: 1px dashed var(--line);
        }

        .stack-table > table > tbody > tr > td::before {
            content: attr(data-label);
            /* Narrow, and allowed to shrink. At 8.5rem the label took most of
               a 375px screen and an order number wrapped to three lines with
               its status badge pushed off the edge. */
            flex: 0 1 6rem;
            min-width: 0;
            font-size: 0.66rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            padding-top: 0.15rem;
        }

        /* Everything after the label shares what is left, wraps rather than
           overflowing, and sits to the right of it. */
        .stack-table > table > tbody > tr > td > * {
            min-width: 0;
        }

        /* A cell with nothing to label -- an empty state spanning the row --
           keeps the full width rather than being pushed over by a blank. */
        .stack-table > table > tbody > tr > td[colspan]::before {
            content: none;
        }

        .stack-table > table > tbody > tr > td[colspan] {
            display: block;
            text-align: center !important;
        }
    }
    </style>

    <style>
        :root {
            --primary:      #148a67;
            --primary-dark: #0f6b50;
            --primary-soft: rgba(20, 138, 103, 0.1);
            --accent:       #d9b14a;
            --accent-soft:  rgba(217, 177, 74, 0.12);
            --ink:          #16202a;
            --muted:        #6f7d8c;
            --line:         rgba(21, 35, 54, 0.1);
            --surface:      #f6f8fb;
            --card:         #ffffff;
        }

        body {
            background: var(--surface);
            color: var(--ink);
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary) !important;
            outline: none;
            box-shadow: 0 0 0 3px var(--primary-soft);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[var(--surface)]">
    @yield('body')

    <script>
    /**
     * Gives every table cell the name of its column.
     *
     * The stylesheet turns each row into a card on a phone and prints these
     * as the label beside each value. Doing it here rather than in the eight
     * places that build these tables means a new table is handled the moment
     * it renders, and nobody has to remember.
     *
     * Rows arrive from fetch long after this runs, and are replaced again on
     * every filter and page change, so it watches rather than runs once.
     */
    /**
     * Holds a button busy while something is happening behind it.
     *
     * Every action here goes to the server and comes back, and in between the
     * button looked exactly as it had a moment earlier. So people pressed it
     * again -- sending a second order, a second payment, a second cancel --
     * or decided nothing had happened and left.
     *
     * The button is disabled for the duration, which is the part that
     * prevents the double press; the spinner is what explains why. Restored
     * in a finally, so a failed request leaves a usable button rather than a
     * dead one.
     */
    async function withBusy(button, work, busyLabel) {
        if (!button) return work();

        const original = button.innerHTML;
        const wasDisabled = button.disabled;

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.innerHTML = `<span class="inline-flex items-center justify-center gap-2">
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity="0.25"/>
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            </svg>${busyLabel ? `<span>${busyLabel}</span>` : ''}</span>`;

        try {
            return await work();
        } finally {
            button.innerHTML = original;
            button.disabled = wasDisabled;
            button.removeAttribute('aria-busy');
        }
    }

    /**
     * The same as withBusy, for code that does not wrap a single call.
     *
     * Returns the function that puts the button back. Used where the action
     * ends by navigating away on success and only needs restoring on the
     * error path.
     */
    function startBusy(button, busyLabel) {
        if (!button) return () => {};

        const original = button.innerHTML;
        const wasDisabled = button.disabled;

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.innerHTML = `<span class="inline-flex items-center justify-center gap-2">
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity="0.25"/>
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            </svg>${busyLabel ? `<span>${busyLabel}</span>` : ''}</span>`;

        return () => {
            button.innerHTML = original;
            button.disabled = wasDisabled;
            button.removeAttribute('aria-busy');
        };
    }

    /**
     * Swaps a password box between hidden and readable, and the icon with it.
     *
     * Lives here rather than on the two pages that had it, because the other
     * eleven password boxes in the system did not -- including both halves of
     * "create a password / confirm your password", where a typo cannot be seen
     * and costs the whole form.
     *
     * Finds the input by walking up from the button, so the markup only has to
     * put the two inside the same wrapper and nothing has to be named.
     */
    function togglePassword(button) {
        const field = button.closest('div').querySelector('input[type="password"], input[type="text"]');

        if (!field) return;

        const showing = field.type === 'text';

        field.type = showing ? 'password' : 'text';
        button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        button.querySelector('.eye-open').classList.toggle('hidden', !showing);
        button.querySelector('.eye-shut').classList.toggle('hidden', showing);

        /* Focus goes back to the box, at the end of what is already typed.
           Without the caret move, showing a password mid-entry dropped the
           cursor back to the start and the next keystroke landed there. */
        field.focus();

        const end = field.value.length;

        try {
            field.setSelectionRange(end, end);
        } catch (error) {
            // Not every input type allows a selection range. Nothing is lost.
        }
    }

    /**
     * Writes a count into the badge on a header button.
     *
     * The cart button said nothing when something was added to it. The item
     * went in, a small message appeared in the corner, and the button beside
     * it -- the one place a customer looks to find out what is in their cart --
     * carried the same nothing it had a second earlier. So people added the
     * same oil twice, or opened the cart to check.
     *
     * The number comes from the server's reply, not from adding one here: a
     * request that was refused for stock leaves the badge telling the truth.
     * Missing or not a number means the reply did not carry one, so the badge
     * is left alone rather than being zeroed.
     */
    function setBadgeCount(name, count) {
        const number = Number(count);

        if (!Number.isFinite(number)) return;

        document.querySelectorAll(`[data-badge="${name}"]`).forEach((badge) => {
            badge.textContent = number > 99 ? '99+' : String(number);
            badge.classList.toggle('hidden', number < 1);
            badge.setAttribute('aria-hidden', number < 1 ? 'true' : 'false');
        });
    }

    /** The cart badge specifically, which is the one that changes most. */
    function setCartCount(count) {
        setBadgeCount('cart', count);
    }

    /**
     * The same, for a form that submits through fetch: finds its submit
     * button so each caller does not have to.
     */
    function busyButtonOf(form) {
        return form?.querySelector('button[type="submit"], button:not([type])') ?? null;
    }

    function labelTableCells(root) {
        (root || document).querySelectorAll('.stack-table > table').forEach((table) => {
            const headings = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());

            if (headings.length === 0) return;

            table.querySelectorAll('tbody > tr').forEach((row) => {
                [...row.children].forEach((cell, index) => {
                    if (cell.hasAttribute('colspan')) return;
                    const label = headings[index];
                    if (label) cell.setAttribute('data-label', label);
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        labelTableCells();

        const observer = new MutationObserver((records) => {
            for (const record of records) {
                if (record.addedNodes.length) {
                    labelTableCells();
                    return;
                }
            }
        });

        document.querySelectorAll('.stack-table').forEach((wrap) => {
            observer.observe(wrap, { childList: true, subtree: true });
        });
    });

        /**
         * How a typed search term is matched, for the lists filtered in the
         * browser. Deliberately the same rule as App\Support\Search, which
         * does it for the lists filtered by the database: somebody typing
         * "5W-30" into the shop and somebody typing it into Inventory should
         * get the same answer.
         *
         * Two rules, and each fixes something that returned nothing at all.
         * Every word has to appear somewhere among the fields, so "motor oil"
         * finds "Motor Engine Oil" rather than failing on the word between
         * them. And a word matches with its punctuation removed as well as
         * with it, so "5W-30" finds a grade stored as 5W30 -- which is how
         * every bottle, handbook and chat reply writes it, and how nobody
         * types it.
         *
         * Lives here rather than in the bundle because these pages load the
         * stylesheet and no script: there is no bundle on the page to put it in.
         */
        function searchWords(term) {
            return String(term ?? '').trim().toLowerCase().split(/\s+/).filter(Boolean);
        }

        function searchPlain(value) {
            return String(value ?? '').toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '');
        }

        function searchMatches(term, fields) {
            const words = searchWords(term);

            if (words.length === 0) {
                return true;
            }

            const haystack = fields.filter((field) => field != null).join(' ').toLowerCase();
            const haystackPlain = searchPlain(haystack);

            return words.every((word) => haystack.includes(word) || haystackPlain.includes(searchPlain(word)));
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const LOGOUT_URL = '@yield('logout-url', '/shop/logout')';
        const LOGIN_URL  = '@yield('login-url', '/shop/login')';

        /**
         * Renders a value safe to drop into markup.
         *
         * Everything on these screens is built by string-concatenating into
         * innerHTML, so any text that originated from a person - a product
         * name, a customer name, a reference number, a server message quoting
         * one of those - has to be neutralised on the way in. Quotes are
         * escaped as well as angle brackets, because several of these land
         * inside attributes.
         */
        function escapeHtml(value) {
            if (value === null || value === undefined) {
                return '';
            }

            return String(value).replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[character]));
        }

        /**
         * Renders one pagination control for a list.
         *
         * Lists are paged on the server, so the control reports the slice
         * being shown against the real total rather than the length of what
         * happens to be loaded.
         *
         * @param {string}   containerId  element to render into
         * @param {object}   meta         current_page, last_page, total, from, to
         * @param {function} onPage       called with the page number to load
         */
        function renderPagination(containerId, meta, onPage) {
            const box = document.getElementById(containerId);
            if (!box || !meta) return;

            if (!meta.total) {
                box.innerHTML = '';
                return;
            }

            const handlerName = containerId + '__goto';
            window[handlerName] = (page) => onPage(page);

            const page = meta.current_page;
            const last = meta.last_page;

            // Keep the control short on long lists: first, last, and a window
            // around wherever the reader currently is.
            const wanted = new Set([1, last, page, page - 1, page + 1]);
            const pages = [...wanted].filter(p => p >= 1 && p <= last).sort((a, b) => a - b);

            const button = (label, target, opts = {}) => {
                const disabled = opts.disabled ? ' disabled' : '';
                const tone = opts.active
                    ? 'bg-[var(--primary)] text-white border-[var(--primary)]'
                    : opts.disabled
                        ? 'border-[var(--line)] text-[var(--muted)] opacity-40 cursor-not-allowed'
                        : 'border-[var(--line)] text-[var(--ink)] hover:border-[var(--primary)] hover:text-[var(--primary)]';

                return `<button type="button"${disabled} onclick="window['${handlerName}'](${target})"
                    class="min-w-[2.25rem] rounded-lg border bg-white px-2.5 py-1.5 text-xs font-semibold transition ${tone}">${label}</button>`;
            };

            let numbers = '';
            let previous = 0;
            pages.forEach(p => {
                if (p - previous > 1) {
                    numbers += '<span class="px-1 text-xs text-[var(--muted)]">&hellip;</span>';
                }
                numbers += button(p, p, { active: p === page });
                previous = p;
            });

            box.innerHTML = `
                <div class="flex flex-col items-center justify-between gap-3 border-t border-[var(--line)] px-6 py-4 sm:flex-row">
                    <p class="text-xs text-[var(--muted)]">
                        Showing <strong class="text-[var(--ink)]">${meta.from ?? 0}</strong>&ndash;<strong class="text-[var(--ink)]">${meta.to ?? 0}</strong>
                        of <strong class="text-[var(--ink)]">${meta.total.toLocaleString()}</strong>
                    </p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        ${button('Prev', page - 1, { disabled: page <= 1 })}
                        ${numbers}
                        ${button('Next', page + 1, { disabled: page >= last })}
                    </div>
                </div>`;
        }

        /** Waits for typing to settle before firing a search request. */
        function debounce(fn, wait = 350) {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn(...args), wait);
            };
        }

        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

        /* Signing out is one decision however many times the button is
           pressed. The second request arrives at a session that has already
           gone, is refused, and the refusal is what the page then acts on --
           so pressing twice could leave somebody looking at a signed-out page
           that never moved. */
        let signingOut = false;

        async function logout(button = null) {
            if (signingOut) return;
            signingOut = true;

            const done = startBusy(button, 'Signing out');

            try {
                const response = await fetch(LOGOUT_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    window.location.href = LOGIN_URL;
                    return;
                }

                signingOut = false;
                done();
            } catch (error) {
                signingOut = false;
                done();
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
