<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Login - Engine Oil Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Customer Login</h2>
                <p class="mt-2 text-center text-sm text-gray-600">Engine Oil Shop</p>
            </div>

            <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>

            <form id="loginForm" class="mt-8 space-y-6">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <input id="username" name="username" type="text" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 text-gray-900 rounded-t-md"
                               placeholder="Username">
                    </div>
                    <div>
                        <input id="password" name="password" type="password" required
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 text-gray-900 rounded-b-md"
                               placeholder="Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Sign in
                    </button>
                </div>
            </form>

            <div class="text-center">
                <a href="/shop" class="text-sm text-blue-600 hover:text-blue-500">Continue as Guest</a>
            </div>

            <div class="text-center text-sm text-gray-600">
                <p>Test Account: customer / customer123</p>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const response = await fetch('/shop/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    username: document.getElementById('username').value,
                    password: document.getElementById('password').value
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                window.location.href = '/shop';
                return;
            }

            const errorDiv = document.getElementById('error-message');
            errorDiv.textContent = data.message || 'Login failed.';
            errorDiv.classList.remove('hidden');
        });
    </script>
</body>
</html>
