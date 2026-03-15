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
        'meta'      => 'https://graph.facebook.com/v22.0',
        'twilio'    => 'https://api.twilio.com/2010-04-01',
        '360dialog' => 'https://waba.360dialog.io/v1',
    ],

    /*
     | Lead Nurture Sequence
     | Controls the automated 3-step follow-up sent on every new lead.
     */
    'nurture' => [
        'enabled'                 => env('WHATSAPP_NURTURE_ENABLED', true),
        'welcome_enabled'         => env('WHATSAPP_NURTURE_WELCOME_ENABLED', true),
        'company_profile_enabled' => env('WHATSAPP_NURTURE_COMPANY_PROFILE_ENABLED', true),
        'custom_link_enabled'     => env('WHATSAPP_NURTURE_CUSTOM_LINK_ENABLED', true),

        // Delay windows in seconds
        'company_profile_delay_min' => (int) env('WHATSAPP_NURTURE_PROFILE_DELAY_MIN', 60),
        'company_profile_delay_max' => (int) env('WHATSAPP_NURTURE_PROFILE_DELAY_MAX', 120),
        'custom_link_delay_min'     => (int) env('WHATSAPP_NURTURE_LINK_DELAY_MIN', 600),
        'custom_link_delay_max'     => (int) env('WHATSAPP_NURTURE_LINK_DELAY_MAX', 1200),

        // Configurable content (editable via settings page)
        'thank_you_text'         => env('WHATSAPP_NURTURE_THANK_YOU_TEXT', 'Thank you for reaching out! We\'ve received your details and will be in touch shortly.'),
        'company_profile_text'   => env('WHATSAPP_NURTURE_COMPANY_PROFILE_TEXT', ''),
        'custom_link_url'        => env('WHATSAPP_NURTURE_CUSTOM_LINK_URL', ''),
    ],
];
