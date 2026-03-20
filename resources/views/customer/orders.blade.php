<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Orders - RANEY LUBRICANTS TRADING</title>
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
                        <a href="/cart" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l.4 2m0 0L7 14h10l2-8H5.4ZM7 14l-1 5h12M9 20a1 1 0 1 0 0 .01M17 20a1 1 0 1 0 0 .01"/></svg><span>Cart</span></a>
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
                        <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Order Tracking</div>
                        <h1 class="mt-5 text-3xl font-black leading-tight text-[var(--ink)] sm:text-4xl">Track payments, delivery progress, and completed orders.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Use the status tabs to focus on unpaid orders, items still in transit, or completed deliveries.</p>
                    </div>
                    <a href="/shop" class="rounded-xl border border-[var(--line)] bg-white/70 px-5 py-3 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Continue Shopping</a>
                </div>
            </section>

            <section class="mb-5 flex flex-wrap gap-3">
                <button type="button" onclick="setStatusFilter('all')" id="tab_all" class="rounded-full bg-[var(--ink)] px-4 py-2 text-sm font-semibold text-white">All</button>
                <button type="button" onclick="setStatusFilter('to_pay')" id="tab_to_pay" class="rounded-full border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--muted)]">To Pay</button>
                <button type="button" onclick="setStatusFilter('to_receive')" id="tab_to_receive" class="rounded-full border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--muted)]">To Receive</button>
                <button type="button" onclick="setStatusFilter('delivered')" id="tab_delivered" class="rounded-full border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--muted)]">Delivered</button>
            </section>

            <section class="overflow-hidden rounded-[1.75rem] border border-[var(--line)] bg-[var(--card-solid)] shadow-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-[rgba(246,248,251,0.9)]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Order ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Payment Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Delivery Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Balance</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Total</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Receipt</th>
                            </tr>
                        </thead>
                        <tbody id="ordersBody">
                            <tr><td colspan="7" class="px-6 py-6 text-center text-[var(--muted)]">Loading orders...</td></tr>
                        </tbody>
                    </table>
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
        let allOrders = [];
        let activeFilter = 'all';

        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

        function setStatusFilter(filter) {
            activeFilter = filter;

            ['all', 'to_pay', 'to_receive', 'delivered'].forEach((tab) => {
                const element = document.getElementById(`tab_${tab}`);
                if (!element) {
                    return;
                }

                if (tab === filter) {
                    element.className = 'rounded-full bg-[var(--ink)] px-4 py-2 text-sm font-semibold text-white';
                } else {
                    element.className = 'rounded-full border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--muted)]';
                }
            });

            renderOrders();
        }

        function renderOrders() {
            const tbody = document.getElementById('ordersBody');
            const orders = activeFilter === 'all'
                ? allOrders
                : allOrders.filter((order) => order.status_group === activeFilter);

            tbody.innerHTML = orders.length
                ? orders.map((order) => `
                    <tr class="border-b border-[var(--line)]">
                        <td class="px-6 py-4 font-semibold text-[var(--ink)]">#${order.sale_id}</td>
                        <td class="px-6 py-4 text-[var(--muted)]">${new Date(order.sale_date).toLocaleString()}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${order.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : order.payment_status === 'partial' ? 'bg-amber-100 text-amber-700' : order.payment_status === 'processing' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700'}">
                                ${order.payment_status}
                            </span>
                            ${order.processing_requests > 0 ? '<div class="mt-2 text-xs font-medium text-sky-700">Payment request under review</div>' : ''}
                        </td>
                        <td class="px-6 py-4 capitalize text-[var(--muted)]">${order.delivery_status.replaceAll('_', ' ')}</td>
                        <td class="px-6 py-4">${formatCurrency(order.balance_due)}</td>
                        <td class="px-6 py-4 font-semibold text-[var(--ink)]">${formatCurrency(order.total_amount)}</td>
                        <td class="px-6 py-4">
                            <a href="/orders/${order.sale_id}" class="font-semibold text-[var(--primary)] transition hover:text-[var(--ink)]">View Receipt</a>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="7" class="px-6 py-6 text-center text-[var(--muted)]">No orders found in this status.</td></tr>';
        }

        async function loadOrders() {
            const response = await fetch('/shop-api/orders', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            allOrders = data.data || [];
            renderOrders();
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

        loadOrders();
    </script>
</body>
</html>