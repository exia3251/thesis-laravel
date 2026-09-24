@extends('layouts.admin')

@section('title', 'Users - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">Users</h1>
            <p class="mt-1 text-sm text-[var(--muted)]">Staff accounts, customer accounts, and what everyone has been doing.</p>
        </div>
        <button id="addUserBtn" onclick="openUserModal()" class="rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Add account</button>
    </div>

    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="space-y-3 border-b border-[var(--line)] px-5 py-4 sm:px-6">
            <div class="flex flex-wrap items-center gap-3">
                <input type="text" id="userSearch" oninput="renderUsers()" placeholder="Search name or email..."
                       class="w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)] sm:w-72">

                <div class="inline-flex rounded-xl border border-[var(--line)] p-1">
                    <button type="button" id="tabActiveUsers" onclick="setArchivedUsers(false)"
                            class="rounded-lg px-4 py-1.5 text-sm font-semibold transition">Active</button>
                    <button type="button" id="tabArchivedUsers" onclick="setArchivedUsers(true)"
                            class="rounded-lg px-4 py-1.5 text-sm font-semibold transition">Archived</button>
                </div>

                <span id="userCount" class="ml-auto text-xs font-semibold text-[var(--muted)]"></span>
            </div>

            {{-- Staff and customers share one table, and there are far more
                 customers, so the roles are a filter rather than a scroll. --}}
            <div id="roleFilters" class="flex flex-wrap gap-2"></div>
        </div>

        <div class="admin-table-wrap">
            <table class="min-w-full">
                <thead class="bg-[var(--surface)]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)] sm:px-6">Account</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">Email</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[var(--muted)] sm:px-6">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersBody" class="divide-y divide-[var(--line)]">
                    <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-[var(--muted)]">Loading accounts...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-[var(--line)] flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-[var(--ink)]">Activity log</h2>
                <p class="mt-0.5 text-xs text-[var(--muted)]">Who did what, and when</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <button onclick="loadLogs('all')" id="tab-all" class="log-tab rounded-xl border px-3 py-1.5 text-xs font-semibold transition">All</button>
                <button onclick="loadLogs('admin')" id="tab-admin" class="log-tab rounded-xl border px-3 py-1.5 text-xs font-semibold transition">Staff</button>
                <button onclick="loadLogs('customer')" id="tab-customer" class="log-tab rounded-xl border px-3 py-1.5 text-xs font-semibold transition">Customers</button>
                <div class="mx-1 h-5 w-px bg-[var(--line)]"></div>
                <button onclick="toggleDateSort()" id="dateSortBtn"
                        class="rounded-xl border border-[var(--line)] px-3 py-1.5 text-xs font-semibold text-[var(--muted)] transition hover:text-[var(--ink)]">
                    <span id="dateSortLabel">Newest first</span>
                </button>
            </div>
        </div>
        <div class="px-6 py-3 border-b border-[var(--line)] bg-[var(--surface)] flex flex-wrap items-center gap-4">
            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Between</span>
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
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">House or building number and street</label>
                <input type="text" id="house_street" maxlength="160" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none" placeholder="123 Rizal Street">
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Barangay</label>
                <input type="text" id="barangay" maxlength="100" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none" placeholder="Poblacion">
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">City or municipality</label>
                <input type="text" id="city" maxlength="100" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none" placeholder="Makati City">
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Province</label>
                <input type="text" id="province" maxlength="100" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none" placeholder="Metro Manila">
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ink)] mb-1">Postal code</label>
                <input type="text" id="postal_code" maxlength="4" class="block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm focus:outline-none" placeholder="1200">
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
            ['house_street', 'barangay', 'city', 'province', 'postal_code'].forEach((field) => {
                document.getElementById(field).value = user.customer_profile ? user.customer_profile[field] || '' : '';
            });
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        let activeLogType = 'all';

        let showArchivedUsers = false;

        let roleFilter = 'all';

        const ROLE_FILTERS = [
            ['all', 'Everyone'],
            ['admin', 'Administrators'],
            ['inventory_staff', 'Inventory'],
            ['accounting', 'Accounting'],
            ['customer', 'Customers'],
        ];

        function renderRoleFilters() {
            const counts = users.reduce((tally, user) => {
                tally[user.role] = (tally[user.role] || 0) + 1;
                return tally;
            }, {});

            document.getElementById('roleFilters').innerHTML = ROLE_FILTERS.map(([key, label]) => {
                const on = key === roleFilter;
                const count = key === 'all' ? users.length : (counts[key] || 0);

                return `
                    <button type="button" onclick="setRoleFilter('${key}')"
                            class="inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-semibold transition ${on
                                ? 'border-[var(--primary)] bg-[var(--primary)] text-white'
                                : 'border-[var(--line)] text-[var(--muted)] hover:border-[var(--primary)] hover:text-[var(--ink)]'}">
                        ${escapeHtml(label)}
                        <span class="${on ? 'text-white/80' : 'text-[var(--ink)]'} font-black">${count}</span>
                    </button>`;
            }).join('');
        }

        function setRoleFilter(role) {
            roleFilter = role;
            renderUsers();
        }

        function renderUsers() {
            const tbody = document.getElementById('usersBody');
            const search = document.getElementById('userSearch').value.toLowerCase();

            const rows = users.filter(user =>
                (roleFilter === 'all' || user.role === roleFilter)
                && (user.full_name.toLowerCase().includes(search) || user.email.toLowerCase().includes(search)));

            renderRoleFilters();
            document.getElementById('userCount').textContent = `${rows.length} of ${users.length} accounts`;

            tbody.innerHTML = rows.length
                ? rows.map((user) => `
                    <tr class="transition hover:bg-[var(--surface)]">
                        <td class="px-5 py-3 sm:px-6">
                            <div class="flex items-center gap-3">
                                ${user.avatar_url
                                    ? `<img src="${user.avatar_url}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">`
                                    : `<span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold ${user.avatar_tone}">${escapeHtml(user.initials)}</span>`}
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-[var(--ink)]">${escapeHtml(user.full_name)}</div>
                                    <div class="text-xs text-[var(--muted)]">${escapeHtml(user.role_label)}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-sm text-[var(--muted)]">${escapeHtml(user.email)}</td>
                        <td class="px-5 py-3">
                            ${user.archived
                                ? '<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Archived</span>'
                                : (user.is_active
                                    ? '<span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Active</span>'
                                    : '<span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Inactive</span>')}
                        </td>
                        <td class="px-5 py-3 text-right sm:px-6">
                            <div class="inline-flex gap-2">
                                ${user.archived
                                    ? `<button onclick="restoreUser(${user.user_id})" class="rounded-lg border border-[var(--line)] px-2.5 py-1.5 text-xs font-bold text-[var(--primary)] transition hover:border-[var(--primary)]">Restore</button>`
                                    : `<button onclick="editUser(${user.user_id})" class="rounded-lg border border-[var(--line)] px-2.5 py-1.5 text-xs font-bold text-[var(--primary)] transition hover:border-[var(--primary)]">Edit</button>
                                       <button onclick="archiveUser(${user.user_id})" class="rounded-lg border border-[var(--line)] px-2.5 py-1.5 text-xs font-bold text-red-700 transition hover:border-red-300">Archive</button>`}
                            </div>
                        </td>
                    </tr>`).join('')
                : `<tr><td colspan="4" class="px-6 py-10 text-center text-sm text-[var(--muted)]">${showArchivedUsers ? 'Nothing archived.' : 'Nothing matches that.'}</td></tr>`;
        }

        async function loadUsers() {
            const query = showArchivedUsers ? '?archived=1' : '';
            const response = await fetch(`/admin-api/users${query}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            users = data.data || [];

            renderUsers();
        }

        function setArchivedUsers(archived) {
            showArchivedUsers = archived;

            const on = 'bg-[var(--primary)] text-white';
            const off = 'text-[var(--muted)] hover:text-[var(--ink)]';

            document.getElementById('tabActiveUsers').className = `rounded-lg px-4 py-1.5 text-sm font-semibold transition ${archived ? off : on}`;
            document.getElementById('tabArchivedUsers').className = `rounded-lg px-4 py-1.5 text-sm font-semibold transition ${archived ? on : off}`;
            document.getElementById('addUserBtn').classList.toggle('hidden', archived);

            loadUsers();
        }

        function editUser(userId) {
            const user = users.find((item) => item.user_id === userId);
            if (user) openUserModal(user);
        }

        async function archiveUser(userId) {
            const user = users.find((item) => item.user_id === userId);
            const name = user ? user.full_name : 'this account';

            const sure = await askToConfirm({
                title: `Archive ${name}?`,
                body: 'They are signed out at once and can no longer log in. Their orders and everything in the activity log are kept, and the account can be restored later.',
                confirm: 'Archive it',
                cancel: 'Leave it active',
            });

            if (!sure) return;

            const response = await fetch(`/admin-api/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            showMessage(data.message || 'Archive request completed.', response.ok ? 'success' : 'error');
            if (response.ok) loadUsers();
        }

        async function restoreUser(userId) {
            const response = await fetch(`/admin-api/users/${userId}/restore`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            showMessage(data.message || 'Restore request completed.', response.ok ? 'success' : 'error');
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
                user_archived: 'bg-red-100 text-red-800',
                user_restored: 'bg-green-100 text-green-800',
                own_account_updated: 'bg-indigo-100 text-indigo-800',
                own_password_changed: 'bg-yellow-100 text-yellow-800',
                own_password_set: 'bg-yellow-100 text-yellow-800',
                chatbot_intent_updated: 'bg-purple-100 text-purple-800',
                vehicle_spec_updated: 'bg-indigo-100 text-indigo-800',
                vehicle_spec_verified: 'bg-green-100 text-green-800',
                product_created: 'bg-green-100 text-green-800',
                product_updated: 'bg-indigo-100 text-indigo-800',
                product_deleted: 'bg-red-100 text-red-800',
                product_archived: 'bg-red-100 text-red-800',
                product_restored: 'bg-green-100 text-green-800',
                database_backup_created: 'bg-teal-100 text-teal-800',
                database_backup_downloaded: 'bg-teal-100 text-teal-800',
                database_backup_deleted: 'bg-red-100 text-red-800',
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
                const on = btn.id === 'tab-' + type;
                btn.className = 'log-tab rounded-xl border px-3 py-1.5 text-xs font-semibold transition '
                    + (on ? 'border-[var(--primary)] bg-[var(--primary)] text-white'
                          : 'border-[var(--line)] text-[var(--muted)] hover:border-[var(--primary)] hover:text-[var(--ink)]');
            });

            const container = document.getElementById('logsList');
            container.innerHTML = '<p class="text-sm text-[var(--muted)]">Loading logs...</p>';

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
            ['house_street', 'barangay', 'city', 'province', 'postal_code'].forEach((field) => {
                body.append(field, document.getElementById(field).value.trim());
            });
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

        // Paints the toggle and loads the active list.
        setArchivedUsers(false);
        loadLogs();
</script>
@endpush
