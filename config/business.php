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
    'address' => env('BUSINESS_ADDRESS', '2 Andalucia St., Brgy. Dulong Bayan, Bacoor City, Cavite'),
    /*
     * No default. "+63 2 1234 5678" sat here and was printed in the footer,
     * on the returns page, in the legal pages and in one email -- a number
     * that belongs to nobody, offered to customers as the way to reach this
     * business. Every place that shows it now checks first, so an unset
     * number is absent rather than fictional. Set BUSINESS_PHONE when there
     * is a real one.
     */
    'phone' => env('BUSINESS_PHONE'),
    'email' => env('BUSINESS_EMAIL', 'sales.raneylubricants@gmail.com'),
    'hours' => env('BUSINESS_HOURS', 'Mon - Sat: 8AM - 6PM'),

    /*
     * The pack sizes the business sells, in one place.
     *
     * Read by the add-product form, which offers these and nothing else, and
     * by the shop assistant, which will not mention a pack that is not on
     * this list. Drums are deliberately absent: they are no longer listed,
     * and an assistant that offers one is an assistant making a promise the
     * shop cannot keep.
     *
     * Products carrying a size that has since left this list keep it. The
     * form shows it, marked as retired, so editing an old product cannot
     * silently repack it.
     */
    'pack_sizes' => ['1 Liter', '4 Liters', '5 Liters'],

    /*
     * Said on every oil recommendation, without exception and without degree.
     *
     * There used to be two of these. A vehicle in the shop's own guide got
     * "always confirm against your handbook"; one that was not got "this
     * vehicle is not on our list, so check the handbook first". Read together
     * they say the guide is reliable and everything else is a guess, which is
     * not true -- every row in that guide is a general reference figure that
     * nobody here has checked against a manual either.
     *
     * So there is one line now and it says the same thing to everybody: the
     * handbook for your own engine is what decides, whoever suggested what.
     */
    'oil_disclaimer' => env(
        'BUSINESS_OIL_DISCLAIMER',
        'Your vehicle handbook has the final say, and checking it before an oil change is the owner\'s'
            . ' responsibility. The wrong viscosity can damage an engine, and only the handbook for your'
            . ' exact engine and year is authoritative.'
    ),

    // The only social account the business actually has. Kept here rather
    // than in the footer markup so it is beside the rest of the contact
    // details, which is where anyone would look for it.
    'facebook' => env('BUSINESS_FACEBOOK', 'https://www.facebook.com/rainee.rubin'),

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
