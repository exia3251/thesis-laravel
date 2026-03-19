<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reports - Engine Oil Inventory</title>
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
                <a href="/admin/reports" class="flex items-center px-6 py-3 text-gray-100 bg-gray-900">Reports</a>
                @if(auth()->user()->isSuperAdmin())
                    <a href="/admin/users" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700">Users</a>
                @endif
                <button type="button" onclick="logout()" class="w-full text-left px-6 py-3 text-gray-300 hover:bg-gray-700">Logout</button>
            </nav>
        </div>

        <div class="ml-64 p-8 space-y-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Reports</h1>
                <p class="text-gray-600">Review operational summaries and export CSV reports for defense or backup purposes.</p>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From</label>
                        <input type="date" id="fromDate" class="mt-1 block w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To</label>
                        <input type="date" id="toDate" class="mt-1 block w-full rounded-md border px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment Status</label>
                        <select id="paymentStatus" class="mt-1 block w-full rounded-md border px-3 py-2">
                            <option value="">All</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Delivery Status</label>
                        <select id="deliveryStatus" class="mt-1 block w-full rounded-md border px-3 py-2">
                            <option value="">All</option>
                            <option value="to_deliver">To Deliver</option>
                            <option value="to_receive">To Receive</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 mt-4">
                    <button onclick="loadSalesReport()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Refresh Sales Report</button>
                    <button onclick="downloadSalesCsv()" class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">Download Sales CSV</button>
                    <button onclick="downloadInventoryCsv()" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">Download Inventory CSV</button>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Sales Report</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivery</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody id="salesReportBody">
                                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading sales report...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Inventory Report</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryReportBody">
                                <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Loading inventory report...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

        function buildQuery() {
            const params = new URLSearchParams();
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            const paymentStatus = document.getElementById('paymentStatus').value;
            const deliveryStatus = document.getElementById('deliveryStatus').value;

            if (from) params.set('from', from);
            if (to) params.set('to', to);
            if (paymentStatus) params.set('payment_status', paymentStatus);
            if (deliveryStatus) params.set('delivery_status', deliveryStatus);

            const query = params.toString();
            return query ? `?${query}` : '';
        }

        async function loadSalesReport() {
            const response = await fetch(`/admin-api/reports/sales${buildQuery()}`, {
                headers: { Accept: 'application/json' }
            });
            const data = await response.json();
            const rows = data.data || [];
            const body = document.getElementById('salesReportBody');

            body.innerHTML = rows.length
                ? rows.map((row) => `
                    <tr>
                        <td class="px-6 py-4">#${row.sale_id}</td>
                        <td class="px-6 py-4">${row.customer_name || 'Walk-in Customer'}</td>
                        <td class="px-6 py-4">${row.payment_status}</td>
                        <td class="px-6 py-4">${row.delivery_status}</td>
                        <td class="px-6 py-4">${formatCurrency(row.total_amount)}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No sales found for the selected filters.</td></tr>';
        }

        async function loadInventoryReport() {
            const response = await fetch('/admin-api/reports/inventory', {
                headers: { Accept: 'application/json' }
            });
            const data = await response.json();
            const rows = data.data || [];
            const body = document.getElementById('inventoryReportBody');

            body.innerHTML = rows.length
                ? rows.map((row) => `
                    <tr>
                        <td class="px-6 py-4">${row.product_name}</td>
                        <td class="px-6 py-4">${row.quantity}</td>
                        <td class="px-6 py-4">${formatCurrency(row.value)}</td>
                        <td class="px-6 py-4">${row.status}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No inventory records found.</td></tr>';
        }

        function downloadSalesCsv() {
            window.location.href = `/admin-api/reports/sales/export${buildQuery()}`;
        }

        function downloadInventoryCsv() {
            window.location.href = '/admin-api/reports/inventory/export';
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

        loadSalesReport();
        loadInventoryReport();
    </script>
</body>
</html>
