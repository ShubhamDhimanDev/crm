<?php

namespace Webkul\WhatsApp\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\WhatsApp\Jobs\SendLeadCompanyProfileJob;
use Webkul\WhatsApp\Jobs\SendLeadCustomLinkJob;
use Webkul\WhatsApp\Jobs\SendLeadWelcomeJob;

class LeadNurtureListener
{
    /**
     * Handle the lead.create.after event.
     *
     * Dispatches the 3-step nurture sequence as delayed queue jobs.
     */
    public function handle(mixed $lead): void
    {
        if (! config('whatsapp.nurture.enabled', true)) {
            return;
        }

        try {
            // Step 1 — immediate thank-you
            SendLeadWelcomeJob::dispatch($lead);

            // Step 2 — company profile (1–2 min delay)
            $profileDelay = rand(
                (int) config('whatsapp.nurture.company_profile_delay_min', 60),
                (int) config('whatsapp.nurture.company_profile_delay_max', 120)
            );
            SendLeadCompanyProfileJob::dispatch($lead)->delay(now()->addSeconds($profileDelay));

            // Step 3 — custom link (10–20 min delay)
            $linkDelay = rand(
                (int) config('whatsapp.nurture.custom_link_delay_min', 600),
                (int) config('whatsapp.nurture.custom_link_delay_max', 1200)
            );
            SendLeadCustomLinkJob::dispatch($lead)->delay(now()->addSeconds($linkDelay));

            Log::info('[Nurture] Sequence dispatched.', [
                'lead_id'       => $lead->id ?? null,
                'profile_delay' => $profileDelay,
                'link_delay'    => $linkDelay,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Nurture] Failed to dispatch sequence.', [
                'lead_id' => $lead->id ?? null,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
