<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Orders - Engine Oil Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Orders</h1>
                <p class="text-gray-600">Track your purchases and payment status.</p>
            </div>
            <div class="flex gap-4">
                <a href="/shop" class="text-blue-600 hover:text-blue-800">Shop</a>
                <a href="/cart" class="text-blue-600 hover:text-blue-800">Cart</a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivery Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
                    </tr>
                </thead>
                <tbody id="ordersBody">
                    <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Loading orders...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

        async function loadOrders() {
            const response = await fetch('/shop-api/orders', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            const orders = data.data || [];
            const tbody = document.getElementById('ordersBody');

            tbody.innerHTML = orders.length
                ? orders.map((order) => `
                    <tr>
                        <td class="px-6 py-4">#${order.sale_id}</td>
                        <td class="px-6 py-4">${new Date(order.sale_date).toLocaleString()}</td>
                        <td class="px-6 py-4 capitalize">${order.payment_status}</td>
                        <td class="px-6 py-4 capitalize">${order.delivery_status.replaceAll('_', ' ')}</td>
                        <td class="px-6 py-4">${formatCurrency(order.balance_due)}</td>
                        <td class="px-6 py-4">${formatCurrency(order.total_amount)}</td>
                        <td class="px-6 py-4">
                            <a href="/orders/${order.sale_id}" class="text-blue-600 hover:text-blue-900">View Receipt</a>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No orders yet.</td></tr>';
        }

        loadOrders();
    </script>
</body>
</html>
