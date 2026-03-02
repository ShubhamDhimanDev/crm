<?php

namespace Webkul\Slack\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\Slack\Services\LeadParser;
use Webkul\Slack\Services\SlackService;
use Webkul\User\Repositories\UserRepository;

class SlackWebhookController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected LeadRepository $leadRepository,
        protected SourceRepository $sourceRepository,
        protected PipelineRepository $pipelineRepository,
        protected TypeRepository $typeRepository,
        protected UserRepository $userRepository,
        protected PersonRepository $personRepository,
        protected LeadParser $leadParser,
        protected SlackService $slackService
    ) {}

    /**
     * Handle an incoming Slack Events API payload.
     *
     * Slack sends two types of requests to this endpoint:
     *  1. URL Verification challenge (one-time, during app setup)
     *  2. Event callbacks (message events, etc.)
     */
    public function handle(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        // Read the raw body ONCE here — needed for both challenge response
        // and signature verification (must be the exact bytes Slack signed).
        $rawBody = file_get_contents('php://input') ?: $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];
        $type    = $payload['type'] ?? null;

        // ── 1. URL Verification challenge ─────────────────────────────────
        // Handle BEFORE signature check: the challenge token itself is proof
        // of origin, and Slack needs a 200 response before signature headers
        // can be meaningfully tested.
        if ($type === 'url_verification') {
            Log::info('[Slack] URL verification challenge received.');

            return response()->json(['challenge' => $payload['challenge']]);
        }

        // ── 2. Verify signature for all other requests ────────────────────
        if (! $this->verifySignature($request, $rawBody)) {
            Log::warning('[Slack] Invalid or missing signature on inbound webhook.', [
                'ip'        => $request->ip(),
                'has_sig'   => (bool) $request->header('X-Slack-Signature'),
                'has_ts'    => (bool) $request->header('X-Slack-Request-Timestamp'),
            ]);

            return response('Unauthorized', 401);
        }

        // ── 3. Event Callback ─────────────────────────────────────────────
        if ($type === 'event_callback') {
            $event = $payload['event'] ?? [];

            $this->processEvent($event, $payload);
        }

        return response('OK', 200);
    }

    /**
     * Process a Slack event.
     */
    protected function processEvent(array $event, array $payload): void
    {
        $eventType = $event['type'] ?? null;

        // Only handle regular channel messages (not bot messages, not edits)
        if ($eventType !== 'message') {
            return;
        }

        // Ignore bot messages and message_changed / message_deleted subtypes
        if (! empty($event['bot_id']) || ! empty($event['subtype'])) {
            return;
        }

        $text    = $event['text'] ?? '';
        $channel = $event['channel'] ?? '';
        $ts      = $event['ts'] ?? null;

        if (! config('slack.lead_capture_enabled')) {
            return;
        }

        if (! $this->leadParser->isLeadMessage($text)) {
            return;
        }

        // ── Parse & create the lead ────────────────────────────────────────
        try {
            $parsed   = $this->leadParser->parse($text);
            $leadData = $this->leadParser->toLeadData($parsed);

            // Assign a "Slack" source (create it if it doesn't exist yet)
            $source = $this->sourceRepository->findOneByField('name', 'Slack')
                ?? $this->sourceRepository->create(['name' => 'Slack']);

            $leadData['lead_source_id'] = $source->id;

            // Resolve the default pipeline and its first stage.
            // This mirrors what the admin LeadController does and ensures the
            // lead appears in the correct pipeline board immediately.
            if (empty($leadData['lead_pipeline_id'])) {
                $pipeline = $this->pipelineRepository->getDefaultPipeline();

                if ($pipeline) {
                    $leadData['lead_pipeline_id'] = $pipeline->id;

                    if (empty($leadData['lead_pipeline_stage_id'])) {
                        $firstStage = $pipeline->stages()->first();
                        $leadData['lead_pipeline_stage_id'] = $firstStage?->id;
                    }
                }
            }

            // Assign default owner — the first active admin user.
            // This prevents a null user.name crash in the CRM frontend.
            if (empty($leadData['user_id'])) {
                $defaultUser = $this->userRepository->findOneByField('status', 1);
                $leadData['user_id'] = $defaultUser?->id;
            }

            // Assign lead type: prefer "New Business", fall back to first available.
            if (empty($leadData['lead_type_id'])) {
                $type = $this->typeRepository->findOneByField('name', 'New Business')
                    ?? $this->typeRepository->first();
                $leadData['lead_type_id'] = $type?->id;
            }

            // Avoid duplicate person creation: if a person with the same email
            // or phone already exists in the CRM, link to them instead.
            if (isset($leadData['person']) && empty($leadData['person']['id'])) {
                $existingPerson = null;

                // Search by email first (most reliable unique identifier)
                $email = $leadData['person']['emails'][0]['value'] ?? null;

                if ($email) {
                    $existingPerson = DB::table('persons')
                        ->whereJsonContains('emails', ['value' => $email])
                        ->first();
                }

                // Fall back to phone search if no email match
                if (! $existingPerson) {
                    $phone = $leadData['person']['contact_numbers'][0]['value'] ?? null;

                    if ($phone) {
                        $existingPerson = DB::table('persons')
                            ->whereJsonContains('contact_numbers', ['value' => $phone])
                            ->first();
                    }
                }

                if ($existingPerson) {
                    // Re-use the existing person — no INSERT, no duplicate error
                    $leadData['person']['id'] = $existingPerson->id;

                    // Check whether this person already has an open (non-won/lost) lead.
                    // If so, skip creating a duplicate and just reply with the existing lead link.
                    $openLead = DB::table('leads')
                        ->join('lead_pipeline_stages', 'leads.lead_pipeline_stage_id', '=', 'lead_pipeline_stages.id')
                        ->where('leads.person_id', $existingPerson->id)
                        ->where('leads.status', 1)
                        ->whereNotIn('lead_pipeline_stages.code', ['won', 'lost'])
                        ->select('leads.id', 'leads.title')
                        ->first();

                    if ($openLead && $channel && $ts) {
                        $this->slackService->postReply(
                            $channel,
                            $ts,
                            "ℹ️ *{$existingPerson->name}* already has an open lead: *{$openLead->title}* (ID #{$openLead->id}). No duplicate was created."
                        );

                        return;
                    }
                }
            }

            $lead = $this->leadRepository->create($leadData);

            // Fire the standard CRM event so Automation workflows also run
            Event::dispatch('lead.create.after', $lead);

            // ── Reply in Slack ─────────────────────────────────────────────
            if ($channel && $ts) {
                $personName = optional($lead->person)->name ?? 'Unknown';
                $value      = $lead->lead_value ? ' | Value: $'.number_format($lead->lead_value, 2) : '';

                $this->slackService->postReply(
                    $channel,
                    $ts,
                    "✅ Lead *{$lead->title}* created in CRM for contact *{$personName}*{$value}. (ID #{$lead->id})"
                );
            }
        } catch (\Throwable $e) {
            Log::error('[Slack] Failed to create lead from message', [
                'error'   => $e->getMessage(),
                'text'    => $text,
                'channel' => $channel,
            ]);

            // Notify the channel that something went wrong
            if ($channel && $ts) {
                $this->slackService->postReply(
                    $channel,
                    $ts,
                    "⚠️ Could not create lead. Please check the format and try again.\nError: ".$e->getMessage()
                );
            }
        }
    }

    /**
     * Verify that the incoming request is genuinely from Slack using
     * HMAC-SHA256 signing secret verification.
     *
     * @see https://api.slack.com/authentication/verifying-requests-from-slack
     */
    protected function verifySignature(Request $request, string $rawBody): bool
    {
        $signingSecret = config('slack.signing_secret');

        // If no signing secret is configured, skip verification (dev/test mode)
        if (empty($signingSecret)) {
            Log::warning('[Slack] No signing secret configured – skipping signature verification.');

            return true;
        }

        $slackSignature = $request->header('X-Slack-Signature');
        $slackTimestamp = $request->header('X-Slack-Request-Timestamp');

        if (! $slackSignature || ! $slackTimestamp) {
            return false;
        }

        // Reject requests older than 5 minutes to prevent replay attacks
        if (abs(time() - (int) $slackTimestamp) > 300) {
            Log::warning('[Slack] Webhook timestamp is too old – possible replay attack.');

            return false;
        }

        $baseString        = "v0:{$slackTimestamp}:{$rawBody}";
        $computedSignature = 'v0='.hash_hmac('sha256', $baseString, $signingSecret);

        return hash_equals($computedSignature, $slackSignature);
    }
}
