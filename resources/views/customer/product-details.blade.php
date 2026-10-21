@extends('layouts.customer')

@section('title', $product->product_name . ' - RANEY LUBRICANTS TRADING')

@section('content')

    <main class="container mx-auto px-4 py-8 sm:px-6">
        <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

        <section class="mb-6 rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Product Overview</div>
                    <h1 class="mt-4 text-2xl font-black leading-tight text-[var(--ink)] sm:text-4xl">{{ $product->product_name }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--muted)]">Review product type, viscosity, packaging, stock level, and description before adding this item to your cart.</p>
                </div>
                <a href="/shop" class="rounded-xl border border-[var(--line)] bg-white/70 px-5 py-3 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Back to Catalog</a>
            </div>
        </section>

        {{-- Stacked on a phone the source order would put the whole
             write-up between the picture and the price, so the buy panel is
             ordered above it. At lg the explicit row and column placement
             takes over and the order classes stop mattering.

             items-start stops the buy panel stretching to match the column
             beside it. Without it the grid makes both cells the height of the
             tallest, so once the description moved under the image the panel
             grew a long tail of white space under Add to Cart. --}}
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
            <div class="order-1 lg:col-start-1 lg:row-start-1">
                <div class="overflow-hidden rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
                    @if ($product->image_path)
                        <img id="productImage" src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}" class="h-[420px] w-full bg-white object-contain p-6">
                    @else
                        <div class="flex h-[420px] items-center justify-center bg-[linear-gradient(135deg,_rgba(20,138,103,0.12),_rgba(255,255,255,0.95)_45%,_rgba(217,177,74,0.18))]">
                            <span class="rounded-full bg-white/90 px-5 py-3 text-xs font-semibold uppercase tracking-[0.25em] text-[var(--muted)]">No Product Image</span>
                        </div>
                    @endif
                </div>

                {{-- The second picture is the pack-size group shot. It is
                     unreadable at card size, which is why the card shows a
                     single bottle, but it is worth seeing once someone is
                     looking properly. --}}
                @if ($product->image_path && $product->image_path_2)
                    @php
                        $shots = [
                            ['path' => $product->image_path,   'label' => 'Product'],
                            ['path' => $product->image_path_2, 'label' => 'Pack sizes'],
                        ];
                    @endphp
                    <div class="mt-3 flex gap-3">
                        @foreach ($shots as $index => $shot)
                            <button type="button"
                                    onclick="showShot('{{ asset('storage/' . $shot['path']) }}', this)"
                                    class="product-shot overflow-hidden rounded-xl border-2 bg-white p-1 transition {{ $index === 0 ? 'border-[var(--primary)]' : 'border-[var(--line)] hover:border-[var(--primary)]' }}"
                                    title="{{ $shot['label'] }}">
                                <img src="{{ asset('storage/' . $shot['path']) }}" alt="{{ $shot['label'] }}" class="h-16 w-20 object-contain">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Under the picture rather than beside the buy controls. The
                 right column is for deciding a pack and a quantity; this is
                 the reading, and it is far too long to sit between the price
                 and the Add to Cart button. --}}
            <div class="order-3 lg:col-start-1 lg:row-start-2">
                @php $spec = $product->specifications ?? []; @endphp

                <section class="mt-6 rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm sm:p-7">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Description</h2>
                    <p class="mt-3 text-sm leading-7 text-[var(--ink)]">{{ $product->description ?: 'No detailed product description is available yet for this item.' }}</p>

                    @if (!empty($spec['overview']))
                        <p class="mt-4 text-sm leading-7 text-[var(--muted)]">{{ $spec['overview'] }}</p>
                    @endif

                    @if (!empty($spec['grades']))
                        <div class="mt-6 border-t border-[var(--line)] pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Approved to</h3>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($spec['grades'] as $grade)
                                    <span class="rounded-lg border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-3 py-1.5 text-xs font-bold text-[var(--primary)]">{{ $grade }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (!empty($spec['applications']))
                        <div class="mt-6 border-t border-[var(--line)] pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Where it is used</h3>
                            @foreach (preg_split('/\R+/', trim($spec['applications'])) as $paragraph)
                                @continue(trim($paragraph) === '')
                                <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ trim($paragraph) }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($spec['benefits']))
                        <div class="mt-6 border-t border-[var(--line)] pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Features and benefits</h3>
                            <ul class="mt-3 space-y-2.5 text-sm">
                                @foreach ($spec['benefits'] as $benefit)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-[0.6rem] block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[var(--primary)]"></span>
                                        <span class="leading-7 text-[var(--muted)]">{{ $benefit }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (!empty($spec['standards']))
                        <div class="mt-6 border-t border-[var(--line)] pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Meets or exceeds</h3>
                            @foreach (preg_split('/\R+/', trim($spec['standards'])) as $paragraph)
                                @continue(trim($paragraph) === '')
                                <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ trim($paragraph) }}</p>
                            @endforeach
                            <p class="mt-4 text-xs leading-6 text-[var(--muted)]">
                                Approvals are the manufacturer's own. Check your handbook for the grade and standard your engine requires.
                            </p>
                        </div>
                    @endif

                    @if (!empty($spec['properties']))
                        <div class="mt-6 border-t border-[var(--line)] pt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Typical properties</h3>
                            <div class="mt-3 overflow-hidden rounded-xl border border-[var(--line)]">
                                <table class="min-w-full text-sm">
                                    <tbody>
                                        @foreach ($spec['properties'] as $property => $value)
                                            <tr class="border-b border-[var(--line)] last:border-0 {{ $loop->even ? 'bg-[rgba(246,248,251,0.7)]' : '' }}">
                                                <td class="px-4 py-2.5 text-[var(--muted)]">{{ $property }}</td>
                                                <td class="px-4 py-2.5 text-right font-semibold text-[var(--ink)]">{{ $value }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-3 text-xs leading-6 text-[var(--muted)]">Typical values, not a specification. They vary within normal manufacturing tolerance.</p>
                        </div>
                    @endif

                    @if (!empty($spec['source']))
                        <p class="mt-6 border-t border-[var(--line)] pt-4 text-xs leading-6 text-[var(--muted)]">
                            Product information published by the manufacturer.
                            <a href="{{ $spec['source'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[var(--primary)] hover:underline">See their page</a>
                        </p>
                    @endif
                </section>
            </div>

            <div class="order-2 rounded-[1.5rem] border border-[var(--line)] bg-white p-5 shadow-sm sm:p-8 lg:col-start-2 lg:row-start-1">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full bg-[var(--primary-soft)] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[var(--primary)]">{{ $product->oil_type }}</span>
                    <span class="rounded-full bg-[var(--accent-soft)] px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-[#9d7b20]">{{ $product->brand }}</span>
                </div>

                <div class="mt-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Price</div>
                    <div id="packPrice" class="mt-2 text-4xl font-black text-[var(--primary)]">PHP {{ number_format((float) $product->price, 2) }}</div>
                </div>

                {{-- The pack sizes this oil is sold in. One row per size in the
                     database, because price and stock genuinely differ, but a
                     single choice here rather than three separate listings. --}}
                @if ($packs->count() > 1)
                    <div class="mt-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Available packs</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($packs as $pack)
                                @php
                                    $packStock = optional($pack->inventory)->quantity ?? 0;
                                    $isCurrent = $pack->product_id === $product->product_id;
                                @endphp
                                <button type="button"
                                        onclick="selectPack({{ $pack->product_id }})"
                                        data-pack="{{ $pack->product_id }}"
                                        @disabled($packStock < 1)
                                        {{-- Out of stock is tested before
                                             current, or the pack the page
                                             happened to open on would render
                                             green and look available even
                                             with nothing behind it. --}}
                                        class="pack-option rounded-xl border px-4 py-2.5 text-sm font-bold transition
                                            @if ($packStock < 1) cursor-not-allowed border-[var(--line)] bg-slate-100 text-slate-400 line-through
                                            @elseif ($isCurrent) border-[var(--primary)] bg-[var(--primary)] text-white
                                            @else border-[var(--line)] bg-white text-[var(--ink)] hover:border-[var(--primary)] hover:text-[var(--primary)] @endif">
                                    {{ $pack->unit }}
                                </button>
                            @endforeach
                        </div>
                        @if ($packs->contains(fn ($p) => (optional($p->inventory)->quantity ?? 0) < 1))
                            <p class="mt-2 text-xs text-[var(--muted)]">A crossed-out size is one we are out of right now.</p>
                        @endif
                    </div>
                @endif

                {{-- Two facts, not four. "Unit" repeated whichever pack the
                     buttons above already show as selected, and "Availability"
                     was Stock said again in words -- a number and then a
                     sentence about that number. --}}
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-[var(--line)] bg-white/80 p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Viscosity</div>
                        <div class="mt-2 text-lg font-bold text-[var(--ink)]">{{ $product->viscosity_grade ?: 'Standard' }}</div>
                    </div>
                    <div class="rounded-2xl border border-[var(--line)] bg-white/80 p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Stock</div>
                        <div id="packStock" class="mt-2 text-lg font-bold {{ (optional($product->inventory)->quantity ?? 0) > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ (optional($product->inventory)->quantity ?? 0) > 0 ? (optional($product->inventory)->quantity ?? 0) . ' available' : 'Out of stock' }}</div>
                    </div>
                </div>

                {{-- Singly or by the box. A box is not another product: it is
                     a count of the same bottles, so what reaches the cart is
                     still a number of bottles and every stock and money rule
                     downstream carries on unchanged. --}}
                <div id="buyModeRow" class="mt-6 hidden">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">How would you like to buy?</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" id="buyModeSingle" onclick="setBuyMode('single')"
                                class="rounded-xl border border-[var(--primary)] bg-[var(--primary)] px-4 py-2.5 text-sm font-bold text-white transition">
                            Single
                        </button>
                        <button type="button" id="buyModeBox" onclick="setBuyMode('box')"
                                class="rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-bold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">
                            Box of {{ (int) config('catalogue.bulk_box.quantity') }}
                        </button>
                    </div>
                    <p id="buyModeNote" class="mt-2 text-xs leading-6 text-[var(--muted)]"></p>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    @php $soldOut = (optional($product->inventory)->quantity ?? 0) < 1; @endphp
                    <div class="rounded-2xl border border-[var(--line)] bg-white/80 px-4 py-3">
                        <label for="quantity" id="quantityLabel" class="block text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Quantity</label>

                        {{-- The steppers exist because this is a text field,
                             which has no spinners of its own. They cannot
                             produce a bad value: each one clamps to the
                             stock ceiling and to one, and disables itself at
                             the end it would push past. --}}
                        <div class="mt-2 flex items-stretch overflow-hidden rounded-xl border border-[var(--line)] {{ $soldOut ? 'bg-slate-100' : 'bg-white' }}">
                            <button type="button" id="quantityDown" onclick="stepQuantity(-1)" aria-label="One fewer"
                                    class="flex w-9 items-center justify-center text-lg font-bold text-[var(--muted)] transition hover:bg-[var(--primary-soft)] hover:text-[var(--primary)] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-[var(--muted)]"
                                    @disabled($soldOut)>&minus;</button>

                            <input id="quantity" type="text" inputmode="numeric" autocomplete="off" pattern="[0-9]*" maxlength="3" data-max="{{ max(optional($product->inventory)->quantity ?? 1, 1) }}" value="1"
                                   class="w-12 border-x border-[var(--line)] px-2 py-2.5 text-center text-sm outline-none transition focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary-soft)] {{ $soldOut ? 'bg-slate-100 text-slate-400' : 'bg-white' }}" @disabled($soldOut)>

                            <button type="button" id="quantityUp" onclick="stepQuantity(1)" aria-label="One more"
                                    class="flex w-9 items-center justify-center text-lg font-bold text-[var(--muted)] transition hover:bg-[var(--primary-soft)] hover:text-[var(--primary)] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-[var(--muted)]"
                                    @disabled($soldOut)>+</button>
                        </div>
                    </div>

                    @auth
                        @if (auth()->user()->isCustomer())
                            <button onclick="addToCart(selectedPackId, this)" class="self-end rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110 {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'cursor-not-allowed opacity-60' : '' }}" {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'disabled' : '' }}>Add to Cart</button>
                        @else
                            <a href="/shop/login" class="self-end rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110">Login to Order</a>
                        @endif
                    @else
                        <a href="/shop/login" class="self-end rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110">Login to Order</a>
                    @endauth
                </div>
            </div>
        </div>

        {{-- What people who bought it thought. Open to everybody, signed in
             or not: a review a customer cannot read until they have an
             account cannot help them decide, which is all it is for. --}}
        <section id="reviews" class="mx-auto mt-10 max-w-6xl px-4 sm:px-6">
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black tracking-tight text-[var(--ink)]">Customer reviews</h2>
                        <p id="reviewSummary" class="mt-1 text-sm text-[var(--muted)]">Loading&hellip;</p>
                    </div>
                    <div id="reviewAverage" class="text-right"></div>
                </div>

                <div id="reviewForm" class="mt-6 hidden rounded-2xl border border-[var(--primary-soft)] bg-[var(--primary-soft)]/40 p-5">
                    <h3 class="text-sm font-bold text-[var(--ink)]">You bought this. How was it?</h3>
                    <form onsubmit="submitReview(event)" class="mt-3">
                        <div class="flex items-center gap-1" id="ratingPicker"></div>
                        <p class="mt-1 text-xs text-[var(--muted)]">Choose a rating, then add words if you want to.</p>

                        <textarea id="reviewBody" rows="3" maxlength="1000"
                                  placeholder="How did it run? Anything another customer should know?"
                                  class="mt-3 w-full rounded-xl border border-[var(--line)] bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--primary)]"></textarea>

                        <p id="reviewError" class="mt-1 hidden text-xs text-red-600"></p>

                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Post review</button>
                        </div>
                    </form>
                </div>

                <div id="reviewList" class="mt-6 divide-y divide-[var(--line)]"></div>
                <div id="reviewPagination"></div>
            </div>
        </section>
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

        @php
            // Built here rather than inside @json, whose argument parser does
            // not survive a multi-line array literal -- it compiled, then the
            // generated PHP would not parse.
            $packPayload = $packs->mapWithKeys(fn ($p) => [$p->product_id => [
                'unit'     => $p->unit,
                'price'    => (float) $p->price,
                'quantity' => optional($p->inventory)->quantity ?? 0,
            ]]);
        @endphp

        // Every pack of this oil, so choosing one is a page the customer
        // already has rather than another request.
        const PACKS = @json($packPayload);

        let selectedPackId = {{ $product->product_id }};

        const BOX_QUANTITY = {{ (int) config('catalogue.bulk_box.quantity') }};
        const BOX_MAX_LITRES = {{ (int) config('catalogue.bulk_box.max_litres_per_unit') }};

        let buyMode = 'single';

        function litresOf(unit) {
            const match = String(unit || '').match(/^(\d+)/);
            return match ? Number(match[1]) : 1;
        }

        // A box is offered on bottles, not on drums, and only when there are
        // actually enough on the shelf to fill one.
        function boxAvailable(pack) {
            return !!pack
                && litresOf(pack.unit) <= BOX_MAX_LITRES
                && pack.quantity >= BOX_QUANTITY;
        }

        function money(value) {
            return 'PHP ' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function setBuyMode(mode) {
            const pack = PACKS[selectedPackId];
            buyMode = (mode === 'box' && boxAvailable(pack)) ? 'box' : 'single';
            refreshBuyMode();
        }

        function refreshBuyMode() {
            const pack = PACKS[selectedPackId];
            if (!pack) return;

            const canBox = boxAvailable(pack);
            const row = document.getElementById('buyModeRow');
            row.classList.toggle('hidden', !canBox);

            if (!canBox) buyMode = 'single';

            const single = document.getElementById('buyModeSingle');
            const box = document.getElementById('buyModeBox');
            const chosen = 'rounded-xl border border-[var(--primary)] bg-[var(--primary)] px-4 py-2.5 text-sm font-bold text-white transition';
            const other = 'rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-bold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]';
            single.className = buyMode === 'single' ? chosen : other;
            box.className = buyMode === 'box' ? chosen : other;

            // In box mode the quantity box counts boxes, so its ceiling is
            // how many whole boxes the shelf can fill.
            const input = document.getElementById('quantity');
            const maxBoxes = Math.floor(pack.quantity / BOX_QUANTITY);

            if (buyMode === 'box') {
                document.getElementById('quantityLabel').textContent = 'Boxes';
                input.dataset.max = Math.max(maxBoxes, 1);
                if (Number(input.value) > maxBoxes) input.value = Math.max(maxBoxes, 1);
                document.getElementById('packPrice').textContent = money(pack.price * BOX_QUANTITY);
                document.getElementById('buyModeNote').textContent =
                    `One box is ${BOX_QUANTITY} x ${pack.unit} at ${money(pack.price * BOX_QUANTITY)}. `
                    + `${maxBoxes} box${maxBoxes === 1 ? '' : 'es'} available.`;
            } else {
                document.getElementById('quantityLabel').textContent = 'Quantity';
                input.dataset.max = Math.max(pack.quantity, 1);
                if (Number(input.value) > pack.quantity) input.value = Math.max(pack.quantity, 1);
                document.getElementById('packPrice').textContent = money(pack.price);
                document.getElementById('buyModeNote').textContent =
                    canBox ? `Buying ${BOX_QUANTITY} or more? A box is ${BOX_QUANTITY} x ${pack.unit}.` : '';
            }

            refreshSteppers();
        }

        function selectPack(productId) {
            const pack = PACKS[productId];
            if (!pack || pack.quantity < 1) return;

            selectedPackId = productId;

            document.getElementById('packPrice').textContent =
                'PHP ' + pack.price.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const stock = document.getElementById('packStock');
            stock.textContent = pack.quantity > 0 ? pack.quantity + ' available' : 'Out of stock';
            stock.className = 'mt-2 text-lg font-bold ' + (pack.quantity > 0 ? 'text-emerald-700' : 'text-red-700');

            // The quantity box is bounded by the pack now selected, not the
            // one the page happened to open on.
            const quantityInput = document.getElementById('quantity');
            if (quantityInput) {
                quantityInput.dataset.max = Math.max(pack.quantity, 1);
                if (Number(quantityInput.value) > pack.quantity) quantityInput.value = Math.max(pack.quantity, 1);
            }

            document.querySelectorAll('.pack-option').forEach((button) => {
                const id = Number(button.dataset.pack);
                const isChosen = id === productId;
                const isOut = (PACKS[id]?.quantity ?? 0) < 1;

                // Same order as the server-rendered markup: out of stock
                // beats chosen, so the two cannot disagree.
                button.className = 'pack-option rounded-xl border px-4 py-2.5 text-sm font-bold transition ' + (
                    isOut ? 'cursor-not-allowed border-[var(--line)] bg-slate-100 text-slate-400 line-through'
                    : isChosen ? 'border-[var(--primary)] bg-[var(--primary)] text-white'
                    : 'border-[var(--line)] bg-white text-[var(--ink)] hover:border-[var(--primary)] hover:text-[var(--primary)]'
                );
            });

            // A 5L may box where the drum beside it does not, so the option
            // is re-read for whichever pack is now selected. This also puts
            // the price back, having just overwritten it above.
            refreshBuyMode();
        }

        /*
         * Only digits may enter the quantity box.
         *
         * This is a text field, not a number field, on purpose. A number
         * input reports value as "" for anything it considers invalid, so "a"
         * could be typed, sit there looking typed, and read back as empty --
         * and repairing that on blur is what made letters order one bottle:
         * the box said "a", the value said "", blur made it 1, and Add to
         * Cart was perfectly happy. A text field's value is always exactly
         * what is on screen, so refusing a character actually refuses it.
         *
         * The stock ceiling lives in data-max rather than the max attribute,
         * which means nothing on a text input.
         */
        (function guardQuantity() {
            const input = document.getElementById('quantity');
            if (!input) return;

            const strip = (text) => String(text ?? '').replace(/[^0-9]/g, '');

            // One character at a time: refuse anything that is not a digit.
            input.addEventListener('keypress', (event) => {
                if (event.ctrlKey || event.metaKey || event.altKey) return;

                if (!/^[0-9]$/.test(event.key)) {
                    event.preventDefault();
                }
            });

            // Pasting and dropping arrive as whole strings. Keeping the
            // digits is kinder than refusing the lot, and a paste of "abc"
            // then simply leaves the box as it was.
            const insertDigitsOnly = (event, text) => {
                const kept = strip(text);
                event.preventDefault();

                const start = input.selectionStart ?? input.value.length;
                const end = input.selectionEnd ?? input.value.length;
                const next = (input.value.slice(0, start) + kept + input.value.slice(end)).slice(0, 3);

                input.value = next;
                const caret = Math.min(start + kept.length, next.length);
                input.setSelectionRange(caret, caret);
            };

            input.addEventListener('paste', (event) => {
                insertDigitsOnly(event, (event.clipboardData || window.clipboardData).getData('text'));
            });

            input.addEventListener('drop', (event) => {
                insertDigitsOnly(event, event.dataTransfer.getData('text'));
            });

            // Last line: whatever route the text took -- IME, autofill, a
            // browser this code has not met -- the value is swept to digits.
            input.addEventListener('input', () => {
                const cleaned = strip(input.value);
                if (cleaned !== input.value) {
                    const caret = input.selectionStart;
                    input.value = cleaned;
                    if (caret !== null) {
                        const at = Math.max(0, Math.min(caret - 1, cleaned.length));
                        input.setSelectionRange(at, at);
                    }
                }
            });

            input.addEventListener('input', refreshSteppers);
        })();

        /** The ceiling for whatever pack and buying mode is selected. */
        function quantityCeiling() {
            return Number(document.getElementById('quantity').dataset.max) || 1;
        }

        /**
         * Moves the quantity by one, and cannot land anywhere invalid: the
         * result is clamped to between 1 and the stock ceiling, and a box
         * holding something unreadable is treated as 1 rather than as NaN.
         */
        function stepQuantity(by) {
            const input = document.getElementById('quantity');
            if (input.disabled) return;

            const current = parseInt(input.value, 10);
            const usable = Number.isInteger(current) && current >= 1;

            // From an empty or unreadable box the first press lands on 1,
            // rather than on 2 because it counted up from an imagined 1.
            const next = usable ? current + by : 1;

            input.value = Math.min(Math.max(next, 1), quantityCeiling());
            refreshSteppers();
        }

        /** Greys out whichever end the quantity is already sitting on. */
        function refreshSteppers() {
            const input = document.getElementById('quantity');
            const down = document.getElementById('quantityDown');
            const up = document.getElementById('quantityUp');
            if (!down || !up) return;

            const value = parseInt(input.value, 10);
            const ceiling = quantityCeiling();

            down.disabled = input.disabled || !Number.isInteger(value) || value <= 1;
            up.disabled = input.disabled || (Number.isInteger(value) && value >= ceiling);
        }

        async function addToCart(productId, button = null) {
            const quantityInput = document.getElementById('quantity');

            const entered = parseInt(quantityInput.value, 10);
            const ceiling = Number(quantityInput.dataset.max) || 1;

            // Checked rather than coerced. Math.max(Number("abc"), 1) is NaN,
            // which JSON turns into null, which the server used to read as a
            // quantity of one -- so typing letters silently ordered a bottle.
            if (!Number.isInteger(entered) || entered < 1) {
                quantityInput.focus();
                showMessage('Enter how many you want, as a whole number of at least 1.', 'error');
                return;
            }

            if (entered > ceiling) {
                quantityInput.value = ceiling;
                showMessage(buyMode === 'box'
                    ? `We only have enough for ${ceiling} box${ceiling === 1 ? '' : 'es'} right now.`
                    : `Only ${ceiling} left in stock.`, 'error');
                return;
            }

            // The cart counts bottles, always. A box is turned into the
            // bottles it holds here, so the stock check, the order and the
            // receipt never need to know a box was involved.
            const units = buyMode === 'box' ? entered * BOX_QUANTITY : entered;

            /* Busy only from here on. The two checks above never reach the
               server, and a button that flickered while refusing a typo would
               say something happened when nothing did. */
            await withBusy(button, async () => {
                const response = await fetch('/shop-api/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: units
                    })
                });

                const data = await response.json();

                setCartCount(data.cart_count);

                const note = response.ok && buyMode === 'box'
                    ? `${entered} box${entered === 1 ? '' : 'es'} added - ${units} x ${PACKS[productId]?.unit ?? 'units'}.`
                    : (data.message || 'Cart updated.');

                showMessage(note, response.ok ? 'success' : 'error');
            }, 'Adding');
        }

        function showShot(url, button) {
            const image = document.getElementById('productImage');
            if (image) image.src = url;

            document.querySelectorAll('.product-shot').forEach((el) => {
                el.className = 'product-shot overflow-hidden rounded-xl border-2 bg-white p-1 transition '
                    + (el === button ? 'border-[var(--primary)]' : 'border-[var(--line)] hover:border-[var(--primary)]');
            });
        }

        // The page opens on a pack, so the box option has to be worked out
        // for it before anyone touches the size buttons.
        refreshBuyMode();
        refreshSteppers();

        /* ---------------------------------------------------------- reviews
           The reviews belong to the product line, not the pack size: the oil
           is the same whether it came in a litre or a drum, so a review of
           the 1L is a review of the 4L. The id is the one the page opened on. */
        const REVIEW_PRODUCT_ID = {{ $product->product_id }};
        let chosenRating = 0;

        function reviewStars(rating) {
            let out = '';
            for (let i = 1; i <= 5; i++) {
                out += `<span class="${i <= rating ? 'text-amber-500' : 'text-[var(--line)]'}">&#9733;</span>`;
            }
            return `<span class="leading-none" title="${rating} out of 5">${out}</span>`;
        }

        function drawRatingPicker() {
            document.getElementById('ratingPicker').innerHTML = [1, 2, 3, 4, 5].map((n) => `
                <button type="button" data-n="${n}" onclick="pickRating(this.dataset.n)"
                        aria-label="${n} star${n === 1 ? '' : 's'}"
                        class="text-2xl leading-none transition ${n <= chosenRating ? 'text-amber-500' : 'text-[var(--line)] hover:text-amber-300'}">&#9733;</button>`).join('');
        }

        function pickRating(n) {
            chosenRating = Number(n);
            drawRatingPicker();
        }

        async function loadReviews(page = 1) {
            const response = await fetch(`/shop-api/products/${REVIEW_PRODUCT_ID}/reviews?page=${page}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;

            const { data, meta, summary } = await response.json();

            document.getElementById('reviewSummary').textContent = summary.count
                ? `${summary.count} ${summary.count === 1 ? 'review' : 'reviews'} from customers who bought it`
                : 'No reviews yet. Customers can review an oil once their order arrives.';

            document.getElementById('reviewAverage').innerHTML = summary.average === null ? '' : `
                <div class="text-3xl font-black tracking-tight text-[var(--ink)]">${summary.average}</div>
                <div class="text-lg">${reviewStars(Math.round(summary.average))}</div>`;

            // Shown only to somebody who bought it and has not reviewed it.
            document.getElementById('reviewForm').classList.toggle('hidden', !summary.can_review);
            if (summary.can_review) drawRatingPicker();

            document.getElementById('reviewList').innerHTML = data.length
                ? data.map((r) => `
                    <div class="py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-base">${reviewStars(r.rating)}</span>
                            <span class="text-sm font-semibold text-[var(--ink)]">${escapeHtml(r.author)}</span>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Verified purchase</span>
                        </div>
                        ${r.body ? `<p class="mt-1.5 text-sm leading-6 text-[var(--ink)]">${escapeHtml(r.body)}</p>` : ''}
                        <div class="mt-1 text-xs text-[var(--muted)]">${escapeHtml(r.written || '')}</div>
                    </div>`).join('')
                : '';

            renderPagination('reviewPagination', meta, loadReviews);
        }

        async function submitReview(event) {
            event.preventDefault();
            const box = document.getElementById('reviewError');
            box.classList.add('hidden');

            if (!chosenRating) {
                box.textContent = 'Choose a rating from one to five stars.';
                box.classList.remove('hidden');
                return;
            }

            await withBusy(busyButtonOf(event.target), async () => {
                const response = await fetch(`/shop-api/products/${REVIEW_PRODUCT_ID}/reviews`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body: JSON.stringify({ rating: chosenRating, body: document.getElementById('reviewBody').value }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    box.textContent = payload.errors ? Object.values(payload.errors).flat().join(' ') : (payload.message || 'Could not post it.');
                    box.classList.remove('hidden');
                    return;
                }

                document.getElementById('reviewBody').value = '';
                chosenRating = 0;
                showMessage(payload.message || 'Thank you.');
                await loadReviews();
            }, 'Posting');
        }

        loadReviews();
</script>
@endpush
