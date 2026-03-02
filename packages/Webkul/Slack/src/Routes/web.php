<?php

use Illuminate\Support\Facades\Route;
use Webkul\Slack\Http\Controllers\SlackWebhookController;

/*
|--------------------------------------------------------------------------
| Slack Routes
|--------------------------------------------------------------------------
|
| Webhook endpoint for receiving events from Slack's Events API.
| This route is excluded from CSRF verification (see VerifyCsrfToken).
|
*/

Route::post('slack/webhook', [SlackWebhookController::class, 'handle'])
    ->name('slack.webhook');
