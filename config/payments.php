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
    'minimum_down_payment_percent' => env('MINIMUM_DOWN_PAYMENT_PERCENT', 20),

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
