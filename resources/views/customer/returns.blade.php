@extends('layouts.customer')

@section('title', 'Returns & Refunds - RANEY LUBRICANTS TRADING')

@section('content')
    <main class="container mx-auto px-4 py-8 sm:px-6">

        <section class="mb-6 rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
            <div class="max-w-3xl">
                <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">Returns &amp; Refunds</div>
                <h1 class="mt-4 text-2xl font-black leading-tight text-[var(--ink)] sm:text-4xl">Returns are arranged with your Sales Executive.</h1>
                <p class="mt-3 text-sm leading-7 text-[var(--muted)]">
                    Return arrangements are coordinated directly between the customer and our Sales Executive.
                    Each transaction may have specific arrangements depending on the customer's needs and order terms.
                </p>
            </div>
        </section>

        {{-- Three named outcomes, because a customer arriving here wants to know
             which of them applies to their case before they call anyone. --}}
        <section class="mb-6">
            <h2 class="mb-4 text-lg font-bold text-[var(--ink)]">If a product needs to be returned</h2>

            @php
                $options = [
                    [
                        'title' => 'Product replacement',
                        'body'  => 'The returned product may be replaced with another product, subject to evaluation and availability.',
                        'icon'  => 'M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5',
                    ],
                    [
                        'title' => 'Product pull-out',
                        'body'  => 'The product may be pulled out or collected, depending on the agreed arrangement.',
                        'icon'  => 'M5 17h-2V6a1 1 0 0 1 1-1h11v12h-4M5 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0ZM15 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0ZM16 8h3l2 4v5h-2',
                    ],
                    [
                        'title' => 'Refunds',
                        'body'  => 'Refunds are generally not applicable to used products or completed bulk transactions.',
                        // A banknote rather than a currency glyph: the dollar
                        // sign that shipped here first is the wrong currency.
                        'icon'  => 'M2 6h20v12H2zM12 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM6 10v.01M18 14v.01',
                    ],
                ];
            @endphp

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($options as $option)
                    <div class="rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--primary-soft)] text-[var(--primary)]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $option['icon'] }}"/></svg>
                        </div>
                        <h3 class="mt-4 text-base font-bold text-[var(--ink)]">{{ $option['title'] }}</h3>
                        <p class="mt-2 text-sm leading-7 text-[var(--muted)]">{{ $option['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">

            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7 lg:col-span-2">
                <h2 class="text-lg font-bold text-[var(--ink)]">How it is decided</h2>
                <p class="mt-3 text-sm leading-7 text-[var(--muted)]">
                    Return and replacement arrangements depend on the circumstances and on the agreement made
                    with the Sales Executive. There is no fixed window or automatic entitlement here, because
                    the terms of a bulk order and the terms of a single drum are rarely the same.
                </p>

                {{-- Said plainly rather than left for a customer to discover: the
                     one outcome most people assume is available is the one that
                     usually is not. --}}
                <div class="mt-5 rounded-xl border-l-4 border-[var(--accent)] bg-[rgba(246,248,251,0.9)] px-5 py-4">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Worth knowing</div>
                    <p class="mt-2 text-sm leading-7 text-[var(--ink)]">
                        A refund is not the usual outcome. Used product and completed bulk transactions are
                        generally not refundable &mdash; replacement or pull-out is what is normally arranged.
                    </p>
                </div>

                <p class="mt-5 text-sm leading-7 text-[var(--muted)]">
                    For concerns regarding returns, please coordinate with your assigned Sales Executive for
                    proper assistance. Have your order number to hand; it is printed at the top of your order
                    summary and in the confirmation email you were sent.
                </p>

                {{-- /orders is customer-only, so this follows the header's test
                     rather than a bare @auth, which a signed-in staff member
                     would also pass. --}}
                @if (auth()->check() && auth()->user()->isCustomer())
                    <a href="/orders" class="mt-5 inline-flex items-center justify-center rounded-xl border border-[var(--line)] bg-white px-5 py-3 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Find my order number</a>
                @endif
            </section>

            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
                <h2 class="text-lg font-bold text-[var(--ink)]">Get in touch</h2>
                <p class="mt-2 text-sm leading-6 text-[var(--muted)]">Reach our Sales Executive during business hours.</p>

                <ul class="mt-5 space-y-4 text-sm">
                    <li class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <div>
                            <div class="font-semibold text-[var(--ink)]">Phone</div>
                            <div class="mt-0.5 text-[var(--muted)]">{{ config('business.phone') }}</div>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <div>
                            <div class="font-semibold text-[var(--ink)]">Email</div>
                            <div class="mt-0.5 break-all text-[var(--muted)]">{{ config('business.email') }}</div>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <div>
                            <div class="font-semibold text-[var(--ink)]">Hours</div>
                            <div class="mt-0.5 text-[var(--muted)]">{{ config('business.hours') }}</div>
                        </div>
                    </li>
                </ul>
            </section>
        </div>

    </main>
@endsection
