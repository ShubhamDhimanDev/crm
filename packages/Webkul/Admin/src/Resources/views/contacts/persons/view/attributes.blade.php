{!! view_render_event('admin.contacts.persons.view.attributes.before', ['person' => $person]) !!}

<div class="flex w-full flex-col gap-4 border-b border-gray-200 p-4 dark:border-gray-800">
    <x-admin::accordion class="select-none !border-none">
        <x-slot:header class="!p-0">
            <h4 class="font-semibold dark:text-white">
                @lang('admin::app.contacts.persons.view.about-person')
            </h4>
        </x-slot>

        <x-slot:content class="mt-4 !px-0 !pb-0">
            {!! view_render_event('admin.contacts.persons.view.attributes.form_controls.before', ['person' => $person]) !!}

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form @submit="handleSubmit($event, () => {})">
                    {!! view_render_event('admin.contacts.persons.view.attributes.form_controls.attributes_view.before', ['person' => $person]) !!}

                    <x-admin::attributes.view
                        :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                            'entity_type' => 'persons',
                            ['code', 'NOTIN', ['name']]
                        ])"
                        :entity="$person"
                        :url="route('admin.contacts.persons.update', $person->id)"
                        :allow-edit="true"
                    />

                    {!! view_render_event('admin.contacts.persons.view.attributes.form_controls.attributes_view.after', ['person' => $person]) !!}
                </form>
            </x-admin::form>

            {!! view_render_event('admin.contacts.persons.view.attributes.form_controls.after', ['person' => $person]) !!}
        </x-slot>
    </x-admin::accordion>
</div>

<!-- Address Section -->
<div class="flex w-full flex-col gap-4 border-b border-gray-200 p-4 dark:border-gray-800">
    <x-admin::accordion class="select-none !border-none">
        <x-slot:header class="!p-0">
            <h4 class="font-semibold dark:text-white">
                @lang('admin::app.contacts.persons.view.address-section')
            </h4>
        </x-slot>

        <x-slot:content class="mt-4 !px-0 !pb-0">
            <div class="flex flex-col gap-3">
                <!-- Alternate Phone -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.contacts.persons.view.phone-alt')
                    </span>
                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $person->phone_alt ?: '—' }}
                    </span>
                </div>

                <!-- Website -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.contacts.persons.view.website')
                    </span>
                    <span class="w-1/2 text-sm dark:text-white">
                        @if ($person->website)
                            <a href="{{ $person->website }}" target="_blank" class="text-brandColor hover:underline">
                                {{ $person->website }}
                            </a>
                        @else
                            <span class="text-gray-400">@lang('admin::app.contacts.persons.view.not-set')</span>
                        @endif
                    </span>
                </div>

                <!-- City -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.contacts.persons.view.city')
                    </span>
                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $person->city ?: '—' }}
                    </span>
                </div>

                <!-- State -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.contacts.persons.view.state')
                    </span>
                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $person->state ?: '—' }}
                    </span>
                </div>

                <!-- Country -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.contacts.persons.view.country')
                    </span>
                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $person->country ?: '—' }}
                    </span>
                </div>

                <!-- Pincode -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.contacts.persons.view.pincode')
                    </span>
                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $person->pincode ?: '—' }}
                    </span>
                </div>
            </div>
        </x-slot>
    </x-admin::accordion>
</div>

{!! view_render_event('admin.contacts.persons.view.attributes.before', ['person' => $person]) !!}
