<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - #{{ $sale->sale_id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="no-print flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Order Receipt</h1>
                <p class="text-gray-600">Review and print your purchase summary.</p>
            </div>
            <div class="flex gap-3">
                <a href="/orders" class="px-4 py-2 rounded bg-gray-200 text-gray-800 hover:bg-gray-300">Back to Orders</a>
                <button onclick="window.print()" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Print Receipt</button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-8">
            <div class="flex justify-between items-start border-b pb-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Engine Oil Shop</h2>
                    <p class="text-gray-500">Official Sales Receipt</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Receipt No.</p>
                    <p class="text-xl font-semibold text-gray-900">#{{ $sale->sale_id }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <p class="text-sm text-gray-500">Customer</p>
                    <p class="font-semibold text-gray-900">{{ $sale->customer_name ?? $sale->user?->full_name }}</p>
                    <p class="text-sm text-gray-600">Payment Method: {{ ucwords(str_replace('_', ' ', $sale->payment_method ?? 'N/A')) }}</p>
                </div>
                <div class="text-left md:text-right">
                    <p class="text-sm text-gray-500">Order Date</p>
                    <p class="font-semibold text-gray-900">{{ optional($sale->sale_date)->format('F d, Y h:i A') }}</p>
                    <p class="text-sm text-gray-600">Delivery Status: {{ ucwords(str_replace('_', ' ', $sale->delivery_status ?? 'to_receive')) }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border mb-8">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($sale->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $item->product?->product_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $item->product?->brand }} | {{ $item->product?->unit }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $item->quantity }}</td>
                                <td class="px-4 py-3">PHP {{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="px-4 py-3">PHP {{ number_format((float) $item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Payment Summary</p>
                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Total Amount</span>
                                <span class="font-medium">PHP {{ number_format((float) $sale->total_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Paid Amount</span>
                                <span class="font-medium">PHP {{ number_format((float) $sale->paid_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Balance Due</span>
                                <span class="font-medium">PHP {{ number_format((float) $sale->balance_due, 2) }}</span>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <span>Status</span>
                                <span class="font-semibold">{{ ucwords($sale->payment_status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-end justify-start md:justify-end">
                    <div class="text-sm text-gray-500">
                        <p>Thank you for your purchase.</p>
                        <p>Please keep this receipt for your records.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
