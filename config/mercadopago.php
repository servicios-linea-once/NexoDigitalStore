<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MercadoPago Configuration
    |--------------------------------------------------------------------------
    */

    'access_token' => env('MP_ACCESS_TOKEN'),
    'public_key' => env('MP_PUBLIC_KEY'),
    'client_id' => env('MP_CLIENT_ID'),
    'client_secret' => env('MP_CLIENT_SECRET'),

    // Webhook secret for signature verification
    'webhook_secret' => env('MP_WEBHOOK_SECRET'),

    // Default country (affects payment methods shown)
    'country' => env('MP_COUNTRY', 'MX'), // MX, PE, CO, AR, BR, CL

];
