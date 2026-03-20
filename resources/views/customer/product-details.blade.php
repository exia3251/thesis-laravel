<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $product->product_name }} - RANEY LUBRICANTS TRADING</title>
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

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="/shop" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5M5.25 9.5V20a1 1 0 0 0 1 1h11.5a1 1 0 0 0 1-1V9.5"/></svg><span>Shop</span></a>
                        <a href="/cart" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l.4 2m0 0L7 14h10l2-8H5.4ZM7 14l-1 5h12M9 20a1 1 0 1 0 0 .01M17 20a1 1 0 1 0 0 .01"/></svg><span>Cart</span></a>
                        <a href="/orders" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h10M9 9h10M9 13h10M5 5h.01M5 9h.01M5 13h.01M5 17h.01M9 17h10"/></svg><span>Orders</span></a>
                        <a href="/profile" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19a4 4 0 0 0-8 0m8 0h4m-4 0H5m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg><span>Profile</span></a>
                        @auth
                            @if(auth()->user()->isCustomer())
                                <button onclick="logout()" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"/></svg><span>Logout</span></button>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <main class="container mx-auto px-4 py-8 sm:px-6">
            <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

            <section class="mb-8 rounded-[2rem] border border-[var(--line)] bg-[linear-gradient(135deg,_rgba(255,255,255,0.84),_rgba(255,255,255,0.7))] px-6 py-7 shadow-xl backdrop-blur-xl sm:px-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Product Overview</div>
                        <h1 class="mt-5 text-3xl font-black leading-tight text-[var(--ink)] sm:text-4xl">{{ $product->product_name }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Review product type, viscosity, packaging, stock level, and description before adding this item to your cart.</p>
                    </div>
                    <a href="/shop" class="rounded-xl border border-[var(--line)] bg-white/70 px-5 py-3 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Back to Catalog</a>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="overflow-hidden rounded-[2rem] border border-[var(--line)] bg-[var(--card-solid)] shadow-xl">
                    @if ($product->image_path)
                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}" class="h-[420px] w-full object-cover">
                    @else
                        <div class="flex h-[420px] items-center justify-center bg-[linear-gradient(135deg,_rgba(20,138,103,0.12),_rgba(255,255,255,0.95)_45%,_rgba(217,177,74,0.18))]">
                            <span class="rounded-full bg-white/90 px-5 py-3 text-xs font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">No Product Image</span>
                        </div>
                    @endif
                </div>

                <div class="rounded-[2rem] border border-[var(--line)] bg-[var(--card)] p-6 shadow-xl backdrop-blur sm:p-8">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-[var(--primary-soft)] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[var(--primary)]">{{ $product->oil_type }}</span>
                        <span class="rounded-full bg-[var(--accent-soft)] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#9d7b20]">{{ $product->brand }}</span>
                    </div>

                    <div class="mt-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Price</div>
                        <div class="mt-2 text-4xl font-black text-[var(--primary)]">PHP {{ number_format((float) $product->price, 2) }}</div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-[var(--line)] bg-white/80 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Viscosity</div>
                            <div class="mt-2 text-lg font-bold text-[var(--ink)]">{{ $product->viscosity_grade ?: 'Standard' }}</div>
                        </div>
                        <div class="rounded-2xl border border-[var(--line)] bg-white/80 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Unit</div>
                            <div class="mt-2 text-lg font-bold text-[var(--ink)]">{{ $product->unit ?: '1 Liter' }}</div>
                        </div>
                        <div class="rounded-2xl border border-[var(--line)] bg-white/80 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Stock</div>
                            <div class="mt-2 text-lg font-bold {{ (optional($product->inventory)->quantity ?? 0) > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ optional($product->inventory)->quantity ?? 0 }} available</div>
                        </div>
                        <div class="rounded-2xl border border-[var(--line)] bg-white/80 p-4">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Availability</div>
                            <div class="mt-2 text-lg font-bold text-[var(--ink)]">{{ (optional($product->inventory)->quantity ?? 0) > 0 ? 'Available for order' : 'Currently unavailable' }}</div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-[1.5rem] border border-[var(--line)] bg-white/75 p-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Description</div>
                        <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ $product->description ?: 'No detailed product description is available yet for this item.' }}</p>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <div class="rounded-2xl border border-[var(--line)] bg-white/80 px-4 py-3">
                            <label for="quantity" class="block text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Quantity</label>
                            <input id="quantity" type="number" min="1" max="{{ max(optional($product->inventory)->quantity ?? 1, 1) }}" step="1" value="1" class="mt-2 w-24 rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)] {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'bg-slate-100 text-slate-400' : 'bg-white' }}" {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'disabled' : '' }}>
                        </div>

                        @auth
                            @if (auth()->user()->isCustomer())
                                <button onclick="addToCart({{ $product->product_id }})" class="self-end rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110 {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'cursor-not-allowed opacity-60' : '' }}" {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'disabled' : '' }}>Add to Cart</button>
                            @else
                                <a href="/shop/login" class="self-end rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110">Login to Order</a>
                            @endif
                        @else
                            <a href="/shop/login" class="self-end rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110">Login to Order</a>
                        @endauth
                    </div>
                </div>
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
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${text}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
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

        async function addToCart(productId) {
            const quantityInput = document.getElementById('quantity');

            const response = await fetch('/shop-api/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: Number(quantityInput.value || 1)
                })
            });

            const data = await response.json();
            showMessage(data.message || 'Cart updated.', response.ok ? 'success' : 'error');
        }
    </script>
</body>
</html>