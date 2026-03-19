<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shop - Engine Oil Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --fuel-yellow: #ffd500;
            --fuel-red: #d71920;
            --fuel-dark: #13161c;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
    <header class="bg-[linear-gradient(135deg,_#111318_0%,_#1d2330_55%,_#260f13_100%)] text-white shadow-2xl">
        <div class="container mx-auto px-4 py-6 flex flex-col gap-4 md:flex-row md:justify-between md:items-center">
            <div>
                <div class="inline-flex rounded-full border border-white/10 bg-white/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.32em] text-[var(--fuel-yellow)]">Lubricants Storefront</div>
                <h1 class="mt-4 text-3xl font-black text-white">Engine Oil Shop</h1>
                <p class="mt-2 text-sm text-slate-300">A bold storefront inspired by premium fuel-retail branding and built for practical ecommerce ordering.</p>
            </div>
            <div class="flex flex-wrap gap-4 items-center">
                @auth
                    @if(auth()->user()->isCustomer())
                        <a href="/cart" class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">Cart</a>
                        <a href="/orders" class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">Orders</a>
                        <a href="/profile" class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">Profile</a>
                        <button onclick="logout()" class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">Logout</button>
                    @else
                        <a href="/shop/login" class="rounded-full bg-[var(--fuel-red)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">Customer Login</a>
                    @endif
                @else
                    <a href="/shop/login" class="rounded-full bg-[var(--fuel-red)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">Login</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="mb-8 overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,_rgba(255,213,0,0.2),_rgba(255,255,255,0.9)_35%,_rgba(215,25,32,0.1)_100%)] p-8 shadow-lg">
            <div class="flex flex-col md:flex-row gap-4 md:items-end md:justify-between">
                <div>
                    <h2 class="text-4xl font-black text-slate-900">High-performance oils for everyday and heavy-duty use.</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-700">Search by brand, oil type, or product name and add items directly to your cart when signed in.</p>
                </div>
                <div class="rounded-2xl bg-white/80 px-4 py-3 text-sm font-semibold text-slate-800 shadow">
                    Fuel-retail inspired design with a cleaner product-first buying flow.
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-3 rounded-[1.5rem] bg-white p-4 shadow-lg">
                <input id="searchInput" type="text" placeholder="Search products" class="rounded-xl border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-[var(--fuel-red)] focus:ring-4 focus:ring-red-100">
                <select id="brandFilter" class="rounded-xl border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-[var(--fuel-red)] focus:ring-4 focus:ring-red-100">
                    <option value="">All Brands</option>
                </select>
                <select id="typeFilter" class="rounded-xl border border-slate-300 px-4 py-3 shadow-sm outline-none transition focus:border-[var(--fuel-red)] focus:ring-4 focus:ring-red-100">
                    <option value="">All Types</option>
                    <option value="Synthetic">Synthetic</option>
                    <option value="Semi-Synthetic">Semi-Synthetic</option>
                    <option value="Mineral">Mineral</option>
                </select>
        </div>

        <div id="message" class="hidden mb-4 rounded-2xl px-4 py-3"></div>

        <div id="productsGrid" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <div class="col-span-full text-center py-8 text-gray-500">Loading products...</div>
        </div>
    </main>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const isCustomer = @json(auth()->check() && auth()->user()->isCustomer());
        let products = [];

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `mb-4 px-4 py-3 rounded ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            box.classList.remove('hidden');
        }

        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

        function renderProducts() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const brand = document.getElementById('brandFilter').value;
            const type = document.getElementById('typeFilter').value;

            const filtered = products.filter((product) => {
                const matchesSearch =
                    product.product_name.toLowerCase().includes(search) ||
                    product.brand.toLowerCase().includes(search) ||
                    product.oil_type.toLowerCase().includes(search);

                const matchesBrand = !brand || product.brand === brand;
                const matchesType = !type || product.oil_type === type;

                return matchesSearch && matchesBrand && matchesType;
            });

            const grid = document.getElementById('productsGrid');
            grid.innerHTML = filtered.length
                ? filtered.map((product) => `
                    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-md transition hover:-translate-y-1 hover:shadow-xl">
                        ${product.image_url
                            ? `<img src="${product.image_url}" alt="${product.product_name}" class="h-52 w-full object-cover">`
                            : `<div class="flex h-52 items-center justify-center bg-[linear-gradient(135deg,_#facc15,_#f3f4f6_50%,_#ef4444)]"><span class="rounded-full bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-700">No Image</span></div>`}
                        <div class="p-5">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-black text-slate-900 mb-1">${product.product_name}</h3>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">${product.brand}</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-amber-800">${product.oil_type}</span>
                            </div>
                            <p class="mb-4 text-sm text-slate-600">${product.viscosity_grade || 'Standard'} | ${product.unit}</p>
                            <div class="mb-4 flex items-center justify-between">
                                <span class="text-2xl font-black text-[var(--fuel-red)]">${formatCurrency(product.price)}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Stock: ${product.quantity}</span>
                            </div>
                            <div class="h-px bg-slate-200"></div>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-xs uppercase tracking-[0.25em] text-slate-400">Engine Oil</span>
                                <button onclick="addToCart(${product.product_id})" class="rounded-full bg-[var(--fuel-red)] px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 ${!isCustomer ? 'opacity-60 cursor-not-allowed' : ''}">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')
                : '<div class="col-span-full text-center py-8 text-gray-500">No matching products found.</div>';
        }

        async function loadProducts() {
            try {
                const response = await fetch('/api/products');
                const data = await response.json();

                if (data.success && data.data) {
                    products = data.data;
                    const brands = [...new Set(products.map((product) => product.brand))];
                    document.getElementById('brandFilter').innerHTML =
                        '<option value="">All Brands</option>' +
                        brands.map((brand) => `<option value="${brand}">${brand}</option>`).join('');
                    renderProducts();
                }
            } catch (error) {
                document.getElementById('productsGrid').innerHTML =
                    '<div class="col-span-full text-center py-8 text-red-500">Error loading products.</div>';
            }
        }

        async function addToCart(productId) {
            if (!isCustomer) {
                window.location.href = '/shop/login';
                return;
            }

            const response = await fetch('/shop-api/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            });

            const data = await response.json();
            showMessage(data.message || 'Cart updated.', response.ok ? 'success' : 'error');
        }

        async function logout() {
            const response = await fetch('/shop/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                window.location.href = '/shop';
            }
        }

        document.getElementById('searchInput').addEventListener('input', renderProducts);
        document.getElementById('brandFilter').addEventListener('change', renderProducts);
        document.getElementById('typeFilter').addEventListener('change', renderProducts);

        loadProducts();
    </script>
</body>
</html>
