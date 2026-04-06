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
use Webkul\WhatsApp\Mails\LeadCustomLinkMail;
use Webkul\WhatsApp\Services\WhatsAppService;

class SendLeadCustomLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly mixed $lead) {}

    public function handle(WhatsAppService $whatsApp, ActivityRepository $activities): void
    {
        if (! config('whatsapp.nurture.enabled') || ! config('whatsapp.nurture.custom_link_enabled')) {
            return;
        }

        $linkUrl = config('whatsapp.nurture.custom_link_url');

        if (empty(trim($linkUrl))) {
            Log::info('[Nurture] Custom link step skipped — no URL configured.', ['lead_id' => $this->lead->id]);

            return;
        }

        $contact = $whatsApp->resolveLeadContact($this->lead);
        $message = "We'd love to know more about you! Please fill out this quick form: {$linkUrl}";

        // Send email
        if ($contact['email']) {
            try {
                Mail::to($contact['email'])->send(
                    new LeadCustomLinkMail($this->lead, $contact['name'], $linkUrl)
                );
            } catch (\Throwable $e) {
                Log::error('[Nurture] Custom link email failed.', ['lead_id' => $this->lead->id, 'error' => $e->getMessage()]);
            }
        }

        // Send WhatsApp
        if ($contact['phone']) {
            try {
                $whatsApp->sendMessage($contact['phone'], $message);
            } catch (\Throwable $e) {
                Log::error('[Nurture] Custom link WhatsApp failed.', ['lead_id' => $this->lead->id, 'error' => $e->getMessage()]);
            }
        }

        // Log activity
        $activity = $activities->create([
            'type'          => 'whatsapp',
            'title'         => 'Nurture — Step 3: Custom Link',
            'comment'       => $message,
            'is_done'       => 1,
            'schedule_from' => now(),
            'schedule_to'   => now(),
            'additional'    => json_encode([
                'direction' => 'outbound',
                'channel'   => 'whatsapp+email',
                'step'      => 3,
                'status'    => 'sent',
                'link'      => $linkUrl,
            ]),
        ]);

        if ($activity) {
            $this->lead->activities()->attach($activity->id);
        }
    }
}
