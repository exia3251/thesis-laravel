<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RANEY LUBRICANTS TRADING — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary:       #148a67;
            --primary-dark:  #0f6b50;
            --primary-soft:  rgba(20,138,103,0.10);
            --accent:        #d9b14a;
            --accent-soft:   rgba(217,177,74,0.12);
            --ink:           #16202a;
            --muted:         #6f7d8c;
            --surface:       #f3f6f9;
            --card:          #ffffff;
            --line:          rgba(21,35,54,0.10);
            --sidebar-bg:    #0d1f18;
            --sidebar-hover: rgba(20,138,103,0.18);
            --sidebar-active:rgba(20,138,103,0.28);
        }
        body { background: var(--surface); color: var(--ink); }
        input, select, textarea {
            border-color: var(--line) !important;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary) !important;
            outline: none;
            box-shadow: 0 0 0 3px var(--primary-soft);
        }
    </style>
</head>
<body class="bg-[var(--surface)]">
    <div class="min-h-screen">
        <div class="fixed inset-y-0 left-0 w-64 bg-[var(--sidebar-bg)] shadow-2xl">
            <div class="flex h-20 items-center justify-center border-b border-white/10 bg-[linear-gradient(135deg,_rgba(20,138,103,0.15),_transparent)]">
                <div class="text-center">
                    <div class="text-lg font-black tracking-tight leading-tight">
                        <span style="color:#148a67;">RANEY</span><span style="color:#d9b14a;"> LUBRICANTS</span>
                    </div>
                    <div class="text-[10px] uppercase tracking-[0.28em] text-white/50 mt-0.5">Trading</div>
                </div>
            </div>
            <nav class="mt-6 space-y-1 px-3">
                <a href="/admin/dashboard" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Dashboard</a>
                <a href="/admin/products" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Products</a>
                <a href="/admin/inventory" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Inventory</a>
                <a href="/admin/sales" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Sales</a>
                <a href="/admin/reports" class="flex items-center rounded-2xl px-4 py-3 font-semibold text-white bg-[var(--sidebar-active)] ring-1 ring-white/10">Reports</a>
                @if(auth()->user()->isSuperAdmin())
                    <a href="/admin/users" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Users</a>
                @endif
                <button type="button" onclick="logout()" class="w-full rounded-2xl px-4 py-3 text-left text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Logout</button>
            </nav>
        </div>

        <div class="ml-64 p-8 space-y-8">
            <div>
                <h1 class="text-3xl font-black text-[var(--ink)]">Reports</h1>
                <p class="text-[var(--muted)]">Review operational summaries and export CSV reports for defense or backup purposes.</p>
            </div>

            <div class="rounded-2xl bg-[var(--card)] shadow-lg p-6 border border-[var(--line)]">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <div class="flex flex-wrap gap-3 mt-4">
                    <button onclick="loadSalesReport()" class="bg-[var(--primary)] text-white px-4 py-2 rounded hover:bg-[var(--primary-dark)]">Refresh Sales Report</button>
                    <button onclick="downloadSalesCsv()" class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">Download Sales CSV</button>
                    <button onclick="downloadInventoryCsv()" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">Download Inventory CSV</button>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="rounded-2xl bg-[var(--card)] shadow-lg overflow-hidden border border-[var(--line)]">
                    <div class="px-6 py-4 border-b border-[var(--line)]">
                        <h2 class="text-xl font-bold text-[var(--ink)]">Sales Report</h2>
                    </div>
                    <div class="overflow-x-auto">
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

                <div class="rounded-2xl bg-[var(--card)] shadow-lg overflow-hidden border border-[var(--line)]">
                    <div class="px-6 py-4 border-b border-[var(--line)]">
                        <h2 class="text-xl font-bold text-[var(--ink)]">Inventory Report</h2>
                    </div>
                    <div class="overflow-x-auto">
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
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

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
                        <td class="px-6 py-4">${row.customer_name || 'Walk-in Customer'}</td>
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
                        <td class="px-6 py-4">${row.product_name}</td>
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

        async function logout() {
            const response = await fetch('/admin/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                window.location.href = '/admin/login';
            }
        }

        loadSalesReport();
        loadInventoryReport();
    </script>
</body>
</html>