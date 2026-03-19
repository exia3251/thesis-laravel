<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cart - Engine Oil Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
                <p class="text-gray-600">Review your items and place your order.</p>
            </div>
            <div class="flex gap-4">
                <a href="/shop" class="text-blue-600 hover:text-blue-800">Continue Shopping</a>
                <a href="/orders" class="text-blue-600 hover:text-blue-800">My Orders</a>
            </div>
        </div>

        <div id="message" class="hidden mb-4 px-4 py-3 rounded"></div>

        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading cart...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                    <select id="payment_method" class="mt-1 block w-full rounded-md border px-3 py-2">
                        <option value="cash_on_delivery">Cash on Delivery</option>
                        <option value="gcash">GCash</option>
                        <option value="cash">Cash</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <input type="text" id="notes" class="mt-1 block w-full rounded-md border px-3 py-2" placeholder="Optional notes">
                </div>
                <div class="text-right">
                    <div class="text-lg font-semibold mb-2">Total: <span id="cartTotal">PHP 0.00</span></div>
                    <button onclick="placeOrder()" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700">Place Order</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let cartItems = [];

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `hidden mb-4 px-4 py-3 rounded ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            box.classList.remove('hidden');
        }

        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

        function renderCart() {
            const tbody = document.getElementById('cartBody');
            const total = cartItems.reduce((sum, item) => sum + Number(item.subtotal), 0);
            document.getElementById('cartTotal').textContent = formatCurrency(total);

            tbody.innerHTML = cartItems.length
                ? cartItems.map((item) => `
                    <tr>
                        <td class="px-6 py-4">${item.product_name}<div class="text-sm text-gray-500">${item.brand} | ${item.unit}</div></td>
                        <td class="px-6 py-4">${formatCurrency(item.price)}</td>
                        <td class="px-6 py-4">
                            <input type="number" min="1" max="${item.stock}" value="${item.quantity}" onchange="updateQuantity(${item.cart_id}, this.value)" class="w-20 rounded-md border px-2 py-1">
                        </td>
                        <td class="px-6 py-4">${formatCurrency(item.subtotal)}</td>
                        <td class="px-6 py-4">
                            <button onclick="removeItem(${item.cart_id})" class="text-red-600 hover:text-red-900">Remove</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Your cart is empty.</td></tr>';
        }

        async function loadCart() {
            const response = await fetch('/shop-api/cart', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            cartItems = data.data || [];
            renderCart();
        }

        async function updateQuantity(cartId, quantity) {
            const response = await fetch(`/shop-api/cart/${cartId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ quantity })
            });

            const data = await response.json();
            showMessage(data.message || 'Cart updated.', response.ok ? 'success' : 'error');
            if (response.ok) loadCart();
        }

        async function removeItem(cartId) {
            const response = await fetch(`/shop-api/cart/${cartId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            showMessage(data.message || 'Item removed.', response.ok ? 'success' : 'error');
            if (response.ok) loadCart();
        }

        async function placeOrder() {
            if (cartItems.length === 0) {
                showMessage('Your cart is empty.', 'error');
                return;
            }

            const response = await fetch('/shop-api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    payment_method: document.getElementById('payment_method').value,
                    notes: document.getElementById('notes').value
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Order placed.', response.ok ? 'success' : 'error');
            if (response.ok) {
                loadCart();
                setTimeout(() => {
                    window.location.href = '/orders';
                }, 800);
            }
        }

        loadCart();
    </script>
</body>
</html>
