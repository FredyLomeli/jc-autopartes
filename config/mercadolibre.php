<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mercado Libre API Configuration
    |--------------------------------------------------------------------------
    |
    | Credenciales de la aplicación en el portal de desarrolladores de Mercado Libre:
    | https://developers.mercadolibre.com.mx
    |
    */

    'app_id' => env('MELI_APP_ID'),
    'client_secret' => env('MELI_CLIENT_SECRET'),
    'redirect_uri' => env('MELI_REDIRECT_URI', env('APP_URL', 'http://localhost:8000') . '/admin/integraciones/mercadolibre/callback'),

    /*
    | Dominio de autenticación por país:
    | México: https://auth.mercadolibre.com.mx
    */
    'auth_base_url' => env('MELI_AUTH_BASE_URL', 'https://auth.mercadolibre.com.mx'),

    /*
    | API base endpoint
    */
    'api_base_url' => env('MELI_API_BASE_URL', 'https://api.mercadolibre.com'),

    /*
    | Identificador de país (MLM = México)
    */
    'country_id' => env('MELI_COUNTRY_ID', 'MLM'),
];
