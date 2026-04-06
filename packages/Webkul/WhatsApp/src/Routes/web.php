<?php

use Illuminate\Support\Facades\Route;
use Webkul\WhatsApp\Http\Controllers\WhatsAppController;

/*
|--------------------------------------------------------------------------
| WhatsApp Routes
|--------------------------------------------------------------------------
| Webhook routes (GET + POST /whatsapp/webhook) are CSRF-exempt.
|
*/

// Webhook verification
Route::get('whatsapp/webhook', [WhatsAppController::class, 'verify'])
    ->name('whatsapp.webhook.verify');

// Inbound messages
Route::post('whatsapp/webhook', [WhatsAppController::class, 'receive'])
    ->name('whatsapp.webhook.receive');
