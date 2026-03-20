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

        <footer class="border-t border-[var(--line)] bg-[rgba(255,255,255,0.88)] backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="space-y-4">
                        <div>
                            <div class="text-xl font-black tracking-tight">
                                <span class="text-[var(--primary)]">RANEY</span>
                                <span class="text-[var(--accent)]"> LUBRICANTS</span>
                            </div>
                            <div class="mt-0.5 text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
                        </div>
                        <p class="text-sm leading-6 text-[var(--muted)]">Your trusted partner for premium engine oils and lubricants. Quality products for maximum performance.</p>
                        <div class="flex gap-3">
                            <span class="cursor-pointer flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            </span>
                            <span class="cursor-pointer flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                            </span>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">About Us</h4>
                        <ul class="space-y-2 text-sm">
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Our Story</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Our Products</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Why Choose Us</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Careers</span></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                        <ul class="space-y-2 text-sm">
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">My Account</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Order Tracking</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Shipping Info</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">Returns &amp; Refunds</span></li>
                            <li><span class="cursor-pointer text-[var(--muted)] transition hover:text-[var(--primary)]">FAQs</span></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Contact Us</h4>
                        <ul class="space-y-3 text-sm text-[var(--muted)]">
                            <li class="flex items-start gap-3">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>123 Industrial Ave, Makati City, Metro Manila, Philippines</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span>+63 2 1234 5678</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>sales@raneylubricants.ph</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Mon – Sat: 8AM – 6PM</span>
                            </li>
                        </ul>
                    </div>

                </div>

                <div class="mt-8 border-t border-[var(--line)] pt-6 flex flex-col items-center justify-between gap-4 text-center sm:flex-row sm:text-left">
                    <p class="text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.</p>
                    <div class="flex gap-4 text-xs text-[var(--muted)]">
                        <span class="cursor-pointer hover:text-[var(--primary)]">Privacy Policy</span>
                        <span class="cursor-pointer hover:text-[var(--primary)]">Terms of Service</span>
                    </div>
                </div>
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