@extends('layouts.customer')

@section('title', 'About Us - RANEY LUBRICANTS TRADING')

@section('content')
    <main class="container mx-auto px-4 py-8 sm:px-6">

        <section class="mb-6 rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
            <div class="max-w-3xl">
                <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">About Us</div>
                <h1 class="mt-4 text-2xl font-black leading-tight text-[var(--ink)] sm:text-4xl">Imported lubricants, chosen for what is actually in them.</h1>
                <p class="mt-3 text-sm leading-7 text-[var(--muted)]">
                    RANEY LUBRICANTS TRADING carries Canroyal Lubricants, Solar Lubricants and Patrol Oil,
                    blended in the Middle East from 100% virgin base oil and premium additives.
                </p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- The company's own account of itself, in its own order of events. --}}
            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7 lg:col-span-2">
                <h2 class="text-lg font-bold text-[var(--ink)]">Our story</h2>

                <p class="mt-4 text-sm leading-7 text-[var(--muted)]">
                    Raney Lubricants Trading offers high-quality imported lubricant products. It started in
                    September 2019 distributing Canroyal Lubricants automotive engine oil, and in November 2019
                    became the Philippine distributor of Patrol Oil for motorcycle and engine oil. It later
                    expanded to cater to industrial and agricultural lubricant applications.
                </p>

                <p class="mt-4 text-sm leading-7 text-[var(--muted)]">
                    The range today is built on Canroyal Lubricants and Solar Lubricants, both blended in the
                    United Arab Emirates, alongside Patrol Oil. All are 100% virgin-based oil with premium
                    additives. Our commitment is to offer lubricant products of the highest quality conforming
                    to internationally recognised standards, at a competitive price, with uncompromising
                    customer service.
                </p>

                {{-- Dates the business gave for itself; kept as a sequence
                     because the order of the three is the point. --}}
                <ol class="mt-7 space-y-5 border-l-2 border-[var(--primary-soft)] pl-6">
                    <li class="relative">
                        <span class="absolute -left-[1.9rem] mt-1 block h-3 w-3 rounded-full bg-[var(--primary)] ring-4 ring-white"></span>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--primary)]">September 2019</div>
                        <div class="mt-1 text-sm font-semibold text-[var(--ink)]">Canroyal Lubricants</div>
                        <p class="mt-1 text-sm leading-6 text-[var(--muted)]">Began distributing automotive engine oil.</p>
                    </li>
                    <li class="relative">
                        <span class="absolute -left-[1.9rem] mt-1 block h-3 w-3 rounded-full bg-[var(--primary)] ring-4 ring-white"></span>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--primary)]">November 2019</div>
                        <div class="mt-1 text-sm font-semibold text-[var(--ink)]">Patrol Oil</div>
                        <p class="mt-1 text-sm leading-6 text-[var(--muted)]">Appointed Philippine distributor for motorcycle and engine oil.</p>
                    </li>
                    <li class="relative">
                        <span class="absolute -left-[1.9rem] mt-1 block h-3 w-3 rounded-full bg-[var(--primary)] ring-4 ring-white"></span>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--primary)]">Since then</div>
                        <div class="mt-1 text-sm font-semibold text-[var(--ink)]">Industrial and agricultural</div>
                        <p class="mt-1 text-sm leading-6 text-[var(--muted)]">Expanded beyond automotive into industrial and agricultural applications.</p>
                    </li>
                    <li class="relative">
                        <span class="absolute -left-[1.9rem] mt-1 block h-3 w-3 rounded-full bg-[var(--primary)] ring-4 ring-white"></span>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--primary)]">Today</div>
                        <div class="mt-1 text-sm font-semibold text-[var(--ink)]">Solar Lubricants</div>
                        <p class="mt-1 text-sm leading-6 text-[var(--muted)]">Added to the range, and now the widest part of it: motor and diesel oils, transmission fluids, coolants and motorcycle oil.</p>
                    </li>
                </ol>
            </section>

            {{-- The standards are the part a buyer checks, so they stand apart
                 from the prose rather than being buried in a paragraph. --}}
            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
                <h2 class="text-lg font-bold text-[var(--ink)]">Standards we hold to</h2>
                <p class="mt-2 text-sm leading-6 text-[var(--muted)]">Certifications carried by the products we distribute.</p>

                @php
                    $standards = [
                        ['API Certified', 'American Petroleum Institute certification.'],
                        ['ACEA standards', 'Conforms to the European sequences.'],
                        ['OEM approvals', 'Approved by original equipment manufacturers.'],
                        // "Both" was written when the range was two brands. Solar
                        // publish themselves as ISO-certified without naming the
                        // standard, so they are stated separately rather than
                        // folded into a 9001:2015 claim nobody made for them.
                        ['ISO 9001:2015', 'Held by the Canroyal and Patrol blending companies. Solar publish themselves as an ISO-certified manufacturer.'],
                        ['100% virgin base oil', 'No reclaimed stock, with premium additives.'],
                    ];
                @endphp

                <ul class="mt-5 space-y-4 text-sm">
                    @foreach ($standards as $standard)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <div>
                                <div class="font-semibold text-[var(--ink)]">{{ $standard[0] }}</div>
                                <p class="mt-0.5 leading-6 text-[var(--muted)]">{{ $standard[1] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- Vision and mission are formal statements, so they are reproduced as
             written rather than paraphrased into the surrounding copy. --}}
        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--primary-soft)] text-[var(--primary)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <h2 class="mt-4 text-lg font-bold text-[var(--ink)]">Vision</h2>
                <p class="mt-3 text-sm leading-7 text-[var(--muted)]">
                    To establish a successful wide-range oil lubricants trading in the Philippines that grants
                    only the foremost quality of products to our dealers.
                </p>
            </section>

            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--primary-soft)] text-[var(--primary)]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                </div>
                <h2 class="mt-4 text-lg font-bold text-[var(--ink)]">Mission</h2>
                <p class="mt-3 text-sm leading-7 text-[var(--muted)]">
                    To educate and provide information to our consumers about the proper and genuine oil
                    lubricants for better performance of vehicles, attaining the most of its potential while
                    maintaining the engines' durability at an affordable price.
                </p>
            </section>
        </div>

        <section class="mt-6 rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-[var(--ink)]">Not sure which oil your engine takes?</h2>
                    <p class="mt-1 text-sm leading-6 text-[var(--muted)]">Browse the catalogue, or ask our assistant for a recommendation by vehicle.</p>
                </div>
                <a href="/shop" class="inline-flex flex-shrink-0 items-center justify-center rounded-xl bg-[var(--primary)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">Browse products</a>
            </div>
        </section>

    </main>
@endsection
