@extends('layouts.admin')

@section('title', 'RANEY LUBRICANTS TRADING — Admin')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-black text-[var(--ink)] sm:text-3xl">Inventory Management</h1>
        <p class="text-[var(--muted)]">Monitor stock levels and record stock-in or stock-out transactions.</p>
    </div>

    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-[var(--line)] px-4 py-4 sm:px-6">
            <input type="text" id="inventorySearch" oninput="renderInventory()" placeholder="Search product or brand..." class="rounded border border-gray-300 px-3 py-2 text-sm w-full sm:w-64 focus:outline-none focus:border-blue-400">
            <div class="flex flex-wrap gap-2 text-sm">
                <button onclick="setStockFilter('all')" id="stockFilter-all" class="stock-filter-btn px-3 py-1.5 rounded border font-medium bg-gray-900 text-white border-gray-900">All</button>
                <button onclick="setStockFilter('in_stock')" id="stockFilter-in_stock" class="stock-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">In Stock</button>
                <button onclick="setStockFilter('low_stock')" id="stockFilter-low_stock" class="stock-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Low Stock</button>
                <button onclick="setStockFilter('out_of_stock')" id="stockFilter-out_of_stock" class="stock-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Out of Stock</button>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="min-w-full">
                <thead class="bg-[var(--surface)]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Product Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Brand</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Current Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Reorder Level</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryBody" class="bg-[var(--card)] divide-y divide-[var(--line)]">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Loading inventory...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
    </div>

    <div id="stockModal" class="hidden fixed inset-0 bg-gray-900/60 p-4">
<div class="mx-auto mt-8 max-h-[85vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:mt-16 sm:p-6">
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
        <div>
            <label class="block text-sm font-medium text-slate-700">Quantity</label>
            <input type="number" id="stock_quantity" min="1" step="1" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-3" value="1">
        </div>
        <div class="flex justify-end gap-3">
            <button type="button" onclick="closeStockModal()" class="rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Cancel</button>
            <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-black">Save Adjustment</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
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
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        /* The dashboard's action list links straight here, so the filter it
           counted is applied on arrival rather than left to be found. */
        const STOCK_FILTERS = ['all', 'in_stock', 'low_stock', 'out_of_stock'];
        const requestedFilter = new URLSearchParams(window.location.search).get('stock');

        let activeStockFilter = STOCK_FILTERS.includes(requestedFilter) ? requestedFilter : 'all';

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
                            <td class="px-6 py-4">${escapeHtml(item.product_name)}</td>
                            <td class="px-6 py-4">${escapeHtml(item.brand)}</td>
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
            document.getElementById('stock_product_name').value = `${escapeHtml(item.product_name)} (${escapeHtml(item.brand)})`;
            document.getElementById('stock_quantity').value = 1;

            document.getElementById('stockModalTitle').textContent = action === 'stock-in' ? 'Stock In' : 'Stock Out';
            document.getElementById('stockModalSubtitle').textContent = action === 'stock-in'
                ? 'Add new inventory units to this product.'
                : 'Record stock removed from this product.';
            document.getElementById('stockModal').classList.remove('hidden');
        }

        function closeStockModal() {
            document.getElementById('stockModal').classList.add('hidden');
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

                })
            });

            const data = await response.json();
            showMessage(data.message || 'Inventory updated.', response.ok ? 'success' : 'error');

            if (response.ok) {
                closeStockModal();
                loadInventory();
            }
        });

        if (activeStockFilter !== 'all') setStockFilter(activeStockFilter);

        loadInventory();
</script>
@endpush
