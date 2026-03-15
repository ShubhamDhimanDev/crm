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
use Webkul\WhatsApp\Mails\LeadCompanyProfileMail;
use Webkul\WhatsApp\Services\WhatsAppService;

class SendLeadCompanyProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly mixed $lead) {}

    public function handle(WhatsAppService $whatsApp, ActivityRepository $activities): void
    {
        if (! config('whatsapp.nurture.enabled') || ! config('whatsapp.nurture.company_profile_enabled')) {
            return;
        }

        $profileText = config('whatsapp.nurture.company_profile_text');

        if (empty(trim($profileText))) {
            Log::info('[Nurture] Company profile step skipped — no content configured.', ['lead_id' => $this->lead->id]);

            return;
        }

        $contact = $whatsApp->resolveLeadContact($this->lead);

        // Send email
        if ($contact['email']) {
            try {
                Mail::to($contact['email'])->send(
                    new LeadCompanyProfileMail($this->lead, $contact['name'], $profileText)
                );
            } catch (\Throwable $e) {
                Log::error('[Nurture] Company profile email failed.', ['lead_id' => $this->lead->id, 'error' => $e->getMessage()]);
            }
        }

        // Send WhatsApp
        if ($contact['phone']) {
            try {
                $whatsApp->sendMessage($contact['phone'], $profileText);
            } catch (\Throwable $e) {
                Log::error('[Nurture] Company profile WhatsApp failed.', ['lead_id' => $this->lead->id, 'error' => $e->getMessage()]);
            }
        }

        // Log activity
        $activity = $activities->create([
            'type'          => 'whatsapp',
            'title'         => 'Nurture — Step 2: Company Profile',
            'comment'       => $profileText,
            'is_done'       => 1,
            'schedule_from' => now(),
            'schedule_to'   => now(),
            'additional'    => json_encode([
                'direction' => 'outbound',
                'channel'   => 'whatsapp+email',
                'step'      => 2,
                'status'    => 'sent',
            ]),
        ]);

        if ($activity) {
            $this->lead->activities()->attach($activity->id);
        }
    }
}
