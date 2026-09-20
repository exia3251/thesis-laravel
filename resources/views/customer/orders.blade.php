@extends('layouts.customer')

@section('title', 'Orders - RANEY LUBRICANTS TRADING')

@section('content')

    <div id="orderMessage" class="hidden"></div>

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
            <button type="button" onclick="setStatusFilter('cancelled')" id="tab_cancelled" class="rounded-full border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--muted)]">Cancelled</button>
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
            <div id="ordersPagination"></div>
        </section>
    </main>


    {{-- Confirming an order is a decision about money, so it gets a real panel
         rather than a browser alert. --}}
    <div id="actionModal" class="hidden fixed inset-0 z-50 bg-black/60 overflow-y-auto no-print">
        <div class="mx-auto my-20 w-full max-w-md rounded-[1.5rem] bg-white shadow-2xl">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div id="amIconWrap" class="shrink-0 rounded-2xl p-3">
                        <svg id="amIcon" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 id="amTitle" class="text-xl font-black text-[var(--ink)]"></h2>
                        <p id="amBody" class="mt-2 text-sm leading-6 text-[var(--muted)]"></p>
                    </div>
                </div>

                <div id="amNoteWrap" class="mt-4 hidden">
                    <label class="block text-sm font-medium text-[var(--ink)]">Reason <span class="font-normal text-[var(--muted)]">(optional)</span></label>
                    <textarea id="amNote" rows="2" maxlength="500" placeholder="Tell us why, so we can improve"
                              class="mt-2 block w-full resize-none rounded-xl border border-[var(--line)] bg-white px-3 py-2.5 text-sm outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]"></textarea>
                </div>

                <p id="amError" class="mt-3 hidden text-xs font-semibold text-red-600"></p>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row">
                    <button type="button" onclick="closeActionModal()" class="flex-1 rounded-xl border border-[var(--line)] bg-white px-4 py-3 text-sm font-semibold text-[var(--muted)] transition hover:bg-[var(--surface)] hover:text-[var(--ink)]">Go back</button>
                    <button id="amConfirm" type="button" class="flex-1 rounded-xl px-4 py-3 text-sm font-bold text-white transition"></button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
        let allOrders = [];
        let activeFilter = 'all';

        function setStatusFilter(filter) {
            activeFilter = filter;

            ['all', 'to_pay', 'to_receive', 'delivered', 'cancelled'].forEach((tab) => {
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
                    <tr class="border-b border-[var(--line)] ${order.order_status === 'cancelled' ? 'bg-slate-50' : ''}">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-[var(--ink)]">#${order.sale_id}</div>
                            ${order.receipt_no ? `<div class="mt-1 text-xs font-medium text-[var(--primary)]">${escapeHtml(order.receipt_no)}</div>` : ''}
                        </td>
                        <td class="px-6 py-4 text-[var(--muted)]">${new Date(order.sale_date).toLocaleString()}</td>
                        <td class="px-6 py-4">${statusBadge(order)}</td>
                        <td class="px-6 py-4 capitalize text-[var(--muted)]">
                            ${order.order_status === 'cancelled' ? '&mdash;' : order.delivery_status.replaceAll('_', ' ')}
                            ${order.received_at ? '<div class="mt-1 text-xs text-emerald-700">You confirmed receipt</div>' : ''}
                        </td>
                        <td class="px-6 py-4">${formatCurrency(order.balance_due)}</td>
                        <td class="px-6 py-4 font-semibold text-[var(--ink)]">${formatCurrency(order.total_amount)}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col items-start gap-2">
                                <a href="/orders/${order.sale_id}" class="font-semibold text-[var(--primary)] transition hover:text-[var(--ink)]">View Receipt</a>
                                <div class="flex flex-wrap gap-2">
                                    ${order.can_confirm_receipt && order.delivery_status !== 'delivered'
                                        ? `<button type="button" onclick="askConfirmReceipt(${order.sale_id})" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                Received
                                            </button>`
                                        : ''}
                                    ${order.can_cancel
                                        ? `<button type="button" onclick="askCancelOrder(${order.sale_id})" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                                Cancel
                                            </button>`
                                        : ''}
                                </div>
                            </div>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="7" class="px-6 py-6 text-center text-[var(--muted)]">No orders found in this status.</td></tr>';
        }

        async function loadOrders(page = 1) {
            const response = await fetch(`/shop-api/orders?page=${page}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            allOrders = data.data || [];

            renderOrders();
            renderPagination('ordersPagination', data.meta, loadOrders);
        }





        // ---- Status badges ----------------------------------------------
        const ICONS = {
            check:  'm4.5 12.75 6 6 9-13.5',
            clock:  'M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            cross:  'M6 18 18 6M6 6l12 12',
            half:   'M12 3v18m9-9a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            dot:    'M12 12h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            peso:   'M6 20V4h5a4 4 0 0 1 0 8H6m-1 3h8M5 11h8',
        };

        function badge(tone, icon, label) {
            return `<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ${tone}">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${ICONS[icon]}"/></svg>
                ${escapeHtml(label)}
            </span>`;
        }

        /** The one thing a customer most wants to know about an order. */
        function statusBadge(order) {
            if (order.order_status === 'cancelled') {
                const parts = [badge('bg-slate-200 text-slate-700', 'cross', 'Cancelled')];

                if (order.refund_status === 'pending') {
                    parts.push(`<div class="mt-2 text-xs font-medium text-amber-700">${formatCurrency(order.refund_amount)} refund on its way</div>`);
                } else if (order.refund_status === 'refunded') {
                    parts.push(`<div class="mt-2 text-xs font-medium text-emerald-700">${formatCurrency(order.refund_amount)} refunded</div>`);
                }

                return parts.join('');
            }

            const tones = {
                paid:       ['bg-emerald-100 text-emerald-800', 'check', 'Paid'],
                partial:    ['bg-amber-100 text-amber-800',     'half',  'Partly paid'],
                processing: ['bg-sky-100 text-sky-800',         'clock', 'Checking payment'],
                unpaid:     ['bg-slate-100 text-slate-700',     'peso',  'Not yet paid'],
            };

            const [tone, icon, label] = tones[order.payment_status] || tones.unpaid;
            const parts = [badge(tone, icon, label)];

            if (order.payment_status === 'partial' && Number(order.balance_due) > 0) {
                parts.push(`<div class="mt-2 text-xs text-[var(--muted)]">${formatCurrency(order.balance_due)} still due</div>`);
            }
            if (order.processing_requests > 0 && order.payment_status !== 'processing') {
                parts.push('<div class="mt-2 text-xs font-medium text-sky-700">Payment under review</div>');
            }

            return parts.join('');
        }

        // ---- Confirmation panel -----------------------------------------
        let pendingAction = null;

        function openActionModal({ title, body, confirmLabel, tone, icon, withNote, onConfirm }) {
            pendingAction = onConfirm;

            document.getElementById('amTitle').textContent = title;
            document.getElementById('amBody').textContent = body;
            document.getElementById('amIcon').innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="${ICONS[icon]}"/>`;
            document.getElementById('amIconWrap').className = `shrink-0 rounded-2xl p-3 ${tone.wrap}`;
            document.getElementById('amIcon').classList.value = `h-6 w-6 ${tone.icon}`;

            const confirm = document.getElementById('amConfirm');
            confirm.textContent = confirmLabel;
            confirm.className = `flex-1 rounded-xl px-4 py-3 text-sm font-bold text-white transition ${tone.button}`;
            confirm.onclick = runPendingAction;

            document.getElementById('amNoteWrap').classList.toggle('hidden', !withNote);
            document.getElementById('amNote').value = '';
            document.getElementById('amError').classList.add('hidden');

            document.getElementById('actionModal').classList.remove('hidden');
        }

        function closeActionModal() {
            document.getElementById('actionModal').classList.add('hidden');
            pendingAction = null;
        }

        async function runPendingAction() {
            if (!pendingAction) return;

            const button = document.getElementById('amConfirm');
            const original = button.textContent;
            button.disabled = true;
            button.textContent = 'Working...';

            try {
                const result = await pendingAction(document.getElementById('amNote').value.trim() || null);

                if (result.ok) {
                    closeActionModal();
                    showOrderMessage(result.message, 'success');
                    loadOrders();
                    return;
                }

                const error = document.getElementById('amError');
                error.textContent = result.message;
                error.classList.remove('hidden');
            } finally {
                button.disabled = false;
                button.textContent = original;
            }
        }

        document.getElementById('actionModal').addEventListener('click', (event) => {
            if (event.target.id === 'actionModal') closeActionModal();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeActionModal();
        });

        function showOrderMessage(text, type) {
            const box = document.getElementById('orderMessage');
            if (!box) return;

            box.textContent = text;
            box.className = `fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border px-5 py-4 text-sm font-medium shadow-2xl ${
                type === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                    : 'border-red-200 bg-red-50 text-red-900'}`;

            clearTimeout(window.__orderMessageTimer);
            window.__orderMessageTimer = setTimeout(() => box.classList.add('hidden'), 5000);
        }

        // ---- The two customer actions -----------------------------------
        function askConfirmReceipt(saleId) {
            const order = allOrders.find((o) => Number(o.sale_id) === Number(saleId));
            const owes = order && Number(order.balance_due) > 0;

            openActionModal({
                title: `Confirm order #${saleId} arrived?`,
                body: owes
                    ? `We will record that you received this order. The remaining ${formatCurrency(order.balance_due)} is still due and our staff will record it once collected.`
                    : 'We will record that you received this order and mark it complete.',
                confirmLabel: 'Yes, it arrived',
                icon: 'check',
                tone: { wrap: 'bg-emerald-100', icon: 'text-emerald-700', button: 'bg-emerald-600 hover:bg-emerald-700' },
                withNote: false,
                onConfirm: async () => {
                    const response = await fetch(`/shop-api/orders/${saleId}/receipt`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    return { ok: response.ok, message: data.message || 'Could not confirm this order.' };
                }
            });
        }

        function askCancelOrder(saleId) {
            const order = allOrders.find((o) => Number(o.sale_id) === Number(saleId));
            const paid = order ? Number(order.paid_amount) : 0;

            openActionModal({
                title: `Cancel order #${saleId}?`,
                body: paid > 0
                    ? `The items go back into stock and the ${formatCurrency(paid)} you have already paid will be refunded to your GCash by our staff.`
                    : 'The items go back into stock. Nothing has been paid on this order, so there is nothing to refund.',
                confirmLabel: 'Cancel this order',
                icon: 'cross',
                tone: { wrap: 'bg-red-100', icon: 'text-red-700', button: 'bg-red-600 hover:bg-red-700' },
                withNote: true,
                onConfirm: async (reason) => {
                    const response = await fetch(`/shop-api/orders/${saleId}/cancel`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ reason })
                    });
                    const data = await response.json();
                    return { ok: response.ok, message: data.message || 'Could not cancel this order.' };
                }
            });
        }

        loadOrders();
</script>
@endpush
