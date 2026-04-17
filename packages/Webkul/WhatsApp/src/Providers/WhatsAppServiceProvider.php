<?php

namespace Webkul\WhatsApp\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\WhatsApp\Http\Controllers\WhatsAppController;
use Webkul\WhatsApp\Providers\EventServiceProvider;
use Webkul\WhatsApp\Services\WhatsAppService;

class WhatsAppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Webhook routes (CSRF-exempt)
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        // Blade views
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'whatsapp');

        // Admin routes (protected)
        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path', 'admin'))
            ->group(function () {
                // Settings page
                Route::get(
                    'settings/integrations/whatsapp',
                    [WhatsAppController::class, 'settings']
                )->name('admin.settings.integrations.whatsapp');

                Route::post(
                    'settings/integrations/whatsapp',
                    [WhatsAppController::class, 'saveSettings']
                )->name('admin.settings.integrations.whatsapp.save');

                // Send WhatsApp message from lead detail
                Route::post(
                    'leads/{lead}/whatsapp/send',
                    [WhatsAppController::class, 'send']
                )->name('admin.leads.whatsapp.send');

                // Test Meta template send (hello_world)
                Route::get(
                    'settings/integrations/whatsapp/test-template',
                    [WhatsAppController::class, 'testTemplatePage']
                )->name('admin.settings.integrations.whatsapp.test-template');

                Route::post(
                    'settings/integrations/whatsapp/test-template',
                    [WhatsAppController::class, 'sendTestTemplate']
                )->name('admin.settings.integrations.whatsapp.test-template.send');
            });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/whatsapp.php', 'whatsapp');

        $this->app->singleton(WhatsAppService::class, fn () => new WhatsAppService());

        $this->app->singleton(WhatsAppController::class, fn ($app) => new WhatsAppController(
            $app->make(WhatsAppService::class),
            $app->make(ActivityRepository::class),
            $app->make(LeadRepository::class),
        ));

        // Register the lead nurture event listener
        $this->app->register(EventServiceProvider::class);
    }
}
