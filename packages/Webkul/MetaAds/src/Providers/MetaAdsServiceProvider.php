<?php

namespace Webkul\MetaAds\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\MetaAds\Http\Controllers\MetaAdsWebhookController;
use Webkul\MetaAds\Services\MetaAdsService;

class MetaAdsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Public webhook routes (CSRF-exempt — see VerifyCsrfToken)
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        // Package migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Blade views
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'meta_ads');

        // Admin settings routes (protected by admin middleware)
        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path', 'admin'))
            ->group(function () {
                Route::get(
                    'settings/integrations/meta-ads',
                    [MetaAdsWebhookController::class, 'settings']
                )->name('admin.settings.integrations.meta-ads');

                Route::post(
                    'settings/integrations/meta-ads',
                    [MetaAdsWebhookController::class, 'saveSettings']
                )->name('admin.settings.integrations.meta-ads.save');
            });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/meta_ads.php', 'meta_ads');

        $this->app->singleton(MetaAdsService::class, fn ($app) => new MetaAdsService(
            $app->make(\Webkul\Lead\Repositories\LeadRepository::class),
            $app->make(\Webkul\Contact\Repositories\PersonRepository::class),
            $app->make(\Webkul\Lead\Repositories\SourceRepository::class),
            $app->make(\Webkul\Lead\Repositories\PipelineRepository::class),
        ));
    }
}
