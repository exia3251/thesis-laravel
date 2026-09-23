<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Who the documents say they are from
    |--------------------------------------------------------------------------
    | These were hard-coded into six views. Set them once here and every
    | receipt, footer and email carries the same details.
    */
    'name' => env('BUSINESS_NAME', 'RANEY LUBRICANTS TRADING'),
    'address' => env('BUSINESS_ADDRESS', '123 Industrial Ave, Makati City, Metro Manila'),
    'phone' => env('BUSINESS_PHONE', '+63 2 1234 5678'),
    'email' => env('BUSINESS_EMAIL', 'sales@raneylubricants.ph'),
    'hours' => env('BUSINESS_HOURS', 'Mon - Sat: 8AM - 6PM'),

    /*
    |--------------------------------------------------------------------------
    | Tax registration
    |--------------------------------------------------------------------------
    | A seller registered for VAT has to show the VATable amount and the tax
    | separately; one below the threshold has to say it is not registered.
    | Showing neither, which is what this system did, is the one thing a
    | document cannot be.
    |
    | Prices in the catalogue are treated as tax inclusive, which is how
    | retail prices are quoted here, so the VAT shown is worked back out of
    | the total rather than added on top.
    */
    'vat_registered' => env('BUSINESS_VAT_REGISTERED', false),
    'vat_rate' => (float) env('BUSINESS_VAT_RATE', 12),
    'tin' => env('BUSINESS_TIN'),

    /*
    |--------------------------------------------------------------------------
    | What the printed document may call itself
    |--------------------------------------------------------------------------
    | "Official Receipt" is a regulated term. Issuing one takes a BIR
    | Authority to Print and a serial range from that authority, and this
    | system has neither, so the document names itself honestly and says so
    | in as many words.
    |
    | Fill in atp_number once the business holds one, and set
    | `is_official` to true, to drop the disclaimer and print the permit
    | details a real receipt carries. Confirm the requirements with your
    | accountant before doing so.
    */
    'document_title' => env('BUSINESS_DOCUMENT_TITLE', 'Order Summary'),
    'is_official' => env('BUSINESS_IS_OFFICIAL_RECEIPT', false),
    'atp_number' => env('BUSINESS_ATP_NUMBER'),

    'disclaimer' => env(
        'BUSINESS_DOCUMENT_DISCLAIMER',
        'This is not a BIR Official Receipt. Please ask our staff if you need one.'
    ),

];
