<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'admin/mail/inbound-parse',
        'admin/web-forms/forms/*',
        'web-forms/*',
        'slack/webhook',
        'slack/command/newlead',
        'slack/interaction',
        'meta/webhook',
        'google/leads/webhook',
        'whatsapp/webhook',
    ];
}
