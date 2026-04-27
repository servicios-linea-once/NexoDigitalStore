<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_NOTIFICATIONS_CHANNEL'),
        ],
    ],

    'external_api' => [
        'base_url' => env('EXTERNAL_API_URL', 'https://api.proveedor.com/v1'),
        'key'      => env('EXTERNAL_API_KEY'),
    ],

    // ── OAuth Providers ──────────────────────────────────────────────────────
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/auth/google/callback',
    ],

    // Steam uses OpenID, no secret needed
    'steam' => [
        'client_id' => env('STEAM_CLIENT_ID', ''),
        'client_secret' => env('STEAM_API_KEY', ''),
        'redirect' => env('APP_URL').'/auth/steam/callback',
    ],

    // ── Telegram Bot ─────────────────────────────────────────────────────────
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'NexoDigitalBot'),
    ],

];
