<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order Receipt - RANEY LUBRICANTS TRADING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --surface: #f6f8fb;
            --card: rgba(255, 255, 255, 0.82);
            --card-solid: #ffffff;
            --ink: #16202a;
            --muted: #6f7d8c;
            --line: rgba(21, 35, 54, 0.1);
            --primary: #148a67;
            --primary-soft: rgba(20, 138, 103, 0.1);
            --accent: #d9b14a;
            --accent-soft: rgba(217, 177, 74, 0.12);
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }
        }
    </style>
</head>
<body class="bg-[var(--surface)] text-[var(--ink)]">
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

                @if ((float) $sale->balance_due > 0)
                    <div id="payment-request" class="mt-8 rounded-[1.5rem] border border-[var(--line)] bg-[var(--card)] p-5 no-print">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-xl">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Submit Payment Request</p>
                                <h3 class="mt-2 text-xl font-bold text-[var(--ink)]">Pay the remaining balance for this order.</h3>
                                <p class="mt-2 text-sm leading-7 text-[var(--muted)]">Payments are submitted for admin review first. If you choose GCash, use the QR below and upload proof with the reference number after payment.</p>
                                <div class="mt-4 rounded-xl border border-[var(--accent-soft)] bg-[rgba(255,252,243,0.92)] p-4 text-sm text-[#715b1d]">
                                    Remaining balance: <strong>PHP {{ number_format((float) $sale->balance_due, 2) }}</strong>
                                </div>
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
                                <input id="request_amount" type="number" min="1" step="1" max="{{ (int) ceil((float) $sale->balance_due) }}" value="{{ (int) ceil((float) $sale->balance_due) }}" class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3 outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[var(--ink)]">Reference Number</label>
                                <input id="request_reference_no" type="text" maxlength="100" minlength="6" pattern="[A-Za-z0-9][A-Za-z0-9\-]{5,99}" title="Use at least 6 characters. Letters, numbers, and hyphens only." class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3 outline-none transition focus:border-[var(--primary)] focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Required GCash reference number" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[var(--ink)]">Proof Image</label>
                                <input id="request_proof_image" type="file" accept="image/*" class="mt-2 block w-full rounded-xl border border-[var(--line)] bg-white/85 px-4 py-3">
                                <p class="mt-2 text-xs text-[var(--muted)]">Accepted: JPG, JPEG, PNG, WEBP. Max file size: 2 MB.</p>
                            </div>
                            <div class="md:col-span-2 flex justify-end">
                                <button type="submit" class="rounded-xl bg-[var(--primary)] px-6 py-3 text-sm font-bold text-white transition hover:brightness-110">Submit Payment Request</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <footer class="no-print border-t border-[var(--line)] bg-[rgba(255,255,255,0.82)] backdrop-blur">
            <div class="max-w-5xl mx-auto grid gap-6 px-4 py-8 md:grid-cols-4">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[0.22em] text-[var(--ink)]">RANEY LUBRICANTS TRADING</h3>
                    <p class="mt-3 text-sm leading-6 text-[var(--muted)]">Official customer receipt view for lubricant purchases and delivery information.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Review receipt details carefully and keep this record for future order reference.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">Payments & Logistics</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Receipts summarize payment status, delivery status, item quantities, and saved delivery information.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ink)]">About</h4>
                    <p class="mt-3 text-sm text-[var(--muted)]">Built for traceable ecommerce transactions in lubricant and automotive fluid retail operations.</p>
                </div>
            </div>
            <div class="border-t border-[var(--line)] px-4 py-4 text-center text-sm text-[var(--muted)]">
                &copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.
            </div>
        </footer>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
</body>
</html>
