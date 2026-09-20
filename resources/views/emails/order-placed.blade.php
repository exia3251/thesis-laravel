@extends('emails.layout')

@section('subject', 'Order #' . $sale->sale_id . ' confirmed')

@section('content')
    <h1 style="margin:0 0 8px; font-size:22px; color:#16202a;">Thanks for your order, {{ $sale->customer_name }}.</h1>
    <p style="margin:0 0 20px; font-size:14px; line-height:22px; color:#6f7d8c;">
        We have received order <strong style="color:#16202a;">#{{ $sale->sale_id }}</strong>, placed
        {{ optional($sale->sale_date)->format('F d, Y \a\t g:i A') }}. Here is what you owe and how to settle it.
    </p>

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
            <tr>
                <td colspan="2" style="padding:12px 0; font-size:14px; font-weight:bold;">Order total</td>
                <td align="right" style="padding:12px 0; font-size:16px; font-weight:bold; color:#148a67;">PHP {{ number_format((float) $sale->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- The split between what is owed online and what is owed at the door is
         the part customers get wrong, so it is stated on its own. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; background-color:#f6f8fb; border-radius:12px; margin-bottom:20px;">
        <tr>
            <td style="padding:18px;">
                <div style="font-size:11px; text-transform:uppercase; letter-spacing:2px; color:#6f7d8c; margin-bottom:10px;">How you chose to pay</div>
                <div style="font-size:14px; font-weight:bold; color:#16202a; margin-bottom:12px;">{{ $sale->planLabel() }}</div>

                @if ($sale->gcashOutstanding() > 0)
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding:6px 0; font-size:13px; color:#6f7d8c;">Send now by GCash</td>
                            <td align="right" style="padding:6px 0; font-size:15px; font-weight:bold; color:#148a67;">PHP {{ number_format($sale->gcashOutstanding(), 2) }}</td>
                        </tr>
                        @if ($sale->codAmount() > 0)
                            <tr>
                                <td style="padding:6px 0; font-size:13px; color:#6f7d8c;">Cash on delivery</td>
                                <td align="right" style="padding:6px 0; font-size:15px; font-weight:bold; color:#16202a;">PHP {{ number_format($sale->codAmount(), 2) }}</td>
                            </tr>
                        @endif
                    </table>
                @else
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding:6px 0; font-size:13px; color:#6f7d8c;">Due in cash on delivery</td>
                            <td align="right" style="padding:6px 0; font-size:15px; font-weight:bold; color:#16202a;">PHP {{ number_format((float) $sale->balance_due, 2) }}</td>
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    @if ($sale->gcashOutstanding() > 0)
        <p style="margin:0 0 20px; font-size:13px; line-height:21px; color:#6f7d8c;">
            Send <strong style="color:#16202a;">PHP {{ number_format($sale->gcashOutstanding(), 2) }}</strong> in one transfer,
            then upload the receipt with its reference number on your order page. An administrator confirms it before it counts
            against your balance.
        </p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td style="background-color:#148a67; border-radius:10px;">
                <a href="{{ $url }}" style="display:inline-block; padding:12px 22px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    {{ $sale->gcashOutstanding() > 0 ? 'Pay and upload receipt' : 'View your order' }}
                </a>
            </td>
        </tr>
    </table>

    <div style="font-size:11px; text-transform:uppercase; letter-spacing:2px; color:#6f7d8c; margin-bottom:6px;">Delivering to</div>
    <p style="margin:0; font-size:13px; line-height:20px; color:#16202a;">
        {{ $sale->delivery_address }}<br>
        {{ $sale->contact_phone }}
    </p>
@endsection
