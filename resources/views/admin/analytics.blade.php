@extends('layouts.admin')

@section('title', 'Analytics - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">Analytics</h1>
            <p class="mt-1 text-sm text-[var(--muted)]">Where the money came from, how much of it arrived, and who it came from.</p>
        </div>
        {{-- The same control as the dashboard, reading the same spans. --}}
        <div class="flex flex-wrap items-center gap-2">
            <select id="rangeSelect" onchange="rangeChanged()"
                    class="rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ink)] outline-none transition focus:border-[var(--primary)]">
                <option value="all">All time</option>
                <option value="12m" selected>Last 12 months</option>
                <option value="6m">Last 6 months</option>
                <option value="3m">Last 3 months</option>
                <option value="30d">Last 30 days</option>
                <option value="7d">Last 7 days</option>
                <option value="custom">Custom range</option>
            </select>

            <div id="customRange" class="hidden flex-wrap items-center gap-2">
                <input type="date" id="rangeFrom" aria-label="Start date"
                       class="rounded-xl border border-[var(--line)] bg-white px-3 py-2.5 text-sm font-semibold text-[var(--ink)] outline-none transition focus:border-[var(--primary)]">
                <span class="text-sm text-[var(--muted)]">to</span>
                <input type="date" id="rangeTo" aria-label="End date"
                       class="rounded-xl border border-[var(--line)] bg-white px-3 py-2.5 text-sm font-semibold text-[var(--ink)] outline-none transition focus:border-[var(--primary)]">
                <button type="button" onclick="loadAnalytics()"
                        class="rounded-xl bg-[var(--primary)] px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Apply</button>
            </div>

            {{-- The old reports screen asked for a date range and printed a
                 table. This screen already has the range, so the export is
                 simply the same period as a spreadsheet. --}}
            <button type="button" onclick="exportSales()"
                    class="inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Export CSV
            </button>
        </div>
    </div>

    <div id="analyticsLoading" class="rounded-[1.5rem] border border-[var(--line)] bg-white p-16 text-center text-sm text-[var(--muted)]">
        Loading analytics...
    </div>

    <div id="analyticsBody" class="hidden space-y-5">

        <section class="grid gap-5 lg:grid-cols-[1.65fr_1fr]">
            {{-- Sales booked against sales collected, month by month. --}}
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--ink)]">Sales overview</h2>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <span id="ovCollected" class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">PHP 0.00</span>
                            <span id="ovDelta" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"></span>
                        </div>
                        <p class="mt-1 text-xs text-[var(--muted)]">Cash received, <span id="ovRangeLabel"></span></p>
                    </div>
                    <div id="ovChips" class="flex flex-wrap gap-2"></div>
                </div>

                {{-- "Booked" is trade jargon. The grey bar is what the orders
                     were worth; the green is what has actually been paid.
                     The gap between them is the money still owed. --}}
                <div class="mt-5 flex flex-wrap items-center gap-4 text-xs text-[var(--muted)]">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#d8dee6]"></span>Order value</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[var(--primary)]"></span>Cash received</span>
                </div>

                <div class="mt-3 overflow-x-auto"><div id="barChart" class="min-w-[560px]"></div></div>
            </div>

            {{-- The same trade read at three depths. --}}
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--ink)]">Where sales come from</h2>
                <p id="breakdownNote" class="mt-1 text-xs text-[var(--muted)]">Share of value in this period</p>

                <div class="mt-4 inline-flex rounded-xl border border-[var(--line)] bg-[var(--surface)] p-1 text-xs font-semibold">
                    <button type="button" data-dim="product" onclick="setDimension('product')" class="dim-tab rounded-lg px-3 py-1.5">Product</button>
                    <button type="button" data-dim="type" onclick="setDimension('type')" class="dim-tab rounded-lg px-3 py-1.5">Oil type</button>
                    <button type="button" data-dim="brand" onclick="setDimension('brand')" class="dim-tab rounded-lg px-3 py-1.5">Brand</button>
                </div>

                {{-- Bounded, and scrolled inside itself. Without the cap the
                     list sets the height of the whole row, so adding
                     products stretched the chart beside it. --}}
                <div id="breakdownChart" class="mt-4 max-h-[19rem] overflow-y-auto pr-1"></div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-[1fr_1.35fr]">
            {{-- Orders placed over time. --}}
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--ink)]">Orders placed</h2>
                <p id="ordersRangeLabel" class="mt-1 text-xs text-[var(--muted)]"></p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <span id="ordersTotal" class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">0</span>
                    <span id="ordersDelta" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"></span>
                </div>
                <div class="mt-4"><div id="areaChart"></div></div>
            </div>

            {{-- Brand share, with the money figures that sit alongside it. --}}
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--ink)]">Sales by brand</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Where the value is concentrated</p>

                <div class="mt-5 grid gap-6 sm:grid-cols-[1fr_auto] sm:items-center">
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-[var(--muted)]">Leading brand</p>
                            <p id="brandTopValue" class="mt-0.5 text-2xl font-black text-[var(--ink)]">PHP 0.00</p>
                            <p id="brandTopName" class="text-xs font-semibold text-[var(--primary)]"></p>
                        </div>
                        <div>
                            <p class="text-xs text-[var(--muted)]">Collection rate</p>
                            <p id="collectionRate" class="mt-0.5 text-2xl font-black text-emerald-700">0%</p>
                            <p class="text-xs text-[var(--muted)]">of everything booked</p>
                        </div>
                        <div>
                            <p class="text-xs text-[var(--muted)]">Still owed</p>
                            <p id="receivables" class="mt-0.5 text-2xl font-black text-[var(--ink)]">PHP 0.00</p>
                            <p id="receivablesNote" class="text-xs text-[var(--muted)]"></p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center gap-4">
                        <div id="donutChart"></div>
                        <div id="brandLegend" class="grid w-full gap-1.5 text-xs"></div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-3">
            {{-- Who bought, and whether they had bought before. --}}
            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--ink)]">Customers</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Everyone who ordered in this period</p>

                <p id="customerCount" class="mt-4 text-3xl font-black tracking-tight text-[var(--ink)]">0</p>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3 border-t border-[var(--line)] pt-3">
                        <dt class="text-[var(--muted)]">New customers</dt>
                        <dd id="customerNew" class="font-bold text-[var(--primary)]">0</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-[var(--line)] pt-3">
                        <dt class="text-[var(--muted)]">Returning</dt>
                        <dd id="customerReturning" class="font-bold text-[var(--ink)]">0%</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-[var(--line)] pt-3">
                        <dt class="text-[var(--muted)]">Orders per customer</dt>
                        <dd id="customerOrders" class="font-bold text-[var(--ink)]">0</dd>
                    </div>
                </dl>

                <p id="customerNote" class="mt-4 text-xs leading-5 text-[var(--muted)]"></p>
            </section>

            {{-- Which payment option customers actually chose. --}}
            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--ink)]">How customers paid</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Share of orders by the option chosen at checkout</p>
                <div id="paymentMix" class="mt-5 space-y-4"></div>
            </section>

            {{-- Moved off the dashboard, which now lists the jobs waiting
                 instead. Scoped to the period, unlike the version there. --}}
            <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--ink)]">Where these orders stand</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Every order placed in this period, by where it has got to</p>
                <div id="orderStatus" class="mt-5 space-y-3"></div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Charts are drawn as inline SVG rather than pulled from a charting
    // library, so the page keeps working with no internet and stays in the
    // same palette as everything else.
    const GREEN = '#148a67';
    const GREEN_DARK = '#0f6b50';
    const GOLD = '#d9b14a';
    const GREY = '#d8dee6';
    const BRAND_TONES = [GREEN, GOLD, '#4f8bbd', '#8d6cb5', '#c2703f'];

    const STATUS_TONES = {
        amber:   { dot: '#d97706', text: 'text-amber-800' },
        sky:     { dot: '#0284c7', text: 'text-sky-800' },
        violet:  { dot: '#7c3aed', text: 'text-violet-800' },
        emerald: { dot: '#148a67', text: 'text-emerald-800' },
        slate:   { dot: '#8b97a5', text: 'text-slate-700' },
    };

    let analytics = null;
    let dimension = 'product';

    const peso = (n) => 'PHP ' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pesoShort = (n) => {
        const v = Number(n || 0);
        if (v >= 1000000) return (v / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        if (v >= 1000) return Math.round(v / 1000) + 'k';
        return String(Math.round(v));
    };

    function deltaPill(element, delta, direction) {
        const up = direction === 'up';
        const flat = direction === 'flat';
        element.className = 'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold '
            + (flat ? 'bg-slate-100 text-slate-600' : up ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800');
        element.innerHTML = flat
            ? 'no change'
            : `<svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${up ? 'M4 17 12 9l4 4 6-6M16 7h6v6' : 'M4 7l8 8 4-4 6 6M16 17h6v-6'}"/></svg>`
              + `${delta > 0 ? '+' : ''}${delta}%`;
    }

    /** Grouped vertical bars: booked beside collected, one pair per month. */
    function drawBars(series) {
        const W = 720, H = 240, padL = 44, padB = 26, padT = 8;
        const plotW = W - padL, plotH = H - padB - padT;
        const peak = Math.max(...series.map(m => Math.max(m.booked, m.collected)), 1);
        const step = plotW / series.length;
        const barW = Math.min(14, step / 3.4);

        // Thirty days of labels will not fit along the axis, so about a
        // dozen are kept and the rest left off rather than overlapped.
        const every = Math.ceil(series.length / 12);

        const ticks = [0, 0.25, 0.5, 0.75, 1].map(f => {
            const y = padT + plotH - f * plotH;
            return `<line x1="${padL}" y1="${y}" x2="${W}" y2="${y}" stroke="#e8edf2" stroke-width="1" stroke-dasharray="3 4"/>
                    <text x="${padL - 8}" y="${y + 3.5}" text-anchor="end" font-size="10" fill="#8b97a5">${pesoShort(peak * f)}</text>`;
        }).join('');

        const bars = series.map((m, i) => {
            const cx = padL + step * i + step / 2;
            const bh = Math.max((m.booked / peak) * plotH, m.booked > 0 ? 2 : 0);
            const ch = Math.max((m.collected / peak) * plotH, m.collected > 0 ? 2 : 0);
            return `<g><title>${m.full_label} — ${peso(m.booked)} ordered, ${peso(m.collected)} received</title>
                <rect x="${cx - barW - 2}" y="${padT + plotH - bh}" width="${barW}" height="${bh}" rx="3" fill="${GREY}"/>
                <rect x="${cx + 2}" y="${padT + plotH - ch}" width="${barW}" height="${ch}" rx="3" fill="${GREEN}"/>
                <rect x="${cx - step / 2}" y="${padT}" width="${step}" height="${plotH}" fill="transparent"/>
                ${i % every === 0 ? `<text x="${cx}" y="${H - 8}" text-anchor="middle" font-size="10" fill="#8b97a5">${m.label}</text>` : ''}</g>`;
        }).join('');

        document.getElementById('barChart').innerHTML =
            `<svg viewBox="0 0 ${W} ${H}" width="100%" height="${H}" role="img" aria-label="Order value against cash received">${ticks}${bars}</svg>`;
    }

    /** Smooth area of order counts over the same months. */
    function drawArea(series) {
        const W = 420, H = 150, padB = 20, padT = 10;
        const plotH = H - padB - padT;
        const peak = Math.max(...series.map(m => m.orders), 1);
        const step = series.length > 1 ? W / (series.length - 1) : W;
        const points = series.map((m, i) => [i * step, padT + plotH - (m.orders / peak) * plotH]);

        let path = `M ${points[0][0]} ${points[0][1]}`;
        for (let i = 0; i < points.length - 1; i++) {
            const [x0, y0] = points[i], [x1, y1] = points[i + 1];
            const cx = (x0 + x1) / 2;
            path += ` C ${cx} ${y0}, ${cx} ${y1}, ${x1} ${y1}`;
        }

        const area = `${path} L ${points[points.length - 1][0]} ${padT + plotH} L 0 ${padT + plotH} Z`;
        const dots = series.map((m, i) => `<g><title>${m.full_label} — ${m.orders} orders</title><circle cx="${points[i][0]}" cy="${points[i][1]}" r="8" fill="transparent"/></g>`).join('');
        const labels = series.map((m, i) => (i % Math.ceil(series.length / 6) === 0)
            ? `<text x="${points[i][0]}" y="${H - 4}" text-anchor="middle" font-size="10" fill="#8b97a5">${m.label}</text>` : '').join('');

        document.getElementById('areaChart').innerHTML = `
            <svg viewBox="0 0 ${W} ${H}" width="100%" height="${H}" role="img" aria-label="Orders placed per month">
                <defs><linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="${GREEN}" stop-opacity="0.22"/><stop offset="100%" stop-color="${GREEN}" stop-opacity="0"/>
                </linearGradient></defs>
                <path d="${area}" fill="url(#areaFill)"/>
                <path d="${path}" fill="none" stroke="${GREEN_DARK}" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                ${dots}${labels}
            </svg>`;
    }

    /** Donut of brand share. */
    function drawDonut(brands) {
        const size = 168, r = 62, cx = size / 2, cy = size / 2;
        const circumference = 2 * Math.PI * r;
        let offset = 0;

        const arcs = brands.map((b, i) => {
            const length = (b.share / 100) * circumference;
            const dash = `${Math.max(length - 3, 0)} ${circumference - Math.max(length - 3, 0)}`;
            const arc = `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${BRAND_TONES[i % BRAND_TONES.length]}"
                stroke-width="18" stroke-dasharray="${dash}" stroke-dashoffset="${-offset}"
                transform="rotate(-90 ${cx} ${cy})" stroke-linecap="round">
                <title>${escapeHtml(b.brand)} — ${b.share}% (${peso(b.revenue)})</title></circle>`;
            offset += length;
            return arc;
        }).join('');

        document.getElementById('donutChart').innerHTML =
            `<svg viewBox="0 0 ${size} ${size}" width="168" height="168" role="img" aria-label="Share of sales by brand">
                <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#f1f4f7" stroke-width="18"/>${arcs}</svg>`;

        document.getElementById('brandLegend').innerHTML = brands.map((b, i) => `
            <div class="flex items-center justify-between gap-3">
                <span class="flex min-w-0 items-center gap-2">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background:${BRAND_TONES[i % BRAND_TONES.length]}"></span>
                    <span class="truncate text-[var(--muted)]">${escapeHtml(b.brand)}</span>
                </span>
                <span class="shrink-0 font-bold text-[var(--ink)]">${b.share}%</span>
            </div>`).join('');
    }

    /** Horizontal bars for whichever dimension is selected. */
    function drawBreakdown() {
        const set = analytics.breakdown[dimension] || { rows: [], shown: 0, available: 0 };
        const rows = set.rows;

        document.querySelectorAll('.dim-tab').forEach(tab => {
            const on = tab.dataset.dim === dimension;
            tab.className = 'dim-tab rounded-lg px-3 py-1.5 ' + (on ? 'bg-white text-[var(--ink)] shadow-sm' : 'text-[var(--muted)]');
        });

        // Says what is on screen when there is more than fits, rather than
        // letting a capped list look like the whole story.
        document.getElementById('breakdownNote').textContent = set.available > set.shown
            ? `Share of value in this period, top ${set.shown} of ${set.available}`
            : 'Share of value in this period';

        const peak = Math.max(...rows.map(r => r.revenue), 1);

        document.getElementById('breakdownChart').innerHTML = rows.length
            ? rows.map(r => `
                <div class="mb-3">
                    <div class="flex items-end justify-between gap-3">
                        <span class="truncate text-xs font-medium text-[var(--ink)]" title="${escapeHtml(r.label)}">${escapeHtml(r.label)}</span>
                        <span class="shrink-0 text-xs text-[var(--muted)]">${pesoShort(r.revenue)} &middot; ${Number(r.units).toLocaleString()} sold &middot; ${r.share}%</span>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-[var(--surface)]">
                        <div class="h-full rounded-full bg-[var(--primary)]" style="width:${(r.revenue / peak) * 100}%"></div>
                    </div>
                </div>`).join('')
            : '<p class="py-6 text-center text-sm text-[var(--muted)]">Nothing sold in this period.</p>';
    }

    function setDimension(next) {
        dimension = next;
        drawBreakdown();
    }

    function render(d) {
        analytics = d;
        const h = d.headline;

        document.getElementById('ovCollected').textContent = peso(h.collected.value);
        deltaPill(document.getElementById('ovDelta'), h.collected.delta_percent, h.collected.direction);
        document.getElementById('ovRangeLabel').textContent = d.range.description;

        document.getElementById('ovChips').innerHTML = d.payment_mix.filter(m => m.orders > 0).map(m => `
            <span class="rounded-xl border border-[var(--line)] bg-[var(--surface)] px-3 py-2">
                <span class="block text-[10px] uppercase tracking-wider text-[var(--muted)]">${escapeHtml(m.label.split(' ')[0])}</span>
                <span class="block text-sm font-bold text-[var(--ink)]">${m.orders} orders</span>
            </span>`).join('');

        drawBars(d.series);
        drawBreakdown();

        document.getElementById('ordersTotal').textContent = Number(h.orders.value).toLocaleString();
        deltaPill(document.getElementById('ordersDelta'), h.orders.delta_percent, h.orders.direction);
        document.getElementById('ordersRangeLabel').textContent = d.range.description;
        drawArea(d.series);

        const leader = d.by_brand[0];
        document.getElementById('brandTopValue').textContent = leader ? peso(leader.revenue) : peso(0);
        document.getElementById('brandTopName').textContent = leader ? `${leader.brand} — ${leader.share}% of sales` : 'No sales yet';
        document.getElementById('collectionRate').textContent = h.collection_rate + '%';
        document.getElementById('receivables').textContent = peso(d.receivables.outstanding);
        document.getElementById('receivablesNote').textContent =
            `on ${d.receivables.orders} of these orders${d.receivables.awaiting_review ? `, ${d.receivables.awaiting_review} payment awaiting review` : ''}`;
        drawDonut(d.by_brand);

        const busiest = Math.max(...d.payment_mix.map(m => m.orders), 1);
        document.getElementById('paymentMix').innerHTML = d.payment_mix.map(m => `
            <div>
                <div class="flex items-end justify-between gap-4">
                    <span class="text-sm font-semibold text-[var(--ink)]">${escapeHtml(m.label)}</span>
                    <span class="text-xs text-[var(--muted)]">${m.orders} orders &middot; ${peso(m.booked)}</span>
                </div>
                <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-[var(--surface)]">
                    <div class="h-full rounded-full bg-[var(--primary)]" style="width:${(m.orders / busiest) * 100}%"></div>
                </div>
            </div>`).join('');

        const c = d.customers;
        document.getElementById('customerCount').textContent = Number(c.customers).toLocaleString();
        document.getElementById('customerNew').textContent = (c.new > 0 ? '+' : '') + Number(c.new).toLocaleString();
        document.getElementById('customerReturning').textContent = c.returning_rate + '%';
        document.getElementById('customerOrders').textContent = c.orders_per_customer;
        document.getElementById('customerNote').textContent = c.customers
            ? `${c.returning} had ordered before this period; ${c.new} had not. `
              + `${Number(c.orders).toLocaleString()} orders between them.`
            : 'Nobody ordered in this period.';

        document.getElementById('orderStatus').innerHTML = d.order_status.map(row => {
            const tone = STATUS_TONES[row.tone] || STATUS_TONES.slate;
            return `
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-[var(--line)] px-4 py-3.5">
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background:${tone.dot}"></span>
                        <span class="truncate text-sm font-medium text-[var(--ink)]">${escapeHtml(row.label)}</span>
                    </span>
                    <span class="shrink-0 text-2xl font-black ${tone.text}">${row.count}</span>
                </div>`;
        }).join('');

        document.getElementById('analyticsLoading').classList.add('hidden');
        document.getElementById('analyticsBody').classList.remove('hidden');
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
            loadAnalytics();
            return;
        }

        const from = document.getElementById('rangeFrom');
        const to = document.getElementById('rangeTo');

        to.max = from.max = today();

        if (!from.value || !to.value) {
            const start = new Date();
            start.setMonth(start.getMonth() - 3);
            from.value = start.toLocaleDateString('en-CA');
            to.value = today();
        }

        loadAnalytics();
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

    /** Every order in the period on screen, as a spreadsheet. */
    function exportSales() {
        if (!analytics) return;

        const params = new URLSearchParams({ from: analytics.range.from, to: analytics.range.to });
        window.location.href = `/admin-api/reports/sales/export?${params}`;
    }

    async function loadAnalytics() {
        document.getElementById('analyticsBody').classList.add('hidden');
        document.getElementById('analyticsLoading').classList.remove('hidden');
        document.getElementById('analyticsLoading').textContent = 'Loading analytics...';

        try {
            const response = await fetch(`/admin-api/analytics?${rangeQuery()}`, { headers: { Accept: 'application/json' } });

            if (response.status === 401) {
                window.location.href = '/admin/login';
                return;
            }

            const payload = await response.json();

            // A refused range is the operator's to correct, so it says which
            // part was wrong rather than "could not load".
            if (response.status === 422) {
                document.getElementById('analyticsLoading').textContent =
                    Object.values(payload.errors || {}).flat().join(' ') || 'That date range cannot be read.';
                return;
            }

            if (!payload.success) throw new Error(payload.message || 'Failed to load.');

            render(payload.data);
        } catch (error) {
            document.getElementById('analyticsLoading').textContent = 'Could not load analytics. Refresh to try again.';
        }
    }

    loadAnalytics();
</script>
@endpush
