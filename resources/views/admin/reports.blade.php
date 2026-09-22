@extends('layouts.admin')

@section('title', 'RANEY LUBRICANTS TRADING — Admin')

@section('content')
    <div>
        <h1 class="text-2xl font-black text-[var(--ink)] sm:text-3xl">Reports</h1>
        <p class="text-[var(--muted)]">Review operational summaries and export CSV reports for defense or backup purposes.</p>
    </div>

    <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">From</label>
                <input type="date" id="fromDate" class="mt-1 block w-full rounded-md border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">To</label>
                <input type="date" id="toDate" class="mt-1 block w-full rounded-md border px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Payment Status</label>
                <select id="paymentStatus" class="mt-1 block w-full rounded-md border px-3 py-2">
                    <option value="">All</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Delivery Status</label>
                <select id="deliveryStatus" class="mt-1 block w-full rounded-md border px-3 py-2">
                    <option value="">All</option>
                    <option value="to_deliver">To Deliver</option>
                    <option value="to_receive">To Receive</option>
                    <option value="delivered">Delivered</option>
                </select>
            </div>
        </div>
        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:gap-3">
            <button onclick="loadSalesReport()" class="bg-[var(--primary)] text-white px-4 py-2 rounded hover:bg-[var(--primary-dark)]">Refresh Sales Report</button>
            <button onclick="downloadSalesCsv()" class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">Download Sales CSV</button>
            <button onclick="downloadInventoryCsv()" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">Download Inventory CSV</button>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--line)]">
                <h2 class="text-xl font-bold text-[var(--ink)]">Sales Report</h2>
            </div>
            <div class="admin-table-wrap">
                <table class="min-w-full">
                    <thead class="bg-[var(--surface)]">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Sale ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Delivery</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody id="salesReportBody">
                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading sales report...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-[var(--line)]">
                <h2 class="text-xl font-bold text-[var(--ink)]">Inventory Report</h2>
            </div>
            <div class="admin-table-wrap">
                <table class="min-w-full">
                    <thead class="bg-[var(--surface)]">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Value</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-[var(--muted)] uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryReportBody">
                        <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Loading inventory report...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>

        function buildQuery() {
            const params = new URLSearchParams();
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            const paymentStatus = document.getElementById('paymentStatus').value;
            const deliveryStatus = document.getElementById('deliveryStatus').value;

            if (from) params.set('from', from);
            if (to) params.set('to', to);
            if (paymentStatus) params.set('payment_status', paymentStatus);
            if (deliveryStatus) params.set('delivery_status', deliveryStatus);

            const query = params.toString();
            return query ? `?${query}` : '';
        }

        async function loadSalesReport() {
            const response = await fetch(`/admin-api/reports/sales${buildQuery()}`, {
                headers: { Accept: 'application/json' }
            });
            const data = await response.json();
            const rows = data.data || [];
            const body = document.getElementById('salesReportBody');

            body.innerHTML = rows.length
                ? rows.map((row) => `
                    <tr>
                        <td class="px-6 py-4">#${row.sale_id}</td>
                        <td class="px-6 py-4">${escapeHtml(row.customer_name || 'Walk-in Customer')}</td>
                        <td class="px-6 py-4">${row.payment_status}</td>
                        <td class="px-6 py-4">${row.delivery_status}</td>
                        <td class="px-6 py-4">${formatCurrency(row.total_amount)}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No sales found for the selected filters.</td></tr>';
        }

        async function loadInventoryReport() {
            const response = await fetch('/admin-api/reports/inventory', {
                headers: { Accept: 'application/json' }
            });
            const data = await response.json();
            const rows = data.data || [];
            const body = document.getElementById('inventoryReportBody');

            body.innerHTML = rows.length
                ? rows.map((row) => `
                    <tr>
                        <td class="px-6 py-4">${escapeHtml(row.product_name)}</td>
                        <td class="px-6 py-4">${row.quantity}</td>
                        <td class="px-6 py-4">${formatCurrency(row.value)}</td>
                        <td class="px-6 py-4">${row.status}</td>
                    </tr>
                `).join('')
                : '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No inventory records found.</td></tr>';
        }

        function downloadSalesCsv() {
            window.location.href = `/admin-api/reports/sales/export${buildQuery()}`;
        }

        function downloadInventoryCsv() {
            window.location.href = '/admin-api/reports/inventory/export';
        }

        loadSalesReport();
        loadInventoryReport();
</script>
@endpush
