<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profile - Engine Oil Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Profile Settings</h1>
                <p class="text-gray-600">Update your contact details and password.</p>
            </div>
            <div class="flex gap-4">
                <a href="/shop" class="text-blue-600 hover:text-blue-800">Shop</a>
                <a href="/orders" class="text-blue-600 hover:text-blue-800">Orders</a>
            </div>
        </div>

        <div id="message" class="hidden mb-4 px-4 py-3 rounded"></div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Contact Information</h2>
                <form id="profileForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" id="phone" class="mt-1 block w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" class="mt-1 block w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea id="address" rows="4" class="mt-1 block w-full rounded-md border px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Profile</button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Change Password</h2>
                <form id="passwordForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" id="current_password" class="mt-1 block w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" id="new_password" class="mt-1 block w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" id="new_password_confirmation" class="mt-1 block w-full rounded-md border px-3 py-2">
                    </div>
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900">Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `hidden mb-4 px-4 py-3 rounded ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            box.classList.remove('hidden');
        }

        async function loadProfile() {
            const response = await fetch('/shop-api/profile', { headers: { Accept: 'application/json' } });
            const data = await response.json();

            if (data.success && data.data) {
                document.getElementById('phone').value = data.data.phone || '';
                document.getElementById('email').value = data.data.email || '';
                document.getElementById('address').value = data.data.address || '';
            }
        }

        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const response = await fetch('/shop-api/profile', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    phone: document.getElementById('phone').value,
                    email: document.getElementById('email').value,
                    address: document.getElementById('address').value
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Profile updated.', response.ok ? 'success' : 'error');
        });

        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const response = await fetch('/shop-api/profile/password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    current_password: document.getElementById('current_password').value,
                    new_password: document.getElementById('new_password').value,
                    new_password_confirmation: document.getElementById('new_password_confirmation').value
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Password updated.', response.ok ? 'success' : 'error');

            if (response.ok) {
                document.getElementById('passwordForm').reset();
            }
        });

        loadProfile();
    </script>
</body>
</html>
