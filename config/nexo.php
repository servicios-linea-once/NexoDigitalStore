<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NexoTokens (NT) — Moneda Interna
    |--------------------------------------------------------------------------
    */
    'token' => [
        'name' => env('NEXO_TOKEN_NAME', 'NexoToken'),
        'symbol' => env('NEXO_TOKEN_SYMBOL', 'NT'),
        'rate_to_usd' => (float) env('NEXO_TOKEN_RATE_USD', 0.10), // 1 NT = $X USD
        'cashback_rate' => (float) env('NEXO_CASHBACK_RATE', 2),     // % cashback en NT
    ],

    /*
    |--------------------------------------------------------------------------
    | Pasarelas de Pago
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'gateways' => ['paypal', 'mercadopago', 'nexotokens'],
        'default' => 'paypal',

        'paypal' => [
            'client_id'          => env('PAYPAL_CLIENT_ID'),
            'client_secret'      => env('PAYPAL_CLIENT_SECRET'),
            'sandbox_client_id'  => env('PAYPAL_SANDBOX_CLIENT_ID', env('PAYPAL_CLIENT_ID')),
            'sandbox_secret'     => env('PAYPAL_SANDBOX_SECRET', env('PAYPAL_CLIENT_SECRET')),
            'mode'               => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
            'webhook_id'         => env('PAYPAL_WEBHOOK_ID'),
            'currency'           => 'USD',
        ],

        'mercadopago' => [
            'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
            'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
            'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
            'country' => 'PE', // Perú
            'currency' => 'PEN',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Moneda
    |--------------------------------------------------------------------------
    */
    'currencies' => [
        'default' => 'PEN',
        'supported' => ['USD', 'PEN'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Comisiones
    |--------------------------------------------------------------------------
    */
    'commissions' => [
        'default_rate' => 10.0, // %
        'min_rate' => 5.0,
        'max_rate' => 20.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventario y Reservas
    |--------------------------------------------------------------------------
    */
    'inventory' => [
        'reservation_minutes' => 15, // minutos antes de liberar reserva
        'low_stock_threshold' => 5,  // alerta cuando queden N claves
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'token'              => env('TELEGRAM_BOT_TOKEN'),
        'username'           => env('TELEGRAM_BOT_USERNAME'),
        'webhook_url'        => env('TELEGRAM_WEBHOOK_URL'),
        'webhook_secret'     => env('TELEGRAM_WEBHOOK_SECRET'), // X-Telegram-Bot-Api-Secret-Token
        'sales_enabled'      => true,   // el bot puede vender
        'supported_currencies' => ['USD', 'PEN'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */
    'security' => [
        'max_login_attempts' => (int) env('SECURITY_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_minutes' => (int) env('SECURITY_LOCKOUT_MINUTES', 15),
        'two_factor_issuer' => env('SECURITY_2FA_ISSUER', 'Nexo Digital Store'),
        'audit_enabled' => (bool) env('SECURITY_AUDIT_ENABLED', true),
        'key_encryption' => true,  // digital_keys.key_value siempre encriptado
    ],

    /*
    |--------------------------------------------------------------------------
    | Suscripciones
    |--------------------------------------------------------------------------
    */
    'subscriptions' => [
        'auto_renew_allowed' => false, // política: sin renovación automática
        'grace_period_days' => 3,     // días de gracia tras expirar
    ],

    /*
    |--------------------------------------------------------------------------
    | Catálogo — Plataformas y Regiones
    |--------------------------------------------------------------------------
    */
    'catalog' => [
        'platforms' => [
            'Steam', 'Epic Games', 'GOG', 'Battle.net', 'PSN', 'Xbox',
            'Nintendo', 'Netflix', 'Spotify', 'Disney+', 'YouTube Premium',
            'HBO Max', 'Crunchyroll', 'Microsoft', 'Rockstar', 'Amazon',
            'Google Play', 'Apple', 'Ubisoft', 'EA Play', 'Generic',
        ],
        'regions' => ['Global', 'PE', 'US', 'EU', 'MX', 'CO', 'AR', 'BR', 'CL'],
        'delivery_types' => [
            'automatic' => 'Entrega automática',
            'manual' => 'Entrega manual',
            'api' => 'Vía API',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Escrow — Retención de Pagos al Vendedor
    |--------------------------------------------------------------------------
    */
    'escrow' => [
        'enabled'      => env('ESCROW_ENABLED', true),
        'hold_hours'   => (int) env('ESCROW_HOLD_HOURS', 24),  // horas de retención tras entrega
        'auto_release' => env('ESCROW_AUTO_RELEASE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Activaciones de Licencias
    |--------------------------------------------------------------------------
    */
    'activations' => [
        'default_max' => (int) env('LICENSE_MAX_ACTIVATIONS', 1), // activaciones por defecto por clave
    ],

];
