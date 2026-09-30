{{--
    The shell both legal pages sit in. They differ only in their text, so the
    markup lives here once and each page passes its own sections in.

    Expects: $eyebrow, $heading, $lede, $updated, $sections
    Each section: [
        'title' => string,
        'body'  => string[],
        'list'  => string[]?,
        'link'  => ['href' => string, 'label' => string]?,
    ]

    Section text is escaped, so a section that needs to point at another page
    passes 'link' rather than putting an anchor in its prose.
--}}
<main class="container mx-auto px-4 py-8 sm:px-6">

    <section class="mb-6 rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
        <div class="max-w-3xl">
            <div class="inline-flex rounded-full border border-[var(--primary-soft)] bg-[var(--primary-soft)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[var(--primary)]">{{ $eyebrow }}</div>
            <h1 class="mt-4 text-2xl font-black leading-tight text-[var(--ink)] sm:text-4xl">{{ $heading }}</h1>
            <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ $lede }}</p>
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Last updated {{ $updated }}</p>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-4">

        {{-- A short contents list, so a reader can go straight to the one
             section they came for instead of scrolling the whole page. --}}
        <nav class="lg:col-span-1">
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm lg:sticky lg:top-6">
                <h2 class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">On this page</h2>
                <ol class="mt-4 space-y-2 text-sm">
                    @foreach ($sections as $i => $section)
                        <li>
                            <a href="#section-{{ $i }}" class="flex gap-2 text-[var(--muted)] transition hover:text-[var(--primary)]">
                                <span class="font-semibold text-[var(--primary)]">{{ $i + 1 }}.</span>
                                <span>{{ $section['title'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        </nav>

        <div class="lg:col-span-3">
            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
                @foreach ($sections as $i => $section)
                    <div id="section-{{ $i }}" class="scroll-mt-6 @unless ($loop->first) mt-9 border-t border-[var(--line)] pt-9 @endunless">
                        <h2 class="text-lg font-bold text-[var(--ink)]">
                            <span class="text-[var(--primary)]">{{ $i + 1 }}.</span> {{ $section['title'] }}
                        </h2>

                        @foreach ($section['body'] as $paragraph)
                            <p class="mt-3 text-sm leading-7 text-[var(--muted)]">{{ $paragraph }}</p>
                        @endforeach

                        @if (!empty($section['list']))
                            <ul class="mt-4 space-y-3 text-sm">
                                @foreach ($section['list'] as $item)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-[0.55rem] block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[var(--primary)]"></span>
                                        <span class="leading-7 text-[var(--muted)]">{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (!empty($section['link']))
                            <a href="{{ $section['link']['href'] }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-[var(--primary)] hover:underline">
                                {{ $section['link']['label'] }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        @endif
                    </div>
                @endforeach
            </section>

            {{-- Every legal page ends with someone to ask, so the contact
                 details are part of the shell rather than each page's text. --}}
            <section class="mt-6 rounded-[1.5rem] border border-[var(--line)] bg-white px-5 py-6 shadow-sm sm:px-8 sm:py-7">
                <h2 class="text-lg font-bold text-[var(--ink)]">Questions about this page</h2>
                <p class="mt-2 text-sm leading-7 text-[var(--muted)]">{{ $site['name'] }}</p>
                <ul class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                    <li>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Email</div>
                        <div class="mt-1 break-all text-[var(--ink)]">{{ $site['email'] }}</div>
                    </li>
                    <li>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Phone</div>
                        <div class="mt-1 text-[var(--ink)]">{{ $site['phone'] ?: $site['email'] }}</div>
                    </li>
                    <li>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Address</div>
                        <div class="mt-1 text-[var(--ink)]">{{ $site['address'] }}</div>
                    </li>
                </ul>
            </section>
        </div>
    </div>

</main>
