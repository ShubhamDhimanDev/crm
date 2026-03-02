<?php

namespace Webkul\MetaAds\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;

class MetaAdsService
{
    public function __construct(
        protected LeadRepository     $leadRepository,
        protected PersonRepository   $personRepository,
        protected SourceRepository   $sourceRepository,
        protected PipelineRepository $pipelineRepository
    ) {}

    /**
     * Verify that the incoming request is genuinely from Meta.
     *
     * Meta signs the raw POST body with HMAC-SHA256 using the App Secret.
     * The signature arrives in the X-Hub-Signature-256 header as "sha256=<hash>".
     */
    public function verifySignature(string $rawBody, string $signatureHeader): bool
    {
        $secret = config('meta_ads.app_secret');

        if (empty($secret)) {
            // Allow unsigned requests when app secret is not configured (dev mode)
            return true;
        }

        [$algo, $providedHash] = array_merge(explode('=', $signatureHeader, 2), ['', '']);

        if ($algo !== 'sha256') {
            return false;
        }

        $expectedHash = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expectedHash, $providedHash);
    }

    /**
     * Process a Meta Lead Ads payload and create a CRM lead from it.
     *
     * @param  array  $leadData  One item from `entry[*].changes[*].value`
     */
    public function processLead(array $leadData): void
    {
        try {
            $fieldData = $this->extractFieldData($leadData);

            $name        = $fieldData['full_name']    ?? ($fieldData['first_name'] ?? '') .' '.($fieldData['last_name'] ?? '');
            $name        = trim($name) ?: 'Meta Lead';
            $email       = $fieldData['email']        ?? null;
            $phone       = $fieldData['phone_number'] ?? null;
            $city        = $fieldData['city']         ?? null;
            $country     = $fieldData['country']      ?? null;

            // Deduplication (by email, then phone)
            $person = $this->findOrCreatePerson($name, $email, $phone, $city, $country);

            // Lead source (ensure "Meta Ads" source exists)
            $source = $this->sourceRepository->findOneWhere(['name' => config('meta_ads.lead_source_name', 'Meta Ads')]);

            // Default pipeline
            $pipeline = $this->pipelineRepository->first();

            $leadPayload = [
                'title'              => $name,
                'person_id'          => $person->id,
                'lead_source_id'     => $source?->id,
                'lead_pipeline_id'   => $pipeline?->id,
                'lead_pipeline_stage_id' => $pipeline?->stages->first()?->id,
                'status'             => 1,

                // A1 fields
                'campaign_name'      => $leadData['campaign_name']  ?? null,
                'ad_name'            => $leadData['ad_name']        ?? null,
                'form_name'          => $leadData['form_name']      ?? null,

                // B9 Meta-specific fields
                'meta_ad_id'         => $leadData['ad_id']          ?? null,
                'meta_adset_id'      => $leadData['adset_id']       ?? null,
                'meta_campaign_id'   => $leadData['campaign_id']    ?? null,
                'meta_form_id'       => $leadData['form_id']        ?? null,
                'meta_page_id'       => $leadData['page_id']        ?? null,
                'platform'           => $leadData['platform']       ?? 'fb',
                'source_created_at'  => isset($leadData['created_time'])
                    ? Carbon::parse($leadData['created_time'])->toDateTimeString()
                    : null,
            ];

            $lead = $this->leadRepository->create($leadPayload);

            Event::dispatch('lead.create.after', $lead);

            Log::info('[MetaAds] Lead created.', ['lead_id' => $lead->id, 'name' => $name]);

        } catch (\Throwable $e) {
            Log::error('[MetaAds] Failed to process lead.', [
                'error'   => $e->getMessage(),
                'payload' => $leadData,
            ]);
        }
    }

    /**
     * Extract flat field_data from Meta's lead payload into a key-value map.
     */
    protected function extractFieldData(array $leadData): array
    {
        $map = [];

        foreach ($leadData['field_data'] ?? [] as $field) {
            $key       = strtolower(str_replace([' ', '-'], '_', $field['name'] ?? ''));
            $map[$key] = $field['values'][0] ?? null;
        }

        return $map;
    }

    /**
     * Find an existing person by email or phone, or create a new one.
     */
    protected function findOrCreatePerson(
        string  $name,
        ?string $email,
        ?string $phone,
        ?string $city,
        ?string $country
    ): \Webkul\Contact\Models\Person {
        // Try email match first
        if ($email) {
            $match = DB::table('persons')
                ->whereRaw("JSON_SEARCH(emails, 'one', ?) IS NOT NULL", [$email])
                ->first();

            if ($match) {
                return $this->personRepository->find($match->id);
            }
        }

        // Try phone match
        if ($phone) {
            $match = DB::table('persons')
                ->whereRaw("JSON_SEARCH(contact_numbers, 'one', ?) IS NOT NULL", [$phone])
                ->first();

            if ($match) {
                return $this->personRepository->find($match->id);
            }
        }

        // Create new person
        $personData = [
            'name'            => $name,
            'emails'          => $email ? [['label' => 'work', 'value' => $email]] : [],
            'contact_numbers' => $phone ? [['label' => 'work', 'value' => $phone]] : [],
            'city'            => $city,
            'country'         => $country,
        ];

        return $this->personRepository->create($personData);
    }
}
