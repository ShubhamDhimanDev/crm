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
use Webkul\Slack\Services\SlackService;
use Webkul\User\Repositories\UserRepository;

class SlackCommandController extends Controller
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
        protected SlackService $slackService
    ) {}

    /**
     * Handle the /newlead slash command.
     *
     * Slack POSTs URL-encoded form data. We respond within 3 seconds
     * by opening a modal using the trigger_id from the payload.
     */
    public function newLead(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        $rawBody = $request->getContent();

        if (! $this->verifySignature($request, $rawBody)) {
            Log::warning('[Slack] Invalid signature on /newlead command.', ['ip' => $request->ip()]);

            return response('Unauthorized', 401);
        }

        $triggerId = $request->input('trigger_id');

        if (empty($triggerId)) {
            return response()->json(['text' => 'Invalid request: missing trigger_id.'], 200);
        }

        // Build the list of users for the "Assign To" select
        $userOptions = $this->userRepository
            ->where('status', 1)
            ->get(['id', 'name'])
            ->map(fn ($u) => [
                'text'  => ['type' => 'plain_text', 'text' => $u->name],
                'value' => (string) $u->id,
            ])
            ->values()
            ->toArray();

        $modal = [
            'type'            => 'modal',
            'callback_id'     => 'newlead_modal',
            'title'           => ['type' => 'plain_text', 'text' => 'Create New Lead'],
            'submit'          => ['type' => 'plain_text', 'text' => 'Create Lead'],
            'close'           => ['type' => 'plain_text', 'text' => 'Cancel'],
            'blocks'          => [
                // Full Name (required)
                [
                    'type'     => 'input',
                    'block_id' => 'full_name_block',
                    'label'    => ['type' => 'plain_text', 'text' => 'Full Name'],
                    'element'  => [
                        'type'        => 'plain_text_input',
                        'action_id'   => 'full_name',
                        'placeholder' => ['type' => 'plain_text', 'text' => 'e.g. John Smith'],
                    ],
                ],
                // Phone (required)
                [
                    'type'     => 'input',
                    'block_id' => 'phone_block',
                    'label'    => ['type' => 'plain_text', 'text' => 'Phone Number'],
                    'element'  => [
                        'type'        => 'plain_text_input',
                        'action_id'   => 'phone',
                        'placeholder' => ['type' => 'plain_text', 'text' => '+1 555 000 0000'],
                    ],
                ],
                // Email (optional)
                [
                    'type'     => 'input',
                    'block_id' => 'email_block',
                    'label'    => ['type' => 'plain_text', 'text' => 'Email'],
                    'optional' => true,
                    'element'  => [
                        'type'      => 'plain_text_input',
                        'action_id' => 'email',
                    ],
                ],
                // Source Note (optional)
                [
                    'type'     => 'input',
                    'block_id' => 'source_note_block',
                    'label'    => ['type' => 'plain_text', 'text' => 'Source Note'],
                    'optional' => true,
                    'element'  => [
                        'type'      => 'plain_text_input',
                        'action_id' => 'source_note',
                    ],
                ],
                // Lead Value (optional)
                [
                    'type'     => 'input',
                    'block_id' => 'lead_value_block',
                    'label'    => ['type' => 'plain_text', 'text' => 'Lead Value'],
                    'optional' => true,
                    'element'  => [
                        'type'        => 'plain_text_input',
                        'action_id'   => 'lead_value',
                        'placeholder' => ['type' => 'plain_text', 'text' => 'e.g. 5000'],
                    ],
                ],
                // Priority (optional)
                [
                    'type'     => 'input',
                    'block_id' => 'priority_block',
                    'label'    => ['type' => 'plain_text', 'text' => 'Priority'],
                    'optional' => true,
                    'element'  => [
                        'type'        => 'static_select',
                        'action_id'   => 'priority',
                        'placeholder' => ['type' => 'plain_text', 'text' => 'Select priority'],
                        'options'     => [
                            ['text' => ['type' => 'plain_text', 'text' => '🔴 Hot'],  'value' => 'hot'],
                            ['text' => ['type' => 'plain_text', 'text' => '🟠 Warm'], 'value' => 'warm'],
                            ['text' => ['type' => 'plain_text', 'text' => '🔵 Cold'], 'value' => 'cold'],
                        ],
                    ],
                ],
                // Assign To (optional) — only shown if there are users
                ...( ! empty($userOptions) ? [[
                    'type'     => 'input',
                    'block_id' => 'assign_to_block',
                    'label'    => ['type' => 'plain_text', 'text' => 'Assign To'],
                    'optional' => true,
                    'element'  => [
                        'type'        => 'static_select',
                        'action_id'   => 'assign_to',
                        'placeholder' => ['type' => 'plain_text', 'text' => 'Select a sales rep'],
                        'options'     => $userOptions,
                    ],
                ]] : []),
                // Notes (optional)
                [
                    'type'     => 'input',
                    'block_id' => 'notes_block',
                    'label'    => ['type' => 'plain_text', 'text' => 'Notes'],
                    'optional' => true,
                    'element'  => [
                        'type'      => 'plain_text_input',
                        'action_id' => 'notes',
                        'multiline' => true,
                    ],
                ],
            ],
        ];

        $result = $this->slackService->openModal($triggerId, $modal);

        if (! ($result['ok'] ?? false)) {
            Log::warning('[Slack] Failed to open /newlead modal.', ['result' => $result]);

            return response()->json(['text' => 'Could not open form. Please try again.'], 200);
        }

        // Return an empty 200 — the modal is opened via the API call above.
        return response('', 200);
    }

    /**
     * Handle Slack interactive component payloads (view_submission, block_actions, etc.).
     *
     * Slack POSTs a URL-encoded field called `payload` containing JSON.
     */
    public function interaction(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        $rawBody = $request->getContent();

        if (! $this->verifySignature($request, $rawBody)) {
            Log::warning('[Slack] Invalid signature on interaction endpoint.', ['ip' => $request->ip()]);

            return response('Unauthorized', 401);
        }

        $payloadJson = $request->input('payload');

        if (empty($payloadJson)) {
            return response('Bad Request', 400);
        }

        $payload = json_decode($payloadJson, true) ?? [];
        $type    = $payload['type'] ?? null;

        if ($type === 'view_submission') {
            $callbackId = $payload['view']['callback_id'] ?? null;

            if ($callbackId === 'newlead_modal') {
                return $this->handleNewLeadModalSubmission($payload);
            }
        }

        return response('OK', 200);
    }

    /**
     * Process a submitted /newlead modal form and create a CRM lead.
     */
    protected function handleNewLeadModalSubmission(array $payload): Response|\Illuminate\Http\JsonResponse
    {
        $values = $payload['view']['state']['values'] ?? [];
        $userId = $payload['user']['id'] ?? null; // Slack user ID

        // Extract field values from the block state
        $fullName   = $values['full_name_block']['full_name']['value'] ?? null;
        $phone      = $values['phone_block']['phone']['value'] ?? null;
        $email      = $values['email_block']['email']['value'] ?? null;
        $sourceNote = $values['source_note_block']['source_note']['value'] ?? null;
        $leadValue  = $values['lead_value_block']['lead_value']['value'] ?? null;
        $priority   = $values['priority_block']['priority']['selected_option']['value'] ?? null;
        $assignToId = $values['assign_to_block']['assign_to']['selected_option']['value'] ?? null;
        $notes      = $values['notes_block']['notes']['value'] ?? null;

        if (empty($fullName) || empty($phone)) {
            return response()->json([
                'response_action' => 'errors',
                'errors'          => array_filter([
                    'full_name_block' => empty($fullName) ? 'Full name is required.' : null,
                    'phone_block'     => empty($phone) ? 'Phone number is required.' : null,
                ]),
            ]);
        }

        try {
            // Build person data
            $personData = [
                'name'            => $fullName,
                'contact_numbers' => [['value' => $phone, 'label' => 'work']],
            ];

            if ($email) {
                $personData['emails'] = [['value' => $email, 'label' => 'work']];
            }

            // Deduplication: check for existing person by email or phone
            $existingPerson = null;

            if ($email) {
                $existingPerson = DB::table('persons')
                    ->whereJsonContains('emails', ['value' => $email])
                    ->first();
            }

            if (! $existingPerson && $phone) {
                $existingPerson = DB::table('persons')
                    ->whereJsonContains('contact_numbers', ['value' => $phone])
                    ->first();
            }

            $person = $existingPerson
                ? $this->personRepository->find($existingPerson->id)
                : $this->personRepository->create($personData);

            // Resolve lead source ("Slack")
            $source = $this->sourceRepository->findOneByField('name', 'Slack')
                ?? $this->sourceRepository->create(['name' => 'Slack']);

            // Resolve default pipeline and first stage
            $pipeline      = $this->pipelineRepository->getDefaultPipeline();
            $pipelineId    = $pipeline?->id;
            $firstStageId  = $pipeline?->stages()->first()?->id;

            // Resolve lead owner
            $ownerId = null;

            if ($assignToId) {
                $ownerId = (int) $assignToId;
            } else {
                $ownerId = $this->userRepository->findOneByField('status', 1)?->id;
            }

            // Resolve lead type
            $type = $this->typeRepository->findOneByField('name', 'New Business')
                ?? $this->typeRepository->first();

            // Build lead title from person name
            $title = "Lead — {$fullName}";

            $leadData = [
                'title'                   => $title,
                'description'             => $notes,
                'lead_value'              => $leadValue ? (float) preg_replace('/[^0-9.]/', '', $leadValue) : null,
                'lead_source_id'          => $source->id,
                'lead_type_id'            => $type?->id,
                'lead_pipeline_id'        => $pipelineId,
                'lead_pipeline_stage_id'  => $firstStageId,
                'user_id'                 => $ownerId,
                'person_id'               => $person->id,
                'priority'                => $priority,
                'status'                  => 1,
            ];

            if ($sourceNote) {
                $leadData['description'] = trim(($notes ?? '') . "\nSource: {$sourceNote}");
            }

            $lead = $this->leadRepository->create($leadData);

            Event::dispatch('lead.create.after', $lead);

            Log::info('[Slack] Lead created via /newlead modal.', [
                'lead_id' => $lead->id,
                'user'    => $userId,
            ]);

            // Close the modal on success — no response_action needed (default closes)
            return response('', 200);
        } catch (\Throwable $e) {
            Log::error('[Slack] Failed to create lead from /newlead modal.', [
                'error' => $e->getMessage(),
                'user'  => $userId,
            ]);

            return response()->json([
                'response_action' => 'errors',
                'errors'          => [
                    'full_name_block' => 'Failed to create lead: '.$e->getMessage(),
                ],
            ]);
        }
    }

    /**
     * Verify the Slack request signature (shared with SlackWebhookController).
     */
    protected function verifySignature(Request $request, string $rawBody): bool
    {
        $signingSecret = config('slack.signing_secret');

        if (empty($signingSecret)) {
            Log::warning('[Slack] No signing secret configured – skipping signature verification.');

            return true;
        }

        $slackSignature = $request->header('X-Slack-Signature');
        $slackTimestamp = $request->header('X-Slack-Request-Timestamp');

        if (! $slackSignature || ! $slackTimestamp) {
            return false;
        }

        if (abs(time() - (int) $slackTimestamp) > 300) {
            Log::warning('[Slack] Timestamp is too old – possible replay attack.');

            return false;
        }

        $baseString        = "v0:{$slackTimestamp}:{$rawBody}";
        $computedSignature = 'v0='.hash_hmac('sha256', $baseString, $signingSecret);

        return hash_equals($computedSignature, $slackSignature);
    }
}
