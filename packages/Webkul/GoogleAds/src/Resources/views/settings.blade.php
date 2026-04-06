<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.integrations.google-ads.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.integrations.google-ads" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('admin::app.settings.integrations.google-ads.title')
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <!-- Left: Webhook Info -->
            <div class="flex-1 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold dark:text-white">
                    @lang('admin::app.settings.integrations.google-ads.webhook-section')
                </h2>

                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    @lang('admin::app.settings.integrations.google-ads.webhook-info')
                </p>

                <!-- Webhook URL -->
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        @lang('admin::app.settings.integrations.google-ads.webhook-url')
                    </label>

                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            value="{{ route('google_ads.webhook.handle') }}"
                            readonly
                            class="block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        />

                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ route('google_ads.webhook.handle') }}')"
                            class="shrink-0 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            @lang('admin::app.settings.integrations.copy')
                        </button>
                    </div>
                </div>

                <!-- Setup Steps -->
                <div class="rounded-md bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                    <p class="mb-2 font-semibold">@lang('admin::app.settings.integrations.google-ads.setup-steps-title')</p>
                    <ol class="list-decimal space-y-1 pl-5">
                        <li>@lang('admin::app.settings.integrations.google-ads.step1')</li>
                        <li>@lang('admin::app.settings.integrations.google-ads.step2')</li>
                        <li>@lang('admin::app.settings.integrations.google-ads.step3')</li>
                        <li>@lang('admin::app.settings.integrations.google-ads.step4')</li>
                    </ol>
                </div>
            </div>

            <!-- Right: Configuration Form -->
            <div class="flex-1 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold dark:text-white">
                    @lang('admin::app.settings.integrations.google-ads.config-section')
                </h2>

                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
                        {{ session('success') }}
                    </div>
                @endif

                <x-admin::form
                    :action="route('admin.settings.integrations.google-ads.save')"
                    method="POST"
                >
                    <!-- Webhook Secret -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.integrations.google-ads.webhook-secret')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="webhook_secret"
                            value="{{ config('google_ads.webhook_secret') }}"
                            :placeholder="trans('admin::app.settings.integrations.google-ads.webhook-secret-placeholder')"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            @lang('admin::app.settings.integrations.google-ads.webhook-secret-help')
                        </p>
                    </x-admin::form.control-group>

                    <button type="submit" class="primary-button">
                        @lang('admin::app.settings.integrations.save-btn')
                    </button>
                </x-admin::form>
            </div>
        </div>
    </div>
</x-admin::layouts>
