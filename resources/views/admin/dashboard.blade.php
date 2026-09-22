@extends('layouts.admin')

@section('title', 'Dashboard - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">Dashboard</h1>
            <p class="mt-1 text-sm text-[var(--muted)]">Recent trade, and anything that needs doing today.</p>
        </div>
        <div class="flex items-center gap-2">
            <select id="rangeSelect" onchange="loadDashboard()"
                    class="rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ink)] outline-none transition focus:border-[var(--primary)]">
                <option value="7">Last 7 days</option>
                <option value="30" selected>Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
            <a href="/admin/analytics" class="rounded-xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Open Analytics</a>
        </div>
    </div>

    <div id="dashLoading" class="rounded-[1.5rem] border border-[var(--line)] bg-white p-16 text-center text-sm text-[var(--muted)]">
        Loading dashboard...
    </div>

    <div id="dashBody" class="hidden space-y-5">
        <section id="statCards" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"></section>

        <section class="grid gap-5 lg:grid-cols-[1.35fr_1fr]">
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-[var(--ink)]">Monthly collections</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Money actually received, last six months</p>
                <div id="monthlyBars" class="mt-6 space-y-5"></div>
            </div>

            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-[var(--ink)]">Order status</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Where every order currently sits</p>
                <div id="orderStatus" class="mt-6 space-y-3"></div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-bold text-[var(--ink)]">Out of stock</h2>
                    <span id="outOfStockCount" class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800"></span>
                </div>
                <div id="outOfStock" class="mt-4 space-y-2"></div>
            </div>

            <div class="rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-bold text-[var(--ink)]">Running low</h2>
                    <span id="lowStockCount" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800"></span>
                </div>
                <div id="lowStock" class="mt-4 space-y-2"></div>
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
    };

    const STATUS_TONES = {
        amber:   { dot: '#d97706', text: 'text-amber-800' },
        sky:     { dot: '#0284c7', text: 'text-sky-800' },
        violet:  { dot: '#7c3aed', text: 'text-violet-800' },
        emerald: { dot: '#148a67', text: 'text-emerald-800' },
        slate:   { dot: '#8b97a5', text: 'text-slate-700' },
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
                    <div class="mt-4 text-3xl font-black tracking-tight text-[var(--ink)]">
                        ${card.format === 'money' ? pesoShort(card.value) : Number(card.value).toLocaleString()}
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">${delta}</div>
                </div>`;
        }).join('');
    }

    /** Month label, value, and a bar underneath - the reference's shape. */
    function renderMonthly(series) {
        const peak = Math.max(...series.map(m => m.collected), 1);

        document.getElementById('monthlyBars').innerHTML = series.map(m => `
            <div>
                <div class="flex items-end justify-between gap-3">
                    <span class="text-sm font-semibold text-[var(--ink)]">${escapeHtml(m.label)}</span>
                    <span class="text-xs text-[var(--muted)]">${peso(m.collected)} &middot; ${m.orders} orders</span>
                </div>
                <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-[var(--surface)]" title="${escapeHtml(m.full_label)}">
                    <div class="h-full rounded-full bg-[var(--primary)]" style="width:${(m.collected / peak) * 100}%"></div>
                </div>
            </div>`).join('');
    }

    function renderStatus(rows) {
        document.getElementById('orderStatus').innerHTML = rows.map(row => {
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
    }

    function renderStockList(containerId, countId, items, total, tone) {
        const badge = document.getElementById(countId);
        badge.textContent = total;
        badge.classList.toggle('hidden', !total);

        document.getElementById(containerId).innerHTML = items.length
            ? items.map(item => `
                <div class="flex items-center justify-between gap-3 rounded-xl border border-[var(--line)] px-3 py-2.5">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-medium text-[var(--ink)]" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</div>
                        <div class="text-xs text-[var(--muted)]">${escapeHtml(item.brand)}</div>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ${tone}">
                        ${item.quantity} left
                    </span>
                </div>`).join('')
              + (total > items.length ? `<p class="pt-1 text-xs text-[var(--muted)]">and ${total - items.length} more</p>` : '')
            : '<p class="rounded-xl border border-[var(--line)] p-4 text-center text-xs text-[var(--muted)]">Nothing here right now.</p>';
    }

    async function loadDashboard() {
        const days = document.getElementById('rangeSelect').value;
        document.getElementById('dashBody').classList.add('hidden');
        document.getElementById('dashLoading').classList.remove('hidden');
        document.getElementById('dashLoading').textContent = 'Loading dashboard...';

        try {
            const response = await fetch(`/admin-api/dashboard/stats?days=${days}`, { headers: { Accept: 'application/json' } });

            if (response.status === 401) {
                window.location.href = '/admin/login';
                return;
            }

            const payload = await response.json();
            if (!payload.success) throw new Error('Failed to load.');

            const d = payload.data;
            renderCards(d.cards);
            renderMonthly(d.monthly_revenue);
            renderStatus(d.order_status);
            renderStockList('outOfStock', 'outOfStockCount', d.attention.out_of_stock, d.attention.out_of_stock_total, 'bg-red-100 text-red-800');
            renderStockList('lowStock', 'lowStockCount', d.attention.low_stock, d.attention.low_stock_total, 'bg-amber-100 text-amber-800');

            document.getElementById('dashLoading').classList.add('hidden');
            document.getElementById('dashBody').classList.remove('hidden');
        } catch (error) {
            document.getElementById('dashLoading').textContent = 'Could not load the dashboard. Refresh to try again.';
        }
    }

    loadDashboard();
</script>
@endpush
