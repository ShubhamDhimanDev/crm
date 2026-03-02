<?php

namespace Webkul\MetaAds\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\MetaAds\Services\MetaAdsService;

class MetaAdsWebhookController extends Controller
{
    public function __construct(protected MetaAdsService $metaAdsService) {}

    /**
     * GET /meta/webhook
     *
     * Responds to Meta's one-time webhook verification request.
     * Meta sends hub.mode, hub.verify_token, and hub.challenge.
     * We must respond with hub.challenge to confirm ownership.
     */
    public function verify(Request $request): Response|JsonResponse
    {
        Log::info('[MetaAds] Webhook verification request received.', $request->query());

        if (
            $request->query('hub_mode') === 'subscribe' &&
            $request->query('hub_verify_token') === config('meta_ads.verify_token')
        ) {
            return response($request->query('hub_challenge'), 200);
        }

        Log::warning('[MetaAds] Webhook verification failed — token mismatch or wrong mode.');

        return response('Forbidden', 403);
    }

    /**
     * POST /meta/webhook
     *
     * Receives new lead payloads from Meta Lead Ads.
     * Payload structure:
     *   { "object": "page", "entry": [{ "changes": [{ "value": {...}, "field": "leadgen" }] }] }
     */
    public function handle(Request $request): Response
    {
        $rawBody  = $request->getContent();
        $payload  = json_decode($rawBody, true) ?? [];

        // Verify the HMAC-SHA256 signature
        $signature = $request->header('X-Hub-Signature-256', '');

        if (! $this->metaAdsService->verifySignature($rawBody, $signature)) {
            Log::warning('[MetaAds] Signature verification failed.', ['ip' => $request->ip()]);

            return response('Unauthorized', 401);
        }

        // Only process leadgen change events
        if (($payload['object'] ?? '') !== 'page') {
            return response('OK', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') === 'leadgen') {
                    $this->metaAdsService->processLead($change['value'] ?? []);
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * GET /admin/settings/integrations/meta-ads
     */
    public function settings()
    {
        return view('meta_ads::settings');
    }

    /**
     * POST /admin/settings/integrations/meta-ads
     */
    public function saveSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'app_id'       => 'nullable|string|max:255',
            'app_secret'   => 'nullable|string|max:255',
            'verify_token' => 'nullable|string|max:255',
            'pixel_id'     => 'nullable|string|max:255',
        ]);

        // Persist to .env / core_config
        $this->saveToConfig([
            'META_APP_ID'       => $request->app_id,
            'META_APP_SECRET'   => $request->app_secret,
            'META_VERIFY_TOKEN' => $request->verify_token,
            'META_PIXEL_ID'     => $request->pixel_id,
        ]);

        return redirect()
            ->route('admin.settings.integrations.meta-ads')
            ->with('success', 'Meta Ads settings saved.');
    }

    /**
     * Write key=value pairs to the .env file.
     */
    protected function saveToConfig(array $values): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $escaped = addslashes((string) $value);

            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $content);
            } else {
                $content .= "\n{$key}={$escaped}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
