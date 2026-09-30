@php
    /*
     * The counts are read here, once per page, rather than fetched by the
     * header after it has drawn. Adding something to the cart and seeing the
     * number stay at zero until the next navigation is what made the shop feel
     * disconnected from itself; a badge that arrives a moment late is the same
     * fault with an extra request.
     *
     * `badge` names the key JavaScript updates after an action, so the header
     * does not have to know which page it is on.
     */
    $customer = auth()->check() && auth()->user()->isCustomer() ? auth()->user() : null;

    $navLinks = [
        ['url' => '/cart',    'label' => 'Cart',    'badge' => 'cart',   'count' => $customer?->cartItemCount() ?? 0,          'icon' => 'M3 4h2l.4 2m0 0L7 14h10l2-8H5.4ZM7 14l-1 5h12M9 20a1 1 0 1 0 0 .01M17 20a1 1 0 1 0 0 .01'],
        ['url' => '/orders',  'label' => 'Orders',  'badge' => 'orders', 'count' => $customer?->ordersNeedingAttention() ?? 0, 'icon' => 'M9 5h10M9 9h10M9 13h10M5 5h.01M5 9h.01M5 13h.01M5 17h.01M9 17h10'],
        ['url' => '/profile', 'label' => 'Profile', 'badge' => null,     'count' => 0,                                         'icon' => 'M15 19a4 4 0 0 0-8 0m8 0h4m-4 0H5m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-[var(--line)] bg-[rgba(246,248,251,0.84)] backdrop-blur-xl">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex min-h-16 flex-col gap-4 py-4 lg:flex-row lg:items-center lg:justify-between">
            {{-- An uploaded logo stands in for the wording. Nothing is
                 required: with no logo the shop keeps the wordmark it has
                 always had, so the slot being empty is not a broken header. --}}
            <a href="/shop" class="group block shrink-0">
                @if ($site['logo_url'])
                    <img src="{{ $site['logo_url'] }}" alt="{{ $site['name'] }}" class="h-11 w-auto max-w-[190px] object-contain">
                @else
                    <div class="text-lg font-black tracking-tight sm:text-xl">
                        <span class="text-[var(--primary)]">RANEY</span>
                        <span class="text-[var(--accent)]"> LUBRICANTS</span>
                    </div>
                    <div class="text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
                @endif
            </a>

            @if ($showSearch ?? false)
                <div class="relative flex-1 lg:max-w-2xl">
                    <input id="searchInput" type="search" placeholder="Search products by name, brand, type, or viscosity..." class="w-full rounded-2xl border border-[var(--line)] bg-white/80 px-5 py-3.5 pl-12 text-sm shadow-sm outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--muted)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 items-center">
                @auth
                    @if(auth()->user()->isCustomer())
                        @foreach ($navLinks as $link)
                            <a href="{{ $link['url'] }}"
                               @class([
                                   'relative inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium transition',
                                   'border-[var(--primary)] bg-[var(--primary-soft)] text-[var(--ink)]' => request()->is(ltrim($link['url'], '/')),
                                   'border-[var(--line)] bg-white/70 text-[var(--muted)] hover:border-[var(--primary)] hover:text-[var(--ink)]' => ! request()->is(ltrim($link['url'], '/')),
                               ])>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/></svg>
                                <span>{{ $link['label'] }}</span>
                                @if ($link['badge'])
                                    {{-- Hidden at nought rather than absent, so
                                         the first item added has an element to
                                         land in and the button does not change
                                         width as it appears. --}}
                                    <span data-badge="{{ $link['badge'] }}"
                                          aria-hidden="{{ $link['count'] > 0 ? 'false' : 'true' }}"
                                          @class([
                                              'ml-0.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[var(--accent)] px-1.5 text-[11px] font-black leading-none text-white',
                                              'hidden' => $link['count'] < 1,
                                          ])>{{ $link['count'] > 99 ? '99+' : $link['count'] }}</span>
                                @endif
                            </a>
                        @endforeach
                        <button onclick="logout(this)" class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/70 px-4 py-2 text-sm font-medium text-[var(--muted)] transition hover:border-[var(--primary)] hover:text-[var(--ink)]">
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
