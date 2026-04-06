<?php

namespace Webkul\WhatsApp\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly mixed  $lead,
        public readonly string $name,
        public readonly string $bodyText
    ) {}

    public function build(): static
    {
        return $this
            ->subject("Thank you for your interest, {$this->name}!")
            ->view('whatsapp::emails.thank-you');
    }
}
