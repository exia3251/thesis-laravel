@extends('layouts.admin')

@section('title', 'Dashboard - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">Dashboard</h1>
            <p class="mt-1 text-sm text-[var(--muted)]">Recent trade, and anything that needs doing today.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select id="rangeSelect" onchange="rangeChanged()"
                    class="rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ink)] outline-none transition focus:border-[var(--primary)]">
                <option value="all">All time</option>
                <option value="12m">Last 12 months</option>
                <option value="6m">Last 6 months</option>
                <option value="3m">Last 3 months</option>
                <option value="30d" selected>Last 30 days</option>
                <option value="7d">Last 7 days</option>
                <option value="custom">Custom range</option>
            </select>

            {{-- Native date fields, so the calendar is the one the operator
                 already knows from every other site. --}}
            <div id="customRange" class="hidden flex-wrap items-center gap-2">
                <input type="date" id="rangeFrom" aria-label="Start date"
                       class="rounded-xl border border-[var(--line)] bg-white px-3 py-2.5 text-sm font-semibold text-[var(--ink)] outline-none transition focus:border-[var(--primary)]">
                <span class="text-sm text-[var(--muted)]">to</span>
                <input type="date" id="rangeTo" aria-label="End date"
                       class="rounded-xl border border-[var(--line)] bg-white px-3 py-2.5 text-sm font-semibold text-[var(--ink)] outline-none transition focus:border-[var(--primary)]">
                <button type="button" onclick="loadDashboard()"
                        class="rounded-xl bg-[var(--primary)] px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Apply</button>
            </div>
        </div>
    </div>

    <div id="dashLoading" class="rounded-[1.5rem] border border-[var(--line)] bg-white p-16 text-center text-sm text-[var(--muted)]">
        Loading dashboard...
    </div>

    <div id="dashBody" class="hidden space-y-5">
        <section id="statCards" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5"></section>

        <section class="grid gap-5 lg:grid-cols-[1.35fr_1fr]">
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-[var(--ink)]">Collections</h2>
                        <p id="collectionsNote" class="mt-1 text-xs text-[var(--muted)]">Money received and orders placed</p>
                    </div>
                    {{-- One series at a time. Two of them side by side had to
                         be scaled separately, because pesos and a count of
                         orders share no axis, and two bars whose heights
                         cannot be compared sat next to each other inviting
                         exactly that. --}}
                    <div class="inline-flex shrink-0 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-1 text-xs font-semibold">
                        <button type="button" id="metricMoney" onclick="setMetric('money')" class="metric-tab rounded-lg px-3 py-1.5 transition">Money</button>
                        <button type="button" id="metricOrders" onclick="setMetric('orders')" class="metric-tab rounded-lg px-3 py-1.5 transition">Orders</button>
                    </div>
                </div>
                <div id="collectionsChart" class="mt-6"></div>
            </div>

            {{-- The jobs waiting on somebody here. Each one is a link to the
                 screen that clears it, filtered to the same set it counted,
                 so the number and the page behind it cannot disagree. --}}
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-[var(--ink)]">Action required</h2>
                        <p class="mt-1 text-xs text-[var(--muted)]">Live right now, whatever period is shown</p>
                    </div>
                    <span id="actionTotal" class="shrink-0 rounded-full bg-[var(--primary-soft)] px-3 py-1 text-xs font-bold text-[var(--primary)]"></span>
                </div>
                <div id="actionList" class="mt-5 space-y-2"></div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    const CARD_ICONS = {
        peso: 'M6 20V4h5a4 4 0 0 1 0 8H6m-1 3h8M5 11h8',
        cart: 'M3 4h2l.4 2m0 0L7 14h10l2-8H5.4ZM7 14l-1 5h12M9 20a1 1 0 1 0 0 .01M17 20a1 1 0 1 0 0 .01',
        box:  'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        clock: 'M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        receipt: 'M9 8h6m-6 4h6m-6 4h3M6 3h12a1 1 0 0 1 1 1v17l-3.5-2-3.5 2-3.5-2L5 21V4a1 1 0 0 1 1-1Z',
    };

    /* The dot, the number and the hover, per kind of job. */
    const ACTION_TONES = {
        red:     { dot: '#dc2626', text: 'text-red-700',     hover: 'hover:border-red-300 hover:bg-red-50' },
        amber:   { dot: '#d97706', text: 'text-amber-700',   hover: 'hover:border-amber-300 hover:bg-amber-50' },
        sky:     { dot: '#0284c7', text: 'text-sky-700',     hover: 'hover:border-sky-300 hover:bg-sky-50' },
        violet:  { dot: '#7c3aed', text: 'text-violet-700',  hover: 'hover:border-violet-300 hover:bg-violet-50' },
        emerald: { dot: '#148a67', text: 'text-emerald-700', hover: 'hover:border-emerald-300 hover:bg-emerald-50' },
    };

    const peso = (n) => 'PHP ' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pesoShort = (n) => {
        const v = Number(n || 0);
        if (v >= 1000000) return 'PHP ' + (v / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        if (v >= 1000) return 'PHP ' + Math.round(v / 1000) + 'k';
        return peso(v);
    };

    function renderCards(cards) {
        document.getElementById('statCards').innerHTML = cards.map(card => {
            const up = card.direction === 'up';
            const flat = card.direction === 'flat' || card.delta_percent === null;

            const delta = flat
                ? `<span class="text-xs text-[var(--muted)]">${escapeHtml(card.note || '')}</span>`
                : `<span class="inline-flex items-center gap-1 text-xs font-bold ${up ? 'text-emerald-700' : 'text-red-700'}">
                       <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${up ? 'M4 17 12 9l4 4 6-6M16 7h6v6' : 'M4 7l8 8 4-4 6 6M16 17h6v-6'}"/></svg>
                       ${card.delta_percent > 0 ? '+' : ''}${card.delta_percent}%
                   </span>
                   <span class="text-xs text-[var(--muted)]">${escapeHtml(card.note || '')}</span>`;

            return `
                <div class="rounded-[1.25rem] border border-[var(--line)] bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[var(--primary-soft)]">
                                <svg class="h-4 w-4 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${CARD_ICONS[card.icon] || CARD_ICONS.box}"/></svg>
                            </span>
                            <span class="text-sm font-medium text-[var(--muted)]">${escapeHtml(card.label)}</span>
                        </div>
                    </div>
                    <div class="mt-4 whitespace-nowrap text-3xl font-black tracking-tight text-[var(--ink)]">
                        ${card.format === 'money' ? pesoShort(card.value) : Number(card.value).toLocaleString()}
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">${delta}</div>
                </div>`;
        }).join('');
    }

    /**
     * Collections over the chosen window.
     *
     * Few buckets read best as a list, because each one can carry its own
     * figure. Many buckets cannot -- thirty of those is a page and a half --
     * so past a dozen it becomes columns, and the figures move to the
     * tooltip and the caption underneath.
     */
    let dashboardData = null;

    /* Money by default: it is the question the rest of the row answers. */
    let collectionsMetric = 'money';

    const METRICS = {
        money: {
            label: 'Money received',
            colour: 'var(--primary)',
            value: (bucket) => bucket.collected,
            format: (value) => peso(value),
        },
        orders: {
            label: 'Orders placed',
            colour: 'var(--accent)',
            value: (bucket) => bucket.orders,
            format: (value) => value + (value === 1 ? ' order' : ' orders'),
        },
    };

    function setMetric(metric) {
        collectionsMetric = metric;

        document.querySelectorAll('.metric-tab').forEach((tab) => {
            const on = tab.id === (metric === 'money' ? 'metricMoney' : 'metricOrders');
            tab.className = 'metric-tab rounded-lg px-3 py-1.5 transition '
                + (on ? 'bg-white text-[var(--ink)] shadow-sm' : 'text-[var(--muted)]');
        });

        if (dashboardData) renderCollections(dashboardData.collections, dashboardData.range);
    }

    function renderCollections(series, range) {
        const metric = METRICS[collectionsMetric];
        const note = document.getElementById('collectionsNote');
        note.textContent = metric.label + ', ' + (range.description || '');

        const holder = document.getElementById('collectionsChart');

        /* Nothing at all, rather than nothing yet: a window with no trade in
           it comes back as a full set of empty buckets, because a quiet day
           inside a busy month has to keep its place. Drawn, that was a row of
           flat bars under "Best: 1 January 2020, 0 orders". */
        const anything = series.some((bucket) => metric.value(bucket) > 0);

        if (!series.length || !anything) {
            holder.innerHTML = '<p class="rounded-xl border border-[var(--line)] p-6 text-center text-xs text-[var(--muted)]">'
                + (collectionsMetric === 'money' ? 'No money came in during this period.' : 'No orders were placed in this period.')
                + '</p>';
            return;
        }

        const peak = Math.max(...series.map(metric.value), 1);
        const busiest = series.reduce((best, m) => (metric.value(m) > metric.value(best) ? m : best), series[0]);

        holder.innerHTML = columns(series, peak, busiest, metric);
    }

    /** A bar that is still visible when the period was quiet but not empty. */
    function barHeight(value, peak) {
        return Math.max(value > 0 ? 3 : 1, (value / peak) * 100);
    }

    function columns(series, peak, busiest, metric) {
        // Every label will not fit on a long span, so roughly a dozen of them
        // are kept and the rest left blank rather than overlapped into mush.
        const every = Math.ceil(series.length / 12);

        // Capped, or three days of trade would be three enormous slabs.
        const width = 'flex-1 max-w-[74px]';

        const bars = series.map(m => `
            <div class="${width} flex h-full flex-col justify-end">
                <div class="rounded-t transition hover:brightness-110"
                     style="height:${barHeight(metric.value(m), peak)}%; background:${metric.colour}"
                     title="${escapeHtml(m.full_label)}: ${peso(m.collected)} over ${m.orders} ${m.orders === 1 ? 'order' : 'orders'}"></div>
            </div>`).join('');

        const labels = series.map((m, i) => `
            <div class="${width} overflow-hidden text-center text-[10px] leading-4 text-[var(--muted)]">
                ${i % every === 0 ? escapeHtml(m.label) : ''}
            </div>`).join('');

        return `
            <div class="flex h-56 items-stretch gap-[3px]">${bars}</div>
            <div class="mt-2 flex gap-[3px]">${labels}</div>
            <p class="mt-4 border-t border-[var(--line)] pt-3 text-xs leading-5 text-[var(--muted)]">
                Best: ${escapeHtml(busiest.full_label)}, ${metric.format(metric.value(busiest))}.
                <span class="text-[var(--muted)]">Hover a bar for both figures.</span>
            </p>`;
    }

    /**
     * Every job, including the ones with nothing in them.
     *
     * A line reading "0 payments to verify" is worth its space: it says the
     * queue was looked at and is empty, which a missing line does not. Those
     * lines are dimmed and are not links, because there is nothing to open.
     */
    function renderActions(rows) {
        const outstanding = rows.reduce((total, row) => total + row.count, 0);
        const badge = document.getElementById('actionTotal');

        badge.textContent = outstanding ? `${outstanding} waiting` : 'All clear';
        badge.className = outstanding
            ? 'shrink-0 rounded-full bg-[var(--primary-soft)] px-3 py-1 text-xs font-bold text-[var(--primary)]'
            : 'shrink-0 rounded-full bg-[var(--surface)] px-3 py-1 text-xs font-bold text-[var(--muted)]';

        document.getElementById('actionList').innerHTML = rows.map(row => {
            const tone = ACTION_TONES[row.tone] || ACTION_TONES.amber;

            if (!row.count) {
                return `
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-[var(--line)] px-3.5 py-3 opacity-60">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full border border-[var(--line)]"></span>
                            <span class="truncate text-sm text-[var(--muted)]">No ${escapeHtml(row.label)}</span>
                        </span>
                    </div>`;
            }

            return `
                <a href="${escapeHtml(row.href)}"
                   class="flex items-center justify-between gap-3 rounded-xl border border-[var(--line)] px-3.5 py-3 transition ${tone.hover}">
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background:${tone.dot}"></span>
                        <span class="truncate text-sm font-medium text-[var(--ink)]">
                            <span class="font-black ${tone.text}">${row.count}</span> ${escapeHtml(row.label)}
                        </span>
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-[var(--muted)]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                </a>`;
        }).join('');
    }

    function today() {
        return new Date().toLocaleDateString('en-CA');
    }

    /** Custom shows the two date fields, and starts them somewhere sensible. */
    function rangeChanged() {
        const custom = document.getElementById('rangeSelect').value === 'custom';
        const holder = document.getElementById('customRange');

        holder.classList.toggle('hidden', !custom);
        holder.classList.toggle('flex', custom);

        if (!custom) {
            loadDashboard();
            return;
        }

        const from = document.getElementById('rangeFrom');
        const to = document.getElementById('rangeTo');

        to.max = from.max = today();

        if (!from.value || !to.value) {
            const start = new Date();
            start.setDate(start.getDate() - 29);
            from.value = start.toLocaleDateString('en-CA');
            to.value = today();
        }

        loadDashboard();
    }

    function rangeQuery() {
        const range = document.getElementById('rangeSelect').value;

        if (range !== 'custom') {
            return `range=${range}`;
        }

        const from = document.getElementById('rangeFrom').value;
        const to = document.getElementById('rangeTo').value;

        return `range=custom&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
    }

    async function loadDashboard() {
        document.getElementById('dashBody').classList.add('hidden');
        document.getElementById('dashLoading').classList.remove('hidden');
        document.getElementById('dashLoading').textContent = 'Loading dashboard...';

        try {
            const response = await fetch(`/admin-api/dashboard/stats?${rangeQuery()}`, { headers: { Accept: 'application/json' } });

            if (response.status === 401) {
                window.location.href = '/admin/login';
                return;
            }

            const payload = await response.json();

            // A refused range is the operator's to correct, so it says which
            // part was wrong rather than "could not load".
            if (response.status === 422) {
                document.getElementById('dashLoading').textContent = Object.values(payload.errors || {}).flat().join(' ')
                    || 'That date range cannot be read.';
                return;
            }

            if (!payload.success) throw new Error('Failed to load.');

            const d = payload.data;
            // Held so the Money/Orders switch can redraw without asking the
            // server for the same period again.
            dashboardData = d;
            renderCards(d.cards);
            renderCollections(d.collections, d.range);
            renderActions(d.actions);

            document.getElementById('dashLoading').classList.add('hidden');
            document.getElementById('dashBody').classList.remove('hidden');
        } catch (error) {
            document.getElementById('dashLoading').textContent = 'Could not load the dashboard. Refresh to try again.';
        }
    }

    setMetric('money');
    loadDashboard();
</script>
@endpush
