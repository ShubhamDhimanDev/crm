<?php

namespace Webkul\WhatsApp\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadCustomLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly mixed  $lead,
        public readonly string $name,
        public readonly string $linkUrl
    ) {}

    public function build(): static
    {
        return $this
            ->subject("One more thing — we'd love to know more about you")
            ->view('whatsapp::emails.custom-link');
    }
}
