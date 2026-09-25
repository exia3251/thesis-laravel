@extends('layouts.bare')

@section('title', $sale->reference() . ' - ' . config('business.name'))

@push('styles')
<style>
    /* On screen this is a page about an order. On paper it should be the
       receipt and nothing else, so the print rules strip the surrounding
       interface rather than merely hiding a few parts of it. */
    @media print {
        @page {
            margin: 14mm;
        }

        .no-print,
        .no-print * {
            display: none !important;
        }

        html, body {
            background: #fff !important;
        }

        body {
            font-size: 11pt;
            color: #000;
        }

        /* Gradients, shadows and deep rounding read as smudges in print. */
        .receipt-surface,
        .receipt-panel {
            background: #fff !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            border-color: #999 !important;
        }

        .receipt-surface {
            border: 0 !important;
            padding: 0 !important;
            max-width: none !important;
        }

        .receipt-page {
            background: #fff !important;
            padding: 0 !important;
            min-height: 0 !important;
        }

        .receipt-panel {
            border: 1px solid #bbb !important;
            padding: 10px 12px !important;
        }

        /* Screen greys are too light to read once printed. */
        .receipt-surface .text-\[var\(--muted\)\] {
            color: #444 !important;
        }

        .receipt-surface table {
            page-break-inside: auto;
        }

        .receipt-surface tr {
            page-break-inside: avoid;
        }

        .receipt-surface thead {
            display: table-header-group;
        }

        .print-only {
            display: block !important;
        }
    }

    .print-only {
        display: none;
    }

    /* Only ever drawn on paper. Fixed so it repeats on a multi-page order,
       and behind the content so nothing becomes unreadable. */
    .cancelled-watermark {
        display: none;
    }

    @media print {
        .cancelled-watermark {
            display: block !important;
            position: fixed;
            top: 42%;
            left: 0;
            right: 0;
            z-index: 0;
            text-align: center;
            font-size: 72pt;
            font-weight: 900;
            letter-spacing: 0.15em;
            color: rgba(0, 0, 0, 0.08);
            transform: rotate(-24deg);
            pointer-events: none;
        }

        .receipt-surface {
            position: relative;
            z-index: 1;
        }
    }
</style>
@endpush

