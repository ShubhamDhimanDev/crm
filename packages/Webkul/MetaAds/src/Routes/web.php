<?php

use Illuminate\Support\Facades\Route;
use Webkul\MetaAds\Http\Controllers\MetaAdsWebhookController;

/*
|--------------------------------------------------------------------------
| Meta Ads Routes
|--------------------------------------------------------------------------
|
| Webhook routes are CSRF-exempt (see VerifyCsrfToken.php).
| Signature verification is handled inside the controller.
|
*/

// Meta webhook verification (GET) and lead delivery (POST)
Route::get('meta/webhook', [MetaAdsWebhookController::class, 'verify'])
    ->name('meta_ads.webhook.verify');

Route::post('meta/webhook', [MetaAdsWebhookController::class, 'handle'])
    ->name('meta_ads.webhook.handle');
