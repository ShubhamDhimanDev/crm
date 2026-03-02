<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta Ads Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the Meta (Facebook / Instagram) Lead Ads webhook integration.
    |
    */

    'app_id'       => env('META_APP_ID', ''),

    'app_secret'   => env('META_APP_SECRET', ''),

    'verify_token' => env('META_VERIFY_TOKEN', ''),

    'pixel_id'     => env('META_PIXEL_ID', ''),

    /*
     | Lead source name that will be auto-applied to every lead created from Meta.
     */
    'lead_source_name' => 'Meta Ads',
];
