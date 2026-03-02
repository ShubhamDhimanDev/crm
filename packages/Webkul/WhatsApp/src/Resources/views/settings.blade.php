<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.integrations.whatsapp.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.integrations.whatsapp" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('admin::app.settings.integrations.whatsapp.title')
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 lg:flex-row">
            <!-- Left: Webhook Info -->
            <div class="flex-1 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold dark:text-white">
                    @lang('admin::app.settings.integrations.whatsapp.webhook-section')
                </h2>

                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    @lang('admin::app.settings.integrations.whatsapp.webhook-info')
                </p>

                <!-- Inbound Webhook URL -->
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        @lang('admin::app.settings.integrations.whatsapp.webhook-url')
                    </label>

                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            value="{{ route('whatsapp.webhook.receive') }}"
                            readonly
                            class="block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        />

                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ route('whatsapp.webhook.receive') }}')"
                            class="shrink-0 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            @lang('admin::app.settings.integrations.copy')
                        </button>
                    </div>
                </div>

                <!-- Supported Providers -->
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300">
                    <p class="mb-2 font-semibold">@lang('admin::app.settings.integrations.whatsapp.providers-title')</p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Meta Cloud API (Facebook / WhatsApp Business)</li>
                        <li>Twilio WhatsApp</li>
                        <li>360dialog</li>
                    </ul>
                </div>
            </div>

            <!-- Right: Configuration Form -->
            <div class="flex-1 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold dark:text-white">
                    @lang('admin::app.settings.integrations.whatsapp.config-section')
                </h2>

                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
                        {{ session('success') }}
                    </div>
                @endif

                <x-admin::form
                    :action="route('admin.settings.integrations.whatsapp.save')"
                    method="POST"
                >
                    <!-- Provider -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.integrations.whatsapp.provider')
                        </x-admin::form.control-group.label>

                        <select
                            name="provider"
                            class="block w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        >
                            <option value="meta"      {{ config('whatsapp.provider') === 'meta'      ? 'selected' : '' }}>Meta Cloud API</option>
                            <option value="twilio"    {{ config('whatsapp.provider') === 'twilio'    ? 'selected' : '' }}>Twilio</option>
                            <option value="360dialog" {{ config('whatsapp.provider') === '360dialog' ? 'selected' : '' }}>360dialog</option>
                        </select>
                    </x-admin::form.control-group>

                    <!-- From Number -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.integrations.whatsapp.from-number')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="from_number"
                            value="{{ config('whatsapp.from_number') }}"
                            placeholder="+919876543210 or Phone Number ID"
                        />
                    </x-admin::form.control-group>

                    <!-- API Key -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.integrations.whatsapp.api-key')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="api_key"
                            value="{{ config('whatsapp.api_key') }}"
                            :placeholder="trans('admin::app.settings.integrations.whatsapp.api-key-placeholder')"
                        />
                    </x-admin::form.control-group>

                    <!-- Webhook Verify Token -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.integrations.whatsapp.verify-token')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="webhook_verify_token"
                            value="{{ config('whatsapp.webhook_verify_token') }}"
                            :placeholder="trans('admin::app.settings.integrations.whatsapp.verify-token-placeholder')"
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
