@extends('layouts.admin')

@section('title', 'RANEY LUBRICANTS TRADING — Admin')

@section('content')
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
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Staff</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)]                     <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Status</th>
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
            <div id="logsPagination"></div>
        </div>
    </div>
</div>
    </div>

    {{-- Deactivate Confirmation Modal --}}
    <div id="deactivateModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
<div class="w-full max-w-sm mx-4 rounded-2xl bg-white shadow-2xl overflow-hidden">
    <div class="bg-[linear-gradient(135deg,_#0d1f18_0%,_#163d2c_100%)] px-6 py-5">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-400/20">
                <svg class="h-5 w-5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Deactivate Account</h3>
                <p class="text-xs text-white/60 mt-0.5">This action will take effect immediately.</p>
            </div>
        </div>
    </div>
    <div class="px-6 py-5">
        <p class="text-sm text-[var(--ink)] leading-6">Are you sure you want to <span class="font-semibold text-amber-600">deactivate</span> this account? The user will be logged out of all active sessions immediately.</p>
    </div>
    <div class="flex gap-3 border-t border-[var(--line)] px-6 py-4">
        <button type="button" onclick="cancelDeactivate()" class="flex-1 rounded-full border border-[var(--line)] py-2.5 text-sm font-semibold text-[var(--muted)] hover:bg-[var(--surface)] transition">Cancel</button>
        <button type="button" onclick="confirmDeactivateAction()" class="flex-1 rounded-full bg-amber-500 py-2.5 text-sm font-bold text-white hover:bg-amber-600 transition">Yes, Deactivate</button>
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
        <div id="userFormErrors" class="hidden mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 space-y-1"></div>
        <form id="userForm" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <input type="hidden" id="userId">
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" id="full_name" required minlength="5" maxlength="60"
                       placeholder="e.g. Juan Dela Cruz"
                       title="At least two words, each with at least 2 letters."
                       class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                <p id="err_full_name" class="hidden mt-1 text-xs text-red-600"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Photo</label>
                <div class="flex items-center gap-3">
                    <span id="avatarPreviewWrap" class="inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-sm font-bold text-slate-600">
                        <img id="avatarPreview" src="" alt="" class="hidden h-full w-full object-cover">
                        <span id="avatarInitials">?</span>
                    </span>
                    <input type="file" id="avatar" accept="image/*" onchange="previewAvatar(this)"
                           class="block w-full rounded-xl border border-[var(--line)] px-3 py-2 text-sm">
                </div>
                <p class="mt-1 text-xs text-[var(--muted)]">Optional. JPG, PNG or WEBP, at least 100&times;100 pixels.</p>
                <p id="err_avatar" class="hidden mt-1 text-xs text-red-600"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" id="email" required maxlength="150"
                       placeholder="you@example.com"
                       title="This address is the account's login identifier."
                       class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                <p id="err_email" class="hidden mt-1 text-xs text-red-600"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Role <span class="text-red-500">*</span></label>
                <select id="role" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                    <option value="admin">Administrator</option>
                    <option value="inventory_staff">Inventory Staff</option>
                    <option value="accounting">Accounting</option>
                    <option value="customer">Customer</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Password <span id="passwordRequired" class="text-red-500">*</span></label>
                <input type="password" id="password" minlength="8" maxlength="32"
                       placeholder="Min 8 characters"
                       class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                <p id="err_password" class="hidden mt-1 text-xs text-red-600"></p>
                <p class="mt-1 text-xs text-[var(--muted)]">Leave blank to keep existing password when editing.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Phone</label>
                <input type="text" id="phone" maxlength="13"
                       placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                       pattern="^(09[0-9]{9}|\+639[0-9]{9})$"
                       title="Valid Philippine mobile number e.g. 09XXXXXXXXX"
                       class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none">
                <p id="err_phone" class="hidden mt-1 text-xs text-red-600"></p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Address</label>
                <textarea id="address" rows="3" maxlength="500" class="block w-full resize-none rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none" placeholder="Delivery address"></textarea>
            </div>
            <label class="md:col-span-2 inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="is_active" checked class="rounded" onchange="confirmDeactivate(this)">
                <span class="text-sm text-[var(--ink)]">Active account</span>
            </label>
        </form>
    </div>
    <div class="flex justify-end gap-3 border-t border-[var(--line)] px-7 py-5">
        <button type="button" onclick="closeUserModal()" class="rounded-full border border-[var(--line)] px-5 py-2.5 text-sm font-semibold text-[var(--muted)] hover:bg-[var(--surface)] transition">Cancel</button>
        <button type="button" onclick="document.getElementById('userForm').dispatchEvent(new Event('submit'))" class="rounded-full bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white hover:bg-[var(--primary-dark)] transition">Save User</button>
    </div>
