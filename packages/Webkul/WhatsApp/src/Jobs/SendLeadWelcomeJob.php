<?php

namespace Webkul\WhatsApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\WhatsApp\Mails\LeadThankYouMail;
use Webkul\WhatsApp\Services\WhatsAppService;

class SendLeadWelcomeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly mixed $lead) {}

    public function handle(WhatsAppService $whatsApp, ActivityRepository $activities): void
    {
        if (! config('whatsapp.nurture.enabled') || ! config('whatsapp.nurture.welcome_enabled')) {
            return;
        }

        $contact  = $whatsApp->resolveLeadContact($this->lead);
        $bodyText = config('whatsapp.nurture.thank_you_text');

        // Send email
        if ($contact['email']) {
            try {
                Mail::to($contact['email'])->send(
                    new LeadThankYouMail($this->lead, $contact['name'], $bodyText)
                );
            } catch (\Throwable $e) {
                Log::error('[Nurture] Welcome email failed.', ['lead_id' => $this->lead->id, 'error' => $e->getMessage()]);
            }
        }

        // Send WhatsApp
        if ($contact['phone']) {
            try {
                $whatsApp->sendMessage($contact['phone'], $bodyText);
            } catch (\Throwable $e) {
                Log::error('[Nurture] Welcome WhatsApp failed.', ['lead_id' => $this->lead->id, 'error' => $e->getMessage()]);
            }
        }

        // Log activity
        $activity = $activities->create([
            'type'          => 'whatsapp',
            'title'         => 'Nurture — Step 1: Thank-you',
            'comment'       => $bodyText,
            'is_done'       => 1,
            'schedule_from' => now(),
            'schedule_to'   => now(),
            'additional'    => json_encode([
                'direction' => 'outbound',
                'channel'   => 'whatsapp+email',
                'step'      => 1,
                'status'    => 'sent',
            ]),
        ]);

        if ($activity) {
            $this->lead->activities()->attach($activity->id);
        }
    }
}
