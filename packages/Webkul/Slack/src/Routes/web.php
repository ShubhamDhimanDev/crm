<?php

use Illuminate\Support\Facades\Route;
use Webkul\Slack\Http\Controllers\SlackCommandController;
use Webkul\Slack\Http\Controllers\SlackWebhookController;

/*
|--------------------------------------------------------------------------
| Slack Routes
|--------------------------------------------------------------------------
|
| All routes below are excluded from CSRF verification (see VerifyCsrfToken).
| Signatures are verified inside each controller using Slack's signing secret.
|
*/

// Events API — inbound lead capture & event callbacks
Route::post('slack/webhook', [SlackWebhookController::class, 'handle'])
    ->name('slack.webhook');

// Slash command: /newlead — opens an interactive modal form (A6)
Route::post('slack/command/newlead', [SlackCommandController::class, 'newLead'])
    ->name('slack.command.newlead');

// Interactive components — handles modal submissions, block actions, etc. (A6)
Route::post('slack/interaction', [SlackCommandController::class, 'interaction'])
    ->name('slack.interaction');
