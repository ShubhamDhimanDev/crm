{!! view_render_event('admin.leads.view.attributes.before', ['lead' => $lead]) !!}

<div class="flex w-full flex-col gap-4 border-b border-gray-200 p-4 dark:border-gray-800">
    <x-admin::accordion class="select-none !border-none">
        <x-slot:header class="!p-0">
            <div class="flex w-full items-center justify-between gap-4 font-semibold dark:text-white">
                <h4>@lang('admin::app.leads.view.attributes.title')</h4>

                @if (bouncer()->hasPermission('leads.edit'))
                    <a
                        href="{{ route('admin.leads.edit', $lead->id) }}"
                        class="icon-edit rounded-md p-1.5 text-2xl transition-all hover:bg-gray-100 dark:hover:bg-gray-950"
                        target="_blank"
                    ></a>
                @endif
            </div>
        </x-slot>

        <x-slot:content class="mt-4 !px-0 !pb-0">
            {!! view_render_event('admin.leads.view.attributes.form_controls.before', ['lead' => $lead]) !!}

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form @submit="handleSubmit($event, () => {})">
                    {!! view_render_event('admin.leads.view.attributes.form_controls.attributes.view.before', ['lead' => $lead]) !!}

                    <x-admin::attributes.view
                        :custom-attributes="app('Webkul\Attribute\Repositories\AttributeRepository')->findWhere([
                            'entity_type' => 'leads',
                            ['code', 'NOTIN', ['title', 'description', 'lead_pipeline_id', 'lead_pipeline_stage_id']]
                        ])"
                        :entity="$lead"
                        :url="route('admin.leads.attributes.update', $lead->id)"
                        :allow-edit="true"
                    />

                    {!! view_render_event('admin.leads.view.attributes.form_controls.attributes.view.after', ['lead' => $lead]) !!}
                </form>
            </x-admin::form>

            {!! view_render_event('admin.leads.view.attributes.form_controls.after', ['lead' => $lead]) !!}
        </x-slot>
    </x-admin::accordion>
</div>

<!-- Additional Info Section -->
<div class="flex w-full flex-col gap-4 border-b border-gray-200 p-4 dark:border-gray-800">
    <x-admin::accordion class="select-none !border-none">
        <x-slot:header class="!p-0">
            <h4 class="font-semibold dark:text-white">
                @lang('admin::app.leads.view.additional-info.title')
            </h4>
        </x-slot>

        <x-slot:content class="mt-4 !px-0 !pb-0">
            <div class="flex flex-col gap-3">
                <!-- Priority -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.leads.view.additional-info.priority')
                    </span>

                    <span class="w-1/2 text-sm dark:text-white">
                        @if ($lead->priority === 'hot')
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-600">
                                @lang('admin::app.leads.view.additional-info.priority-hot')
                            </span>
                        @elseif ($lead->priority === 'warm')
                            <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-600">
                                @lang('admin::app.leads.view.additional-info.priority-warm')
                            </span>
                        @elseif ($lead->priority === 'cold')
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-600">
                                @lang('admin::app.leads.view.additional-info.priority-cold')
                            </span>
                        @else
                            <span class="text-gray-400">@lang('admin::app.leads.view.additional-info.not-set')</span>
                        @endif
                    </span>
                </div>

                <!-- Lead Score -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.leads.view.additional-info.lead-score')
                    </span>

                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $lead->lead_score ?? '—' }}
                    </span>
                </div>

                <!-- Industry -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.leads.view.additional-info.industry')
                    </span>

                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $lead->industry ?: '—' }}
                    </span>
                </div>

                <!-- Campaign Name -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.leads.view.additional-info.campaign-name')
                    </span>

                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $lead->campaign_name ?: '—' }}
                    </span>
                </div>

                <!-- Ad Name -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.leads.view.additional-info.ad-name')
                    </span>

                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $lead->ad_name ?: '—' }}
                    </span>
                </div>

                <!-- Form Name -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.leads.view.additional-info.form-name')
                    </span>

                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $lead->form_name ?: '—' }}
                    </span>
                </div>

                <!-- Follow-up At -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.leads.view.additional-info.followup-at')
                    </span>

                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $lead->followup_at ? $lead->followup_at->format('d M Y') : '—' }}
                    </span>
                </div>

                <!-- Last Contacted At -->
                <div class="flex gap-2">
                    <span class="w-1/2 text-sm font-medium text-gray-600 dark:text-gray-300">
                        @lang('admin::app.leads.view.additional-info.last-contacted')
                    </span>

                    <span class="w-1/2 text-sm dark:text-white">
                        {{ $lead->last_contacted_at ? $lead->last_contacted_at->format('d M Y') : '—' }}
                    </span>
                </div>
            </div>
        </x-slot>
    </x-admin::accordion>
</div>

{!! view_render_event('admin.leads.view.attributes.before', ['lead' => $lead]) !!}
