<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.integrations.meta-ads.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.integrations.meta-ads" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('admin::app.settings.integrations.meta-ads.title')
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <!-- Left: Webhook Info -->
            <div class="flex-1 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold dark:text-white">
                    @lang('admin::app.settings.integrations.meta-ads.webhook-section')
                </h2>

                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    @lang('admin::app.settings.integrations.meta-ads.webhook-info')
                </p>

                <!-- Webhook Callback URL -->
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        @lang('admin::app.settings.integrations.meta-ads.callback-url')
                    </label>

                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            value="{{ route('meta_ads.webhook.handle') }}"
                            readonly
                            class="block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        />

                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ route('meta_ads.webhook.handle') }}')"
                            class="shrink-0 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            @lang('admin::app.settings.integrations.copy')
                        </button>
                    </div>
                </div>

                <!-- Setup Steps -->
                <div class="rounded-md bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                    <p class="mb-2 font-semibold">@lang('admin::app.settings.integrations.meta-ads.setup-steps-title')</p>
                    <ol class="list-decimal space-y-1 pl-5">
                        <li>@lang('admin::app.settings.integrations.meta-ads.step1')</li>
                        <li>@lang('admin::app.settings.integrations.meta-ads.step2')</li>
                        <li>@lang('admin::app.settings.integrations.meta-ads.step3')</li>
                        <li>@lang('admin::app.settings.integrations.meta-ads.step4')</li>
                    </ol>
                </div>
            </div>

            <!-- Right: Configuration Form -->
            <div class="flex-1 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold dark:text-white">
                    @lang('admin::app.settings.integrations.meta-ads.config-section')
                </h2>

                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
                        {{ session('success') }}
                    </div>
                @endif

                <x-admin::form
                    :action="route('admin.settings.integrations.meta-ads.save')"
                    method="POST"
                >
                    <!-- App ID -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.integrations.meta-ads.app-id')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="app_id"
                            value="{{ config('meta_ads.app_id') }}"
                            :placeholder="trans('admin::app.settings.integrations.meta-ads.app-id-placeholder')"
                        />
                    </x-admin::form.control-group>

                    <!-- App Secret -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.integrations.meta-ads.app-secret')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="app_secret"
                            value="{{ config('meta_ads.app_secret') }}"
                            :placeholder="trans('admin::app.settings.integrations.meta-ads.app-secret-placeholder')"
                        />
                    </x-admin::form.control-group>

                    <!-- Verify Token -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.integrations.meta-ads.verify-token')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="verify_token"
                            value="{{ config('meta_ads.verify_token') }}"
                            :placeholder="trans('admin::app.settings.integrations.meta-ads.verify-token-placeholder')"
                        />

                        <p class="mt-1 text-xs text-gray-500">
                            @lang('admin::app.settings.integrations.meta-ads.verify-token-help')
                        </p>
                    </x-admin::form.control-group>

                    <!-- Pixel ID -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.integrations.meta-ads.pixel-id')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="pixel_id"
                            value="{{ config('meta_ads.pixel_id') }}"
                            :placeholder="trans('admin::app.settings.integrations.meta-ads.pixel-id-placeholder')"
                        />
                    </x-admin::form.control-group>

                    <button type="submit" class="primary-button">
                        @lang('admin::app.settings.integrations.save-btn')
                    </button>
                </x-admin::form>
            </div>
        </div>
    </div>
</x-admin::layouts>
