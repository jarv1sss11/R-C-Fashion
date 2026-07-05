<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Delivery Costs
    |--------------------------------------------------------------------------
    |
    | Flat cost per delivery option, in KES. Overridden at runtime by the
    | Admin Settings page (Step 11) — CheckoutService only ever reads this
    | config key, never the settings table directly.
    |
    */
    'delivery_costs' => [
        'standard' => 200.0,
        'express' => 500.0,
    ],
];
