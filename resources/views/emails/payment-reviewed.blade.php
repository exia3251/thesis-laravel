@extends('emails.layout')

@section('subject', ($approved ? 'Payment received for order ' : 'Payment not confirmed for order ') . $sale->reference())

@section('content')
    @if ($approved)
        <h1 style="margin:0 0 8px; font-size:22px; color:#16202a;">Your payment has been confirmed.</h1>
        <p style="margin:0 0 20px; font-size:14px; line-height:22px; color:#6f7d8c;">
            We checked the GCash receipt you sent against our records and it is confirmed.
            Order <strong style="color:#16202a;">{{ $sale->reference() }}</strong> has been updated.
        </p>
    @else
        <h1 style="margin:0 0 8px; font-size:22px; color:#16202a;">We could not confirm that payment.</h1>
        <p style="margin:0 0 20px; font-size:14px; line-height:22px; color:#6f7d8c;">
            We checked the GCash receipt you sent for order <strong style="color:#16202a;">{{ $sale->reference() }}</strong>
            and could not match it to a payment we have received. Nothing has been taken from your balance.
        </p>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; background-color:{{ $approved ? '#ecfdf5' : '#fffbeb' }}; border-radius:12px; margin-bottom:20px;">
        <tr>
            <td style="padding:18px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; font-size:13px; color:#6f7d8c;">Amount submitted</td>
                        <td align="right" style="padding:4px 0; font-size:15px; font-weight:bold; color:{{ $approved ? '#148a67' : '#b45309' }};">
                            PHP {{ number_format((float) $paymentRequest->amount, 2) }}
                        </td>
                    </tr>
                    @if ($paymentRequest->reference_no)
                        <tr>
                            <td style="padding:4px 0; font-size:13px; color:#6f7d8c;">GCash reference</td>
                            <td align="right" style="padding:4px 0; font-size:13px; color:#16202a;">{{ $paymentRequest->reference_no }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:4px 0; font-size:13px; color:#6f7d8c;">Reviewed</td>
                        <td align="right" style="padding:4px 0; font-size:13px; color:#16202a;">
                            {{ optional($paymentRequest->reviewed_at)->format('F d, Y \a\t g:i A') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- A staff note is the only place a customer learns what specifically
         was wrong, so it is shown whenever one was left. --}}
    @if (filled($paymentRequest->admin_notes))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; border-left:3px solid {{ $approved ? '#148a67' : '#d97706' }}; margin-bottom:20px;">
            <tr>
                <td style="padding:2px 0 2px 14px;">
                    <div style="font-size:10px; text-transform:uppercase; letter-spacing:2px; color:#6f7d8c;">Note from our staff</div>
                    <p style="margin:6px 0 0; font-size:14px; line-height:21px; color:#16202a;">{{ $paymentRequest->admin_notes }}</p>
                </td>
            </tr>
        </table>
    @endif

    {{-- Where the order stands now, which is the next thing anyone asks. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; background-color:#f6f8fb; border-radius:12px; margin-bottom:20px;">
        <tr>
            <td style="padding:18px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:4px 0; font-size:13px; color:#6f7d8c;">Order total</td>
                        <td align="right" style="padding:4px 0; font-size:13px; color:#16202a;">PHP {{ number_format((float) $sale->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0; font-size:13px; color:#6f7d8c;">Paid so far</td>
                        <td align="right" style="padding:4px 0; font-size:13px; color:#16202a;">PHP {{ number_format((float) $sale->paid_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0 0; border-top:1px solid rgba(21,35,54,0.10); font-size:14px; font-weight:bold; color:#16202a;">
                            {{ (float) $sale->balance_due > 0 ? 'Still to pay' : 'Balance' }}
                        </td>
                        <td align="right" style="padding:10px 0 0; border-top:1px solid rgba(21,35,54,0.10); font-size:16px; font-weight:bold; color:{{ (float) $sale->balance_due > 0 ? '#b45309' : '#148a67' }};">
                            PHP {{ number_format((float) $sale->balance_due, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @php
        // What is still owed, not what the plan set aside. codAmount() is the
        // share committed to cash at checkout and stays at that figure after
        // it has been paid, so quoting it here announced money owed on an
        // order that was already settled.
        $outstanding = (float) $sale->balance_due;
        $gcashDue = $sale->gcashOutstanding();
    @endphp

    @if (!$approved)
        <p style="margin:0 0 20px; font-size:13px; line-height:21px; color:#b45309;">
            Check the reference number on your GCash receipt and submit it again from your order page.
            If you believe this is a mistake, reply to our staff on {{ config('business.phone') }} with the receipt to hand.
        </p>
    @elseif ($outstanding <= 0)
        <p style="margin:0 0 20px; font-size:13px; line-height:21px; color:#148a67;">
            This order is fully settled. We will let you know when it is on its way.
        </p>
    @elseif ($gcashDue > 0)
        <p style="margin:0 0 20px; font-size:13px; line-height:21px; color:#6f7d8c;">
            <strong style="color:#16202a;">PHP {{ number_format($gcashDue, 2) }}</strong> of your GCash commitment is
            still to send. You can do that from your order page.
        </p>
    @else
        <p style="margin:0 0 20px; font-size:13px; line-height:21px; color:#6f7d8c;">
            Nothing more is owed online. The remaining
            <strong style="color:#16202a;">PHP {{ number_format($outstanding, 2) }}</strong> is paid in cash when your order arrives.
        </p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="background-color:#148a67; border-radius:10px;">
                <a href="{{ $url }}" style="display:inline-block; padding:12px 22px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    {{ $approved ? 'View your order' : 'Submit it again' }}
                </a>
            </td>
        </tr>
    </table>
@endsection
