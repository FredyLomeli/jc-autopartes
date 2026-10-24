<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mercado Pago Credentials
    |--------------------------------------------------------------------------
    |
    | Credenciales de la aplicación en Mercado Pago Developers para Checkout Pro
    | y recepción segura de notificaciones webhook.
    |
    */

    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN', ''),
    'public_key' => env('MERCADOPAGO_PUBLIC_KEY', ''),
    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Modo Sandbox / Simulación
    |--------------------------------------------------------------------------
    |
    | Define si las llamadas operan en modo de prueba o producción.
    |
    */

    'sandbox' => (bool) env('MERCADOPAGO_SANDBOX', true),

    /*
    |--------------------------------------------------------------------------
    | Endpoint Base de Mercado Pago API
    |--------------------------------------------------------------------------
    */

    'base_url' => env('MERCADOPAGO_BASE_URL', 'https://api.mercadopago.com'),

    /*
    |--------------------------------------------------------------------------
    | Parámetros predeterminados de Checkout Pro
    |--------------------------------------------------------------------------
    */

    'currency_id' => 'MXN',
    'statement_descriptor' => env('MERCADOPAGO_STATEMENT_DESCRIPTOR', 'JC AUTOPARTES'),
    'auto_return' => 'approved',
];
