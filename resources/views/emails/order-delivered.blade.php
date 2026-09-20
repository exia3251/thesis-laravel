@extends('emails.layout')

@section('subject', 'Receipt for order #' . $sale->sale_id)

@section('content')
    <h1 style="margin:0 0 8px; font-size:22px; color:#16202a;">Your order has been delivered.</h1>
    <p style="margin:0 0 20px; font-size:14px; line-height:22px; color:#6f7d8c;">
        Order <strong style="color:#16202a;">#{{ $sale->sale_id }}</strong> was handed over on
        {{ optional($sale->delivered_at)->format('F d, Y \a\t g:i A') }}. This email is your receipt.
    </p>

    @if ($sale->receipt_no || $sale->delivery_no)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; border:1px solid rgba(21,35,54,0.10); border-radius:12px; margin-bottom:20px;">
            <tr>
                @if ($sale->receipt_no)
                    <td style="padding:14px 18px;">
                        <div style="font-size:10px; text-transform:uppercase; letter-spacing:2px; color:#6f7d8c;">Receipt No.</div>
                        <div style="margin-top:3px; font-size:15px; font-weight:bold; color:#148a67;">{{ $sale->receipt_no }}</div>
                    </td>
                @endif
                @if ($sale->delivery_no)
                    <td style="padding:14px 18px;">
                        <div style="font-size:10px; text-transform:uppercase; letter-spacing:2px; color:#6f7d8c;">Delivery No.</div>
                        <div style="margin-top:3px; font-size:15px; font-weight:bold; color:#16202a;">{{ $sale->delivery_no }}</div>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:20px;">
        <thead>
            <tr>
                <th align="left" style="padding:8px 0; border-bottom:2px solid rgba(21,35,54,0.10); font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#6f7d8c;">Item</th>
                <th align="center" style="padding:8px 0; border-bottom:2px solid rgba(21,35,54,0.10); font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#6f7d8c;">Qty</th>
                <th align="right" style="padding:8px 0; border-bottom:2px solid rgba(21,35,54,0.10); font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#6f7d8c;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td style="padding:10px 0; border-bottom:1px solid rgba(21,35,54,0.07); font-size:13px;">
                        {{ $item->product?->product_name ?? 'Item no longer listed' }}
                        <div style="font-size:11px; color:#6f7d8c;">{{ $item->product?->brand }} &middot; {{ $item->product?->unit }}</div>
                    </td>
                    <td align="center" style="padding:10px 0; border-bottom:1px solid rgba(21,35,54,0.07); font-size:13px;">{{ $item->quantity }}</td>
                    <td align="right" style="padding:10px 0; border-bottom:1px solid rgba(21,35,54,0.07); font-size:13px;">PHP {{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; background-color:#f6f8fb; border-radius:12px; margin-bottom:20px;">
        <tr>
            <td style="padding:18px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:5px 0; font-size:13px; color:#6f7d8c;">Order total</td>
                        <td align="right" style="padding:5px 0; font-size:13px; color:#16202a;">PHP {{ number_format((float) $sale->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0; font-size:13px; color:#6f7d8c;">Paid</td>
                        <td align="right" style="padding:5px 0; font-size:13px; color:#16202a;">PHP {{ number_format((float) $sale->paid_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0 0; border-top:1px solid rgba(21,35,54,0.10); font-size:14px; font-weight:bold; color:#16202a;">Balance</td>
                        <td align="right" style="padding:10px 0 0; border-top:1px solid rgba(21,35,54,0.10); font-size:16px; font-weight:bold; color:{{ (float) $sale->balance_due > 0 ? '#b45309' : '#148a67' }};">
                            PHP {{ number_format((float) $sale->balance_due, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ((float) $sale->balance_due > 0)
        <p style="margin:0 0 20px; font-size:13px; line-height:21px; color:#b45309;">
            There is still <strong>PHP {{ number_format((float) $sale->balance_due, 2) }}</strong> outstanding on this order.
            Please settle it with our staff.
        </p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="background-color:#148a67; border-radius:10px;">
                <a href="{{ $url }}" style="display:inline-block; padding:12px 22px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">View full receipt</a>
            </td>
        </tr>
    </table>
@endsection
