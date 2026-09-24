@extends('layouts.admin')

@section('title', 'Inventory - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">Inventory</h1>
            <p class="mt-1 text-sm text-[var(--muted)]">What is on the shelf, and what needs ordering.</p>
        </div>
        <button type="button" onclick="window.location.href='/admin-api/reports/inventory/export'"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
            Export CSV
        </button>
    </div>

    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    {{-- The three counts the shelf is judged by, and each one filters the
         table below it, so the number and the rows cannot disagree. --}}
    <div id="stockSummary" class="mb-5 grid gap-4 sm:grid-cols-3"></div>

    <div class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-[var(--line)] px-5 py-4 sm:px-6">
            <input type="text" id="inventorySearch" oninput="renderInventory()" placeholder="Search product or brand..."
                   class="w-full rounded-xl border border-[var(--line)] px-3.5 py-2.5 text-sm outline-none transition focus:border-[var(--primary)] sm:w-72">

            <div class="inline-flex flex-wrap gap-1 rounded-xl border border-[var(--line)] p-1 text-sm">
                <button onclick="setStockFilter('all')" id="stockFilter-all" class="stock-filter-btn rounded-lg px-3 py-1.5 font-semibold transition">All</button>
                <button onclick="setStockFilter('in_stock')" id="stockFilter-in_stock" class="stock-filter-btn rounded-lg px-3 py-1.5 font-semibold transition">In stock</button>
                <button onclick="setStockFilter('low_stock')" id="stockFilter-low_stock" class="stock-filter-btn rounded-lg px-3 py-1.5 font-semibold transition">Running low</button>
                <button onclick="setStockFilter('out_of_stock')" id="stockFilter-out_of_stock" class="stock-filter-btn rounded-lg px-3 py-1.5 font-semibold transition">Out of stock</button>
            </div>

            <span id="inventoryCount" class="ml-auto text-xs font-semibold text-[var(--muted)]"></span>
        </div>

        <div class="admin-table-wrap">
            <table class="min-w-full">
                <thead class="bg-[var(--surface)]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)] sm:px-6">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">On hand</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">Reorder at</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">Value</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[var(--muted)] sm:px-6">Movement</th>
                    </tr>
                </thead>
                <tbody id="inventoryBody" class="divide-y divide-[var(--line)] bg-[var(--card)]">
                    <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-[var(--muted)]">Loading inventory...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Stock in and stock out, with the shelf it is moving shown beside the
         number so the wrong pack size is harder to reach for. --}}
    <div id="stockModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md overflow-hidden rounded-[1.5rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-[var(--line)] px-6 py-5">
                <div>
                    <h2 id="stockModalTitle" class="text-base font-bold text-[var(--ink)]">Stock adjustment</h2>
                    <p id="stockModalSubtitle" class="mt-0.5 text-xs text-[var(--muted)]">Record inventory movement.</p>
                </div>
                <button type="button" onclick="closeStockModal()" aria-label="Close"
                        class="rounded-lg p-1.5 text-[var(--muted)] transition hover:bg-[var(--surface)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="stockForm" class="space-y-5 px-6 py-6">
                <input type="hidden" id="stock_action" value="stock-in">
                <input type="hidden" id="stock_product_id">

                <div id="stockProductCard" class="flex items-center gap-4 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-3"></div>

                <div>
                    <label for="stock_quantity" class="block text-sm font-medium text-[var(--ink)]">Quantity</label>

                    {{-- Digits only, with steppers that cannot produce a bad
                         value -- the same handling as the shop's quantity
                         box, for the same reason: a number field reports an
                         empty string for anything it cannot parse. --}}
                    <div class="mt-1.5 flex w-full max-w-[12rem] items-center rounded-xl border border-[var(--line)]">
                        <button type="button" onclick="stepStock(-1)" aria-label="One fewer"
                                class="px-3.5 py-2.5 text-lg font-bold leading-none text-[var(--muted)] transition hover:text-[var(--primary)]">&minus;</button>
                        <input type="text" id="stock_quantity" inputmode="numeric" autocomplete="off" value="1"
                               class="w-full border-x border-[var(--line)] px-2 py-2.5 text-center text-sm font-semibold outline-none">
                        <button type="button" onclick="stepStock(1)" aria-label="One more"
                                class="px-3.5 py-2.5 text-lg font-bold leading-none text-[var(--muted)] transition hover:text-[var(--primary)]">+</button>
                    </div>

                    <p id="stockHint" class="mt-1.5 text-xs text-[var(--muted)]"></p>
                </div>

                <div class="flex justify-end gap-2 border-t border-[var(--line)] pt-5">
                    <button type="button" onclick="closeStockModal()"
                            class="rounded-xl border border-[var(--line)] px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--surface)]">Cancel</button>
                    <button type="submit" id="stockSubmit"
                            class="rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Save</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
        let inventoryItems = [];
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            const isSuccess = type === 'success';
            box.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">
                        ${isSuccess
                            ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 3v4m10-4v4M6 11h12v8H6z"/></svg>'
                            : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'}
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${isSuccess ? 'Inventory Update' : 'Action Needed'}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        /* The dashboard's action list links straight here, so the filter it
           counted is applied on arrival rather than left to be found. */
        const STOCK_FILTERS = ['all', 'in_stock', 'low_stock', 'out_of_stock'];
        const requestedFilter = new URLSearchParams(window.location.search).get('stock');

        let activeStockFilter = STOCK_FILTERS.includes(requestedFilter) ? requestedFilter : 'all';

        function setStockFilter(filter) {
            activeStockFilter = filter;

            document.querySelectorAll('.stock-filter-btn').forEach(btn => {
                const on = btn.id === 'stockFilter-' + filter;
                btn.className = 'stock-filter-btn rounded-lg px-3 py-1.5 font-semibold transition '
                    + (on ? 'bg-[var(--primary)] text-white' : 'text-[var(--muted)] hover:text-[var(--ink)]');
            });

            renderInventory();
        }

        const STOCK_TONES = {
            out: { chip: 'bg-red-100 text-red-800', label: 'Out of stock', dot: '#dc2626' },
            low: { chip: 'bg-amber-100 text-amber-800', label: 'Running low', dot: '#d97706' },
            ok:  { chip: 'bg-emerald-100 text-emerald-800', label: 'In stock', dot: '#148a67' },
        };

        const peso = (n) => 'PHP ' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function matchesFilter(item) {
            if (activeStockFilter === 'all') return true;
            if (activeStockFilter === 'out_of_stock') return item.status === 'out';
            if (activeStockFilter === 'low_stock') return item.status === 'low';
            return item.status === 'ok';
        }

        /** The three counts across the top, each one a filter. */
        function renderSummary() {
            const counts = {
                out_of_stock: inventoryItems.filter(i => i.status === 'out').length,
                low_stock: inventoryItems.filter(i => i.status === 'low').length,
                in_stock: inventoryItems.filter(i => i.status === 'ok').length,
            };

            const value = inventoryItems.reduce((total, item) => total + Number(item.stock_value || 0), 0);

            const card = (key, label, count, tone, note) => `
                <button type="button" onclick="setStockFilter('${key}')"
                        class="rounded-[1.25rem] border bg-white px-5 py-4 text-left shadow-sm transition hover:border-[var(--primary)] ${activeStockFilter === key ? 'border-[var(--primary)]' : 'border-[var(--line)]'}">
                    <span class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--muted)]">
                        <span class="h-2.5 w-2.5 rounded-full" style="background:${tone}"></span>${label}
                    </span>
                    <span class="mt-2 block text-3xl font-black tracking-tight text-[var(--ink)]">${count}</span>
                    <span class="mt-1 block text-xs text-[var(--muted)]">${note}</span>
                </button>`;

            document.getElementById('stockSummary').innerHTML =
                card('out_of_stock', 'Out of stock', counts.out_of_stock, STOCK_TONES.out.dot, 'nothing left to sell')
                + card('low_stock', 'Running low', counts.low_stock, STOCK_TONES.low.dot, 'at or under the reorder level')
                + card('in_stock', 'In stock', counts.in_stock, STOCK_TONES.ok.dot, peso(value) + ' on the shelf');
        }

        function renderInventory() {
            const search = document.getElementById('inventorySearch').value.toLowerCase();
            const tbody = document.getElementById('inventoryBody');

            const items = inventoryItems.filter(item =>
                (item.product_name.toLowerCase().includes(search) || item.brand.toLowerCase().includes(search))
                && matchesFilter(item));

            document.getElementById('inventoryCount').textContent =
                `${items.length} of ${inventoryItems.length} products`;

            renderSummary();

            tbody.innerHTML = items.length
                ? items.map((item) => {
                    const tone = STOCK_TONES[item.status] || STOCK_TONES.ok;

                    return `
                        <tr class="transition hover:bg-[var(--surface)]">
                            <td class="px-5 py-3 sm:px-6">
                                <div class="flex items-center gap-3">
                                    ${item.image_url
                                        ? `<img src="${item.image_url}" alt="" class="h-12 w-12 shrink-0 rounded-lg border border-[var(--line)] bg-white object-contain p-1">`
                                        : '<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-[var(--line)] bg-[var(--surface)] text-[9px] font-semibold uppercase tracking-wider text-[var(--muted)]">No<br>image</div>'}
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-[var(--ink)]" title="${escapeHtml(item.product_name)}">${escapeHtml(item.product_name)}</div>
                                        <div class="text-xs text-[var(--muted)]">${escapeHtml(item.brand)}${item.unit ? ' &middot; ' + escapeHtml(item.unit) : ''}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-lg font-black text-[var(--ink)]">${item.quantity}</td>
                            <td class="px-5 py-3 text-sm text-[var(--muted)]">${item.reorder_level}</td>
                            <td class="px-5 py-3 text-sm text-[var(--muted)]">${peso(item.stock_value)}</td>
                            <td class="px-5 py-3">
                                <span class="inline-block rounded-full px-2.5 py-1 text-xs font-bold ${tone.chip}">${tone.label}</span>
                            </td>
                            <td class="px-5 py-3 text-right sm:px-6">
                                <div class="inline-flex gap-2">
                                    <button type="button" onclick="adjustStock(${item.product_id}, 'stock-in')"
                                            class="rounded-lg border border-[var(--line)] px-3 py-1.5 text-xs font-bold text-[var(--primary)] transition hover:border-[var(--primary)]">Stock in</button>
                                    <button type="button" onclick="adjustStock(${item.product_id}, 'stock-out')"
                                            class="rounded-lg border border-[var(--line)] px-3 py-1.5 text-xs font-bold text-red-700 transition hover:border-red-300 ${item.quantity <= 0 ? 'pointer-events-none opacity-40' : ''}">Stock out</button>
                                </div>
                            </td>
                        </tr>`;
                }).join('')
                : `<tr><td colspan="6" class="px-6 py-10 text-center text-sm text-[var(--muted)]">Nothing matches that.</td></tr>`;
        }

        async function loadInventory() {
            try {
                const response = await fetch('/admin-api/inventory', { headers: { Accept: 'application/json' } });
                if (response.status === 401) { window.location.href = '/admin/login'; return; }
                const data = await response.json();
                inventoryItems = data.data || [];
                renderInventory();
            } catch (error) {
                showMessage('Failed to load inventory.', 'error');
            }
        }

        function adjustStock(productId, action) {
            const item = inventoryItems.find((entry) => entry.product_id === productId);

            if (!item) {
                showMessage('Inventory item not found.', 'error');
                return;
            }

            if (action === 'stock-out' && item.quantity <= 0) {
                showMessage('There is nothing on the shelf to take out.', 'error');
                return;
            }

            document.getElementById('stock_action').value = action;
            document.getElementById('stock_product_id').value = productId;
            document.getElementById('stock_quantity').value = 1;

            document.getElementById('stockProductCard').innerHTML = `
                ${item.image_url
                    ? `<img src="${item.image_url}" alt="" class="h-14 w-14 shrink-0 rounded-lg border border-[var(--line)] bg-white object-contain p-1">`
                    : '<div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border border-[var(--line)] bg-white text-[9px] font-semibold uppercase text-[var(--muted)]">No image</div>'}
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-[var(--ink)]">${escapeHtml(item.product_name)}</div>
                    <div class="text-xs text-[var(--muted)]">${escapeHtml(item.brand)}${item.unit ? ' &middot; ' + escapeHtml(item.unit) : ''}</div>
                    <div class="mt-1 text-xs font-bold text-[var(--ink)]">${item.quantity} on hand</div>
                </div>`;

            const takingOut = action === 'stock-out';

            document.getElementById('stockModalTitle').textContent = takingOut ? 'Stock out' : 'Stock in';
            document.getElementById('stockModalSubtitle').textContent = takingOut
                ? 'Record stock leaving the shelf.'
                : 'Record stock arriving on the shelf.';
            document.getElementById('stockHint').textContent = takingOut
                ? `At most ${item.quantity}, which is everything on the shelf.`
                : 'Whole units only.';

            const submit = document.getElementById('stockSubmit');
            submit.textContent = takingOut ? 'Take out' : 'Add in';

            // Taking out cannot go past what is there; putting in has only the
            // server's own ceiling to respect.
            document.getElementById('stock_quantity').dataset.max = takingOut ? item.quantity : 100000;

            const modal = document.getElementById('stockModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        /* ------------------------------------------------ the quantity box ---

           A number input reports an empty string for anything it cannot
           parse, so "a" reads the same as blank and a blur handler turning
           blank into 1 quietly accepted the letter. It is a text box with
           digits whitelisted instead. */

        function stockBounds() {
            const field = document.getElementById('stock_quantity');
            return { field, max: Math.max(1, parseInt(field.dataset.max || '100000', 10)) };
        }

        function stepStock(by) {
            const { field, max } = stockBounds();
            const current = parseInt(field.value, 10);
            const next = (Number.isNaN(current) ? 1 : current) + by;

            field.value = Math.min(max, Math.max(1, next));
        }

        function closeStockModal() {
            const modal = document.getElementById('stockModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('stockForm').addEventListener('submit', async (event) => {
            event.preventDefault();

            const action = document.getElementById('stock_action').value;
            const response = await fetch(`/admin-api/inventory/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: Number(document.getElementById('stock_product_id').value),
                    // Read through the same clamp the steppers use, so a half
                    // typed number cannot be submitted by pressing return.
                    quantity: (() => {
                        const { field, max } = stockBounds();
                        const value = parseInt(field.value, 10);
                        return Number.isNaN(value) ? 1 : Math.min(max, Math.max(1, value));
                    })(),
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Inventory updated.', response.ok ? 'success' : 'error');

            if (response.ok) {
                closeStockModal();
                loadInventory();
            }
        });

        (function bindQuantity() {
            const field = document.getElementById('stock_quantity');

            field.addEventListener('input', () => {
                const digits = field.value.replace(/\D+/g, '');
                field.value = digits.replace(/^0+(?=\d)/, '');
            });

            field.addEventListener('blur', () => {
                const { max } = stockBounds();
                const value = parseInt(field.value, 10);

                field.value = Number.isNaN(value) ? 1 : Math.min(max, Math.max(1, value));
            });
        })();

        if (activeStockFilter !== 'all') setStockFilter(activeStockFilter);

        loadInventory();
</script>
@endpush
