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

            <div id="message" class="hidden mb-4 px-4 py-3 rounded"></div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
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

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `mb-4 px-4 py-3 rounded ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            box.classList.remove('hidden');
        }

        async function loadInventory() {
            try {
                const response = await fetch('/admin-api/inventory', { headers: { Accept: 'application/json' } });

                if (response.status === 401) {
                    window.location.href = '/admin/login';
                    return;
                }

                const data = await response.json();
                const tbody = document.getElementById('inventoryBody');
                const items = data.data || [];

                tbody.innerHTML = items.length
                    ? items.map((item) => {
                        const stock = item.quantity || 0;
                        let statusColor = 'text-green-600';
                        let statusText = 'In Stock';

                        if (stock === 0) {
                            statusColor = 'text-red-600';
                            statusText = 'Out of Stock';
                        } else if (stock <= item.reorder_level) {
                            statusColor = 'text-orange-600';
                            statusText = 'Low Stock';
                        }

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
                            </tr>
                        `;
                    }).join('')
                    : '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No inventory records yet.</td></tr>';
            } catch (error) {
                showMessage('Failed to load inventory.', 'error');
            }
        }

        async function adjustStock(productId, action) {
            const quantity = prompt(`Enter quantity to ${action === 'stock-in' ? 'add' : 'remove'}:`);

            if (!quantity) {
                return;
            }

            const referenceNo = prompt('Reference number (optional):') || '';
            const notes = prompt('Notes (optional):') || '';

            const response = await fetch(`/admin-api/inventory/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity,
                    reference_no: referenceNo,
                    notes
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Inventory updated.', response.ok ? 'success' : 'error');

            if (response.ok) {
                loadInventory();
            }
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

        loadInventory();
    </script>
</body>
</html>
