<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    */

    'mode' => env('PAYPAL_MODE', 'sandbox'), // 'sandbox' | 'live'

    'sandbox' => [
        'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
        'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
    ],

    'live' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    ],

    'webhook_id' => env('PAYPAL_WEBHOOK_ID'),

    // IPN verification URL
    'verify_url' => [
        'sandbox' => 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr',
        'live' => 'https://ipnpb.paypal.com/cgi-bin/webscr',
    ],
];
