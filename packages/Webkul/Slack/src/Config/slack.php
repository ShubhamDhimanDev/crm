<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Slack Bot Token
    |--------------------------------------------------------------------------
    |
    | The Bot User OAuth Token from your Slack App. Starts with xoxb-.
    | Required for posting messages to Slack channels.
    |
    */

    'bot_token' => env('SLACK_BOT_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Slack Signing Secret
    |--------------------------------------------------------------------------
    |
    | Used to verify that inbound webhook requests genuinely come from Slack.
    | Found under your Slack App's "Basic Information" page.
    |
    */

    'signing_secret' => env('SLACK_SIGNING_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Notification Channel
    |--------------------------------------------------------------------------
    |
    | The default Slack channel (e.g. #leads, #sales) where CRM notifications
    | will be posted. Use the channel ID (C0123ABCD) for best reliability.
    |
    */

    'notification_channel' => env('SLACK_NOTIFICATION_CHANNEL', '#leads'),

    /*
    |--------------------------------------------------------------------------
    | Lead Capture
    |--------------------------------------------------------------------------
    |
    | When enabled, the bot listens for messages starting with "lead:" in any
    | channel it is a member of and automatically creates a CRM lead.
    |
    */

    'lead_capture_enabled' => env('SLACK_LEAD_CAPTURE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Outbound Notifications
    |--------------------------------------------------------------------------
    |
    | Toggle individual CRM→Slack notification types.
    |
    */

    'notifications' => [
        'lead_created'       => env('SLACK_NOTIFY_LEAD_CREATED', true),
        'lead_updated'       => env('SLACK_NOTIFY_LEAD_UPDATED', false),
        'lead_stage_changed' => env('SLACK_NOTIFY_LEAD_STAGE_CHANGED', true),
    ],

];
