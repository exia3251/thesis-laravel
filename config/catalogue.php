<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Buying by the box
    |--------------------------------------------------------------------------
    | Bottles are sold singly or by the box. A box is not a separate product
    | and not a separate row: it is a count of the same bottles, so ordering
    | one takes `quantity` units out of inventory and the cart, the stock
    | check and the receipt all carry on working in bottles.
    |
    | `max_litres_per_unit` keeps the option off the drum, which is not
    | something anyone boxes six of.
    */
    'bulk_box' => [
        'quantity' => (int) env('BULK_BOX_QUANTITY', 6),
        'max_litres_per_unit' => (int) env('BULK_BOX_MAX_LITRES', 5),
    ],

];
