@extends('layouts.admin')

@section('title', 'RANEY LUBRICANTS TRADING — Admin')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-black text-[var(--ink)] sm:text-3xl">Sales</h1>
            <p class="text-[var(--muted)]">Review sales history, confirm customer payment requests, and manage delivery progress.</p>
        </div>
        <button onclick="openSaleModal()" class="bg-[var(--primary)] text-white px-4 py-2 rounded hover:bg-[var(--primary-dark)]">
            Create New Sale
        </button>
    </div>

    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-[var(--line)] flex flex-wrap gap-3 items-center">
            <input type="text" id="salesSearch" oninput="searchSales()" placeholder="Search customer or sale ID..." class="rounded border border-gray-300 px-3 py-2 text-sm w-full sm:w-64 focus:outline-none focus:border-blue-400">
            <div class="flex gap-2 text-sm">
                <button onclick="setPaymentFilter('all')" id="payFilter-all" class="pay-filter-btn px-3 py-1.5 rounded border font-medium bg-gray-900 text-white border-gray-900">All</button>
                <button onclick="setPaymentFilter('paid')" id="payFilter-paid" class="pay-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Paid</button>
                <button onclick="setPaymentFilter('partial')" id="payFilter-partial" class="pay-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Partial</button>
                <button onclick="setPaymentFilter('unpaid')" id="payFilter-unpaid" class="pay-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Unpaid</button>
                <button onclick="setPaymentFilter('processing')" id="payFilter-processing" class="pay-filter-btn px-3 py-1.5 rounded border font-medium text-gray-600 border-gray-300 hover:bg-gray-100">Awaiting Confirmation</button>
            </div>

            {{-- Shown when the dashboard sent us here for one job. It is a
                 chip rather than another button, because it is a temporary
                 view of the list and not a filter to be kept. --}}
            <div id="focusChip" class="hidden items-center gap-2 rounded-full bg-[var(--primary-soft)] px-3 py-1.5 text-sm font-semibold text-[var(--primary)]">
                <span id="focusLabel"></span>
                <button type="button" onclick="clearFocus()" aria-label="Show all sales"
                        class="rounded-full p-0.5 transition hover:bg-white/60">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div class="admin-table-wrap">
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
    </div>

    {{-- Reviewing a payment means comparing a receipt against what the order
         actually owes, so both sit side by side rather than in a table cell. --}}
    {{-- Every payment ever claimed against one order, so a disputed amount can
         be traced back to the receipt and reference it came from. --}}
    {{-- Handover photo, the delivery-side counterpart to a payment receipt. --}}
    <div id="deliveryModal" class="hidden fixed inset-0 z-50 bg-black/60 overflow-y-auto">
        <div class="mx-auto my-16 w-full max-w-lg rounded-[1.5rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-[var(--line)] px-6 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Confirm delivery</p>
                    <h2 class="mt-1 text-xl font-black text-[var(--ink)]">Order <span id="dlSaleId"></span></h2>
                </div>
                <button type="button" onclick="closeDeliveryModal()" class="rounded-full p-2 text-[var(--muted)] transition hover:bg-[var(--surface)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <div id="dlBalanceWarning" class="mb-4 hidden rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900"></div>
                <label class="block text-sm font-medium text-[var(--ink)]">Photo taken at handover <span class="text-red-500">*</span></label>
                <input id="dlProof" type="file" accept="image/*" class="mt-2 block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm">
                <p class="mt-2 text-xs text-[var(--muted)]">{{ strtoupper(implode(', ', config('payments.proof.mimes'))) }} &middot; up to {{ round(config('payments.proof.max_kilobytes') / 1024) }} MB &middot; at least {{ config('payments.proof.min_width') }}&times;{{ config('payments.proof.min_height') }} pixels.</p>
                <label class="mt-4 block text-sm font-medium text-[var(--ink)]">Notes</label>
                <textarea id="dlNotes" rows="2" maxlength="500" placeholder="e.g. received by the shop supervisor" class="mt-2 block w-full resize-none rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm outline-none focus:border-[var(--primary)]"></textarea>
                <p id="dlError" class="mt-3 hidden text-xs font-semibold text-red-600"></p>
                <button type="button" onclick="submitDeliveryProof()" class="mt-4 w-full rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:bg-[var(--primary-dark)]">Confirm delivery</button>
            </div>
        </div>
    </div>

    {{-- The transfer happens in GCash; this only records that it was done. --}}
    <div id="refundModal" class="hidden fixed inset-0 z-50 bg-black/60 overflow-y-auto">
        <div class="mx-auto my-16 w-full max-w-lg rounded-[1.5rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-[var(--line)] px-6 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Record refund</p>
                    <h2 class="mt-1 text-xl font-black text-[var(--ink)]">Order <span id="rfSaleId"></span></h2>
                </div>
                <button type="button" onclick="closeRefundModal()" class="rounded-full p-2 text-[var(--muted)] transition hover:bg-[var(--surface)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <div class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-4">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-800">Amount owed back</div>
                    <div id="rfAmount" class="mt-1 text-3xl font-black text-amber-900">PHP 0.00</div>
                    <p class="mt-2 text-xs leading-5 text-amber-900">Send this through GCash first, then record it here. This form does not move any money.</p>
                </div>
                <label class="mt-4 block text-sm font-medium text-[var(--ink)]">GCash reference of your transfer</label>
                <input id="rfReference" type="text" maxlength="100" placeholder="e.g. 1029384756" class="mt-2 block w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm outline-none focus:border-[var(--primary)]">
                <label class="mt-4 block text-sm font-medium text-[var(--ink)]">Notes</label>
                <textarea id="rfNotes" rows="2" maxlength="500" placeholder="e.g. sent to 09XXXXXXXXX" class="mt-2 block w-full resize-none rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm outline-none focus:border-[var(--primary)]"></textarea>
                <p id="rfError" class="mt-3 hidden text-xs font-semibold text-red-600"></p>
                <button type="button" onclick="submitRefund()" class="mt-4 w-full rounded-xl bg-amber-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-amber-700">Mark as refunded</button>
            </div>
        </div>
    </div>

    <div id="historyModal" class="hidden fixed inset-0 z-50 bg-black/60 overflow-y-auto">
        <div class="mx-auto my-8 w-full max-w-3xl rounded-[1.5rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-[var(--line)] px-6 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Payment history</p>
                    <h2 class="mt-1 text-xl font-black text-[var(--ink)]">Order <span id="hsSaleId"></span> &mdash; <span id="hsCustomer"></span></h2>
                    <p id="hsSummary" class="mt-1 text-sm text-[var(--muted)]"></p>
                </div>
                <button type="button" onclick="closeHistoryModal()" class="rounded-full p-2 text-[var(--muted)] transition hover:bg-[var(--surface)] hover:text-[var(--ink)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="hsBody" class="max-h-[32rem] space-y-3 overflow-y-auto p-6"></div>
        </div>
    </div>

    <div id="verifyModal" class="hidden fixed inset-0 z-50 bg-black/60 overflow-y-auto">
        <div class="mx-auto my-8 w-full max-w-5xl rounded-[1.5rem] bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-[var(--line)] px-6 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Verify payment</p>
                    <h2 class="mt-1 text-xl font-black text-[var(--ink)]">Order <span id="vfSaleId"></span> &mdash; <span id="vfCustomer"></span></h2>
                </div>
                <button type="button" onclick="closeVerifyModal()" class="rounded-full p-2 text-[var(--muted)] transition hover:bg-[var(--surface)] hover:text-[var(--ink)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="grid gap-6 p-6 lg:grid-cols-2">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Receipt supplied</div>
                    <div id="vfProofWrap" class="mt-2 overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface)]">
                        <img id="vfProof" src="" alt="Proof of payment" class="max-h-[26rem] w-full object-contain">
                    </div>
                    <div id="vfNoProof" class="mt-2 hidden rounded-2xl border border-dashed border-[var(--line)] bg-[var(--surface)] p-8 text-center text-sm text-[var(--muted)]">
                        No receipt was attached to this request.
                    </div>
                    <a id="vfProofLink" href="#" target="_blank" class="mt-2 inline-block text-xs font-semibold text-[var(--primary)] hover:underline">Open full size</a>
                </div>

                <div>
                    <div class="rounded-2xl border-2 border-[var(--primary)] bg-[var(--primary-soft)] p-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--primary)]">Customer is claiming</div>
                        <div id="vfAmount" class="mt-1 text-3xl font-black text-[var(--primary)]">PHP 0.00</div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-xs text-[#0f6b50]">Reference</span>
                            <code id="vfReference" class="rounded bg-white/70 px-2 py-1 text-sm font-bold text-[var(--ink)]">&mdash;</code>
                        </div>
                    </div>

                    <div id="vfWarning" class="mt-3 hidden rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900"></div>

                    <dl class="mt-4 space-y-2 rounded-2xl border border-[var(--line)] p-4 text-sm">
                        <div class="flex justify-between"><dt class="text-[var(--muted)]">Order total</dt><dd id="vfTotal" class="font-semibold"></dd></div>
                        <div class="flex justify-between"><dt class="text-[var(--muted)]">Payment option</dt><dd id="vfPlan" class="font-semibold"></dd></div>
                        <div class="flex justify-between"><dt class="text-[var(--muted)]">Paid so far</dt><dd id="vfPaid" class="font-semibold"></dd></div>
                        <div class="flex justify-between"><dt class="text-[var(--muted)]">Balance now</dt><dd id="vfBalance" class="font-semibold"></dd></div>
                        <div class="flex justify-between border-t border-[var(--line)] pt-2">
                            <dt class="font-semibold text-[var(--ink)]">Balance if approved</dt>
                            <dd id="vfAfter" class="font-black text-[var(--primary)]"></dd>
                        </div>
                    </dl>

                    <label class="mt-4 block text-sm font-medium text-[var(--ink)]">Notes <span class="font-normal text-[var(--muted)]">(kept on the record)</span></label>
                    <textarea id="vfNotes" rows="2" maxlength="500" placeholder="e.g. matched against the GCash transaction log"
                              class="mt-2 block w-full resize-none rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm outline-none focus:border-[var(--primary)]"></textarea>

                    <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                        <button type="button" onclick="submitVerification('approve')" class="flex-1 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">Approve payment</button>
                        <button type="button" onclick="submitVerification('reject')" class="flex-1 rounded-xl border-2 border-red-300 bg-white px-4 py-3 text-sm font-bold text-red-700 transition hover:bg-red-50">Reject</button>
                    </div>
                    <p class="mt-2 text-center text-xs text-[var(--muted)]">Approving moves money against the order and cannot be undone here.</p>
                </div>
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
                <div id="salesPagination"></div>
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
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
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
            return `<span class="inline-block rounded-full px-3 py-1 text-xs font-semibold ${cls}">${escapeHtml(label)}</span>`;
        }

        let allSales = [];
        let activePaymentFilter = 'all';
        let salesMeta = null;

        /* One job, arrived at from the dashboard's action list. */
        const FOCUS_LABELS = {
            owing: 'Orders awaiting payment',
            processing: 'Payments to verify',
            to_deliver: 'Paid orders not yet delivered',
            refunds: 'Refunds to process',
        };

        const requestedFocus = new URLSearchParams(window.location.search).get('focus');
        let activeFocus = FOCUS_LABELS[requestedFocus] ? requestedFocus : null;

        function showFocus() {
            const chip = document.getElementById('focusChip');

            chip.classList.toggle('hidden', !activeFocus);
            chip.classList.toggle('flex', !!activeFocus);

            if (activeFocus) document.getElementById('focusLabel').textContent = FOCUS_LABELS[activeFocus];
        }

        function clearFocus() {
            activeFocus = null;
            showFocus();
            loadSales(1);
        }

        function setPaymentFilter(filter) {
            // The two would fight each other -- unpaid within "paid orders not
            // yet delivered" is an empty table -- so choosing one drops the
            // other, and the chip disappearing says so.
            activeFocus = null;
            showFocus();

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
            loadSales(1);
        }

        function renderSales() {
            const tbody = document.getElementById('salesBody');
            const sales = allSales;

            tbody.innerHTML = sales.length
                ? sales.map((sale) => {
                    const paymentRequests = Array.isArray(sale.payment_requests) ? sale.payment_requests : [];
                    const pendingRequestsHtml = paymentRequests
                        .filter((request) => request.status === 'processing')
                        .map((request) => `
                            <div class="rounded-lg border border-sky-200 bg-sky-50 p-2 text-xs text-sky-900">
                                <div class="font-semibold">Customer GCash request: ${formatCurrency(request.amount)}</div>
                                <div class="mt-1">${request.reference_no ? `Ref: ${escapeHtml(request.reference_no)}` : 'No reference given'}</div>
                                <button type="button" onclick="openVerifyModal(${sale.sale_id}, ${request.id})" class="mt-2 w-full rounded bg-sky-700 px-2 py-1.5 font-semibold text-white transition hover:bg-sky-800">Review payment</button>
                            </div>
                        `).join('');

                    return `
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-semibold">#${sale.sale_id}</div>
                            ${sale.receipt_no ? `<div class="mt-1 text-xs font-medium text-[var(--primary)]">${escapeHtml(sale.receipt_no)}</div>` : ''}
                            ${sale.delivery_no ? `<div class="text-xs text-[var(--muted)]">${escapeHtml(sale.delivery_no)}</div>` : ''}
                        </td>
                        <td class="px-6 py-4">${new Date(sale.sale_date).toLocaleString()}</td>
                        <td class="px-6 py-4">${escapeHtml(sale.customer_name || (sale.user && sale.user.full_name) || 'Walk-in Customer')}</td>
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
                                <div class="flex flex-wrap gap-2">
                                    ${sale.order_status === 'cancelled' ? '' : `<button type="button" onclick="updateSaleStatus(${sale.sale_id})" class="rounded border border-[var(--line)] px-2 py-1 text-xs font-semibold text-blue-700 transition hover:bg-blue-50">Update</button>`}
                                    <button type="button" onclick="openHistoryModal(${sale.sale_id})" class="rounded border border-[var(--line)] px-2 py-1 text-xs font-semibold text-[var(--muted)] transition hover:bg-[var(--surface)] hover:text-[var(--ink)]">History${(sale.payment_requests || []).length ? ` (${(sale.payment_requests || []).length})` : ''}</button>
                                </div>
                                ${renderLifecycleActions(sale)}
                                ${pendingRequestsHtml}
                            </div>
                        </td>
                    </tr>
                `;
                }).join('')
                : '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No matching sales records.</td></tr>';
        }

        async function loadSales(page = 1) {
            const params = new URLSearchParams({ page });
            const search = document.getElementById('salesSearch').value.trim();

            if (search) params.set('search', search);
            if (activePaymentFilter !== 'all') params.set('payment_status', activePaymentFilter);
            if (activeFocus) params.set('focus', activeFocus);

            const response = await fetch(`/admin-api/sales?${params}`, { headers: { Accept: 'application/json' } });
            if (response.status === 401) { window.location.href = '/admin/login'; return; }

            const data = await response.json();
            allSales = data.data || [];
            salesMeta = data.meta || null;

            renderSales();
            renderPagination('salesPagination', salesMeta, loadSales);
        }

        const searchSales = debounce(() => loadSales(1));

        async function loadSaleProducts() {
            const response = await fetch('/admin-api/sales/products/list', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            saleProducts = data.data || [];

            const select = document.getElementById('product_select');
            select.innerHTML = saleProducts.map((product) => `
                <option value="${product.product_id}">
                    ${escapeHtml(product.product_name)} - ${formatCurrency(product.price)} (${product.inventory ? product.inventory.quantity : 0} in stock)
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
                        <td class="px-4 py-3 text-slate-800 font-medium">${escapeHtml(item.product_name)}</td>
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




        // ---- Payment verification ---------------------------------------
        const PLAN_LABELS = {
            cod: 'Cash on Delivery',
            gcash_full: 'GCash (full payment)',
            split: 'Split - GCash down payment, balance on delivery',
        };

        let verifyContext = null;

        function openVerifyModal(saleId, requestId) {
            const sale = allSales.find((s) => Number(s.sale_id) === Number(saleId));
            const request = sale && (sale.payment_requests || []).find((r) => Number(r.id) === Number(requestId));

            if (!sale || !request) {
                showMessage('That payment request is no longer available. Refreshing.', 'error');
                loadSales();
                return;
            }

            verifyContext = { requestId };

            const amount = Number(request.amount);
            const total = Number(sale.total_amount);
            const paid = Number(sale.paid_amount);
            const balance = Number(sale.balance_due);
            const applied = Math.min(amount, balance);

            document.getElementById('vfSaleId').textContent = '#' + sale.sale_id;
            document.getElementById('vfCustomer').textContent = sale.customer_name || (sale.user && sale.user.full_name) || 'Walk-in Customer';
            document.getElementById('vfAmount').textContent = formatCurrency(amount);
            document.getElementById('vfReference').textContent = request.reference_no || 'none given';
            document.getElementById('vfTotal').textContent = formatCurrency(total);
            document.getElementById('vfPlan').textContent = PLAN_LABELS[sale.payment_plan] || sale.payment_plan || 'not recorded';
            document.getElementById('vfPaid').textContent = formatCurrency(paid);
            document.getElementById('vfBalance').textContent = formatCurrency(balance);
            document.getElementById('vfAfter').textContent = formatCurrency(Math.max(balance - applied, 0));
            document.getElementById('vfNotes').value = '';

            const wrap = document.getElementById('vfProofWrap');
            const missing = document.getElementById('vfNoProof');
            const link = document.getElementById('vfProofLink');

            if (request.proof_image_path) {
                const url = '/storage/' + request.proof_image_path;
                document.getElementById('vfProof').src = url;
                link.href = url;
                wrap.classList.remove('hidden');
                link.classList.remove('hidden');
                missing.classList.add('hidden');
            } else {
                wrap.classList.add('hidden');
                link.classList.add('hidden');
                missing.classList.remove('hidden');
            }

            // Anything that should make an administrator look twice before approving.
            const warnings = [];
            const expected = Math.max(Number(sale.gcash_amount || 0) - paid, 0);

            if (amount > balance) {
                warnings.push('Claimed ' + formatCurrency(amount) + ' exceeds the ' + formatCurrency(balance)
                    + ' outstanding. Only ' + formatCurrency(applied) + ' would be applied.');
            }
            if (expected > 0 && Math.abs(amount - expected) > 0.01) {
                warnings.push('The GCash amount agreed at checkout was ' + formatCurrency(expected) + '.');
            }
            if (!request.reference_no) {
                warnings.push('No GCash reference number was supplied.');
            }
            if (!request.proof_image_path) {
                warnings.push('No receipt image was attached.');
            }

            const warnBox = document.getElementById('vfWarning');
            warnBox.innerHTML = warnings.map((w) => '<div>&bull; ' + w + '</div>').join('');
            warnBox.classList.toggle('hidden', warnings.length === 0);

            document.getElementById('verifyModal').classList.remove('hidden');
        }

        function closeVerifyModal() {
            document.getElementById('verifyModal').classList.add('hidden');
            verifyContext = null;
        }

        async function submitVerification(action) {
            if (!verifyContext) return;

            const buttons = document.querySelectorAll('#verifyModal button');
            buttons.forEach((b) => b.disabled = true);

            try {
                const response = await fetch('/admin-api/payment-requests/' + verifyContext.requestId + '/' + action, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ admin_notes: document.getElementById('vfNotes').value.trim() })
                });

                const data = await response.json();
                showMessage(data.message || 'Payment updated.', response.ok ? 'success' : 'error');

                if (response.ok) {
                    closeVerifyModal();
                    loadSales();
                }
            } finally {
                buttons.forEach((b) => b.disabled = false);
            }
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            closeVerifyModal();
            closeHistoryModal();
            closeDeliveryModal();
            closeRefundModal();
        });

        document.getElementById('verifyModal').addEventListener('click', (event) => {
            if (event.target.id === 'verifyModal') closeVerifyModal();
        });


        // ---- Payment history --------------------------------------------
        const REQUEST_STATUS_STYLES = {
            approved:   { pill: 'bg-emerald-100 text-emerald-800', label: 'Approved' },
            rejected:   { pill: 'bg-red-100 text-red-800',         label: 'Rejected' },
            processing: { pill: 'bg-sky-100 text-sky-800',         label: 'Awaiting review' },
        };

        function openHistoryModal(saleId) {
            const sale = allSales.find((s) => Number(s.sale_id) === Number(saleId));
            if (!sale) return;

            const requests = [...(sale.payment_requests || [])]
                .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

            const approved = requests
                .filter((r) => r.status === 'approved')
                .reduce((sum, r) => sum + Number(r.amount), 0);

            document.getElementById('hsSaleId').textContent = '#' + sale.sale_id;
            document.getElementById('hsCustomer').textContent = sale.customer_name || (sale.user && sale.user.full_name) || 'Walk-in Customer';
            document.getElementById('hsSummary').textContent =
                requests.length + ' payment request' + (requests.length === 1 ? '' : 's') + ' \u00b7 '
                + formatCurrency(approved) + ' approved of ' + formatCurrency(sale.total_amount);

            document.getElementById('hsBody').innerHTML = requests.length
                ? requests.map((r) => {
                    const style = REQUEST_STATUS_STYLES[r.status] || { pill: 'bg-slate-100 text-slate-700', label: r.status };
                    const reviewer = r.reviewer && r.reviewer.full_name;

                    return `
                        <div class="flex gap-4 rounded-2xl border border-[var(--line)] p-4">
                            ${r.proof_image_path
                                ? `<a href="/storage/${r.proof_image_path}" target="_blank" class="shrink-0">
                                       <img src="/storage/${r.proof_image_path}" alt="Receipt" class="h-24 w-24 rounded-xl border border-[var(--line)] object-cover transition hover:opacity-80">
                                   </a>`
                                : `<div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl border border-dashed border-[var(--line)] bg-[var(--surface)] text-center text-[10px] font-semibold uppercase tracking-wider text-[var(--muted)]">No receipt</div>`}
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-lg font-black text-[var(--ink)]">${formatCurrency(r.amount)}</div>
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${style.pill}">${style.label}</span>
                                </div>
                                <div class="mt-1 text-sm text-[var(--muted)]">
                                    ${r.payment_method ? r.payment_method.toUpperCase() : 'GCASH'}
                                    ${r.reference_no ? ` &middot; Ref <code class="rounded bg-[var(--surface)] px-1.5 py-0.5 font-semibold text-[var(--ink)]">${escapeHtml(r.reference_no)}</code>` : ' &middot; no reference given'}
                                </div>
                                <div class="mt-1 text-xs text-[var(--muted)]">
                                    Submitted ${new Date(r.created_at).toLocaleString()}
                                    ${r.reviewed_at ? ` &middot; reviewed ${new Date(r.reviewed_at).toLocaleString()}${reviewer ? ' by ' + reviewer : ''}` : ''}
                                </div>
                                ${r.admin_notes ? `<div class="mt-2 rounded-lg bg-[var(--surface)] px-3 py-2 text-xs leading-5 text-[var(--ink)]">${escapeHtml(r.admin_notes)}</div>` : ''}
                            </div>
                        </div>
                    `;
                }).join('')
                : '<p class="py-8 text-center text-sm text-[var(--muted)]">No payment has been claimed against this order yet.</p>';

            document.getElementById('historyModal').classList.remove('hidden');
        }

        function closeHistoryModal() {
            document.getElementById('historyModal').classList.add('hidden');
        }

        document.getElementById('historyModal').addEventListener('click', (event) => {
            if (event.target.id === 'historyModal') closeHistoryModal();
        });


        // ---- Cancellation, refunds and delivery --------------------------
        let deliveryContext = null;
        let refundContext = null;

        function renderLifecycleActions(sale) {
            if (sale.order_status === 'cancelled') {
                const refunded = sale.refund_status === 'refunded';
                const owed = sale.refund_status === 'pending';

                return `
                    <div class="rounded-lg border ${owed ? 'border-amber-300 bg-amber-50' : 'border-[var(--line)] bg-[var(--surface)]'} p-2 text-xs">
                        <div class="font-semibold ${owed ? 'text-amber-900' : 'text-[var(--muted)]'}">Cancelled</div>
                        ${sale.cancellation_reason ? `<div class="mt-1 text-[var(--muted)]">${escapeHtml(sale.cancellation_reason)}</div>` : ''}
                        ${owed ? `<button type="button" onclick="openRefundModal(${sale.sale_id})" class="mt-2 w-full rounded bg-amber-600 px-2 py-1.5 font-semibold text-white transition hover:bg-amber-700">Record ${formatCurrency(sale.refund_amount)} refund</button>` : ''}
                        ${refunded ? `<div class="mt-1 font-semibold text-emerald-700">${formatCurrency(sale.refund_amount)} refunded${sale.refund_reference ? ' - ' + sale.refund_reference : ''}</div>` : ''}
                    </div>
                `;
            }

            const buttons = [];

            if (sale.delivery_status !== 'delivered') {
                buttons.push(`<button type="button" onclick="openDeliveryModal(${sale.sale_id})" class="flex-1 rounded border border-emerald-300 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100">Confirm delivery</button>`);
                buttons.push(`<button type="button" onclick="cancelSale(${sale.sale_id})" class="flex-1 rounded border border-red-200 px-2 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-50">Cancel</button>`);
            } else if (sale.delivery_proof_path) {
                buttons.push(`<a href="/storage/${sale.delivery_proof_path}" target="_blank" class="flex-1 rounded border border-[var(--line)] px-2 py-1 text-center text-xs font-semibold text-[var(--muted)] transition hover:bg-[var(--surface)]">Delivery photo</a>`);
            }

            return buttons.length ? `<div class="flex gap-2">${buttons.join('')}</div>` : '';
        }

        function openDeliveryModal(saleId) {
            const sale = allSales.find((s) => Number(s.sale_id) === Number(saleId));
            if (!sale) return;

            deliveryContext = { saleId };
            document.getElementById('dlSaleId').textContent = '#' + saleId;
            document.getElementById('dlProof').value = '';
            document.getElementById('dlNotes').value = '';
            document.getElementById('dlError').classList.add('hidden');

            const balance = Number(sale.balance_due);
            const warning = document.getElementById('dlBalanceWarning');
            warning.textContent = balance > 0
                ? `This order still owes ${formatCurrency(balance)}. Record the cash collected on delivery first, or this will be refused.`
                : '';
            warning.classList.toggle('hidden', balance <= 0);

            document.getElementById('deliveryModal').classList.remove('hidden');
        }

        function closeDeliveryModal() {
            document.getElementById('deliveryModal').classList.add('hidden');
            deliveryContext = null;
        }

        async function submitDeliveryProof() {
            if (!deliveryContext) return;

            const file = document.getElementById('dlProof').files[0];
            const error = document.getElementById('dlError');

            if (!file) {
                error.textContent = 'Attach the handover photo first.';
                error.classList.remove('hidden');
                return;
            }

            const body = new FormData();
            body.append('delivery_proof', file);
            body.append('notes', document.getElementById('dlNotes').value.trim());

            const response = await fetch(`/admin-api/sales/${deliveryContext.saleId}/delivery-proof`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body
            });

            const data = await response.json();

            if (response.ok) {
                closeDeliveryModal();
                showMessage(data.message || 'Delivery confirmed.', 'success');
                loadSales();
                return;
            }

            error.textContent = data.message || Object.values(data.errors || {})[0]?.[0] || 'Could not confirm delivery.';
            error.classList.remove('hidden');
        }

        async function cancelSale(saleId) {
            const reason = prompt(`Cancel order #${saleId}?\n\nStock goes back and any payment becomes a refund you owe.\n\nReason:`);
            if (reason === null) return;

            if (!reason.trim()) {
                showMessage('A reason is required so the cancellation can be explained later.', 'error');
                return;
            }

            const response = await fetch(`/admin-api/sales/${saleId}/cancel`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ reason: reason.trim() })
            });

            const data = await response.json();
            showMessage(data.message || 'Order cancelled.', response.ok ? 'success' : 'error');
            if (response.ok) loadSales();
        }

        function openRefundModal(saleId) {
            const sale = allSales.find((s) => Number(s.sale_id) === Number(saleId));
            if (!sale) return;

            refundContext = { saleId };
            document.getElementById('rfSaleId').textContent = '#' + saleId;
            document.getElementById('rfAmount').textContent = formatCurrency(sale.refund_amount);
            document.getElementById('rfReference').value = '';
            document.getElementById('rfNotes').value = '';
            document.getElementById('rfError').classList.add('hidden');
            document.getElementById('refundModal').classList.remove('hidden');
        }

        function closeRefundModal() {
            document.getElementById('refundModal').classList.add('hidden');
            refundContext = null;
        }

        async function submitRefund() {
            if (!refundContext) return;

            const response = await fetch(`/admin-api/sales/${refundContext.saleId}/refund`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    reference: document.getElementById('rfReference').value.trim() || null,
                    notes: document.getElementById('rfNotes').value.trim() || null
                })
            });

            const data = await response.json();

            if (response.ok) {
                closeRefundModal();
                showMessage(data.message || 'Refund recorded.', 'success');
                loadSales();
                return;
            }

            const error = document.getElementById('rfError');
            error.textContent = data.message || Object.values(data.errors || {})[0]?.[0] || 'Could not record the refund.';
            error.classList.remove('hidden');
        }

        ['deliveryModal', 'refundModal'].forEach((id) => {
            document.getElementById(id).addEventListener('click', (event) => {
                if (event.target.id === id) {
                    closeDeliveryModal();
                    closeRefundModal();
                }
            });
        });

        showFocus();
        loadSales();
</script>
@endpush
