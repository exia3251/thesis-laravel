@extends('emails.layout')

@section('subject', config('business.document_title') . ' for order ' . $sale->reference())

@section('content')
    <h1 style="margin:0 0 8px; font-size:22px; color:#16202a;">Your order has been delivered.</h1>
    <p style="margin:0 0 20px; font-size:14px; line-height:22px; color:#6f7d8c;">
        Order <strong style="color:#16202a;">{{ $sale->reference() }}</strong> was handed over on
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
                @php $tax = $sale->taxBreakdown(); @endphp
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:5px 0; font-size:13px; color:#6f7d8c;">{{ $tax['registered'] ? 'VATable sales' : 'Subtotal' }}</td>
                        <td align="right" style="padding:5px 0; font-size:13px; color:#16202a;">PHP {{ number_format($tax['subtotal'], 2) }}</td>
                    </tr>
                    @if ($tax['registered'])
                        <tr>
                            <td style="padding:5px 0; font-size:13px; color:#6f7d8c;">VAT ({{ (int) $tax['rate'] }}%)</td>
                            <td align="right" style="padding:5px 0; font-size:13px; color:#16202a;">PHP {{ number_format($tax['vat'], 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:8px 0 0; border-top:1px solid rgba(21,35,54,0.10); font-size:14px; font-weight:bold; color:#16202a;">Total</td>
                        <td align="right" style="padding:8px 0 0; border-top:1px solid rgba(21,35,54,0.10); font-size:14px; font-weight:bold; color:#16202a;">PHP {{ number_format($tax['total'], 2) }}</td>
                    </tr>
                    @unless ($tax['registered'])
                        <tr>
                            <td colspan="2" align="right" style="padding:4px 0 0; font-size:11px; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; color:#6f7d8c;">Non-VAT registered</td>
                        </tr>
                    @endunless
                </table>

                {{-- The same sequence the receipt on the site shows, so a
                     customer reading either one sees the payments in the
                     order they happened rather than a single total. --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px; border-top:1px solid rgba(21,35,54,0.10);">
                    <tr>
                        <td colspan="2" style="padding:12px 0 6px; font-size:11px; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; color:#6f7d8c;">Payment</td>
                    </tr>
                    @foreach ($sale->paymentTimeline() as $row)
                        @php
                            $colour = match ($row['state']) {
                                'in'      => '#148a67',
                                'pending' => '#0284c7',
                                'void'    => '#94a3b8',
                                'due'     => '#b45309',
                                'out'     => '#7c3aed',
                                default   => '#16202a',
                            };
                        @endphp
                        <tr>
                            @php
                                $state = match ($row['state']) {
                                    'in' => 'paid', 'pending' => 'being checked',
                                    'void' => 'not accepted', 'due' => 'due',
                                    'out' => 'returned to you', default => null,
                                };
                                $aside = collect([$row['detail'], $state])->filter()->implode(' · ');
                            @endphp
                            <td style="padding:4px 0; font-size:13px; color:#16202a;">
                                {{ $row['label'] }}
                                @if ($aside)
                                    <span style="color:{{ $colour }};">&middot; {{ $aside }}</span>
                                @endif
                            </td>
                            <td align="right" style="padding:4px 0; font-size:13px; font-weight:bold; color:{{ $colour }};">PHP {{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @endforeach
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
                <a href="{{ $url }}" style="display:inline-block; padding:12px 22px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">Open it on the site</a>
            </td>
        </tr>
    </table>

    {{-- An email calling itself a receipt has to make the same statements the
         printed one does, or the two disagree about what the document is. --}}
    <p style="margin:22px 0 0; padding-top:16px; border-top:1px solid rgba(21,35,54,0.10); font-size:11px; line-height:18px; color:#6f7d8c;">
        @unless (config('business.is_official'))
            <strong style="color:#16202a;">{{ config('business.disclaimer') }}</strong><br>
        @endunless
        @unless ($sale->taxBreakdown()['registered'])
            This document is not valid for claim of input tax.<br>
        @endunless
        Keep this for your records.
    </p>
@endsection
