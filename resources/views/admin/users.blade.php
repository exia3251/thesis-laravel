<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Management - Engine Oil Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <div class="fixed inset-y-0 left-0 w-64 bg-gray-800">
            <div class="flex items-center justify-center h-16 bg-gray-900">
                <span class="text-white font-bold text-xl">Admin Panel</span>
            </div>
            <nav class="mt-5">
                <a href="/admin/dashboard" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Dashboard</a>
                <a href="/admin/products" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Products</a>
                <a href="/admin/inventory" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Inventory</a>
                <a href="/admin/sales" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Sales</a>
                <a href="/admin/reports" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Reports</a>
                <a href="/admin/users" class="flex items-center px-6 py-3 text-gray-100 bg-gray-900">Users</a>
                <button type="button" onclick="logout()" class="w-full text-left px-6 py-3 text-gray-300 hover:bg-gray-700">Logout</button>
            </nav>
        </div>

        <div class="ml-64 p-8 space-y-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                    <p class="text-gray-600">Create and manage admin and customer accounts.</p>
                </div>
                <button onclick="openUserModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add User</button>
            </div>

            <div id="message" class="hidden px-4 py-3 rounded"></div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersBody">
                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading users...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Activity Logs</h2>
                </div>
                <div class="p-6">
                    <div id="logsList" class="space-y-3 text-sm text-gray-700">
                        <p class="text-gray-500">Loading logs...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="userModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 id="userModalTitle" class="text-lg font-bold">Add User</h3>
                <button type="button" onclick="closeUserModal()" class="text-gray-500 hover:text-gray-700">Close</button>
            </div>
            <form id="userForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" id="userId">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" id="full_name" required class="mt-1 block w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="username" required class="mt-1 block w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select id="role" class="mt-1 block w-full rounded-md border px-3 py-2">
                        <option value="admin">Admin</option>
                        <option value="customer">Customer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" class="mt-1 block w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" id="phone" class="mt-1 block w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" class="mt-1 block w-full rounded-md border px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea id="address" rows="3" class="mt-1 block w-full rounded-md border px-3 py-2"></textarea>
                </div>
                <label class="md:col-span-2 inline-flex items-center gap-2">
                    <input type="checkbox" id="is_active" checked>
                    <span class="text-sm text-gray-700">Active account</span>
                </label>
                <div class="md:col-span-2 flex justify-end gap-2">
                    <button type="button" onclick="closeUserModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let users = [];

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'} px-4 py-3 rounded`;
            box.classList.remove('hidden');
        }

        function resetUserForm() {
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('is_active').checked = true;
        }

        function openUserModal(user = null) {
            document.getElementById('userModal').classList.remove('hidden');
            document.getElementById('userModalTitle').textContent = user ? 'Edit User' : 'Add User';
            resetUserForm();

            if (!user) return;

            document.getElementById('userId').value = user.user_id;
            document.getElementById('full_name').value = user.full_name;
            document.getElementById('username').value = user.username;
            document.getElementById('role').value = user.role;
            document.getElementById('is_active').checked = user.is_active;
            document.getElementById('phone').value = user.customer_profile ? user.customer_profile.phone || '' : '';
            document.getElementById('email').value = user.customer_profile ? user.customer_profile.email || '' : '';
            document.getElementById('address').value = user.customer_profile ? user.customer_profile.address || '' : '';
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        async function loadUsers() {
            const response = await fetch('/admin-api/users', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            users = data.data || [];
            const tbody = document.getElementById('usersBody');

            tbody.innerHTML = users.length
                ? users.map((user) => `
                    <tr>
                        <td class="px-6 py-4">${user.full_name}</td>
                        <td class="px-6 py-4">${user.username}</td>
                        <td class="px-6 py-4 capitalize">${user.role.replace('_', ' ')}</td>
                        <td class="px-6 py-4">${user.is_active ? 'Active' : 'Inactive'}</td>
                        <td class="px-6 py-4 space-x-3">
                            <button onclick="editUser(${user.user_id})" class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button onclick="deleteUser(${user.user_id})" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No users found.</td></tr>';
        }

        function editUser(userId) {
            const user = users.find((item) => item.user_id === userId);
            if (user) openUserModal(user);
        }

        async function deleteUser(userId) {
            if (!confirm('Delete this user account?')) return;

            const response = await fetch(`/admin-api/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            showMessage(data.message || 'Delete request completed.', response.ok ? 'success' : 'error');
            if (response.ok) loadUsers();
        }

        async function loadLogs() {
            const response = await fetch('/admin-api/logs', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            const logs = data.data || [];
            const container = document.getElementById('logsList');

            container.innerHTML = logs.length
                ? logs.map((log) => `
                    <div class="border rounded px-3 py-2">
                        <div class="font-semibold">${log.action.replaceAll('_', ' ')}</div>
                        <div>${log.description || 'No description provided.'}</div>
                        <div class="text-xs text-gray-500">${log.user ? log.user.full_name : 'System'} | ${log.ip_address || 'No IP recorded'}</div>
                    </div>
                `).join('')
                : '<p class="text-gray-500">No activity logs yet.</p>';
        }

        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const userId = document.getElementById('userId').value;
            const payload = {
                full_name: document.getElementById('full_name').value,
                username: document.getElementById('username').value,
                role: document.getElementById('role').value,
                password: document.getElementById('password').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                address: document.getElementById('address').value,
                is_active: document.getElementById('is_active').checked
            };

            const response = await fetch(userId ? `/admin-api/users/${userId}` : '/admin-api/users', {
                method: userId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            showMessage(data.message || 'User saved.', response.ok ? 'success' : 'error');

            if (response.ok) {
                closeUserModal();
                loadUsers();
                loadLogs();
            }
        });

        async function logout() {
            const response = await fetch('/admin/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                window.location.href = '/admin/login';
            }
        }

        loadUsers();
        loadLogs();
    </script>
</body>
</html>
