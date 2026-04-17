<?php

namespace Webkul\WhatsApp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\WhatsApp\Services\WhatsAppService;

class WhatsAppController extends Controller
{
    public function __construct(
        protected WhatsAppService    $whatsAppService,
        protected ActivityRepository $activityRepository,
        protected LeadRepository     $leadRepository
    ) {}

    // -------------------------------------------------------------------------
    // Webhook (Inbound) — GET verification + POST message receive
    // -------------------------------------------------------------------------

    /**
     * GET /whatsapp/webhook
     *
     * Handles Meta's webhook verification challenge.
     */
    public function verify(Request $request): Response|JsonResponse
    {
        Log::info('[WhatsApp] Webhook verification request.', $request->query());

        if (
            $request->query('hub_mode') === 'subscribe' &&
            $request->query('hub_verify_token') === config('whatsapp.webhook_verify_token')
        ) {
            return response($request->query('hub_challenge'), 200)
                    ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * POST /whatsapp/webhook
     *
     * Receives inbound WhatsApp messages and logs them as activities.
     */
    public function receive(Request $request): Response
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256', '');

        if (! $this->whatsAppService->verifyMetaSignature($rawBody, $signature)) {
            Log::warning('[WhatsApp] Inbound signature mismatch.', ['ip' => $request->ip()]);

            return response('Unauthorized', 401);
        }

        $payload = json_decode($rawBody, true) ?? [];

        $this->processInbound($payload);

        return response('OK', 200);
    }

    /**
     * Extract messages from Meta's WhatsApp Cloud API payload and log activities.
     */
    protected function processInbound(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $message) {
                    if ($message['type'] !== 'text') {
                        continue;
                    }

                    $from = ltrim($message['from'] ?? '', '+');
                    $body = $message['text']['body'] ?? '';

                    // Find a lead whose person matches this phone
                    $lead = $this->findLeadByPhone($from);

                    $this->activityRepository->create([
                        'type'          => 'whatsapp',
                        'title'         => 'WhatsApp — Inbound',
                        'comment'       => $body,
                        'is_done'       => 1,
                        'schedule_from' => now(),
                        'schedule_to'   => now(),
                        'additional'    => json_encode([
                            'direction' => 'inbound',
                            'from'      => $from,
                            'status'    => 'received',
                        ]),
                        'lead_id' => $lead?->id,
                    ]);

                    Log::info('[WhatsApp] Inbound message logged.', [
                        'from'    => $from,
                        'lead_id' => $lead?->id,
                    ]);
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Outbound — Send message from lead detail page
    // -------------------------------------------------------------------------

    /**
     * POST /admin/leads/{lead}/whatsapp/send
     *
     * Sends a WhatsApp message from the lead detail page and logs it as activity.
     */
    public function send(Request $request, int $leadId): JsonResponse
    {
        $request->validate([
            'phone'   => 'required|string',
            'message' => 'required|string|max:4096',
        ]);

        $lead = $this->leadRepository->findOrFail($leadId);

        // Send the message
        $result = $this->whatsAppService->sendMessage($request->phone, $request->message);

        $success = ! isset($result['error']) && ! isset($result['errorCode']);

        // Log activity regardless of delivery status
        $this->activityRepository->create([
            'type'          => 'whatsapp',
            'title'         => 'WhatsApp — Outbound',
            'comment'       => $request->message,
            'is_done'       => 1,
            'schedule_from' => now(),
            'schedule_to'   => now(),
            'user_id'       => Auth::id(),
            'additional'    => json_encode([
                'direction' => 'outbound',
                'to'        => $request->phone,
                'status'    => $success ? 'sent' : 'failed',
            ]),
        ]);

        // Link to lead via pivot
        $activity = $this->activityRepository->getModel()->latest()->first();

        if ($activity) {
            $lead->activities()->attach($activity->id);
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Message sent.' : 'Delivery failed — logged as activity.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Admin settings
    // -------------------------------------------------------------------------

    /**
     * GET /admin/settings/integrations/whatsapp
     */
    public function settings()
    {
        return view('whatsapp::settings');
    }

    /**
     * POST /admin/settings/integrations/whatsapp
     */
    public function saveSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'provider'                      => 'nullable|string|in:meta,twilio,360dialog',
            'from_number'                   => 'nullable|string|max:50',
            'api_key'                       => 'nullable|string|max:500',
            'webhook_verify_token'          => 'nullable|string|max:255',
            // Nurture fields
            'nurture_enabled'               => 'nullable|string|in:true,false',
            'nurture_welcome_enabled'       => 'nullable|string|in:true,false',
            'nurture_company_profile_enabled' => 'nullable|string|in:true,false',
            'nurture_custom_link_enabled'   => 'nullable|string|in:true,false',
            'nurture_thank_you_text'        => 'nullable|string|max:1000',
            'nurture_company_profile_text'  => 'nullable|string|max:4000',
            'nurture_custom_link_url'       => 'nullable|url|max:500',
        ]);

        $envMap = [
            'WHATSAPP_PROVIDER'             => $request->provider,
            'WHATSAPP_FROM_NUMBER'          => $request->from_number,
            'WHATSAPP_API_KEY'              => $request->api_key,
            'WHATSAPP_WEBHOOK_VERIFY_TOKEN' => $request->webhook_verify_token,
            // Nurture sequence
            'WHATSAPP_NURTURE_ENABLED'                  => $request->input('nurture_enabled', 'true'),
            'WHATSAPP_NURTURE_WELCOME_ENABLED'          => $request->input('nurture_welcome_enabled', 'true'),
            'WHATSAPP_NURTURE_COMPANY_PROFILE_ENABLED'  => $request->input('nurture_company_profile_enabled', 'true'),
            'WHATSAPP_NURTURE_CUSTOM_LINK_ENABLED'      => $request->input('nurture_custom_link_enabled', 'true'),
            'WHATSAPP_NURTURE_THANK_YOU_TEXT'           => $request->nurture_thank_you_text,
            'WHATSAPP_NURTURE_COMPANY_PROFILE_TEXT'     => $request->nurture_company_profile_text,
            'WHATSAPP_NURTURE_CUSTOM_LINK_URL'          => $request->nurture_custom_link_url,
        ];

        $envPath = base_path('.env');

        if (file_exists($envPath)) {
            $content = file_get_contents($envPath);

            foreach ($envMap as $key => $value) {
                // Always wrap in double quotes so values with spaces/apostrophes are valid .env
                $escaped = '"'.str_replace('"', '\\"', (string) $value).'"';

                if (preg_match("/^{$key}=/m", $content)) {
                    $content = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $content);
                } else {
                    $content .= "\n{$key}={$escaped}";
                }
            }

            file_put_contents($envPath, $content);
        }

        return redirect()
            ->route('admin.settings.integrations.whatsapp')
            ->with('success', 'WhatsApp settings saved.');
    }

