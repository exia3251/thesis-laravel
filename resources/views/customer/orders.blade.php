@extends('layouts.customer')

@section('title', 'Orders - RANEY LUBRICANTS TRADING')

@section('content')

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

@endsection

@push('scripts')
<script>
        let allOrders = [];
        let activeFilter = 'all';

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

        loadOrders();
</script>
@endpush
