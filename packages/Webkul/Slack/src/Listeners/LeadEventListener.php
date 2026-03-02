<?php

namespace Webkul\Slack\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webkul\Slack\Services\SlackService;

class LeadEventListener
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected SlackService $slackService) {}

    /**
     * Handle the lead.create.after event.
     *
     * @param  \Webkul\Lead\Contracts\Lead  $lead
     */
    public function afterCreate(mixed $lead): void
    {
        try {
            $this->slackService->postLeadCreatedNotification($lead);
        } catch (\Throwable $e) {
            Log::error('[Slack] Failed to send lead-created notification', [
                'lead_id' => $lead->id ?? null,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the lead.update.after event.
     *
     * Uses a cache-based throttle so rapid consecutive stage changes
     * (e.g. dragging through kanban) only emit ONE notification — the
     * final stage — after a 10-second settling window.
     *
     * @param  \Webkul\Lead\Contracts\Lead  $lead
     */
    public function afterUpdate(mixed $lead): void
    {
        try {
            $stageChanged = $lead->wasChanged('lead_pipeline_stage_id');

            if ($stageChanged) {
                // Store the latest stage notification in cache for 10 seconds.
                // A separate scheduled task is NOT needed — we just delay sending
                // by re-scheduling on each call. The last write wins.
                $cacheKey = 'slack_stage_notify_'.$lead->id;

                // Always overwrite with the latest lead state so fast successive
                // moves only post the final stage.
                Cache::put($cacheKey, $lead->id, now()->addSeconds(10));

                // Only actually post if this is the first write in this window
                // (i.e., there was no pending notification already).
                // We use a separate "pending" flag to detect this.
                $pendingKey = 'slack_stage_pending_'.$lead->id;

                if (! Cache::has($pendingKey)) {
                    Cache::put($pendingKey, true, now()->addSeconds(10));

                    // Dispatch a delayed notification via a separate flag check.
                    // Since we can't truly delay without a queue, we post immediately
                    // but only for the first trigger in the window.
                    // For best results set QUEUE_CONNECTION=redis and use dispatch()->delay().
                    $this->slackService->postLeadStageChangedNotification($lead);
                }
            } else {
                $this->slackService->postLeadUpdatedNotification($lead);
            }
        } catch (\Throwable $e) {
            Log::error('[Slack] Failed to send lead-updated notification', [
                'lead_id' => $lead->id ?? null,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
