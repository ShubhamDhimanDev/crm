<x-admin::layouts>
    <x-slot:title>
        WhatsApp Template Test
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h1 class="text-xl font-bold dark:text-white">WhatsApp Template Test</h1>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Send Meta template <strong>hello_world</strong> to verify your integration (requires Meta credentials).
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            <x-admin::form
                :action="route('admin.settings.integrations.whatsapp.test-template.send')"
                method="POST"
            >
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        Recipient Phone (E.164)
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="phone"
                        :value="old('phone')"
                        placeholder="+919876543210"
                    />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        Language Code
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="language_code"
                        :value="old('language_code', 'en_US')"
                        placeholder="en_US"
                    />
                </x-admin::form.control-group>

                <button type="submit" class="primary-button">
                    Send hello_world
                </button>
            </x-admin::form>

            @if (session('result'))
                <div class="mt-6">
                    <h2 class="mb-2 text-sm font-semibold dark:text-white">Provider Response</h2>

                    <pre class="overflow-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-gray-800 dark:text-gray-200">{{ json_encode(session('result'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
