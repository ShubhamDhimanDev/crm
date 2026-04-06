<?php

namespace Webkul\Slack\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Slack\Services\LeadParser;
use Webkul\Slack\Services\SlackService;

class SlackServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load webhook and command routes
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        // Load Slack package migrations (slack_channels, users.slack_user_id, etc.)
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge slack config
        $this->mergeConfigFrom(__DIR__.'/../Config/slack.php', 'slack');

        // Register singleton services
        $this->app->singleton(SlackService::class, fn () => new SlackService);
        $this->app->singleton(LeadParser::class, fn () => new LeadParser);

        // Register the Slack event listeners
        $this->app->register(EventServiceProvider::class);
    }
}
