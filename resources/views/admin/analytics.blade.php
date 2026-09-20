@extends('layouts.admin')

@section('title', 'Analytics - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-[var(--ink)]">Analytics</h1>
            <p class="mt-1 text-sm text-[var(--muted)]">Where the money came from, how much of it arrived, and what the stock is doing.</p>
        </div>
        <div class="flex items-center gap-2">
            <select id="rangeSelect" onchange="loadAnalytics()"
                    class="rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ink)] outline-none transition focus:border-[var(--primary)]">
                <option value="6">Last 6 months</option>
                <option value="12" selected>Last 12 months</option>
                <option value="24">Last 24 months</option>
            </select>
            <a href="/admin/reports" class="rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Export Report</a>
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
                            <span id="ovCollected" class="text-3xl font-black tracking-tight text-[var(--ink)]">PHP 0.00</span>
                            <span id="ovDelta" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold"></span>
                        </div>
                        <p class="mt-1 text-xs text-[var(--muted)]">Collected <span id="ovRangeLabel"></span></p>
                    </div>
                    <div id="ovChips" class="flex flex-wrap gap-2"></div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-4 text-xs text-[var(--muted)]">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#d8dee6]"></span>Booked</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[var(--primary)]"></span>Collected</span>
                </div>

                <div class="mt-3 overflow-x-auto"><div id="barChart" class="min-w-[560px]"></div></div>
            </div>

            {{-- The same trade read at three depths. --}}
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--ink)]">Where sales come from</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Share of value in this period</p>

                <div class="mt-4 inline-flex rounded-xl border border-[var(--line)] bg-[var(--surface)] p-1 text-xs font-semibold">
                    <button type="button" data-dim="product" onclick="setDimension('product')" class="dim-tab rounded-lg px-3 py-1.5">Product</button>
                    <button type="button" data-dim="type" onclick="setDimension('type')" class="dim-tab rounded-lg px-3 py-1.5">Oil type</button>
                    <button type="button" data-dim="brand" onclick="setDimension('brand')" class="dim-tab rounded-lg px-3 py-1.5">Brand</button>
                </div>

                <div id="breakdownChart" class="mt-4"></div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-[1fr_1.35fr]">
            {{-- Orders placed over time. --}}
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--ink)]">Orders placed</h2>
                <p id="ordersRangeLabel" class="mt-1 text-xs text-[var(--muted)]"></p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <span id="ordersTotal" class="text-3xl font-black tracking-tight text-[var(--ink)]">0</span>
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

        {{-- Stock against the rate it actually leaves the shelf. --}}
        <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-[var(--ink)]">Stock health</h2>
                    <p class="mt-1 text-xs text-[var(--muted)]">How long current stock lasts at this period's selling rate. Bottom-left runs out soonest; far right is money sitting still.</p>
                </div>
                <div id="stockSummary" class="flex flex-wrap gap-2"></div>
            </div>

            <div class="mt-5 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
                <div class="overflow-x-auto"><div id="scatterChart" class="min-w-[460px]"></div></div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--muted)]">Needs attention</p>
                    <div id="stockAlerts" class="mt-3 space-y-2"></div>
                </div>
            </div>
        </section>

        {{-- How much of the trade rests on how few customers. --}}
        <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-[var(--ink)]">Customer concentration</h2>
                    <p class="mt-1 text-xs text-[var(--muted)]">Revenue per customer, largest first, with the running share behind it</p>
                </div>
                <div id="paretoHeadline" class="rounded-xl border border-[var(--line)] bg-[var(--surface)] px-4 py-2 text-right"></div>
            </div>
            <div class="mt-5 overflow-x-auto"><div id="paretoChart" class="min-w-[620px]"></div></div>
        </section>

        {{-- Which payment option customers actually chose. --}}
        <section class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-[var(--ink)]">How customers paid</h2>
            <p class="mt-1 text-xs text-[var(--muted)]">Share of orders by the option chosen at checkout</p>
            <div id="paymentMix" class="mt-5 space-y-4"></div>
        </section>
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

    const STOCK_TONES = {
        out:     { dot: '#dc2626', chip: 'bg-red-100 text-red-800',        label: 'Out of stock' },
        reorder: { dot: '#d97706', chip: 'bg-amber-100 text-amber-800',    label: 'Reorder soon' },
        healthy: { dot: GREEN,     chip: 'bg-emerald-100 text-emerald-800', label: 'Healthy' },
        dead:    { dot: '#8b97a5', chip: 'bg-slate-200 text-slate-700',    label: 'Not moving' },
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

        const ticks = [0, 0.25, 0.5, 0.75, 1].map(f => {
            const y = padT + plotH - f * plotH;
            return `<line x1="${padL}" y1="${y}" x2="${W}" y2="${y}" stroke="#e8edf2" stroke-width="1" stroke-dasharray="3 4"/>
                    <text x="${padL - 8}" y="${y + 3.5}" text-anchor="end" font-size="10" fill="#8b97a5">${pesoShort(peak * f)}</text>`;
        }).join('');

        const bars = series.map((m, i) => {
            const cx = padL + step * i + step / 2;
            const bh = Math.max((m.booked / peak) * plotH, m.booked > 0 ? 2 : 0);
            const ch = Math.max((m.collected / peak) * plotH, m.collected > 0 ? 2 : 0);
            return `<g><title>${m.full_label} — booked ${peso(m.booked)}, collected ${peso(m.collected)}</title>
                <rect x="${cx - barW - 2}" y="${padT + plotH - bh}" width="${barW}" height="${bh}" rx="3" fill="${GREY}"/>
                <rect x="${cx + 2}" y="${padT + plotH - ch}" width="${barW}" height="${ch}" rx="3" fill="${GREEN}"/>
                <rect x="${cx - step / 2}" y="${padT}" width="${step}" height="${plotH}" fill="transparent"/>
                <text x="${cx}" y="${H - 8}" text-anchor="middle" font-size="10" fill="#8b97a5">${m.label}</text></g>`;
        }).join('');

        document.getElementById('barChart').innerHTML =
            `<svg viewBox="0 0 ${W} ${H}" width="100%" height="${H}" role="img" aria-label="Monthly sales booked and collected">${ticks}${bars}</svg>`;
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
                <title>${b.brand} — ${b.share}% (${peso(b.revenue)})</title></circle>`;
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
        const rows = analytics.breakdown[dimension] || [];

        document.querySelectorAll('.dim-tab').forEach(tab => {
            const on = tab.dataset.dim === dimension;
            tab.className = 'dim-tab rounded-lg px-3 py-1.5 ' + (on ? 'bg-white text-[var(--ink)] shadow-sm' : 'text-[var(--muted)]');
        });

        const peak = Math.max(...rows.map(r => r.revenue), 1);

        document.getElementById('breakdownChart').innerHTML = rows.length
            ? rows.map(r => `
                <div class="mb-3">
                    <div class="flex items-end justify-between gap-3">
                        <span class="truncate text-xs font-medium text-[var(--ink)]" title="${escapeHtml(r.label)}">${escapeHtml(r.label)}</span>
                        <span class="shrink-0 text-xs text-[var(--muted)]">${pesoShort(r.revenue)} &middot; ${r.share}%</span>
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

    /**
     * Stock plotted against how fast it sells. Position carries the meaning:
     * left edge is about to run out, far right is stock nothing is drawing on.
     */
    function drawScatter(health) {
        const W = 540, H = 280, padL = 46, padB = 34, padT = 12, padR = 12;
        const plotW = W - padL - padR, plotH = H - padB - padT;
        const CAP = 150;

        const items = health.items.filter(i => i.stock > 0 || i.units_sold > 0);
        const peakUnits = Math.max(...items.map(i => i.units_sold), 1);

        // Anything with no movement has no cover figure, so it is pinned to the
        // right edge where "not moving" belongs rather than dropped.
        const xFor = (item) => {
            const cover = item.days_of_cover === null ? CAP : Math.min(item.days_of_cover, CAP);
            return padL + (cover / CAP) * plotW;
        };
        const yFor = (item) => padT + plotH - (item.units_sold / peakUnits) * plotH;

        const reorderX = padL + (14 / CAP) * plotW;
        const deadX = padL + (120 / CAP) * plotW;

        const zones = `
            <rect x="${padL}" y="${padT}" width="${reorderX - padL}" height="${plotH}" fill="#fef2f2"/>
            <rect x="${deadX}" y="${padT}" width="${padL + plotW - deadX}" height="${plotH}" fill="#f8fafc"/>
            <text x="${padL + 6}" y="${padT + 14}" font-size="9" fill="#b91c1c">runs out soon</text>
            <text x="${padL + plotW - 6}" y="${padT + 14}" text-anchor="end" font-size="9" fill="#64748b">money sitting still</text>`;

        const grid = [0, 0.25, 0.5, 0.75, 1].map(f => {
            const y = padT + plotH - f * plotH;
            return `<line x1="${padL}" y1="${y}" x2="${padL + plotW}" y2="${y}" stroke="#e8edf2" stroke-width="1" stroke-dasharray="3 4"/>
                    <text x="${padL - 8}" y="${y + 3.5}" text-anchor="end" font-size="10" fill="#8b97a5">${Math.round(peakUnits * f)}</text>`;
        }).join('');

        const xTicks = [0, 30, 60, 90, 120, CAP].map(d => {
            const x = padL + (d / CAP) * plotW;
            return `<text x="${x}" y="${H - 14}" text-anchor="middle" font-size="10" fill="#8b97a5">${d === CAP ? CAP + '+' : d}</text>`;
        }).join('');

        const dots = items.map(item => {
            const tone = STOCK_TONES[item.status] || STOCK_TONES.healthy;
            const cover = item.days_of_cover === null ? 'no movement' : item.days_of_cover + ' days of cover';
            return `<g><title>${escapeHtml(item.name)} — ${item.stock} in stock, ${item.units_sold} sold, ${cover}</title>
                <circle cx="${xFor(item)}" cy="${yFor(item)}" r="6" fill="${tone.dot}" fill-opacity="0.75" stroke="white" stroke-width="1.5"/></g>`;
        }).join('');

        document.getElementById('scatterChart').innerHTML = `
            <svg viewBox="0 0 ${W} ${H}" width="100%" height="${H}" role="img" aria-label="Stock cover against units sold">
                ${zones}${grid}${dots}${xTicks}
                <text x="${padL + plotW / 2}" y="${H - 2}" text-anchor="middle" font-size="10" fill="#8b97a5">days of cover</text>
                <text x="12" y="${padT + plotH / 2}" text-anchor="middle" font-size="10" fill="#8b97a5" transform="rotate(-90 12 ${padT + plotH / 2})">units sold</text>
            </svg>`;

        const s = health.summary;
        document.getElementById('stockSummary').innerHTML = [
            ['out', s.out_of_stock, 'out of stock'],
            ['reorder', s.reorder_now, 'reorder soon'],
            ['dead', s.dead_capital, 'not moving'],
            ['healthy', s.healthy, 'healthy'],
        ].map(([key, count, label]) => `
            <span class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold ${STOCK_TONES[key].chip}">
                <span class="h-2 w-2 rounded-full" style="background:${STOCK_TONES[key].dot}"></span>${count} ${escapeHtml(label)}
            </span>`).join('');

        const attention = health.items
            .filter(i => i.status === 'out' || i.status === 'reorder' || i.status === 'dead')
            .sort((a, b) => b.units_sold - a.units_sold)
            .slice(0, 6);

        document.getElementById('stockAlerts').innerHTML = attention.length
            ? attention.map(i => {
                const tone = STOCK_TONES[i.status];
                return `<div class="flex items-start gap-3 rounded-xl border border-[var(--line)] p-3">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" style="background:${tone.dot}"></span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-xs font-semibold text-[var(--ink)]" title="${escapeHtml(i.name)}">${escapeHtml(i.name)}</div>
                        <div class="text-[11px] text-[var(--muted)]">
                            ${i.stock} in stock &middot; ${i.units_sold} sold
                            ${i.days_of_cover !== null ? ` &middot; ${i.days_of_cover} days left` : ' &middot; no movement'}
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold ${tone.chip}">${tone.label}</span>
                </div>`;
            }).join('')
            : '<p class="rounded-xl border border-[var(--line)] p-4 text-xs text-[var(--muted)]">Nothing needs attention right now.</p>';
    }

    /** Pareto: revenue per customer descending, with the running share over it. */
    function drawPareto(c) {
        const shown = c.customers.slice(0, 14);
        if (!shown.length) {
            document.getElementById('paretoChart').innerHTML = '<p class="py-8 text-center text-sm text-[var(--muted)]">No customer orders in this period.</p>';
            return;
        }

        const W = 720, H = 260, padL = 48, padR = 40, padB = 54, padT = 12;
        const plotW = W - padL - padR, plotH = H - padB - padT;
        const peak = Math.max(...shown.map(r => r.revenue), 1);
        const step = plotW / shown.length;
        const barW = Math.min(30, step * 0.55);

        const grid = [0, 0.25, 0.5, 0.75, 1].map(f => {
            const y = padT + plotH - f * plotH;
            return `<line x1="${padL}" y1="${y}" x2="${padL + plotW}" y2="${y}" stroke="#e8edf2" stroke-width="1" stroke-dasharray="3 4"/>
                    <text x="${padL - 8}" y="${y + 3.5}" text-anchor="end" font-size="10" fill="#8b97a5">${pesoShort(peak * f)}</text>
                    <text x="${padL + plotW + 8}" y="${y + 3.5}" font-size="10" fill="${GOLD}">${Math.round(f * 100)}%</text>`;
        }).join('');

        const bars = shown.map((r, i) => {
            const cx = padL + step * i + step / 2;
            const h = Math.max((r.revenue / peak) * plotH, 2);
            const short = r.name.length > 14 ? r.name.slice(0, 13) + '…' : r.name;
            return `<g><title>${escapeHtml(r.name)} — ${peso(r.revenue)} across ${r.orders} orders (${r.share}%, running ${r.cumulative_share}%)</title>
                <rect x="${cx - barW / 2}" y="${padT + plotH - h}" width="${barW}" height="${h}" rx="4" fill="${GREEN}"/>
                <text x="${cx}" y="${H - 34}" text-anchor="end" font-size="9.5" fill="#8b97a5"
                      transform="rotate(-38 ${cx} ${H - 34})">${escapeHtml(short)}</text></g>`;
        }).join('');

        const linePoints = shown.map((r, i) => [padL + step * i + step / 2, padT + plotH - (r.cumulative_share / 100) * plotH]);
        const linePath = 'M ' + linePoints.map(p => `${p[0]} ${p[1]}`).join(' L ');
        const lineDots = linePoints.map((p, i) =>
            `<g><title>${escapeHtml(shown[i].name)} — running share ${shown[i].cumulative_share}%</title>
             <circle cx="${p[0]}" cy="${p[1]}" r="3.5" fill="${GOLD}" stroke="white" stroke-width="1.5"/></g>`).join('');

        document.getElementById('paretoChart').innerHTML = `
            <svg viewBox="0 0 ${W} ${H}" width="100%" height="${H}" role="img" aria-label="Revenue by customer with cumulative share">
                ${grid}${bars}
                <path d="${linePath}" fill="none" stroke="${GOLD}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                ${lineDots}
            </svg>
            <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-[var(--muted)]">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[var(--primary)]"></span>Revenue</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:${GOLD}"></span>Running share of total</span>
                <span>Showing the ${shown.length} largest of ${c.total_customers}</span>
            </div>`;

        document.getElementById('paretoHeadline').innerHTML = `
            <div class="text-2xl font-black text-[var(--ink)]">${c.top_fifth_share}%</div>
            <div class="text-xs text-[var(--muted)]">of revenue from the top ${c.top_fifth_count} of ${c.total_customers} customers</div>
            <div class="mt-1 text-[11px] text-[var(--muted)]">largest single account: ${c.largest_share}%</div>`;
    }

    function render(d) {
        analytics = d;
        const h = d.headline;

        document.getElementById('ovCollected').textContent = peso(h.collected.value);
        deltaPill(document.getElementById('ovDelta'), h.collected.delta_percent, h.collected.direction);
        document.getElementById('ovRangeLabel').textContent = d.range.label;

        document.getElementById('ovChips').innerHTML = d.payment_mix.filter(m => m.orders > 0).map(m => `
            <span class="rounded-xl border border-[var(--line)] bg-[var(--surface)] px-3 py-2">
                <span class="block text-[10px] uppercase tracking-wider text-[var(--muted)]">${escapeHtml(m.label.split(' ')[0])}</span>
                <span class="block text-sm font-bold text-[var(--ink)]">${m.orders} orders</span>
            </span>`).join('');

        drawBars(d.monthly);
        drawBreakdown();

        document.getElementById('ordersTotal').textContent = Number(h.orders.value).toLocaleString();
        deltaPill(document.getElementById('ordersDelta'), h.orders.delta_percent, h.orders.direction);
        document.getElementById('ordersRangeLabel').textContent = d.range.label;
        drawArea(d.monthly);

        const leader = d.by_brand[0];
        document.getElementById('brandTopValue').textContent = leader ? peso(leader.revenue) : peso(0);
        document.getElementById('brandTopName').textContent = leader ? `${leader.brand} — ${leader.share}% of sales` : 'No sales yet';
        document.getElementById('collectionRate').textContent = h.collection_rate + '%';
        document.getElementById('receivables').textContent = peso(d.receivables.outstanding);
        document.getElementById('receivablesNote').textContent =
            `${d.receivables.orders} orders${d.receivables.awaiting_review ? `, ${d.receivables.awaiting_review} payment awaiting review` : ''}`;
        drawDonut(d.by_brand);

        drawScatter(d.inventory_health);
        drawPareto(d.concentration);

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

        document.getElementById('analyticsLoading').classList.add('hidden');
        document.getElementById('analyticsBody').classList.remove('hidden');
    }

    async function loadAnalytics() {
        const months = document.getElementById('rangeSelect').value;
        document.getElementById('analyticsBody').classList.add('hidden');
        document.getElementById('analyticsLoading').classList.remove('hidden');
        document.getElementById('analyticsLoading').textContent = 'Loading analytics...';

        try {
            const response = await fetch(`/admin-api/analytics?months=${months}`, { headers: { Accept: 'application/json' } });

            if (response.status === 401) {
                window.location.href = '/admin/login';
                return;
            }

            const payload = await response.json();
            if (!payload.success) throw new Error(payload.message || 'Failed to load.');

            render(payload.data);
        } catch (error) {
            document.getElementById('analyticsLoading').textContent = 'Could not load analytics. Refresh to try again.';
        }
    }

    loadAnalytics();
</script>
@endpush
