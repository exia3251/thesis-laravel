@extends('layouts.admin')

@section('title', 'Reviews - RANEY LUBRICANTS TRADING')

@section('content')
    <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

    <div class="mb-7">
        <h1 class="text-2xl font-black tracking-tight text-[var(--ink)] sm:text-3xl">Reviews</h1>
        <p class="mt-1 text-sm text-[var(--muted)]">What customers said about the oils they bought.</p>
    </div>

    <section id="reviewCards" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"></section>

    {{-- The shape of opinion, which one average hides: ten fives and ten ones
         average the same as twenty threes. --}}
    <section class="mt-5 rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold text-[var(--ink)]">How the ratings fall</h2>
        <div id="spread" class="mt-4 space-y-2"></div>
    </section>

    <section class="mt-5 rounded-[1.5rem] border border-[var(--line)] bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)] px-6 py-5">
            <div>
                <h2 class="text-base font-bold text-[var(--ink)]">Every review</h2>
                <p class="mt-1 text-xs text-[var(--muted)]">Newest first. Every one came from a delivered order.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" id="reviewSearch" oninput="searchReviews()" placeholder="Search product, customer or words"
                       class="w-full rounded-xl border border-[var(--line)] px-3 py-2 text-sm outline-none focus:border-[var(--primary)] sm:w-64">
                <select id="ratingFilter" onchange="loadReviews(1)"
                        class="rounded-xl border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--ink)] outline-none focus:border-[var(--primary)]">
                    <option value="">All ratings</option>
                    <option value="5">5 stars</option>
                    <option value="4">4 stars</option>
                    <option value="3">3 stars</option>
                    <option value="2">2 stars</option>
                    <option value="1">1 star</option>
                </select>
                <select id="removedFilter" onchange="loadReviews(1)"
                        class="rounded-xl border border-[var(--line)] bg-white px-4 py-2 text-sm font-semibold text-[var(--ink)] outline-none focus:border-[var(--primary)]">
                    <option value="all">On the shop</option>
                    <option value="removed">Removed</option>
                </select>
            </div>
        </div>
        <div id="reviewList" class="divide-y divide-[var(--line)]"></div>
        <div id="reviewPagination"></div>
    </section>

    {{-- Removing one asks for a reason, which goes to the activity log. A
         business that can quietly erase its bad reviews has a review system
         worth nothing. --}}
    <div id="removeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-lg rounded-[1.5rem] bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-[var(--ink)]">Remove this review</h3>
            <p class="mt-1 text-sm text-[var(--muted)]">It comes off the product page. The reason is recorded against your name.</p>

            <div id="removeTarget" class="mt-4 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4 text-sm"></div>

            <form id="removeForm" class="mt-4" onsubmit="submitRemoval(event)">
                <input type="hidden" id="removeId">
                <label class="block text-sm font-semibold text-[var(--ink)]">Why</label>
                <input type="text" id="removeReason" required minlength="4" maxlength="200"
                       placeholder="e.g. abusive language, names a member of staff"
                       class="mt-1.5 w-full rounded-xl border border-[var(--line)] px-3 py-2.5 text-sm outline-none focus:border-[var(--primary)]">
                <p id="removeError" class="mt-1 hidden text-xs text-red-600"></p>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" onclick="closeRemove()" class="rounded-xl border border-[var(--line)] px-5 py-2.5 text-sm font-semibold text-[var(--ink)] transition hover:bg-[var(--surface)]">Cancel</button>
                    <button type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">Remove</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let reviews = [];
    let reviewPage = 1;
    let messageTimeout;

    function showMessage(text, type = 'success') {
        const box = document.getElementById('message');
        const ok = type === 'success';
        box.innerHTML = `<div class="text-sm font-medium ${ok ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>`;
        box.classList.remove('hidden');
        clearTimeout(messageTimeout);
        messageTimeout = setTimeout(() => box.classList.add('hidden'), 3000);
    }

    function card(label, value, note) {
        return `
            <div class="rounded-[1.25rem] border border-[var(--line)] bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-[var(--muted)]">${escapeHtml(label)}</div>
                <div class="mt-3 text-3xl font-black tracking-tight text-[var(--ink)]">${escapeHtml(String(value))}</div>
                <div class="mt-1 text-xs text-[var(--muted)]">${escapeHtml(note)}</div>
            </div>`;
    }

    /* Filled stars up to the rating, hollow after it. Drawn rather than
       written as a number alone, because a rating is read at a glance. */
    function stars(rating) {
        let out = '';
        for (let i = 1; i <= 5; i++) {
            out += `<span class="${i <= rating ? 'text-amber-500' : 'text-[var(--line)]'}">&#9733;</span>`;
        }
        return `<span class="text-base leading-none" title="${rating} out of 5">${out}</span>`;
    }

    async function loadReviews(page = 1) {
        const params = new URLSearchParams({ page, only: document.getElementById('removedFilter').value });
        const rating = document.getElementById('ratingFilter').value;
        const search = document.getElementById('reviewSearch').value.trim();
        if (rating) params.set('rating', rating);
        if (search) params.set('search', search);

        const response = await fetch(`/admin-api/reviews?${params}`, { headers: { Accept: 'application/json' } });
        if (response.status === 401) { window.location.href = '/admin/login'; return; }

        const { data, meta, summary } = await response.json();

        if (!data.length && meta.total > 0 && meta.current_page > meta.last_page) {
            return loadReviews(meta.last_page);
        }

        reviews = data;
        reviewPage = meta.current_page;

        document.getElementById('reviewCards').innerHTML =
            card('Reviews', summary.total, 'on the shop') +
            card('Average', summary.average === null ? '—' : summary.average, 'out of 5') +
            card('Showing', meta.total, 'match these filters') +
            card('Removed', summary.removed, 'taken down');

        const spread = summary.spread || {};
        const highest = Math.max(1, ...Object.values(spread).map(Number));
        document.getElementById('spread').innerHTML = [5, 4, 3, 2, 1].map((n) => {
            const count = Number(spread[n] || 0);
            return `
                <div class="flex items-center gap-3">
                    <div class="w-20 shrink-0 text-xs font-semibold text-[var(--muted)]">${n} star${n === 1 ? '' : 's'}</div>
                    <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-[var(--surface)]">
                        <div class="h-full rounded-full bg-amber-500" style="width:${(count / highest) * 100}%"></div>
                    </div>
                    <div class="w-10 shrink-0 text-right text-xs font-bold tabular-nums text-[var(--ink)]">${count}</div>
                </div>`;
        }).join('');

        document.getElementById('reviewList').innerHTML = reviews.length
            ? reviews.map((r) => `
                <div class="flex flex-wrap items-start justify-between gap-3 px-6 py-4 ${r.removed ? 'opacity-60' : ''}">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            ${stars(r.rating)}
                            <span class="text-sm font-semibold text-[var(--ink)]">${escapeHtml(r.product_name)}</span>
                            ${r.removed ? '<span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">Removed</span>' : ''}
                        </div>
                        ${r.body ? `<p class="mt-1.5 text-sm leading-6 text-[var(--ink)]">${escapeHtml(r.body)}</p>` : '<p class="mt-1.5 text-sm italic text-[var(--muted)]">Rating only, no words.</p>'}
                        <div class="mt-1.5 text-xs text-[var(--muted)]">
                            ${escapeHtml(r.customer)} &middot; ${escapeHtml(r.written || '')}${r.order_no ? ' &middot; order ' + escapeHtml(r.order_no) : ''}
                        </div>
                    </div>
                    ${r.removed
                        ? `<button type="button" data-id="${r.review_id}" onclick="restoreReview(this.dataset.id, this)"
                                   class="shrink-0 rounded-xl border border-[var(--line)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Put back</button>`
                        : `<button type="button" data-id="${r.review_id}" onclick="openRemove(this.dataset.id)"
                                   class="shrink-0 rounded-xl border border-[var(--line)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition hover:border-red-400 hover:text-red-600">Remove</button>`}
                </div>`).join('')
            : '<p class="px-6 py-10 text-center text-sm text-[var(--muted)]">No reviews match that.</p>';

        renderPagination('reviewPagination', meta, loadReviews);
    }

    const searchReviews = debounce(() => loadReviews(1));

    function openRemove(id) {
        const review = reviews.find((r) => String(r.review_id) === String(id));
        if (!review) return;

        document.getElementById('removeId').value = review.review_id;
        document.getElementById('removeReason').value = '';
        document.getElementById('removeError').classList.add('hidden');
        document.getElementById('removeTarget').innerHTML =
            `<div class="font-semibold text-[var(--ink)]">${escapeHtml(review.product_name)} &middot; ${review.rating}/5</div>`
            + `<div class="mt-1 text-[var(--muted)]">${escapeHtml(review.body || 'Rating only, no words.')}</div>`;

        const modal = document.getElementById('removeModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeRemove() {
        const modal = document.getElementById('removeModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function submitRemoval(event) {
        event.preventDefault();
        const id = document.getElementById('removeId').value;

        await withBusy(busyButtonOf(event.target), async () => {
            const response = await fetch(`/admin-api/reviews/${id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify({ reason: document.getElementById('removeReason').value }),
            });

            const payload = await response.json();

            if (!response.ok) {
                const box = document.getElementById('removeError');
                box.textContent = payload.errors ? Object.values(payload.errors).flat().join(' ') : (payload.message || 'Could not remove it.');
                box.classList.remove('hidden');
                return;
            }

            closeRemove();
            showMessage(payload.message || 'Removed.');
            await loadReviews(reviewPage);
        }, 'Removing');
    }

    async function restoreReview(id, button) {
        await withBusy(button, async () => {
            const response = await fetch(`/admin-api/reviews/${id}/restore`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            });
            const payload = await response.json();
            showMessage(payload.message || 'Done.', response.ok ? 'success' : 'error');
            await loadReviews(reviewPage);
        }, 'Restoring');
    }

    loadReviews();
</script>
@endpush
