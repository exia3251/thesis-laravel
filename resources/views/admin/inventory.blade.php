<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inventory - Engine Oil Inventory</title>
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
                <a href="/admin/inventory" class="flex items-center px-6 py-3 text-gray-100 bg-gray-900">Inventory</a>
                <a href="/admin/sales" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Sales</a>
                <a href="/admin/reports" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Reports</a>
                @if(auth()->user()->isSuperAdmin())
                    <a href="/admin/users" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Users</a>
                @endif
                <button type="button" onclick="logout()" class="w-full text-left px-6 py-3 text-gray-300 hover:bg-gray-700">Logout</button>
            </nav>
        </div>

        <div class="ml-64 p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Inventory Management</h1>
                <p class="text-gray-600">Monitor stock levels and record stock-in or stock-out transactions.</p>
            </div>

            <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap gap-3 items-center">
                    <input type="text" id="inventorySearch" oninput="renderInventory()" placeholder="Search product or brand..." class="rounded border border-gray-300 px-3 py-2 text-sm w-64 focus:outline-none focus:border-blue-400">
                    <div class="flex gap-2 text-sm">
                        <button onclick="setStockFilter('all')" id="stockFilter-all" class="stock-filter-btn px-3 py-1.5 rounded border font-medium bg-gray-900 text-white border-gray-900">All</button>
                        <button onclick="setStockFilter('in_stock')" id="stockFilter-in_stock" class="stock-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">In Stock</button>
                        <button onclick="setStockFilter('low_stock')" id="stockFilter-low_stock" class="stock-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Low Stock</button>
                        <button onclick="setStockFilter('out_of_stock')" id="stockFilter-out_of_stock" class="stock-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Out of Stock</button>
                    </div>
                </div>
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reorder Level</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Loading inventory...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="stockModal" class="hidden fixed inset-0 bg-gray-900/60 p-4">
        <div class="mx-auto mt-16 w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h2 id="stockModalTitle" class="text-xl font-bold text-slate-900">Stock Adjustment</h2>
                    <p id="stockModalSubtitle" class="text-sm text-slate-500">Record inventory movement.</p>
                </div>
                <button type="button" onclick="closeStockModal()" class="text-slate-500 hover:text-slate-700">Close</button>
            </div>

            <form id="stockForm" class="mt-6 space-y-4">
                <input type="hidden" id="stock_action" value="stock-in">
                <input type="hidden" id="stock_product_id">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Product</label>
                    <input type="text" id="stock_product_name" class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-3 text-slate-700" readonly>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Quantity</label>
                        <input type="number" id="stock_quantity" min="1" step="1" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-3" value="1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Reference No.</label>
                        <input type="text" id="stock_reference_no" maxlength="100" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-3" placeholder="Optional reference">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Notes</label>
                    <textarea id="stock_notes" rows="4" class="mt-1 block w-full resize-none rounded-xl border border-slate-300 px-3 py-3" placeholder="Optional notes"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeStockModal()" class="rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-black">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let inventoryItems = [];
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            const isSuccess = type === 'success';
            box.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">
                        ${isSuccess
                            ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 3v4m10-4v4M6 11h12v8H6z"/></svg>'
                            : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'}
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${isSuccess ? 'Inventory Update' : 'Action Needed'}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${text}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        let activeStockFilter = 'all';

        function setStockFilter(filter) {
            activeStockFilter = filter;
            document.querySelectorAll('.stock-filter-btn').forEach(btn => {
                btn.classList.remove('bg-gray-900', 'text-white', 'border-gray-900');
                btn.classList.add('text-gray-600', 'border-gray-300');
            });
            const active = document.getElementById('stockFilter-' + filter);
            if (active) {
                active.classList.add('bg-gray-900', 'text-white', 'border-gray-900');
                active.classList.remove('text-gray-600', 'border-gray-300');
            }
            renderInventory();
        }

        function renderInventory() {
            const search = document.getElementById('inventorySearch').value.toLowerCase();
            const tbody = document.getElementById('inventoryBody');

            let items = inventoryItems.filter(item => {
                const matchSearch = item.product_name.toLowerCase().includes(search) || item.brand.toLowerCase().includes(search);
                const stock = item.quantity || 0;
                const matchFilter =
                    activeStockFilter === 'all' ||
                    (activeStockFilter === 'in_stock' && stock > item.reorder_level / 2) ||
                    (activeStockFilter === 'low_stock' && stock > 0 && stock <= item.reorder_level / 2) ||
                    (activeStockFilter === 'out_of_stock' && stock === 0);
                return matchSearch && matchFilter;
            });

            tbody.innerHTML = items.length
                ? items.map((item) => {
                    const stock = item.quantity || 0;
                    let statusColor = 'text-green-600';
                    let statusText = 'In Stock';
                    if (stock === 0) { statusColor = 'text-red-600'; statusText = 'Out of Stock'; }
                    else if (stock <= item.reorder_level / 2) { statusColor = 'text-orange-600'; statusText = 'Low Stock'; }
                    return `
                        <tr>
                            <td class="px-6 py-4">${item.product_name}</td>
                            <td class="px-6 py-4">${item.brand}</td>
                            <td class="px-6 py-4 font-semibold">${stock}</td>
                            <td class="px-6 py-4">${item.reorder_level}</td>
                            <td class="px-6 py-4 ${statusColor}">${statusText}</td>
                            <td class="px-6 py-4 space-x-3">
                                <button type="button" onclick="adjustStock(${item.product_id}, 'stock-in')" class="text-blue-600 hover:text-blue-900">Stock In</button>
                                <button type="button" onclick="adjustStock(${item.product_id}, 'stock-out')" class="text-red-600 hover:text-red-900">Stock Out</button>
                            </td>
                        </tr>`;
                }).join('')
                : '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No matching inventory records.</td></tr>';
        }

        async function loadInventory() {
            try {
                const response = await fetch('/admin-api/inventory', { headers: { Accept: 'application/json' } });
                if (response.status === 401) { window.location.href = '/admin/login'; return; }
                const data = await response.json();
                inventoryItems = data.data || [];
                renderInventory();
            } catch (error) {
                showMessage('Failed to load inventory.', 'error');
            }
        }

        function adjustStock(productId, action) {
            const item = inventoryItems.find((entry) => entry.product_id === productId);

            if (!item) {
                showMessage('Inventory item not found.', 'error');
                return;
            }

            document.getElementById('stock_action').value = action;
            document.getElementById('stock_product_id').value = productId;
            document.getElementById('stock_product_name').value = `${item.product_name} (${item.brand})`;
            document.getElementById('stock_quantity').value = 1;
            document.getElementById('stock_reference_no').value = '';
            document.getElementById('stock_notes').value = '';
            document.getElementById('stockModalTitle').textContent = action === 'stock-in' ? 'Stock In' : 'Stock Out';
            document.getElementById('stockModalSubtitle').textContent = action === 'stock-in'
                ? 'Add new inventory units to this product.'
                : 'Record stock removed from this product.';
            document.getElementById('stockModal').classList.remove('hidden');
        }

        function closeStockModal() {
            document.getElementById('stockModal').classList.add('hidden');
        }

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

        document.getElementById('stockForm').addEventListener('submit', async (event) => {
            event.preventDefault();

            const action = document.getElementById('stock_action').value;
            const response = await fetch(`/admin-api/inventory/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: Number(document.getElementById('stock_product_id').value),
                    quantity: Number(document.getElementById('stock_quantity').value),
                    reference_no: document.getElementById('stock_reference_no').value,
                    notes: document.getElementById('stock_notes').value
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Inventory updated.', response.ok ? 'success' : 'error');

            if (response.ok) {
                closeStockModal();
                loadInventory();
            }
        });

        loadInventory();
    </script>
</body>
</html>