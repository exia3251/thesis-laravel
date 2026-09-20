@extends('layouts.customer')

@section('title', 'Cart - RANEY LUBRICANTS TRADING')

@section('content')

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
            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">How would you like to pay?</div>

            <div class="mt-4 grid gap-3 lg:grid-cols-3">
                <label class="payment-option group relative flex cursor-pointer flex-col rounded-2xl border-2 border-[var(--line)] bg-white/70 p-4 transition hover:border-[var(--primary)]" data-plan="cod">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="payment_plan" value="cod" class="mt-1 accent-[var(--primary)]" checked onchange="onPlanChange()">
                        <div>
                            <div class="font-bold text-[var(--ink)]">Cash on Delivery</div>
                            <p class="mt-1 text-xs leading-5 text-[var(--muted)]">Pay the courier in full when your order arrives.</p>
                        </div>
                    </div>
                    <div class="mt-3 rounded-xl bg-[var(--surface)] px-3 py-2 text-xs text-[var(--muted)]">
                        Due on delivery <span class="float-right font-bold text-[var(--ink)]" data-cod-preview>PHP 0.00</span>
                    </div>
                </label>

                <label class="payment-option group relative flex cursor-pointer flex-col rounded-2xl border-2 border-[var(--line)] bg-white/70 p-4 transition hover:border-[var(--primary)]" data-plan="gcash_full">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="payment_plan" value="gcash_full" class="mt-1 accent-[var(--primary)]" onchange="onPlanChange()">
                        <div>
                            <div class="font-bold text-[var(--ink)]">GCash — pay in full</div>
                            <p class="mt-1 text-xs leading-5 text-[var(--muted)]">Send the whole amount now and upload your receipt.</p>
                        </div>
                    </div>
                    <div class="mt-3 rounded-xl bg-[var(--surface)] px-3 py-2 text-xs text-[var(--muted)]">
                        Pay by GCash <span class="float-right font-bold text-[var(--ink)]" data-gcash-preview>PHP 0.00</span>
                    </div>
                </label>

                <label class="payment-option group relative flex cursor-pointer flex-col rounded-2xl border-2 border-[var(--line)] bg-white/70 p-4 transition hover:border-[var(--primary)]" data-plan="split">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="payment_plan" value="split" class="mt-1 accent-[var(--primary)]" onchange="onPlanChange()">
                        <div>
                            <div class="font-bold text-[var(--ink)]">Down payment</div>
                            <p class="mt-1 text-xs leading-5 text-[var(--muted)]">Send part by GCash now, pay the rest on delivery.</p>
                        </div>
                    </div>
                    <div class="mt-3 rounded-xl bg-[var(--surface)] px-3 py-2 text-xs text-[var(--muted)]">
                        Minimum <span class="float-right font-bold text-[var(--ink)]" data-minimum-preview>PHP 0.00</span>
                    </div>
                </label>
            </div>

            <div id="splitPanel" class="mt-4 hidden rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-4">
                <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">GCash down payment</label>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-[var(--muted)]">PHP</span>
                        <input id="gcash_amount" type="number" step="0.01" min="0"
                               class="block w-full rounded-xl border border-[var(--line)] bg-white px-4 py-3 pl-12 outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]"
                               oninput="onSplitAmountChange()">
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="setSplitPercent(20)" class="rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-xs font-semibold text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">20%</button>
                        <button type="button" onclick="setSplitPercent(50)" class="rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-xs font-semibold text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">50%</button>
                        <button type="button" onclick="setSplitPercent(75)" class="rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-xs font-semibold text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">75%</button>
                    </div>
                </div>
                <p id="splitError" class="mt-2 hidden text-xs font-semibold text-red-600"></p>
                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-white px-3 py-2">
                        <div class="text-xs text-[var(--muted)]">Now by GCash</div>
                        <div class="font-bold text-[var(--primary)]" id="splitNow">PHP 0.00</div>
                    </div>
                    <div class="rounded-xl bg-white px-3 py-2">
                        <div class="text-xs text-[var(--muted)]">On delivery</div>
                        <div class="font-bold text-[var(--ink)]" id="splitLater">PHP 0.00</div>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-col items-start justify-between gap-4 border-t border-[var(--line)] pt-5 sm:flex-row sm:items-end">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Order total</div>
                    <div class="mt-2 text-3xl font-black text-[var(--primary)]" id="cartTotal">PHP 0.00</div>
                </div>
                <button id="checkoutButton" onclick="placeOrder()" class="rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110">Place Order</button>
            </div>
        </section>
    </main>

