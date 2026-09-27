@extends('layouts.customer')

@php $showSearch = true; @endphp

@section('title', 'Shop - RANEY LUBRICANTS TRADING')

@push('styles')
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush

@section('content')

    <main class="container mx-auto px-4 py-8 sm:px-6">
        <section class="relative mb-6 overflow-hidden rounded-[1.5rem] bg-[linear-gradient(135deg,_#0d1f18_0%,_#163d2c_55%,_#1a3020_100%)] px-5 py-10 text-white shadow-lg sm:px-8 sm:py-12">
            {{-- Decorative blobs --}}
            <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-[radial-gradient(circle,_rgba(20,138,103,0.35)_0%,_rgba(20,138,103,0)_70%)]"></div>
            <div class="pointer-events-none absolute -bottom-16 left-1/4 h-56 w-56 rounded-full bg-[radial-gradient(circle,_rgba(217,177,74,0.22)_0%,_rgba(217,177,74,0)_70%)]"></div>
            <div class="pointer-events-none absolute right-1/3 top-1/2 h-40 w-40 rounded-full bg-[radial-gradient(circle,_rgba(20,138,103,0.18)_0%,_rgba(20,138,103,0)_70%)]"></div>

            <div class="relative flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--accent)]">RANEY LUBRICANTS TRADING</div>
                    <h1 class="mt-5 text-4xl font-black leading-tight md:text-5xl">Premium engine oils &amp; lubricants for every engine.</h1>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="#catalog" class="rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-[var(--primary-dark)]">Browse Catalog</a>
                        <a href="#featuredProducts" class="rounded-xl border border-white/20 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">Top Products</a>
                    </div>
                </div>

                {{-- The brands are the first question a customer answers, so
                     they get room rather than being three small pills in a
                     corner. Each one says how many lines it covers, which is
                     the next thing you would want to know. --}}
                <div class="w-full flex-shrink-0 lg:w-auto">
                    <div class="mb-4 text-[11px] font-semibold uppercase tracking-[0.28em] text-white/50">Shop by Brand</div>
                    <div id="heroBrands" class="grid grid-cols-3 gap-3 lg:w-[22rem]">
                        @foreach (['SOLAR', 'CANROYAL', 'PATROL'] as $brand)
                            <button type="button" onclick="quickFilterBrand('{{ $brand }}')"
                                    class="group flex flex-col items-center justify-center gap-1 rounded-2xl border border-white/20 bg-white/10 px-3 py-5 backdrop-blur transition hover:border-[var(--accent)] hover:bg-white/20">
                                <span class="text-sm font-black tracking-wide text-white">{{ $brand }}</span>
                                <span class="text-[11px] font-semibold text-white/50 group-hover:text-[var(--accent)]" data-brand-count="{{ $brand }}">&nbsp;</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Two cards, matching. The third was a tips panel nobody needed,
             and the middle one was cream against two whites, which made the
             row read as three unrelated things rather than one band. --}}
        <section class="mb-8 grid items-stretch gap-4 md:grid-cols-2">
            <div class="flex flex-col rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm sm:p-6">
                <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Browse by Brand</div>
                <div id="brandShowcase" class="mt-4 flex flex-wrap content-start gap-2">
                    <span class="rounded-full border border-[var(--line)] px-4 py-2 text-sm text-[var(--muted)]">Loading brands...</span>
                </div>
            </div>
            <div class="flex flex-col rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm sm:p-6">
                <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Browse by Oil Type</div>
                <div id="typeShowcase" class="mt-4 flex flex-wrap content-start gap-2">
                    <span class="rounded-full border border-[var(--line)] px-4 py-2 text-sm text-[var(--muted)]">Loading types...</span>
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
                <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 text-center text-[var(--muted)] shadow-sm">Loading featured products...</div>
            </div>
        </section>

        <section id="catalog" class="mb-6 rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm">
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

@endsection

