<?php

namespace Webkul\WhatsApp\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp text message to a phone number.
     *
     * @param  string  $to    Recipient phone number in E.164 format (e.g. +919876543210)
     * @param  string  $body  Message text
     */
    public function sendMessage(string $to, string $body): array
    {
        $provider = config('whatsapp.provider', 'meta');

        return match ($provider) {
            'meta'      => $this->sendViaMeta($to, $body),
            'twilio'    => $this->sendViaTwilio($to, $body),
            '360dialog' => $this->sendVia360Dialog($to, $body),
            default     => throw new \InvalidArgumentException("Unsupported WhatsApp provider: {$provider}"),
        };
    }

    /**
     * Send via Meta Cloud API (Graph API v18.0).
     */
    protected function sendViaMeta(string $to, string $body): array
    {
        $phoneNumberId = config('whatsapp.from_number');
        $apiKey        = config('whatsapp.api_key');
        $baseUrl       = config('whatsapp.api_base_urls.meta');

        $response = Http::withToken($apiKey)
            ->post("{$baseUrl}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $body],
            ]);

        $data = $response->json();

        if (! $response->successful()) {
            Log::error('[WhatsApp] Meta send failed.', ['response' => $data, 'to' => $to]);
        }

        return $data ?? [];
    }

    /**
     * Send via Twilio (WhatsApp Sandbox / Business).
     */
    protected function sendViaTwilio(string $to, string $body): array
    {
        [$accountSid, $authToken] = explode(':', config('whatsapp.api_key') . ':', 2);
        $from     = 'whatsapp:'.config('whatsapp.from_number');
        $baseUrl  = config('whatsapp.api_base_urls.twilio');
        $endpoint = "{$baseUrl}/Accounts/{$accountSid}/Messages.json";

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post($endpoint, [
                'From' => $from,
                'To'   => 'whatsapp:'.$to,
                'Body' => $body,
            ]);

        $data = $response->json();

        if (! $response->successful()) {
            Log::error('[WhatsApp] Twilio send failed.', ['response' => $data, 'to' => $to]);
        }

        return $data ?? [];
    }

    /**
     * Send via 360dialog Business API.
     */
    protected function sendVia360Dialog(string $to, string $body): array
    {
        $apiKey  = config('whatsapp.api_key');
        $baseUrl = config('whatsapp.api_base_urls.360dialog');

        $response = Http::withHeaders(['D360-API-KEY' => $apiKey])
            ->post("{$baseUrl}/messages", [
                'to'   => $to,
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        $data = $response->json();

        if (! $response->successful()) {
            Log::error('[WhatsApp] 360dialog send failed.', ['response' => $data, 'to' => $to]);
        }

        return $data ?? [];
    }

    /**
     * Verify an incoming Meta WhatsApp webhook signature.
     */
    public function verifyMetaSignature(string $rawBody, string $signature): bool
    {
        $secret = config('whatsapp.api_key');

        if (empty($secret)) {
            return true;
        }

        [$algo, $hash] = array_merge(explode('=', $signature, 2), ['', '']);

        return $algo === 'sha256' && hash_equals(hash_hmac('sha256', $rawBody, $secret), $hash);
    }
}
