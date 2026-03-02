<?php

namespace Webkul\GoogleAds\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\GoogleAds\Http\Controllers\GoogleAdsWebhookController;
use Webkul\GoogleAds\Services\GoogleAdsService;

class GoogleAdsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'google_ads');

        // Admin settings routes
        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path', 'admin'))
            ->group(function () {
                Route::get(
                    'settings/integrations/google-ads',
                    [GoogleAdsWebhookController::class, 'settings']
                )->name('admin.settings.integrations.google-ads');

                Route::post(
                    'settings/integrations/google-ads',
                    [GoogleAdsWebhookController::class, 'saveSettings']
                )->name('admin.settings.integrations.google-ads.save');
            });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/google_ads.php', 'google_ads');

        $this->app->singleton(GoogleAdsService::class, fn ($app) => new GoogleAdsService(
            $app->make(\Webkul\Lead\Repositories\LeadRepository::class),
            $app->make(\Webkul\Contact\Repositories\PersonRepository::class),
            $app->make(\Webkul\Lead\Repositories\SourceRepository::class),
            $app->make(\Webkul\Lead\Repositories\PipelineRepository::class),
        ));
    }
}
