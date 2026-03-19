<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sales - Engine Oil Inventory</title>
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
                <a href="/admin/sales" class="flex items-center px-6 py-3 text-gray-100 bg-gray-900">Sales</a>
                <a href="/admin/reports" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Reports</a>
                @if(auth()->user()->isSuperAdmin())
                    <a href="/admin/users" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Users</a>
                @endif
                <button type="button" onclick="logout()" class="w-full text-left px-6 py-3 text-gray-300 hover:bg-gray-700">Logout</button>
            </nav>
        </div>

        <div class="ml-64 p-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Sales</h1>
                    <p class="text-gray-600">Create sales, record partial payments, and review sales history.</p>
                </div>
                <button onclick="openSaleModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Create New Sale
                </button>
            </div>

            <div id="message" class="hidden mb-4 px-4 py-3 rounded"></div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivery</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="salesBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">Loading sales...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="saleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Create New Sale</h3>
                <button type="button" onclick="closeSaleModal()" class="text-gray-500 hover:text-gray-700">Close</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Customer Name</label>
                    <input type="text" id="customer_name" class="mt-1 block w-full rounded-md border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                    <select id="payment_method" class="mt-1 block w-full rounded-md border px-3 py-2">
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="cash_on_delivery">Cash on Delivery</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Paid Amount</label>
                    <input type="number" id="paid_amount" min="0" step="1" class="mt-1 block w-full rounded-md border px-3 py-2" value="0">
                </div>
            </div>

            <div class="border rounded-lg p-4 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Product</label>
                        <select id="product_select" class="mt-1 block w-full rounded-md border px-3 py-2"></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Quantity</label>
                        <input type="number" id="product_quantity" min="1" class="mt-1 block w-full rounded-md border px-3 py-2" value="1">
                    </div>
                    <div>
                        <button type="button" onclick="addSaleItem()" class="w-full bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900">Add Item</button>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Product</th>
                            <th class="text-left py-2">Qty</th>
                            <th class="text-left py-2">Price</th>
                            <th class="text-left py-2">Subtotal</th>
                            <th class="text-left py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="saleItemsBody">
                        <tr>
                            <td colspan="5" class="py-3 text-center text-gray-500">No items added yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-between items-center">
                <div class="text-lg font-semibold">Total: <span id="saleTotal">PHP 0.00</span></div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeSaleModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                    <button type="button" onclick="submitSale()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Sale</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let saleProducts = [];
        let saleItems = [];
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `mb-4 px-4 py-3 rounded ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

        async function loadSales() {
            const response = await fetch('/admin-api/sales', { headers: { Accept: 'application/json' } });

            if (response.status === 401) {
                window.location.href = '/admin/login';
                return;
            }

            const data = await response.json();
            const sales = data.data || [];
            const tbody = document.getElementById('salesBody');

            tbody.innerHTML = sales.length
                ? sales.map((sale) => `
                    <tr>
                        <td class="px-6 py-4">#${sale.sale_id}</td>
                        <td class="px-6 py-4">${new Date(sale.sale_date).toLocaleString()}</td>
                        <td class="px-6 py-4">${sale.customer_name || (sale.user && sale.user.full_name) || 'Walk-in Customer'}</td>
                        <td class="px-6 py-4">
                            <select id="payment_status_${sale.sale_id}" class="rounded border px-2 py-1">
                                <option value="unpaid" ${sale.payment_status === 'unpaid' ? 'selected' : ''}>Unpaid</option>
                                <option value="partial" ${sale.payment_status === 'partial' ? 'selected' : ''}>Partial</option>
                                <option value="paid" ${sale.payment_status === 'paid' ? 'selected' : ''}>Paid</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <select id="delivery_status_${sale.sale_id}" class="rounded border px-2 py-1">
                                <option value="to_deliver" ${sale.delivery_status === 'to_deliver' ? 'selected' : ''}>To Deliver</option>
                                <option value="to_receive" ${sale.delivery_status === 'to_receive' ? 'selected' : ''}>To Receive</option>
                                <option value="delivered" ${sale.delivery_status === 'delivered' ? 'selected' : ''}>Delivered</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <input id="paid_amount_${sale.sale_id}" type="number" min="0" step="1" value="${Math.round(Number(sale.paid_amount || 0))}" class="w-28 rounded border px-2 py-1">
                        </td>
                        <td class="px-6 py-4">${formatCurrency(sale.total_amount)}</td>
                        <td class="px-6 py-4">
                            <button type="button" onclick="updateSaleStatus(${sale.sale_id})" class="text-blue-600 hover:text-blue-900">Update</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No sales records yet.</td></tr>';
        }

        async function loadSaleProducts() {
            const response = await fetch('/admin-api/sales/products/list', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            saleProducts = data.data || [];

            const select = document.getElementById('product_select');
            select.innerHTML = saleProducts.map((product) => `
                <option value="${product.product_id}">
                    ${product.product_name} - ${formatCurrency(product.price)} (${product.inventory ? product.inventory.quantity : 0} in stock)
                </option>
            `).join('');
        }

        function openSaleModal() {
            document.getElementById('saleModal').classList.remove('hidden');
            loadSaleProducts();
        }

        function closeSaleModal() {
            document.getElementById('saleModal').classList.add('hidden');
            saleItems = [];
            document.getElementById('customer_name').value = '';
            document.getElementById('payment_method').value = 'cash';
            document.getElementById('paid_amount').value = '0';
            renderSaleItems();
        }

        function addSaleItem() {
            const productId = Number(document.getElementById('product_select').value);
            const quantity = Number(document.getElementById('product_quantity').value);
            const product = saleProducts.find((item) => item.product_id === productId);

            if (!product || quantity < 1) {
                showMessage('Select a valid product and quantity.', 'error');
                return;
            }

            const existingItem = saleItems.find((item) => item.product_id === productId);

            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                saleItems.push({
                    product_id: product.product_id,
                    product_name: product.product_name,
                    price: Number(product.price),
                    quantity
                });
            }

            renderSaleItems();
        }

        function removeSaleItem(productId) {
            saleItems = saleItems.filter((item) => item.product_id !== productId);
            renderSaleItems();
        }

        function renderSaleItems() {
            const tbody = document.getElementById('saleItemsBody');
            const total = saleItems.reduce((sum, item) => sum + item.price * item.quantity, 0);

            document.getElementById('saleTotal').textContent = formatCurrency(total);

            tbody.innerHTML = saleItems.length
                ? saleItems.map((item) => `
                    <tr class="border-b">
                        <td class="py-2">${item.product_name}</td>
                        <td class="py-2">${item.quantity}</td>
                        <td class="py-2">${formatCurrency(item.price)}</td>
                        <td class="py-2">${formatCurrency(item.price * item.quantity)}</td>
                        <td class="py-2">
                            <button type="button" onclick="removeSaleItem(${item.product_id})" class="text-red-600 hover:text-red-900">Remove</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="py-3 text-center text-gray-500">No items added yet.</td></tr>';
        }

        async function submitSale() {
            if (saleItems.length === 0) {
                showMessage('Add at least one sale item.', 'error');
                return;
            }

            const response = await fetch('/admin-api/sales', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    customer_name: document.getElementById('customer_name').value || 'Walk-in Customer',
                    payment_method: document.getElementById('payment_method').value,
                    paid_amount: document.getElementById('paid_amount').value || 0,
                    items: saleItems
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Sale saved.', response.ok ? 'success' : 'error');

            if (response.ok) {
                closeSaleModal();
                loadSales();
            }
        }

        async function updateSaleStatus(saleId) {
            const response = await fetch(`/admin-api/sales/${saleId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    payment_status: document.getElementById(`payment_status_${saleId}`).value,
                    delivery_status: document.getElementById(`delivery_status_${saleId}`).value,
                    paid_amount: document.getElementById(`paid_amount_${saleId}`).value || 0
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Sale status updated.', response.ok ? 'success' : 'error');

            if (response.ok) {
                loadSales();
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

        loadSales();
    </script>
</body>
</html>
