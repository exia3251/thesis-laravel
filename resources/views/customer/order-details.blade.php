@extends('layouts.bare')

@section('title', 'Order Receipt - RANEY LUBRICANTS TRADING')

@push('styles')
<style>
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white !important;
        }
    }
</style>
@endpush

@section('content')
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)]">
        <div class="max-w-5xl mx-auto px-4 py-8">
            <div id="message" class="fixed bottom-6 right-6 z-50 hidden max-w-sm rounded-2xl border border-[var(--line)] bg-white/95 p-4 shadow-2xl backdrop-blur"></div>

            <div class="no-print mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="/shop" class="group block">
                        <div class="text-3xl font-black tracking-tight">
                            <span class="text-[var(--primary)]">RANEY</span>
                            <span class="text-[var(--accent)]"> LUBRICANTS</span>
                        </div>
                        <div class="mt-1 text-[11px] uppercase tracking-[0.32em] text-[var(--muted)]">Trading</div>
                    </a>
                    <h1 class="mt-4 text-3xl font-black text-[var(--ink)]">Order Receipt</h1>
                    <p class="mt-2 text-sm leading-7 text-[var(--muted)]">Review and print your purchase summary with saved delivery information and payment status.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="/orders" class="rounded-xl border border-[var(--line)] bg-white/80 px-5 py-3 text-sm font-semibold text-[var(--ink)] transition hover:border-[var(--primary)] hover:text-[var(--primary)]">Back to Orders</a>
                    <button onclick="window.print()" class="rounded-xl bg-[var(--primary)] px-5 py-3 text-sm font-semibold text-white transition hover:brightness-110">Print Receipt</button>
                </div>
            </div>

            <div class="rounded-[2rem] border border-[var(--line)] bg-[var(--card-solid)] p-8 shadow-xl">
                <div class="mb-6 flex flex-col gap-4 border-b border-[var(--line)] pb-6 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-[var(--ink)]">RANEY LUBRICANTS TRADING</h2>
                        <p class="mt-1 text-sm text-[var(--muted)]">Official Sales Receipt</p>
                    </div>
                    <div class="text-left md:text-right">
                        <p class="text-sm text-[var(--muted)]">Receipt No.</p>
                        <p class="text-xl font-semibold text-[var(--ink)]">#{{ $sale->sale_id }}</p>
                    </div>
                </div>

                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-[1.5rem] border border-[var(--line)] bg-[var(--card)] p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Customer</p>
                        <p class="mt-2 font-semibold text-[var(--ink)]">{{ $sale->customer_name ?? $sale->user?->full_name }}</p>
                        <p class="mt-2 text-sm text-[var(--muted)]">Payment Method: {{ ucwords(str_replace('_', ' ', $sale->payment_method ?? 'N/A')) }}</p>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Delivery Address</p>
                        <p class="mt-2 text-sm leading-7 text-[var(--ink)]">{{ $sale->delivery_address ?? 'No address saved.' }}</p>
                        <p class="mt-1 text-sm text-[var(--muted)]">{{ $sale->contact_phone ?? 'No contact phone saved.' }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-[var(--line)] bg-[var(--card)] p-5 md:text-right">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Order Date</p>
                        <p class="mt-2 font-semibold text-[var(--ink)]">{{ optional($sale->sale_date)->format('F d, Y h:i A') }}</p>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Delivery Status</p>
                        <p class="mt-2 font-semibold text-[var(--ink)]">{{ ucwords(str_replace('_', ' ', $sale->delivery_status ?? 'to_receive')) }}</p>
                    </div>
                </div>

                <div class="mb-8 overflow-hidden rounded-[1.5rem] border border-[var(--line)]">
                    <table class="min-w-full">
                        <thead class="bg-[rgba(246,248,251,0.9)]">
                            <tr>
                                <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Product</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Qty</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Unit Price</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold uppercase tracking-[0.2em] text-[var(--muted)]">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--line)]">
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-[var(--ink)]">{{ $item->product?->product_name }}</div>
                                        <div class="text-sm text-[var(--muted)]">{{ $item->product?->brand }} | {{ $item->product?->unit }}</div>
                                    </td>
                                    <td class="px-4 py-4">{{ $item->quantity }}</td>
                                    <td class="px-4 py-4">PHP {{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="px-4 py-4">PHP {{ number_format((float) $item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-[1.5rem] border border-[var(--line)] bg-[var(--card)] p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Payment Summary</p>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-[var(--muted)]">Total Amount</span>
                                <span class="font-medium text-[var(--ink)]">PHP {{ number_format((float) $sale->total_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-[var(--muted)]">Paid Amount</span>
                                <span class="font-medium text-[var(--ink)]">PHP {{ number_format((float) $sale->paid_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-[var(--muted)]">Balance Due</span>
                                <span class="font-medium text-[var(--ink)]">PHP {{ number_format((float) $sale->balance_due, 2) }}</span>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-[var(--line)] pt-3">
                                <span class="text-[var(--muted)]">Status</span>
                                <span class="font-semibold text-[var(--ink)]">{{ ucwords($sale->payment_status) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-[1.5rem] border border-[var(--line)] bg-[var(--card)] p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Payment Requests</p>
                        <div class="mt-4 space-y-3">
                            @forelse ($sale->paymentRequests as $paymentRequest)
                                <div class="rounded-xl border border-[var(--line)] bg-white/80 p-4 text-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-semibold text-[var(--ink)]">PHP {{ number_format((float) $paymentRequest->amount, 2) }}</div>
                                            <div class="mt-1 text-[var(--muted)]">{{ ucwords(str_replace('_', ' ', $paymentRequest->payment_method)) }}</div>
                                        </div>
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $paymentRequest->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($paymentRequest->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-sky-100 text-sky-700') }}">
                                            {{ ucfirst($paymentRequest->status) }}
                                        </span>
                                    </div>
                                    @if ($paymentRequest->reference_no)
                                        <div class="mt-2 text-xs text-[var(--muted)]">Reference: {{ $paymentRequest->reference_no }}</div>
                                    @endif
                                    @if ($paymentRequest->proof_image_path)
                                        <a href="{{ asset('storage/' . $paymentRequest->proof_image_path) }}" target="_blank" class="mt-2 inline-block text-xs font-semibold text-[var(--primary)] hover:underline">View uploaded proof</a>
                                    @endif
                                </div>
                            @empty
                                <div class="text-sm leading-7 text-[var(--muted)]">
                                    <p>No payment requests submitted yet.</p>
                                    <p>Please keep this receipt for your records.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @php
                    $pendingRequest = $sale->paymentRequests->firstWhere('status', 'processing');
                @endphp

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
                                @if ($gcashDue > 0)
                                    <input id="request_amount" type="number" value="{{ $gcashDue }}" step="0.01" readonly
                                           class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-slate-100 px-4 py-3 font-semibold text-slate-700">
                                    <p class="mt-2 text-xs text-[var(--muted)]">Fixed by the payment option you chose at checkout.</p>
                                @else
                                    <input id="request_amount" type="number" min="{{ $extraFloor }}" step="0.01" max="{{ (float) $sale->balance_due }}" value="{{ (float) $sale->balance_due }}"
                                           class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3 outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]">
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

    @include('partials.customer-footer')
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
                        <div class="mt-1 text-sm font-medium ${isSuccess ? 'text-emerald-900' : 'text-red-900'}">${text}</div>
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
</script>
@endpush
