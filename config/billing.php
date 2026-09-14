<?php

use App\Models\Entitlement;

return [

    /*
    |--------------------------------------------------------------------------
    | Product variants
    |--------------------------------------------------------------------------
    |
    | Lemon Squeezy variant ids. These are what the webhook matches against to
    | decide whether an order grants the deck creator or is simply a donation,
    | so a mismatch here means purchases quietly grant nothing.
    |
    */

    'premium_variant_id' => env('LEMONSQUEEZY_PREMIUM_VARIANT_ID'),

    'donation_variant_id' => env('LEMONSQUEEZY_DONATION_VARIANT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Entitlement granted by a premium purchase
    |--------------------------------------------------------------------------
    */

    'premium_entitlement' => Entitlement::TYPE_DECK_CREATOR,

    /*
    |--------------------------------------------------------------------------
    | Donations
    |--------------------------------------------------------------------------
    |
    | Presets are what the UI offers; the client sends a preset key or a custom
    | amount, never a price. Amounts are in cents and are validated server-side
    | against the min/max below, so a tampered request cannot charge an
    | arbitrary sum or a negative one.
    |
    */

    'donation' => [
        'currency' => env('LEMONSQUEEZY_DONATION_CURRENCY', 'EUR'),
        'presets' => [300, 500, 1000],
        'min' => 100,
        'max' => 50000,
    ],

];