@section('content')
    <div class="receipt-page min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)]">
        <div class="max-w-5xl mx-auto px-4 py-8">
            <div id="message" class="no-print fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

            @php
                $business = config('business');
                $tax = $sale->taxBreakdown();
                $timeline = $sale->paymentTimeline();
                $money = fn ($amount) => 'PHP ' . number_format((float) $amount, 2);
            @endphp

            <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-3">
                <a href="/orders" class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--muted)] transition hover:text-[var(--primary)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                    Back to my orders
                </a>
                <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-[var(--primary)] px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829v-3.45m0 3.45a3 3 0 0 0 3 3h4.56a3 3 0 0 0 3-3m-10.56 0a3 3 0 0 1-3-3v-2.16a3 3 0 0 1 3-3h10.56a3 3 0 0 1 3 3v2.16a3 3 0 0 1-3 3m-10.56 0V6.75a3 3 0 0 1 3-3h4.56a3 3 0 0 1 3 3v3.63m0 3.45v3.42a3 3 0 0 1-3 3h-4.56a3 3 0 0 1-3-3v-3.42"/></svg>
                    Print
                </button>
            </div>

            {{-- Only drawn on paper, where a cancelled order could otherwise
                 be mistaken for a live one once it leaves the screen. --}}
            @if ($sale->isCancelled())
                <div class="cancelled-watermark" aria-hidden="true">CANCELLED</div>
            @endif

            <div class="receipt-surface rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm sm:p-8">

                <header class="flex flex-col gap-5 border-b border-[var(--line)] pb-6 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-[var(--ink)]">{{ $business['name'] }}</h1>
                        {{-- Shown on screen as well as on paper. A customer
                             reading this is often about to ring up about the
                             very order in front of them. --}}
                        <div class="mt-2 space-y-0.5 text-xs leading-5 text-[var(--muted)]">
                            <div>{{ $business['address'] }}</div>
                            <div>{{ $business['phone'] }} &middot; {{ $business['email'] }}</div>
                            @if (filled($business['tin']))
                                <div>TIN {{ $business['tin'] }}</div>
                            @endif
                            @if ($business['is_official'] && filled($business['atp_number']))
                                <div>ATP {{ $business['atp_number'] }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="shrink-0 sm:text-right">
                        <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-[var(--primary)]">{{ $business['document_title'] }}</div>

                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="sm:flex sm:justify-end sm:gap-3">
                                <dt class="text-xs text-[var(--muted)] sm:self-center">Order no.</dt>
                                <dd class="font-mono font-bold text-[var(--ink)]">{{ $sale->reference() }}</dd>
                            </div>
                            <div class="sm:flex sm:justify-end sm:gap-3">
                                <dt class="text-xs text-[var(--muted)] sm:self-center">Receipt no.</dt>
                                <dd class="font-mono {{ $sale->receipt_no ? 'font-bold text-[var(--ink)]' : 'text-xs italic text-[var(--muted)]' }}">
                                    {{ $sale->receipt_no ?: 'issued once paid in full' }}
                                </dd>
                            </div>
                            @if ($sale->delivery_no)
                                <div class="sm:flex sm:justify-end sm:gap-3">
                                    <dt class="text-xs text-[var(--muted)] sm:self-center">Delivery no.</dt>
                                    <dd class="font-mono font-bold text-[var(--ink)]">{{ $sale->delivery_no }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </header>

                @if ($sale->isCancelled())
                    <div class="mt-6 rounded-xl border-2 border-slate-400 bg-slate-100 px-5 py-3 text-center">
                        <span class="text-sm font-black uppercase tracking-[0.3em] text-slate-700">Cancelled</span>
                        @if ($sale->cancelled_at)
                            <span class="ml-2 text-sm text-slate-600">on {{ $sale->cancelled_at->format('j F Y') }}</span>
                        @endif
                    </div>
                @endif

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div class="receipt-panel rounded-[1.25rem] border border-[var(--line)] p-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--muted)]">Deliver to</p>
                        <p class="mt-2 font-bold text-[var(--ink)]">{{ $sale->customer_name ?: $sale->user?->full_name }}</p>
                        <p class="mt-1.5 text-sm leading-6 text-[var(--ink)]">{{ $sale->delivery_address ?: 'No address recorded.' }}</p>
                        @if ($sale->contact_phone)
                            <p class="mt-1 text-sm text-[var(--muted)]">{{ $sale->contact_phone }}</p>
                        @endif
                    </div>

                    <div class="receipt-panel rounded-[1.25rem] border border-[var(--line)] p-5">
                        <dl class="space-y-2.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--muted)]">Order date</dt>
                                <dd class="text-right font-medium text-[var(--ink)]">{{ optional($sale->sale_date)->format('j F Y, g:i A') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--muted)]">Payment</dt>
                                <dd class="text-right font-medium text-[var(--ink)]">{{ $sale->planLabel() }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--muted)]">Delivery</dt>
                                <dd class="text-right font-medium text-[var(--ink)]">{{ $sale->statusLabel() }}</dd>
                            </div>
                            @if ($sale->received_at)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-[var(--muted)]">Received</dt>
                                    <dd class="text-right font-medium text-emerald-700">{{ $sale->received_at->format('j F Y') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-[1.25rem] border border-[var(--line)]">
                    <table class="min-w-full">
                        <thead class="bg-[var(--surface)]">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-[var(--muted)]">Item</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-[0.18em] text-[var(--muted)]">Qty</th>
                                <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-[0.18em] text-[var(--muted)]">Unit price</th>
                                <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-[0.18em] text-[var(--muted)]">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--line)]">
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-[var(--ink)]">{{ $item->product?->product_name ?? 'Item no longer listed' }}</div>
                                        @if ($item->product)
                                            <div class="text-xs text-[var(--muted)]">{{ $item->product->brand }} &middot; {{ $item->product->unit }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center tabular-nums">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-[var(--muted)]">{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-medium tabular-nums text-[var(--ink)]">{{ number_format((float) $item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- A seller registered for VAT must show the tax separately;
                     one below the threshold must say it is not registered.
                     Showing neither is the one thing a document cannot do. --}}
                <div class="mt-5 flex justify-end">
                    <dl class="w-full max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between gap-6">
                            <dt class="text-[var(--muted)]">{{ $tax['registered'] ? 'VATable sales' : 'Subtotal' }}</dt>
                            <dd class="tabular-nums text-[var(--ink)]">{{ $money($tax['subtotal']) }}</dd>
                        </div>
                        @if ($tax['registered'])
                            <div class="flex justify-between gap-6">
                                <dt class="text-[var(--muted)]">VAT ({{ (int) $tax['rate'] }}%)</dt>
                                <dd class="tabular-nums text-[var(--ink)]">{{ $money($tax['vat']) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-6 border-t border-[var(--line)] pt-2.5">
                            <dt class="font-bold text-[var(--ink)]">Total</dt>
                            <dd class="text-lg font-black tabular-nums text-[var(--ink)]">{{ $money($tax['total']) }}</dd>
                        </div>
                        @unless ($tax['registered'])
                            <p class="pt-1 text-right text-[11px] font-bold uppercase tracking-[0.14em] text-[var(--muted)]">Non-VAT registered</p>
                        @endunless
                    </dl>
                </div>

                {{-- One sequence rather than a summary beside a list of
                     requests, which left the customer reconciling the two on
                     exactly the split payments this shop encourages. --}}
                <div class="mt-7">
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--muted)]">Payment</h2>

                    <ol class="mt-3 divide-y divide-[var(--line)] rounded-[1.25rem] border border-[var(--line)]">
                        @foreach ($timeline as $row)
                            @php
                                $tone = match ($row['state']) {
                                    'in'      => ['text-emerald-700', 'Paid'],
                                    'pending' => ['text-sky-700', 'Being checked'],
                                    'void'    => ['text-slate-400 line-through', 'Not accepted'],
                                    'due'     => ['text-amber-700', 'Due'],
                                    'out'     => ['text-violet-700', 'Returned to you'],
                                    default   => ['text-[var(--ink)]', null],
                                };
                            @endphp
                            <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-4 py-3">
                                <div class="min-w-0">
                                    <span class="text-sm font-medium text-[var(--ink)]">{{ $row['label'] }}</span>
                                    @if ($row['detail'])
                                        <span class="ml-2 font-mono text-xs text-[var(--muted)]">{{ $row['detail'] }}</span>
                                    @endif
                                    @if ($tone[1])
                                        <span class="ml-2 text-xs font-semibold {{ $row['state'] === 'void' ? 'text-slate-500' : $tone[0] }}">{{ $tone[1] }}</span>
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-baseline gap-4">
                                    @if ($row['date'])
                                        <span class="text-xs text-[var(--muted)]">{{ $row['date']->format('j M Y') }}</span>
                                    @endif
                                    <span class="w-28 text-right text-sm font-semibold tabular-nums {{ $tone[0] }}">{{ $money($row['amount']) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                @if ($sale->delivery_proof_path)
                    {{-- The customer never saw this. It is the evidence their
                         order arrived, and showing it settles most of the
                         arguments that would otherwise be had by phone. --}}
                    <div class="mt-7">
                        <h2 class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--muted)]">Proof of delivery</h2>
                        <div class="mt-3 flex flex-wrap items-center gap-4 rounded-[1.25rem] border border-[var(--line)] p-4">
                            <img src="{{ asset('storage/' . $sale->delivery_proof_path) }}" alt="Photograph taken at handover"
                                 class="h-24 w-24 rounded-xl border border-[var(--line)] object-cover">
                            <div class="min-w-0 text-sm text-[var(--muted)]">
                                <p>Photographed at handover{{ $sale->delivery_no ? ' against ' . $sale->delivery_no : '' }}.</p>
                                <a href="{{ asset('storage/' . $sale->delivery_proof_path) }}" target="_blank"
                                   class="no-print mt-1 inline-block font-semibold text-[var(--primary)] hover:underline">View full size</a>
                            </div>
                        </div>
                    </div>
                @endif

                <footer class="mt-7 border-t border-[var(--line)] pt-5 text-xs leading-6 text-[var(--muted)]">
                    @unless ($business['is_official'])
                        <p class="font-semibold text-[var(--ink)]">{{ $business['disclaimer'] }}</p>
                    @endunless
                    @unless ($tax['registered'])
                        <p>This document is not valid for claim of input tax.</p>
                    @endunless
                    <p class="mt-2">Thank you for your business. Keep this for your records.</p>
                </footer>
            </div>

            @php
                $pendingRequest = $sale->paymentRequests->firstWhere('status', 'processing');
                    $canCancel = $sale->canBeCancelledByCustomer();
                    $canConfirm = !$sale->isCancelled() && !$sale->received_at && !$sale->isDelivered();
                @endphp

                @if ($sale->isCancelled())
                    <div class="mt-8 rounded-[1.5rem] border-2 border-slate-300 bg-slate-50 p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="shrink-0 rounded-2xl bg-slate-200 p-3">
                                <svg class="h-6 w-6 text-slate-700" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </div>
                            {{-- The date and the refund are on the receipt
                                 above now, so this carries only the reason,
                                 which has nowhere else to go. --}}
                            <div class="flex-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-600">Why this was cancelled</p>
                                <p class="mt-2 text-sm leading-6 text-slate-800">
                                    {{ $sale->cancellation_reason ?: 'No reason was recorded.' }}
                                </p>
                                @if ($sale->refund_status === 'pending')
                                    <p class="mt-3 text-sm leading-6 text-amber-900">Our staff will send the refund to your GCash.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ($canCancel || $canConfirm)
                    <div class="mt-8 rounded-[1.5rem] border border-[var(--line)] bg-[var(--card)] p-5 no-print">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Manage this order</p>
                        <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                            @if ($canConfirm)
                                <button type="button" onclick="askConfirmReceipt()" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border-2 border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Received
                                </button>
                            @endif
                            @if ($canCancel)
                                <button type="button" onclick="askCancelOrder()" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border-2 border-red-200 bg-white px-4 py-3 text-sm font-bold text-red-700 transition hover:bg-red-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    Cancel
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($pendingRequest)
                    {{-- Only one payment may be under review at a time, so show its
                         status rather than a form that would be refused. --}}
                    <div id="payment-request" class="mt-8 rounded-[1.5rem] border-2 border-sky-200 bg-sky-50 p-5 no-print">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="rounded-xl bg-sky-100 p-3">
                                <svg class="h-6 w-6 text-sky-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">Waiting for confirmation</p>
                                <h3 class="mt-2 text-xl font-bold text-sky-900">PHP {{ number_format((float) $pendingRequest->amount, 2) }} is under review.</h3>
                                <p class="mt-2 text-sm leading-7 text-sky-800">
                                    Submitted {{ $pendingRequest->created_at?->diffForHumans() }}@if ($pendingRequest->reference_no) under reference <strong>{{ $pendingRequest->reference_no }}</strong>@endif.
                                    An administrator will confirm it against the GCash record, and your balance updates once they do.
                                </p>
                                @if ($pendingRequest->proof_image_path)
                                    <a href="{{ asset('storage/' . $pendingRequest->proof_image_path) }}" target="_blank" class="mt-3 inline-block text-sm font-semibold text-sky-700 hover:underline">View the receipt you sent</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif ((float) $sale->balance_due > 0)
                    <div id="payment-request" class="mt-8 rounded-[1.5rem] border border-[var(--line)] bg-[var(--card)] p-5 no-print">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-xl">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Submit Payment Request</p>
                                @php
                                    $gcashDue = $sale->gcashOutstanding();
                                    $extraFloor = min((float) config('payments.minimum_extra_payment', 500), (float) $sale->balance_due);
                                @endphp

                                <h3 class="mt-2 text-xl font-bold text-[var(--ink)]">
                                    {{ $gcashDue > 0 ? 'Send your GCash payment for this order.' : 'Settle part of your delivery balance early.' }}
                                </h3>
                                <p class="mt-2 text-sm leading-7 text-[var(--muted)]">Scan the QR, send the payment, then upload the receipt with its reference number. An administrator confirms it before it counts against your balance.</p>

                                <dl class="mt-4 space-y-2 rounded-xl border border-[var(--line)] bg-white/70 p-4 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-[var(--muted)]">Order total</dt>
                                        <dd class="font-semibold text-[var(--ink)]">PHP {{ number_format((float) $sale->total_amount, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-[var(--muted)]">Already paid</dt>
                                        <dd class="font-semibold text-[var(--ink)]">PHP {{ number_format((float) $sale->paid_amount, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between border-t border-[var(--line)] pt-2">
                                        <dt class="text-[var(--muted)]">Remaining balance</dt>
                                        <dd class="font-bold text-[var(--ink)]">PHP {{ number_format((float) $sale->balance_due, 2) }}</dd>
                                    </div>
                                </dl>

                                @if ($gcashDue > 0)
                                    <div class="mt-4 rounded-xl border-2 border-[var(--primary)] bg-[var(--primary-soft)] p-4">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--primary)]">Send exactly</div>
                                        <div class="mt-1 text-3xl font-black text-[var(--primary)]">PHP {{ number_format($gcashDue, 2) }}</div>
                                        <p class="mt-2 text-xs leading-5 text-[#0f6b50]">
                                            This is the GCash portion you chose at checkout. Send it in one transfer.
                                            @if ($sale->codAmount() > 0)
                                                The remaining PHP {{ number_format($sale->codAmount(), 2) }} is paid in cash when your order arrives.
                                            @endif
                                        </p>
                                    </div>
                                @else
                                    <div class="mt-4 rounded-xl border border-[var(--accent-soft)] bg-[rgba(255,252,243,0.92)] p-4 text-sm leading-6 text-[#715b1d]">
                                        Your GCash payment is settled. The remaining PHP {{ number_format((float) $sale->balance_due, 2) }} is due in cash on delivery &mdash; paying it early is optional, and must be at least PHP {{ number_format($extraFloor, 2) }}.
                                    </div>
                                @endif
                            </div>
                            <div id="gcashQrPanel" class="w-full max-w-xs rounded-[1.5rem] border border-[var(--line)] bg-white/85 p-4 text-center {{ $sale->payment_method === 'gcash' ? '' : 'hidden' }}">
                                <img src="{{ asset('images/gcash-qr-placeholder.svg') }}" alt="GCash QR" class="mx-auto h-56 w-56 rounded-2xl border border-[var(--line)] object-cover">
                                <p class="mt-3 text-xs uppercase tracking-[0.22em] text-[var(--muted)]">GCash QR</p>
                                <p class="mt-2 text-sm text-[var(--muted)]">Replace this placeholder with the real staff QR later.</p>
                            </div>
                        </div>

                        <form id="paymentRequestForm" class="mt-6 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-[var(--ink)]">Payment Method</label>
                                <input id="request_payment_method_label" type="text" value="GCash" class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-slate-100 px-4 py-3 text-slate-600" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[var(--ink)]">Amount</label>
                                {{-- Editable even when there is a down payment due. It
                                     was locked to the whole committed amount, which no
                                     GCash wallet can send past PHP 100,000. --}}
                                <input id="request_amount" type="number" step="0.01"
                                       min="{{ $extraFloor }}" max="{{ (float) $sale->balance_due }}"
                                       value="{{ $gcashDue > 0 ? $gcashDue : (float) $sale->balance_due }}"
                                       class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3 font-semibold outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]">
                                @if ($gcashDue > 0)
                                    <p class="mt-2 text-xs leading-5 text-[var(--muted)]">
                                        PHP {{ number_format($gcashDue, 2) }} is due online before delivery.
                                        @if ($extraFloor < $gcashDue)
                                            Send it in one go, or in parts of at least PHP {{ number_format($extraFloor, 2) }}
                                            &mdash; each part needs its own reference number and screenshot.
                                        @else
                                            Send it in one transfer.
                                        @endif
                                    </p>
                                @else
                                    <p class="mt-2 text-xs text-[var(--muted)]">At least PHP {{ number_format($extraFloor, 2) }}, up to PHP {{ number_format((float) $sale->balance_due, 2) }}.</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[var(--ink)]">Reference Number</label>
                                <input id="request_reference_no" type="text" maxlength="100" minlength="6" pattern="[A-Za-z0-9][A-Za-z0-9\-]{5,99}" title="Use at least 6 characters. Letters, numbers, and hyphens only." class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3 outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Required GCash reference number" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[var(--ink)]">Proof Image</label>
                                <input id="request_proof_image" type="file" accept="image/*" class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3">
                                <p class="mt-2 text-xs text-[var(--muted)]">
                                    {{ strtoupper(implode(', ', config('payments.proof.mimes'))) }} &middot;
                                    up to {{ round(config('payments.proof.max_kilobytes') / 1024) }} MB &middot;
                                    at least {{ config('payments.proof.min_width') }}&times;{{ config('payments.proof.min_height') }} pixels so the reference stays readable.
                                </p>
                            </div>
                            <div class="md:col-span-2 flex justify-end">
                                <button type="submit" class="rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110">Submit Payment Request</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>

    <div class="no-print">
        @include('partials.customer-footer')
    </div>


    <div id="actionModal" class="hidden fixed inset-0 z-50 bg-black/60 overflow-y-auto no-print">
        <div class="mx-auto my-20 w-full max-w-md rounded-[1.5rem] bg-white shadow-2xl">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div id="amIconWrap" class="shrink-0 rounded-2xl p-3">
                        <svg id="amIcon" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 id="amTitle" class="text-xl font-black text-[var(--ink)]"></h2>
                        <p id="amBody" class="mt-2 text-sm leading-6 text-[var(--muted)]"></p>
                    </div>
                </div>
                <div id="amNoteWrap" class="mt-4 hidden">
                    <label class="block text-sm font-medium text-[var(--ink)]">Reason <span class="font-normal text-[var(--muted)]">(optional)</span></label>
                    <textarea id="amNote" rows="2" maxlength="500" placeholder="Tell us why, so we can improve"
                              class="mt-2 block w-full resize-none rounded-xl border border-[var(--line)] bg-white px-3 py-2.5 text-sm outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]"></textarea>
                </div>
                <p id="amError" class="mt-3 hidden text-xs font-semibold text-red-600"></p>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row">
                    <button type="button" onclick="closeActionModal()" class="flex-1 rounded-xl border border-[var(--line)] bg-white px-4 py-3 text-sm font-semibold text-[var(--muted)] transition hover:bg-[var(--surface)] hover:text-[var(--ink)]">Go back</button>
                    <button id="amConfirm" type="button" class="flex-1 rounded-xl px-4 py-3 text-sm font-bold text-white transition"></button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            const isSuccess = type === 'success';
            box.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="rounded-xl ${isSuccess ? 'bg-emerald-100' : 'bg-red-100'} p-2">
                        ${isSuccess
                            ? '<svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
                            : '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'}
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] ${isSuccess ? 'text-emerald-700' : 'text-red-700'}">${isSuccess ? 'Payment Request' : 'Action Needed'}</div>
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${escapeHtml(text)}</div>
                    </div>
                </div>
            `;
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 3200);
        }

        const paymentRequestForm = document.getElementById('paymentRequestForm');
        const referenceInput = document.getElementById('request_reference_no');
        const gcashQrPanel = document.getElementById('gcashQrPanel');

        function syncPaymentRequestUi() {
            if (!referenceInput || !gcashQrPanel) {
                return;
            }

            const isGcash = !gcashQrPanel.classList.contains('hidden');
            referenceInput.required = isGcash;
        }

        if (paymentRequestForm) {
            syncPaymentRequestUi();

            paymentRequestForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const formData = new FormData();
                formData.append('payment_method', 'gcash');
                formData.append('amount', document.getElementById('request_amount').value);
                formData.append('reference_no', document.getElementById('request_reference_no').value);

                const proofFile = document.getElementById('request_proof_image').files[0];
                if (proofFile) {
                    formData.append('proof_image', proofFile);
                }

                const response = await fetch('/shop-api/orders/{{ $sale->sale_id }}/payment-requests', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();
                showMessage(data.message || 'Payment request submitted.', response.ok ? 'success' : 'error');

                if (response.ok) {
                    setTimeout(() => window.location.reload(), 900);
                }
            });
        }

        // ---- Confirmation panel -----------------------------------------
        const SALE_ID = @json($sale->sale_id);
        const SALE_BALANCE = @json((float) $sale->balance_due);
        const SALE_PAID = @json((float) $sale->paid_amount);
        const ACTION_ICONS = {
            check: 'm4.5 12.75 6 6 9-13.5',
            cross: 'M6 18 18 6M6 6l12 12',
        };

        let pendingAction = null;

        function openActionModal({ title, body, confirmLabel, tone, icon, withNote, onConfirm }) {
            pendingAction = onConfirm;
            document.getElementById('amTitle').textContent = title;
            document.getElementById('amBody').textContent = body;
            document.getElementById('amIcon').innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="${ACTION_ICONS[icon]}"/>`;
            document.getElementById('amIconWrap').className = `shrink-0 rounded-2xl p-3 ${tone.wrap}`;
            document.getElementById('amIcon').classList.value = `h-6 w-6 ${tone.icon}`;

            const confirmButton = document.getElementById('amConfirm');
            confirmButton.textContent = confirmLabel;
            confirmButton.className = `flex-1 rounded-xl px-4 py-3 text-sm font-bold text-white transition ${tone.button}`;
            confirmButton.onclick = runPendingAction;

            document.getElementById('amNoteWrap').classList.toggle('hidden', !withNote);
            document.getElementById('amNote').value = '';
            document.getElementById('amError').classList.add('hidden');
            document.getElementById('actionModal').classList.remove('hidden');
        }

        function closeActionModal() {
            document.getElementById('actionModal').classList.add('hidden');
            pendingAction = null;
        }

        async function runPendingAction() {
            if (!pendingAction) return;

            const button = document.getElementById('amConfirm');
            const original = button.textContent;
            button.disabled = true;
            button.textContent = 'Working...';

            try {
                const result = await pendingAction(document.getElementById('amNote').value.trim() || null);

                if (result.ok) {
                    showMessage(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1200);
                    return;
                }

                const error = document.getElementById('amError');
                error.textContent = result.message;
                error.classList.remove('hidden');
            } finally {
                button.disabled = false;
                button.textContent = original;
            }
        }

        document.getElementById('actionModal').addEventListener('click', (event) => {
            if (event.target.id === 'actionModal') closeActionModal();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeActionModal();
        });

        function askConfirmReceipt() {
            openActionModal({
                title: `Confirm order #${SALE_ID} arrived?`,
                body: SALE_BALANCE > 0
                    ? `We will record that you received this order. The remaining PHP ${SALE_BALANCE.toFixed(2)} is still due and our staff will record it once collected.`
                    : 'We will record that you received this order and mark it complete.',
                confirmLabel: 'Yes, it arrived',
                icon: 'check',
                tone: { wrap: 'bg-emerald-100', icon: 'text-emerald-700', button: 'bg-emerald-600 hover:bg-emerald-700' },
                withNote: false,
                onConfirm: async () => {
                    const response = await fetch(`/shop-api/orders/${SALE_ID}/receipt`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    return { ok: response.ok, message: data.message || 'Could not confirm this order.' };
                }
            });
        }

        function askCancelOrder() {
            openActionModal({
                title: `Cancel order #${SALE_ID}?`,
                body: SALE_PAID > 0
                    ? `The items go back into stock and the PHP ${SALE_PAID.toFixed(2)} you have already paid will be refunded to your GCash by our staff.`
                    : 'The items go back into stock. Nothing has been paid on this order, so there is nothing to refund.',
                confirmLabel: 'Cancel this order',
                icon: 'cross',
                tone: { wrap: 'bg-red-100', icon: 'text-red-700', button: 'bg-red-600 hover:bg-red-700' },
                withNote: true,
                onConfirm: async (reason) => {
                    const response = await fetch(`/shop-api/orders/${SALE_ID}/cancel`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ reason })
                    });
                    const data = await response.json();
                    return { ok: response.ok, message: data.message || 'Could not cancel this order.' };
                }
            });
        }

</script>
@endpush
