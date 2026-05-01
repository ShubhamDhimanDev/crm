<x-web_form::layouts>
    <x-slot:title>
        {{ strip_tags($webForm->title) }}
    </x-slot>

    @php
        $scopeSelector = '.zyro-webform-' . $webForm->id;

        $scopeCustomCss = function (string $css, string $scope) use (&$scopeCustomCss): string {
            $css = trim($css);

            if ($css === '') {
                return '';
            }

            $length = strlen($css);
            $index = 0;
            $result = '';

            while ($index < $length) {
                while ($index < $length && ctype_space($css[$index])) {
                    $index++;
                }

                if ($index >= $length) {
                    break;
                }

                $selectorStart = $index;

                while ($index < $length && $css[$index] !== '{') {
                    $index++;
                }

                if ($index >= $length) {
                    break;
                }

                $selector = trim(substr($css, $selectorStart, $index - $selectorStart));
                $index++;

                $depth = 1;
                $blockStart = $index;

                while ($index < $length && $depth > 0) {
                    if ($css[$index] === '{') {
                        $depth++;
                    } elseif ($css[$index] === '}') {
                        $depth--;
                    }

                    $index++;
                }

                $block = substr($css, $blockStart, $index - $blockStart - 1);

                if ($selector === '') {
                    continue;
                }

                if (str_starts_with($selector, '@')) {
                    if (preg_match('/^@(media|supports|container|layer)\b/i', $selector)) {
                        $result .= $selector . '{' . $scopeCustomCss($block, $scope) . '}';
                    } else {
                        $result .= $selector . '{' . $block . '}';
                    }

                    continue;
                }

                $scopedSelectors = collect(explode(',', $selector))
                    ->map(fn ($part) => trim($part))
                    ->filter()
                    ->map(function ($part) use ($scope) {
                        if (str_starts_with($part, $scope)) {
                            return $part;
                        }

                        if (str_starts_with($part, ':root')) {
                            return preg_replace('/^:root\b/', $scope, $part);
                        }

                        if (str_starts_with($part, '&')) {
                            return preg_replace('/^&/', $scope, $part);
                        }

                        return $scope . ' ' . $part;
                    })
                    ->implode(', ');

                $result .= $scopedSelectors . '{' . $block . '}';
            }

            return $result;
        };

        $scopedCustomCss = ! empty($webForm->custom_css)
            ? $scopeCustomCss($webForm->custom_css, $scopeSelector)
            : '';
    @endphp

    <!-- Web Form -->
    <v-web-form>
        <div class="flex h-[100vh] items-center justify-center">
            <div class="flex flex-col items-center gap-5">
                <x-web_form::spinner />
            </div>
        </div>
    </v-web-form>

    @pushOnce('scripts')
        @if (! empty($scopedCustomCss))
            <style>
                {{ $scopedCustomCss }}
            </style>
        @endif

        <script
            type="text/template"
            id="v-web-form-template"
        >
            <div
                class="zyro-webform-root zyro-webform-{{ $webForm->id }} flex h-[100vh] items-center justify-center"
                style="background-color: {{ $webForm->background_color }}"
            >
                <div class="zyro-webform-shell flex flex-col items-center gap-5">
                    <!-- Logo -->
                    <!--<img
                        class="w-max"
                        src="{{ vite()->asset('images/logo.svg') }}"
                        alt="{{ config('app.name') }}"
                    />-->

                    <h1
                        class="zyro-webform-title text-2xl font-bold"
                        style="color: {{ $webForm->form_title_color }} !important;"
                    >
                        {{ $webForm->title }}
                    </h1>

                    <p class="zyro-webform-description mt-2 text-base text-gray-600">{{ $webForm->description }}</p>

                    <div
                        class="zyro-webform-card box-shadow flex min-w-[300px] flex-col rounded-lg bg-white dark:bg-gray-900"
                        style="background-color: {{ $webForm->form_background_color }}"
                    >
                        {!! view_render_event('web_forms.web_forms.form_controls.before', ['webForm' => $webForm]) !!}

                        <!-- Webform Form -->
                        <x-web_form::form
                            v-slot="{ meta, values, errors, handleSubmit }"
                            as="div"
                            ref="modalForm"
                        >
                            <form
                                class="zyro-webform-form"
                                @submit="handleSubmit($event, create)"
                                ref="webForm"
                            >
                                @include('web_form::settings.web-forms.controls')

                                <div class="zyro-webform-actions flex justify-center">
                                    <x-web_form::button
                                        class="primary-button zyro-webform-submit"
                                        :title="$webForm->submit_button_label"
                                        ::loading="isStoring"
                                        ::disabled="isStoring"
                                        style="background-color: {{ $webForm->form_submit_button_color }} !important"
                                    />
                                </div>
                            </form>
                        </x-web_form::form>

                        {!! view_render_event('web_forms.web_forms.form_controls.after', ['webForm' => $webForm]) !!}
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-web-form', {
                template: '#v-web-form-template',

                data() {
                    return {
                        isStoring: false,
                    };
                },

                mounted() {
                    this.bindConditionalVisibility();
                    this.updateConditionalVisibility();
                },

                methods: {
                    bindConditionalVisibility() {
                        this.$refs.webForm?.addEventListener('change', this.updateConditionalVisibility);
                        this.$refs.webForm?.addEventListener('input', this.updateConditionalVisibility);
                    },

                    updateConditionalVisibility() {
                        const wrappers = this.$refs.webForm?.querySelectorAll('.js-webform-attribute') || [];

                        wrappers.forEach((wrapper) => {
                            const dependsOnAttributeId = wrapper.dataset.dependsOnAttributeId;
                            const dependsOnValue = (wrapper.dataset.dependsOnValue || '').trim();
                            const isHiddenByDefault = wrapper.dataset.isHidden === '1';

                            if (! dependsOnAttributeId) {
                                this.toggleWrapperVisibility(wrapper, ! isHiddenByDefault);

                                return;
                            }

                            const sourceWrapper = this.$refs.webForm.querySelector(
                                `.js-webform-attribute[data-webform-attribute-id="${dependsOnAttributeId}"]`
                            );

                            if (! sourceWrapper) {
                                this.toggleWrapperVisibility(wrapper, false);

                                return;
                            }

                            const sourceValues = this.getWrapperFieldValues(sourceWrapper);

                            const shouldShow = dependsOnValue
                                ? sourceValues.includes(dependsOnValue)
                                : sourceValues.some(value => value !== '');

                            this.toggleWrapperVisibility(wrapper, shouldShow);
                        });
                    },

                    getWrapperFieldValues(wrapper) {
                        const fields = wrapper.querySelectorAll('select, input:not([type="hidden"]), textarea');

                        const values = [];

                        fields.forEach((field) => {
                            if (field.tagName === 'SELECT' && field.multiple) {
                                Array.from(field.selectedOptions).forEach((option) => {
                                    values.push(String(option.value).trim());
                                });

                                return;
                            }

                            if (field.type === 'checkbox' || field.type === 'radio') {
                                if (field.checked) {
                                    values.push(String(field.value).trim());
                                }

                                return;
                            }

                            values.push(String(field.value ?? '').trim());
                        });

                        return values;
                    },

                    toggleWrapperVisibility(wrapper, shouldShow) {
                        wrapper.style.display = shouldShow ? '' : 'none';

                        const fields = wrapper.querySelectorAll('select, input, textarea');

                        fields.forEach((field) => {
                            field.disabled = ! shouldShow;
                        });
                    },

                    validateVisibleConditionalRequiredFields(setErrors) {
                        const wrappers = this.$refs.webForm?.querySelectorAll('.js-webform-attribute') || [];

                        const validationErrors = {};

                        wrappers.forEach((wrapper) => {
                            const isConditional = wrapper.dataset.isConditional === '1';
                            const isRequired = wrapper.dataset.isRequired === '1';
                            const isVisible = wrapper.style.display !== 'none';

                            if (! isConditional || ! isRequired || ! isVisible) {
                                return;
                            }

                            const fields = Array.from(wrapper.querySelectorAll('select, input, textarea'))
                                .filter((field) => field.name)
                                .filter((field) => field.type !== 'hidden')
                                .filter((field) => ! field.disabled);

                            if (! fields.length) {
                                return;
                            }

                            const isFilled = fields.some((field) => {
                                if (field.type === 'checkbox' || field.type === 'radio') {
                                    return field.checked;
                                }

                                if (field.type === 'file') {
                                    return Boolean(field.files?.length);
                                }

                                if (field.tagName === 'SELECT' && field.multiple) {
                                    return Array.from(field.selectedOptions).some((option) => option.value !== '');
                                }

                                return String(field.value ?? '').trim() !== '';
                            });

                            if (isFilled) {
                                return;
                            }

                            validationErrors[fields[0].name] = 'This field is required.';
                        });

                        if (Object.keys(validationErrors).length) {
                            setErrors(validationErrors);

                            return false;
                        }

                        return true;
                    },

                    create(params, { resetForm, setErrors }) {
                        this.isStoring = true;

                        if (! this.validateVisibleConditionalRequiredFields(setErrors)) {
                            this.isStoring = false;

                            return;
                        }

                        const formData = new FormData(this.$refs.webForm);

                        const utmParams = new URLSearchParams(window.location.search);

                        const utmFields = [
                            'utm_source',
                            'utm_medium',
                            'utm_campaign',
                            'utm_id',
                            'utm_term',
                            'utm_content',
                        ];

                        utmFields.forEach((field) => {
                            const value = utmParams.get(field);

                            if (value) {
                                formData.set(field, value);
                            }
                        });

                        let inputNames = Array.from(formData.keys());

                        inputNames = inputNames.reduce((acc, name) => {
                            const dotName = name.replace(/\[([^\]]+)\]/g, '.$1');

                            acc[dotName] = name;

                            return acc;
                        }, {});

                        this.$axios
                            .post('{{ route('admin.settings.web_forms.form_store', $webForm->id) }}', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data',
                                },
                            })
                            .then(response => {
                                resetForm();

                                this.$refs.webForm.reset();

                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                            })
                            .catch(error => {
                                if (error.response.data.redirect) {
                                    window.location.href = error.response.data.redirect;

                                    return;
                                }

                                if (! error.response.data.errors) {
                                    this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });

                                    return;
                                }

                                const laravelErrors = error.response.data.errors || {};
                                const mappedErrors = {};

                                for (
                                    const [dotKey, messages]
                                    of Object.entries(laravelErrors)
                                ) {
                                    const inputName = inputNames[dotKey];

                                    if (
                                        inputName
                                        && messages.length
                                    ) {
                                        mappedErrors[inputName] = messages[0];
                                    }
                                }

                                setErrors(mappedErrors);
                            })
                            .finally(() => {
                                this.isStoring = false;
                            });
                    }
                }
            });
        </script>
    @endPushOnce
</x-web_form::layouts>
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.body.style.setProperty("background", "transparent", "important");
    document.body.style.setProperty("background-color", "transparent", "important");

    const params = new URLSearchParams(window.location.search);

    const utmData = {
        utm_source: params.get("utm_source"),
        utm_medium: params.get("utm_medium"),
        utm_campaign: params.get("utm_campaign"),
        utm_id: params.get("utm_id"),
        utm_term: params.get("utm_term"),
        utm_content: params.get("utm_content"),
    };

    console.log("UTM Data:", utmData);
});
</script>
