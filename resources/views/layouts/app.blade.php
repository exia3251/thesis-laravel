<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RANEY LUBRICANTS TRADING')</title>
    @vite(['resources/css/app.css'])
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

        async function logout() {
            const response = await fetch(LOGOUT_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                window.location.href = LOGIN_URL;
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