@push('scripts')
<script>
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
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${escapeHtml(label)}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
        }

        function getProductStock(product) {
            const stock = Number(product?.quantity);
            return Number.isNaN(stock) ? 0 : stock;
        }

        // "1 Liter" -> "1L", "200 Liters" -> "200L". A chip has room for the
        // short form and nothing else; anything unrecognised is left alone.
        function packLabel(unit) {
            const match = String(unit || '').match(/^(\d+)\s*Liters?$/i);
            return match ? `${match[1]}L` : (unit || '');
        }

        // One chip style for both cards, so the two read as a pair.
        function showcaseChip(label, count, handler, attribute) {
            return `<button type="button" data-${attribute}="${escapeHtml(label)}" onclick="${handler}(this.dataset.${attribute})"
                        class="inline-flex items-center gap-2 rounded-full border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:bg-[var(--primary-soft)] hover:text-[var(--primary)]">
                        ${escapeHtml(label)}
                        <span class="rounded-full bg-[var(--primary-soft)] px-2 py-0.5 text-[11px] font-bold text-[var(--primary)]">${count}</span>
                    </button>`;
        }

        function renderShowcase() {
            const countBy = (key) => products.reduce((tally, product) => {
                tally[product[key]] = (tally[product[key]] ?? 0) + 1;
                return tally;
            }, {});

            const brandCounts = countBy('brand');
            const typeCounts = countBy('oil_type');

            // Most-stocked first, so the card leads with what we actually have.
            const byCount = (counts) => Object.keys(counts).sort((a, b) => counts[b] - counts[a]);

            const brands = byCount(brandCounts).slice(0, 8);
            const types = byCount(typeCounts);

            const empty = (what) => `<span class="rounded-full border border-[var(--line)] px-4 py-2 text-sm text-[var(--muted)]">No ${what} yet</span>`;

            document.getElementById('brandShowcase').innerHTML = brands.length
                ? brands.map((brand) => showcaseChip(brand, brandCounts[brand], 'quickFilterBrand', 'brand')).join('')
                : empty('brands');

            document.getElementById('typeShowcase').innerHTML = types.length
                ? types.map((type) => showcaseChip(type, typeCounts[type], 'quickFilterType', 'type')).join('')
                : empty('types');

            // The hero buttons carry the same counts.
            document.querySelectorAll('[data-brand-count]').forEach((label) => {
                const count = brandCounts[label.dataset.brandCount] ?? 0;
                label.textContent = count === 1 ? '1 product' : `${count} products`;
            });
        }

        async function loadFeaturedProducts() {
            try {
                const response = await fetch('/shop-api/featured-products');
                const data = await response.json();
                const featured = data.data || [];

                document.getElementById('featuredGrid').innerHTML = featured.length
                ? featured.map((product, index) => `
                    <article onclick="window.location.href='/shop/products/${product.product_id}'" class="group flex flex-col cursor-pointer overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="relative flex-shrink-0">
                            ${product.image_url
                                ? `<img src="${product.image_url}" alt="${escapeHtml(product.product_name)}" class="h-64 w-full bg-white object-contain p-3">`
                                : `<div class="flex h-64 items-center justify-center bg-[linear-gradient(135deg,_rgba(20,138,103,0.12),_rgba(255,255,255,0.95)_45%,_rgba(217,177,74,0.18))]"><span class="rounded-full bg-white/90 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">No Image</span></div>`}
                            <div class="absolute left-4 top-4 rounded-full bg-[rgba(22,32,42,0.82)] px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-[var(--accent)]">Pick ${index + 1}</div>
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <h3 class="mb-1 line-clamp-2 text-lg font-black leading-snug text-[var(--ink)]">${escapeHtml(product.product_name)}</h3>
                                    <p class="truncate text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">${escapeHtml(product.brand)}</p>
                                </div>
                                <span class="flex-shrink-0 rounded-full bg-[var(--primary-soft)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.15em] text-[var(--primary)]">${escapeHtml(product.oil_type)}</span>
                            </div>
                            <p class="mb-3 truncate text-sm text-[var(--muted)]">${escapeHtml(product.viscosity_grade || 'Standard')}</p>

                            <div class="mb-3 flex flex-wrap gap-1.5">
                                ${(product.packs || []).map((pack) => `
                                    <span class="rounded-md border px-2 py-0.5 text-[11px] font-semibold ${pack.quantity > 0
                                        ? 'border-[var(--primary-soft)] bg-[var(--primary-soft)] text-[var(--primary)]'
                                        : 'border-[var(--line)] bg-transparent text-slate-400 line-through'}">${escapeHtml(packLabel(pack.unit))}</span>
                                `).join('')}
                            </div>

                            <div class="mt-auto">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-2xl font-black text-[var(--primary)]">
                                        ${(product.pack_count ?? 1) > 1 ? `<span class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--muted)]">from</span> ` : ''}${formatCurrency(product.from_price ?? product.price)}
                                    </span>
                                    <span class="rounded-full bg-[#f3f6f8] px-3 py-1 text-xs font-semibold text-[var(--muted)]">Stock: ${product.quantity}</span>
                                </div>
                                <div class="h-px bg-[var(--line)]"></div>
                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <span class="text-xs font-bold uppercase tracking-[0.25em] text-[var(--muted)] group-hover:text-[var(--primary)]">View Details</span>
                                    <button onclick="event.stopPropagation(); ${(product.pack_count ?? 1) > 1
                                        ? `window.location.href='/shop/products/${product.product_id}'`
                                        : `addToCart(${product.product_id})`}" class="rounded-xl bg-[var(--primary)] px-4 py-2 text-sm font-bold text-white transition hover:brightness-110">
                                        ${(product.pack_count ?? 1) > 1 ? 'Choose Size' : 'Add to Cart'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                `).join('')
                : '<div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 text-center text-[var(--muted)] shadow-sm">No featured products available yet.</div>';
            } catch (error) {
                document.getElementById('featuredGrid').innerHTML =
                    '<div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 text-center text-red-500 shadow-sm">Failed to load featured products.</div>';
            }
        }

        function renderProducts() {
            const search = document.getElementById('searchInput').value;
            const brand = document.getElementById('brandFilter').value;
            const type = document.getElementById('typeFilter').value;

            const filtered = products.filter((product) => {
                /* Every word has to land somewhere, and a word matches with
                   its punctuation removed as well as with it -- so "5W-30"
                   finds a grade stored as 5W30, and "motor oil" finds the
                   motor engine oils. A plain substring found neither. */
                const matchesSearch = searchMatches(search, [
                    product.product_name,
                    product.brand,
                    product.oil_type,
                    product.viscosity_grade,
                    product.unit,
                ]);

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
                    const multiPack = (product.pack_count ?? 1) > 1;
                    const showUnavailableDivider = unavailable && index === availableProducts.length;

                    return `
                        ${showUnavailableDivider ? `
                            <div class="col-span-full mt-2 rounded-[1.5rem] border border-[var(--line)] bg-slate-100/90 px-6 py-4 text-sm font-semibold uppercase tracking-[0.22em] text-slate-600 shadow-sm">
                                Unavailable Products
                            </div>
                        ` : ''}
                        <article onclick="window.location.href='/shop/products/${product.product_id}'" class="group flex flex-col cursor-pointer overflow-hidden rounded-[1.5rem] border border-[var(--line)] ${unavailable ? 'bg-slate-100 opacity-80' : 'bg-white'} shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            ${product.image_url
                                ? `<div class="flex-shrink-0"><img src="${product.image_url}" alt="${escapeHtml(product.product_name)}" class="h-60 w-full bg-white object-contain p-3 ${unavailable ? 'grayscale' : ''}"></div>`
                                : `<div class="flex-shrink-0 flex h-60 items-center justify-center bg-[linear-gradient(135deg,_rgba(20,138,103,0.12),_rgba(255,255,255,0.95)_45%,_rgba(217,177,74,0.18))] ${unavailable ? 'grayscale' : ''}"><span class="rounded-full bg-white/90 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">No Image</span></div>`}
                            <div class="flex flex-1 flex-col p-5">
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <h3 class="mb-1 line-clamp-2 text-base font-black leading-snug text-[var(--ink)]">${escapeHtml(product.product_name)}</h3>
                                        <p class="truncate text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">${escapeHtml(product.brand)}</p>
                                    </div>
                                    <span class="flex-shrink-0 rounded-full bg-[var(--primary-soft)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.15em] text-[var(--primary)]">${escapeHtml(product.oil_type)}</span>
                                </div>
                                <p class="mb-3 truncate text-sm text-[var(--muted)]">${escapeHtml(product.viscosity_grade || 'Standard')}</p>

                                {{-- The pack sizes this oil comes in. Shown on
                                     the card so a customer can see at a glance
                                     that the 4L exists, and which sizes are
                                     currently out. --}}
                                <div class="mb-3 flex flex-wrap gap-1.5">
                                    ${(product.packs || []).map((pack) => `
                                        <span class="rounded-md border px-2 py-0.5 text-[11px] font-semibold ${pack.quantity > 0
                                            ? 'border-[var(--primary-soft)] bg-[var(--primary-soft)] text-[var(--primary)]'
                                            : 'border-[var(--line)] bg-transparent text-slate-400 line-through'}">${escapeHtml(packLabel(pack.unit))}</span>
                                    `).join('')}
                                </div>

                                <div class="mt-auto">
                                    <div class="mb-3 flex items-center justify-between">
                                        <span class="text-xl font-black text-[var(--primary)]">
                                            ${multiPack ? `<span class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--muted)]">from</span> ` : ''}${formatCurrency(product.from_price ?? product.price)}
                                        </span>
                                        <span class="rounded-full ${unavailable ? 'bg-slate-200 text-slate-600' : 'bg-[#f3f6f8] text-[var(--muted)]'} px-3 py-1 text-xs font-semibold">Stock: ${stock}</span>
                                    </div>
                                    <div class="h-px bg-[var(--line)]"></div>
                                    <div class="mt-3 flex items-center justify-between gap-3">
                                        <span class="text-xs font-bold uppercase tracking-[0.25em] text-[var(--muted)] group-hover:text-[var(--primary)]">View Details</span>
                                        {{-- With more than one size there is no
                                             right basket to drop it in, so the
                                             card sends the customer to choose
                                             rather than guessing for them. --}}
                                        <button onclick="event.stopPropagation(); ${multiPack
                                            ? `window.location.href='/shop/products/${product.product_id}'`
                                            : `addToCart(${product.product_id})`}" class="rounded-xl ${unavailable ? 'cursor-not-allowed bg-slate-400' : 'bg-[var(--primary)]'} px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 ${!isCustomer && !multiPack ? 'opacity-60 cursor-not-allowed' : ''}" ${unavailable ? 'disabled' : ''}>
                                            ${multiPack ? 'Choose Size' : 'Add to Cart'}
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
                const response = await fetch('/shop-api/products');
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
                    '<div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 text-center text-red-500 shadow-sm">Failed to load featured products.</div>';
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

        document.getElementById('searchInput').addEventListener('input', renderProducts);
        document.getElementById('brandFilter').addEventListener('change', renderProducts);
        document.getElementById('typeFilter').addEventListener('change', renderProducts);

        loadProducts();
</script>
@endpush
