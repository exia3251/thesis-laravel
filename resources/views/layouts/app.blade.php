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
