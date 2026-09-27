@extends('layouts.bare')

@section('title', 'Start here - RANEY LUBRICANTS TRADING')

{{--
    The page a survey respondent lands on.

    They arrive from a link in a form, knowing nothing about the system, and
    the first thing they meet otherwise is a sign-in page that wants an account
    they do not have. So: the two logins to copy, and a list of things worth
    trying, written as tasks rather than as a feature tour.

    Local only, like the demo logins it prints. It is scaffolding for a survey,
    not part of the shop.
--}}

@section('content')
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)] px-4 py-10 sm:px-6">
        <div class="mx-auto w-full max-w-3xl">

            <div class="mb-8 text-center">
                <div class="text-3xl font-black tracking-tight sm:text-4xl">
                    <span class="text-[var(--primary)]">RANEY</span>
                    <span class="text-[var(--accent)]"> LUBRICANTS</span>
                </div>
                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.3em] text-[var(--muted)]">Trading</p>
                <h1 class="mt-6 text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">Thank you for testing this system</h1>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-[var(--muted)]">
                    This is a working online store and back office for a lubricants business. Nothing here is real:
                    no money moves, no order is ever dispatched, and you can break whatever you like.
                    Try a few of the things below, then answer the survey.
                </p>
            </div>

            @if (config('business.survey_form_url'))
                <div class="mb-8 text-center">
                    <a href="{{ config('business.survey_form_url') }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:brightness-110">
                        Open the survey form
                    </a>
                    <p class="mt-2 text-xs text-[var(--muted)]">Opens in a new tab &mdash; keep this one to come back to.</p>
                </div>
            @endif

            {{-- The two doors. Separate accounts, which is the single thing
                 people get wrong: a staff login on the shop page is refused. --}}
            <section class="grid gap-5 sm:grid-cols-2">
                <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-[0.22em] text-[var(--accent)]">As a customer</div>
                    <h2 class="mt-2 text-lg font-bold text-[var(--ink)]">The shop</h2>
                    <p class="mt-1 text-sm leading-6 text-[var(--muted)]">Browse, ask the assistant, place an order.</p>

                    <dl class="mt-4 space-y-1 rounded-xl bg-[var(--surface)] px-4 py-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-[var(--muted)]">Email</dt>
                            <dd class="select-all font-semibold text-[var(--ink)]">john@example.com</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[var(--muted)]">Password</dt>
                            <dd class="select-all font-semibold text-[var(--ink)]">customer123</dd>
                        </div>
                    </dl>

                    <a href="/shop/login" class="mt-4 flex w-full items-center justify-center rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-110">
                        Open the shop sign-in
                    </a>
                    <a href="/shop" class="mt-2 block text-center text-xs font-semibold text-[var(--primary)] hover:underline">
                        or look around without signing in
                    </a>
                </div>

                <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-[0.22em] text-[var(--accent)]">As the business</div>
                    <h2 class="mt-2 text-lg font-bold text-[var(--ink)]">The back office</h2>
                    <p class="mt-1 text-sm leading-6 text-[var(--muted)]">Orders, stock, figures, the assistant's answers.</p>

                    <dl class="mt-4 space-y-1 rounded-xl bg-[var(--surface)] px-4 py-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-[var(--muted)]">Email</dt>
                            <dd class="select-all font-semibold text-[var(--ink)]">admin@raney.test</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-[var(--muted)]">Password</dt>
                            <dd class="select-all font-semibold text-[var(--ink)]">admin123</dd>
                        </div>
                    </dl>

                    <a href="/admin/login" class="mt-4 flex w-full items-center justify-center rounded-xl bg-[var(--ink)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-125">
                        Open the staff sign-in
                    </a>
                    <p class="mt-2 text-center text-xs text-[var(--muted)]">
                        Also <span class="select-all">inventory@raney.test</span> / <span class="select-all">inventory123</span>
                        and <span class="select-all">accounting@raney.test</span> / <span class="select-all">accounting123</span>,
                        which see less.
                    </p>
                </div>
            </section>

            <div class="mt-5 rounded-2xl border border-[var(--accent-soft)] bg-[rgba(255,252,243,0.92)] px-5 py-4 text-sm leading-6 text-[#715b1d]">
                The two sign-ins take different accounts. A staff login typed into the shop page will be refused,
                and the other way round &mdash; the page will tell you which one to use.
            </div>

            <section class="mt-8 rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-bold text-[var(--ink)]">Worth trying as a customer</h2>
                <ol class="mt-4 space-y-4">
                    @foreach ([
                        ['Find an oil for a car', 'Open the chat bubble at the bottom right and ask it something a customer would ask: "what oil for my Toyota Vios", "ano ang pwede sa Mirage ko", or the make and year of your own car. Try one it will not know.'],
                        ['Ask it something else', 'Returns, delivery areas, how to pay, whether a down payment is allowed. Then ask it something off-topic and see what it does.'],
                        ['Look at a product', 'Open any oil from the shop. Check the pack sizes, the description and what the page tells you about stock.'],
                        ['Place an order', 'Add something to the cart and go through checkout. Delivery is anywhere in the Philippines.'],
                        ['Try the ways of paying', 'Cash on delivery, GCash, or a 50% down payment with the rest on delivery. Each behaves differently afterwards.'],
                        ['Follow the order', 'Open it from My Orders. See the receipt, the balance if there is one, and how far along the delivery is.'],
                        ['Forget your password', 'The sign-in page has a reset link. It sends a real email, so use an address you can open.'],
                    ] as $i => $task)
                        <li class="flex gap-4">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[var(--primary-soft)] text-xs font-black text-[var(--primary)]">{{ $i + 1 }}</span>
                            <div>
                                <div class="text-sm font-bold text-[var(--ink)]">{{ $task[0] }}</div>
                                <p class="mt-0.5 text-sm leading-6 text-[var(--muted)]">{{ $task[1] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="mt-5 rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-bold text-[var(--ink)]">Worth trying as the business</h2>
                <p class="mt-1 text-sm text-[var(--muted)]">Sign in at the staff page first. The menu is down the left.</p>
                <ol class="mt-4 space-y-4">
                    @foreach ([
                        ['Dashboard', 'Money taken over a period you choose, and a list of what needs doing today. Change the date range and watch the figures follow. Click one of the jobs and see where it takes you.'],
                        ['Analytics', 'The same date filter, applied to products, brands, oil types and customers. Everything here can be exported.'],
                        ['Products', 'Add a product. It shows you how the shop page will look before anything is saved, and asks whether to go ahead.'],
                        ['Inventory', 'What is on the shelf, what is running low, what has run out.'],
                        ['Sales', 'Open any order and read the whole receipt: how it was paid, what is still owed, where the delivery is. Move one from Processing to With the courier to Delivered. If a customer sent a GCash reference, approve or reject it here.'],
                        ['Users', 'The customer and staff accounts, and a log of what each has been doing.'],
                        ['Assistant', 'Every question the chatbot could not answer, most-asked first. Write an answer for one and then ask the chatbot that question again.'],
                        ['Backup', 'Take a copy of the whole system, and restore one, without touching a database.'],
                    ] as $i => $task)
                        <li class="flex gap-4">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[var(--surface)] text-xs font-black text-[var(--ink)]">{{ $i + 1 }}</span>
                            <div>
                                <div class="text-sm font-bold text-[var(--ink)]">{{ $task[0] }}</div>
                                <p class="mt-0.5 text-sm leading-6 text-[var(--muted)]">{{ $task[1] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="mt-5 rounded-[1.5rem] border border-[var(--line)] bg-white/70 p-6 text-sm leading-6 text-[var(--muted)]">
                <h2 class="text-sm font-bold uppercase tracking-[0.22em] text-[var(--ink)]">Before you ask</h2>
                <ul class="mt-3 space-y-2">
                    <li><span class="font-semibold text-[var(--ink)]">The warning page you saw first</span> belongs to the tunnel that puts this laptop online. It appears once. It is not part of the system.</li>
                    <li><span class="font-semibold text-[var(--ink)]">Sign in with Google is switched off</span> on purpose &mdash; it cannot be published on a temporary address. Use the account above.</li>
                    <li><span class="font-semibold text-[var(--ink)]">The assistant can be slow or plain</span> if several people ask at once. It falls back to shorter answers rather than failing.</li>
                    <li><span class="font-semibold text-[var(--ink)]">If nothing loads at all</span>, the laptop running it is probably off. Try again later.</li>
                </ul>
            </section>

            <p class="mt-8 text-center text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING</p>
        </div>
    </div>
@endsection
