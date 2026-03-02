<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Supported providers: twilio | 360dialog | meta
    |
    */

    'provider' => env('WHATSAPP_PROVIDER', 'meta'),

    'from_number' => env('WHATSAPP_FROM_NUMBER', ''),

    'api_key' => env('WHATSAPP_API_KEY', ''),

    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),

    /*
     | Provider-specific base URLs
     */
    'api_base_urls' => [
        'meta'      => 'https://graph.facebook.com/v18.0',
        'twilio'    => 'https://api.twilio.com/2010-04-01',
        '360dialog' => 'https://waba.360dialog.io/v1',
    ],
];
