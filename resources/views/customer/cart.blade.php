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
                        <a href="/shop" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5M5.25 9.5V20a1 1 0 0 0 1 1h11.5a1 1 0 0 0 1-1V9.5"/></svg><span>Shop</span></a>
                        <a href="/orders" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h10M9 9h10M9 13h10M5 5h.01M5 9h.01M5 13h.01M5 17h.01M9 17h10"/></svg><span>Orders</span></a>
                        <a href="/profile" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19a4 4 0 0 0-8 0m8 0h4m-4 0H5m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg><span>Profile</span></a>
                        <button onclick="logout()" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"/></svg><span>Logout</span></button>
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

            <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

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

        <footer class="border-t border-[var(--line)] bg-[rgba(255,255,255,0.88)] backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="space-y-4">
                        <div>
                            <div class="text-xl font-black tracking-tight">
                                <span class="text-[var(--primary)]">RANEY</span>
                                <span class="text-[var(--accent)]"> LUBRICANTS</span>
                            </div>
                            <div class="mt-0.5 text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
                        </div>
                        <p class="text-sm leading-6 text-[var(--muted)]">Your trusted partner for premium engine oils and lubricants. Quality products for maximum performance.</p>
                        <div class="flex gap-3">
                            <span class="cursor-pointer flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            </span>
                            <span class="cursor-pointer flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                            </span>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">About Us</h4>
                        <ul class="space-y-2 text-sm">
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Our Story</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Our Products</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Why Choose Us</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Careers</span></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                        <ul class="space-y-2 text-sm">
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">My Account</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Order Tracking</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Shipping Info</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Returns &amp; Refunds</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">FAQs</span></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Contact Us</h4>
                        <ul class="space-y-3 text-sm text-[var(--muted)]">
                            <li class="flex items-start gap-3">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>123 Industrial Ave, Makati City, Metro Manila, Philippines</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span>+63 2 1234 5678</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>sales@raneylubricants.ph</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Mon – Sat: 8AM – 6PM</span>
                            </li>
                        </ul>
                    </div>

                </div>

                <div class="mt-8 border-t border-[var(--line)] pt-6 flex flex-col items-center justify-between gap-4 text-center sm:flex-row sm:text-left">
                    <p class="text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.</p>
                    <div class="flex gap-4 text-xs text-[var(--muted)]">
                        <span class="cursor-pointer hover:text-[var(--primary)]">Privacy Policy</span>
                        <span class="cursor-pointer hover:text-[var(--primary)]">Terms of Service</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let cartItems = [];
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            const isSuccess = type === 'success';
            box.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">
                        ${isSuccess
                            ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l.4 2m0 0L7 14h10l2-8H5.4ZM7 14l-1 5h12M9 20a1 1 0 1 0 0 .01M17 20a1 1 0 1 0 0 .01"/></svg>'
                            : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'}
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${isSuccess ? 'Cart Update' : 'Action Needed'}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${text}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        async function logout() {
            const response = await fetch('/shop/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                window.location.href = '/shop';
            }
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
                    const orderId = data?.data?.order_id;
                    const paymentMethod = data?.data?.payment_method;

                    if (orderId && paymentMethod === 'gcash') {
                        window.location.href = `/orders/${orderId}#payment-request`;
                        return;
                    }

                    window.location.href = '/orders';
                }, 800);
            }
        }

        loadCart();
        loadProfileSummary();
    </script>
</body>
</html>