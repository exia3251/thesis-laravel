<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Down payment floor
    |--------------------------------------------------------------------------
    | The smallest share of an order total that may be settled by GCash when
    | the customer splits payment with cash on delivery. Expressed as a
    | percentage of the order total.
    */
    'minimum_down_payment_percent' => env('MINIMUM_DOWN_PAYMENT_PERCENT', 50),

    /*
    |--------------------------------------------------------------------------
    | How long a customer has to settle the balance
    |--------------------------------------------------------------------------
    | The window the business allows for the remainder of an order to be paid
    | off, quoted as a range because it is agreed per customer rather than
    | enforced by a screen. Nothing in this system counts these days down or
    | acts when they run out; the figures exist so the terms page and the
    | assistant quote the same window.
    */
    'settlement_grace_days' => [
        'minimum' => env('SETTLEMENT_GRACE_DAYS_MIN', 30),
        'maximum' => env('SETTLEMENT_GRACE_DAYS_MAX', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voluntary early settlement
    |--------------------------------------------------------------------------
    | Once the GCash commitment on an order has been met, a customer may still
    | pay down the cash-on-delivery balance early. Each of those payments costs
    | an administrator a review, so they carry a floor. Paying off the whole
    | remaining balance is always allowed, even when it is under this amount.
    */
    'minimum_extra_payment' => env('MINIMUM_EXTRA_PAYMENT', 500),

    /*
    |--------------------------------------------------------------------------
    | Largest amount any one order or payment may carry
    |--------------------------------------------------------------------------
    | Every money column in the schema is decimal(10,2), which stops just under
    | a hundred million. A figure above the column's range is rejected by the
    | database with a raw SQL error rather than a message anyone can act on, so
    | the validator holds the line well below it.
    */
    'maximum_amount' => 9999999.99,

    /*
    |--------------------------------------------------------------------------
    | Proof of payment
    |--------------------------------------------------------------------------
    | Screenshot rules for a GCash payment request. Kept here so the
    | controller, the validator and the help text on the upload form cannot
    | drift apart.
    */
    'proof' => [
        'max_kilobytes' => 4096,
        'min_width'     => 200,
        'min_height'    => 200,
        'mimes'         => ['jpg', 'jpeg', 'png', 'webp'],
    ],

];
