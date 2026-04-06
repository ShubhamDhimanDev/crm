<?php

namespace Webkul\GoogleAds\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\GoogleAds\Services\GoogleAdsService;

class GoogleAdsWebhookController extends Controller
{
    public function __construct(protected GoogleAdsService $googleAdsService) {}

    /**
     * POST /google/leads/webhook
     *
     * Receives new lead payloads from Google Ads Lead Forms.
     *
     * Authentication: shared secret in X-Google-Webhook-Secret header
     * or `google_key` query parameter.
     */
    public function handle(Request $request): Response
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];
        // Accept secret from header or query param
        $secret  = $request->header('X-Google-Webhook-Secret')
            ?? $payload['google_key'];

        if (! $this->googleAdsService->verifySecret($secret)) {
            Log::warning('[GoogleAds] Webhook secret mismatch.', ['ip' => $request->ip(), 'secret_provided' => $payload['google_key']]);

            return response('Unauthorized', 401);
        }

        // Google can send a single lead or an array wrapped in `leads`
        $leads = $payload['leads'] ?? [$payload];

        foreach ($leads as $lead) {
            if (! empty($lead)) {
                $this->googleAdsService->processLead($lead);
            }
        }

        return response('OK', 200);
    }

    /**
     * GET /admin/settings/integrations/google-ads
     */
    public function settings()
    {
        return view('google_ads::settings');
    }

    /**
     * POST /admin/settings/integrations/google-ads
     */
    public function saveSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'webhook_secret' => 'nullable|string|max:255',
        ]);

        $envPath = base_path('.env');

        if (file_exists($envPath)) {
            $content = file_get_contents($envPath);
            $value   = addslashes((string) $request->webhook_secret);

            if (preg_match('/^GOOGLE_ADS_WEBHOOK_SECRET=/m', $content)) {
                $content = preg_replace('/^GOOGLE_ADS_WEBHOOK_SECRET=.*/m', "GOOGLE_ADS_WEBHOOK_SECRET={$value}", $content);
            } else {
                $content .= "\nGOOGLE_ADS_WEBHOOK_SECRET={$value}";
            }

            file_put_contents($envPath, $content);
        }

        return redirect()
            ->route('admin.settings.integrations.google-ads')
            ->with('success', 'Google Ads settings saved.');
    }
}
