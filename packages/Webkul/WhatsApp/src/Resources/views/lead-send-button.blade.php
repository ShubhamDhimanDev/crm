{{--
    WhatsApp Send Button + Modal
    Include this partial inside the lead detail view to allow sending WhatsApp messages.
    Usage: @include('whatsapp::lead-send-button', ['lead' => $lead])
--}}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-whatsapp-send-template"
    >
        <div>
            <!-- Send WhatsApp Button -->
            <button
                type="button"
                class="flex items-center gap-1.5 rounded-md border border-green-500 bg-white px-3 py-1.5 text-sm font-medium text-green-600 transition hover:bg-green-50 dark:border-green-600 dark:bg-gray-900 dark:text-green-400 dark:hover:bg-gray-800"
                @click="open = true"
            >
                <span class="text-base">💬</span>
                @lang('admin::app.leads.view.whatsapp.send-btn')
            </button>

            <!-- Modal -->
            <x-admin::modal ::is-open="open">
                <x-slot:header>
                    <div class="text-base font-semibold dark:text-white">
                        @lang('admin::app.leads.view.whatsapp.modal-title')
                    </div>
                </x-slot>

                <x-slot:content>
                    <div class="flex flex-col gap-4 p-4">
                        <!-- Phone Number -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                @lang('admin::app.leads.view.whatsapp.phone-label')
                            </label>

                            <input
                                type="tel"
                                v-model="phone"
                                placeholder="+919876543210"
                                class="block w-full rounded-md border border-gray-200 px-3 py-2 text-sm focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            />
                        </div>

                        <!-- Message -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                @lang('admin::app.leads.view.whatsapp.message-label')
                            </label>

                            <textarea
                                v-model="message"
                                rows="4"
                                :placeholder="'@lang('admin::app.leads.view.whatsapp.message-placeholder')'"
                                class="block w-full rounded-md border border-gray-200 px-3 py-2 text-sm focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            ></textarea>
                        </div>

                        <!-- Success / Error -->
                        <div v-if="result" :class="result.success ? 'text-green-600' : 'text-red-600'" class="text-sm">
                            @{{ result.message }}
                        </div>
                    </div>
                </x-slot>

                <x-slot:footer>
                    <div class="flex justify-end gap-2 px-4 pb-4">
                        <button
                            type="button"
                            class="secondary-button"
                            @click="open = false; result = null"
                        >
                            @lang('admin::app.leads.view.whatsapp.cancel-btn')
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            :disabled="sending || !phone || !message"
                            @click="send"
                        >
                            <span v-if="sending">@lang('admin::app.leads.view.whatsapp.sending')</span>
                            <span v-else>@lang('admin::app.leads.view.whatsapp.send-btn')</span>
                        </button>
                    </div>
                </x-slot>
            </x-admin::modal>
        </div>
    </script>

    <script type="module">
        app.component('v-whatsapp-send', {
            template: '#v-whatsapp-send-template',

            props: {
                leadId:        { type: Number, required: true },
                defaultPhone:  { type: String, default: '' },
            },

            data() {
                return {
                    open:    false,
                    sending: false,
                    phone:   this.defaultPhone,
                    message: '',
                    result:  null,
                };
            },

            methods: {
                async send() {
                    if (! this.phone || ! this.message) return;

                    this.sending = true;
                    this.result  = null;

                    try {
                        const response = await fetch(`{{ url('admin/leads') }}/${this.leadId}/whatsapp/send`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            },
                            body: JSON.stringify({ phone: this.phone, message: this.message }),
                        });

                        this.result = await response.json();

                        if (this.result.success) {
                            this.message = '';

                            setTimeout(() => { this.open = false; this.result = null; }, 1500);
                        }
                    } catch (err) {
                        this.result = { success: false, message: 'Network error. Please try again.' };
                    } finally {
                        this.sending = false;
                    }
                },
            },
        });
    </script>
@endPushOnce

<v-whatsapp-send
    :lead-id="{{ $lead->id }}"
    default-phone="{{ optional($lead->person)->contact_numbers[0]['value'] ?? '' }}"
></v-whatsapp-send>
