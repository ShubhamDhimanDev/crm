<?php

use Illuminate\Support\Facades\Route;
use Webkul\GoogleAds\Http\Controllers\GoogleAdsWebhookController;

/*
|--------------------------------------------------------------------------
| Google Ads Routes
|--------------------------------------------------------------------------
| CSRF-exempt webhook route. Secret verification is done in the controller.
|
*/

Route::post('google/leads/webhook', [GoogleAdsWebhookController::class, 'handle'])
    ->name('google_ads.webhook.handle');
