<?php

namespace Webkul\WhatsApp\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadCompanyProfileMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly mixed  $lead,
        public readonly string $name,
        public readonly string $profileText
    ) {}

    public function build(): static
    {
        $appName = config('app.name', 'Us');

        return $this
            ->subject("About {$appName} — Here's what we do")
            ->view('whatsapp::emails.company-profile');
    }
}
