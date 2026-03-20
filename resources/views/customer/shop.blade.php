<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shop - RANEY LUBRICANTS TRADING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --surface: #f6f8fb;
            --card: rgba(255, 255, 255, 0.82);
            --card-solid: #ffffff;
            --ink: #16202a;
            --muted: #6f7d8c;
            --line: rgba(21, 35, 54, 0.1);
            --primary: #148a67;
            --primary-soft: rgba(20, 138, 103, 0.1);
            --accent: #d9b14a;
            --accent-soft: rgba(217, 177, 74, 0.12);
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-[var(--surface)] text-[var(--ink)]">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)]">
        <header class="sticky top-0 z-50 border-b border-[var(--line)] bg-[rgba(246,248,251,0.84)] backdrop-blur-xl">
            <div class="container mx-auto px-4 sm:px-6">
                <div class="flex min-h-16 flex-col gap-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <a href="/shop" class="group block">
                        <div class="text-lg font-black tracking-tight sm:text-xl">
                            <span class="text-[var(--primary)]">RANEY</span>
                            <span class="text-[var(--accent)]"> LUBRICANTS</span>
                        </div>
                        <div class="text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
                    </a>

                    <div class="relative flex-1 lg:max-w-2xl">
                        <input id="searchInput" type="search" placeholder="Search products by name, brand, type, or viscosity..." class="w-full rounded-2xl border border-[var(--line)] bg-white/80 px-5 py-3.5 pl-12 text-sm shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--muted)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                    </div>

                    <div class="flex flex-wrap gap-2 items-center">
                        @auth
                            @if(auth()->user()->isCustomer())
                                <a href="/cart" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l.4 2m0 0L7 14h10l2-8H5.4ZM7 14l-1 5h12M9 20a1 1 0 1 0 0 .01M17 20a1 1 0 1 0 0 .01"/></svg>
                                    <span>Cart</span>
                                </a>
                                <a href="/orders" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h10M9 9h10M9 13h10M5 5h.01M5 9h.01M5 13h.01M5 17h.01M9 17h10"/></svg>
                                    <span>Orders</span>
                                </a>
                                <a href="/profile" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19a4 4 0 0 0-8 0m8 0h4m-4 0H5m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                                    <span>Profile</span>
                                </a>
                                <button onclick="logout()" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"/></svg>
                                    <span>Logout</span>
                                </button>
                            @else
                                <a href="/shop/login" class="rounded-xl bg-[var(--primary)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">Customer Login</a>
                            @endif
                        @else
                            <a href="/shop/login" class="rounded-xl bg-[var(--primary)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110">Login</a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <main class="container mx-auto px-4 py-8 sm:px-6">
            <section class="relative mb-8 overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,_#0d1f18_0%,_#163d2c_55%,_#1a3020_100%)] px-8 py-12 shadow-2xl text-white">
                {{-- Decorative blobs --}}
                <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-[radial-gradient(circle,_rgba(20,138,103,0.35)_0%,_rgba(20,138,103,0)_70%)]"></div>
                <div class="pointer-events-none absolute -bottom-16 left-1/4 h-56 w-56 rounded-full bg-[radial-gradient(circle,_rgba(217,177,74,0.22)_0%,_rgba(217,177,74,0)_70%)]"></div>
                <div class="pointer-events-none absolute right-1/3 top-1/2 h-40 w-40 rounded-full bg-[radial-gradient(circle,_rgba(20,138,103,0.18)_0%,_rgba(20,138,103,0)_70%)]"></div>

                <div class="relative flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--accent)]">RANEY LUBRICANTS TRADING</div>
                        <h1 class="mt-5 text-4xl font-black leading-tight md:text-5xl">Premium engine oils &amp; lubricants for every engine.</h1>
                        <p class="mt-4 max-w-xl text-sm leading-7 text-white/70">From daily drivers to heavy-duty machinery — find the right oil, coolant, or fluid for your needs.</p>
                        <div class="mt-7 flex flex-wrap gap-3">
                            <a href="#catalog" class="rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-[var(--primary-dark)]">Browse Catalog</a>
                            <a href="#featuredProducts" class="rounded-xl border border-white/20 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">Top Products</a>
                        </div>
                    </div>

                    <div class="flex-shrink-0">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-white/50 mb-3">Shop by Brand</div>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" onclick="quickFilterBrand('SOLAR')" class="rounded-full border border-white/20 bg-white/10 px-5 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-[var(--primary)] hover:border-[var(--primary)]">SOLAR</button>
                            <button type="button" onclick="quickFilterBrand('CANROYAL')" class="rounded-full border border-white/20 bg-white/10 px-5 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-[var(--primary)] hover:border-[var(--primary)]">CANROYAL</button>
                            <button type="button" onclick="quickFilterBrand('PATROL')" class="rounded-full border border-white/20 bg-white/10 px-5 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-[var(--primary)] hover:border-[var(--primary)]">PATROL</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mb-8 grid gap-4 md:grid-cols-3">
                <div class="rounded-[1.75rem] border border-[var(--line)] bg-[var(--card)] p-6 shadow-lg backdrop-blur">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Featured Brands</div>
                    <div id="brandShowcase" class="mt-4 flex flex-wrap gap-2">
                        <span class="rounded-full bg-white px-3 py-2 text-sm text-[var(--muted)]">Loading brands...</span>
                    </div>
                </div>
                <div class="rounded-[1.75rem] border border-[var(--accent-soft)] bg-[rgba(255,252,243,0.9)] p-6 shadow-lg">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[#9d7b20]">Top Product Types</div>
                    <div id="typeShowcase" class="mt-4 flex flex-wrap gap-2">
                        <span class="rounded-full bg-white px-3 py-2 text-sm text-[#9d7b20]">Loading types...</span>
                    </div>
                </div>
                <div class="rounded-[1.75rem] border border-[rgba(21,35,54,0.1)] bg-white/80 p-6 shadow-lg">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--primary)]">Shopping Tips</div>
                    <div class="mt-4 space-y-2 text-sm leading-6 text-[var(--muted)]">
                        <p>Check viscosity grade and unit size before adding to cart.</p>
                        <p>Use filters to compare oil categories, coolant products, and stock-ready items.</p>
                    </div>
                </div>
            </section>

            <section id="featuredProducts" class="mb-8">
                <div class="mb-5 flex items-end justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--primary)]">Featured Products</div>
                        <h2 class="mt-2 text-3xl font-black text-[var(--ink)]">Top Products</h2>
                    </div>
                    <a href="#catalog" class="text-sm font-semibold text-[var(--muted)] hover:text-[var(--primary)]">Jump to full catalog</a>
                </div>
                <div id="featuredGrid" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <div class="rounded-[1.75rem] bg-white p-6 text-center text-[var(--muted)] shadow-lg">Loading featured products...</div>
                </div>
            </section>

            <section id="catalog" class="mb-6 rounded-[2rem] border border-[var(--line)] bg-[var(--card)] p-5 shadow-lg backdrop-blur">
                <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Full Catalog</div>
                        <h2 class="mt-2 text-3xl font-black text-[var(--ink)]">Search the full RANEY catalog</h2>
                    </div>
                    <p class="max-w-xl text-sm leading-6 text-[var(--muted)]">Browse by brand, product type, packaging size, and keywords to find the right lubricant or coolant faster.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3 text-sm text-[var(--muted)]">Use the sticky search bar above for quick product lookup.</div>
                    <select id="brandFilter" class="rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]">
                        <option value="">All Brands</option>
                    </select>
                    <select id="typeFilter" class="rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3 shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]">
                        <option value="">All Types</option>
                        <option value="Synthetic">Synthetic</option>
                        <option value="Semi-Synthetic">Semi-Synthetic</option>
                        <option value="Mineral">Mineral</option>
                        <option value="Coolant">Coolant</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </section>

            <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

            <div id="productsGrid" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div class="col-span-full rounded-[1.5rem] border border-[var(--line)] bg-white/80 py-10 text-center text-[var(--muted)] shadow-sm">Loading products...</div>
            </div>
        </main>

        <footer class="border-t border-[var(--line)] bg-[rgba(255,255,255,0.88)] backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="space-y-4">
                        <div>
                            <div class="text-xl font-black tracking-tight">
                                <span class="text-[var(--primary)]">RANEY</span>
                                <span class="text-[var(--accent)]"> LUBRICANTS</span>
                            </div>
                            <div class="mt-0.5 text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
                        </div>
                        <p class="text-sm leading-6 text-[var(--muted)]">Your trusted partner for premium engine oils and lubricants. Quality products for maximum performance.</p>
                        <div class="flex gap-3">
                            <span class="cursor-pointer flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            </span>
                            <span class="cursor-pointer flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                            </span>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">About Us</h4>
                        <ul class="space-y-2 text-sm">
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Our Story</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Our Products</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Why Choose Us</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Careers</span></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                        <ul class="space-y-2 text-sm">
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">My Account</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Order Tracking</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Shipping Info</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Returns &amp; Refunds</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">FAQs</span></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Contact Us</h4>
                        <ul class="space-y-3 text-sm text-[var(--muted)]">
                            <li class="flex items-start gap-3">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>123 Industrial Ave, Makati City, Metro Manila, Philippines</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span>+63 2 1234 5678</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>sales@raneylubricants.ph</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Mon – Sat: 8AM – 6PM</span>
                            </li>
                        </ul>
                    </div>

                </div>

                <div class="mt-8 border-t border-[var(--line)] pt-6 flex flex-col items-center justify-between gap-4 text-center sm:flex-row sm:text-left">
                    <p class="text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.</p>
                    <div class="flex gap-4 text-xs text-[var(--muted)]">
                        <span class="cursor-pointer hover:text-[var(--primary)]">Privacy Policy</span>
                        <span class="cursor-pointer hover:text-[var(--primary)]">Terms of Service</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const isCustomer = @json(auth()->check() && auth()->user()->isCustomer());
        let products = [];
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            const isSuccess = type === 'success';
            const icon = isSuccess
                ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
                : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>';
            const label = isSuccess ? 'Cart Update' : 'Action Needed';
            box.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">${icon}</div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${label}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${text}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        function formatCurrency(value) {
            return `PHP ${Number(value || 0).toFixed(2)}`;
        }

        function getProductStock(product) {
            const stock = Number(product?.quantity);
            return Number.isNaN(stock) ? 0 : stock;
        }

        function renderShowcase() {
            const brands = [...new Set(products.map((product) => product.brand))].slice(0, 8);
            const types = [...new Set(products.map((product) => product.oil_type))];
            const availableCount = products.filter((product) => getProductStock(product) > 0).length;

            document.getElementById('brandShowcase').innerHTML = brands.length
                ? brands.map((brand) => `<button type="button" onclick="quickFilterBrand('${brand.replace(/'/g, "\\'")}')" class="rounded-full border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">${brand}</button>`).join('')
                : '<span class="rounded-full bg-white px-3 py-2 text-sm text-[var(--muted)]">No brands yet</span>';

            document.getElementById('typeShowcase').innerHTML = types.length
                ? types.map((type) => `<button type="button" onclick="quickFilterType('${type.replace(/'/g, "\\'")}')" class="rounded-full border border-transparent bg-white px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:bg-[var(--primary-soft)] hover:text-[var(--primary)]">${type}</button>`).join('')
                : '<span class="rounded-full bg-white px-3 py-2 text-sm text-[var(--muted)]">No types yet</span>';
        }

        async function loadFeaturedProducts() {
            try {
                const response = await fetch('/shop-api/featured-products');
                const data = await response.json();
                const featured = data.data || [];

                document.getElementById('featuredGrid').innerHTML = featured.length
                ? featured.map((product, index) => `
                    <article onclick="window.location.href='/shop/products/${product.product_id}'" class="group flex flex-col cursor-pointer overflow-hidden rounded-[1.75rem] border border-[var(--line)] bg-[var(--card-solid)] shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <div class="relative flex-shrink-0">
                            ${product.image_url
                                ? `<img src="${product.image_url}" alt="${product.product_name}" class="h-64 w-full object-cover">`
                                : `<div class="flex h-64 items-center justify-center bg-[linear-gradient(135deg,_rgba(20,138,103,0.12),_rgba(255,255,255,0.95)_45%,_rgba(217,177,74,0.18))]"><span class="rounded-full bg-white/90 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">No Image</span></div>`}
                            <div class="absolute left-4 top-4 rounded-full bg-[rgba(22,32,42,0.82)] px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-[var(--accent)]">Pick ${index + 1}</div>
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <h3 class="mb-1 line-clamp-2 text-lg font-black leading-snug text-[var(--ink)]">${product.product_name}</h3>
                                    <p class="truncate text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">${product.brand}</p>
                                </div>
                                <span class="flex-shrink-0 rounded-full bg-[var(--primary-soft)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.15em] text-[var(--primary)]">${product.oil_type}</span>
                            </div>
                            <p class="mb-3 truncate text-sm text-[var(--muted)]">${product.viscosity_grade || 'Standard'} | ${product.unit}</p>
                            <div class="mt-auto">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-2xl font-black text-[var(--primary)]">${formatCurrency(product.price)}</span>
                                    <span class="rounded-full bg-[#f3f6f8] px-3 py-1 text-xs font-semibold text-[var(--muted)]">Stock: ${product.quantity}</span>
                                </div>
                                <div class="h-px bg-[var(--line)]"></div>
                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <span class="text-xs font-bold uppercase tracking-[0.25em] text-[var(--muted)] group-hover:text-[var(--primary)]">View Details</span>
                                    <button onclick="event.stopPropagation(); addToCart(${product.product_id})" class="rounded-xl bg-[var(--primary)] px-4 py-2 text-sm font-bold text-white transition hover:brightness-110">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                `).join('')
                : '<div class="rounded-[1.75rem] bg-white p-6 text-center text-[var(--muted)] shadow-lg">No featured products available yet.</div>';
            } catch (error) {
                document.getElementById('featuredGrid').innerHTML =
                    '<div class="rounded-[1.75rem] bg-white p-6 text-center text-red-500 shadow-lg">Failed to load featured products.</div>';
            }
        }

        function renderProducts() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const brand = document.getElementById('brandFilter').value;
            const type = document.getElementById('typeFilter').value;

            const filtered = products.filter((product) => {
                const matchesSearch =
                    product.product_name.toLowerCase().includes(search) ||
                    product.brand.toLowerCase().includes(search) ||
                    product.oil_type.toLowerCase().includes(search) ||
                    (product.viscosity_grade || '').toLowerCase().includes(search);

                const matchesBrand = !brand || product.brand === brand;
                const matchesType = !type || product.oil_type === type;

                return matchesSearch && matchesBrand && matchesType;
            });

            const availableProducts = filtered
                .filter((product) => getProductStock(product) > 0)
                .sort((a, b) => a.product_name.localeCompare(b.product_name));

            const unavailableProducts = filtered
                .filter((product) => getProductStock(product) === 0)
                .sort((a, b) => a.product_name.localeCompare(b.product_name));

            const orderedProducts = [...availableProducts, ...unavailableProducts];

            document.getElementById('productsGrid').innerHTML = orderedProducts.length
                ? orderedProducts.map((product, index) => {
                    const stock = getProductStock(product);
                    const unavailable = stock === 0;
                    const showUnavailableDivider = unavailable && index === availableProducts.length;

                    return `
                        ${showUnavailableDivider ? `
                            <div class="col-span-full mt-2 rounded-[1.5rem] border border-[var(--line)] bg-slate-100/90 px-6 py-4 text-sm font-semibold uppercase tracking-[0.22em] text-slate-600 shadow-sm">
                                Unavailable Products
                            </div>
                        ` : ''}
                        <article onclick="window.location.href='/shop/products/${product.product_id}'" class="group flex flex-col cursor-pointer overflow-hidden rounded-[1.75rem] border border-[var(--line)] ${unavailable ? 'bg-slate-100/90 opacity-80' : 'bg-[var(--card-solid)]'} shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                            ${product.image_url
                                ? `<div class="flex-shrink-0"><img src="${product.image_url}" alt="${product.product_name}" class="h-60 w-full object-cover ${unavailable ? 'grayscale' : ''}"></div>`
                                : `<div class="flex-shrink-0 flex h-60 items-center justify-center bg-[linear-gradient(135deg,_rgba(20,138,103,0.12),_rgba(255,255,255,0.95)_45%,_rgba(217,177,74,0.18))] ${unavailable ? 'grayscale' : ''}"><span class="rounded-full bg-white/90 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">No Image</span></div>`}
                            <div class="flex flex-1 flex-col p-5">
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="mb-1 line-clamp-2 text-base font-black leading-snug text-[var(--ink)]">${product.product_name}</h3>
                                        <p class="truncate text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">${product.brand}</p>
                                    </div>
                                    <span class="flex-shrink-0 rounded-full bg-[var(--primary-soft)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.15em] text-[var(--primary)]">${product.oil_type}</span>
                                </div>
                                <p class="mb-3 truncate text-sm text-[var(--muted)]">${product.viscosity_grade || 'Standard'} | ${product.unit}</p>
                                <div class="mt-auto">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-xl font-black text-[var(--primary)]">${formatCurrency(product.price)}</span>
                                        <span class="rounded-full ${unavailable ? 'bg-slate-200 text-slate-600' : 'bg-[#f3f6f8] text-[var(--muted)]'} px-3 py-1 text-xs font-semibold">Stock: ${stock}</span>
                                    </div>
                                    <div class="h-px bg-[var(--line)]"></div>
                                    <div class="mt-3 flex items-center justify-between gap-3">
                                        <span class="text-xs font-bold uppercase tracking-[0.25em] text-[var(--muted)] group-hover:text-[var(--primary)]">View Details</span>
                                        <button onclick="event.stopPropagation(); addToCart(${product.product_id})" class="rounded-xl ${unavailable ? 'cursor-not-allowed bg-slate-400' : 'bg-[var(--primary)]'} px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 ${!isCustomer ? 'opacity-60 cursor-not-allowed' : ''}" ${unavailable ? 'disabled' : ''}>
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    `;
                }).join('')
                : '<div class="col-span-full rounded-[1.5rem] border border-[var(--line)] bg-white/80 py-10 text-center text-[var(--muted)] shadow-sm">No matching products found.</div>';
        }

        function quickFilterBrand(brand) {
            document.getElementById('brandFilter').value = brand;
            renderProducts();
            document.getElementById('catalog').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function quickFilterType(type) {
            document.getElementById('typeFilter').value = type;
            renderProducts();
            document.getElementById('catalog').scrollIntoView({ behavior: 'smooth', block: 'start' });
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
                    renderShowcase();
                    loadFeaturedProducts();
                    renderProducts();
                }
            } catch (error) {
                document.getElementById('productsGrid').innerHTML =
                    '<div class="col-span-full rounded-[1.5rem] border border-[var(--line)] bg-white/80 py-10 text-center text-red-500 shadow-sm">Error loading products.</div>';
                document.getElementById('featuredGrid').innerHTML =
                    '<div class="rounded-[1.75rem] bg-white p-6 text-center text-red-500 shadow-lg">Failed to load featured products.</div>';
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