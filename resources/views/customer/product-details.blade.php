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
                    <div class="rounded-2xl border border-[var(--line)] bg-white/80 px-4 py-3">
                        <label for="quantity" id="quantityLabel" class="block text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Quantity</label>
                        <input id="quantity" type="number" inputmode="numeric" min="1" max="{{ max(optional($product->inventory)->quantity ?? 1, 1) }}" step="1" value="1"
                               oninput="sanitiseQuantity()" onblur="normaliseQuantity()" class="mt-2 w-24 rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)] {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'bg-slate-100 text-slate-400' : 'bg-white' }}" {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'disabled' : '' }}>
                    </div>

                    @auth
                        @if (auth()->user()->isCustomer())
                            <button onclick="addToCart(selectedPackId)" class="self-end rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110 {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'cursor-not-allowed opacity-60' : '' }}" {{ (optional($product->inventory)->quantity ?? 0) < 1 ? 'disabled' : '' }}>Add to Cart</button>
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
                input.max = Math.max(maxBoxes, 1);
                if (Number(input.value) > maxBoxes) input.value = Math.max(maxBoxes, 1);
                document.getElementById('packPrice').textContent = money(pack.price * BOX_QUANTITY);
                document.getElementById('buyModeNote').textContent =
                    `One box is ${BOX_QUANTITY} x ${pack.unit} at ${money(pack.price * BOX_QUANTITY)}. `
                    + `${maxBoxes} box${maxBoxes === 1 ? '' : 'es'} available.`;
            } else {
                document.getElementById('quantityLabel').textContent = 'Quantity';
                input.max = Math.max(pack.quantity, 1);
                if (Number(input.value) > pack.quantity) input.value = pack.quantity;
                document.getElementById('packPrice').textContent = money(pack.price);
                document.getElementById('buyModeNote').textContent =
                    canBox ? `Buying ${BOX_QUANTITY} or more? A box is ${BOX_QUANTITY} x ${pack.unit}.` : '';
            }
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
                quantityInput.max = Math.max(pack.quantity, 1);
                if (Number(quantityInput.value) > pack.quantity) quantityInput.value = pack.quantity;
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

        // A number input still lets "e", "-" and "+" be typed, and can be
        // left empty. Strip anything that is not a digit as it is entered.
        function sanitiseQuantity() {
            const input = document.getElementById('quantity');
            const cleaned = input.value.replace(/[^0-9]/g, '').replace(/^0+(?=\d)/, '');

            if (cleaned !== input.value) {
                input.value = cleaned;
            }
        }

        // Leaving the box empty or on zero settles back to one, rather than
        // being carried to the server as a value it has to reject.
        function normaliseQuantity() {
            const input = document.getElementById('quantity');
            const ceiling = Number(input.max) || 1;
            let value = parseInt(input.value, 10);

            if (!Number.isFinite(value) || value < 1) {
                value = 1;
            }

            input.value = Math.min(value, ceiling);
        }

        async function addToCart(productId) {
            const quantityInput = document.getElementById('quantity');

            const entered = parseInt(quantityInput.value, 10);
            const ceiling = Number(quantityInput.max) || 1;

            // Checked rather than coerced. Math.max(Number("abc"), 1) is NaN,
            // which JSON turns into null, which the server used to read as a
            // quantity of one -- so typing letters silently ordered a bottle.
            if (!Number.isInteger(entered) || entered < 1) {
                normaliseQuantity();
                showMessage('Enter how many you want, as a whole number.', 'error');
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

            const note = response.ok && buyMode === 'box'
                ? `${entered} box${entered === 1 ? '' : 'es'} added - ${units} x ${PACKS[productId]?.unit ?? 'units'}.`
                : (data.message || 'Cart updated.');

            showMessage(note, response.ok ? 'success' : 'error');
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
</script>
@endpush
