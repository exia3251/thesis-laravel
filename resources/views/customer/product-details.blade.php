@extends('layouts.customer')

@section('title', '{{ $product->product_name }} - RANEY LUBRICANTS TRADING')

@section('content')

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

@endsection

@push('scripts')
<script>
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
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
                    </div>
                </div>
            `;
            box.className = 'fixed bottom-6 right-6 z-50 max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur';
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 2800);
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
@endpush
