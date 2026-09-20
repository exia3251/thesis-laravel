@extends('layouts.admin')

@section('title', 'RANEY LUBRICANTS TRADING — Admin')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-black text-[var(--ink)]">Sales</h1>
            <p class="text-[var(--muted)]">Review sales history, confirm customer payment requests, and manage delivery progress.</p>
        </div>
        <button onclick="openSaleModal()" class="bg-[var(--primary)] text-white px-4 py-2 rounded hover:bg-[var(--primary-dark)]">
            Create New Sale
        </button>
    </div>

    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="rounded-2xl bg-[var(--card)] shadow-lg overflow-hidden border border-[var(--line)]">
        <div class="px-6 py-4 border-b border-[var(--line)] flex flex-wrap gap-3 items-center">
            <input type="text" id="salesSearch" oninput="renderSales()" placeholder="Search customer or sale ID..." class="rounded border border-gray-300 px-3 py-2 text-sm w-64 focus:outline-none focus:border-blue-400">
            <div class="flex gap-2 text-sm">
                <button onclick="setPaymentFilter('all')" id="payFilter-all" class="pay-filter-btn px-3 py-1.5 rounded border font-medium bg-gray-900 text-white border-gray-900">All</button>
                <button onclick="setPaymentFilter('paid')" id="payFilter-paid" class="pay-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Paid</button>
                <button onclick="setPaymentFilter('partial')" id="payFilter-partial" class="pay-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Partial</button>
                <button onclick="setPaymentFilter('unpaid')" id="payFilter-unpaid" class="pay-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Unpaid</button>
                <button onclick="setPaymentFilter('processing')" id="payFilter-processing" class="pay-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Awaiting Confirmation</button>
            </div>
        </div>
        <table class="min-w-full">
            <thead class="bg-[var(--surface)]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Sale ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Payment</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Delivery</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Paid Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Total Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="salesBody" class="bg-[var(--card)] divide-y divide-[var(--line)]">
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">Loading sales...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
    </div>

    <div id="saleModal" class="hidden fixed inset-0 z-50 bg-black/50 overflow-y-auto">
<div class="relative mx-auto my-10 w-full max-w-4xl rounded-2xl bg-white shadow-2xl">

    {{-- Modal Header --}}
    <div class="flex items-center justify-between border-b border-slate-200 px-7 py-5">
        <div>
            <h3 class="text-xl font-bold text-slate-900">Create New Sale</h3>
            <p class="text-xs text-slate-500 mt-0.5">Fill in customer details, add products, then save.</p>
        </div>
        <button type="button" onclick="closeSaleModal()" class="rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">&#10005;</button>
    </div>

    <div class="p-7 space-y-6">

        {{-- Customer Details --}}
        <div>
            <div class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Customer Details</div>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer Name</label>
                    <input type="text" id="customer_name" placeholder="Walk-in Customer" required class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
                </div>
                <input type="hidden" id="payment_method" value="cash">
                <input type="hidden" id="paid_amount" value="0">
            </div>
        </div>

        {{-- Add Product --}}
        <div>
            <div class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Add Product</div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Product</label>
                        <select id="product_select" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400"></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Quantity</label>
                        <input type="number" id="product_quantity" min="1" value="1" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <button type="button" onclick="addSaleItem()" class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-black transition">+ Add Item</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div>
            <div class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Order Items</div>
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 w-1/2">Product</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 w-16">Qty</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 w-28">Price</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 w-28">Subtotal</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 w-20">Action</th>
                        </tr>
                    </thead>
                    <tbody id="saleItemsBody" class="divide-y divide-slate-100">
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400 text-sm">No items added yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Modal Footer --}}
    <div class="flex items-center justify-between border-t border-slate-200 px-7 py-5">
        <div class="text-lg font-bold text-slate-900">Total: <span id="saleTotal" class="text-blue-600">PHP 0.00</span></div>
        <div class="flex gap-3">
            <button type="button" onclick="closeSaleModal()" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
            <button type="button" onclick="submitSale()" class="rounded-full bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white hover:bg-[var(--primary-dark)]">Save Sale</button>
        </div>
    </div>
@endsection

