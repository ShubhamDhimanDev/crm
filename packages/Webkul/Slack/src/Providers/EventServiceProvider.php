<?php

namespace Webkul\Slack\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event-to-listener mappings.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        'lead.create.after' => [
            'Webkul\Slack\Listeners\LeadEventListener@afterCreate',
        ],

        'lead.update.after' => [
            'Webkul\Slack\Listeners\LeadEventListener@afterUpdate',
        ],
    ];
}
