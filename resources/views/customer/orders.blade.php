<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                        <a href="/shop" class="rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">Shop</a>
                        <a href="/cart" class="rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">Cart</a>
                        <a href="/profile" class="rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">Profile</a>
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

        <footer class="border-t border-[var(--line)] bg-[rgba(255,255,255,0.82)] backdrop-blur">
            <div class="container mx-auto grid gap-6 px-4 py-8 md:grid-cols-4">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[0.22em] text-[var(--ink)]">RANEY LUBRICANTS TRADING</h3>
                    <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Track customer purchases through payment, receiving, and delivered stages.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Use the receipt view to confirm delivery address, payment details, and ordered products.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">Payments & Logistics</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Orders are grouped for to-pay, to-receive, and delivered tracking to simplify order follow-up.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">About</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Designed to support lubricant order visibility from checkout through final delivery confirmation.</p>
                </div>
            </div>
            <div class="border-t border-[var(--line)] px-4 py-4 text-center text-sm text-[var(--muted)]">
                &copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.
            </div>
        </footer>
    </div>

    <script>
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
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${order.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : order.payment_status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'}">
                                ${order.payment_status}
                            </span>
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

        loadOrders();
    </script>
</body>
</html>