    /**
     * GET /admin/settings/integrations/whatsapp/test-template
     */
    public function testTemplatePage()
    {
        return view('whatsapp::test-template');
    }

    /**
     * POST /admin/settings/integrations/whatsapp/test-template
     */
    public function sendTestTemplate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'phone'         => 'required|string|max:30',
            'language_code' => 'nullable|string|max:20',
        ]);

        if (! config('whatsapp.from_number') || ! config('whatsapp.api_key')) {
            return back()->with('error', 'Please configure WHATSAPP_FROM_NUMBER (Meta Phone Number ID) and WHATSAPP_API_KEY first.');
        }

        $result = $this->whatsAppService->sendTemplate(
            $request->phone,
            'hello_world',
            $request->input('language_code', 'en_US')
        );

        $success = ! isset($result['error']);

        return back()
            ->with($success ? 'success' : 'error', $success ? 'Template sent.' : 'Template send failed.')
            ->with('result', $result);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Try to find a lead whose contact_numbers contain the given phone number.
     */
    protected function findLeadByPhone(string $phone): ?\Webkul\Lead\Models\Lead
    {
        $person = \Webkul\Contact\Models\Person::whereRaw(
            "JSON_SEARCH(contact_numbers, 'one', ?) IS NOT NULL",
            [$phone]
        )->first();

        if (! $person) {
            return null;
        }

        return $this->leadRepository->findOneWhere(['person_id' => $person->id]);
    }
}
