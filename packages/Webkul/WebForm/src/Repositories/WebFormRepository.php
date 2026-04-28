<?php

namespace Webkul\WebForm\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\WebForm\Contracts\WebForm;

class WebFormRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected WebFormAttributeRepository $webFormAttributeRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     *
     * @return mixed
     */
    public function model()
    {
        return WebForm::class;
    }

    /**
     * Create Web Form.
     *
     * @return \Webkul\WebForm\Contracts\WebForm
     */
    public function create(array $data)
    {
        $webForm = $this->model->create(array_merge($data, [
            'form_id' => Str::random(50),
        ]));

        $attributes = $this->normalizeAttributes($data['attributes'] ?? []);

        foreach ($attributes as $attributeData) {
            $this->webFormAttributeRepository->create(array_merge([
                'web_form_id' => $webForm->id,
            ], $attributeData));
        }

        return $webForm;
    }

    /**
     * Update Web Form.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\WebForm\Contracts\WebForm
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $webForm = parent::update($data, $id);

        $previousAttributeIds = $webForm->attributes()->pluck('id');

        $attributes = $this->normalizeAttributes($data['attributes'] ?? []);

        foreach ($attributes as $attributeId => $attributeData) {
            if (Str::contains($attributeId, 'attribute_')) {
                $this->webFormAttributeRepository->create(array_merge([
                    'web_form_id' => $webForm->id,
                ], $attributeData));
            } else {
                if (is_numeric($index = $previousAttributeIds->search($attributeId))) {
                    $previousAttributeIds->forget($index);
                }

                $this->webFormAttributeRepository->update($attributeData, $attributeId);
            }
        }

        foreach ($previousAttributeIds as $attributeId) {
            $this->webFormAttributeRepository->delete($attributeId);
        }

        return $webForm;
    }

    /**
     * Normalize sortable and toggle fields for each incoming webform attribute.
     */
    protected function normalizeAttributes(array $attributes): array
    {
        $position = 1;

        foreach ($attributes as $attributeId => $attributeData) {
            $attributes[$attributeId]['sort_order'] = (int) ($attributeData['sort_order'] ?? $position);
            $attributes[$attributeId]['is_hidden'] = (int) ($attributeData['is_hidden'] ?? 0);
            $attributes[$attributeId]['is_required'] = (int) ($attributeData['is_required'] ?? 0);
            $attributes[$attributeId]['depends_on_attribute_id'] = empty($attributeData['depends_on_attribute_id'])
                ? null
                : (int) $attributeData['depends_on_attribute_id'];
            $attributes[$attributeId]['depends_on_value'] = isset($attributeData['depends_on_value'])
                ? trim((string) $attributeData['depends_on_value'])
                : null;

            $position++;
        }

        return $attributes;
    }
}
