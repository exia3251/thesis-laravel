<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    <div class="flex items-end justify-start md:justify-end">
                        <div class="text-sm leading-7 text-[var(--muted)]">
                            <p>Thank you for your purchase.</p>
                            <p>Please keep this receipt for your records.</p>
                        </div>
                    </div>
                </div>
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
</body>
</html>
