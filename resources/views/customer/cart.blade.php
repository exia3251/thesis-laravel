<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cart - RANEY LUBRICANTS TRADING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --surface: #f6f8fb;
            --card: rgba(255, 255, 255, 0.82);
            --card-solid: #ffffff;
            --ink: #16202a;
            --muted: #6f7d8c;
            --line: rgba(21, 35, 54, 0.1);
            --primary: #148a67;
            --primary-soft: rgba(20, 138, 103, 0.1);
            --accent: #d9b14a;
            --accent-soft: rgba(217, 177, 74, 0.12);
        }
    </style>
</head>
<body class="bg-[var(--surface)] text-[var(--ink)]">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)]">
        <header class="sticky top-0 z-50 border-b border-[var(--line)] bg-[rgba(246,248,251,0.84)] backdrop-blur-xl">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="flex min-h-16 flex-col gap-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <a href="/shop" class="group block">
                        <div class="text-lg font-black tracking-tight sm:text-xl">
                            <span class="text-[var(--primary)]">RANEY</span>
                            <span class="text-[var(--accent)]"> LUBRICANTS</span>
                        </div>
                        <div class="text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
                    </a>

                    <div class="flex flex-wrap gap-2 items-center">
                        <a href="/shop" class="rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">Shop</a>
                        <a href="/orders" class="rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">Orders</a>
                        <a href="/profile" class="rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">Profile</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="container mx-auto px-4 py-8 sm:px-6">
            <section class="mb-8 rounded-[2rem] border border-[var(--line)] bg-[linear-gradient(135deg,_rgba(255,255,255,0.84),_rgba(255,255,255,0.7))] px-6 py-7 shadow-xl backdrop-blur-xl sm:px-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Checkout Ready</div>
                        <h1 class="mt-5 text-3xl font-black leading-tight text-[var(--ink)] sm:text-4xl">Shopping cart and delivery-ready checkout.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Review active items, confirm your saved delivery details, and place an order only when stock is still available.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="/shop" class="rounded-xl border border-[var(--line)] bg-white/70 px-5 py-3 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Continue Shopping</a>
                        <a href="/orders" class="rounded-xl border border-[var(--line)] bg-white/70 px-5 py-3 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">My Orders</a>
                    </div>
                </div>
            </section>

            <div id="message" class="hidden mb-4 rounded-2xl px-4 py-3 shadow-sm"></div>

            <section class="mb-6 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="rounded-[1.75rem] border border-[var(--line)] bg-[var(--card)] p-6 shadow-lg backdrop-blur">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Delivery Address</div>
                    <p id="profileAddress" class="mt-3 text-sm leading-7 text-[var(--ink)]">Loading saved address...</p>
                </div>
                <div class="rounded-[1.75rem] border border-[var(--accent-soft)] bg-[rgba(255,252,243,0.92)] p-6 shadow-lg">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[#9d7b20]">Checkout Reminder</div>
                    <p class="mt-3 text-sm leading-7 text-[#715b1d]">Unavailable items cannot be checked out. Remove them first if stock has changed after they were added to your cart.</p>
                </div>
            </section>

            <section class="mb-6 overflow-hidden rounded-[1.75rem] border border-[var(--line)] bg-[var(--card-solid)] shadow-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-[rgba(246,248,251,0.9)]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Product</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Price</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Quantity</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Subtotal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Action</th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <tr><td colspan="6" class="px-6 py-6 text-center text-[var(--muted)]">Loading cart...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-[1.75rem] border border-[var(--line)] bg-[var(--card)] p-6 shadow-lg backdrop-blur">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:items-end">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Payment Method</label>
                        <select id="payment_method" class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3 outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]">
                            <option value="cash_on_delivery">Cash on Delivery</option>
                            <option value="gcash">GCash</option>
                            <option value="cash">Cash</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end sm:justify-end">
                        <div class="text-left sm:text-right">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Total</div>
                            <div class="mt-2 text-3xl font-black text-[var(--primary)]" id="cartTotal">PHP 0.00</div>
                        </div>
                        <button id="checkoutButton" onclick="placeOrder()" class="rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110">Place Order</button>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-[var(--line)] bg-[rgba(255,255,255,0.82)] backdrop-blur">
            <div class="container mx-auto grid gap-6 px-4 py-8 md:grid-cols-4">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[0.22em] text-[var(--ink)]">RANEY LUBRICANTS TRADING</h3>
                    <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Review your lubricant orders with saved address and payment preferences before checkout.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Remove unavailable items and make sure your profile details are complete before placing orders.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">Payments & Logistics</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Cash, GCash, cash on delivery, and other payment methods are supported in the checkout flow.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">About</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">The cart is connected to stock validation so current availability stays reliable before ordering.</p>
                </div>
            </div>
            <div class="border-t border-[var(--line)] px-4 py-4 text-center text-sm text-[var(--muted)]">
                &copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.
            </div>
        </footer>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let cartItems = [];
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `mb-4 rounded-2xl px-4 py-3 shadow-sm ${type === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'}`;
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

        function updateCheckoutState() {
            const hasInactiveItems = cartItems.some((item) => item.is_inactive);
            const checkoutButton = document.getElementById('checkoutButton');
            checkoutButton.disabled = hasInactiveItems || cartItems.length === 0;
            checkoutButton.classList.toggle('opacity-60', checkoutButton.disabled);
            checkoutButton.classList.toggle('cursor-not-allowed', checkoutButton.disabled);
        }

        function renderCart() {
            const tbody = document.getElementById('cartBody');
            const total = cartItems
                .filter((item) => !item.is_inactive)
                .reduce((sum, item) => sum + Number(item.subtotal), 0);
            document.getElementById('cartTotal').textContent = formatCurrency(total);
            updateCheckoutState();

            tbody.innerHTML = cartItems.length
                ? cartItems.map((item) => `
                    <tr class="border-b border-[var(--line)] ${item.is_inactive ? 'bg-red-50/60' : ''}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                ${item.image_url
                                    ? `<img src="${item.image_url}" alt="${item.product_name}" class="h-16 w-16 rounded-2xl border border-[var(--line)] object-cover">`
                                    : `<div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-[var(--line)] bg-slate-100 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">No Image</div>`}
                                <div>
                                    <div class="font-semibold text-[var(--ink)]">${item.product_name}</div>
                                    <div class="text-sm text-[var(--muted)]">${item.brand} | ${item.unit}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">${formatCurrency(item.price)}</td>
                        <td class="px-6 py-4">
                            <input type="number" min="1" step="1" max="${Math.max(item.stock, 1)}" value="${item.quantity}" onchange="updateQuantity(${item.cart_id}, this.value)" class="w-20 rounded-xl border border-[var(--line)] px-3 py-2 outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)] ${item.is_inactive ? 'bg-slate-100 text-slate-400' : 'bg-white'}" ${item.is_inactive ? 'disabled' : ''}>
                        </td>
                        <td class="px-6 py-4">
                            ${item.is_inactive
                                ? `<span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">${item.inactive_reason}</span>`
                                : `<span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Active</span>`}
                        </td>
                        <td class="px-6 py-4">${formatCurrency(item.subtotal)}</td>
                        <td class="px-6 py-4">
                            <button onclick="removeItem(${item.cart_id})" class="font-semibold text-red-600 transition hover:text-red-800">Remove</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="6" class="px-6 py-6 text-center text-[var(--muted)]">Your cart is empty.</td></tr>';
        }

        async function loadCart() {
            const response = await fetch('/shop-api/cart', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            cartItems = data.data || [];
            renderCart();
        }

        async function loadProfileSummary() {
            const response = await fetch('/shop-api/profile', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            const profile = data.data || {};
            document.getElementById('profileAddress').textContent = profile.address
                ? `${profile.address}${profile.phone ? ` | ${profile.phone}` : ''}`
                : 'Complete your profile address and phone before placing an order.';
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

            if (cartItems.some((item) => item.is_inactive)) {
                showMessage('Remove unavailable items before checkout.', 'error');
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
                    payment_method: document.getElementById('payment_method').value
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
        loadProfileSummary();
    </script>
</body>
</html>
