<?php

namespace Webkul\WebForm\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\WebForm\Http\Requests\WebForm;
use Webkul\WebForm\Repositories\WebFormRepository;

class WebFormController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected WebFormRepository $webFormRepository,
        protected PersonRepository $personRepository,
        protected LeadRepository $leadRepository,
        protected PipelineRepository $pipelineRepository,
        protected SourceRepository $sourceRepository,
        protected TypeRepository $typeRepository,
    ) {}

    /**
     * Remove the specified email template from storage.
     */
    public function formJS(string $formId): Response
    {
        $webForm = $this->webFormRepository->findOneByField('form_id', $formId);

        return response()->view('web_form::settings.web-forms.embed', compact('webForm'))
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * Remove the specified email template from storage.
     */
    public function formStore(int $id): JsonResponse
    {
        $person = $this->personRepository
            ->getModel()
            ->where('emails', 'like', '%'.request('persons.emails.0.value').'%')
            ->first();

        if ($person) {
            request()->request->add(['persons' => array_merge(request('persons'), ['id' => $person->id])]);
        }

        app(WebForm::class);

        $webForm = $this->webFormRepository->findOrFail($id);

        if ($webForm->create_lead) {
            request()->request->add(['entity_type' => 'leads']);

            Event::dispatch('lead.create.before');

            $data = request('leads');

            $utmData = $this->getUtmData();

            $data['entity_type'] = 'leads';

            $data['person'] = request('persons');

            $data['status'] = 1;

            $pipeline = $this->pipelineRepository->getDefaultPipeline();

            $stage = $pipeline->stages()->first();

            $data['lead_pipeline_id'] = $pipeline->id;

            $data['lead_pipeline_stage_id'] = $stage->id;

            $data['title'] = request('leads.title') ?: 'Lead From Web Form';

            $data['lead_value'] = request('leads.lead_value') ?: 0;

            if (! empty($utmData['utm_campaign']) && empty($data['campaign_name'])) {
                $data['campaign_name'] = $utmData['utm_campaign'];
            }

            if (! empty($utmData['utm_content']) && empty($data['ad_name'])) {
                $data['ad_name'] = $utmData['utm_content'];
            }

            if (! empty($utmData['utm_id']) && empty($data['form_name'])) {
                $data['form_name'] = $utmData['utm_id'];
            }

            foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_term', 'utm_content'] as $utmField) {
                if (! empty($utmData[$utmField])) {
                    $data[$utmField] = $utmData[$utmField];
                }
            }

            if (! request('leads.lead_source_id')) {
                $mappedSourceId = $this->resolveLeadSourceIdFromUtm($utmData['utm_source'] ?? null);

                $source = $mappedSourceId
                    ? $this->sourceRepository->find($mappedSourceId)
                    : $this->sourceRepository->findOneByField('name', 'Web Form');

                if (! $source) {
                    $source = $this->sourceRepository->first();
                }

                $data['lead_source_id'] = $source->id;
            }

            $data['lead_type_id'] = request('leads.lead_type_id') ?: $this->typeRepository->first()->id;

            $lead = $this->leadRepository->create($data);

            Event::dispatch('lead.create.after', $lead);
        } else {
            if (! $person) {
                Event::dispatch('contacts.person.create.before');

                $data = request('persons');

                request()->request->add(['entity_type' => 'persons']);

                $data['entity_type'] = 'persons';

                $person = $this->personRepository->create($data);

                Event::dispatch('contacts.person.create.after', $person);
            }
        }

        if ($webForm->submit_success_action == 'message') {
            return response()->json([
                'message' => $webForm->submit_success_content,
            ], 200);
        } else {
            return response()->json([
                'redirect' => $webForm->submit_success_content,
            ], 301);
        }
    }

    /**
     * Remove the specified email template from storage.
     */
    public function preview(string $id): View
    {
        $webForm = $this->webFormRepository->findOneByField('form_id', $id);

        if (is_null($webForm)) {
            abort(404);
        }

        return view('web_form::settings.web-forms.preview', compact('webForm'));
    }

    /**
     * Get normalized UTM params from the request.
     */
    private function getUtmData(): array
    {
        $utmData = request()->only([
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_id',
            'utm_term',
            'utm_content',
        ]);

        foreach ($utmData as $key => $value) {
            $utmData[$key] = is_string($value) ? trim($value) : $value;
        }

        return $utmData;
    }

    /**
     * Resolve lead source id from UTM source using live lead source names.
     */
    private function resolveLeadSourceIdFromUtm(?string $utmSource): ?int
    {
        if (empty($utmSource)) {
            return null;
        }

        $normalized = strtolower(trim($utmSource));

        // Allow direct mapping when utm_source already matches an existing source name.
        $sourceId = $this->findLeadSourceIdByNames([$utmSource]);

        if ($sourceId) {
            return $sourceId;
        }

        if (preg_match('/google|adwords|youtube|gdn|display/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Google Ads']);
        }

        if (preg_match('/facebook|instagram|meta|\bfb\b|\big\b/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Meta Ads']);
        }

        if (preg_match('/website|site/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Website', 'Web']);
        }

        if (preg_match('/\bweb\b/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Web', 'Website']);
        }

        if (preg_match('/email|newsletter|mailchimp/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Email']);
        }

        if (preg_match('/slack/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Slack']);
        }

        if (preg_match('/referral|partner|affiliate/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Referral']);
        }

        if (preg_match('/phone|call/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Phone', 'Cold Call']);
        }

        if (preg_match('/manual/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Manual Entry']);
        }

        if (preg_match('/direct/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Direct']);
        }

        if (preg_match('/exhibition|expo|event/', $normalized)) {
            return $this->findLeadSourceIdByNames(['Exhibition']);
        }

        return $this->findLeadSourceIdByNames(['Other']);
    }

    /**
     * Find lead source id by candidate source names.
     */
    private function findLeadSourceIdByNames(array $candidateNames): ?int
    {
        $sources = $this->sourceRepository->all(['id', 'name'])
            ->mapWithKeys(fn ($source) => [strtolower(trim($source->name)) => $source->id]);

        foreach ($candidateNames as $name) {
            $key = strtolower(trim($name));

            if (isset($sources[$key])) {
                return (int) $sources[$key];
            }
        }

        return null;
    }

    /**
     * Preview the web form from datagrid.
     */
    public function view(int $id): View
    {
        $webForm = $this->webFormRepository->findOneByField('id', $id);

        request()->merge(['id' => $webForm->form_id]);

        if (is_null($webForm)) {
            abort(404);
        }

        return view('web_form::settings.web-forms.preview', compact('webForm'));
    }
}
