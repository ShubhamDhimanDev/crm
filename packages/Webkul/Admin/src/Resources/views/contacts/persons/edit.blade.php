
<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.contacts.persons.edit.title')
    </x-slot>

    {!! view_render_event('admin.persons.edit.form.before') !!}

    <x-admin::form
        :action="route('admin.contacts.persons.update', $person->id)"
        method="PUT"
        enctype="multipart/form-data"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    {!! view_render_event('admin.persons.edit.breadcrumbs.before') !!}

                    <x-admin::breadcrumbs
                        name="contacts.persons.edit"
                        :entity="$person"
                    />

                    {!! view_render_event('admin.persons.edit.breadcrumbs.after') !!}

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.contacts.persons.edit.title')
                    </div>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <!--  Save button for Person -->
                    <div class="flex items-center gap-x-2.5">
                        {!! view_render_event('admin.persons.edit.save_button.before') !!}

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.contacts.persons.edit.save-btn')
                        </button>

                        {!! view_render_event('admin.persons.edit.save_button.after') !!}
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                {!! view_render_event('admin.contacts.persons.edit.form_controls.before') !!}

                <x-admin::attributes
                    :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                        ['code', 'NOTIN', ['organization_id']],
                        'entity_type' => 'persons',
                    ])"
                    :custom-validations="[
                        'name' => [
                            'min:2',
                            'max:100',
                        ],
                        'job_title' => [
                            'max:100',
                        ],
                    ]"
                    :entity="$person"
                />

                <v-organization></v-organization>

                <!-- Address & Additional Contact Info -->
                <div class="mt-4 flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <p class="text-base font-semibold dark:text-white">
                            @lang('admin::app.contacts.persons.edit.address-section')
                        </p>
                    </div>

                    <div class="flex gap-4 max-sm:flex-wrap">
                        <div class="w-full">
                            <!-- Alternate Phone -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.contacts.persons.edit.phone-alt')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="tel"
                                    name="phone_alt"
                                    value="{{ $person->phone_alt }}"
                                    :label="trans('admin::app.contacts.persons.edit.phone-alt')"
                                />
                            </x-admin::form.control-group>

                            <!-- Website -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.contacts.persons.edit.website')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="website"
                                    value="{{ $person->website }}"
                                    :label="trans('admin::app.contacts.persons.edit.website')"
                                />
                            </x-admin::form.control-group>

                            <!-- City -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.contacts.persons.edit.city')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="city"
                                    value="{{ $person->city }}"
                                    :label="trans('admin::app.contacts.persons.edit.city')"
                                />
                            </x-admin::form.control-group>
                        </div>

                        <div class="w-full">
                            <!-- State -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.contacts.persons.edit.state')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="state"
                                    value="{{ $person->state }}"
                                    :label="trans('admin::app.contacts.persons.edit.state')"
                                />
                            </x-admin::form.control-group>

                            <!-- Country -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.contacts.persons.edit.country')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="country"
                                    value="{{ $person->country }}"
                                    :label="trans('admin::app.contacts.persons.edit.country')"
                                />
                            </x-admin::form.control-group>

                            <!-- Pincode -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.contacts.persons.edit.pincode')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="pincode"
                                    value="{{ $person->pincode }}"
                                    :label="trans('admin::app.contacts.persons.edit.pincode')"
                                />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>

                {!! view_render_event('admin.contacts.persons.edit.form_controls.after') !!}
            </div>
        </div>
    </x-admin::form>

    {!! view_render_event('admin.persons.edit.form.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-organization-template"
        >
            <div>
                <x-admin::attributes
                    :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                        ['code', 'IN', ['organization_id']],
                        'entity_type' => 'persons',
                    ])"
                    :entity="$person"
                />

                <template v-if="organizationName">
                    <x-admin::form.control-group.control
                        type="hidden"
                        name="organization_name"
                        v-model="organizationName"
                    />
                </template>
            </div>
        </script>

        <script type="module">
            app.component('v-organization', {
                template: '#v-organization-template',

                data() {
                    return {
                        organizationName: null,
                    };
                },

                methods: {
                    handleLookupAdded(event) {
                        this.organizationName = event?.name || null;
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
