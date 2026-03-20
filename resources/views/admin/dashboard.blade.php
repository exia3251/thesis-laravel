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
            --primary:        #148a67;
            --primary-dark:   #0f6b50;
            --primary-soft:   rgba(20,138,103,0.10);
            --accent:         #d9b14a;
            --accent-soft:    rgba(217,177,74,0.12);
            --ink:            #16202a;
            --muted:          #6f7d8c;
            --surface:        #f3f6f9;
            --card:           #ffffff;
            --line:           rgba(21,35,54,0.10);
            --sidebar-bg:     #0d1f18;
            --sidebar-hover:  rgba(20,138,103,0.18);
            --sidebar-active: rgba(20,138,103,0.28);
        }
        body { background: var(--surface); color: var(--ink); }
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary) !important;
            outline: none;
            box-shadow: 0 0 0 3px var(--primary-soft);
        }
    </style>
</head>
<body class="bg-[var(--surface)]">
    <div class="min-h-screen bg-[var(--surface)]">
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
                <a href="/admin/dashboard" class="flex items-center rounded-2xl px-4 py-3 font-semibold text-white bg-[var(--sidebar-active)] ring-1 ring-white/10">Dashboard</a>
                <a href="/admin/products" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Products</a>
                <a href="/admin/inventory" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Inventory</a>
                <a href="/admin/sales" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Sales</a>
                <a href="/admin/reports" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Reports</a>
                @if(auth()->user()->isSuperAdmin())
                    <a href="/admin/users" class="flex items-center rounded-2xl px-4 py-3 text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Users</a>
                @endif
                <button type="button" onclick="logout()" class="w-full rounded-2xl px-4 py-3 text-left text-slate-300 transition hover:bg-[var(--sidebar-hover)] hover:text-white">Logout</button>
            </nav>
        </div>

        <div class="ml-64 p-8">
            <div class="mb-8 overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,_#0d1f18_0%,_#163d2c_50%,_#0f2a20_100%)] p-8 text-white shadow-2xl">
                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6">
                    <div>
                        <div class="inline-flex rounded-full border border-white/10 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-[var(--accent)]">Operations Dashboard</div>
                        <h1 class="mt-5 text-4xl font-black">Keep stock moving and sales visible.</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">A fuel-retail inspired command center for engine oil inventory, sales activity, and reorder monitoring.</p>
                    </div>
                    <div class="flex-shrink-0 rounded-[1.25rem] bg-white/10 border border-white/10 p-4 backdrop-blur">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60 mb-3">Filter by Date</div>
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-white/70 w-10">From</label>
                                <input id="dateFrom" type="date" class="rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm text-white placeholder-white/40 focus:outline-none focus:border-white/40">
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-white/70 w-10">To</label>
                                <input id="dateTo" type="date" class="rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm text-white placeholder-white/40 focus:outline-none focus:border-white/40">
                            </div>
                            <div class="flex gap-2 mt-1">
                                <button type="button" onclick="applyDateFilter()" class="flex-1 rounded-full bg-[var(--primary)] px-4 py-2 text-xs font-bold text-white transition hover:bg-[var(--primary-dark)]">Apply</button>
                                <button type="button" onclick="resetDateFilter()" class="flex-1 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold text-white hover:bg-white/20">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
                <div class="rounded-[1.5rem] border border-[var(--line)] bg-[var(--card)] p-6 shadow-lg">
                    <div class="text-xs font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">Total Products</div>
                    <div class="mt-3 text-3xl font-black text-[var(--ink)]" id="totalProducts">0</div>
                </div>
                <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-6 shadow-lg">
                    <div class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-700">Out of Stock</div>
                    <div class="mt-3 text-3xl font-black text-amber-800" id="lowStock">0</div>
                </div>
                <div class="rounded-[1.5rem] border border-orange-200 bg-orange-50 p-6 shadow-lg">
                    <div class="text-xs font-semibold uppercase tracking-[0.25em] text-orange-700">Low Stock Items</div>
                    <div class="mt-3 text-3xl font-black text-orange-800" id="lowStockItems">0</div>
                </div>
                <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 p-6 shadow-lg">
                    <div class="text-xs font-semibold uppercase tracking-[0.25em] text-emerald-700">Sales Revenue</div>
                    <div class="mt-3 text-3xl font-black text-emerald-700" id="totalSales">PHP 0.00</div>
                </div>
                <div class="rounded-[1.5rem] border border-sky-200 bg-sky-50 p-6 shadow-lg">
                    <div class="text-xs font-semibold uppercase tracking-[0.25em] text-sky-700">Sales Count</div>
                    <div class="mt-3 text-3xl font-black text-sky-700" id="ordersToday">0</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="rounded-2xl bg-amber-50 border border-amber-200 p-6 shadow-lg">
                    <div class="text-amber-700 text-sm">Open Payment Alerts</div>
                    <div class="text-3xl font-bold text-amber-900" id="unpaidSales">0</div>
                    <p class="text-sm text-amber-700 mt-2">Sales that are unpaid or partially paid.</p>
                </div>
                <div class="rounded-2xl bg-sky-50 border border-sky-200 p-6 shadow-lg">
                    <div class="text-sky-700 text-sm">Pending Deliveries</div>
                    <div class="text-3xl font-bold text-sky-900" id="pendingDeliveries">0</div>
                    <p class="text-sm text-sky-700 mt-2">Sales waiting to be received or delivered.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
                <div class="rounded-[1.5rem] bg-[var(--card)] shadow-lg border border-[var(--line)]">
                    <div class="border-b border-[var(--line)] px-6 py-4">
                        <h2 class="text-xl font-bold text-[var(--ink)]">Out of Stock</h2>
                        <p class="text-xs text-[var(--muted)] mt-1">Products with zero remaining stock</p>
                    </div>
                    <div class="p-6">
                        <div id="lowStockAlerts" class="space-y-3 text-sm text-gray-700">
                            <p class="text-gray-500">Loading...</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-[1.5rem] bg-[var(--card)] shadow-lg border border-[var(--line)]">
                    <div class="border-b border-[var(--line)] px-6 py-4">
                        <h2 class="text-xl font-bold text-[var(--ink)]">Low Stock Alerts</h2>
                        <p class="text-xs text-[var(--muted)] mt-1">Products at or below half their reorder level</p>
                    </div>
                    <div class="p-6">
                        <div id="lowStockItemAlerts" class="space-y-3 text-sm text-gray-700">
                            <p class="text-gray-500">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="rounded-[1.5rem] bg-[var(--card)] shadow-lg border border-[var(--line)]">
                    <div class="border-b border-[var(--line)] px-6 py-4">
                        <h2 class="text-xl font-bold text-[var(--ink)]">Recent Products</h2>
                    </div>
                    <div class="p-6">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">Product Name</th>
                                    <th class="text-left py-2">Brand</th>
                                    <th class="text-left py-2">Price</th>
                                    <th class="text-left py-2">Stock</th>
                                </tr>
                            </thead>
                            <tbody id="productsBody">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-gray-500">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-[1.5rem] bg-[var(--card)] shadow-lg border border-[var(--line)]">
                    <div class="border-b border-[var(--line)] px-6 py-4">
                        <h2 class="text-xl font-bold text-[var(--ink)]">Top Products</h2>
                    </div>
                    <div class="p-6">
                        <div id="topProducts" class="space-y-3 text-sm text-gray-700">
                            <p class="text-gray-500">Loading...</p>
                        </div>
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

        async function loadDashboard() {
            try {
                const params = new URLSearchParams();
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;

                if (dateFrom) {
                    params.set('date_from', dateFrom);
                }

                if (dateTo) {
                    params.set('date_to', dateTo);
                }

                const statsUrl = params.toString()
                    ? `/admin-api/dashboard/stats?${params.toString()}`
                    : '/admin-api/dashboard/stats';

                const [statsResponse, productsResponse] = await Promise.all([
                    fetch(statsUrl, { headers: { Accept: 'application/json' } }),
                    fetch('/admin-api/products', { headers: { Accept: 'application/json' } })
                ]);

                if (statsResponse.status === 401 || productsResponse.status === 401) {
                    window.location.href = '/admin/login';
                    return;
                }

                const statsPayload = await statsResponse.json();
                const productsPayload = await productsResponse.json();

                if (statsPayload.success) {
                    const stats = statsPayload.data;
                    document.getElementById('totalProducts').textContent = stats.total_products;
                    document.getElementById('lowStock').textContent = stats.low_stock_count;
                    document.getElementById('lowStockItems').textContent = stats.low_stock_items_count;
                    document.getElementById('totalSales').textContent = formatCurrency(stats.sales_revenue);
                    document.getElementById('ordersToday').textContent = stats.sales_count;
                    document.getElementById('unpaidSales').textContent = stats.unpaid_sales;
                    document.getElementById('pendingDeliveries').textContent = stats.pending_deliveries;

                    const topProducts = document.getElementById('topProducts');
                    if (stats.top_products.length === 0) {
                        topProducts.innerHTML = '<p class="text-gray-500">No sales data yet.</p>';
                    } else {
                        topProducts.innerHTML = stats.top_products.map((product) => `
                            <div class="flex items-center justify-between border rounded px-3 py-2">
                                <span>${product.product_name}</span>
                                <span class="font-semibold">${product.total_sold} sold</span>
                            </div>
                        `).join('');
                    }

                    const lowStockAlerts = document.getElementById('lowStockAlerts');
                    if (stats.low_stock_products.length === 0) {
                        lowStockAlerts.innerHTML = '<p class="text-gray-500">No out of stock products.</p>';
                    } else {
                        lowStockAlerts.innerHTML = stats.low_stock_products.map((product) => `
                            <div class="flex items-center justify-between border border-red-100 bg-red-50 rounded px-3 py-2">
                                <span class="font-medium text-red-900">${product.product_name}</span>
                                <span class="text-xs font-semibold text-red-700">Out of Stock</span>
                            </div>
                        `).join('');
                    }

                    const lowStockItemAlerts = document.getElementById('lowStockItemAlerts');
                    if (stats.low_stock_alerts.length === 0) {
                        lowStockItemAlerts.innerHTML = '<p class="text-gray-500">No low stock alerts.</p>';
                    } else {
                        lowStockItemAlerts.innerHTML = stats.low_stock_alerts.map((product) => `
                            <div class="flex items-center justify-between border border-orange-100 bg-orange-50 rounded px-3 py-2">
                                <span class="font-medium text-orange-900">${product.product_name}</span>
                                <span class="text-xs font-semibold text-orange-700">${product.quantity} left / reorder at ${product.reorder_level}</span>
                            </div>
                        `).join('');
                    }
                }

                if (productsPayload.success) {
                    const tbody = document.getElementById('productsBody');
                    const products = productsPayload.data.slice(0, 5);

                    tbody.innerHTML = products.length
                        ? products.map((product) => `
                            <tr class="border-b">
                                <td class="py-2">${product.product_name}</td>
                                <td class="py-2">${product.brand}</td>
                                <td class="py-2">${formatCurrency(product.price)}</td>
                                <td class="py-2">${product.inventory ? product.inventory.quantity : 0}</td>
                            </tr>
                        `).join('')
                        : '<tr><td colspan="4" class="text-center py-4 text-gray-500">No products yet.</td></tr>';
                }
            } catch (error) {
                document.getElementById('productsBody').innerHTML =
                    '<tr><td colspan="4" class="text-center py-4 text-red-500">Failed to load dashboard data.</td></tr>';
                document.getElementById('topProducts').innerHTML =
                    '<p class="text-red-500">Failed to load top products.</p>';
                document.getElementById('lowStockAlerts').innerHTML =
                    '<p class="text-red-500">Failed to load low stock alerts.</p>';
            }
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

        function applyDateFilter() {
            loadDashboard();
        }

        function resetDateFilter() {
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            loadDashboard();
        }

        loadDashboard();
    </script>
</body>
</html>