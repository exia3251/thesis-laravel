@extends('layouts.customer')

@section('title', 'Cart - RANEY LUBRICANTS TRADING')

@section('content')

    <main class="container mx-auto px-4 py-8 sm:px-6">
        <section class="mb-6 rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Checkout Ready</div>
                    <h1 class="mt-4 text-2xl font-black leading-tight text-[var(--ink)] sm:text-4xl">Shopping cart and delivery-ready checkout.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Check what is still in stock, confirm your delivery details, and choose how you want to pay.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="/shop" class="rounded-xl border border-[var(--line)] bg-white/70 px-5 py-3 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Continue Shopping</a>
                    <a href="/orders" class="rounded-xl border border-[var(--line)] bg-white/70 px-5 py-3 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">My Orders</a>
                </div>
            </div>
        </section>

        <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

        <section class="mb-6 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Delivery Address</div>
                    <a href="/profile#address" id="profileAddressEdit" class="hidden shrink-0 text-xs font-semibold text-[var(--primary)] hover:underline">Change</a>
                </div>
                <p id="profileAddress" class="mt-3 text-sm leading-7 text-[var(--ink)]">Loading saved address...</p>
                <p id="profileAddressPhone" class="mt-1 hidden text-sm text-[var(--muted)]"></p>
                <a href="/profile#address" id="profileAddressAdd"
                   class="mt-3 hidden items-center gap-1.5 text-sm font-semibold text-[var(--primary)] hover:underline">
                    Add your delivery address
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                </a>
            </div>
            <div class="rounded-[1.5rem] border border-[var(--accent-soft)] bg-[#fffcf3] p-5 shadow-sm sm:p-6">
                <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[#9d7b20]">Checkout Reminder</div>
                <p class="mt-3 text-sm leading-7 text-[#715b1d]">Items marked unavailable cannot be checked out. Remove them, or lower the quantity to what is still in stock.</p>
            </div>
        </section>

        <section class="mb-6 overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
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

        <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm sm:p-6">
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
                    @php
                        // Built from the floor rather than hard-coded. These
                        // were 20/50/75 while the floor was 20; when the floor
                        // moved to 50 the first button went on offering an
                        // amount the checkout rejects, so the shortcut handed
                        // the customer a validation error.
                        $floor = (int) config('payments.minimum_down_payment_percent');
                        $shortcuts = collect([$floor, (int) (round((($floor + 100) / 2) / 5) * 5), 90])
                            ->filter(fn ($p) => $p >= $floor && $p < 100)
                            ->unique()
                            ->sort()
                            ->values();
                    @endphp
                    <div class="flex gap-2">
                        @foreach ($shortcuts as $percent)
                            <button type="button" onclick="setSplitPercent({{ $percent }})" class="rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-xs font-semibold text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">{{ $percent }}%</button>
                        @endforeach
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

            @if (auth()->user()?->needsDeliveryDetails())
                {{-- The server refuses this order anyway. Saying so here,
                     next to the button, beats letting somebody choose a
                     payment plan and then be turned away. --}}
                <div class="mt-5 flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm leading-6 text-amber-900">
                        We need a phone number and a delivery address before this order can be placed.
                    </p>
                    <a href="/profile#address" class="shrink-0 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white transition hover:brightness-110">
                        Add delivery details
                    </a>
                </div>
            @endif

            <div class="mt-5 flex flex-col items-start justify-between gap-4 border-t border-[var(--line)] pt-5 sm:flex-row sm:items-end">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Order total</div>
                    <div class="mt-2 text-3xl font-black text-[var(--primary)]" id="cartTotal">PHP 0.00</div>
                </div>
                <button id="checkoutButton" onclick="placeOrder()"
                        @if (auth()->user()?->needsDeliveryDetails()) data-needs-details="1" @endif
                        class="rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110">Place Order</button>
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
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        function updateCheckoutState() {
            const hasUnavailableItems = cartItems.some((item) => item.is_unavailable);
            const checkoutButton = document.getElementById('checkoutButton');
            checkoutButton.disabled = hasUnavailableItems || cartItems.length === 0 || splitAmountError() !== null
                || checkoutButton.dataset.needsDetails === '1';
            checkoutButton.classList.toggle('opacity-60', checkoutButton.disabled);
            checkoutButton.classList.toggle('cursor-not-allowed', checkoutButton.disabled);
        }

        function renderCart() {
            const tbody = document.getElementById('cartBody');
            const total = cartItems
                .filter((item) => !item.is_unavailable)
                .reduce((sum, item) => sum + Number(item.subtotal), 0);
            orderTotal = total;
            document.getElementById('cartTotal').textContent = formatCurrency(total);
            refreshPlanPreviews();
            updateCheckoutState();

            tbody.innerHTML = cartItems.length
                ? cartItems.map((item) => `
                    <tr class="border-b border-[var(--line)] ${item.is_unavailable ? 'bg-red-50/50' : ''}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                ${item.image_url
                                    ? `<img src="${item.image_url}" alt="${escapeHtml(item.product_name)}" class="h-16 w-16 rounded-2xl border border-[var(--line)] bg-white object-contain p-1 ${item.is_unavailable ? 'grayscale opacity-60' : ''}">`
                                    : `<div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-[var(--line)] bg-slate-100 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">No Image</div>`}
                                <div>
                                    <div class="font-semibold text-[var(--ink)]">${escapeHtml(item.product_name)}</div>
                                    <div class="text-sm text-[var(--muted)]">${escapeHtml(item.brand)} &middot; ${escapeHtml(item.unit)}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">${formatCurrency(item.price)}</td>
                        <td class="px-6 py-4">
                            <div class="inline-flex items-stretch overflow-hidden rounded-xl border border-[var(--line)] ${item.is_unavailable ? 'bg-slate-100' : 'bg-white'}">
                                <button type="button" class="qty-step flex w-8 items-center justify-center text-lg font-bold text-[var(--muted)] transition hover:bg-[var(--primary-soft)] hover:text-[var(--primary)] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-[var(--muted)]"
                                        data-cart="${item.cart_id}" data-step="-1" aria-label="One fewer" ${item.is_unavailable ? 'disabled' : ''}>&minus;</button>

                                <input class="qty-input w-12 border-x border-[var(--line)] px-2 py-2 text-center outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary-soft)] ${item.is_unavailable ? 'bg-slate-100 text-slate-400' : 'bg-white'}"
                                       type="text" inputmode="numeric" autocomplete="off" pattern="[0-9]*" maxlength="3"
                                       data-cart="${item.cart_id}" data-max="${Math.max(item.stock, 1)}" data-was="${item.quantity}"
                                       value="${item.quantity}" ${item.is_unavailable ? 'disabled' : ''}>

                                <button type="button" class="qty-step flex w-8 items-center justify-center text-lg font-bold text-[var(--muted)] transition hover:bg-[var(--primary-soft)] hover:text-[var(--primary)] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-[var(--muted)]"
                                        data-cart="${item.cart_id}" data-step="1" aria-label="One more" ${item.is_unavailable ? 'disabled' : ''}>+</button>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            ${item.is_unavailable
                                ? `<span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        Unavailable
                                    </span>
                                    <div class="mt-1.5 text-xs leading-5 text-red-700">${escapeHtml(item.unavailable_reason)}</div>`
                                : `<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        Available
                                    </span>
                                    <div class="mt-1.5 text-xs text-[var(--muted)]">${item.stock} in stock</div>`}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ${item.is_unavailable
                                ? `<div class="font-semibold text-slate-400 line-through">${formatCurrency(item.subtotal)}</div>
                                   <div class="mt-0.5 text-xs text-[var(--muted)]">not counted</div>`
                                : `<div class="font-semibold text-[var(--ink)]">${formatCurrency(item.subtotal)}</div>`}
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="removeItem(${item.cart_id}, this)" title="Remove from cart" class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-xs font-semibold text-[var(--muted)] transition hover:border-red-300 hover:bg-red-50 hover:text-red-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.35 9m-4.78 0L9.26 9M19 7l-.87 12.14A2 2 0 0 1 16.14 21H7.86a2 2 0 0 1-1.99-1.86L5 7m5-3h4a1 1 0 0 1 1 1v2H9V5a1 1 0 0 1 1-1Z"/></svg>
                                Remove
                            </button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="6" class="px-6 py-6 text-center text-[var(--muted)]">Your cart is empty.</td></tr>';
        }

        async function loadCart() {
            const response = await fetch('/shop-api/cart', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            cartItems = data.data || [];
            setCartCount(data.cart_count);
            renderCart();
        }

        /*
         * The address the order will be sent to.
         *
         * This read `profile.address`, which stopped existing when the single
         * address line was split into house, barangay, city, province and
         * postal code. The read found nothing, so the panel showed "complete
         * your profile address" to everybody -- including customers whose
         * address was complete and printed on their last receipt. The one
         * thing this panel exists to confirm was the one thing it never said.
         *
         * It reads `full_address`, which the profile composes from those
         * fields, and asks `is_complete` whether anything is actually missing
         * rather than inferring it from an empty string.
         */
        async function loadProfileSummary() {
            const response = await fetch('/shop-api/profile', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            const profile = data.data || {};

            const line = document.getElementById('profileAddress');
            const phone = document.getElementById('profileAddressPhone');
            const change = document.getElementById('profileAddressEdit');
            const add = document.getElementById('profileAddressAdd');

            // Assigned as text, so the escaping the old version did on the way
            // into textContent -- which showed &amp; to anybody living on a
            // street with an ampersand in it -- is gone with it.
            if (profile.full_address) {
                line.textContent = profile.full_address;
                line.classList.remove('text-[var(--muted)]');
            } else {
                line.textContent = 'No delivery address saved yet.';
                line.classList.add('text-[var(--muted)]');
            }

            phone.textContent = profile.phone || '';
            phone.classList.toggle('hidden', !profile.phone);

            // Complete means deliverable, which needs a phone as well as an
            // address -- the same test the Place Order button is gated on, so
            // the panel cannot say one thing while the button does another.
            change.classList.toggle('hidden', !profile.is_complete);
            add.classList.toggle('hidden', !!profile.is_complete);
            add.classList.toggle('inline-flex', !profile.is_complete);

            if (!profile.is_complete) {
                add.textContent = profile.full_address && !profile.phone
                    ? 'Add a phone number for the courier'
                    : 'Add your delivery address';
            }
        }

        /*
         * The same rules as the product page: digits only, refused at the
         * keystroke, and steppers that clamp rather than count.
         *
         * Delegated from the table because the rows are rebuilt after every
         * change, so anything bound to a row would be bound to a row that no
         * longer exists. Installed once, below.
         */
        (function guardCartQuantities() {
            const table = document.getElementById('cartBody') || document;
            const strip = (text) => String(text ?? '').replace(/[^0-9]/g, '');
            const ceilingOf = (input) => Number(input.dataset.max) || 1;

            const refreshRow = (input) => {
                const row = input.closest('div');
                const value = parseInt(input.value, 10);
                const [down, up] = row.querySelectorAll('.qty-step');

                if (down) down.disabled = input.disabled || !Number.isInteger(value) || value <= 1;
                if (up) up.disabled = input.disabled || (Number.isInteger(value) && value >= ceilingOf(input));
            };

            table.addEventListener('keypress', (event) => {
                const input = event.target.closest('.qty-input');
                if (!input) return;
                if (event.ctrlKey || event.metaKey || event.altKey) return;

                if (!/^[0-9]$/.test(event.key)) {
                    event.preventDefault();
                }
            });

            const insertDigitsOnly = (event, input, text) => {
                const kept = strip(text);
                event.preventDefault();

                const start = input.selectionStart ?? input.value.length;
                const end = input.selectionEnd ?? input.value.length;
                const next = (input.value.slice(0, start) + kept + input.value.slice(end)).slice(0, 3);

                input.value = next;
                const caret = Math.min(start + kept.length, next.length);
                input.setSelectionRange(caret, caret);
                refreshRow(input);
            };

            table.addEventListener('paste', (event) => {
                const input = event.target.closest('.qty-input');
                if (input) insertDigitsOnly(event, input, (event.clipboardData || window.clipboardData).getData('text'));
            });

            table.addEventListener('drop', (event) => {
                const input = event.target.closest('.qty-input');
                if (input) insertDigitsOnly(event, input, event.dataTransfer.getData('text'));
            });

            table.addEventListener('input', (event) => {
                const input = event.target.closest('.qty-input');
                if (!input) return;

                const cleaned = strip(input.value);
                if (cleaned !== input.value) {
                    input.value = cleaned;
                }

                refreshRow(input);
            });

            // Typed changes are sent when the box is left, not on every
            // keystroke, so typing "12" does not order 1 and then 12.
            table.addEventListener('change', (event) => {
                const input = event.target.closest('.qty-input');
                if (!input) return;

                clearTimeout(stepTimers.get(input));
                commitQuantity(input);
            });

            table.addEventListener('click', (event) => {
                const button = event.target.closest('.qty-step');
                if (!button || button.disabled) return;

                const input = button.parentElement.querySelector('.qty-input');
                const current = parseInt(input.value, 10);
                const usable = Number.isInteger(current) && current >= 1;
                const next = usable ? current + Number(button.dataset.step) : 1;

                input.value = Math.min(Math.max(next, 1), ceilingOf(input));
                refreshRow(input);

                // Pressing + ten times is one decision, not ten. Without
                // this each press sent its own request and reloaded the
                // cart under the finger doing the pressing.
                clearTimeout(stepTimers.get(input));
                stepTimers.set(input, setTimeout(() => commitQuantity(input), 400));
            });
        })();

        /** One pending commit per row, so rapid presses collapse into one. */
        const stepTimers = new WeakMap();

        /**
         * Sends a quantity only once it is a whole number of at least one and
         * within stock. Anything else is put back to what the row last held,
         * with a reason, rather than travelling to the server to be refused.
         */
        function commitQuantity(input) {
            const previous = Number(input.dataset.was) || 1;
            const ceiling = Number(input.dataset.max) || 1;
            const value = parseInt(input.value, 10);

            if (!Number.isInteger(value) || value < 1) {
                input.value = previous;
                showMessage('Quantity has to be a whole number of at least 1.', 'error');
                return;
            }

            if (value > ceiling) {
                input.value = ceiling;
                showMessage(`Only ${ceiling} left in stock.`, 'error');
                return;
            }

            if (value === previous) return;

            updateQuantity(Number(input.dataset.cart), value, input.parentElement);
        }

        async function updateQuantity(cartId, quantity, row = null) {
            /* The steppers rest while the row is being written.

               Presses already collapse into one request through the timer
               above, but that only covers presses before the request goes --
               a press during it opened a second one, and whichever answer
               arrived last decided what the row said. The plus and minus stop
               for the round trip; the box itself is left alone, because it
               only commits when it loses focus. */
            const steppers = row ? [...row.querySelectorAll('.qty-step')] : [];
            const wasDisabled = steppers.map((button) => button.disabled);

            steppers.forEach((button) => { button.disabled = true; });

            try {
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
                setCartCount(data.cart_count);
                showMessage(data.message || 'Cart updated.', response.ok ? 'success' : 'error');
                if (response.ok) loadCart();
            } finally {
                // A successful change rebuilds the row, so putting these back
                // only matters when the row survives -- which is every failure.
                steppers.forEach((button, index) => {
                    if (button.isConnected) button.disabled = wasDisabled[index];
                });
            }
        }

        async function removeItem(cartId, button = null) {
            await withBusy(button, async () => {
                const response = await fetch(`/shop-api/cart/${cartId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                setCartCount(data.cart_count);
                showMessage(data.message || 'Item removed.', response.ok ? 'success' : 'error');
                if (response.ok) loadCart();
            }, 'Removing');
        }

        async function placeOrder() {
            if (cartItems.length === 0) {
                showMessage('Your cart is empty.', 'error');
                return;
            }

            if (cartItems.some((item) => item.is_unavailable)) {
                showMessage('Some items are unavailable. Remove them or reduce the quantity before checking out.', 'error');
                return;
            }

            /* The one press on the storefront that must never be sent twice.
               Checkout writes a sale, its lines and its stock movements, and
               nothing about the button said the first press had been heard --
               so an impatient second press ordered the cart again. Held from
               here until the page leaves, or until the order is refused. */
            const button = document.getElementById('checkoutButton');
            const done = startBusy(button, 'Placing order');

            try {
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

                if (!response.ok) {
                    done();
                    return;
                }

                // Left busy from here: the page is about to navigate, and a
                // button that came back to life first is one somebody presses.
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
            } catch (error) {
                // A dropped connection used to leave the button spinning for
                // good, with no way to try again but a reload.
                done();
                showMessage('Could not reach the server. Please try again.', 'error');
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