@endsection

@push('scripts')
<script>
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

        function updateCheckoutState() {
            const hasInactiveItems = cartItems.some((item) => item.is_inactive);
            const checkoutButton = document.getElementById('checkoutButton');
            checkoutButton.disabled = hasInactiveItems || cartItems.length === 0 || splitAmountError() !== null;
            checkoutButton.classList.toggle('opacity-60', checkoutButton.disabled);
            checkoutButton.classList.toggle('cursor-not-allowed', checkoutButton.disabled);
        }

        function renderCart() {
            const tbody = document.getElementById('cartBody');
            const total = cartItems
                .filter((item) => !item.is_inactive)
                .reduce((sum, item) => sum + Number(item.subtotal), 0);
            orderTotal = total;
            document.getElementById('cartTotal').textContent = formatCurrency(total);
            refreshPlanPreviews();
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
                    payment_plan: selectedPlan(),
                    gcash_amount: Number(document.getElementById('gcash_amount').value || 0)
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Order placed.', response.ok ? 'success' : 'error');
            if (response.ok) {
                loadCart();
                setTimeout(() => {
                    const orderId = data?.data?.order_id;
                    const plan = data?.data?.payment_plan;

                    if (orderId && plan !== 'cod') {
                        window.location.href = `/orders/${orderId}#payment-request`;
                        return;
                    }

                    window.location.href = '/orders';
                }, 800);
            }
        }


        // ---- Payment plan ------------------------------------------------
        let orderTotal = 0;
        const MIN_DOWN_PERCENT = @json((float) config('payments.minimum_down_payment_percent'));

        function selectedPlan() {
            return document.querySelector('input[name="payment_plan"]:checked')?.value || 'cod';
        }

        function minimumDownPayment() {
            return Math.round(orderTotal * MIN_DOWN_PERCENT) / 100;
        }

        function refreshPlanPreviews() {
            const min = minimumDownPayment();
            document.querySelectorAll('[data-cod-preview]').forEach(el => el.textContent = formatCurrency(orderTotal));
            document.querySelectorAll('[data-gcash-preview]').forEach(el => el.textContent = formatCurrency(orderTotal));
            document.querySelectorAll('[data-minimum-preview]').forEach(el => el.textContent = formatCurrency(min));
            onSplitAmountChange();
        }

        function onPlanChange() {
            const plan = selectedPlan();

            document.querySelectorAll('.payment-option').forEach((option) => {
                const active = option.dataset.plan === plan;
                option.classList.toggle('border-[var(--primary)]', active);
                option.classList.toggle('bg-[var(--primary-soft)]', active);
                option.classList.toggle('border-[var(--line)]', !active);
                option.classList.toggle('bg-white/70', !active);
            });

            const panel = document.getElementById('splitPanel');
            panel.classList.toggle('hidden', plan !== 'split');

            if (plan === 'split' && !document.getElementById('gcash_amount').value) {
                setSplitPercent(MIN_DOWN_PERCENT);
            }

            onSplitAmountChange();
        }

        function setSplitPercent(percent) {
            document.getElementById('gcash_amount').value = (Math.round(orderTotal * percent) / 100).toFixed(2);
            onSplitAmountChange();
        }

        /** Returns an error string when the split amount is not usable, else null. */
        function splitAmountError() {
            if (selectedPlan() !== 'split') return null;

            const amount = Number(document.getElementById('gcash_amount').value || 0);
            const min = minimumDownPayment();

            if (!amount) return 'Enter how much you want to send by GCash.';
            if (amount < min) return `The down payment must be at least ${formatCurrency(min)} (${MIN_DOWN_PERCENT}% of the order).`;
            if (amount >= orderTotal) return 'A down payment must leave a balance for delivery. Choose GCash in full instead.';

            return null;
        }

        function onSplitAmountChange() {
            const amount = Number(document.getElementById('gcash_amount').value || 0);
            const error = splitAmountError();
            const errorEl = document.getElementById('splitError');

            errorEl.textContent = error || '';
            errorEl.classList.toggle('hidden', !error);

            document.getElementById('splitNow').textContent = formatCurrency(amount);
            document.getElementById('splitLater').textContent = formatCurrency(Math.max(orderTotal - amount, 0));

            updateCheckoutState();
        }

        onPlanChange();
        loadCart();
        loadProfileSummary();
</script>
@endpush
