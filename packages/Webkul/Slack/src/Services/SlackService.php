<?php

namespace Webkul\Slack\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SlackService
{
    /**
     * Slack Web API base URL.
     */
    protected const BASE_URI = 'https://slack.com/api/';

    /**
     * The GuzzleHttp client instance.
     */
    protected Client $client;

    /**
     * Create a new SlackService instance.
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'timeout'  => 15,
            'headers'  => [
                'Authorization' => 'Bearer '.config('slack.bot_token'),
                'Content-Type'  => 'application/json; charset=utf-8',
            ],
        ]);
    }

    /**
     * Post a plain-text or Block Kit message to a Slack channel.
     *
     * @param  array  $blocks  Optional Block Kit blocks
     */
    public function postMessage(string $channel, string $text, array $blocks = []): array
    {
        $payload = [
            'channel' => $channel,
            'text'    => $text,
        ];

        if (! empty($blocks)) {
            $payload['blocks'] = $blocks;
        }

        return $this->call('chat.postMessage', $payload);
    }

    /**
     * Post a reply to a specific thread (message).
     */
    public function postReply(string $channel, string $threadTs, string $text): array
    {
        return $this->call('chat.postMessage', [
            'channel'   => $channel,
            'thread_ts' => $threadTs,
            'text'      => $text,
        ]);
    }

    /**
     * Send a rich "Lead Created" notification to the configured channel.
     *
     * @param  \Webkul\Lead\Contracts\Lead  $lead
     */
    public function postLeadCreatedNotification(mixed $lead): void
    {
        $channel = config('slack.notification_channel');

        if (! config('slack.notifications.lead_created') || empty($channel)) {
            return;
        }

        $personName = optional($lead->person)->name ?? 'Unknown';
        $ownerName  = optional($lead->user)->name ?? 'Unassigned';
        $value      = $lead->lead_value ? '$'.number_format($lead->lead_value, 2) : 'N/A';
        $source     = optional($lead->source)->name ?? 'Direct';

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type'  => 'plain_text',
                    'text'  => '🎯 New Lead: '.$lead->title,
                    'emoji' => true,
                ],
            ],
            [
                'type'   => 'section',
                'fields' => [
                    ['type' => 'mrkdwn', 'text' => "*Contact:*\n{$personName}"],
                    ['type' => 'mrkdwn', 'text' => "*Deal Value:*\n{$value}"],
                    ['type' => 'mrkdwn', 'text' => "*Assigned To:*\n{$ownerName}"],
                    ['type' => 'mrkdwn', 'text' => "*Source:*\n{$source}"],
                ],
            ],
        ];

        if (! empty($lead->description)) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Notes:*\n{$lead->description}",
                ],
            ];
        }

        $blocks[] = ['type' => 'divider'];

        $this->postMessage($channel, "🎯 New lead: {$lead->title} (Contact: {$personName})", $blocks);
    }

    /**
     * Send a "Lead Stage Changed" notification to the configured channel.
     *
     * @param  \Webkul\Lead\Contracts\Lead  $lead
     */
    public function postLeadStageChangedNotification(mixed $lead): void
    {
        $channel = config('slack.notification_channel');

        if (! config('slack.notifications.lead_stage_changed') || empty($channel)) {
            return;
        }

        $stageName  = optional($lead->stage)->name ?? 'Unknown Stage';
        $stageCode  = optional($lead->stage)->code ?? '';
        $personName = optional($lead->person)->name ?? 'Unknown';
        $title      = $lead->title;

        // Special rich notification for Won / Lost final stages
        if ($stageCode === 'won') {
            $value    = $lead->lead_value ? '$'.number_format($lead->lead_value, 2) : 'N/A';
            $closedAt = $lead->closed_at
                ? ' — Closed: '.(\Carbon\Carbon::parse($lead->closed_at)->format('M j, Y'))
                : '';
            $this->postMessage(
                $channel,
                "🏆 *{$title}* ({$personName}) marked as *Won* — Value: {$value}{$closedAt}"
            );

            return;
        }

        if ($stageCode === 'lost') {
            $reason   = ! empty($lead->lost_reason) ? " — Reason: {$lead->lost_reason}" : '';
            $closedAt = $lead->closed_at
                ? ' — Closed: '.(\Carbon\Carbon::parse($lead->closed_at)->format('M j, Y'))
                : '';
            $this->postMessage(
                $channel,
                "❌ *{$title}* ({$personName}) marked as *Lost*{$reason}{$closedAt}"
            );

            return;
        }

        $this->postMessage(
            $channel,
            "🔄 *{$title}* ({$personName}) moved to *{$stageName}*."
        );
    }

    /**
     * Send a "Lead Updated" notification.
     *
     * @param  \Webkul\Lead\Contracts\Lead  $lead
     */
    public function postLeadUpdatedNotification(mixed $lead): void
    {
        $channel = config('slack.notification_channel');

        if (! config('slack.notifications.lead_updated') || empty($channel)) {
            return;
        }

        $personName = optional($lead->person)->name ?? 'Unknown';

        $this->postMessage(
            $channel,
            "✏️ *{$lead->title}* ({$personName}) was updated."
        );
    }

    /**
     * Post a custom plain-text message to any channel.
     */
    public function postCustomMessage(string $channel, string $message): void
    {
        $this->postMessage($channel, $message);
    }

    /**
     * Open a Slack modal view using a trigger_id from a slash command or block action.
     *
     * @see https://api.slack.com/methods/views.open  (A6)
     */
    public function openModal(string $triggerId, array $view): array
    {
        return $this->call('views.open', [
            'trigger_id' => $triggerId,
            'view'       => $view,
        ]);
    }

    /**
     * Send a Direct Message to a Slack user by their Slack user ID.
     *
     * Opens (or re-uses) the DM channel via conversations.open, then
     * posts a message to it. (A8)
     *
     * @see https://api.slack.com/methods/conversations.open
     * @see https://api.slack.com/methods/chat.postMessage
     */
    public function sendDm(string $slackUserId, string $text): array
    {
        // Open (or retrieve) the DM channel for this user
        $openResult = $this->call('conversations.open', ['users' => $slackUserId]);

        if (! ($openResult['ok'] ?? false)) {
            logger()->warning('[Slack] Could not open DM channel.', [
                'slack_user_id' => $slackUserId,
                'error'         => $openResult['error'] ?? 'unknown',
            ]);

            return $openResult;
        }

        $channelId = $openResult['channel']['id'] ?? null;

        if (! $channelId) {
            return ['ok' => false, 'error' => 'no_channel_id'];
        }

        return $this->postMessage($channelId, $text);
    }

    /**
     * Call a Slack Web API method.
     */
    protected function call(string $method, array $payload): array
    {
        try {
            $response = $this->client->post($method, ['json' => $payload]);
            $body     = json_decode($response->getBody()->getContents(), true);

            if (! ($body['ok'] ?? false)) {
                logger()->warning('[Slack] API error', [
                    'method'  => $method,
                    'error'   => $body['error'] ?? 'unknown',
                    'payload' => $payload,
                ]);
            }

            return $body;
        } catch (RequestException $e) {
            logger()->error('[Slack] HTTP error', [
                'method'  => $method,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