@endsection

@push('scripts')
<script>

        function setAvatarPreview(url, initials, tone) {
            const img = document.getElementById('avatarPreview');
            const letters = document.getElementById('avatarInitials');
            const wrap = document.getElementById('avatarPreviewWrap');

            wrap.className = 'inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold '
                + (tone || 'bg-slate-200 text-slate-600');

            if (url) {
                img.src = url;
                img.classList.remove('hidden');
                letters.classList.add('hidden');
            } else {
                img.removeAttribute('src');
                img.classList.add('hidden');
                letters.textContent = initials || '?';
                letters.classList.remove('hidden');
            }
        }

        function previewAvatar(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (event) => setAvatarPreview(event.target.result, null, null);
            reader.readAsDataURL(file);
        }


        const ROLE_LABELS = {
            admin: 'Administrator',
            inventory_staff: 'Inventory Staff',
            accounting: 'Accounting',
            customer: 'Customer',
        };

        function roleLabel(role) {
            return ROLE_LABELS[role] || role;
        }

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
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
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
            clearUserFormErrors();
            resetUserForm();
            // Password required only on create
            document.getElementById('passwordRequired').style.display = user ? 'none' : '';
            document.getElementById('password').required = !user;

            if (!user) return;

            document.getElementById('userId').value = user.user_id;
            document.getElementById('full_name').value = user.full_name;
            document.getElementById('email').value = user.email;
            setAvatarPreview(user.avatar_url, user.initials, user.avatar_tone);
            document.getElementById('role').value = user.role;
            document.getElementById('is_active').checked = user.is_active;
            document.getElementById('phone').value = user.customer_profile ? user.customer_profile.phone || '' : '';
            document.getElementById('email').value = user.customer_profile ? user.customer_profile.email || '' : '';
            document.getElementById('address').value = user.customer_profile ? user.customer_profile.address || '' : '';
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        let activeLogType = 'all';

        async function loadUsers() {
            const response = await fetch('/admin-api/users', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            users = data.data || [];
            const tbody = document.getElementById('usersBody');

            tbody.innerHTML = users.length
                ? users.map((user) => `
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                ${user.avatar_url
                                    ? `<img src="${user.avatar_url}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">`
                                    : `<span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold ${user.avatar_tone}">${escapeHtml(user.initials)}</span>`}
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-[var(--ink)]">${escapeHtml(user.full_name)}</div>
                                    <div class="text-xs text-[var(--muted)]">${escapeHtml(user.role_label)}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">${escapeHtml(user.email)}</td>
                                                <td class="px-6 py-4">${user.is_active ? 'Active' : 'Inactive'}</td>
                        <td class="px-6 py-4 space-x-3">
                            <button onclick="editUser(${user.user_id})" class="text-blue-600 hover:text-blue-900">Edit</button>
                            ${ /* <button onclick="deleteUser(${user.user_id})" class="text-red-600 hover:text-red-900">Delete</button> */ "" }
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
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold ${badgeClass}">${escapeHtml(label)}</span>
                            <span class="text-xs text-gray-500 font-medium">${escapeHtml(log.user ? log.user.full_name : 'System')}</span>
                            <span class="text-xs text-gray-400 ml-auto">${logDate}</span>
                        </div>
                        <div class="text-gray-600">${escapeHtml(log.description || 'No description provided.')}</div>
                    </div>`;
                }).join('')
                : '<p class="text-gray-500">No activity logs yet.</p>';
        }

        async function loadLogs(type = 'all', page = 1) {
            activeLogType = type;
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

            const response = await fetch(`/admin-api/logs?type=${type}&page=${page}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            currentLogs = data.data || [];

            renderCurrentLogs();
            renderPagination('logsPagination', data.meta, (next) => loadLogs(activeLogType, next));
        }

        let _deactivateCheckbox = null;

        function confirmDeactivate(checkbox) {
            const userId = document.getElementById('userId').value;
            if (!userId) return; // creating — no confirmation
            if (checkbox.checked) return; // re-activating — no confirmation

            // Revert checkbox immediately, show custom modal
            checkbox.checked = true;
            _deactivateCheckbox = checkbox;
            document.getElementById('deactivateModal').classList.remove('hidden');
        }

        function confirmDeactivateAction() {
            if (_deactivateCheckbox) {
                _deactivateCheckbox.checked = false;
                _deactivateCheckbox = null;
            }
            document.getElementById('deactivateModal').classList.add('hidden');
        }

        function cancelDeactivate() {
            _deactivateCheckbox = null;
            document.getElementById('deactivateModal').classList.add('hidden');
        }

        function clearUserFormErrors() {
            document.getElementById('userFormErrors').classList.add('hidden');
            document.getElementById('userFormErrors').innerHTML = '';
            ['full_name','email','password','phone','avatar'].forEach(f => {
                const el = document.getElementById('err_' + f);
                if (el) { el.classList.add('hidden'); el.textContent = ''; }
                const input = document.getElementById(f);
                if (input) input.classList.remove('border-red-400');
            });
        }

        function showUserFieldError(field, message) {
            const el = document.getElementById('err_' + field);
            const input = document.getElementById(field);
            if (el) { el.textContent = message; el.classList.remove('hidden'); }
            if (input) input.classList.add('border-red-400');
        }

        function validateUserForm(userId) {
            clearUserFormErrors();
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const phone    = document.getElementById('phone').value.trim();
            let valid = true;

            // Full name — at least 2 words, each 2+ letters
            if (!fullName) {
                showUserFieldError('full_name', 'Full name is required.');
                valid = false;
            } else if (!/^[A-Za-z]{2,}(\s[A-Za-z]{2,})+$/.test(fullName)) {
                showUserFieldError('full_name', 'Must contain at least two words, each with at least 2 letters.');
                valid = false;
            } else if (fullName.length > 60) {
                showUserFieldError('full_name', 'Full name must not exceed 60 characters.');
                valid = false;
            }

            // Email — the login identifier, so it is required for every role
            if (!email) {
                showUserFieldError('email', 'Email address is required.');
                valid = false;
            } else if (email.length > 150) {
                showUserFieldError('email', 'Email must not exceed 150 characters.');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showUserFieldError('email', 'Enter a valid email address.');
                valid = false;
            }

            // Password — required on create, optional on edit
            if (!userId) {
                if (!password) {
                    showUserFieldError('password', 'Password is required.');
                    valid = false;
                } else if (password.length < 8) {
                    showUserFieldError('password', 'Password must be at least 8 characters.');
                    valid = false;
                } else if (password.length > 32) {
                    showUserFieldError('password', 'Password must not exceed 32 characters.');
                    valid = false;
                }
            } else if (password && (password.length < 8 || password.length > 32)) {
                showUserFieldError('password', 'Password must be 8–32 characters.');
                valid = false;
            }

            // Phone — optional but must match PH format if provided
            if (phone && !/^(09\d{9}|\+639\d{9})$/.test(phone)) {
                showUserFieldError('phone', 'Enter a valid PH number e.g. 09XXXXXXXXX or +639XXXXXXXXX.');
                valid = false;
            }

            return valid;
        }

        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const userId = document.getElementById('userId').value;

            if (!validateUserForm(userId)) return;

            // Sent as multipart so the photo can ride along. PHP does not parse
            // a multipart body on PUT, so an update posts with _method instead.
            const body = new FormData();
            body.append('full_name', document.getElementById('full_name').value.trim());
            body.append('address', document.getElementById('address').value.trim());
            body.append('role', document.getElementById('role').value);
            body.append('password', document.getElementById('password').value);
            body.append('phone', document.getElementById('phone').value.trim());
            body.append('email', document.getElementById('email').value.trim());
            body.append('is_active', document.getElementById('is_active').checked ? '1' : '0');

            const photo = document.getElementById('avatar').files[0];
            if (photo) {
                body.append('avatar', photo);
            }

            if (userId) {
                body.append('_method', 'PUT');
            }

            const response = await fetch(userId ? `/admin-api/users/${userId}` : '/admin-api/users', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body
            });

            const data = await response.json();

            if (response.ok) {
                clearUserFormErrors();
                showMessage(data.message || 'User saved.', 'success');
                closeUserModal();
                loadUsers();
                loadLogs();
            } else {
                // Show backend validation errors inline
                if (data.errors) {
                    const errBox = document.getElementById('userFormErrors');
                    const messages = Object.values(data.errors).flat();
                    errBox.innerHTML = messages.map(m => `<div>${m}</div>`).join('');
                    errBox.classList.remove('hidden');
                    // Also show per-field
                    const fieldMap = { full_name: 'full_name', email: 'email', password: 'password', phone: 'phone' };
                    Object.entries(data.errors).forEach(([field, msgs]) => {
                        if (fieldMap[field]) showUserFieldError(fieldMap[field], msgs[0]);
                    });
                } else {
                    showMessage(data.message || 'Failed to save user.', 'error');
                }
            }
        });

        loadUsers();
        loadLogs();
</script>
@endpush
