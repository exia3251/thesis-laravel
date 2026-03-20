<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RANEY LUBRICANTS TRADING — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary:       #148a67;
            --primary-dark:  #0f6b50;
            --primary-soft:  rgba(20,138,103,0.10);
            --accent:        #d9b14a;
            --accent-soft:   rgba(217,177,74,0.12);
            --ink:           #16202a;
            --muted:         #6f7d8c;
            --surface:       #f3f6f9;
            --card:          #ffffff;
            --line:          rgba(21,35,54,0.10);
            --sidebar-bg:    #0d1f18;
            --sidebar-hover: rgba(20,138,103,0.18);
            --sidebar-active:rgba(20,138,103,0.28);
        }
        body { background: var(--surface); color: var(--ink); }
        input, select, textarea {
            border-color: var(--line) !important;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary) !important;
            outline: none;
            box-shadow: 0 0 0 3px var(--primary-soft);
        }
    </style>
</head>
<body class="bg-[var(--surface)]">
    <div class="min-h-screen">
        <div class="fixed inset-y-0 left-0 w-64 bg-[var(--sidebar-bg)] shadow-2xl">
            <div class="flex h-20 items-center justify-center border-b border-white/10 bg-[linear-gradient(135deg,_rgba(20,138,103,0.15),_transparent)]">
                <div class="text-center">
                    <div class="text-lg font-black tracking-tight leading-tight">
                        <span style="color:#148a67;">RANEY</span><span style="color:#d9b14a;"> LUBRICANTS</span>
                    </div>
                    <div class="text-[10px] uppercase tracking-[0.28em] text-white/50 mt-0.5">Trading</div>
                </div>
            </div>
            <nav class="mt-6 space-y-1 px-3">
                <a href="/admin/dashboard" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Dashboard</a>
                <a href="/admin/products" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Products</a>
                <a href="/admin/inventory" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Inventory</a>
                <a href="/admin/sales" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Sales</a>
                <a href="/admin/reports" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Reports</a>
                @if(auth()->user()->isSuperAdmin())
                    <a href="/admin/users" class="flex items-center rounded-2xl px-4 py-3 font-semibold text-white bg-[var(--sidebar-active)] ring-1 ring-white/10">Users</a>
                @endif
                <button type="button" onclick="logout()" class="w-full rounded-2xl px-4 py-3 text-left text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Logout</button>
            </nav>
        </div>

        <div class="ml-64 p-8 space-y-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-black text-[var(--ink)]">User Management</h1>
                    <p class="text-[var(--muted)]">Create and manage admin and customer accounts.</p>
                </div>
                <button onclick="openUserModal()" class="rounded-full bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white hover:bg-[var(--primary-dark)] transition shadow-sm">+ Add User</button>
            </div>

            <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

            <div class="rounded-2xl bg-[var(--card)] shadow-lg overflow-hidden border border-[var(--line)]">
                <table class="min-w-full">
                    <thead class="bg-[var(--surface)]">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersBody">
                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading users...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="rounded-2xl bg-[var(--card)] shadow-lg overflow-hidden border border-[var(--line)]">
                <div class="px-6 py-4 border-b border-[var(--line)] flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-bold text-[var(--ink)]">Recent Activity Logs</h2>
                    <div class="flex flex-wrap gap-2 text-sm items-center">
                        <button onclick="loadLogs('all')" id="tab-all" class="log-tab px-3 py-1 rounded-full border font-medium bg-gray-900 text-white border-gray-900">All</button>
                        <button onclick="loadLogs('admin')" id="tab-admin" class="log-tab px-3 py-1 rounded-full border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Admin</button>
                        <button onclick="loadLogs('customer')" id="tab-customer" class="log-tab px-3 py-1 rounded-full border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Customer</button>
                        <div class="w-px h-5 bg-gray-200 mx-1"></div>
                        <button onclick="toggleDateSort()" id="dateSortBtn" class="px-3 py-1 rounded-full border font-medium text-gray-600 border-gray-300 hover:bg-gray-100 flex items-center gap-1">
                            <span id="dateSortLabel">Newest First</span>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-3 border-b border-[var(--line)] bg-[var(--surface)] flex flex-wrap items-center gap-4">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Filter by Date</span>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-[var(--muted)]">From</label>
                        <input id="logDateFrom" type="date" class="rounded-xl border border-[var(--line)] bg-white px-3 py-1.5 text-sm focus:outline-none">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-[var(--muted)]">To</label>
                        <input id="logDateTo" type="date" class="rounded-xl border border-[var(--line)] bg-white px-3 py-1.5 text-sm focus:outline-none">
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="applyLogDateFilter()" class="rounded-full bg-[var(--primary)] px-3 py-1.5 text-xs font-bold text-white hover:bg-[var(--primary-dark)] transition">Apply</button>
                        <button type="button" onclick="resetLogDateFilter()" class="rounded-full border border-[var(--line)] px-3 py-1.5 text-xs font-semibold text-[var(--muted)] hover:bg-white transition">Reset</button>
                    </div>
                </div>
                <div class="p-6">
                    <div id="logsList" class="space-y-2 text-sm text-gray-700">
                        <p class="text-gray-500">Loading logs...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="userModal" class="hidden fixed inset-0 z-50 bg-black/50 overflow-y-auto">
        <div class="relative mx-auto my-10 w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-[var(--line)] px-7 py-5">
                <div>
                    <h3 id="userModalTitle" class="text-xl font-bold text-[var(--ink)]">Add User</h3>
                    <p class="text-xs text-[var(--muted)] mt-0.5">Fill in account details and save.</p>
                </div>
                <button type="button" onclick="closeUserModal()" class="rounded-full p-2 text-[var(--muted)] hover:bg-[var(--surface)] hover:text-[var(--ink)]">&#10005;</button>
            </div>
            <div class="p-7">
                <form id="userForm" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <input type="hidden" id="userId">
                    <div>
                        <label class="block text-sm font-medium text-[var(--ink)] mb-1">Full Name</label>
                        <input type="text" id="full_name" required class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--ink)] mb-1">Username</label>
                        <input type="text" id="username" required class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--ink)] mb-1">Role</label>
                        <select id="role" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                            <option value="admin">Admin</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--ink)] mb-1">Password</label>
                        <input type="password" id="password" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--ink)] mb-1">Phone</label>
                        <input type="text" id="phone" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--ink)] mb-1">Email</label>
                        <input type="email" id="email" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-[var(--ink)] mb-1">Address</label>
                        <textarea id="address" rows="3" class="block w-full resize-none rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none"></textarea>
                    </div>
                    <label class="md:col-span-2 inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="is_active" checked class="rounded">
                        <span class="text-sm text-[var(--ink)]">Active account</span>
                    </label>
                </form>
            </div>
            <div class="flex justify-end gap-3 border-t border-[var(--line)] px-7 py-5">
                <button type="button" onclick="closeUserModal()" class="rounded-full border border-[var(--line)] px-5 py-2.5 text-sm font-semibold text-[var(--muted)] hover:bg-[var(--surface)] transition">Cancel</button>
                <button type="button" onclick="document.getElementById('userForm').dispatchEvent(new Event('submit'))" class="rounded-full bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white hover:bg-[var(--primary-dark)] transition">Save User</button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let users = [];
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            const isSuccess = type === 'success';
            box.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">
                        ${isSuccess
                            ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a4.5 4.5 0 0 0-3.379-4.348 5.25 5.25 0 1 0-5.242 0A4.5 4.5 0 0 0 6 18.72"/></svg>'
                            : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'}
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${isSuccess ? 'User Update' : 'Action Needed'}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${text}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
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

        function formatLogDate(dateStr) {
            if (!dateStr) return 'No date';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
                + ' ' + d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }

        let logSortAsc = false; // newest first by default
        let logDateFrom = null;
        let logDateTo = null;

        function toggleDateSort() {
            logSortAsc = !logSortAsc;
            document.getElementById('dateSortLabel').textContent = logSortAsc ? 'Oldest First' : 'Newest First';
            renderCurrentLogs();
        }

        function applyLogDateFilter() {
            logDateFrom = document.getElementById('logDateFrom').value || null;
            logDateTo = document.getElementById('logDateTo').value || null;
            renderCurrentLogs();
        }

        function resetLogDateFilter() {
            logDateFrom = null;
            logDateTo = null;
            document.getElementById('logDateFrom').value = '';
            document.getElementById('logDateTo').value = '';
            renderCurrentLogs();
        }

        let currentLogs = [];

        function renderCurrentLogs() {
            let filtered = currentLogs;
            if (logDateFrom) {
                filtered = filtered.filter(log => log.created_at && new Date(log.created_at) >= new Date(logDateFrom));
            }
            if (logDateTo) {
                const toDate = new Date(logDateTo);
                toDate.setHours(23, 59, 59, 999);
                filtered = filtered.filter(log => log.created_at && new Date(log.created_at) <= toDate);
            }
            const sorted = [...filtered].sort((a, b) => {
                const da = new Date(a.created_at || 0);
                const db = new Date(b.created_at || 0);
                return logSortAsc ? da - db : db - da;
            });

            const actionColors = {
                admin_login: 'bg-blue-100 text-blue-800',
                customer_login: 'bg-blue-100 text-blue-800',
                customer_registered: 'bg-green-100 text-green-800',
                logout: 'bg-gray-100 text-gray-700',
                single_session_replaced: 'bg-yellow-100 text-yellow-800',
                session_invalidated: 'bg-yellow-100 text-yellow-800',
                user_created: 'bg-green-100 text-green-800',
                user_updated: 'bg-indigo-100 text-indigo-800',
                user_deleted: 'bg-red-100 text-red-800',
                product_created: 'bg-green-100 text-green-800',
                product_updated: 'bg-indigo-100 text-indigo-800',
                product_deleted: 'bg-red-100 text-red-800',
                product_catalog_imported: 'bg-purple-100 text-purple-800',
                stock_in: 'bg-green-100 text-green-800',
                stock_out: 'bg-orange-100 text-orange-800',
                sale_created: 'bg-green-100 text-green-800',
                sale_status_updated: 'bg-indigo-100 text-indigo-800',
                payment_request_approved: 'bg-green-100 text-green-800',
                payment_request_rejected: 'bg-red-100 text-red-800',
                order_placed: 'bg-blue-100 text-blue-800',
                payment_request_submitted: 'bg-yellow-100 text-yellow-800',
                profile_updated: 'bg-indigo-100 text-indigo-800',
                password_changed: 'bg-orange-100 text-orange-800',
            };

            const container = document.getElementById('logsList');
            container.innerHTML = sorted.length
                ? sorted.map((log) => {
                    const badgeClass = actionColors[log.action] || 'bg-gray-100 text-gray-700';
                    const label = log.action.replaceAll('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    const logDate = log.created_at
                        ? new Date(log.created_at).toLocaleString('en-PH', {
                            year: 'numeric', month: 'short', day: 'numeric',
                            hour: '2-digit', minute: '2-digit', second: '2-digit',
                            hour12: true
                          })
                        : 'No date';
                    return `
                    <div class="border rounded px-3 py-2">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold ${badgeClass}">${label}</span>
                            <span class="text-xs text-gray-500 font-medium">${log.user ? log.user.full_name : 'System'}</span>
                            <span class="text-xs text-gray-400">&bull; ${log.ip_address || 'No IP'}</span>
                            <span class="text-xs text-gray-400 ml-auto">${logDate}</span>
                        </div>
                        <div class="text-gray-600">${log.description || 'No description provided.'}</div>
                    </div>`;
                }).join('')
                : '<p class="text-gray-500">No activity logs yet.</p>';
        }

        async function loadLogs(type = 'all') {
            document.querySelectorAll('.log-tab').forEach(btn => {
                btn.classList.remove('bg-gray-900', 'text-white', 'border-gray-900');
                btn.classList.add('text-gray-600', 'border-gray-300');
            });
            const activeTab = document.getElementById('tab-' + type);
            if (activeTab) {
                activeTab.classList.add('bg-gray-900', 'text-white', 'border-gray-900');
                activeTab.classList.remove('text-gray-600', 'border-gray-300');
            }

            const container = document.getElementById('logsList');
            container.innerHTML = '<p class="text-gray-500">Loading logs...</p>';

            const response = await fetch(`/admin-api/logs?type=${type}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            currentLogs = data.data || [];
            renderCurrentLogs();
        }

        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const userId = document.getElementById('userId').value;
            const payload = {
                full_name: document.getElementById('full_name').value,
                username: document.getElementById('username').value,
                address: document.getElementById('address').value,
                role: document.getElementById('role').value,
                password: document.getElementById('password').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,

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