@push('scripts')
<script>
        let saleProducts = [];
        let saleItems = [];
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            const isSuccess = type === 'success';
            box.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">
                        ${isSuccess
                            ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75A1.5 1.5 0 0 1 6.75 17.25v-10.5A1.5 1.5 0 0 1 8.25 5.25h7.5l3 3v9a1.5 1.5 0 0 1-1.5 1.5h-9ZM9 9h6m-6 3h6m-6 3h4.5"/></svg>'
                            : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'}
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${isSuccess ? 'Sales Update' : 'Action Needed'}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${text}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        function getPaymentBadge(status) {
            const styles = {
                paid:       'bg-green-100 text-green-800',
                partial:    'bg-yellow-100 text-yellow-800',
                processing: 'bg-blue-100 text-blue-800',
                unpaid:     'bg-red-100 text-red-800',
            };
            const labels = {
                paid:       'Paid',
                partial:    'Partial',
                processing: 'Awaiting Confirmation',
                unpaid:     'Unpaid',
            };
            const cls = styles[status] || 'bg-gray-100 text-gray-800';
            const label = labels[status] || status;
            return `<span class="inline-block rounded-full px-3 py-1 text-xs font-semibold ${cls}">${label}</span>`;
        }

        let allSales = [];
        let activePaymentFilter = 'all';

        function setPaymentFilter(filter) {
            activePaymentFilter = filter;
            document.querySelectorAll('.pay-filter-btn').forEach(btn => {
                btn.classList.remove('bg-gray-900', 'text-white', 'border-gray-900');
                btn.classList.add('text-gray-600', 'border-gray-300');
            });
            const active = document.getElementById('payFilter-' + filter);
            if (active) {
                active.classList.add('bg-gray-900', 'text-white', 'border-gray-900');
                active.classList.remove('text-gray-600', 'border-gray-300');
            }
            renderSales();
        }

        function renderSales() {
            const search = document.getElementById('salesSearch').value.toLowerCase();
            const tbody = document.getElementById('salesBody');

            const sales = allSales.filter(sale => {
                const customerName = (sale.customer_name || (sale.user && sale.user.full_name) || '').toLowerCase();
                const matchSearch = customerName.includes(search) || String(sale.sale_id).includes(search);
                const matchFilter = activePaymentFilter === 'all' || sale.payment_status === activePaymentFilter;
                return matchSearch && matchFilter;
            });

            tbody.innerHTML = sales.length
                ? sales.map((sale) => {
                    const paymentRequests = Array.isArray(sale.payment_requests) ? sale.payment_requests : [];
                    const pendingRequestsHtml = paymentRequests
                        .filter((request) => request.status === 'processing')
                        .map((request) => `
                            <div class="rounded-lg border border-sky-200 bg-sky-50 p-2 text-xs text-sky-900">
                                <div class="font-semibold">Customer GCash request: ${formatCurrency(request.amount)}</div>
                                <div class="mt-1">${request.payment_method}${request.reference_no ? ` | Ref: ${request.reference_no}` : ''}</div>
                                ${request.proof_image_path ? `<a href="/storage/${request.proof_image_path}" target="_blank" class="mt-1 inline-block font-semibold text-sky-700 hover:underline">View proof</a>` : ''}
                                <div class="mt-2 flex gap-2">
                                    <button type="button" onclick="approvePaymentRequest(${request.id})" class="rounded bg-emerald-600 px-2 py-1 text-white hover:bg-emerald-700">Approve</button>
                                    <button type="button" onclick="rejectPaymentRequest(${request.id})" class="rounded bg-red-600 px-2 py-1 text-white hover:bg-red-700">Reject</button>
                                </div>
                            </div>
                        `).join('');

                    return `
                    <tr>
                        <td class="px-6 py-4">#${sale.sale_id}</td>
                        <td class="px-6 py-4">${new Date(sale.sale_date).toLocaleString()}</td>
                        <td class="px-6 py-4">${sale.customer_name || (sale.user && sale.user.full_name) || 'Walk-in Customer'}</td>
                        <td class="px-6 py-4">
                            ${getPaymentBadge(sale.payment_status)}
                        </td>
                        <td class="px-6 py-4">
                            <select id="delivery_status_${sale.sale_id}" class="rounded border px-2 py-1">
                                <option value="to_deliver" ${sale.delivery_status === 'to_deliver' ? 'selected' : ''}>To Deliver</option>
                                <option value="to_receive" ${sale.delivery_status === 'to_receive' ? 'selected' : ''}>To Receive</option>
                                <option value="delivered" ${sale.delivery_status === 'delivered' ? 'selected' : ''} ${sale.payment_status === 'unpaid' ? 'disabled' : ''}>Delivered</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <input id="paid_amount_${sale.sale_id}" type="number" min="0" step="1" value="${Math.round(Number(sale.paid_amount || 0))}" class="w-28 rounded border px-2 py-1">
                        </td>
                        <td class="px-6 py-4">${formatCurrency(sale.total_amount)}</td>
                        <td class="px-6 py-4">
                            <div class="space-y-2">
                                <button type="button" onclick="updateSaleStatus(${sale.sale_id})" class="text-blue-600 hover:text-blue-900">Update</button>
                                ${pendingRequestsHtml}
                            </div>
                        </td>
                    </tr>
                `;
                }).join('')
                : '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No matching sales records.</td></tr>';
        }

        async function loadSales() {
            const response = await fetch('/admin-api/sales', { headers: { Accept: 'application/json' } });
            if (response.status === 401) { window.location.href = '/admin/login'; return; }
            const data = await response.json();
            allSales = data.data || [];
            renderSales();
        }

        async function loadSaleProducts() {
            const response = await fetch('/admin-api/sales/products/list', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            saleProducts = data.data || [];

            const select = document.getElementById('product_select');
            select.innerHTML = saleProducts.map((product) => `
                <option value="${product.product_id}">
                    ${product.product_name} - ${formatCurrency(product.price)} (${product.inventory ? product.inventory.quantity : 0} in stock)
                </option>
            `).join('');
        }

        function openSaleModal() {
            document.getElementById('saleModal').classList.remove('hidden');
            loadSaleProducts();
        }

        function closeSaleModal() {
            document.getElementById('saleModal').classList.add('hidden');
            saleItems = [];
            document.getElementById('customer_name').value = '';
            document.getElementById('payment_method').value = 'cash';
            document.getElementById('paid_amount').value = 0;
            renderSaleItems();
        }

        function addSaleItem() {
            const productId = Number(document.getElementById('product_select').value);
            const quantity = Number(document.getElementById('product_quantity').value);
            const product = saleProducts.find((item) => item.product_id === productId);

            if (!product || quantity < 1) {
                showMessage('Select a valid product and quantity.', 'error');
                return;
            }

            const existingItem = saleItems.find((item) => item.product_id === productId);

            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                saleItems.push({
                    product_id: product.product_id,
                    product_name: product.product_name,
                    price: Number(product.price),
                    quantity
                });
            }

            renderSaleItems();
        }

        function removeSaleItem(productId) {
            saleItems = saleItems.filter((item) => item.product_id !== productId);
            renderSaleItems();
        }

        function renderSaleItems() {
            const tbody = document.getElementById('saleItemsBody');
            const total = saleItems.reduce((sum, item) => sum + item.price * item.quantity, 0);

            document.getElementById('saleTotal').textContent = formatCurrency(total);

            tbody.innerHTML = saleItems.length
                ? saleItems.map((item) => `
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 text-slate-800 font-medium">${item.product_name}</td>
                        <td class="px-4 py-3 text-center text-slate-600">${item.quantity}</td>
                        <td class="px-4 py-3 text-right text-slate-600">${formatCurrency(item.price)}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">${formatCurrency(item.price * item.quantity)}</td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" onclick="removeSaleItem(${item.product_id})" class="rounded-full px-3 py-1 text-xs font-semibold text-red-600 border border-red-200 hover:bg-red-50 transition">Remove</button>
                        </td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="py-6 text-center text-slate-400 text-sm">No items added yet.</td></tr>';
        }

        async function submitSale() {
            const customerName = document.getElementById('customer_name').value.trim();
            if (!customerName) {
                showMessage('Customer name is required.', 'error');
                document.getElementById('customer_name').focus();
                return;
            }

            if (saleItems.length === 0) {
                showMessage('Add at least one sale item.', 'error');
                return;
            }

            const total = saleItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
            document.getElementById('paid_amount').value = total;

            const response = await fetch('/admin-api/sales', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    customer_name: document.getElementById('customer_name').value || 'Walk-in Customer',
                    payment_method: document.getElementById('payment_method').value,
                    paid_amount: document.getElementById('paid_amount').value || 0,
                    items: saleItems
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Sale saved.', response.ok ? 'success' : 'error');

            if (response.ok) {
                closeSaleModal();
                loadSales();
            }
        }

        async function updateSaleStatus(saleId) {
            const response = await fetch(`/admin-api/sales/${saleId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    payment_status: 'derived',
                    delivery_status: document.getElementById(`delivery_status_${saleId}`).value,
                    paid_amount: document.getElementById(`paid_amount_${saleId}`).value || 0
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Sale status updated.', response.ok ? 'success' : 'error');

            if (response.ok) {
                loadSales();
            }
        }

        async function approvePaymentRequest(requestId) {
            const response = await fetch(`/admin-api/payment-requests/${requestId}/approve`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            });

            const data = await response.json();
            showMessage(data.message || 'Payment request approved.', response.ok ? 'success' : 'error');

            if (response.ok) {
                loadSales();
            }
        }

        async function rejectPaymentRequest(requestId) {
            const response = await fetch(`/admin-api/payment-requests/${requestId}/reject`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            });

            const data = await response.json();
            showMessage(data.message || 'Payment request rejected.', response.ok ? 'success' : 'error');

            if (response.ok) {
                loadSales();
            }
        }

        loadSales();
</script>
@endpush
